<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    die("Authentication required");
}

// Set the page title
$title = 'Test Database Connectivity';
require_once $relative_path . 'includes/header.php';

// Function to execute a query and return results
function executeQuery($conn, $query, $params = []) {
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Query preparation failed: ' . mysqli_error($conn),
            'data' => []
        ];
    }
    
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $bindParams[] = $param;
        }
        
        $bindParamsRef = [];
        for ($i = 0; $i < count($bindParams); $i++) {
            $bindParamsRef[] = &$bindParams[$i];
        }
        
        array_unshift($bindParamsRef, $stmt, $types);
        call_user_func_array('mysqli_stmt_bind_param', $bindParamsRef);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        return [
            'success' => false,
            'error' => 'Query execution failed: ' . mysqli_stmt_error($stmt),
            'data' => []
        ];
    }
    
    $result = mysqli_stmt_get_result($stmt);
    $data = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    
    mysqli_stmt_close($stmt);
    
    return [
        'success' => true,
        'error' => '',
        'data' => $data
    ];
}

// Test 1: Check if education_levels table has data
$educationLevelsQuery = "SELECT * FROM education_levels WHERE status = 'Active' ORDER BY display_order";
$educationLevels = executeQuery($conn, $educationLevelsQuery);

// Test 2: Check if sections table has data
$sectionsQuery = "SELECT * FROM sections WHERE status = 'Active' ORDER BY grade_level, name";
$sections = executeQuery($conn, $sectionsQuery);

// Test 3: Check the relationship between education levels and grade levels
$educationLevelGradesQuery = "
    SELECT 
        el.id AS education_level_id,
        el.level_name,
        CASE 
            WHEN el.level_name = 'Kindergarten' THEN 'K'
            ELSE CONCAT(el.grade_min, ' - ', el.grade_max)
        END AS grade_range,
        (SELECT COUNT(*) FROM sections s WHERE 
            CASE 
                WHEN el.level_name = 'Kindergarten' AND s.grade_level = 'K' THEN 1
                WHEN el.level_name != 'Kindergarten' AND 
                     (CAST(s.grade_level AS SIGNED) BETWEEN el.grade_min AND el.grade_max) THEN 1
                ELSE 0
            END = 1
        ) AS section_count
    FROM education_levels el
    WHERE el.status = 'Active'
    ORDER BY el.display_order
";
$educationLevelGrades = executeQuery($conn, $educationLevelGradesQuery);

// Test 4: Check if students are properly linked to education levels
$studentEducationLevelQuery = "
    SELECT 
        COUNT(*) AS total_students,
        COUNT(s.education_level_id) AS students_with_education_level,
        (COUNT(*) - COUNT(s.education_level_id)) AS students_without_education_level
    FROM students s
";
$studentEducationLevel = executeQuery($conn, $studentEducationLevelQuery);

// Test 5: Check if students are properly linked to sections
$studentSectionQuery = "
    SELECT 
        COUNT(*) AS total_students,
        COUNT(NULLIF(s.section, '')) AS students_with_section,
        (COUNT(*) - COUNT(NULLIF(s.section, ''))) AS students_without_section
    FROM students s
";
$studentSection = executeQuery($conn, $studentSectionQuery);

// Test 6: Check if students have proper grade levels
$studentGradeLevelQuery = "
    SELECT 
        COUNT(*) AS total_students,
        COUNT(NULLIF(s.grade_level, '')) AS students_with_grade_level,
        (COUNT(*) - COUNT(NULLIF(s.grade_level, ''))) AS students_without_grade_level
    FROM students s
";
$studentGradeLevel = executeQuery($conn, $studentGradeLevelQuery);

// Test 7: Check consistency between education level and grade level
$consistencyQuery = "
    SELECT 
        s.id, 
        s.lrn, 
        s.first_name, 
        s.last_name, 
        s.grade_level, 
        s.section,
        el.level_name,
        CASE 
            WHEN el.level_name = 'Kindergarten' AND s.grade_level = 'K' THEN 'Consistent'
            WHEN el.level_name = 'Elementary' AND CAST(s.grade_level AS SIGNED) BETWEEN 1 AND 6 THEN 'Consistent'
            WHEN el.level_name = 'Junior High School' AND CAST(s.grade_level AS SIGNED) BETWEEN 7 AND 10 THEN 'Consistent'
            WHEN el.level_name = 'Senior High School' AND CAST(s.grade_level AS SIGNED) BETWEEN 11 AND 12 THEN 'Consistent'
            ELSE 'Inconsistent'
        END AS consistency_status
    FROM students s
    LEFT JOIN education_levels el ON s.education_level_id = el.id
    WHERE s.education_level_id IS NOT NULL
    LIMIT 10
