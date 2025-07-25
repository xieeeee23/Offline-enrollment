<?php
// This is a fixed version of students.php with an additional closing brace
// Original file had an unclosed brace on line 236


$title = 'Senior High School Enrollment System';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Check if students table exists, create if not
$query = "SHOW TABLES LIKE 'students'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Create the students table
    $query = "CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lrn VARCHAR(20) NOT NULL UNIQUE,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        dob DATE NOT NULL,
        gender ENUM('Male', 'Female', 'Other') NOT NULL,
        address TEXT,
        contact_number VARCHAR(20),
        guardian_name VARCHAR(100),
        guardian_contact VARCHAR(20),
        grade_level VARCHAR(20) NOT NULL,
        section VARCHAR(20) NOT NULL,
        enrollment_status ENUM('enrolled', 'pending', 'withdrawn') DEFAULT 'pending',
        photo VARCHAR(255) DEFAULT NULL,
        enrolled_by INT,
        date_enrolled DATE,
        education_level_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (education_level_id) REFERENCES education_levels(id) ON DELETE SET NULL
    )";
    
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error creating students table: ' . mysqli_error($conn), 'danger');
    }
    
    // Create directory for student photos if it doesn't exist
    $upload_dir = $relative_path . 'uploads/students';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
} else {
    // Check if photo column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'photo'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add photo column to students table
        $query = "ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
        if (!mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert('Error adding photo column to students table: ' . mysqli_error($conn), 'danger');
        } else {
            $_SESSION['alert'] = showAlert('Photo column added to students table.', 'success');
        }
    }
    
    // Check if address column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'address'";
    $result = mysqli_query($conn, $query);
    if (mysqli_num_rows($result) == 0) {
        // Add address column to students table
        $query = "ALTER TABLE students ADD COLUMN address TEXT AFTER gender";
        if (!mysqli_query($conn, $query)) {
            $_SESSION['alert'] = showAlert('Error adding address column to students table: ' . mysqli_error($conn), 'danger');
        } else {
            $_SESSION['alert'] = showAlert('Address column added to students table.', 'success');
        }
    }
    
    // Check if education_level_id column exists in students table
    $query = "SHOW COLUMNS FROM students LIKE 'education_level_id'";
    $result = mysqli_query($conn, $query);
    $education_level_column_exists = mysqli_num_rows($result) > 0;
    
    if ($education_level_column_exists) {
        // Remove education_level_id column from students table if it exists
        $query = "ALTER TABLE students DROP FOREIGN KEY students_ibfk_2";
        mysqli_query($conn, $query);
        
        $query = "ALTER TABLE students DROP COLUMN education_level_id";
        mysqli_query($conn, $query);
    }
}

// Create directory for student photos if it doesn't exist
$upload_dir = $relative_path . 'uploads/students';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check if gender column exists in students table
$query = "SHOW COLUMNS FROM students LIKE 'gender'";
$result = mysqli_query($conn, $query);
if (mysqli_num_rows($result) == 0) {
    // Add gender column to students table
    $query = "ALTER TABLE students ADD COLUMN gender ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male' AFTER last_name";
    if (!mysqli_query($conn, $query)) {
        $_SESSION['alert'] = showAlert('Error adding gender column to students table: ' . mysqli_error($conn), 'danger');
    } else {
        $_SESSION['alert'] = showAlert('Gender column added to students table.', 'success');
    }
}

// Process delete student
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delete_id = (int) $_GET['id'];
    
    // Check if student exists
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $student = mysqli_fetch_assoc($result);
        
        // Delete student photo if exists
        if (!empty($student['photo'])) {
            $full_path = $relative_path . $student['photo'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
        
        // Delete student
        $query = "DELETE FROM students WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log action
            $log_desc = "Deleted student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
            logAction($_SESSION['user_id'], 'DELETE', $log_desc);
            
            $_SESSION['alert'] = showAlert('Student deleted successfully.', 'success');
        } else {
            $_SESSION['alert'] = showAlert('Error deleting student: ' . mysqli_error($conn), 'danger');
        }
    } else {
        $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    }
    
    // Redirect to students page
    redirect('modules/registrar/students.php');
}

