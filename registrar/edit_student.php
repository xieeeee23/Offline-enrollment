<?php
$title = 'Edit Student';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Check if student ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert'] = showAlert('Student ID is required.', 'danger');
    redirect('modules/registrar/students.php');
}

$student_id = (int) $_GET['id'];

// Get student data
$query = "SELECT s.*, 
          shsd.track, shsd.previous_school, shsd.previous_track, shsd.previous_strand,
          shsd.semester, shsd.school_year
          FROM students s
          LEFT JOIN senior_highschool_details shsd ON s.id = shsd.student_id
          WHERE s.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    redirect('modules/registrar/students.php');
}

$student = mysqli_fetch_assoc($result);

// Check if we need to alter the table to add new columns
$columns_to_check = [
    'middle_name' => 'VARCHAR(50)',
    'religion' => 'VARCHAR(50)',
    'email' => 'VARCHAR(100)',
    'father_name' => 'VARCHAR(100)',
    'father_occupation' => 'VARCHAR(100)',
    'mother_name' => 'VARCHAR(100)',
    'mother_occupation' => 'VARCHAR(100)',
    'strand' => 'VARCHAR(50)',
    'guardian_name' => 'VARCHAR(100)',
    'guardian_contact' => 'VARCHAR(20)',
    'student_type' => 'ENUM("new", "old") DEFAULT "new"'
];

