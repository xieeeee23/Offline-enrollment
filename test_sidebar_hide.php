<?php
$title = 'Test Sidebar Hide';
$page_header = 'Test Sidebar Hide';
$relative_path = '';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Sidebar Hide Test</h5>
                </div>
                <div class="card-body">
                    <p>This page is used to test the sidebar hide functionality. Click the toggle button in the top navbar to completely hide/show the sidebar.</p>
                    
                    <div class="alert alert-info">
                        <h6>Instructions:</h6>
                        <ol>
                            <li>Click the toggle button in the top navbar to hide/show the sidebar.</li>
                            <li>The sidebar should completely hide when collapsed.</li>
                            <li>The content should expand to take up the full width of the screen.</li>
                            <li>The toggle button should change direction.</li>
                            <li>Your preference should be saved and remembered when you refresh the page.</li>
                        </ol>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Current Sidebar State:</h6>
                        <div id="sidebar-state-display" class="alert alert-secondary">
                            Checking...
                        </div>
                        
                        <button id="check-state-btn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-2"></i> Check Current State
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkStateBtn = document.getElementById('check-state-btn');
    const stateDisplay = document.getElementById('sidebar-state-display');
    
    function updateStateDisplay() {
        const sidebar = document.querySelector('.sidebar');
        const isCollapsed = sidebar.classList.contains('collapsed');
        const sidebarWidth = sidebar.offsetWidth;
        const sidebarVisible = sidebarWidth > 0;
        
        stateDisplay.innerHTML = `
            <strong>Sidebar collapsed:</strong> ${isCollapsed ? 'Yes' : 'No'}<br>
            <strong>Sidebar width:</strong> ${sidebarWidth}px<br>
            <strong>Sidebar visible:</strong> ${sidebarVisible ? 'Yes' : 'No'}<br>
            <strong>Classes on sidebar:</strong> ${sidebar.className}<br>
            <strong>Icon direction:</strong> ${isCollapsed ? 'Right' : 'Left'}<br>
            <strong>User preference:</strong> ${document.body.getAttribute('data-sidebar-expanded') === '0' ? 'Collapsed' : 'Expanded'}
        `;
        
        stateDisplay.className = isCollapsed ? 'alert alert-warning' : 'alert alert-success';
    }
    
    // Initial check
    updateStateDisplay();
    
    // Check button click
    checkStateBtn.addEventListener('click', updateStateDisplay);
    
    // Listen for sidebar toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    if (sidebarToggleBtn) {
        sidebarToggleBtn.addEventListener('click', function() {
            // Wait for the animation to complete
            setTimeout(updateStateDisplay, 300);
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 