// Process form submission for adding/editing student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_student'])) {
    $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : null;
    $lrn = cleanInput($_POST['lrn'] ?? '');
    $first_name = cleanInput($_POST['first_name']);
    $middle_name = cleanInput($_POST['middle_name'] ?? '');
    $last_name = cleanInput($_POST['last_name']);
    $dob = cleanInput($_POST['dob']);
    $gender = cleanInput($_POST['gender']);
    $religion = cleanInput($_POST['religion'] ?? '');
    $address = cleanInput($_POST['address']);
    $contact_number = cleanInput($_POST['contact_number']);
    $email = cleanInput($_POST['email'] ?? '');
    $father_name = cleanInput($_POST['father_name'] ?? '');
    $father_occupation = cleanInput($_POST['father_occupation'] ?? '');
    $mother_name = cleanInput($_POST['mother_name'] ?? '');
    $mother_occupation = cleanInput($_POST['mother_occupation'] ?? '');
    $grade_level = cleanInput($_POST['grade_level']);
    $section = cleanInput($_POST['section']);
    $enrollment_status = cleanInput($_POST['enrollment_status']);
    
    // Validate input
    $errors = [];
    
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    }
    
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required.';
    } elseif (strtotime($dob) === false) {
        $errors[] = 'Invalid date of birth.';
    }
    
    if (empty($gender)) {
        $errors[] = 'Gender is required.';
    }
    
    if (empty($grade_level)) {
        $errors[] = 'Grade level is required.';
    }
    
    if (empty($section)) {
        $errors[] = 'Section is required.';
    }
    
    if (empty($enrollment_status)) {
        $errors[] = 'Enrollment status is required.';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required.';
    }
    
    if (empty($contact_number)) {
        $errors[] = 'Contact number is required.';
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Auto-generate LRN if empty
    if (empty($lrn)) {
        $current_year = date('Y');
        $random_num = mt_rand(100000, 999999);
        $lrn = $current_year . str_pad($random_num, 6, '0', STR_PAD_LEFT);
        
        // Check if generated LRN exists
        while (valueExists('students', 'lrn', $lrn)) {
            $random_num = mt_rand(100000, 999999);
            $lrn = $current_year . str_pad($random_num, 6, '0', STR_PAD_LEFT);
        }
    } else if (valueExists('students', 'lrn', $lrn, $edit_id)) {
        $errors[] = 'LRN already exists.';
    }
    
    if (empty($errors)) {
        // Process photo upload if provided
        $photo_path = null;
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['student_photo']['tmp_name'];
            $file_name = $_FILES['student_photo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Check if file is an image
            $allowed_exts = ['jpg', 'jpeg', 'png'];
            if (!in_array($file_ext, $allowed_exts)) {
                $errors[] = 'Only JPG, JPEG and PNG files are allowed for the photo.';
            } else {
                // Create upload directory if it doesn't exist
                $upload_dir = $relative_path . 'uploads/students';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate a unique filename
                $new_filename = 'student_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
                $file_path = 'uploads/students/' . $new_filename;
                $full_path = $relative_path . $file_path;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $full_path)) {
                    $photo_path = $file_path;
                } else {
                    $errors[] = 'Error uploading photo.';
                }
            }
        }
        
        if (empty($errors)) {
            if ($edit_id === null) {
                // Add new student
                if ($photo_path) {
                    // INSERT with photo - 19 params (18 strings + 1 integer)
                    $query = "INSERT INTO students (lrn, first_name, middle_name, last_name, dob, gender, religion, 
                            address, contact_number, email, father_name, father_occupation, mother_name, 
                            mother_occupation, grade_level, section, enrollment_status, photo, enrolled_by, date_enrolled) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    
                    // 19 parameters: 18 strings ('s') + 1 integer ('i')
                    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssi", 
                                       $lrn, $first_name, $middle_name, $last_name, 
                                       $dob, $gender, $religion, $address, $contact_number, $email, 
                                       $father_name, $father_occupation, $mother_name, $mother_occupation, 
                                       $grade_level, $section, $enrollment_status, $photo_path, $_SESSION['user_id']);
                    } else {
                    // INSERT without photo - 18 params (17 strings + 1 integer)
                    $query = "INSERT INTO students (lrn, first_name, middle_name, last_name, dob, gender, religion, 
                            address, contact_number, email, father_name, father_occupation, mother_name, 
                            mother_occupation, grade_level, section, enrollment_status, enrolled_by, date_enrolled) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    
                    // 18 parameters: 17 strings ('s') + 1 integer ('i')
                    mysqli_stmt_bind_param($stmt, "sssssssssssssssssi", $lrn, $first_name, $middle_name, $last_name, 
                                       $dob, $gender, $religion, $address, $contact_number, $email, 
                                       $father_name, $father_occupation, $mother_name, $mother_occupation, 
                                       $grade_level, $section, $enrollment_status, $_SESSION['user_id']);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    $student_id = mysqli_insert_id($conn);
                    
                    // Log action
                    $log_desc = "Enrolled new student: {$first_name} {$last_name} (LRN: {$lrn})";
                    if ($photo_path) {
                        $log_desc .= " with photo";
                    }
                    logAction($_SESSION['user_id'], 'CREATE', $log_desc);
                    
                    // Save SHS details if grade level is 11 or 12
                    if (in_array($grade_level, ['Grade 11', 'Grade 12']) && isset($_POST['track']) && isset($_POST['strand'])) {
                        $track = cleanInput($_POST['track']);
                        $strand = cleanInput($_POST['strand']);
                        $semester = cleanInput($_POST['semester']);
                        $school_year = cleanInput($_POST['school_year']);
                        $previous_school = cleanInput($_POST['previous_school'] ?? '');
                        $previous_track = cleanInput($_POST['previous_track'] ?? '');
                        $previous_strand = cleanInput($_POST['previous_strand'] ?? '');
                        
                        $shs_query = "INSERT INTO senior_highschool_details 
                                      (student_id, track, strand, semester, school_year, previous_school, previous_track, previous_strand)
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $shs_stmt = mysqli_prepare($conn, $shs_query);
                        mysqli_stmt_bind_param($shs_stmt, "isssssss", 
                                              $student_id, $track, $strand, $semester, $school_year, 
                                              $previous_school, $previous_track, $previous_strand);
                        mysqli_stmt_execute($shs_stmt);
                        
                        $log_desc = "Added SHS details for student: {$first_name} {$last_name}, Track: {$track}, Strand: {$strand}";
                        logAction($_SESSION['user_id'], 'CREATE', $log_desc);
                    }
                    
                    $_SESSION['alert'] = showAlert('Student enrolled successfully.', 'success');
                    redirect('modules/registrar/students.php');
                } else {
                    $_SESSION['alert'] = showAlert('Error enrolling student: ' . mysqli_error($conn), 'danger');
                }
            } else {
                // Update existing student
                if ($photo_path) {
                    // Delete old photo if exists
                    $query = "SELECT photo FROM students WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $edit_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if ($row = mysqli_fetch_assoc($result)) {
                        $old_photo = $row['photo'];
                        if (!empty($old_photo)) {
                            $old_path = $relative_path . $old_photo;
                            if (file_exists($old_path)) {
                                unlink($old_path);
                            }
                        }
                    }
                    
                    // Update with new photo - 19 params (18 strings + 1 integer)
                    $query = "UPDATE students SET lrn = ?, first_name = ?, middle_name = ?, last_name = ?, dob = ?, 
                            gender = ?, religion = ?, address = ?, contact_number = ?, email = ?, father_name = ?, 
                            father_occupation = ?, mother_name = ?, mother_occupation = ?, grade_level = ?, section = ?, 
                            enrollment_status = ?, photo = ? WHERE id = ?";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    
                    // 19 parameters: 18 strings ('s') + 1 integer ('i')
                    mysqli_stmt_bind_param($stmt, "ssssssssssssssssssi", $lrn, $first_name, $middle_name, $last_name, $dob, 
                             $gender, $religion, $address, $contact_number, $email, $father_name, 
                             $father_occupation, $mother_name, $mother_occupation, $grade_level, 
                             $section, $enrollment_status, $photo_path, $edit_id);
                } else {
                    // Update without changing photo
                    $query = "UPDATE students SET lrn = ?, first_name = ?, middle_name = ?, last_name = ?, dob = ?, 
                            gender = ?, religion = ?, address = ?, contact_number = ?, email = ?, father_name = ?, 
                            father_occupation = ?, mother_name = ?, mother_occupation = ?, grade_level = ?, section = ?, 
                            enrollment_status = ? WHERE id = ?";
                    
                    $stmt = mysqli_prepare($conn, $query);
                    
                    // 18 parameters: 17 strings ('s') + 1 integer ('i')
                    mysqli_stmt_bind_param($stmt, "sssssssssssssssssi", 
                             $lrn, $first_name, $middle_name, $last_name, $dob, 
                             $gender, $religion, $address, $contact_number, $email, $father_name, 
                             $father_occupation, $mother_name, $mother_occupation, $grade_level, 
                             $section, $enrollment_status, $edit_id);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    // Log action
                    $log_desc = "Updated student: {$first_name} {$last_name} (LRN: {$lrn})";
                    if ($photo_path) {
                        $log_desc .= " and changed photo";
                    }
                    logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                    
            // Update or insert SHS details if grade level is 11 or 12
            if (in_array($grade_level, ['Grade 11', 'Grade 12']) && isset($_POST['track']) && isset($_POST['strand'])) {
                $track = cleanInput($_POST['track']);
                $strand = cleanInput($_POST['strand']);
                $semester = cleanInput($_POST['semester']);
                $school_year = cleanInput($_POST['school_year']);
                $previous_school = cleanInput($_POST['previous_school'] ?? '');
                $previous_track = cleanInput($_POST['previous_track'] ?? '');
                $previous_strand = cleanInput($_POST['previous_strand'] ?? '');
                
                // Check if SHS details already exist
                $check_shs_query = "SELECT id FROM senior_highschool_details WHERE student_id = ?";
                $check_shs_stmt = mysqli_prepare($conn, $check_shs_query);
                mysqli_stmt_bind_param($check_shs_stmt, "i", $edit_id);
                mysqli_stmt_execute($check_shs_stmt);
                $check_shs_result = mysqli_stmt_get_result($check_shs_stmt);
                
                if (mysqli_num_rows($check_shs_result) > 0) {
                    // Update existing SHS details
                    $shs_query = "UPDATE senior_highschool_details 
                                  SET track = ?, strand = ?, semester = ?, school_year = ?, 
                                      previous_school = ?, previous_track = ?, previous_strand = ? 
                                  WHERE student_id = ?";
                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                    mysqli_stmt_bind_param($shs_stmt, "sssssssi", 
                                          $track, $strand, $semester, $school_year, 
                                          $previous_school, $previous_track, $previous_strand, $edit_id);
                    mysqli_stmt_execute($shs_stmt);
                    
                    $log_desc = "Updated SHS details for student: {$first_name} {$last_name}, Track: {$track}, Strand: {$strand}";
                    logAction($_SESSION['user_id'], 'UPDATE', $log_desc);
                } else {
                    // Insert new SHS details
                    $shs_query = "INSERT INTO senior_highschool_details 
                                  (student_id, track, strand, semester, school_year, previous_school, previous_track, previous_strand)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                    mysqli_stmt_bind_param($shs_stmt, "isssssss", 
                                          $edit_id, $track, $strand, $semester, $school_year, 
                                          $previous_school, $previous_track, $previous_strand);
                    mysqli_stmt_execute($shs_stmt);
                    
                    $log_desc = "Added SHS details for student: {$first_name} {$last_name}, Track: {$track}, Strand: {$strand}";
                    logAction($_SESSION['user_id'], 'CREATE', $log_desc);
                }
            }
            
            $_SESSION['alert'] = showAlert('Student updated successfully.', 'success');
            redirect('modules/registrar/students.php');
        }
    }
    
    if (!empty($errors)) {
        // Display errors
        $error_list = '<ul>';
        foreach ($errors as $error) {
            $error_list .= '<li>' . $error . '</li>';
        }
        $error_list .= '</ul>';
        $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
    }
}

