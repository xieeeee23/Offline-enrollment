<?php
$title = 'Manage SHS Schedule';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect($relative_path . 'dashboard.php');
    exit;
}

// Check if SHS_Schedule_List table exists
$check_table_query = "SHOW TABLES LIKE 'SHS_Schedule_List'";
$check_table_result = mysqli_query($conn, $check_table_query);
$table_exists = ($check_table_result && mysqli_num_rows($check_table_result) > 0);

// Create table if it doesn't exist
if (!$table_exists) {
    $create_table_query = "CREATE TABLE IF NOT EXISTS SHS_Schedule_List (
        id INT AUTO_INCREMENT PRIMARY KEY,
        teacher_id INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        section VARCHAR(50) NOT NULL,
        grade_level VARCHAR(20) NOT NULL,
        day_of_week VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        room VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE
    )";
    
    if (mysqli_query($conn, $create_table_query)) {
        $_SESSION['alert'] = showAlert('SHS_Schedule_List table created successfully!', 'success');
        $table_exists = true;
    } else {
        $_SESSION['alert'] = showAlert('Error creating table: ' . mysqli_error($conn), 'danger');
    }
}

// Process delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Delete the schedule entry
    $delete_query = "DELETE FROM SHS_Schedule_List WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, "i", $delete_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['alert'] = showAlert('Schedule entry deleted successfully!', 'success');
        // Log action
        logAction($_SESSION['user_id'], 'DELETE', 'Deleted schedule entry ID: ' . $delete_id);
    } else {
        $_SESSION['alert'] = showAlert('Error deleting schedule entry: ' . mysqli_error($conn), 'danger');
    }
    
    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Process form submission for adding/editing schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule']) || isset($_POST['edit_schedule'])) {
        $teacher_id = (int)$_POST['teacher_id'];
        $subject = cleanInput($_POST['subject']);
        $section = cleanInput($_POST['section']);
        $grade_level = cleanInput($_POST['grade_level']);
        $day_of_week = cleanInput($_POST['day_of_week']);
        $start_time = cleanInput($_POST['start_time']);
        $end_time = cleanInput($_POST['end_time']);
        $room = cleanInput($_POST['room']);
        
        // Validate inputs
        $errors = [];
        
        if (empty($teacher_id)) {
            $errors[] = 'Teacher is required';
        }
        
        if (empty($subject)) {
            $errors[] = 'Subject is required';
        }
        
        if (empty($section)) {
            $errors[] = 'Section is required';
        }
        
        if (empty($grade_level)) {
            $errors[] = 'Grade level is required';
        }
        
        if (empty($day_of_week)) {
            $errors[] = 'Day is required';
        }
        
        if (empty($start_time)) {
            $errors[] = 'Start time is required';
        }
        
        if (empty($end_time)) {
            $errors[] = 'End time is required';
        }
        
        // Check if end time is after start time
        if (!empty($start_time) && !empty($end_time) && strtotime($end_time) <= strtotime($start_time)) {
            $errors[] = 'End time must be after start time';
        }
        
        if (empty($errors)) {
            if (isset($_POST['edit_schedule'])) {
                // Update existing schedule
                $schedule_id = (int)$_POST['schedule_id'];
                
                $update_query = "UPDATE SHS_Schedule_List 
                                SET teacher_id = ?, subject = ?, section = ?, grade_level = ?, 
                                    day_of_week = ?, start_time = ?, end_time = ?, room = ? 
                                WHERE id = ?";
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "isssssssi", $teacher_id, $subject, $section, $grade_level, 
                                      $day_of_week, $start_time, $end_time, $room, $schedule_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Schedule updated successfully!', 'success');
                    // Log action
                    logAction($_SESSION['user_id'], 'UPDATE', 'Updated schedule entry ID: ' . $schedule_id);
                } else {
                    $_SESSION['alert'] = showAlert('Error updating schedule: ' . mysqli_error($conn), 'danger');
                }
            } else {
                // Add new schedule
                $insert_query = "INSERT INTO SHS_Schedule_List 
                                (teacher_id, subject, section, grade_level, day_of_week, start_time, end_time, room) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "isssssss", $teacher_id, $subject, $section, $grade_level, 
                                      $day_of_week, $start_time, $end_time, $room);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['alert'] = showAlert('Schedule added successfully!', 'success');
                    // Log action
                    logAction($_SESSION['user_id'], 'CREATE', 'Added new schedule entry');
                } else {
                    $_SESSION['alert'] = showAlert('Error adding schedule: ' . mysqli_error($conn), 'danger');
                }
            }
        } else {
            // Display validation errors
            $error_list = '<ul>';
            foreach ($errors as $error) {
                $error_list .= '<li>' . $error . '</li>';
            }
            $error_list .= '</ul>';
            $_SESSION['alert'] = showAlert('Please fix the following errors:' . $error_list, 'danger');
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get schedule entry for editing
$edit_schedule = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    
    $edit_query = "SELECT * FROM SHS_Schedule_List WHERE id = ?";
    $stmt = mysqli_prepare($conn, $edit_query);
    mysqli_stmt_bind_param($stmt, "i", $edit_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $edit_schedule = mysqli_fetch_assoc($result);
    }
}

