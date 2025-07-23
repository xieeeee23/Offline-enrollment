<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Calculate the relative path to the includes directory
if (!isset($relative_path)) {
    $relative_path = '';
    $current_path = $_SERVER['SCRIPT_NAME'];
    if (strpos($current_path, 'modules') !== false) {
        $relative_path = '../../';
    } elseif (strpos($current_path, 'admin') !== false || 
              strpos($current_path, 'teacher') !== false || 
              strpos($current_path, 'registrar') !== false || 
              strpos($current_path, 'reports') !== false) {
        $relative_path = '../../../';
    }
}

require_once $relative_path . 'includes/config.php';
require_once $relative_path . 'includes/functions.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user = [];
$user_settings = [];

if ($is_logged_in) {
    // Get current user information
    $user_id = $_SESSION['user_id'];
    $current_user = getUserById($user_id);
    
    if (!$current_user) {
        // If user doesn't exist, log them out
        session_destroy();
        header("Location: " . BASE_URL . "login.php");
        exit;
    }
    
    // Get user settings
    $user_settings = isset($_SESSION['user_settings']) ? $_SESSION['user_settings'] : getUserSettings($user_id);
    
    // Store settings in session if not already there
    if (!isset($_SESSION['user_settings'])) {
        $_SESSION['user_settings'] = $user_settings;
    }
}