// Process student photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo']) && isset($_POST['student_id'])) {
    $student_id = (int) $_POST['student_id'];
    
    // Check if file was uploaded
    if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['student_photo']['tmp_name'];
        $file_name = $_FILES['student_photo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if file is an image
        $allowed_exts = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_ext, $allowed_exts)) {
            $_SESSION['alert'] = showAlert('Only JPG, JPEG and PNG files are allowed.', 'danger');
            redirect('modules/registrar/students.php?action=edit&id=' . $student_id);
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = $relative_path . 'uploads/students';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate a unique filename
        $new_filename = 'student_' . $student_id . '_' . time() . '.' . $file_ext;
        $file_path = 'uploads/students/' . $new_filename;
        $full_path = $relative_path . $file_path;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $full_path)) {
            // Update student record with photo path
            $query = "UPDATE students SET photo = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $file_path, $student_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Student photo uploaded successfully.', 'success');
                
                // Log action
                logAction($_SESSION['user_id'], 'UPLOAD', 'Uploaded photo for student ID: ' . $student_id);
            } else {
                $_SESSION['alert'] = showAlert('Error updating student record: ' . mysqli_error($conn), 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('Error uploading file.', 'danger');
        }
        
        redirect('modules/registrar/students.php?action=edit&id=' . $student_id);
    } else {
        $_SESSION['alert'] = showAlert('No file selected or error uploading file.', 'warning');
        redirect('modules/registrar/students.php?action=edit&id=' . $student_id);
    }
}

// Process student photo deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_photo' && isset($_GET['id'])) {
    $student_id = (int) $_GET['id'];
    
    // Get current photo path
    $query = "SELECT photo FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $photo_path = $row['photo'];
        
        if (!empty($photo_path)) {
            // Delete file if it exists
            $full_path = $relative_path . $photo_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            
            // Update student record
            $query = "UPDATE students SET photo = NULL WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['alert'] = showAlert('Student photo deleted successfully.', 'success');
                
                // Log action
                logAction($_SESSION['user_id'], 'DELETE', 'Deleted photo for student ID: ' . $student_id);
            } else {
                $_SESSION['alert'] = showAlert('Error updating student record: ' . mysqli_error($conn), 'danger');
            }
        } else {
            $_SESSION['alert'] = showAlert('No photo found for this student.', 'warning');
        }
    } else {
        $_SESSION['alert'] = showAlert('Student not found.', 'danger');
    }
    
    redirect('modules/registrar/students.php?action=edit&id=' . $student_id);
}

// Add a new action to view students with photos
if (isset($_GET['action']) && $_GET['action'] === 'view_photos') {
    $students_with_photos = [];
    $query = "SELECT id, lrn, first_name, last_name, grade_level, section, enrollment_status, photo 
              FROM students 
              WHERE photo IS NOT NULL AND photo != '' 
              ORDER BY grade_level, section, last_name, first_name";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $students_with_photos[] = $row;
        }
    }
    ?>
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Students with Photos</h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Student Photo Gallery</h5>
                    <div>
                        <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Students
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($students_with_photos)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No students with photos found.
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php foreach ($students_with_photos as $student): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <img src="<?php echo $relative_path . $student['photo']; ?>" class="card-img-top" alt="Student Photo" style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">LRN: <?php echo htmlspecialchars($student['lrn']); ?></small><br>
                                                <small class="text-muted">Grade & Section: <?php echo htmlspecialchars($student['grade_level'] . ' - ' . $student['section']); ?></small><br>
                                                <span class="badge <?php echo $student['enrollment_status'] === 'enrolled' ? 'bg-success' : ($student['enrollment_status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                                    <?php echo ucfirst($student['enrollment_status']); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="card-footer">
                                            <div class="d-flex justify-content-between">
                                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=edit&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=delete_photo&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this photo?')">
                                                    <i class="fas fa-trash"></i> Delete Photo
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once $relative_path . 'includes/footer.php';
    exit;
}

// Get student to edit if edit parameter is set
$edit_student = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $edit_student = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['alert'] = showAlert('Student not found.', 'danger');
        redirect('modules/registrar/students.php');
    }
}

// Get filter parameters
$grade_filter = isset($_GET['grade']) ? cleanInput($_GET['grade']) : null;
$section_filter = isset($_GET['section']) ? cleanInput($_GET['section']) : null;
$status_filter = isset($_GET['status']) ? cleanInput($_GET['status']) : null;
$search = isset($_GET['search']) ? cleanInput($_GET['search']) : null;
$track_filter = isset($_GET['track']) ? cleanInput($_GET['track']) : null;
$strand_filter = isset($_GET['strand']) ? cleanInput($_GET['strand']) : null;
$semester_filter = isset($_GET['semester']) ? cleanInput($_GET['semester']) : null;
$school_year_filter = isset($_GET['school_year']) ? cleanInput($_GET['school_year']) : null;

