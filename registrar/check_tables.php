<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Set content type to HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tables Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        h1, h2, h3 {
            color: #0066cc;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Tables Check</h1>
        
        <?php
        // Check if user is logged in
        if (!isLoggedIn()): ?>
            <div class="error">You must be logged in to view this page.</div>
        <?php else: ?>
            
            <h2>Education Levels Table</h2>
            <?php
            // Check if education_levels table exists
            $query = "SHOW TABLES LIKE 'education_levels'";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) == 0): ?>
                <div class="error">The education_levels table does not exist!</div>
            <?php else: ?>
                <h3>Table Structure</h3>
                <?php
                // Get table structure
                $query = "DESCRIBE education_levels";
                $result = mysqli_query($conn, $query);
                
                if (!$result): ?>
                    <div class="error">Error retrieving table structure: <?php echo mysqli_error($conn); ?></div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Null</th>
                                <th>Key</th>
                                <th>Default</th>
                                <th>Extra</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Field']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Null']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Key']); ?></td>
                                    <td><?php echo isset($row['Default']) ? htmlspecialchars($row['Default']) : 'NULL'; ?></td>
                                    <td><?php echo htmlspecialchars($row['Extra']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <h3>Table Data</h3>
                    <?php
                    // Get table data
                    $query = "SELECT * FROM education_levels ORDER BY display_order, name";
                    $result = mysqli_query($conn, $query);
                    
                    if (!$result): ?>
                        <div class="error">Error retrieving table data: <?php echo mysqli_error($conn); ?></div>
                    <?php elseif (mysqli_num_rows($result) == 0): ?>
                        <div class="error">No data found in the education_levels table!</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <?php
                                    $fields = mysqli_fetch_fields($result);
                                    foreach ($fields as $field): ?>
                                        <th><?php echo htmlspecialchars($field->name); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <?php foreach ($row as $key => $value): ?>
                                            <td><?php echo htmlspecialchars($value ?? 'NULL'); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
                
                <h2>Grade Levels Check</h2>
                <?php
                // Test each education level ID to see what grade levels are returned
                $query = "SELECT id, name FROM education_levels WHERE status = 'Active' ORDER BY display_order, name";
                $result = mysqli_query($conn, $query);
                
                if (!$result || mysqli_num_rows($result) == 0): ?>
                    <div class="error">No active education levels found!</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Education Level ID</th>
                                <th>Education Level Name</th>
                                <th>Grade Levels</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result)): 
                                $education_level_id = $row['id'];
                                $education_level_name = $row['name'];
                                
                                // Define grade levels based on education level name
                                $grade_levels = [];
                                
                                if ($education_level_name == 'Kindergarten') {
                                    $grade_levels = ['Kindergarten'];
                                } elseif ($education_level_name == 'Elementary') {
                                    $grade_levels = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
                                } elseif ($education_level_name == 'Junior High School') {
                                    $grade_levels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
                                } elseif ($education_level_name == 'Senior High School') {
                                    $grade_levels = ['Grade 11', 'Grade 12'];
                                } else {
                                    $grade_levels = ['Unknown education level'];
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($education_level_id); ?></td>
                                    <td><?php echo htmlspecialchars($education_level_name); ?></td>
                                    <td><?php echo htmlspecialchars(implode(', ', $grade_levels)); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 