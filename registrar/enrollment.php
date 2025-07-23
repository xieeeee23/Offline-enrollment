<?php
$title = 'Student Enrollment';
$page_header = 'Student Enrollment';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user has necessary permissions
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
    exit();
}

// Get education levels
$education_levels_query = "SELECT * FROM education_levels ORDER BY id";
$education_levels_result = mysqli_query($conn, $education_levels_query);
$education_levels = [];

while ($row = mysqli_fetch_assoc($education_levels_result)) {
    $education_levels[] = $row;
}

// Get selected education level
$selected_level = isset($_GET['level']) ? cleanInput($_GET['level']) : '';
$education_level_id = 0;

// Get selected school year and semester for filtering
$selected_school_year = isset($_GET['school_year']) ? cleanInput($_GET['school_year']) : '';
$selected_semester = isset($_GET['semester']) ? cleanInput($_GET['semester']) : '';

// Get available school years
$school_years = [];
$school_years_query = "SELECT DISTINCT school_year FROM enrollment_history ORDER BY school_year DESC";
$school_years_result = mysqli_query($conn, $school_years_query);

while ($row = mysqli_fetch_assoc($school_years_result)) {
    $school_years[] = $row['school_year'];
}

// If no school years found in history, add current school year
if (empty($school_years)) {
    $current_year = date('Y');
    $school_years[] = $current_year . '-' . ($current_year + 1);
}

// If no school year selected, use the first one
if (empty($selected_school_year) && !empty($school_years)) {
    $selected_school_year = $school_years[0];
}

// Available semesters
$semesters = ['First', 'Second'];

// If no semester selected, use the first one
if (empty($selected_semester)) {
    $selected_semester = $semesters[0];
}

// Find education level ID
if (!empty($selected_level)) {
    foreach ($education_levels as $level) {
        if ($level['name'] == $selected_level) {
            $education_level_id = $level['id'];
            break;
        }
    }
}

// Get grade levels for the selected education level
$grade_levels = [];
if (!empty($selected_level)) {
    $grade_query = "SELECT DISTINCT grade_level FROM sections WHERE education_level = ? ORDER BY grade_level";
    $stmt = mysqli_prepare($conn, $grade_query);
    mysqli_stmt_bind_param($stmt, 's', $selected_level);
    mysqli_stmt_execute($stmt);
    $grade_result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grade_levels[] = $row['grade_level'];
    }
}

// Get SHS strands if education level is Senior High School
$strands = [];
if ($selected_level == 'Senior High School') {
    $strands_query = "SELECT * FROM strands ORDER BY name";
    $strands_result = mysqli_query($conn, $strands_query);
    
    while ($row = mysqli_fetch_assoc($strands_result)) {
        $strands[] = $row;
    }
}

