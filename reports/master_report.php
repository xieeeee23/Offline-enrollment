<?php
$title = 'System Master Report';
$relative_path = '../../';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    redirect('dashboard.php');
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    
    // Get all the data
    $students_query = "SELECT * FROM students ORDER BY lname ASC";
    $students_result = mysqli_query($conn, $students_query);
    $students = [];
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
    
    $teachers_query = "SELECT * FROM teachers ORDER BY lname ASC";
    $teachers_result = mysqli_query($conn, $teachers_query);
    $teachers = [];
    while ($row = mysqli_fetch_assoc($teachers_result)) {
        $teachers[] = $row;
    }
    
    $sections_query = "SELECT DISTINCT section FROM students ORDER BY section ASC";
    $sections_result = mysqli_query($conn, $sections_query);
    $sections = [];
    while ($row = mysqli_fetch_assoc($sections_result)) {
        $sections[] = $row['section'];
    }
    
    $strands_query = "SELECT DISTINCT strand FROM students WHERE strand IS NOT NULL AND strand != '' ORDER BY strand ASC";
    $strands_result = mysqli_query($conn, $strands_query);
    $strands = [];
    while ($row = mysqli_fetch_assoc($strands_result)) {
        $strands[] = $row['strand'];
    }
    
    if ($export_type == 'excel') {
        // Set headers for Excel download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="system_master_report.csv"');
        
        // Create a file pointer
        $output = fopen('php://output', 'w');
        
        // System Statistics
        fputcsv($output, array('SYSTEM STATISTICS'));
        fputcsv($output, array('Total Students', count($students)));
        fputcsv($output, array('Total Teachers', count($teachers)));
        fputcsv($output, array('Total Sections', count($sections)));
        fputcsv($output, array('Total Strands', count($strands)));
        fputcsv($output, array(''));
        
        // Student Information
        fputcsv($output, array('STUDENT INFORMATION'));
        $header = array('LRN', 'Last Name', 'First Name', 'Middle Name', 'Gender', 'Date of Birth', 'Contact', 'Email', 'Address', 'Grade Level', 'Section', 'Strand', 'Status');
        fputcsv($output, $header);
        
        foreach ($students as $student) {
            $student_data = array(
                $student['lrn'],
                $student['lname'],
                $student['fname'],
                $student['mname'],
                $student['gender'],
                $student['dob'],
                $student['contact'],
                $student['email'],
                $student['address'],
                $student['grade_level'],
                $student['section'],
                $student['strand'],
                $student['status']
            );
            fputcsv($output, $student_data);
        }
        fputcsv($output, array(''));
        
        // Teacher Information
        fputcsv($output, array('TEACHER INFORMATION'));
        $header = array('ID', 'Last Name', 'First Name', 'Middle Name', 'Gender', 'Email', 'Contact', 'Address', 'Department');
        fputcsv($output, $header);
        
        foreach ($teachers as $teacher) {
            $teacher_data = array(
                $teacher['id'],
                $teacher['lname'],
                $teacher['fname'],
                $teacher['mname'],
                $teacher['gender'],
                $teacher['email'],
                $teacher['contact'],
                $teacher['address'],
                $teacher['department']
            );
            fputcsv($output, $teacher_data);
        }
        fputcsv($output, array(''));
        
        // Section Information
        fputcsv($output, array('SECTION INFORMATION'));
        $header = array('Section', 'Total Students');
        fputcsv($output, $header);
        
        foreach ($sections as $section) {
            $section_query = "SELECT COUNT(*) as count FROM students WHERE section = '$section'";
            $section_result = mysqli_query($conn, $section_query);
            $section_count = mysqli_fetch_assoc($section_result)['count'];
            
            $section_data = array(
                $section,
                $section_count
            );
            fputcsv($output, $section_data);
        }
        
        fclose($output);
        exit;
    } else {
        // If any other export type is requested, redirect back to the report page
        $_SESSION['alert'] = showAlert('Only Excel export is available.', 'info');
        redirect($_SERVER['PHP_SELF']);
    }
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">System Master Report</h1>
    </div>
    
    <?php if (isset($_SESSION['alert'])) {
        echo $_SESSION['alert'];
        unset($_SESSION['alert']);
    } ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Comprehensive System Report</h6>
            <div>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?export=excel" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel mr-1"></i> Export to Excel
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h5 class="alert-heading">Complete System Report</h5>
                <p>This report consolidates all data from the system including students, teachers, sections, and more into a single comprehensive report.</p>
                <p>Click the Excel export button above to generate the complete report.</p>
                <hr>
                <p class="mb-0">The Excel report will include:</p>
                <ul>
                    <li>System Statistics</li>
                    <li>Student Information</li>
                    <li>Teacher Information</li>
                    <li>Section Information</li>
                    <li>Enrollment Status Summary</li>
                </ul>
            </div>
            
            <div class="text-center mt-4">
                <i class="fas fa-file-alt fa-6x text-primary"></i>
                <p class="mt-2 text-muted">Click the buttons above to generate your report</p>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 