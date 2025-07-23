<?php
$title = 'Add New Student';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Initialize variables with default values
$has_voucher = 0; // Default value for has_voucher
$suffix = ''; // Initialize suffix
$voucher_number = ''; // Initialize voucher_number
$student_type = 'new'; // Default value for student_type

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $lrn = cleanInput($_POST['lrn'], 'text');
    $first_name = cleanInput($_POST['first_name'], 'name');
    $middle_name = cleanInput($_POST['middle_name'], 'name');
    $last_name = cleanInput($_POST['last_name'], 'name');
    $suffix = isset($_POST['suffix']) ? cleanInput($_POST['suffix'], 'name') : '';
    $dob = cleanInput($_POST['dob'], 'text');
    $gender = cleanInput($_POST['gender'], 'text');
    $religion = cleanInput($_POST['religion'], 'text');
    $address = cleanInput($_POST['address'], 'address');
    $contact_number = cleanInput($_POST['contact_number'], 'phone');
    $email = cleanInput($_POST['email'], 'email');
    $father_name = cleanInput($_POST['father_name'], 'name');
    $father_occupation = cleanInput($_POST['father_occupation'], 'text');
    $mother_name = cleanInput($_POST['mother_name'], 'name');
    $mother_occupation = cleanInput($_POST['mother_occupation'], 'text');
    $guardian_name = cleanInput($_POST['guardian_name'], 'name');
    $guardian_contact = cleanInput($_POST['guardian_contact'], 'phone');
    $grade_level = cleanInput($_POST['grade_level'], 'text');
    $semester = cleanInput($_POST['semester'], 'text');
    $school_year = cleanInput($_POST['school_year'], 'text');
    $strand = cleanInput($_POST['strand'], 'uppercase');
    $track = cleanInput($_POST['track'], 'uppercase');
    $section = cleanInput($_POST['section'], 'uppercase');
    $enrollment_status = cleanInput($_POST['enrollment_status'], 'text');
    $previous_school = cleanInput($_POST['previous_school'], 'name');
    $previous_track = cleanInput($_POST['previous_track'], 'uppercase');
    $previous_strand = cleanInput($_POST['previous_strand'], 'uppercase');
    $has_voucher = (int)$_POST['has_voucher'];
    $voucher_number = isset($_POST['voucher_number']) ? cleanInput($_POST['voucher_number'], 'uppercase') : '';
    $student_type = cleanInput($_POST['student_type'], 'text');
    
    // Validation
    $errors = [];
    
    if (empty($lrn)) {
        $errors[] = 'LRN is required';
    } elseif (!preg_match('/^\d{12}$/', $lrn)) {
        $errors[] = 'LRN must be exactly 12 digits (no letters or special characters)';
    }
    
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required';
    }
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required';
    }
    
    // Modified section validation logic
    if (empty($section)) {
        if ($enrollment_status === 'enrolled') {
            $errors[] = 'Section is required for enrolled students';
        }
        // For pending or withdrawn students, section is not required
        // No error message needed
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Modified voucher validation - only validate if has_voucher is checked AND voucher_number is provided
    if ($has_voucher == 1 && !empty($voucher_number)) {
        // Validate voucher number format if needed
        // For now, we're just accepting any non-empty value
    } else if ($has_voucher == 1 && empty($voucher_number)) {
        // If has_voucher is checked but no number provided, set has_voucher to 0
        $has_voucher = 0;
    }
    
    // Check if student_type column exists in the students table
    $check_column_query = "SHOW COLUMNS FROM students LIKE 'student_type'";
    $check_column_result = mysqli_query($conn, $check_column_query);
    
    if (mysqli_num_rows($check_column_result) == 0) {
        // Add student_type column if it doesn't exist
        $add_column_query = "ALTER TABLE students ADD COLUMN student_type ENUM('new', 'old') DEFAULT 'new'";
        mysqli_query($conn, $add_column_query);
    }
    
    // Check if LRN already exists
    $query = "SELECT id FROM students WHERE lrn = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $lrn);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $errors[] = 'LRN already exists';
    }
    
    // Handle photo upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $relative_path . 'uploads/students/';
        $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = 'Only JPG, JPEG, and PNG files are allowed';
        } else {
            $photo_filename = 'student_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_file = $upload_dir . $photo_filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = 'uploads/students/' . $photo_filename;
            } else {
                $errors[] = 'Failed to upload photo';
            }
        }
    }
    
    // If no errors, insert student
    if (empty($errors)) {
        // For pending or withdrawn students, section is not required
        if ($enrollment_status !== 'enrolled' && empty($section)) {
            $section = ''; // Set to empty string for non-enrolled students
        }
        
        // Prepare student data for insertion
        $student_data = [
            'lrn' => $lrn,
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'dob' => $dob,
            'gender' => $gender,
            'religion' => $religion,
            'address' => $address,
            'contact_number' => $contact_number,
            'email' => $email,
            'father_name' => $father_name,
            'father_occupation' => $father_occupation,
            'mother_name' => $mother_name,
            'mother_occupation' => $mother_occupation,
            'guardian_name' => $guardian_name,
            'guardian_contact' => $guardian_contact,
            'grade_level' => $grade_level,
            'strand' => $strand,
            'section' => $section,
            'enrollment_status' => $enrollment_status,
            'photo' => $photo_path,
            'enrolled_by' => $_SESSION['user_id'],
            'date_enrolled' => date('Y-m-d'),
            'has_voucher' => $has_voucher,
            'student_type' => $student_type
        ];
        
        // Add voucher number only if has_voucher is 1
        if ($has_voucher == 1 && !empty($voucher_number)) {
            $student_data['voucher_number'] = $voucher_number;
        }

        // Check for duplicate records using safeInsert
        $result = safeInsert('students', $student_data, [
            'unique_fields' => ['lrn', 'first_name', 'last_name', 'dob'],
            'entity_name' => 'student',
            'log_action' => true
        ]);

        if ($result['success']) {
            $student_id = $result['insert_id'];
            
            // Add entry to senior_highschool_details table
            if (!empty($strand)) {
                // Prepare SHS data
                $shs_data = [
                    'student_id' => $student_id,
                    'track' => $track,
                    'strand' => $strand,
                    'semester' => $semester,
                    'school_year' => $school_year,
                    'previous_school' => $previous_school,
                    'previous_track' => $previous_track,
                    'previous_strand' => $previous_strand
                ];
                
                // Insert SHS details using safeInsert
                $shs_result = safeInsert('senior_highschool_details', $shs_data, [
                    'entity_name' => 'senior high school details',
                    'log_action' => true
                ]);
                
                if (!$shs_result['success']) {
                    // Log the error but continue
                    logAction($_SESSION['user_id'], 'ERROR', "Error adding SHS details for student ID $student_id: " . $shs_result['message']);
                } else {
                    // Log success
                    logAction($_SESSION['user_id'], 'CREATE', "Added SHS details for student: $first_name $last_name, Track: $track, Strand: $strand");
                }
                
                // Record enrollment history
                $enrollment_history_data = [
                    'student_id' => $student_id,
                    'school_year' => $school_year,
                    'semester' => $semester,
                    'grade_level' => $grade_level,
                    'strand' => $strand,
                    'section' => $section,
                    'enrollment_status' => $enrollment_status,
                    'date_enrolled' => date('Y-m-d'),
                    'enrolled_by' => $_SESSION['user_id'],
                    'notes' => 'Initial enrollment'
                ];
                
                // Check if enrollment_history table exists
                $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'enrollment_history'");
                if (mysqli_num_rows($table_check) > 0) {
                    // Insert enrollment history
                    $history_result = safeInsert('enrollment_history', $enrollment_history_data, [
                        'entity_name' => 'enrollment history',
                        'log_action' => true
                    ]);
                    
                    if (!$history_result['success']) {
                        // Log the error but continue
                        logAction($_SESSION['user_id'], 'ERROR', "Error adding enrollment history for student ID $student_id: " . $history_result['message']);
                    }
                }
            }
            
            $_SESSION['alert'] = showAlert('Student added successfully.', 'success');
            redirect('modules/registrar/students.php');
        } else {
            if (isset($result['duplicate']) && $result['duplicate']) {
                $_SESSION['alert'] = showAlert($result['message'], 'warning');
            } else {
                $errors[] = 'Error adding student: ' . $result['message'];
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Add New Student</h1>
        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Students
        </a>
    </div>

    <?php 
    if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    }
    
    if (isset($errors) && !empty($errors)) {
        echo '<div class="alert alert-danger">';
        echo '<ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary text-white">Student Information</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lrn">LRN (Learner Reference Number) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lrn" name="lrn" placeholder="Enter 12-digit LRN" pattern="\d{12}" title="LRN must be exactly 12 digits" required>
                            <small class="form-text text-muted">LRN must be exactly 12 digits (no letters or special characters).</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="enrollment_status">Enrollment Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="enrollment_status" name="enrollment_status" required>
                                <option value="enrolled">Enrolled</option>
                                <option value="pending">Pending</option>
                                <option value="withdrawn">Withdrawn</option>
                                <option value="irregular">Irregular</option>
                                <option value="graduated">Graduated</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="student_type">Student Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="student_type" name="student_type" required>
                                <option value="new" <?php echo (isset($student_type) && $student_type === 'new') ? 'selected' : 'selected'; ?>>New Student</option>
                                <option value="old" <?php echo (isset($student_type) && $student_type === 'old') ? 'selected' : ''; ?>>Old Student</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="photo">Student Photo</label>
                            <input type="file" class="form-control-file" id="photo" name="photo">
                            <small class="form-text text-muted">Upload a photo (JPG, JPEG, PNG)</small>
                        </div>
                    </div>
                </div>

                <h5 class="mt-3">Personal Information</h5>
                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required 
                                   value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                   value="<?php echo isset($middle_name) ? htmlspecialchars($middle_name) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dob">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob" required 
                                   value="<?php echo isset($dob) ? $dob : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender">Sex <span class="text-danger">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="Male" <?php echo (isset($gender) && $gender === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($gender) && $gender === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="religion">Religion</label>
                            <input type="text" class="form-control" id="religion" name="religion" 
                                   value="<?php echo isset($religion) ? htmlspecialchars($religion) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                   value="<?php echo isset($contact_number) ? htmlspecialchars($contact_number) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                        </div>
                    </div>
                </div>

                <h5 class="mt-4">Parent Information</h5>
                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="father_name">Father's Name</label>
                            <input type="text" class="form-control" id="father_name" name="father_name" 
                                   value="<?php echo isset($father_name) ? htmlspecialchars($father_name) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="father_occupation">Father's Occupation</label>
                            <input type="text" class="form-control" id="father_occupation" name="father_occupation" 
                                   value="<?php echo isset($father_occupation) ? htmlspecialchars($father_occupation) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mother_name">Mother's Name</label>
                            <input type="text" class="form-control" id="mother_name" name="mother_name" 
                                   value="<?php echo isset($mother_name) ? htmlspecialchars($mother_name) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mother_occupation">Mother's Occupation</label>
                            <input type="text" class="form-control" id="mother_occupation" name="mother_occupation" 
                                   value="<?php echo isset($mother_occupation) ? htmlspecialchars($mother_occupation) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="guardian_name">Guardian's Name</label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                                   value="<?php echo isset($guardian_name) ? htmlspecialchars($guardian_name) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="guardian_contact">Guardian's Contact</label>
                            <input type="text" class="form-control" id="guardian_contact" name="guardian_contact" 
                                   value="<?php echo isset($guardian_contact) ? htmlspecialchars($guardian_contact) : ''; ?>">
                        </div>
                    </div>
                </div>

                <h5 class="mt-4">Academic Information</h5>
                <hr>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="grade_level">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-control" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <option value="Grade 11" <?php echo (isset($grade_level) && $grade_level === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="Grade 12" <?php echo (isset($grade_level) && $grade_level === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="semester">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="First" <?php echo (isset($semester) && $semester === 'First') ? 'selected' : 'selected'; ?>>First Semester</option>
                                <option value="Second" <?php echo (isset($semester) && $semester === 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="school_year">School Year <span class="text-danger">*</span></label>
                            <select class="form-control" id="school_year" name="school_year" required>
                                <?php
                                // Get school years from database
                                $query = "SELECT school_year, is_current FROM school_years WHERE status = 'Active' ORDER BY year_start DESC";
                                $result = mysqli_query($conn, $query);
                                
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        $selected = $row['is_current'] ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['school_year']) . "' {$selected}>" . 
                                             htmlspecialchars($row['school_year']) . "</option>";
                                    }
                                } else {
                                    // Fallback if no school years in database
                                    $current_year = (int)date('Y');
                                    $next_year = $current_year + 1;
                                    $school_year = $current_year . '-' . $next_year;
                                    echo "<option value='" . htmlspecialchars($school_year) . "' selected>" . 
                                         htmlspecialchars($school_year) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="strand">Strand</label>
                            <select class="form-control" id="strand" name="strand">
                                <option value="">Select Strand</option>
                                <?php
                                // Get all strands from the shs_strands table
                                $query = "SELECT strand_code, strand_name, track_name FROM shs_strands WHERE status = 'Active' ORDER BY strand_name";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = (isset($strand) && $strand === $row['strand_code']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['strand_code']) . "' $selected
                                          data-track='" . htmlspecialchars($row['track_name']) . "'>" . 
                                         htmlspecialchars($row['strand_code'] . ' - ' . $row['strand_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <input type="hidden" id="track" name="track" value="<?php echo isset($track) ? htmlspecialchars($track) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="section">Section <span class="text-danger" id="section-required-mark">*</span></label>
                            <select class="form-control" id="section" name="section">
                                <option value="">Select Section</option>
                                <?php
                                // Get all sections from the sections table
                                $query = "SELECT name, grade_level, strand FROM sections WHERE status = 'Active' ORDER BY name";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = (isset($section) && $section === $row['name']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['name']) . "' $selected 
                                          data-grade='" . htmlspecialchars($row['grade_level']) . "' 
                                          data-strand='" . htmlspecialchars($row['strand']) . "'>" . 
                                          htmlspecialchars($row['name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="form-text text-info">
                                <i class="fas fa-info-circle"></i> Section is only required for enrolled students. Pending students can be added without a section.
                            </small>
                        </div>
                    </div>
                </div>

                <h5 class="mt-4">Previous School Information</h5>
                <hr>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="previous_school">Previous School</label>
                            <input type="text" class="form-control" id="previous_school" name="previous_school" 
                                   value="<?php echo isset($previous_school) ? htmlspecialchars($previous_school) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="previous_track">Previous Track</label>
                            <input type="text" class="form-control" id="previous_track" name="previous_track" 
                                   value="<?php echo isset($previous_track) ? htmlspecialchars($previous_track) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="previous_strand">Previous Strand</label>
                            <input type="text" class="form-control" id="previous_strand" name="previous_strand" 
                                   value="<?php echo isset($previous_strand) ? htmlspecialchars($previous_strand) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="has_voucher">Has Voucher? <span class="text-danger">*</span></label>
                            <select class="form-control" id="has_voucher" name="has_voucher" required>
                                <option value="0" <?php echo (!isset($has_voucher) || $has_voucher == 0) ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo (isset($has_voucher) && $has_voucher == 1) ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="voucher_number">Voucher Number</label>
                            <input type="text" class="form-control" id="voucher_number" name="voucher_number" 
                                   value="<?php echo isset($voucher_number) ? htmlspecialchars($voucher_number) : ''; ?>"
                                   placeholder="Enter voucher number if applicable">
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Add Student</button>
                    <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add any JavaScript for dynamic dropdowns here
document.addEventListener('DOMContentLoaded', function() {
    const gradeLevel = document.getElementById('grade_level');
    const strand = document.getElementById('strand');
    const sectionDropdown = document.getElementById('section');
    const trackInput = document.getElementById('track');
    const enrollmentStatus = document.getElementById('enrollment_status');
    const hasVoucher = document.getElementById('has_voucher');
    const voucherNumber = document.getElementById('voucher_number');
    const studentType = document.getElementById('student_type');
    
    // Function to filter sections based on grade level and strand
    function filterSections() {
        const selectedGrade = gradeLevel.value;
        const selectedStrand = strand.value;
        
        // Loop through all section options
        Array.from(sectionDropdown.options).forEach(option => {
            if (option.value === '') return; // Skip the placeholder option
            
            const optionGrade = option.getAttribute('data-grade');
            const optionStrand = option.getAttribute('data-strand');
            
            // Show or hide based on filters
            if (
                (selectedGrade === '' || optionGrade === selectedGrade) && 
                (selectedStrand === '' || optionStrand === selectedStrand)
            ) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
        
        // Check if the currently selected option is now hidden
        if (sectionDropdown.selectedIndex > 0 && 
            sectionDropdown.options[sectionDropdown.selectedIndex].style.display === 'none') {
            sectionDropdown.value = ''; // Reset to placeholder if current selection is filtered out
        }
    }
    
    // Function to update track based on selected strand
    function updateTrack() {
        if (strand.selectedIndex > 0) {
            const selectedOption = strand.options[strand.selectedIndex];
            const trackName = selectedOption.getAttribute('data-track');
            trackInput.value = trackName || '';
        } else {
            trackInput.value = '';
        }
    }
    
    // Function to check enrollment status and section
    function checkEnrollmentStatusAndSection() {
        if (enrollmentStatus.value === 'pending') {
            // For pending status, make section optional but not required
            sectionDropdown.value = ''; // Clear section for pending students
            sectionDropdown.disabled = false;
            sectionDropdown.required = false;
            sectionDropdown.classList.add('bg-light');
            document.querySelector('label[for="section"]').innerHTML = 'Section <small class="text-info">(Optional for Pending)</small>';
        } else if (enrollmentStatus.value === 'withdrawn') {
            if (sectionDropdown.value !== '') {
                alert('Warning: Students with "Withdrawn" status should not be assigned a section. Section has been cleared.');
                sectionDropdown.value = '';
            }
            sectionDropdown.disabled = true;
            sectionDropdown.required = false;
            sectionDropdown.classList.add('bg-light');
            document.querySelector('label[for="section"]').innerHTML = 'Section';
        } else if (enrollmentStatus.value === 'graduated') {
            // Keep section for graduated students but make it optional
            sectionDropdown.disabled = false;
            sectionDropdown.required = false;
            sectionDropdown.classList.add('bg-light');
            document.querySelector('label[for="section"]').innerHTML = 'Section <small class="text-info">(Optional for Graduated)</small>';
        } else if (enrollmentStatus.value === 'irregular') {
            // For irregular students, section is required
            sectionDropdown.disabled = false;
            sectionDropdown.required = true;
            sectionDropdown.classList.remove('bg-light');
            document.querySelector('label[for="section"]').innerHTML = 'Section <span class="text-danger">*</span>';
        } else {
            // Enable and require for enrolled students
            sectionDropdown.disabled = false;
            sectionDropdown.required = true;
            sectionDropdown.classList.remove('bg-light');
            document.querySelector('label[for="section"]').innerHTML = 'Section <span class="text-danger">*</span>';
        }
    }
    
    // Function to handle voucher field visibility
    function updateVoucherField() {
        if (hasVoucher.value === '1') {
            voucherNumber.disabled = false;
            voucherNumber.parentElement.style.display = 'block';
        } else {
            voucherNumber.disabled = true;
            voucherNumber.value = '';
            voucherNumber.parentElement.style.display = 'block';
        }
    }
    
    // Add event listeners
    gradeLevel.addEventListener('change', filterSections);
    strand.addEventListener('change', function() {
        filterSections();
        updateTrack();
    });
    
    enrollmentStatus.addEventListener('change', checkEnrollmentStatusAndSection);
    hasVoucher.addEventListener('change', updateVoucherField);
    
    // Initial calls
    filterSections();
    updateTrack();
    checkEnrollmentStatusAndSection(); // Check initial status
    updateVoucherField(); // Initialize voucher field
});

// Add custom validation for LRN field
document.addEventListener('DOMContentLoaded', function() {
    const lrnInput = document.getElementById('lrn');
    
    lrnInput.addEventListener('input', function() {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Validate the length
        if (this.value.length === 12) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else if (this.value.length > 0) {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-valid');
            this.classList.remove('is-invalid');
        }
    });
});

// Add JavaScript validation for name fields
document.addEventListener('DOMContentLoaded', function() {
    const lrnInput = document.getElementById('lrn');
    const firstNameInput = document.getElementById('first_name');
    const middleNameInput = document.getElementById('middle_name');
    const lastNameInput = document.getElementById('last_name');
    
    lrnInput.addEventListener('input', function() {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Validate the length
        if (this.value.length === 12) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else if (this.value.length > 0) {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-valid');
            this.classList.remove('is-invalid');
        }
    });
    
    // Name validation function
    function validateNameCase(input) {
        if (input.value.length > 0) {
            if (input.value.toLowerCase() === input.value) {
                input.classList.add('is-invalid');
                input.nextElementSibling = input.nextElementSibling || document.createElement('div');
                input.nextElementSibling.className = 'invalid-feedback';
                input.nextElementSibling.textContent = 'Name cannot be all lowercase. Please use proper capitalization.';
            } else {
                input.classList.remove('is-invalid');
            }
        }
    }
    
    // Add event listeners for name fields
    firstNameInput.addEventListener('blur', function() {
        validateNameCase(this);
    });
    
    middleNameInput.addEventListener('blur', function() {
        if (this.value.length > 0) {
            validateNameCase(this);
        } else {
            this.classList.remove('is-invalid');
        }
    });
    
    lastNameInput.addEventListener('blur', function() {
        validateNameCase(this);
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 