foreach ($columns_to_check as $column => $type) {
    if (!isset($student[$column])) {
        $query = "SHOW COLUMNS FROM students LIKE '$column'";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) == 0) {
            // Add column to students table
            $query = "ALTER TABLE students ADD COLUMN $column $type";
            if (!mysqli_query($conn, $query)) {
                $_SESSION['alert'] = showAlert("Error adding $column column: " . mysqli_error($conn), 'danger');
            }
        }
        // Set default value for the column in the student array
        $student[$column] = '';
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $lrn = trim($_POST['lrn']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $religion = trim($_POST['religion']);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $father_name = trim($_POST['father_name']);
    $father_occupation = trim($_POST['father_occupation']);
    $mother_name = trim($_POST['mother_name']);
    $mother_occupation = trim($_POST['mother_occupation']);
    $guardian_name = trim($_POST['guardian_name']);
    $guardian_contact = trim($_POST['guardian_contact']);
    $grade_level = $_POST['grade_level'];
    $strand = $_POST['strand'];
    $track = $_POST['track'];
    $section = $_POST['section'];
    $enrollment_status = $_POST['enrollment_status'];
    $previous_school = trim($_POST['previous_school']);
    $previous_track = trim($_POST['previous_track']);
    $previous_strand = trim($_POST['previous_strand']);
    $student_type = $_POST['student_type'];
    $semester = isset($_POST['semester']) ? $_POST['semester'] : 'First';
    $school_year = isset($_POST['school_year']) ? $_POST['school_year'] : '';
    $has_voucher = isset($_POST['has_voucher']) ? (int)$_POST['has_voucher'] : 0;
    $voucher_number = isset($_POST['voucher_number']) ? cleanInput($_POST['voucher_number'], 'uppercase') : '';
    
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
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // Check if LRN already exists (excluding current student)
    if (valueExists('students', 'lrn', $lrn, $student_id)) {
        $errors[] = "Duplicate record detected: A student with LRN $lrn already exists in the system.";
        $_SESSION['alert'] = showAlert("Duplicate record detected: A student with LRN $lrn already exists in the system.", 'warning');
    }
    
    // Handle photo upload
    $photo_path = $student['photo']; // Keep existing photo by default
    
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
                // Delete old photo if exists
                if (!empty($student['photo'])) {
                    $old_photo_path = $relative_path . $student['photo'];
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                
                $photo_path = 'uploads/students/' . $photo_filename;
            } else {
                $errors[] = 'Failed to upload photo';
            }
        }
    }
    
    // Handle photo deletion
    if (isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1') {
        if (!empty($student['photo'])) {
            $old_photo_path = $relative_path . $student['photo'];
            if (file_exists($old_photo_path)) {
                unlink($old_photo_path);
            }
            $photo_path = null;
        }
    }
    
    // If no errors, update student
    if (empty($errors)) {
        $query = "UPDATE students SET 
                  lrn = ?, 
                  first_name = ?, 
                  middle_name = ?,
                  last_name = ?, 
                  dob = ?, 
                  gender = ?, 
                  religion = ?,
                  address = ?, 
                  contact_number = ?, 
                  email = ?,
                  father_name = ?,
                  father_occupation = ?,
                  mother_name = ?,
                  mother_occupation = ?,
                  guardian_name = ?, 
                  guardian_contact = ?, 
                  grade_level = ?, 
                  strand = ?,
                  section = ?, 
                  enrollment_status = ?, 
                  photo = ?,
                  student_type = ?,
                  has_voucher = ?,
                  voucher_number = ?
                  WHERE id = ?";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssssssi", 
                              $lrn, $first_name, $middle_name, $last_name, $dob, $gender, $religion, 
                              $address, $contact_number, $email, $father_name, $father_occupation, 
                              $mother_name, $mother_occupation, $guardian_name, $guardian_contact, 
                              $grade_level, $strand, $section, $enrollment_status, 
                              $photo_path, $student_type, $has_voucher, $voucher_number, $student_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update or insert senior_highschool_details
            if (!empty($strand)) {
                // Check if student already has SHS details
                $check_query = "SELECT id FROM senior_highschool_details WHERE student_id = ?";
                $check_stmt = mysqli_prepare($conn, $check_query);
                mysqli_stmt_bind_param($check_stmt, "i", $student_id);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                // Get current school year
                $school_year_query = "SELECT school_year FROM school_years WHERE is_current = 1 LIMIT 1";
                $school_year_result = mysqli_query($conn, $school_year_query);
                $school_year = '2025-2026'; // Default if not found
                
                if (mysqli_num_rows($school_year_result) > 0) {
                    $school_year_row = mysqli_fetch_assoc($school_year_result);
                    $school_year = $school_year_row['school_year'];
                }
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    // Update existing record
                    $shs_query = "UPDATE senior_highschool_details 
                                 SET track = ?, strand = ?, 
                                     previous_school = ?, previous_track = ?, previous_strand = ?,
                                     semester = ?, school_year = ? 
                                 WHERE student_id = ?";
                    
                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                    mysqli_stmt_bind_param($shs_stmt, "sssssssi", 
                                         $track, $strand, 
                                         $previous_school, $previous_track, $previous_strand, 
                                         $semester, $school_year, 
                                         $student_id);
                } else {
                    // Insert new record
                    $shs_query = "INSERT INTO senior_highschool_details 
                                 (student_id, track, strand, semester, school_year, 
                                  previous_school, previous_track, previous_strand) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                    mysqli_stmt_bind_param($shs_stmt, "isssssss", 
                                         $student_id, $track, $strand, $semester, $school_year, 
                                         $previous_school, $previous_track, $previous_strand);
                }
                
                if (!mysqli_stmt_execute($shs_stmt)) {
                    // Log the error but continue
                    $log_desc = "Error updating SHS details for student ID $student_id: " . mysqli_error($conn);
                    logAction($_SESSION['user_id'], 'ERROR', $log_desc);
                } else {
                    // Log success
                    $log_desc = "Updated SHS details for student: $first_name $last_name, Track: $track, Strand: $strand";
                    logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                }
            }
            
            // Log action
            $log_desc = "Updated student: $first_name $last_name (LRN: $lrn)";
            logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
            
            // Check if enrollment status has changed
            if ($student['enrollment_status'] != $enrollment_status || 
                $student['grade_level'] != $grade_level || 
                $student['strand'] != $strand || 
                $student['section'] != $section) {
                
                // Record enrollment history
                // Get current school year
                $school_year_query = "SELECT school_year FROM school_years WHERE is_current = 1 LIMIT 1";
                $school_year_result = mysqli_query($conn, $school_year_query);
                $school_year = '2025-2026'; // Default if not found
                
                if (mysqli_num_rows($school_year_result) > 0) {
                    $school_year_row = mysqli_fetch_assoc($school_year_result);
                    $school_year = $school_year_row['school_year'];
                }
                
                // Get current semester
                $semester_query = "SELECT semester FROM school_years WHERE is_current = 1 LIMIT 1";
                $semester_result = mysqli_query($conn, $semester_query);
                $semester = 'First'; // Default if not found
                
                if (mysqli_num_rows($semester_result) > 0) {
                    $semester_row = mysqli_fetch_assoc($semester_result);
                    if (!empty($semester_row['semester'])) {
                        $semester = $semester_row['semester'];
                    }
                }
                
                // Prepare notes based on changes
                $notes = "Status updated from {$student['enrollment_status']} to {$enrollment_status}";
                
                if ($student['grade_level'] != $grade_level) {
                    $notes .= ", Grade level changed from {$student['grade_level']} to {$grade_level}";
                }
                
                if ($student['strand'] != $strand) {
                    $notes .= ", Strand changed from {$student['strand']} to {$strand}";
                }
                
                if ($student['section'] != $section) {
                    $notes .= ", Section changed from {$student['section']} to {$section}";
                }
                
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
                    'notes' => $notes
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
                    } else {
                        $_SESSION['alert'] = showAlert('Student updated successfully and enrollment history recorded.', 'success');
                    }
                }
            } else {
                $_SESSION['alert'] = showAlert('Student updated successfully.', 'success');
            }
            
            redirect('modules/registrar/students.php');
        } else {
            $errors[] = 'Error updating student: ' . mysqli_error($conn);
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Student</h1>
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
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $student_id; ?>" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="lrn">LRN <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lrn" name="lrn" required 
                                   value="<?php echo htmlspecialchars($student['lrn']); ?>"
                                   pattern="[0-9]{12}" maxlength="12" title="LRN must be exactly 12 digits">
                            <small class="form-text text-muted">Enter exactly 12 digits as required by DepEd.</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="photo">Student Photo</label>
                            <?php if (!empty($student['photo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $relative_path . htmlspecialchars($student['photo']); ?>" 
                                         alt="Student Photo" class="img-thumbnail" style="max-height: 100px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="delete_photo" name="delete_photo" value="1">
                                        <label class="form-check-label" for="delete_photo">Delete current photo</label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control-file" id="photo" name="photo">
                            <small class="form-text text-muted">Upload a new photo (JPG, JPEG, PNG)</small>
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
                                   value="<?php echo htmlspecialchars($student['first_name']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                   value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required 
                                   value="<?php echo htmlspecialchars($student['last_name']); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dob">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="dob" name="dob" required 
                                   value="<?php echo $student['dob']; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender">Sex <span class="text-danger">*</span></label>
                            <select class="form-control" id="gender" name="gender" required>
                                <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="religion">Religion</label>
                            <input type="text" class="form-control" id="religion" name="religion" 
                                   value="<?php echo htmlspecialchars($student['religion']); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_number">Contact Number</label>
                            <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                   value="<?php echo htmlspecialchars($student['contact_number']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($student['email']); ?>">
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
                                   value="<?php echo htmlspecialchars($student['father_name']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="father_occupation">Father's Occupation</label>
                            <input type="text" class="form-control" id="father_occupation" name="father_occupation" 
                                   value="<?php echo htmlspecialchars($student['father_occupation']); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mother_name">Mother's Name</label>
                            <input type="text" class="form-control" id="mother_name" name="mother_name" 
                                   value="<?php echo htmlspecialchars($student['mother_name']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mother_occupation">Mother's Occupation</label>
                            <input type="text" class="form-control" id="mother_occupation" name="mother_occupation" 
                                   value="<?php echo htmlspecialchars($student['mother_occupation']); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="guardian_name">Guardian's Name</label>
                            <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                                   value="<?php echo htmlspecialchars($student['guardian_name']); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="guardian_contact">Guardian's Contact</label>
                            <input type="text" class="form-control" id="guardian_contact" name="guardian_contact" 
                                   value="<?php echo htmlspecialchars($student['guardian_contact']); ?>">
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
                                <option value="Grade 11" <?php echo ($student['grade_level'] === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                                <option value="Grade 12" <?php echo ($student['grade_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
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
                                    $selected = ($student['strand'] === $row['strand_code']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['strand_code']) . "' $selected
                                          data-track='" . htmlspecialchars($row['track_name']) . "'>" . 
                                         htmlspecialchars($row['strand_code'] . ' - ' . $row['strand_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <input type="hidden" id="track" name="track" value="<?php echo isset($student['track']) ? htmlspecialchars($student['track']) : ''; ?>">
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
                                    $selected = ($student['section'] === $row['name']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['name']) . "' $selected 
                                          data-grade='" . htmlspecialchars($row['grade_level']) . "' 
                                          data-strand='" . htmlspecialchars($row['strand']) . "'>" . 
                                          htmlspecialchars($row['name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="form-text text-warning">
                                <strong>Important:</strong> Only assign a section to students who have paid their downpayment (status should be "Enrolled").
                                Students with "Pending" status should not be assigned a section yet.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="enrollment_status">Enrollment Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="enrollment_status" name="enrollment_status" required>
                                <option value="enrolled" <?php echo ($student['enrollment_status'] === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                                <option value="pending" <?php echo ($student['enrollment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="withdrawn" <?php echo ($student['enrollment_status'] === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                                <option value="irregular" <?php echo ($student['enrollment_status'] === 'irregular') ? 'selected' : ''; ?>>Irregular</option>
                                <option value="graduated" <?php echo ($student['enrollment_status'] === 'graduated') ? 'selected' : ''; ?>>Graduated</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="has_voucher">Has Voucher? <span class="text-danger">*</span></label>
                            <select class="form-control" id="has_voucher" name="has_voucher" required>
                                <option value="0" <?php echo (!isset($student['has_voucher']) || $student['has_voucher'] == 0) ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo (isset($student['has_voucher']) && $student['has_voucher'] == 1) ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="voucher_number">Voucher Number</label>
                            <input type="text" class="form-control" id="voucher_number" name="voucher_number"
                                value="<?php echo isset($student['voucher_number']) ? htmlspecialchars($student['voucher_number']) : ''; ?>"
                                placeholder="Enter voucher number if applicable">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="student_type">Student Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="student_type" name="student_type" required>
                                <option value="new" <?php echo (!isset($student['student_type']) || $student['student_type'] === 'new') ? 'selected' : ''; ?>>New Student</option>
                                <option value="old" <?php echo (isset($student['student_type']) && $student['student_type'] === 'old') ? 'selected' : ''; ?>>Old Student</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="semester">Semester <span class="text-danger">*</span></label>
                            <select class="form-control" id="semester" name="semester" required>
                                <option value="First" <?php echo (!isset($student['semester']) || $student['semester'] === 'First') ? 'selected' : ''; ?>>First Semester</option>
                                <option value="Second" <?php echo (isset($student['semester']) && $student['semester'] === 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="school_year">School Year <span class="text-danger">*</span></label>
                            <select class="form-control" id="school_year" name="school_year" required>
                                <option value="">Select School Year</option>
                                <?php
                                // Get all school years from the school_years table
                                $query = "SELECT school_year FROM school_years ORDER BY school_year DESC";
                                $result = mysqli_query($conn, $query);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $selected = ($student['school_year'] === $row['school_year']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['school_year']) . "' $selected>" . htmlspecialchars($row['school_year']) . "</option>";
                                }
                                ?>
                            </select>
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
                                   value="<?php echo isset($student['previous_school']) ? htmlspecialchars($student['previous_school']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="previous_track">Previous Track</label>
                            <input type="text" class="form-control" id="previous_track" name="previous_track" 
                                   value="<?php echo isset($student['previous_track']) ? htmlspecialchars($student['previous_track']) : ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="previous_strand">Previous Strand</label>
                            <input type="text" class="form-control" id="previous_strand" name="previous_strand" 
                                   value="<?php echo isset($student['previous_strand']) ? htmlspecialchars($student['previous_strand']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Student</button>
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
    const studentType = document.getElementById('student_type');
    const semester = document.getElementById('semester');
    const schoolYear = document.getElementById('school_year');
    
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
            // For pending status, clear section and make it optional but not required
            if (sectionDropdown.value !== '') {
                alert('Warning: Students with "Pending" status should not be assigned a section yet. Section has been cleared.');
                sectionDropdown.value = '';
            }
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
    
    // Add event listeners
    gradeLevel.addEventListener('change', filterSections);
    strand.addEventListener('change', function() {
        filterSections();
        updateTrack();
    });
    
    enrollmentStatus.addEventListener('change', checkEnrollmentStatusAndSection);
    
    // Voucher handling
    const hasVoucher = document.getElementById('has_voucher');
    const voucherNumber = document.getElementById('voucher_number');
    
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
    
    // Add event listener for voucher field
    hasVoucher.addEventListener('change', updateVoucherField);
    
    // Initial calls
    filterSections();
    updateTrack();
    checkEnrollmentStatusAndSection(); // Check initial status
    updateVoucherField(); // Initialize voucher field
    
    // Handle photo deletion checkbox
    const deletePhotoCheckbox = document.getElementById('delete_photo');
    const photoInput = document.getElementById('photo');
    
    if (deletePhotoCheckbox) {
        deletePhotoCheckbox.addEventListener('change', function() {
            if (this.checked) {
                photoInput.disabled = true;
            } else {
                photoInput.disabled = false;
            }
        });
    }
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
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 