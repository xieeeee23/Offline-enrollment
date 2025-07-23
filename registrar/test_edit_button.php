<?php
// Include necessary files
$relative_path = '../../';
require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in and has admin or registrar role
if (!checkAccess(['admin', 'registrar'])) {
    $_SESSION['alert'] = showAlert('You do not have permission to access this page.', 'danger');
    header('Location: ' . $relative_path . 'dashboard.php');
    exit;
}

// Get a sample requirement for testing
$query = "SELECT * FROM requirements ORDER BY id LIMIT 1";
$result = mysqli_query($conn, $query);
$sample_requirement = mysqli_fetch_assoc($result);

// Include header
$title = 'Test Edit Button';
require_once $relative_path . 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Test Edit Button Functionality</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo $relative_path; ?>modules/registrar/requirements.php">Requirements</a></li>
        <li class="breadcrumb-item active">Test Edit Button</li>
    </ol>
    
    <?php if (isset($_SESSION['alert'])) { echo $_SESSION['alert']; unset($_SESSION['alert']); } ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-vial me-1"></i> Test Environment
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Sample Requirement</h5>
                    <?php if ($sample_requirement): ?>
                        <table class="table table-bordered">
                            <tr>
                                <th>ID</th>
                                <td><?php echo $sample_requirement['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Name</th>
                                <td><?php echo htmlspecialchars($sample_requirement['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td><?php echo htmlspecialchars($sample_requirement['type']); ?></td>
                            </tr>
                            <tr>
                                <th>Program</th>
                                <td><?php echo htmlspecialchars($sample_requirement['program']); ?></td>
                            </tr>
                            <tr>
                                <th>Actions</th>
                                <td>
                                    <button type="button" class="btn btn-primary edit-requirement-btn" data-id="<?php echo $sample_requirement['id']; ?>">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">No requirements found in the database.</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5>Debug Console</h5>
                    <div id="debug-console" class="bg-dark text-light p-3" style="height: 300px; overflow-y: auto;">
                        <div class="text-muted">Debug information will appear here...</div>
                    </div>
                    <div class="mt-2">
                        <button id="clear-console" class="btn btn-sm btn-secondary">Clear Console</button>
                        <button id="test-ajax" class="btn btn-sm btn-info ms-2">Test AJAX</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Requirement Modal -->
<div class="modal fade" id="editRequirementModal" tabindex="-1" aria-labelledby="editRequirementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-white" id="editRequirementModalLabel">Edit Requirement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="editModalLoader" class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading requirement data...</p>
                </div>
                
                <div id="editModalContent" style="display: none;">
                    <form id="editRequirementForm" method="post" action="<?php echo $relative_path; ?>modules/registrar/process_requirement.php">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="requirement_id" id="editRequirementId">
                        <div class="mb-3">
                            <label for="editRequirementName" class="form-label">Requirement Name</label>
                            <input type="text" class="form-control" id="editRequirementName" name="requirement_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editRequirementType" class="form-label">Requirement Type</label>
                            <select class="form-select" id="editRequirementType" name="requirement_type" required>
                                <option value="document">Document</option>
                                <option value="payment">Payment</option>
                                <option value="form">Form</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editRequiredFor" class="form-label">Required For</label>
                            <select class="form-select" id="editRequiredFor" name="required_for" required>
                                <option value="all">All Programs</option>
                                <option value="undergraduate">Undergraduate</option>
                                <option value="graduate">Graduate</option>
                                <option value="masters">Masters</option>
                                <option value="phd">PhD</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editRequirementDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editRequirementDescription" name="requirement_description" rows="3"></textarea>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="editIsRequired" name="is_required">
                            <label class="form-check-label" for="editIsRequired">Required for Enrollment</label>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editRequirementForm" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Update Requirement
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Debug logger function
    function debugLog(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const typeClass = type === 'error' ? 'text-danger' : 
                         type === 'success' ? 'text-success' : 
                         type === 'warning' ? 'text-warning' : 'text-info';
        
        const logEntry = `<div class="${typeClass}"><small>${timestamp}</small> ${message}</div>`;
        $('#debug-console').append(logEntry);
        
        // Auto-scroll to bottom
        const console = document.getElementById('debug-console');
        console.scrollTop = console.scrollHeight;
    }
    
    $(document).ready(function() {
        debugLog('Page loaded, test environment ready');
        
        // Clear console button
        $('#clear-console').on('click', function() {
            $('#debug-console').html('<div class="text-muted">Console cleared...</div>');
        });
        
        // Test AJAX button
        $('#test-ajax').on('click', function() {
            debugLog('Testing AJAX connection...', 'info');
            $.ajax({
                url: '<?php echo $relative_path; ?>modules/registrar/process_requirement.php',
                type: 'GET',
                data: { 
                    action: 'get', 
                    id: <?php echo $sample_requirement ? $sample_requirement['id'] : 0; ?> 
                },
                dataType: 'json',
                success: function(response) {
                    debugLog('AJAX response received: ' + JSON.stringify(response), 'success');
                },
                error: function(xhr, status, error) {
                    debugLog('AJAX Error: ' + error, 'error');
                    debugLog('Response Text: ' + xhr.responseText, 'error');
                }
            });
        });
        
        // Add click event handler for edit buttons
        $('.edit-requirement-btn').on('click', function() {
            const requirementId = $(this).data('id');
            debugLog('Edit button clicked for requirement ID: ' + requirementId, 'info');
            editRequirement(requirementId);
        });
    });
    
    // Function to populate edit modal with requirement data
    function editRequirement(id) {
        debugLog('Edit requirement function called for ID: ' + id, 'info');
        
        // Show the modal first to prevent flickering
        $('#editRequirementModal').modal('show');
        
        // Show loading indicator
        $('#editModalLoader').show();
        $('#editModalContent').hide();
        
        // Fetch requirement data via AJAX
        $.ajax({
            url: '<?php echo $relative_path; ?>modules/registrar/process_requirement.php',
            type: 'GET',
            data: { 
                action: 'get', 
                id: id 
            },
            dataType: 'json',
            success: function(response) {
                debugLog('Response received: ' + JSON.stringify(response), 'success');
                
                if (response && response.success === true) {
                    var requirement = response.data;
                    
                    // Populate the form fields
                    $('#editRequirementId').val(requirement.id);
                    $('#editRequirementName').val(requirement.name);
                    $('#editRequirementType').val(requirement.type);
                    $('#editRequiredFor').val(requirement.program);
                    $('#editRequirementDescription').val(requirement.description);
                    $('#editIsRequired').prop('checked', requirement.is_required == 1);
                    
                    debugLog('Form populated with requirement data', 'success');
                    
                    // Show the modal content
                    $('#editModalLoader').hide();
                    $('#editModalContent').show();
                } else {
                    $('#editModalLoader').hide();
                    debugLog('Error: ' + (response && response.message ? response.message : 'Unknown error'), 'error');
                    $('#editRequirementModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                $('#editModalLoader').hide();
                debugLog('AJAX Error: ' + error, 'error');
                debugLog('Status: ' + status, 'error');
                debugLog('Response Text: ' + xhr.responseText, 'error');
                $('#editRequirementModal').modal('hide');
            }
        });
    }
</script>

<?php require_once $relative_path . 'includes/footer.php'; ?> 