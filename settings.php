<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // Collect settings from form
    $settings = [
        'theme_preference' => $_POST['theme_preference'] ?? 'system',
        'high_contrast' => isset($_POST['high_contrast']) ? 1 : 0,
        'sidebar_expanded' => isset($_POST['sidebar_expanded']) ? 1 : 0,
        'table_compact' => isset($_POST['table_compact']) ? 1 : 0,
        'table_hover' => isset($_POST['table_hover']) ? 1 : 0,
        'font_size' => $_POST['font_size'] ?? 'normal',
        'enable_animations' => isset($_POST['enable_animations']) ? 1 : 0,
        'animation_speed' => $_POST['animation_speed'] ?? 'normal',
        'card_style' => $_POST['card_style'] ?? 'default',
        'color_blind_mode' => isset($_POST['color_blind_mode']) ? 1 : 0,
        'motion_reduce' => $_POST['motion_reduce'] ?? 'none',
        'focus_visible' => isset($_POST['focus_visible']) ? 1 : 0
    ];
    
    // Update user settings
    if (updateUserSettings($user_id, $settings)) {
        $_SESSION['alert'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i> Settings updated successfully!
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
        
        // Update session settings
        $_SESSION['user_settings'] = getUserSettings($user_id);
    } else {
        $_SESSION['alert'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i> Error updating settings. Please try again.
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                              </div>';
    }
    
    // Redirect to avoid form resubmission
    header('Location: settings.php');
    exit;
}

// Set page title and header
$page_title = 'User Settings';
$page_header = 'User Settings';
$extra_css = '<style>
    .theme-preview { border-radius: 0.25rem; padding: 1rem; transition: all 0.3s ease; }
    .theme-dark { background-color: #343a40; color: #fff; }
    .high-contrast.theme-dark { background-color: #000; color: #fff; }
    .high-contrast.theme-light { background-color: #fff; color: #000; }
</style>';

// Refresh user settings to ensure we have the most up-to-date settings
$user_id = $_SESSION['user_id'];
$_SESSION['user_settings'] = getUserSettings($user_id);

require_once 'includes/header.php';

// Get current user settings
$user_id = $_SESSION['user_id'];
$user_settings = getUserSettings($user_id);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <?php if (isset($_SESSION['alert'])): ?>
                <?php echo $_SESSION['alert']; unset($_SESSION['alert']); ?>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Appearance Settings</h6>
                    <div>
                        <button type="button" id="applyChanges" class="btn btn-sm btn-success d-none">
                            <i class="fas fa-eye me-1"></i> Preview Changes
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form method="post" id="settingsForm">
                        <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="theme-tab" data-bs-toggle="tab" data-bs-target="#theme" type="button" role="tab" aria-controls="theme" aria-selected="true">
                                    <i class="fas fa-palette me-1"></i> Theme
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="layout-tab" data-bs-toggle="tab" data-bs-target="#layout" type="button" role="tab" aria-controls="layout" aria-selected="false">
                                    <i class="fas fa-columns me-1"></i> Layout
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="accessibility-tab" data-bs-toggle="tab" data-bs-target="#accessibility" type="button" role="tab" aria-controls="accessibility" aria-selected="false">
                                    <i class="fas fa-universal-access me-1"></i> Accessibility
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="settingsTabContent">
                            <!-- Theme Tab -->
                            <div class="tab-pane fade show active" id="theme" role="tabpanel" aria-labelledby="theme-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Theme Mode</h5>
                                        
                                        <div class="theme-selector mb-4">
                                            <div class="row g-3">
                                                <div class="col-4">
                                                    <input type="radio" class="btn-check" name="theme_preference" id="theme_light" value="light" <?php echo $user_settings['theme_preference'] === 'light' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" for="theme_light">
                                                        <i class="fas fa-sun fa-2x mb-2"></i>
                                                        <span>Light Mode</span>
                                                    </label>
                                                </div>
                                                <div class="col-4">
                                                    <input type="radio" class="btn-check" name="theme_preference" id="theme_dark" value="dark" <?php echo $user_settings['theme_preference'] === 'dark' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" for="theme_dark">
                                                        <i class="fas fa-moon fa-2x mb-2"></i>
                                                        <span>Dark Mode</span>
                                                    </label>
                                                </div>
                                                <div class="col-4">
                                                    <input type="radio" class="btn-check" name="theme_preference" id="theme_system" value="system" <?php echo $user_settings['theme_preference'] === 'system' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-3" for="theme_system">
                                                        <i class="fas fa-laptop fa-2x mb-2"></i>
                                                        <span>System</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="form-text mt-2">
                                                <i class="fas fa-info-circle me-1"></i> System preference will follow your device's light/dark mode setting.
                                            </div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="high_contrast" name="high_contrast" value="1" <?php echo $user_settings['high_contrast'] == 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="high_contrast">High Contrast Mode</label>
                                            <div class="form-text">Enhances visibility with stronger contrasts between elements</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Theme Preview</h5>
                                        <div id="theme-preview" class="theme-preview border rounded p-3 mb-3">
                                            <div class="preview-header mb-3 pb-2 border-bottom d-flex justify-content-between align-items-center">
                                                <h5 class="m-0">Preview</h5>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-bars"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-cog"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="preview-content">
                                                <div class="preview-card mb-3 p-2 border rounded">
                                                    <h6>Sample Card</h6>
                                                    <p class="small mb-2">This is how content will appear in your selected theme.</p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <button type="button" class="btn btn-sm btn-primary">Primary</button>
                                                        <span class="badge bg-success">Success</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="preview-form">
                                                    <div class="mb-2">
                                                        <label class="form-label small">Sample Input</label>
                                                        <input type="text" class="form-control form-control-sm" value="Sample text" disabled>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" checked disabled>
                                                        <label class="form-check-label small">Sample checkbox</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Layout Tab -->
                            <div class="tab-pane fade" id="layout" role="tabpanel" aria-labelledby="layout-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Layout Options</h5>
                                        
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Sidebar Settings</h6>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="sidebar_expanded" name="sidebar_expanded" value="1" <?php echo $user_settings['sidebar_expanded'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="sidebar_expanded">Keep Sidebar Expanded</label>
                                                    <div class="form-text">Always show sidebar in expanded mode on page load</div>
                                                </div>
                                                
                                                <div class="sidebar-position mb-3">
                                                    <label class="form-label">Sidebar Position</label>
                                                    <div class="btn-group w-100" role="group">
                                                        <input type="radio" class="btn-check" name="sidebar_position" id="sidebar_left" value="left" checked>
                                                        <label class="btn btn-outline-secondary" for="sidebar_left">
                                                            <i class="fas fa-align-left me-1"></i> Left
                                                        </label>
                                                        
                                                        <input type="radio" class="btn-check" name="sidebar_position" id="sidebar_right" value="right" disabled>
                                                        <label class="btn btn-outline-secondary" for="sidebar_right">
                                                            <i class="fas fa-align-right me-1"></i> Right
                                                            <span class="badge bg-secondary ms-1">Coming Soon</span>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Table Settings</h6>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="table_compact" name="table_compact" value="1" <?php echo $user_settings['table_compact'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="table_compact">Use Compact Tables</label>
                                                    <div class="form-text">Display tables in a more compact format</div>
                                                </div>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="table_hover" name="table_hover" value="1" <?php echo $user_settings['table_hover'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="table_hover">Table Row Hover Effect</label>
                                                    <div class="form-text">Highlight table rows on hover</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Content Display</h5>
                                        
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Animation Settings</h6>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="enable_animations" name="enable_animations" value="1" <?php echo $user_settings['enable_animations'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="enable_animations">Enable Animations</label>
                                                    <div class="form-text">Use animations for page transitions and UI elements</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="animation_speed" class="form-label">Animation Speed</label>
                                                    <select class="form-select" id="animation_speed" name="animation_speed">
                                                        <option value="slow" <?php echo $user_settings['animation_speed'] === 'slow' ? 'selected' : ''; ?>>Slow</option>
                                                        <option value="normal" <?php echo $user_settings['animation_speed'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                        <option value="fast" <?php echo $user_settings['animation_speed'] === 'fast' ? 'selected' : ''; ?>>Fast</option>
                                                    </select>
                                                    <div class="form-text">Control the speed of UI animations</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Card Display</h6>
                                                
                                                <div class="mb-3">
                                                    <label for="card_style" class="form-label">Card Style</label>
                                                    <select class="form-select" id="card_style" name="card_style">
                                                        <option value="default" <?php echo $user_settings['card_style'] === 'default' ? 'selected' : ''; ?>>Default</option>
                                                        <option value="flat" <?php echo $user_settings['card_style'] === 'flat' ? 'selected' : ''; ?>>Flat</option>
                                                        <option value="bordered" <?php echo $user_settings['card_style'] === 'bordered' ? 'selected' : ''; ?>>Bordered</option>
                                                    </select>
                                                    <div class="form-text">Choose how cards are displayed throughout the system</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Accessibility Tab -->
                            <div class="tab-pane fade" id="accessibility" role="tabpanel" aria-labelledby="accessibility-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Text Settings</h5>
                                        
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Font Size</h6>
                                                
                                                <div class="mb-3">
                                                    <label for="font_size" class="form-label">Text Size</label>
                                                    <select class="form-select" id="font_size" name="font_size">
                                                        <option value="small" <?php echo $user_settings['font_size'] === 'small' ? 'selected' : ''; ?>>Small</option>
                                                        <option value="normal" <?php echo $user_settings['font_size'] === 'normal' ? 'selected' : ''; ?>>Normal</option>
                                                        <option value="large" <?php echo $user_settings['font_size'] === 'large' ? 'selected' : ''; ?>>Large</option>
                                                        <option value="xlarge" <?php echo $user_settings['font_size'] === 'xlarge' ? 'selected' : ''; ?>>Extra Large</option>
                                                    </select>
                                                    <div class="form-text">Choose the text size for better readability</div>
                                                </div>
                                                
                                                <div class="font-size-preview p-2 border rounded">
                                                    <p class="small-text mb-1">Small text example</p>
                                                    <p class="normal-text mb-1">Normal text example</p>
                                                    <p class="large-text mb-1">Large text example</p>
                                                    <p class="xlarge-text mb-0">Extra large text example</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <h5 class="mb-3">Visual Accessibility</h5>
                                        
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Focus Indicators</h6>
                                                
                                                <div class="form-check form-switch mb-3">
                                                    <input class="form-check-input" type="checkbox" id="focus_visible" name="focus_visible" value="1" <?php echo $user_settings['focus_visible'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="focus_visible">Enhanced Focus Indicators</label>
                                                    <div class="form-text">Make keyboard focus more visible for better accessibility</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="motion_reduce" class="form-label">Motion Sensitivity</label>
                                                    <select class="form-select" id="motion_reduce" name="motion_reduce">
                                                        <option value="none" <?php echo $user_settings['motion_reduce'] === 'none' ? 'selected' : ''; ?>>No Reduction</option>
                                                        <option value="reduce" <?php echo $user_settings['motion_reduce'] === 'reduce' ? 'selected' : ''; ?>>Reduce Motion</option>
                                                        <option value="disable" <?php echo $user_settings['motion_reduce'] === 'disable' ? 'selected' : ''; ?>>Disable All Animations</option>
                                                    </select>
                                                    <div class="form-text">Reduce or disable animations for users sensitive to motion</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="card-title">Color Accessibility</h6>
                                                
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="color_blind_mode" name="color_blind_mode" value="1" <?php echo $user_settings['color_blind_mode'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="color_blind_mode">Color Blind Mode</label>
                                                </div>
                                                
                                                <div class="form-text mb-3">Adjusts colors to be more distinguishable for users with color vision deficiencies</div>
                                                
                                                <div class="color-blind-preview d-flex justify-content-around p-2 border rounded">
                                                    <span class="badge bg-primary">Primary</span>
                                                    <span class="badge bg-success">Success</span>
                                                    <span class="badge bg-danger">Danger</span>
                                                    <span class="badge bg-warning text-dark">Warning</span>
                                                    <span class="badge bg-info text-dark">Info</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" id="resetSettings" class="btn btn-outline-secondary">
                                <i class="fas fa-undo me-1"></i> Reset to Defaults
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Custom confirmation dialog
    function customConfirm(message, callback) {
        // Create modal element
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmModalLabel">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="confirmYes">Yes</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Get modal element
        const modalEl = document.getElementById('confirmModal');
        
        // Initialize Bootstrap modal
        const modal = new bootstrap.Modal(modalEl);
        
        // Show modal
        modal.show();
        
        // Handle Yes button click
        document.getElementById('confirmYes').addEventListener('click', function() {
            modal.hide();
            callback(true);
        });
        
        // Handle modal hidden event
        modalEl.addEventListener('hidden.bs.modal', function() {
            document.body.removeChild(modalEl);
            if (!document.getElementById('confirmYes').clicked) {
                callback(false);
            }
        });
    }
    
    // Theme preview functionality
    const themeRadios = document.querySelectorAll('input[name="theme_preference"]');
    const themePreview = document.getElementById('theme-preview');
    const highContrastSwitch = document.getElementById('high_contrast');
    const fontSizeSelect = document.getElementById('font_size');
    const settingsForm = document.getElementById('settingsForm');
    const applyChangesBtn = document.getElementById('applyChanges');
    
    // Track form changes
    let formChanged = false;
    
    // Function to update the theme preview
    function updatePreview() {
        // Remove existing classes
        themePreview.classList.remove('theme-light', 'theme-dark', 'high-contrast');
        
        // Get selected theme
        const selectedTheme = document.querySelector('input[name="theme_preference"]:checked').value;
        
        // Apply theme to preview
        if (selectedTheme === 'dark') {
            themePreview.classList.add('theme-dark');
            themePreview.style.backgroundColor = '#343a40';
            themePreview.style.color = '#fff';
        } else {
            themePreview.classList.add('theme-light');
            themePreview.style.backgroundColor = '#fff';
            themePreview.style.color = '#333';
        }
        
        // Apply high contrast if checked
        if (highContrastSwitch.checked) {
            themePreview.classList.add('high-contrast');
            if (selectedTheme === 'dark') {
                themePreview.style.backgroundColor = '#000';
                themePreview.style.color = '#fff';
            } else {
                themePreview.style.backgroundColor = '#fff';
                themePreview.style.color = '#000';
            }
        }
        
        // Apply font size to preview
        themePreview.style.fontSize = getFontSizeValue(fontSizeSelect.value);
    }
    
    // Get font size value in rem
    function getFontSizeValue(size) {
        switch(size) {
            case 'small': return '0.85rem';
            case 'normal': return '1rem';
            case 'large': return '1.15rem';
            case 'xlarge': return '1.3rem';
            default: return '1rem';
        }
    }
    
    // Apply changes in real-time
    function applyChanges() {
        const body = document.body;
        const selectedTheme = document.querySelector('input[name="theme_preference"]:checked').value;
        const highContrast = document.getElementById('high_contrast').checked;
        const fontSize = document.getElementById('font_size').value;
        const tableCompact = document.getElementById('table_compact').checked;
        const tableHover = document.getElementById('table_hover').checked;
        const enableAnimations = document.getElementById('enable_animations').checked;
        const animationSpeed = document.getElementById('animation_speed').value;
        const cardStyle = document.getElementById('card_style').value;
        const colorBlindMode = document.getElementById('color_blind_mode').checked;
        const motionReduce = document.getElementById('motion_reduce').value;
        const focusVisible = document.getElementById('focus_visible').checked;
        
        // Remove existing classes
        body.classList.remove(
            'dark-mode', 'high-contrast-mode', 
                            'font-size-small', 'font-size-normal', 
            'font-size-large', 'font-size-xlarge',
            'animations-disabled', 'motion-reduce', 'motion-disable',
            'color-blind-mode', 'focus-visible',
            'animation-speed-slow', 'animation-speed-normal', 'animation-speed-fast'
        );
        
        // Apply dark mode if needed
        if (selectedTheme === 'dark' || 
            (selectedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            body.classList.add('dark-mode');
        }
        
        // Apply high contrast if checked
        if (highContrast) {
            body.classList.add('high-contrast-mode');
        }
        
        // Apply font size
        body.classList.add(`font-size-${fontSize}`);
        
        // Apply table compact if checked
            document.querySelectorAll('.table').forEach(table => {
            if (tableCompact) {
                table.classList.add('table-compact');
            } else {
                table.classList.remove('table-compact');
            }
        });
        
        // Apply table hover effect
        document.querySelectorAll('.table').forEach(table => {
            if (tableHover) {
                table.classList.add('table-hover');
            } else {
                table.classList.remove('table-hover');
            }
        });
        
        // Apply animation settings
        if (!enableAnimations) {
            body.classList.add('animations-disabled');
        }
        
        // Apply animation speed
        body.classList.add(`animation-speed-${animationSpeed}`);
        
        // Apply motion reduction
        if (motionReduce === 'reduce') {
            body.classList.add('motion-reduce');
        } else if (motionReduce === 'disable') {
            body.classList.add('motion-disable');
        }
        
        // Apply color blind mode
        if (colorBlindMode) {
            body.classList.add('color-blind-mode');
        }
        
        // Apply focus visibility
        if (focusVisible) {
            body.classList.add('focus-visible');
        }
        
        // Apply card style
        if (cardStyle !== 'default') {
            document.querySelectorAll('.card').forEach(card => {
                card.classList.remove('card-style-flat', 'card-style-bordered');
                card.classList.add(`card-style-${cardStyle}`);
            });
        } else {
            document.querySelectorAll('.card').forEach(card => {
                card.classList.remove('card-style-flat', 'card-style-bordered');
            });
        }
    }
    
    // Update preview on theme change
    themeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            updatePreview();
            formChanged = true;
            applyChangesBtn.classList.remove('d-none');
        });
    });
    
    // Update preview on high contrast change
    highContrastSwitch.addEventListener('change', function() {
        updatePreview();
        formChanged = true;
        applyChangesBtn.classList.remove('d-none');
    });
    
    // Update preview on font size change
    fontSizeSelect.addEventListener('change', function() {
        updatePreview();
        formChanged = true;
        applyChangesBtn.classList.remove('d-none');
    });
    
    // Track form changes
    settingsForm.addEventListener('change', function() {
        formChanged = true;
        applyChangesBtn.classList.remove('d-none');
    });
    
    // Apply changes button
    applyChangesBtn.addEventListener('click', function() {
        applyChanges();
        
        // Show toast notification
        const toast = document.createElement('div');
        toast.className = 'position-fixed bottom-0 end-0 p-3';
        toast.style.zIndex = '5';
        toast.innerHTML = `
            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-check-circle me-2"></i> Changes previewed! Save to make permanent.
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        document.body.appendChild(toast);
        
        const toastEl = new bootstrap.Toast(toast.querySelector('.toast'));
        toastEl.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            document.body.removeChild(toast);
        });
    });
    
    // Reset settings button
    document.getElementById('resetSettings').addEventListener('click', function() {
        // Show confirmation dialog
        customConfirm('Reset all settings to default values?', function(confirmed) {
            if (confirmed) {
                // Reset form values
                document.getElementById('theme_system').checked = true;
                document.getElementById('high_contrast').checked = false;
                document.getElementById('sidebar_expanded').checked = true;
                document.getElementById('table_compact').checked = false;
                document.getElementById('table_hover').checked = true;
                document.getElementById('font_size').value = 'normal';
                document.getElementById('enable_animations').checked = true;
                document.getElementById('animation_speed').value = 'normal';
                document.getElementById('card_style').value = 'default';
                document.getElementById('color_blind_mode').checked = false;
                document.getElementById('motion_reduce').value = 'none';
                document.getElementById('focus_visible').checked = true;
                
                // Update preview
                updatePreview();
                
                // Show apply changes button
                formChanged = true;
                applyChangesBtn.classList.remove('d-none');
            }
        });
    });
    
    // Warn about unsaved changes when navigating away
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
    
    // Initialize preview
    updatePreview();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 