// Get students with SHS details
$students = [];
$query = "SELECT s.* 
          FROM students s 
          LEFT JOIN senior_highschool_details shsd ON s.id = shsd.student_id
          WHERE 1=1";

// Add filters
if (!empty($grade_filter)) {
    $query .= " AND s.grade_level = '" . mysqli_real_escape_string($conn, $grade_filter) . "'";
}

if (!empty($section_filter)) {
    $query .= " AND s.section = '" . mysqli_real_escape_string($conn, $section_filter) . "'";
}

if (!empty($status_filter)) {
    $query .= " AND s.enrollment_status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
}

// Add track and strand filters
if (!empty($track_filter)) {
    $query .= " AND shsd.track = '" . mysqli_real_escape_string($conn, $track_filter) . "'";
}

if (!empty($strand_filter)) {
    $query .= " AND shsd.strand = '" . mysqli_real_escape_string($conn, $strand_filter) . "'";
}

// Add semester and school year filters
if (!empty($semester_filter)) {
    $query .= " AND shsd.semester = '" . mysqli_real_escape_string($conn, $semester_filter) . "'";
}

if (!empty($school_year_filter)) {
    $query .= " AND shsd.school_year = '" . mysqli_real_escape_string($conn, $school_year_filter) . "'";
}

if (!empty($search)) {
    $query .= " AND (s.lrn LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                OR s.first_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' 
                OR s.last_name LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";
}

$query .= " ORDER BY s.grade_level, s.section, s.last_name, s.first_name";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
}

// Get unique grade levels for filter
$grades = [];
$grade_query = "SELECT DISTINCT grade_level FROM students ORDER BY grade_level";
$grade_result = mysqli_query($conn, $grade_query);
if ($grade_result) {
    while ($row = mysqli_fetch_assoc($grade_result)) {
        $grades[] = $row['grade_level'];
    }
}

// Get unique sections for filter
$sections = [];
$section_query = "SELECT DISTINCT section FROM students ORDER BY section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result) {
    while ($row = mysqli_fetch_assoc($section_result)) {
        $sections[] = $row['section'];
    }
}

// Get unique school years for filter
$school_years = [];
$school_year_query = "SELECT DISTINCT school_year FROM senior_highschool_details ORDER BY school_year DESC";
$school_year_result = mysqli_query($conn, $school_year_query);
if ($school_year_result) {
    while ($row = mysqli_fetch_assoc($school_year_result)) {
        $school_years[] = $row['school_year'];
    }
}

// Get school years from the school_years table
$school_years = [];
$school_years_query = "SELECT school_year, is_current FROM school_years WHERE status = 'Active' ORDER BY year_start DESC";
$school_years_result = mysqli_query($conn, $school_years_query);