// Get all teachers for dropdown
$teachers = [];
$teacher_query = "SELECT id, first_name, last_name FROM teachers ORDER BY last_name, first_name";
$teacher_result = mysqli_query($conn, $teacher_query);
if ($teacher_result) {
    while ($row = mysqli_fetch_assoc($teacher_result)) {
        $teachers[] = $row;
    }
}

// Get all schedules
$schedules = [];
if ($table_exists) {
    $schedule_query = "SELECT s.*, 
                      CONCAT(t.first_name, ' ', t.last_name) as teacher_name 
                      FROM SHS_Schedule_List s 
                      LEFT JOIN teachers t ON s.teacher_id = t.id 
                      ORDER BY s.day_of_week, s.start_time";
    $schedule_result = mysqli_query($conn, $schedule_query);
    
    if ($schedule_result) {
        while ($row = mysqli_fetch_assoc($schedule_result)) {
            $schedules[] = $row;
        }
    }
}

// Days of the week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Grade levels
$grade_levels = ['Grade 11', 'Grade 12'];

// Get sections
$sections = [];
$section_query = "SELECT DISTINCT section FROM sections ORDER BY section";
$section_result = mysqli_query($conn, $section_query);
if ($section_result) {
    while ($row = mysqli_fetch_assoc($section_result)) {
        $sections[] = $row['section'];
    }
}

// If no sections found, use default sections
if (empty($sections)) {
    $sections = ['A', 'B', 'C', 'D'];
}

// Get subjects
$subjects = [];
$subject_query = "SELECT name FROM subjects WHERE status = 'active' ORDER BY name";
$subject_result = mysqli_query($conn, $subject_query);
if ($subject_result) {
    while ($row = mysqli_fetch_assoc($subject_result)) {
        $subjects[] = $row['name'];
    }
}

