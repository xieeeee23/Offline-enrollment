<?php
// This section appears to be part of the students.php file
// Adding proper formatting and fixing any syntax issues

if ($education_level_column_exists) {
    // Remove education_level_id column from students table if it exists
    $query = "ALTER TABLE students DROP FOREIGN KEY students_ibfk_2";
    mysqli_query($conn, $query);
    
    $query = "ALTER TABLE students DROP COLUMN education_level_id";
    mysqli_query($conn, $query);
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
?> 