if ($school_years_result) {
    while ($row = mysqli_fetch_assoc($school_years_result)) {
        $school_years[] = $row;
    }
}
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Senior High School Enrollment System</h1>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Student Management</h5>
                <?php if (checkAccess(['admin'])): ?>
                <div>
                    <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=view_photos" class="btn btn-sm btn-light me-2">
                        <i class="fas fa-images me-1"></i> View Photos
                    </a>
                    <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=add" class="btn btn-sm btn-light">
                        <i class="fas fa-plus me-1"></i> Add Student
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="grade" class="form-label">Grade Level</label>
                        <select class="form-select" id="grade" name="grade">
                            <option value="">All Grades</option>
                            <option value="Grade 11" <?php echo ($grade_filter == 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                            <option value="Grade 12" <?php echo ($grade_filter == 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section">
                            <option value="">All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo $section; ?>" <?php echo ($section_filter == $section) ? 'selected' : ''; ?>>
                                    <?php echo $section; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="enrolled" <?php echo ($status_filter === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                            <option value="pending" <?php echo ($status_filter === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="withdrawn" <?php echo ($status_filter === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="semester" class="form-label">Semester</label>
                        <select class="form-select" id="semester" name="semester">
                            <option value="">All Semesters</option>
                            <option value="First" <?php echo ($semester_filter === 'First') ? 'selected' : ''; ?>>First Semester</option>
                            <option value="Second" <?php echo ($semester_filter === 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="school_year" class="form-label">School Year</label>
                        <select class="form-select" id="school_year" name="school_year">
                            <option value="">All School Years</option>
                            <?php foreach ($school_years as $school_year): ?>
                                <option value="<?php echo $school_year['school_year']; ?>" <?php echo ($school_year_filter == $school_year['school_year']) ? 'selected' : ''; ?>>
                                    <?php echo $school_year['school_year']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- SHS Track filter -->
                    <div class="col-md-3">
                        <label for="track_filter" class="form-label">Track</label>
                        <select class="form-select" id="track_filter" name="track">
                            <option value="">All Tracks</option>
                            <?php
                            $tracks_query = "SELECT DISTINCT track_name FROM shs_strands WHERE status = 'Active' ORDER BY track_name";
                            $tracks_result = mysqli_query($conn, $tracks_query);
                            
                            if ($tracks_result) {
                                while ($track = mysqli_fetch_assoc($tracks_result)) {
                                    $selected = ($track_filter === $track['track_name']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($track['track_name']) . "' $selected>" . htmlspecialchars($track['track_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- SHS Strand filter -->
                    <div class="col-md-3">
                        <label for="strand_filter" class="form-label">Strand</label>
                        <select class="form-select" id="strand_filter" name="strand">
                            <option value="">All Strands</option>
                            <?php
                            if (!empty($track_filter)) {
                                // If track is selected, show only strands for that track
                                $strands_query = "SELECT strand_code, strand_name FROM shs_strands WHERE track_name = ? AND status = 'Active' ORDER BY strand_name";
                                $strands_stmt = mysqli_prepare($conn, $strands_query);
                                mysqli_stmt_bind_param($strands_stmt, "s", $track_filter);
                                mysqli_stmt_execute($strands_stmt);
                                $strands_result = mysqli_stmt_get_result($strands_stmt);
                            } else {
                                // Otherwise show all strands
                                $strands_query = "SELECT strand_code, strand_name FROM shs_strands WHERE status = 'Active' ORDER BY track_name, strand_name";
                                $strands_result = mysqli_query($conn, $strands_query);
                            }
                            
                            if (isset($strands_result)) {
                                while ($strand = mysqli_fetch_assoc($strands_result)) {
                                    $selected = ($strand_filter === $strand['strand_code']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($strand['strand_code']) . "' $selected>" . 
                                         htmlspecialchars($strand['strand_code'] . ' - ' . $strand['strand_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" placeholder="Name or LRN">
                    </div>
                    
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                            <a href="<?php echo $relative_path; ?>modules/reports/student_list.php" class="btn btn-success ms-auto">
                                <i class="fas fa-print"></i> Print Student List
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Add or edit student form
if (isset($_GET['action']) && ($_GET['action'] === 'add' || $_GET['action'] === 'edit')) {
    $edit_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
    $student = null;
    
    if ($edit_id !== null) {
        // Get student details for editing
        $query = "SELECT * FROM students WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $edit_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
        } else {
            $_SESSION['alert'] = showAlert('Student not found.', 'danger');
            redirect($relative_path . 'modules/registrar/students.php');
        }
    }
    
    $form_title = ($edit_id === null) ? 'Add New Student' : 'Edit Student';
    ?>
    
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0"><?php echo $form_title; ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                        <?php if ($edit_id !== null): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Surname <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="gender" class="form-label">Sex <span class="text-danger">*</span></label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php echo (isset($student) && $student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($student) && $student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="dob" class="form-label">Birthdate <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dob" name="dob" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>" required>
                        </div>
                        
                            <div class="col-md-4">
                                <label for="religion" class="form-label">Religion</label>
                                <input type="text" class="form-control" id="religion" name="religion" value="<?php echo htmlspecialchars($student['religion'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">Select Grade</option>
                                    <option value="Grade 11" <?php echo (isset($student) && $student['grade_level'] === 'Grade 11') ? 'selected' : ''; ?>>Grade 11</option>
                                    <option value="Grade 12" <?php echo (isset($student) && $student['grade_level'] === 'Grade 12') ? 'selected' : ''; ?>>Grade 12</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                                <select class="form-select" id="section" name="section" required>
                                    <option value="">Select Section</option>
                                    <?php
                                    // Get all active sections
                                    $sections_query = "SELECT name FROM sections WHERE status = 'Active' ORDER BY grade_level, name";
                                    $sections_result = mysqli_query($conn, $sections_query);
                                    
                                    if ($sections_result) {
                                        while ($section_row = mysqli_fetch_assoc($sections_result)) {
                                            $selected = (isset($student) && $student['section'] === $section_row['name']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($section_row['name']) . "' $selected>" . 
                                                 htmlspecialchars($section_row['name']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Senior High School Information -->
                        <div class="card mb-3 bg-light">
                            <div class="card-header">
                                <h5 class="mb-0">Senior High School Information</h5>
                            </div>
                            <div class="card-body">
                        <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="track" class="form-label">Track <span class="text-danger">*</span></label>
                                        <select class="form-select" id="track" name="track" required>
                                            <option value="">Select Track</option>
                                    <?php 
                                            $tracks_query = "SELECT DISTINCT track_name FROM shs_strands WHERE status = 'Active' ORDER BY track_name";
                                            $tracks_result = mysqli_query($conn, $tracks_query);
                                            
                                            if ($tracks_result) {
                                                $tracks = [];
                                                while ($track = mysqli_fetch_assoc($tracks_result)) {
                                                    $tracks[] = $track['track_name'];
                                                }
                                                
                                                // Get selected track
                                                $selected_track = '';
                                                if (isset($student) && isset($student['id'])) {
                                                    $shs_query = "SELECT * FROM senior_highschool_details WHERE student_id = ?";
                                                    $shs_stmt = mysqli_prepare($conn, $shs_query);
                                                    mysqli_stmt_bind_param($shs_stmt, "i", $student['id']);
                                                    mysqli_stmt_execute($shs_stmt);
                                                    $shs_result = mysqli_stmt_get_result($shs_stmt);
                                                    if ($shs_data = mysqli_fetch_assoc($shs_result)) {
                                                        $selected_track = $shs_data['track'];
                                                    }
                                                }
                                                
                                                foreach ($tracks as $track) {
                                                    $selected = ($track == $selected_track) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($track) . "' $selected>" . htmlspecialchars($track) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                                    <div class="col-md-6">
                                        <label for="strand" class="form-label">Strand <span class="text-danger">*</span></label>
                                        <select class="form-select" id="strand" name="strand" required>
                                            <option value="">Select Track First</option>
                                            <?php
                                            // Get selected strand
                                            $selected_strand = '';
                                            if (isset($student) && isset($student['id'])) {
                                                if (isset($shs_data) && !empty($shs_data['strand'])) {
                                                    $selected_strand = $shs_data['strand'];
                                                }
                                            }
                                            
                                            if (!empty($selected_track)) {
                                                $strands_query = "SELECT strand_code, strand_name FROM shs_strands WHERE track_name = ? AND status = 'Active' ORDER BY strand_name";
                                                $strands_stmt = mysqli_prepare($conn, $strands_query);
                                                mysqli_stmt_bind_param($strands_stmt, "s", $selected_track);
                                                mysqli_stmt_execute($strands_stmt);
                                                $strands_result = mysqli_stmt_get_result($strands_stmt);
                                                
                                                while ($strand = mysqli_fetch_assoc($strands_result)) {
                                                    $selected = ($strand['strand_code'] == $selected_strand) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($strand['strand_code']) . "' $selected>" . 
                                                         htmlspecialchars($strand['strand_code'] . ' - ' . $strand['strand_name']) . "</option>";
                                                }
                                            }
                                            ?>
                                </select>
                            </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                        <select class="form-select" id="semester" name="semester" required>
                                            <option value="">Select Semester</option>
                                            <option value="First" <?php echo (isset($shs_data) && $shs_data['semester'] == 'First') ? 'selected' : ''; ?>>First Semester</option>
                                            <option value="Second" <?php echo (isset($shs_data) && $shs_data['semester'] == 'Second') ? 'selected' : ''; ?>>Second Semester</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="school_year" class="form-label">School Year <span class="text-danger">*</span></label>
                                        <select class="form-select" id="school_year" name="school_year" required>
                                            <option value="">Select School Year</option>
                                    <?php
                                            $current_year = date('Y');
                                            for ($i = 0; $i < 5; $i++) {
                                                $year = $current_year - $i;
                                                $school_year = $year . '-' . ($year + 1);
                                                $selected = (isset($shs_data) && $shs_data['school_year'] == $school_year) ? 'selected' : '';
                                                echo "<option value='" . $school_year . "' $selected>" . $school_year . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="previous_school" class="form-label">Previous School</label>
                                        <input type="text" class="form-control" id="previous_school" name="previous_school" value="<?php echo htmlspecialchars($shs_data['previous_school'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="previous_track" class="form-label">Previous Track</label>
                                        <input type="text" class="form-control" id="previous_track" name="previous_track" value="<?php echo htmlspecialchars($shs_data['previous_track'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="previous_strand" class="form-label">Previous Strand</label>
                                        <input type="text" class="form-control" id="previous_strand" name="previous_strand" value="<?php echo htmlspecialchars($shs_data['previous_strand'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="father_name" class="form-label">Father's Name</label>
                                <input type="text" class="form-control" id="father_name" name="father_name" value="<?php echo htmlspecialchars($student['father_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="father_occupation" class="form-label">Father's Occupation</label>
                                <input type="text" class="form-control" id="father_occupation" name="father_occupation" value="<?php echo htmlspecialchars($student['father_occupation'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mother_name" class="form-label">Mother's Name</label>
                                <input type="text" class="form-control" id="mother_name" name="mother_name" value="<?php echo htmlspecialchars($student['mother_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="mother_occupation" class="form-label">Mother's Occupation</label>
                                <input type="text" class="form-control" id="mother_occupation" name="mother_occupation" value="<?php echo htmlspecialchars($student['mother_occupation'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="address" class="form-label">Complete Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="2" required><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="enrollment_status" class="form-label">Enrollment Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="enrollment_status" name="enrollment_status" required>
                                    <option value="">Select Status</option>
                                    <option value="enrolled" <?php echo (isset($student) && $student['enrollment_status'] === 'enrolled') ? 'selected' : ''; ?>>Enrolled</option>
                                    <option value="pending" <?php echo (isset($student) && $student['enrollment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="withdrawn" <?php echo (isset($student) && $student['enrollment_status'] === 'withdrawn') ? 'selected' : ''; ?>>Withdrawn</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                            <label for="student_photo" class="form-label">Student Photo</label>
                            <input type="file" class="form-control" id="student_photo" name="student_photo" accept=".jpg,.jpeg,.png">
                            <small class="form-text text-muted">Accepted formats: JPG, JPEG, PNG</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                            <button type="submit" name="save_student" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if ($edit_id !== null): ?>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Student Photo</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($student['photo'])): ?>
                        <img src="<?php echo $relative_path . $student['photo']; ?>" class="img-fluid mb-3" alt="Student Photo" style="max-height: 250px;">
                        <div class="d-grid gap-2">
                            <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=delete_photo&id=<?php echo $edit_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this photo?')">
                                <i class="fas fa-trash me-1"></i> Delete Photo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center mb-3">
                            <i class="fas fa-user-circle fa-6x text-muted"></i>
                            <p class="text-muted mt-2">No photo uploaded</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAll = document.getElementById('select_all');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const bulkApplyBtn = document.getElementById('bulk_apply_btn');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                studentCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkButtonState();
            });
        }
        
        // Handle individual checkboxes
        studentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkButtonState();
                
                // Update "select all" checkbox
                if (!this.checked) {
                    selectAll.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(studentCheckboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            });
        });
        
        // Update bulk action button state
        function updateBulkButtonState() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            bulkApplyBtn.disabled = checkedCount === 0;
        }
        
        // Confirm bulk delete
        const bulkActionForm = document.getElementById('bulk_action_form');
        if (bulkActionForm) {
            bulkActionForm.addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="bulk_action"]').value;
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            
            if (action === 'delete' && checkedCount > 0) {
                if (!confirm(`Are you sure you want to delete ${checkedCount} selected student(s)?`)) {
                    e.preventDefault();
                }
            } else if (!action) {
                e.preventDefault();
                alert('Please select an action to perform.');
            }
        });
        }
        
        // Get the grade level and section select elements
        const gradeLevelSelect = document.getElementById('grade_level');
        const sectionSelect = document.getElementById('section');
        
        // Function to load sections based on grade level
        function loadSections(gradeLevel) {
            if (!gradeLevel) {
                // Clear section dropdown if no grade is selected
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                return;
            }
            
            const currentSelectedSection = sectionSelect.value;
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
            
            // AJAX request to get sections for the selected grade level
            fetch('<?php echo $relative_path; ?>modules/registrar/get_sections.php?grade_level=' + encodeURIComponent(gradeLevel))
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    
                    if (!data || data.length === 0) {
                        sectionSelect.innerHTML += '<option value="" disabled>No sections found</option>';
                    } else {
                        data.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.name;
                            option.textContent = section.name;
                            
                            // If this was the previously selected section, select it again
                            if (section.name === currentSelectedSection) {
                                option.selected = true;
                            }
                            
                            sectionSelect.appendChild(option);
                        });
                        
                        <?php if (isset($student) && !empty($student['section'])): ?>
                        // If editing and we have a selected section from database, try to select it
                        const savedSection = '<?php echo addslashes($student['section']); ?>';
                        if (!currentSelectedSection) {
                        for (let i = 0; i < sectionSelect.options.length; i++) {
                                if (sectionSelect.options[i].value === savedSection) {
                                sectionSelect.selectedIndex = i;
                                break;
                                }
                            }
                        }
                        <?php endif; ?>
                    }
                    
                    sectionSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    sectionSelect.disabled = false;
                });
        }
        
        // Add event listener to grade level select
        if (gradeLevelSelect) {
            gradeLevelSelect.addEventListener('change', function() {
                loadSections(this.value);
                
                // Show/hide SHS fields based on grade level
                toggleSHSFields(this.value);
            });
            
            // Initialize SHS fields visibility on page load
            toggleSHSFields(gradeLevelSelect.value);
            
            // Load sections on page load if grade is already selected
            if (gradeLevelSelect.value) {
                loadSections(gradeLevelSelect.value);
            }
        }
        
        // Function to show/hide SHS fields based on grade level
        function toggleSHSFields(gradeLevel) {
            const shsSection = document.querySelector('.card.mb-3.bg-light');
            if (shsSection) {
                if (gradeLevel === 'Grade 11' || gradeLevel === 'Grade 12') {
                    shsSection.style.display = 'block';
                    
                    // Make SHS fields required
                    document.querySelectorAll('#track, #strand, #semester, #school_year').forEach(el => {
                        el.setAttribute('required', 'required');
                    });
                } else {
                    shsSection.style.display = 'none';
                    
                    // Make SHS fields not required
                    document.querySelectorAll('#track, #strand, #semester, #school_year').forEach(el => {
                        el.removeAttribute('required');
                    });
                }
            }
        }
        
        // Track and strand handling for SHS
        const trackSelect = document.getElementById('track');
        if (trackSelect) {
            // Load strands on page load if track is already selected
            if (trackSelect.value) {
                loadStrands(trackSelect.value);
            }
            
            // Add event listener for track changes
            trackSelect.addEventListener('change', function() {
                loadStrands(this.value);
            });
        }
        
        // Function to load strands based on selected track
        function loadStrands(track) {
            if (!track) {
                // Clear strand dropdown if no track is selected
                document.getElementById('strand').innerHTML = '<option value="">Select Track First</option>';
                return;
            }
            
            const strandDropdown = document.getElementById('strand');
            const currentSelectedStrand = strandDropdown.value;
            strandDropdown.disabled = true;
            strandDropdown.innerHTML = '<option value="">Loading strands...</option>';
            
            // AJAX request to get strands for the selected track
            fetch('<?php echo $relative_path; ?>modules/registrar/get_strands.php?track=' + encodeURIComponent(track))
                .then(response => response.json())
                .then(strands => {
                    strandDropdown.innerHTML = '<option value="">Select Strand</option>';
                    
                    if (strands.length === 0) {
                        strandDropdown.innerHTML += '<option value="" disabled>No strands found for this track</option>';
                    } else {
                        strands.forEach(strand => {
                            const option = document.createElement('option');
                            option.value = strand.strand_code;
                            option.textContent = strand.strand_code + ' - ' + strand.strand_name;
                            
                            // If this was the previously selected strand, select it again
                            if (strand.strand_code === currentSelectedStrand) {
                                option.selected = true;
                            }
                            
                            strandDropdown.appendChild(option);
                        });
                    }
                    
                    strandDropdown.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading strands:', error);
                    strandDropdown.innerHTML = '<option value="">Error loading strands</option>';
                    strandDropdown.disabled = false;
                });
        }
        
        // Track filter functionality for student list
        const trackFilterSelect = document.getElementById('track_filter');
        const strandFilterSelect = document.getElementById('strand_filter');
        
        if (trackFilterSelect && strandFilterSelect) {
            trackFilterSelect.addEventListener('change', function() {
                updateStrandFilter(this.value);
            });
            
            // Function to update strand filter based on selected track
            function updateStrandFilter(track) {
                strandFilterSelect.disabled = true;
                strandFilterSelect.innerHTML = '<option value="">Loading strands...</option>';
                
                if (!track) {
                    // If no track selected, show "All Strands" option
                    strandFilterSelect.innerHTML = '<option value="">All Strands</option>';
                    strandFilterSelect.disabled = false;
                } else {
                    // Get strands for selected track
                    fetch('<?php echo $relative_path; ?>modules/registrar/get_strands.php?track=' + encodeURIComponent(track))
                        .then(response => response.json())
                        .then(strands => {
                            strandFilterSelect.innerHTML = '<option value="">All Strands</option>';
                            
                            if (strands.length === 0) {
                                strandFilterSelect.innerHTML += '<option value="" disabled>No strands found for this track</option>';
                            } else {
                                strands.forEach(strand => {
                                    const option = document.createElement('option');
                                    option.value = strand.strand_code;
                                    option.textContent = strand.strand_code + ' - ' + strand.strand_name;
                                    strandFilterSelect.appendChild(option);
                                });
                            }
                            
                            strandFilterSelect.disabled = false;
                        })
                        .catch(error => {
                            console.error('Error loading strands:', error);
                            strandFilterSelect.innerHTML = '<option value="">Error loading strands</option>';
                            strandFilterSelect.disabled = false;
                        });
                }
            }
        }
    });
    </script>
    
    <?php
    require_once $relative_path . 'includes/footer.php';
    exit;
}

// Process bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['student_ids'])) {
    $bulk_action = cleanInput($_POST['bulk_action']);
    $student_ids = $_POST['student_ids'];
    
    if (!empty($student_ids) && !empty($bulk_action)) {
        $count = 0;
        
        if ($bulk_action === 'delete') {
            foreach ($student_ids as $id) {
                $id = (int) $id;
                
                // Get student info for logging
                $query = "SELECT * FROM students WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $student = mysqli_fetch_assoc($result);
                
                if ($student) {
                    // Delete student photo if exists
                    if (!empty($student['photo'])) {
                        $full_path = $relative_path . $student['photo'];
                        if (file_exists($full_path)) {
                            unlink($full_path);
                        }
                    }
                    
                    // Delete student
                    $query = "DELETE FROM students WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $count++;
                        
                        // Log action
                        $log_desc = "Deleted student: {$student['first_name']} {$student['last_name']} (LRN: {$student['lrn']})";
                        logAction($_SESSION['user_id'], 'DELETE', $log_desc);
                    }
                }
            }
            
            $_SESSION['alert'] = showAlert("Successfully deleted {$count} student(s).", 'success');
        } elseif (in_array($bulk_action, ['enrolled', 'pending', 'withdrawn'])) {
            foreach ($student_ids as $id) {
                $id = (int) $id;
                
                $query = "UPDATE students SET enrollment_status = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "si", $bulk_action, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $count++;
                }
            }
            
            $_SESSION['alert'] = showAlert("Successfully updated {$count} student(s) to " . ucfirst($bulk_action) . " status.", 'success');
        }
        
        redirect($relative_path . 'modules/registrar/students.php');
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Student List</h5>
            </div>
            <div class="card-body">
                <?php if (checkAccess(['admin'])): ?>
                <form method="post" id="bulk_action_form">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <select class="form-select form-select-sm d-inline-block w-auto me-2" name="bulk_action">
                                    <option value="">Bulk Action</option>
                                    <option value="enrolled">Mark as Enrolled</option>
                                    <option value="pending">Mark as Pending</option>
                                    <option value="withdrawn">Mark as Withdrawn</option>
                                    <option value="delete">Delete Selected</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary" id="bulk_apply_btn" disabled>Apply</button>
                            </div>
                            <div>
                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=add" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus me-1"></i> Add New Student
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-success me-2 btn-print" data-print-target="#students-table">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                        <button type="button" class="btn btn-sm btn-primary btn-export-excel" data-table-id="students-table" data-filename="students_list_<?php echo date('Y-m-d'); ?>">
                            <i class="fas fa-file-excel me-1"></i> Export to Excel
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="students-table" class="table table-striped table-hover data-table">
                            <thead>
                                <tr>
                                    <?php if (checkAccess(['admin'])): ?>
                                    <th>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="select_all">
                                        </div>
                                    </th>
                                    <?php endif; ?>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Track/Strand</th>
                                    <th>Grade/Section</th>
                                    <th>Contact Info</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="<?php echo checkAccess(['admin']) ? 8 : 7; ?>" class="text-center">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <?php if (checkAccess(['admin'])): ?>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>">
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars($student['lrn']); ?></td>
                                            <td>
                                                <?php 
                                                    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
                                                    if (!empty($student['middle_name'])) {
                                                        echo ' ' . htmlspecialchars(substr($student['middle_name'], 0, 1) . '.');
                                                    }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                // Display strand and track for senior high school students
                                                $shs_query = "SELECT track, strand, semester, school_year FROM senior_highschool_details WHERE student_id = ?";
                                                $shs_stmt = mysqli_prepare($conn, $shs_query);
                                                mysqli_stmt_bind_param($shs_stmt, "i", $student['id']);
                                                mysqli_stmt_execute($shs_stmt);
                                                $shs_result = mysqli_stmt_get_result($shs_stmt);
                                                
                                                if ($shs_data = mysqli_fetch_assoc($shs_result)) {
                                                    echo '<span class="badge bg-primary">' . htmlspecialchars($shs_data['strand']) . '</span>';
                                                    echo '<br><small class="text-muted">' . htmlspecialchars($shs_data['track']) . '</small>';
                                                    
                                                    // Show semester and school year
                                                    if (!empty($shs_data['semester']) && !empty($shs_data['school_year'])) {
                                                        echo '<br><small>' . htmlspecialchars($shs_data['semester'] . ' Sem, ' . $shs_data['school_year']) . '</small>';
                                                    }
                                                } else {
                                                    echo '<span class="badge bg-warning text-dark">Not Set</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($student['grade_level'] . ' - ' . $student['section']); ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if (!empty($student['contact_number'])) {
                                                    echo '<i class="fas fa-phone-alt fa-sm me-1"></i> ' . htmlspecialchars($student['contact_number']);
                                                }
                                                
                                                if (!empty($student['email'])) {
                                                    echo '<br><i class="fas fa-envelope fa-sm me-1"></i> ' . htmlspecialchars($student['email']);
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = 'bg-secondary';
                                                switch ($student['enrollment_status']) {
                                                    case 'enrolled':
                                                        $status_class = 'bg-success';
                                                        break;
                                                    case 'pending':
                                                        $status_class = 'bg-warning text-dark';
                                                        break;
                                                    case 'withdrawn':
                                                        $status_class = 'bg-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($student['enrollment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo $relative_path; ?>modules/registrar/view_student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="View Student Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if (checkAccess(['admin', 'registrar'])): ?>
                                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=edit&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Edit Student">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (checkAccess(['admin'])): ?>
                                                <a href="<?php echo $relative_path; ?>modules/registrar/students.php?action=delete&id=<?php echo $student['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this student?')" data-bs-toggle="tooltip" title="Delete Student">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php if (checkAccess(['admin'])): ?>
                </form>
                <?php endif; ?>
                
                <div class="card-footer text-muted">
                    Total students: <?php echo count($students); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle select all checkbox
        const selectAll = document.getElementById('select_all');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox');
        const bulkApplyBtn = document.getElementById('bulk_apply_btn');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                studentCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAll.checked;
                });
                updateBulkButtonState();
            });
        }
        
        // Handle individual checkboxes
        studentCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkButtonState();
                
                // Update "select all" checkbox
                if (!this.checked) {
                    selectAll.checked = false;
                } else {
                    // Check if all checkboxes are checked
                    const allChecked = Array.from(studentCheckboxes).every(cb => cb.checked);
                    selectAll.checked = allChecked;
                }
            });
        });
        
        // Update bulk action button state
        function updateBulkButtonState() {
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            bulkApplyBtn.disabled = checkedCount === 0;
        }
        
        // Confirm bulk delete
        document.getElementById('bulk_action_form').addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="bulk_action"]').value;
            const checkedCount = document.querySelectorAll('.student-checkbox:checked').length;
            
            if (action === 'delete' && checkedCount > 0) {
                if (!confirm(`Are you sure you want to delete ${checkedCount} selected student(s)?`)) {
                    e.preventDefault();
                }
            } else if (!action) {
                e.preventDefault();
                alert('Please select an action to perform.');
            }
        });
    });