// If no subjects found, use default subjects
if (empty($subjects)) {
    $subjects = [
        'English', 'Mathematics', 'Science', 'Filipino',
        'Social Studies', 'Physical Education', 'Literature',
        'Calculus', 'Physics', 'Chemistry'
    ];
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manage SHS Schedule</h1>
        <div>
            <a href="<?php echo $relative_path; ?>modules/reports/schedule_report.php" class="btn btn-sm btn-info">
                <i class="fas fa-calendar-alt fa-sm text-white-50 me-1"></i> View Schedule Report
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>

    <div class="row">
        <!-- Add/Edit Schedule Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php echo $edit_schedule ? 'Edit Schedule' : 'Add Schedule'; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                        <?php if ($edit_schedule): ?>
                            <input type="hidden" name="schedule_id" value="<?php echo $edit_schedule['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Teacher <span class="text-danger">*</span></label>
                            <select class="form-select" id="teacher_id" name="teacher_id" required>
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" <?php echo ($edit_schedule && $edit_schedule['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['last_name'] . ', ' . $teacher['first_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                            <select class="form-select" id="subject" name="subject" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo ($edit_schedule && $edit_schedule['subject'] == $subject) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade_level" name="grade_level" required>
                                <option value="">Select Grade Level</option>
                                <?php foreach ($grade_levels as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>" <?php echo ($edit_schedule && $edit_schedule['grade_level'] == $grade) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grade); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="">Select Section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section); ?>" <?php echo ($edit_schedule && $edit_schedule['section'] == $section) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($section); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Day <span class="text-danger">*</span></label>
                            <select class="form-select" id="day_of_week" name="day_of_week" required>
                                <option value="">Select Day</option>
                                <?php foreach ($days as $day): ?>
                                    <option value="<?php echo htmlspecialchars($day); ?>" <?php echo ($edit_schedule && $edit_schedule['day_of_week'] == $day) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($day); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['start_time']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['end_time']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="room" class="form-label">Room</label>
                            <input type="text" class="form-control" id="room" name="room" value="<?php echo $edit_schedule ? htmlspecialchars($edit_schedule['room']) : ''; ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <?php if ($edit_schedule): ?>
                                <button type="submit" name="edit_schedule" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Schedule
                                </button>
                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_schedule" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Add Schedule
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Schedule List -->
        <div class="col-md-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">SHS Schedule List</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($schedules)): ?>
                        <div class="alert alert-info">
                            No schedule entries found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="scheduleTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Day</th>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Grade & Section</th>
                                        <th>Room</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($schedule['teacher_name']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['day_of_week']); ?></td>
                                            <td>
                                                <?php 
                                                echo date('h:i A', strtotime($schedule['start_time'])); 
                                                echo ' - '; 
                                                echo date('h:i A', strtotime($schedule['end_time'])); 
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['grade_level'] . ' ' . $schedule['section']); ?></td>
                                            <td><?php echo htmlspecialchars($schedule['room'] ?? 'TBA'); ?></td>
                                            <td>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?edit=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" onclick="confirmDeleteSHS(<?php echo $schedule['id']; ?>)" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <!-- Fallback delete link (hidden by default) -->
                                                <noscript>
                                                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?delete=<?php echo $schedule['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this schedule entry?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </noscript>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this schedule entry? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Simple delete confirmation function
    function confirmDeleteSHS(id) {
        if (confirm('Are you sure you want to delete this schedule entry? This action cannot be undone.')) {
            window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?delete=' + id;
        }
        return false;
    }
    
    // Initialize DataTable
    $(document).ready(function() {
        $('#scheduleTable').DataTable({
            order: [[1, 'asc'], [2, 'asc']],
            responsive: true
        });
        
        // Ensure Bootstrap is loaded
        if (typeof bootstrap === 'undefined') {
            console.warn('Bootstrap is not loaded. Loading it dynamically...');
            
            // Load Bootstrap JS if not already loaded
            const bootstrapScript = document.createElement('script');
            bootstrapScript.src = '<?php echo $relative_path; ?>assets/js/bootstrap.bundle.min.js';
            bootstrapScript.onload = function() {
                console.log('Bootstrap loaded successfully');
            };
            bootstrapScript.onerror = function() {
                console.error('Failed to load Bootstrap');
            };
            document.head.appendChild(bootstrapScript);
        }
        
        // Add event listeners to delete buttons
        $('.delete-btn').on('click', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            confirmDelete(id);
        });
    });
    
    // Vanilla JavaScript fallback for delete buttons
    document.addEventListener('DOMContentLoaded', function() {
        // Check if jQuery is available
        if (typeof $ === 'undefined') {
            console.warn('jQuery not detected. Using vanilla JavaScript for delete buttons.');
            
            // Get all delete buttons
            const deleteButtons = document.querySelectorAll('.delete-btn');
            
            // Add click event to each button
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    confirmDelete(id);
                });
            });
        }
    });
    
    // Delete confirmation
    function confirmDelete(id) {
        // Try to use the Bootstrap modal first
        try {
            // Set the delete link URL
        document.getElementById('deleteLink').href = '<?php echo $_SERVER['PHP_SELF']; ?>?delete=' + id;
            
            // Initialize and show the modal
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
            
            // Add click event to delete button
            document.getElementById('deleteLink').onclick = function(e) {
                // Just let the link work normally
            };
        } catch (error) {
            // Fallback to simple JavaScript confirm if Bootstrap modal fails
            console.error("Modal error:", error);
            if (confirm('Are you sure you want to delete this schedule entry? This action cannot be undone.')) {
                window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?delete=' + id;
            }
        }
        
        return false;
    }
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 