// Get sections for the selected education level
$sections = [];
if (!empty($selected_level)) {
    $sections_query = "SELECT * FROM sections WHERE education_level = ? ORDER BY grade_level, name";
    $stmt = mysqli_prepare($conn, $sections_query);
    mysqli_stmt_bind_param($stmt, 's', $selected_level);
    mysqli_stmt_execute($stmt);
    $sections_result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($sections_result)) {
        $sections[] = $row;
    }
    
    // Create default sections if none exist
    if (count($sections) == 0) {
        // Define default sections based on education level
        $default_sections = [];
        
        switch ($selected_level) {
            case 'Kindergarten':
                $default_sections = [
                    ['Kindergarten A', 'K', 'Room K-1'],
                    ['Kindergarten B', 'K', 'Room K-2']
                ];
                break;
            case 'Elementary':
                for ($grade = 1; $grade <= 6; $grade++) {
                    $default_sections[] = ["Grade $grade - A", "$grade", "Room E$grade-A"];
                    $default_sections[] = ["Grade $grade - B", "$grade", "Room E$grade-B"];
                }
                break;
            case 'Junior High School':
                for ($grade = 7; $grade <= 10; $grade++) {
                    $default_sections[] = ["Grade $grade - A", "$grade", "Room J$grade-A"];
                    $default_sections[] = ["Grade $grade - B", "$grade", "Room J$grade-B"];
                }
                break;
            case 'Senior High School':
                $tracks = ['STEM', 'HUMSS', 'ABM', 'GAS', 'TVL'];
                for ($grade = 11; $grade <= 12; $grade++) {
                    foreach ($tracks as $track) {
                        $default_sections[] = ["Grade $grade - $track", "$grade", "Room S$grade-$track"];
                    }
                }
                break;
        }
        
        // Insert default sections
        $insert_section = "INSERT INTO sections (name, grade_level, room, education_level, school_year) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_section);
        $current_school_year = date('Y') . '-' . (date('Y') + 1);
        
        foreach ($default_sections as $section) {
            mysqli_stmt_bind_param($stmt, 'sssss', $section[0], $section[1], $section[2], $selected_level, $current_school_year);
            mysqli_stmt_execute($stmt);
        }
        
        // Refresh sections list
        $sections_query = "SELECT * FROM sections WHERE education_level = ? ORDER BY grade_level, name";
        $stmt = mysqli_prepare($conn, $sections_query);
        mysqli_stmt_bind_param($stmt, 's', $selected_level);
        mysqli_stmt_execute($stmt);
        $sections_result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($sections_result)) {
            $sections[] = $row;
        }
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_student'])) {
    // Get common form data
    $first_name = cleanInput($_POST['first_name']);
    $middle_name = cleanInput($_POST['middle_name'] ?? '');
    $last_name = cleanInput($_POST['last_name']);
    $gender = cleanInput($_POST['gender']);
    $birthdate = cleanInput($_POST['birthdate']);
    $section_id = (int)$_POST['section_id'];
    $lrn = cleanInput($_POST['lrn'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $parent_guardian_name = cleanInput($_POST['parent_guardian_name'] ?? '');
    $parent_guardian_contact = cleanInput($_POST['parent_guardian_contact'] ?? '');
    $parent_guardian_email = cleanInput($_POST['parent_guardian_email'] ?? '');
    $previous_school = cleanInput($_POST['previous_school'] ?? '');
    $has_special_needs = isset($_POST['has_special_needs']) ? 1 : 0;
    $special_needs_details = cleanInput($_POST['special_needs_details'] ?? '');
    $semester = cleanInput($_POST['semester'] ?? 'First');
    $school_year = cleanInput($_POST['school_year'] ?? '');
    $enrollment_status = cleanInput($_POST['enrollment_status'] ?? 'enrolled');
    
    // Education level specific data
    $strand_id = ($selected_level == 'Senior High School') ? (int)$_POST['strand_id'] : 0;
    $preschool_experience = ($selected_level == 'Kindergarten') ? cleanInput($_POST['preschool_experience'] ?? '') : '';
    $development_notes = ($selected_level == 'Kindergarten') ? cleanInput($_POST['development_notes'] ?? '') : '';
    
    // Validation
    $errors = [];
    
    // Common validation
    if (empty($first_name)) $errors[] = "First name is required.";
    if (strtolower($first_name) === $first_name) $errors[] = "First name cannot be all lowercase. Please use proper capitalization.";
    
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (strtolower($last_name) === $last_name) $errors[] = "Last name cannot be all lowercase. Please use proper capitalization.";
    
    if (!empty($middle_name) && strtolower($middle_name) === $middle_name) $errors[] = "Middle name cannot be all lowercase. Please use proper capitalization.";
    
    if (empty($gender)) $errors[] = "Gender is required.";
    if (empty($birthdate)) $errors[] = "Birthdate is required.";
    
    // Section is only required if status is enrolled
    if ($enrollment_status === 'enrolled' && empty($section_id)) {
        $errors[] = "Section is required for enrolled students.";
    }
    
    if (empty($parent_guardian_name)) $errors[] = "Parent/Guardian name is required.";
    if (empty($school_year)) $errors[] = "School year is required.";
    if (empty($semester)) $errors[] = "Semester is required.";
    
    // LRN validation for non-Kindergarten
    if ($selected_level != 'Kindergarten' && empty($lrn)) {
        $errors[] = "LRN is required for $selected_level students.";
    }
    
    if ($selected_level == 'Senior High School' && empty($strand_id)) {
        $errors[] = "Strand is required for Senior High School students.";
    }
    
    // Age validation based on grade level
    if (!empty($birthdate)) {
        $today = new DateTime();
        $birth = new DateTime($birthdate);
        $age = $birth->diff($today)->y;
        
        // Get grade level from section
        $section_query = "SELECT grade_level FROM sections WHERE id = ?";
        $stmt = mysqli_prepare($conn, $section_query);
        mysqli_stmt_bind_param($stmt, 'i', $section_id);
        mysqli_stmt_execute($stmt);
        $section_result = mysqli_stmt_get_result($stmt);
        $section_data = mysqli_fetch_assoc($section_result);
        $grade_level = $section_data['grade_level'];
        
        // Age validation based on education level and grade
        switch ($selected_level) {
            case 'Kindergarten':
                if ($age < 5 || $age > 7) {
                    $errors[] = "Student must be between 5-7 years old for Kindergarten.";
                }
                break;
            case 'Elementary':
                $min_age = 5 + (int)$grade_level;
                $max_age = $min_age + 3;
                if ($age < $min_age || $age > $max_age) {
                    $errors[] = "Student's age should be around $min_age-$max_age years for Grade $grade_level.";
                }
                break;
            case 'Junior High School':
                $min_age = 11 + ((int)$grade_level - 7);
                $max_age = $min_age + 3;
                if ($age < $min_age || $age > $max_age) {
                    $errors[] = "Student's age should be around $min_age-$max_age years for Grade $grade_level.";
                }
                break;
            case 'Senior High School':
                $min_age = 15 + ((int)$grade_level - 11);
                $max_age = $min_age + 3;
                if ($age < $min_age || $age > $max_age) {
                    $errors[] = "Student's age should be around $min_age-$max_age years for Grade $grade_level.";
                }
                break;
        }
    }
    
    // If no errors, proceed with enrollment
    if (empty($errors)) {
        try {
            // Check for duplicate LRN
            if (!empty($lrn) && valueExists('students', 'lrn', $lrn)) {
                $errors[] = "A student with LRN $lrn already exists in the system.";
                $_SESSION['alert'] = showAlert("Duplicate record detected: A student with LRN $lrn already exists in the system.", 'warning');
                throw new Exception("Duplicate LRN detected");
            }
            
            // Check for duplicate name and birthdate combination using the new function
            if ($duplicate_message = checkDuplicate('students', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'dob' => $birthdate
            ], 'student')) {
                $errors[] = $duplicate_message;
                $_SESSION['alert'] = showAlert($duplicate_message, 'warning');
                throw new Exception("Duplicate student detected");
            }
            
            // Start transaction
            mysqli_begin_transaction($conn);
            
            // Get section details
            $section_query = "SELECT name, grade_level FROM sections WHERE id = ?";
            $stmt = mysqli_prepare($conn, $section_query);
            mysqli_stmt_bind_param($stmt, 'i', $section_id);
            mysqli_stmt_execute($stmt);
            $section_result = mysqli_stmt_get_result($stmt);
            $section_data = mysqli_fetch_assoc($section_result);
            
            // Insert student data
            $insert_query = "INSERT INTO students (
                lrn, first_name, middle_name, last_name, gender, dob, grade_level, section,
                address, parent_guardian_name, parent_guardian_contact, parent_guardian_email,
                previous_school, has_special_needs, special_needs_details, education_level_id,
                enrollment_status, enrolled_by, date_enrolled
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
            
            $stmt = mysqli_prepare($conn, $insert_query);
            
            // Set section to NULL if not provided and status is pending
            $section_name = null;
            if (!empty($section_id)) {
                $section_name = $section_data['name'];
            }
            
            mysqli_stmt_bind_param($stmt, 'ssssssssssssiiiiss', 
                $lrn, $first_name, $middle_name, $last_name, $gender, $birthdate, 
                $section_data['grade_level'], $section_name, $address, 
                $parent_guardian_name, $parent_guardian_contact, $parent_guardian_email,
                $previous_school, $has_special_needs, $special_needs_details, $education_level_id,
                $enrollment_status, $_SESSION['user_id']
            );
            mysqli_stmt_execute($stmt);
            $student_id = mysqli_insert_id($conn);
            
            // Add education level specific data
            if ($selected_level == 'Senior High School' && $strand_id > 0) {
                $insert_shs_query = "INSERT INTO senior_highschool_details (student_id, strand_id, semester, school_year) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_shs_query);
                mysqli_stmt_bind_param($stmt, 'iiss', $student_id, $strand_id, $semester, $school_year);
                mysqli_stmt_execute($stmt);
            } elseif ($selected_level == 'Kindergarten') {
                $insert_kinder_query = "INSERT INTO kinder_students (student_id, preschool_experience, development_notes) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_kinder_query);
                mysqli_stmt_bind_param($stmt, 'iss', $student_id, $preschool_experience, $development_notes);
                mysqli_stmt_execute($stmt);
            }
            
            // Add entry to enrollment history
            $strand_value = null;
            if ($selected_level == 'Senior High School' && $strand_id > 0) {
                // Get strand name
                $strand_query = "SELECT name FROM strands WHERE id = ?";
                $strand_stmt = mysqli_prepare($conn, $strand_query);
                mysqli_stmt_bind_param($strand_stmt, 'i', $strand_id);
                mysqli_stmt_execute($strand_stmt);
                $strand_result = mysqli_stmt_get_result($strand_stmt);
                $strand_data = mysqli_fetch_assoc($strand_result);
                $strand_value = $strand_data ? $strand_data['name'] : null;
            }
            
            // Check if enrollment_history table exists, create if not
            $check_table_query = "SHOW TABLES LIKE 'enrollment_history'";
            $check_table_result = mysqli_query($conn, $check_table_query);
            
            if (mysqli_num_rows($check_table_result) > 0) {
                // Insert into enrollment history
                $history_query = "INSERT INTO enrollment_history (
                    student_id, school_year, semester, grade_level, section, strand, 
                    enrollment_date, enrollment_status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?)";
                
                $history_stmt = mysqli_prepare($conn, $history_query);
                mysqli_stmt_bind_param($history_stmt, 'isssssi', 
                    $student_id, $school_year, $semester, $section_data['grade_level'], 
                    $section_data['name'], $strand_value, $_SESSION['user_id']
                );
                mysqli_stmt_execute($history_stmt);
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Log action
            logAction($_SESSION['user_id'], 'ENROLL', "Enrolled student: $first_name $last_name in $selected_level");
            
            // Set success message
            $_SESSION['alert'] = showAlert("Student successfully enrolled!", 'success');
            
            // Redirect to view student page
            redirect($relative_path . 'modules/registrar/view_student.php?id=' . $student_id);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            $_SESSION['alert'] = showAlert('Error enrolling student: ' . $e->getMessage(), 'danger');
        }
    } else {
        // Display validation errors
        $_SESSION['alert'] = showAlert(implode('<br>', $errors), 'danger');
    }
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_header; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Student Enrollment</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) { echo $_SESSION['alert']; unset($_SESSION['alert']); } ?>
    
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Select Education Level</h5>
                </div>
                <div class="card-body">
                    <form method="get" action="" class="row g-3">
                        <div class="col-md-4">
                            <label for="level" class="form-label">Education Level</label>
                            <select name="level" id="level" class="form-select" required>
                                <option value="">Select Education Level</option>
                        <?php foreach ($education_levels as $level): ?>
                                <option value="<?php echo htmlspecialchars($level['name']); ?>" <?php echo ($selected_level == $level['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($level['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="school_year" class="form-label">School Year</label>
                            <select name="school_year" id="school_year" class="form-select">
                                <?php foreach ($school_years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($selected_school_year == $year) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select name="semester" id="semester" class="form-select">
                                <?php foreach ($semesters as $sem): ?>
                                <option value="<?php echo htmlspecialchars($sem); ?>" <?php echo ($selected_semester == $sem) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sem); ?>
                                </option>
                        <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($selected_level)): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($selected_level); ?> Enrollment Form</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?level=' . urlencode($selected_level)); ?>" class="needs-validation" novalidate>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Student Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label">First Name*</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                                        <div class="invalid-feedback">First name is required.</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="last_name" class="form-label">Last Name*</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                                        <div class="invalid-feedback">Last name is required.</div>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-4">
                                        <label for="gender" class="form-label">Gender*</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        <div class="invalid-feedback">Please select gender.</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="birthdate" class="form-label">Birthdate*</label>
                                        <input type="date" class="form-control" id="birthdate" name="birthdate" required>
                                        <div class="invalid-feedback">Birthdate is required.</div>
                                    </div>
                                    
                                    <?php if ($selected_level != 'Kindergarten'): ?>
                                    <div class="col-md-4">
                                        <label for="lrn" class="form-label">LRN (Learner Reference Number)*</label>
                                        <input type="text" class="form-control" id="lrn" name="lrn" required>
                                        <div class="invalid-feedback">LRN is required.</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="grade_level" class="form-label">Grade Level*</label>
                                                <select class="form-select" id="grade_level" name="grade_level" required>
                                                    <option value="">Select Grade Level</option>
                                                    <?php foreach ($grade_levels as $grade): ?>
                                                        <option value="<?php echo htmlspecialchars($grade); ?>">
                                                            <?php echo $grade === 'K' ? 'Kindergarten' : 'Grade ' . htmlspecialchars($grade); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="invalid-feedback">Please select a grade level.</div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label for="section_id" class="form-label">Section*</label>
                                                <select class="form-select" id="section_id" name="section_id" required disabled>
                                                    <option value="">Select Grade Level First</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a section.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($selected_level == 'Senior High School'): ?>
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="strand_id" class="form-label">Strand/Track*</label>
                                        <select class="form-select" id="strand_id" name="strand_id" required>
                                            <option value="">Select Strand/Track</option>
                                            <?php foreach ($strands as $strand): ?>
                                            <option value="<?php echo $strand['id']; ?>">
                                                <?php echo htmlspecialchars($strand['name'] . ' (' . $strand['code'] . ')'); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">Please select a strand/track.</div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Enrollment Details</h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="school_year" class="form-label">School Year*</label>
                                        <input type="text" class="form-control" id="school_year" name="school_year" value="<?php echo htmlspecialchars($selected_school_year); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="semester" class="form-label">Semester*</label>
                                        <select class="form-select" id="semester" name="semester" required>
                                            <option value="First" <?php echo ($selected_semester == 'First') ? 'selected' : ''; ?>>First Semester</option>
                                            <option value="Second" <?php echo ($selected_semester == 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a semester.</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="enrollment_status" class="form-label">Enrollment Status*</label>
                                        <select class="form-select" id="enrollment_status" name="enrollment_status" required>
                                            <option value="enrolled">Enrolled</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                        <div class="invalid-feedback">Please select an enrollment status.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Parent/Guardian Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="parent_guardian_name" class="form-label">Parent/Guardian Name*</label>
                                        <input type="text" class="form-control" id="parent_guardian_name" name="parent_guardian_name" required>
                                        <div class="invalid-feedback">Parent/Guardian name is required.</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="parent_guardian_contact" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="parent_guardian_contact" name="parent_guardian_contact">
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="parent_guardian_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="parent_guardian_email" name="parent_guardian_email">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Previous School Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="previous_school" class="form-label">Previous School</label>
                                        <input type="text" class="form-control" id="previous_school" name="previous_school">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($selected_level == 'Kindergarten'): ?>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Kindergarten Specific Information</h4>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="preschool_experience" class="form-label">Preschool Experience</label>
                                        <select class="form-select" id="preschool_experience" name="preschool_experience">
                                            <option value="">Select Option</option>
                                            <option value="None">None</option>
                                            <option value="Daycare">Daycare</option>
                                            <option value="Preschool">Preschool</option>
                                            <option value="Home-based learning">Home-based learning</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="development_notes" class="form-label">Development Notes</label>
                                        <textarea class="form-control" id="development_notes" name="development_notes" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <h4 class="mb-3">Special Needs</h4>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="has_special_needs" name="has_special_needs">
                                            <label class="form-check-label" for="has_special_needs">
                                                Student has special needs
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-12" id="special_needs_details_container" style="display: none;">
                                        <label for="special_needs_details" class="form-label">Special Needs Details</label>
                                        <textarea class="form-control" id="special_needs_details" name="special_needs_details" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" name="enroll_student" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Enroll Student
                                </button>
                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Students
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Show/hide special needs details
    const specialNeedsCheckbox = document.getElementById('has_special_needs');
    const specialNeedsDetailsContainer = document.getElementById('special_needs_details_container');
    
    if (specialNeedsCheckbox && specialNeedsDetailsContainer) {
        specialNeedsCheckbox.addEventListener('change', function() {
            specialNeedsDetailsContainer.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Dynamic section dropdown based on grade level
    const gradeLevel = document.getElementById('grade_level');
    const sectionDropdown = document.getElementById('section_id');
    const enrollmentStatus = document.getElementById('enrollment_status');
    
    if (gradeLevel && sectionDropdown) {
        gradeLevel.addEventListener('change', function() {
            const selectedGrade = this.value;
            const educationLevel = '<?php echo addslashes($selected_level); ?>';
            
            if (selectedGrade) {
                // Enable section dropdown
                sectionDropdown.disabled = false;
                sectionDropdown.innerHTML = '<option value="">Loading sections...</option>';
                
                // Fetch sections via AJAX
                fetch('<?php echo $relative_path; ?>modules/registrar/get_sections.php?education_level=' + 
                      encodeURIComponent(educationLevel) + '&grade_level=' + encodeURIComponent(selectedGrade))
                    .then(response => response.json())
                    .then(data => {
                        sectionDropdown.innerHTML = '<option value="">Select Section</option>';
                        
                        if (data.length === 0) {
                            sectionDropdown.innerHTML += '<option value="" disabled>No sections found</option>';
                        } else {
                            data.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section.id;
                                option.textContent = section.name + ' (' + section.room + ')';
                                sectionDropdown.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching sections:', error);
                        sectionDropdown.innerHTML = '<option value="">Error loading sections</option>';
                    });
            } else {
                // Reset and disable section dropdown
                sectionDropdown.innerHTML = '<option value="">Select Grade Level First</option>';
                sectionDropdown.disabled = true;
            }
        });
    }
    
    // Update section required attribute based on enrollment status
    if (enrollmentStatus && sectionDropdown) {
        enrollmentStatus.addEventListener('change', function() {
            if (this.value === 'enrolled') {
                sectionDropdown.setAttribute('required', '');
            } else {
                sectionDropdown.removeAttribute('required');
            }
        });
        
        // Initial call to set the required attribute based on the default selected status
        if (enrollmentStatus.value === 'enrolled') {
            sectionDropdown.setAttribute('required', '');
        } else {
            sectionDropdown.removeAttribute('required');
        }
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?>