</script>

<script>
// Initialize sections dropdown when page loads and when grade level changes
document.addEventListener('DOMContentLoaded', function() {
    const gradeLevelSelect = document.getElementById('grade_level');
    if (gradeLevelSelect) {
        // Load sections on page load if grade is already selected
        if (gradeLevelSelect.value) {
            loadSections(gradeLevelSelect.value);
        }
        
        // Add event listener for grade level changes
        gradeLevelSelect.addEventListener('change', function() {
            loadSections(this.value);
            
            // Show/hide SHS fields based on grade level
            toggleSHSFields(this.value);
        });
        
        // Initialize SHS fields visibility on page load
        toggleSHSFields(gradeLevelSelect.value);
    }
    
    // Track and strand handling for SHS
    const trackSelect = document.getElementById('track');
    if (trackSelect) {
        // Load strands on page load if track is already selected
        if (trackSelect.value) {
            loadStrands(trackSelect.value);
        }
        
        // Add event listener for track changes
        trackSelect.addEventListener('change', function() {
            loadStrands(this.value);
        });
    }
});

// Function to show/hide SHS fields based on grade level
function toggleSHSFields(gradeLevel) {
    const shsSection = document.querySelector('.card.mb-3.bg-light');
    if (shsSection) {
        if (gradeLevel === 'Grade 11' || gradeLevel === 'Grade 12') {
            shsSection.style.display = 'block';
            
            // Make SHS fields required
            document.querySelectorAll('#track, #strand, #semester, #school_year').forEach(el => {
                el.setAttribute('required', 'required');
            });
        } else {
            shsSection.style.display = 'none';
            
            // Make SHS fields not required
            document.querySelectorAll('#track, #strand, #semester, #school_year').forEach(el => {
                el.removeAttribute('required');
            });
        }
    }
}

