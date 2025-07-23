<?php
/**
 * Verify User Settings
 * 
 * This script displays the current user settings from the database and session
 * to help diagnose any issues with the settings system.
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Set page title and header
$page_title = 'Verify Settings';
$page_header = 'Verify User Settings';

// Get user settings from database
$user_id = $_SESSION['user_id'];
$db_settings = getUserSettings($user_id);

// Get user settings from session
$session_settings = isset($_SESSION['user_settings']) ? $_SESSION['user_settings'] : [];

require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">User Settings Verification</h6>
                    <div>
                        <a href="refresh_settings.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Refresh Settings
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-3">Database Settings</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Setting</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($db_settings as $key => $value): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($key); ?></td>
                                            <td>
                                                <?php 
                                                if (is_bool($value)) {
                                                    echo $value ? 'true' : 'false';
                                                } elseif ($value === null) {
                                                    echo '<em>null</em>';
                                                } elseif ($value === '') {
                                                    echo '<em>empty string</em>';
                                                } else {
                                                    echo htmlspecialchars($value);
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="mb-3">Session Settings</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Setting</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($session_settings)): ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No session settings found</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($session_settings as $key => $value): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($key); ?></td>
                                                <td>
                                                    <?php 
                                                    if (is_bool($value)) {
                                                        echo $value ? 'true' : 'false';
                                                    } elseif ($value === null) {
                                                        echo '<em>null</em>';
                                                    } elseif ($value === '') {
                                                        echo '<em>empty string</em>';
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="settings.php" class="btn btn-primary">
                            <i class="fas fa-cog me-1"></i> Go to Settings
                        </a>
                        <a href="add_user_settings_columns.php" class="btn btn-secondary">
                            <i class="fas fa-database me-1"></i> Add Missing Columns
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 