// Get page title
$page_title = isset($title) ? $title . ' | ' . SYSTEM_NAME : SYSTEM_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - LocalEnroll Pro" : "LocalEnroll Pro"; ?></title>
    
    <!-- Google Fonts - Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $relative_path; ?>assets/css/custom.css">
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body<?php echo $is_logged_in ? applyUserSettings($user_settings) : ''; ?>>
<?php if ($is_logged_in): ?>
    <!-- Sidebar Layout -->
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <img src="<?php echo $relative_path; ?>assets/images/logo.jpg" alt="KLIA Logo" class="sidebar-logo">
                <div class="sidebar-brand">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</div>
                <div class="sidebar-brand-small">KLIA</div>
            </div>
            
            <div class="sidebar-menu">
                <ul class="sidebar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'registrar'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/registrar/students.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/teacher/teachers.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/teacher/teachers.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Teachers</span>
                        </a>
                    </li>
                    
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/admin/schedule.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/admin/schedule.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Schedule</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'teacher'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/teacher/schedule.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/teacher/schedule.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Schedule</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Registrar Nav Items -->
                    <?php if (in_array($_SESSION['role'], ['admin', 'registrar'])): ?>
                    <a class="nav-link" href="#" id="registrarMenuTrigger">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-graduate"></i></div>
                        Academic Management
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="sb-sidenav-menu-nested nav" id="collapseRegistrar" style="display: none;">
                        <a class="nav-link <?php echo isActivePage('requirements.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/requirements.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                            Requirements
                        </a>
                        <a class="nav-link <?php echo isActivePage('enrollment_history.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/enrollment_history.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                            Enrollment History
                        </a>
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/registrar/manage_sections.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/manage_sections.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-th-list"></i></div>
                            Manage Sections
                        </a>
                        <a class="nav-link <?php echo isActivePage('subjects.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/academics/subjects.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-book"></i></div>
                            SHS Subjects
                        </a>
                        <a class="nav-link <?php echo isActivePage('strands.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/strands.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-stream"></i></div>
                            SHS Strands
                        </a>
                    </div>
                    <?php endif; ?>

                    <!-- Admin Menu Items -->
                    <?php if (checkAccess(['admin'])): ?>
                    <a class="nav-link" href="#" id="adminMenuTrigger">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-shield"></i></div>
                        System Administration
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>
                    <div class="sb-sidenav-menu-nested nav" id="collapseAdmin" style="display: none;">
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/admin/users.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/admin/users.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                            Manage Users
                        </a>
                        <a class="nav-link <?php echo isActivePage('database.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/admin/database.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-database"></i></div>
                            Database Management
                        </a>
                        <a class="nav-link <?php echo isActivePage('logs.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/admin/logs.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                            System Logs
                        </a>
                        <a class="nav-link <?php echo isActivePage('reports.php') ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/reports/reports.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-file-alt"></i></div>
                            Reports
                        </a>
                        <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'modules/registrar/auto_generate_school_years.php') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>modules/registrar/auto_generate_school_years.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-plus"></i></div>
                            Auto Generate School Years
                        </a>
                    </div>
                    <?php endif; ?>

                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Registrar menu toggle
                        const registrarTrigger = document.getElementById('registrarMenuTrigger');
                        const registrarMenu = document.getElementById('collapseRegistrar');
                        const adminTrigger = document.getElementById('adminMenuTrigger');
                        const adminMenu = document.getElementById('collapseAdmin');
                        
                        function toggleMenu(trigger, menu) {
                            trigger.addEventListener('click', function(e) {
                                e.preventDefault();
                                const isHidden = menu.style.display === 'none';
                                
                                // Close all menus first
                                document.querySelectorAll('.sb-sidenav-menu-nested').forEach(m => {
                                    m.style.display = 'none';
                                    m.previousElementSibling.querySelector('.sb-sidenav-collapse-arrow i').className = 'fas fa-angle-down';
                                });
                                
                                // Toggle current menu
                                if (isHidden) {
                                    menu.style.display = 'block';
                                    trigger.querySelector('.sb-sidenav-collapse-arrow i').className = 'fas fa-angle-up';
                                } else {
                                    menu.style.display = 'none';
                                    trigger.querySelector('.sb-sidenav-collapse-arrow i').className = 'fas fa-angle-down';
                                }
                            });
                        }
                        
                        if (registrarTrigger && registrarMenu) {
                            toggleMenu(registrarTrigger, registrarMenu);
                        }
                        
                        if (adminTrigger && adminMenu) {
                            toggleMenu(adminTrigger, adminMenu);
                        }
                        
                        // Keep menu open if sub-item is active
                        document.querySelectorAll('.sb-sidenav-menu-nested .nav-link.active').forEach(activeItem => {
                            const menu = activeItem.closest('.sb-sidenav-menu-nested');
                            if (menu) {
                                menu.style.display = 'block';
                                const trigger = menu.previousElementSibling;
                                if (trigger) {
                                    trigger.querySelector('.sb-sidenav-collapse-arrow i').className = 'fas fa-angle-up';
                                }
                            }
                        });
                    });
                    </script>
                    
                    <!-- Settings Menu Item -->
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>settings.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-cogs"></i></div>
                        Settings
                    </a>
                </ul>
            </div>
            
            <div class="sidebar-footer">
                <!-- Sidebar toggle button removed from here -->
            </div>
        </nav>

        <!-- Sidebar overlay for mobile -->
        <div class="sidebar-overlay"></div>

        <!-- Content -->
        <div id="content" class="content">
            <!-- Top Navbar -->
            <nav class="top-navbar">
                <button id="mobileSidebarToggle" class="btn btn-link d-lg-none">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Add sidebar toggle button here -->
                <button id="sidebarToggleBtn" class="btn btn-light btn-sm" title="Toggle Sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="top-navbar-nav ms-auto">
                    <!-- Dark Mode Toggle with Dropdown -->
                    <div class="dropdown me-3">
                        <button id="darkModeToggle" class="btn btn-light btn-sm position-relative" title="Theme Options" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-moon"></i>
                            <span class="theme-indicator"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end theme-dropdown" aria-labelledby="darkModeToggle">
                            <li>
                                <h6 class="dropdown-header">Theme Options</h6>
                            </li>
                            <li>
                                <button class="dropdown-item theme-option" data-theme="light">
                                    <i class="fas fa-sun me-2"></i> Light Mode
                                    <i class="fas fa-check ms-2 theme-check light-check"></i>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item theme-option" data-theme="dark">
                                    <i class="fas fa-moon me-2"></i> Dark Mode
                                    <i class="fas fa-check ms-2 theme-check dark-check"></i>
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item theme-option" data-theme="system">
                                    <i class="fas fa-laptop me-2"></i> System Preference
                                    <i class="fas fa-check ms-2 theme-check system-check"></i>
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo $relative_path; ?>settings.php">
                                    <i class="fas fa-sliders-h me-2"></i> More Settings
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($current_user['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo $relative_path; ?>profile.php"><i class="fas fa-user me-1"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="<?php echo $relative_path; ?>settings.php"><i class="fas fa-cogs me-1"></i> Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo $relative_path; ?>logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Main Content -->
            <div class="main-content">
                <?php if (isset($page_header)): ?>
                <div class="row mb-4" data-aos="fade-up">
                    <div class="col-12">
                        <h1 class="page-header"><?php echo $page_header; ?></h1>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['alert'])): ?>
                <div data-aos="fade-down" data-aos-duration="800">
                    <?php echo $_SESSION['alert']; ?>
                    <?php unset($_SESSION['alert']); ?>
                </div>
                <?php endif; ?> 
<?php else: ?>
<div class="container">
<?php endif; ?> 