// Function to load strands based on selected track
function loadStrands(track) {
    if (!track) {
        // Clear strand dropdown if no track is selected
        document.getElementById('strand').innerHTML = '<option value="">Select Track First</option>';
        return;
    }
    
    const strandDropdown = document.getElementById('strand');
    const currentSelectedStrand = strandDropdown.value;
    strandDropdown.disabled = true;
    strandDropdown.innerHTML = '<option value="">Loading strands...</option>';
    
    // AJAX request to get strands for the selected track
    fetch('<?php echo $relative_path; ?>modules/registrar/get_strands.php?track=' + encodeURIComponent(track))
        .then(response => response.json())
        .then(strands => {
            strandDropdown.innerHTML = '<option value="">Select Strand</option>';
            
            if (strands.length === 0) {
                strandDropdown.innerHTML += '<option value="" disabled>No strands found for this track</option>';
            } else {
                strands.forEach(strand => {
                const option = document.createElement('option');
                    option.value = strand.strand_code;
                    option.textContent = strand.strand_code + ' - ' + strand.strand_name;
                    
                    // If this was the previously selected strand, select it again
                    if (strand.strand_code === currentSelectedStrand) {
                        option.selected = true;
                    }
                    
                    strandDropdown.appendChild(option);
                });
            }
            
            strandDropdown.disabled = false;
        })
        .catch(error => {
            console.error('Error loading strands:', error);
            strandDropdown.innerHTML = '<option value="">Error loading strands</option>';
            strandDropdown.disabled = false;
        });
}

