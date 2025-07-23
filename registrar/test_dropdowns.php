<?php
// Calculate the relative path to the includes directory
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';
require_once $relative_path . 'includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect($relative_path . 'login.php');
}

// Check if user has access to this module
if (!hasAccess('registrar')) {
    $_SESSION['alert'] = showAlert('You do not have access to this module.', 'danger');
    redirect($relative_path);
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Test Dropdowns</h1>
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>How the Dropdowns Work</h5>
                <ul class="mb-0">
                    <li><strong>Education Level:</strong> First, select the education level (e.g., Kindergarten, Elementary, etc.)</li>
                    <li><strong>Grade Level:</strong> Based on the selected education level, appropriate grade levels will be loaded</li>
                    <li><strong>Section:</strong> After selecting grade level, available sections will be loaded</li>
                    <li><strong>Special Case - Kindergarten:</strong> If Kindergarten is selected as education level, grade level will automatically be set to 'K' and section selection will be disabled since it's not applicable</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Test Form</h5>
                </div>
                <div class="card-body">
                    <form id="testForm" method="post">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="education_level_id" class="form-label">Education Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="education_level_id" name="education_level_id" required>
                                    <option value="">Select Education Level</option>
                                    <?php 
                                    $edu_levels_query = "SELECT id, level_name FROM education_levels WHERE status = 'Active' ORDER BY display_order";
                                    $edu_levels_result = mysqli_query($conn, $edu_levels_query);
                                    if ($edu_levels_result) {
                                        while ($level = mysqli_fetch_assoc($edu_levels_result)) {
                                            echo '<option value="' . $level['id'] . '">' . htmlspecialchars($level['level_name']) . '</option>';
                                        }
                                    } else {
                                        echo '<option value="" disabled>Error loading education levels</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="grade_level" class="form-label">Grade Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <option value="">Select Grade Level</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                                <select class="form-select" id="section" name="section" required>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo $relative_path; ?>modules/registrar/students.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Students
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check me-1"></i> Test Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Debug Information</h5>
                </div>
                <div class="card-body">
                    <div id="debug-info" class="bg-light p-3 rounded">
                        <p>Select options above to see debug information here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Database Connection Test</h5>
                </div>
                <div class="card-body">
                    <?php
                    if ($conn) {
                        echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Database connection successful!</div>';
                        
                        // Test education_levels table
                        $test_query = "SELECT COUNT(*) as count FROM education_levels";
                        $test_result = mysqli_query($conn, $test_query);
                        if ($test_result) {
                            $row = mysqli_fetch_assoc($test_result);
                            echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> Found ' . $row['count'] . ' education levels in the database.</div>';
                        } else {
                            echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Error querying education_levels table: ' . mysqli_error($conn) . '</div>';
                        }
                        
                        // Test sections table
                        $test_query = "SELECT COUNT(*) as count FROM sections";
                        $test_result = mysqli_query($conn, $test_query);
                        if ($test_result) {
                            $row = mysqli_fetch_assoc($test_result);
                            echo '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i> Found ' . $row['count'] . ' sections in the database.</div>';
                        } else {
                            echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Error querying sections table: ' . mysqli_error($conn) . '</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> Database connection failed!</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the dropdown fix scripts -->
<script src="<?php echo $relative_path; ?>modules/registrar/fix_dropdowns.js"></script>
<script src="<?php echo $relative_path; ?>modules/registrar/fix_section_dropdown.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Debug info
    const debugInfo = document.getElementById('debug-info');
    const educationLevelSelect = document.getElementById('education_level_id');
    const gradeLevelSelect = document.getElementById('grade_level');
    const sectionSelect = document.getElementById('section');
    
    // Function to update debug info
    function updateDebugInfo() {
        const educationLevel = educationLevelSelect.options[educationLevelSelect.selectedIndex]?.text || 'None';
        const educationLevelId = educationLevelSelect.value || 'None';
        const gradeLevel = gradeLevelSelect.options[gradeLevelSelect.selectedIndex]?.text || 'None';
        const gradeLevelValue = gradeLevelSelect.value || 'None';
        const section = sectionSelect.options[sectionSelect.selectedIndex]?.text || 'None';
        const sectionValue = sectionSelect.value || 'None';
        
        debugInfo.innerHTML = `
            <h6>Selected Values:</h6>
            <ul>
                <li><strong>Education Level:</strong> ${educationLevel} (ID: ${educationLevelId})</li>
                <li><strong>Grade Level:</strong> ${gradeLevel} (Value: ${gradeLevelValue})</li>
                <li><strong>Section:</strong> ${section} (Value: ${sectionValue})</li>
            </ul>
            <h6>Dropdown Options:</h6>
            <ul>
                <li><strong>Education Level Options:</strong> ${educationLevelSelect.options.length}</li>
                <li><strong>Grade Level Options:</strong> ${gradeLevelSelect.options.length}</li>
                <li><strong>Section Options:</strong> ${sectionSelect.options.length}</li>
            </ul>
        `;
    }
    
    // Add event listeners
    educationLevelSelect.addEventListener('change', updateDebugInfo);
    gradeLevelSelect.addEventListener('change', updateDebugInfo);
    sectionSelect.addEventListener('change', updateDebugInfo);
    
    // Prevent form submission
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();
        debugInfo.innerHTML += `
            <div class="alert alert-success mt-3">
                <h6>Form would submit with these values:</h6>
                <ul>
                    <li><strong>Education Level ID:</strong> ${educationLevelSelect.value}</li>
                    <li><strong>Grade Level:</strong> ${gradeLevelSelect.value}</li>
                    <li><strong>Section:</strong> ${sectionSelect.value}</li>
                </ul>
            </div>
        `;
    });
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 