";
$consistency = executeQuery($conn, $consistencyQuery);
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Database Connectivity Test</h1>
    
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Education Levels</h5>
                </div>
                <div class="card-body">
                    <?php if ($educationLevels['success'] && !empty($educationLevels['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Level Name</th>
                                        <th>Grade Min</th>
                                        <th>Grade Max</th>
                                        <th>Display Order</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($educationLevels['data'] as $level): ?>
                                        <tr>
                                            <td><?php echo $level['id']; ?></td>
                                            <td><?php echo $level['level_name']; ?></td>
                                            <td><?php echo $level['grade_min']; ?></td>
                                            <td><?php echo $level['grade_max']; ?></td>
                                            <td><?php echo $level['display_order']; ?></td>
                                            <td><?php echo $level['status']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!$educationLevels['success']): ?>
                        <div class="alert alert-danger">
                            Error: <?php echo $educationLevels['error']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No education levels found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Education Level to Grade Level Mapping</h5>
                </div>
                <div class="card-body">
                    <?php if ($educationLevelGrades['success'] && !empty($educationLevelGrades['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Education Level</th>
                                        <th>Grade Range</th>
                                        <th>Section Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($educationLevelGrades['data'] as $mapping): ?>
                                        <tr>
                                            <td><?php echo $mapping['level_name']; ?></td>
                                            <td><?php echo $mapping['grade_range']; ?></td>
                                            <td><?php echo $mapping['section_count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!$educationLevelGrades['success']): ?>
                        <div class="alert alert-danger">
                            Error: <?php echo $educationLevelGrades['error']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No education level to grade level mappings found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Student Education Level Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($studentEducationLevel['success'] && !empty($studentEducationLevel['data'])): ?>
                        <?php $data = $studentEducationLevel['data'][0]; ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Students</td>
                                        <td><?php echo $data['total_students']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Students with Education Level</td>
                                        <td><?php echo $data['students_with_education_level']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Students without Education Level</td>
                                        <td><?php echo $data['students_without_education_level']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!$studentEducationLevel['success']): ?>
                        <div class="alert alert-danger">
                            Error: <?php echo $studentEducationLevel['error']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No student education level data found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Student Section Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($studentSection['success'] && !empty($studentSection['data'])): ?>
                        <?php $data = $studentSection['data'][0]; ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Metric</th>
                                        <th>Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Students</td>
                                        <td><?php echo $data['total_students']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Students with Section</td>
                                        <td><?php echo $data['students_with_section']; ?></td>
                                    </tr>
                                    <tr>
                                        <td>Students without Section</td>
                                        <td><?php echo $data['students_without_section']; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (!$studentSection['success']): ?>
                        <div class="alert alert-danger">
                            Error: <?php echo $studentSection['error']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No student section data found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Education Level and Grade Level Consistency</h5>
                </div>
                <div class="card-body">
                    <?php if ($consistency['success'] && !empty($consistency['data'])): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>LRN</th>
                                        <th>Name</th>
                                        <th>Grade Level</th>
                                        <th>Section</th>
                                        <th>Education Level</th>
                                        <th>Consistency Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consistency['data'] as $student): ?>
                                        <tr>
                                            <td><?php echo $student['lrn']; ?></td>
                                            <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                            <td><?php echo $student['grade_level']; ?></td>
                                            <td><?php echo $student['section']; ?></td>
                                            <td><?php echo $student['level_name']; ?></td>
                                            <td>
                                                <?php if ($student['consistency_status'] === 'Consistent'): ?>
                                                    <span class="badge bg-success">Consistent</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inconsistent</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p class="mt-2 text-muted">Showing up to 10 records. This is a sample of the data.</p>
                        </div>
                    <?php elseif (!$consistency['success']): ?>
                        <div class="alert alert-danger">
                            Error: <?php echo $consistency['error']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No student consistency data found.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $relative_path . 'includes/footer.php'; ?> 