// Track filter functionality for student list
const trackFilterSelect = document.getElementById('track_filter');
const strandFilterSelect = document.getElementById('strand_filter');

if (trackFilterSelect && strandFilterSelect) {
    trackFilterSelect.addEventListener('change', function() {
        updateStrandFilter(this.value);
    });
    
    // Function to update strand filter based on selected track
    function updateStrandFilter(track) {
        strandFilterSelect.disabled = true;
        strandFilterSelect.innerHTML = '<option value="">Loading strands...</option>';
        
        if (!track) {
            // If no track selected, show "All Strands" option
            strandFilterSelect.innerHTML = '<option value="">All Strands</option>';
            strandFilterSelect.disabled = false;
        } else {
            // Get strands for selected track
            fetch('<?php echo $relative_path; ?>modules/registrar/get_strands.php?track=' + encodeURIComponent(track))
                .then(response => response.json())
                .then(strands => {
                    strandFilterSelect.innerHTML = '<option value="">All Strands</option>';
                    
                    if (strands.length === 0) {
                        strandFilterSelect.innerHTML += '<option value="" disabled>No strands found for this track</option>';
                    } else {
                        strands.forEach(strand => {
                            const option = document.createElement('option');
                            option.value = strand.strand_code;
                            option.textContent = strand.strand_code + ' - ' + strand.strand_name;
                            strandFilterSelect.appendChild(option);
                        });
                    }
                    
                    strandFilterSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error loading strands:', error);
                    strandFilterSelect.innerHTML = '<option value="">Error loading strands</option>';
                    strandFilterSelect.disabled = false;
                });
        }
    }
}
</script>

<?php endif; ?>
<?php require_once $relative_path . 'includes/footer.php'; ?> 
} // Fixed unclosed brace

} // Added missing closing brace
 
