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
$title = 'Test Grade Levels API';
require_once $relative_path . 'includes/header.php';

// Get all education levels
$query = "SELECT * FROM education_levels WHERE status = 'Active' ORDER BY display_order";
$result = mysqli_query($conn, $query);
$education_levels = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $education_levels[] = $row;
    }
}
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Test Grade Levels API</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Test Education Level to Grade Level API</h5>
                </div>
                <div class="card-body">
                    <form id="testForm">
                        <div class="mb-3">
                            <label for="education_level" class="form-label">Select Education Level</label>
                            <select class="form-select" id="education_level">
                                <option value="">Select Education Level</option>
                                <?php foreach ($education_levels as $level): ?>
                                    <option value="<?php echo $level['id']; ?>"><?php echo htmlspecialchars($level['level_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" id="testButton" class="btn btn-primary">Test API</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">API Response</h5>
                </div>
                <div class="card-body">
                    <div id="apiResponse">
                        <p class="text-muted">Select an education level and click "Test API" to see the response.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Direct API Test</h5>
                </div>
                <div class="card-body">
                    <h6>Test Elementary Level (ID: 2)</h6>
                    <div id="elementaryTest" class="mt-3">
                        <button type="button" id="testElementary" class="btn btn-primary">Test Elementary</button>
                        <div id="elementaryResponse" class="mt-2"></div>
                    </div>
                    
                    <hr>
                    
                    <h6>Test All Education Levels</h6>
                    <div id="allLevelsTest" class="mt-3">
                        <button type="button" id="testAllLevels" class="btn btn-primary">Test All Levels</button>
                        <div id="allLevelsResponse" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Test button click handler
    document.getElementById('testButton').addEventListener('click', function() {
        const educationLevelId = document.getElementById('education_level').value;
        if (!educationLevelId) {
            alert('Please select an education level');
            return;
        }
        
        testApi(educationLevelId, 'apiResponse');
    });
    
    // Test Elementary button click handler
    document.getElementById('testElementary').addEventListener('click', function() {
        testApi(2, 'elementaryResponse');
    });
    
    // Test All Levels button click handler
    document.getElementById('testAllLevels').addEventListener('click', function() {
        const container = document.getElementById('allLevelsResponse');
        container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        const promises = [];
        <?php foreach ($education_levels as $level): ?>
            promises.push(
                fetch('get_grade_levels.php?education_level_id=<?php echo $level['id']; ?>')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        return {
                            id: <?php echo $level['id']; ?>,
                            name: '<?php echo addslashes($level['level_name']); ?>',
                            response: data
                        };
                    })
                    .catch(error => {
                        return {
                            id: <?php echo $level['id']; ?>,
                            name: '<?php echo addslashes($level['level_name']); ?>',
                            error: error.message
                        };
                    })
            );
        <?php endforeach; ?>
        
        Promise.all(promises)
            .then(results => {
                let html = '<div class="table-responsive"><table class="table table-striped">';
                html += '<thead><tr><th>ID</th><th>Education Level</th><th>Response</th></tr></thead><tbody>';
                
                results.forEach(result => {
                    html += '<tr>';
                    html += '<td>' + result.id + '</td>';
                    html += '<td>' + result.name + '</td>';
                    
                    if (result.error) {
                        html += '<td class="text-danger">Error: ' + result.error + '</td>';
                    } else if (result.response && result.response.length > 0) {
                        html += '<td class="text-success">' + result.response.length + ' grade levels found<br>';
                        html += '<ul>';
                        result.response.forEach(grade => {
                            html += '<li>' + grade.value + ': ' + grade.label + '</li>';
                        });
                        html += '</ul></td>';
                    } else {
                        html += '<td class="text-warning">No grade levels returned</td>';
                    }
                    
                    html += '</tr>';
                });
                
                html += '</tbody></table></div>';
                container.innerHTML = html;
            });
    });
    
    // Function to test API
    function testApi(educationLevelId, containerId) {
        const container = document.getElementById(containerId);
        container.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
        
        fetch('get_grade_levels.php?education_level_id=' + educationLevelId)
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Invalid content type: ' + contentType);
                }
                return response.json();
            })
            .then(data => {
                let html = '<div class="alert alert-success">API call successful!</div>';
                html += '<h6>Response:</h6>';
                html += '<pre class="bg-light p-3">' + JSON.stringify(data, null, 2) + '</pre>';
                
                if (data && data.length > 0) {
                    html += '<h6>Grade Levels:</h6>';
                    html += '<ul class="list-group">';
                    data.forEach(grade => {
                        html += '<li class="list-group-item">' + grade.value + ': ' + grade.label + '</li>';
                    });
                    html += '</ul>';
                } else {
                    html += '<div class="alert alert-warning">No grade levels returned.</div>';
                }
                
                container.innerHTML = html;
            })
            .catch(error => {
                container.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
    }
});
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 