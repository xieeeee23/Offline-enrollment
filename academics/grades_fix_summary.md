# Grades Functionality Fix Summary

## Issue
The "Enter Grades" and "View Grades" functionality was showing a "Not Found" error page when clicking the buttons. The issue was related to URL encoding and parameter handling between the grading.php file and enter_grades.php/view_grades.php files.

Additionally, there were errors with the SQL queries in both files trying to access non-existent columns ('s.student_id' and 's.suffix') in the students table, as well as references to these non-existent columns in the HTML output.

Another issue was with the grading_periods table missing the 'period_number' column that was being referenced in the SQL queries.

## Changes Made

### 1. Fixed Redirect in grading.php
- Updated the form submission handlers to use direct header redirects with BASE_URL instead of the redirect() function
- Added proper URL encoding for all parameters using urlencode()

```php
// Process form submission to enter grades
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enter_grades'])) {
    $subject_id = (int) $_POST['subject_id'];
    $section = cleanInput($_POST['section']);
    $school_year = cleanInput($_POST['school_year']);
    $semester = cleanInput($_POST['semester']);
    
    // Use direct header redirect to avoid URL encoding issues
    $redirect_url = BASE_URL . 'modules/academics/enter_grades.php?subject=' . $subject_id . 
             '&section=' . urlencode($section) . 
             '&year=' . urlencode($school_year) . 
             '&semester=' . urlencode($semester);
    
    header("Location: " . $redirect_url);
    exit();
}
```

### 2. Enhanced Parameter Handling in enter_grades.php and view_grades.php
- Added consistent parameter handling in both files
- Added debugging information to help identify issues with parameters

```php
// Get parameters - use consistent parameter names
$subject_id = isset($_GET['subject']) ? (int) $_GET['subject'] : 0;
$section = isset($_GET['section']) ? cleanInput($_GET['section']) : '';
$school_year = isset($_GET['year']) ? cleanInput($_GET['year']) : '';
$semester = isset($_GET['semester']) ? cleanInput($_GET['semester']) : '';

// For debugging
if (!$subject_id || empty($section) || empty($school_year) || empty($semester)) {
    echo "<div class='alert alert-danger'>Debug info: subject=$subject_id, section=$section, year=$school_year, semester=$semester</div>";
    echo "<div class='alert alert-danger'>Raw GET data: " . print_r($_GET, true) . "</div>";
    
    $_SESSION['alert'] = showAlert('Invalid parameters. Please select subject, section, school year, and semester.', 'danger');
    redirect($relative_path . 'modules/academics/grading.php');
    exit();
}
```

### 3. Updated redirect() Function in functions.php
- Enhanced the redirect function to better handle URL encoding and path handling

### 4. Created Test Files
- Created test_grades.php to test different URL formats and parameter handling
- Created check_grades_url.php for additional testing and diagnostics

### 5. Fixed SQL Query Errors
- Removed the non-existent columns from the SQL queries in both enter_grades.php and view_grades.php files

```php
// Before (error)
$students_query = "SELECT s.id, s.student_id, s.first_name, s.middle_name, s.last_name, s.suffix 
                  FROM students s 
                  WHERE s.section = ? 
                  ORDER BY s.last_name, s.first_name";

// After (fixed)
$students_query = "SELECT s.id, s.first_name, s.middle_name, s.last_name 
                  FROM students s 
                  WHERE s.section = ? 
                  ORDER BY s.last_name, s.first_name";
```

### 6. Fixed HTML Output References
- Updated HTML output to remove references to non-existent columns
- Changed references from `$student['student_id']` to `$student['id']`
- Removed code that tried to display the non-existent suffix field

```php
// Before (error)
<td><?php echo htmlspecialchars($student['student_id']); ?></td>
<td>
    <?php 
    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
    if (!empty($student['middle_name'])) {
        echo ' ' . htmlspecialchars(substr($student['middle_name'], 0, 1) . '.');
    }
    if (!empty($student['suffix'])) {
        echo ' ' . htmlspecialchars($student['suffix']);
    }
    ?>
</td>

// After (fixed)
<td><?php echo htmlspecialchars($student['id']); ?></td>
<td>
    <?php 
    echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']);
    if (!empty($student['middle_name'])) {
        echo ' ' . htmlspecialchars(substr($student['middle_name'], 0, 1) . '.');
    }
    ?>
</td>
```

### 7. Fixed period_number Column Reference
- Modified SQL queries to not rely on the non-existent 'period_number' column in the grading_periods table
- Changed ORDER BY clause to use 'id' instead of 'period_number'
- Removed reference to 'gp.period_number' in the JOIN query

```php
// Before (error)
$periods_query = "SELECT * FROM grading_periods ORDER BY period_number";

// After (fixed)
$periods_query = "SELECT * FROM grading_periods ORDER BY id";
```

```php
// Before (error)
$grades_query = "SELECT sg.*, gp.name as period_name, gp.period_number 
                FROM student_grades sg
                JOIN grading_periods gp ON sg.period_id = gp.id
                WHERE sg.student_id = ? AND sg.subject_id = ? 
                AND sg.school_year = ? AND sg.semester = ?
                ORDER BY gp.period_number";

// After (fixed)
$grades_query = "SELECT sg.*, gp.name as period_name 
                FROM student_grades sg
                JOIN grading_periods gp ON sg.period_id = gp.id
                WHERE sg.student_id = ? AND sg.subject_id = ? 
                AND sg.school_year = ? AND sg.semester = ?
                ORDER BY gp.id";
```

### 8. Created Fix Script for Grading Periods Table
- Created fix_grading_periods.php to add the missing 'period_number' column to the grading_periods table
- This script checks if the column exists, adds it if missing, and updates existing records with sequential period numbers

## Testing Instructions

1. Navigate to the Grading System page: `/modules/academics/grading.php`
2. Select a subject, section, school year, and semester
3. Click "Enter Grades" or "View Grades"
4. Verify that you are redirected to the correct page with all parameters intact

For additional testing:
- Visit `/modules/academics/test_grades.php` to test direct links
- Visit `/modules/academics/check_grades_url.php` to test form submissions and URL encoding
- Visit `/modules/academics/fix_grading_periods.php` to add the missing period_number column to the grading_periods table

## Common Issues

If you still encounter "Not Found" errors:
1. Check that BASE_URL is correctly configured in includes/config.php
2. Verify that the URL parameters are being properly encoded
3. Check for any URL rewriting rules in .htaccess that might be affecting the redirects
4. Enable the debugging output in enter_grades.php and view_grades.php to see the actual parameters being received 