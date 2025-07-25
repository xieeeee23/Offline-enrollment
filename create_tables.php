<?php
$title = 'SHS Enrollment System Setup';
$relative_path = './';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin role
if (!checkAccess(['admin'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Database setup function
function setup_database($conn) {
    $success = true;
    $messages = [];
    
    // Array of all tables to check and create if needed
    $tables_to_create = [
        // Users table
        "users" => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('admin', 'registrar', 'teacher', 'parent', 'student') NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Logs table
        "logs" => "CREATE TABLE IF NOT EXISTS logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            ip_address VARCHAR(50),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // Students table
        "students" => "CREATE TABLE IF NOT EXISTS students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lrn VARCHAR(20) NOT NULL UNIQUE,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50) DEFAULT NULL,
            last_name VARCHAR(50) NOT NULL,
            dob DATE NOT NULL,
            gender ENUM('Male', 'Female') NOT NULL,
            religion VARCHAR(50) DEFAULT NULL,
            address TEXT,
            contact_number VARCHAR(20) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            father_name VARCHAR(100) DEFAULT NULL,
            father_occupation VARCHAR(100) DEFAULT NULL,
            mother_name VARCHAR(100) DEFAULT NULL,
            mother_occupation VARCHAR(100) DEFAULT NULL,
            grade_level ENUM('Grade 11', 'Grade 12') NOT NULL,
            section VARCHAR(20),
            enrollment_status ENUM('enrolled', 'pending', 'withdrawn') DEFAULT 'pending',
            photo VARCHAR(255) DEFAULT NULL,
            enrolled_by INT,
            date_enrolled DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (enrolled_by) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // Senior high school details
        "senior_highschool_details" => "CREATE TABLE IF NOT EXISTS senior_highschool_details (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            track VARCHAR(100) NOT NULL,
            strand VARCHAR(20) NOT NULL,
            semester ENUM('First', 'Second') NOT NULL,
            school_year VARCHAR(20) NOT NULL,
            previous_school VARCHAR(255),
            previous_track VARCHAR(100),
            previous_strand VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
        )",
        
        // SHS strands
        "shs_strands" => "CREATE TABLE IF NOT EXISTS shs_strands (
            id INT PRIMARY KEY AUTO_INCREMENT,
            track_name VARCHAR(100) NOT NULL,
            strand_code VARCHAR(20) NOT NULL UNIQUE,
            strand_name VARCHAR(100) NOT NULL,
            description TEXT,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        
        // Sections
        "sections" => "CREATE TABLE IF NOT EXISTS sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            grade_level ENUM('Grade 11', 'Grade 12') NOT NULL,
            strand VARCHAR(20) NOT NULL,
            max_students INT DEFAULT 40,
            status ENUM('Active', 'Inactive') DEFAULT 'Active',
            school_year VARCHAR(20) NOT NULL,
            semester ENUM('First', 'Second') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (strand) REFERENCES shs_strands(strand_code)
        )",
        
        // Teachers
        "teachers" => "CREATE TABLE IF NOT EXISTS teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) DEFAULT NULL,
            department VARCHAR(100) DEFAULT NULL,
            subject VARCHAR(100) DEFAULT NULL,
            grade_level VARCHAR(50) DEFAULT NULL,
            qualification TEXT DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )"
    ];
    
    // Create each table if it doesn't exist
    foreach ($tables_to_create as $table_name => $create_query) {
        // Check if table exists
        $check_query = "SHOW TABLES LIKE '$table_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) == 0) {
            // Table doesn't exist, create it
            if (!mysqli_query($conn, $create_query)) {
                $success = false;
                $messages[] = [
                    'type' => 'danger',
                    'text' => "Error creating $table_name table: " . mysqli_error($conn)
                ];
            } else {
                $messages[] = [
                    'type' => 'success',
                    'text' => "$table_name table created successfully."
                ];
            }
        } else {
            $messages[] = [
                'type' => 'success',
                'text' => "$table_name table already exists."
            ];
        }
    }
    
    // Add default data for certain tables
    if ($success) {
        // Add default strands if shs_strands table is empty
        $check_query = "SELECT COUNT(*) as count FROM shs_strands";
        $check_result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($check_result);
        
        if ($row['count'] == 0) {
            $insert_query = "INSERT INTO shs_strands (track_name, strand_code, strand_name, description, status) VALUES
                ('Academic', 'ABM', 'Accountancy, Business and Management', 'Focus on business-related fields and financial management', 'Active'),
                ('Academic', 'STEM', 'Science, Technology, Engineering, and Mathematics', 'Focus on science and math-related fields', 'Active'),
                ('Academic', 'HUMSS', 'Humanities and Social Sciences', 'Focus on literature, philosophy, social sciences', 'Active'),
                ('Academic', 'GAS', 'General Academic Strand', 'General subjects for undecided students', 'Active'),
                ('Technical-Vocational-Livelihood', 'TVL-HE', 'Home Economics', 'Skills related to household management', 'Active'),
                ('Technical-Vocational-Livelihood', 'TVL-ICT', 'Information and Communications Technology', 'Computer and tech-related skills', 'Active'),
                ('Technical-Vocational-Livelihood', 'TVL-IA', 'Industrial Arts', 'Skills related to manufacturing and production', 'Active'),
                ('Sports', 'Sports', 'Sports Track', 'Focus on physical education and sports development', 'Active'),
                ('Arts and Design', 'Arts', 'Arts and Design Track', 'Focus on visual and performing arts', 'Active')";
            
            if (!mysqli_query($conn, $insert_query)) {
                $messages[] = [
                    'type' => 'warning',
                    'text' => 'Error inserting default strands: ' . mysqli_error($conn)
                ];
            } else {
                $messages[] = [
                    'type' => 'success',
                    'text' => 'Default strands inserted successfully.'
                ];
            }
        }
        
        // Add default sections if sections table is empty
        $check_query = "SELECT COUNT(*) as count FROM sections";
        $check_result = mysqli_query($conn, $check_query);
        $row = mysqli_fetch_assoc($check_result);
        
        if ($row['count'] == 0) {
            $current_year = date('Y');
            $school_year = $current_year . '-' . ($current_year + 1);
            
            $insert_query = "INSERT INTO sections (name, grade_level, strand, max_students, status, school_year, semester) VALUES 
                ('ABM-11A', 'Grade 11', 'ABM', 40, 'Active', '$school_year', 'First'),
                ('STEM-11A', 'Grade 11', 'STEM', 40, 'Active', '$school_year', 'First'),
                ('HUMSS-11A', 'Grade 11', 'HUMSS', 40, 'Active', '$school_year', 'First'),
                ('ABM-12A', 'Grade 12', 'ABM', 40, 'Active', '$school_year', 'First'),
                ('STEM-12A', 'Grade 12', 'STEM', 40, 'Active', '$school_year', 'First'),
                ('HUMSS-12A', 'Grade 12', 'HUMSS', 40, 'Active', '$school_year', 'First')";
            
            if (!mysqli_query($conn, $insert_query)) {
                $messages[] = [
                    'type' => 'warning',
                    'text' => 'Error inserting default sections: ' . mysqli_error($conn)
                ];
            } else {
                $messages[] = [
                    'type' => 'success',
                    'text' => 'Default sections inserted successfully.'
                ];
            }
        }
    }
    
    // Create upload directory for student photos if it doesn't exist
    $upload_dir = $relative_path . 'uploads/students';
    if (!file_exists($upload_dir)) {
        if (mkdir($upload_dir, 0777, true)) {
            $messages[] = [
                'type' => 'success',
                'text' => 'Student photos upload directory created.'
            ];
        } else {
            $messages[] = [
                'type' => 'warning',
                'text' => 'Failed to create student photos upload directory.'
            ];
        }
    }
    
    return [
        'success' => $success,
        'messages' => $messages
    ];
}

// Handle setup request
if (isset($_POST['setup_database'])) {
    $result = setup_database($conn);
    
    foreach ($result['messages'] as $message) {
        $_SESSION['alert'] = showAlert($message['text'], $message['type']);
    }
    
    redirect('create_tables.php');
}
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">SHS Enrollment System Setup</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">System Setup</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])): ?>
        <?php echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-xl-12">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-database me-1"></i>
                    Database Setup
                </div>
                <div class="card-body">
                    <p>This page will help you set up the necessary tables for the Senior High School Enrollment System. Click the button below to create or update the required database tables.</p>
                    
                    <p><strong>Tables that will be created:</strong></p>
                    <ul>
                        <li><strong>students</strong> - Stores basic student information</li>
                        <li><strong>senior_highschool_details</strong> - Stores SHS-specific details like track, strand, semester</li>
                        <li><strong>shs_strands</strong> - Stores the list of available SHS strands</li>
                        <li><strong>sections</strong> - Stores the list of sections for each strand and grade level</li>
                    </ul>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="mt-4">
                        <button type="submit" name="setup_database" class="btn btn-primary">
                            <i class="fas fa-cogs me-1"></i> Set Up Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?>
    