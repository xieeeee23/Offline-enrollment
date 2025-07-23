/**
 * LocalEnroll Pro - Custom JavaScript
 * Enhances the user experience with interactive features
 */

/**
 * Initialize sidebar toggle functionality
 */
function initSidebarToggle() {
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const content = document.getElementById('content');
    const overlay = document.querySelector('.sidebar-overlay');
    const toggleIcon = sidebarToggleBtn ? sidebarToggleBtn.querySelector('i') : null;
    
    if (sidebar && content) {
        // Set initial state based on user preferences - default to expanded (not collapsed)
        const sidebarExpandedPref = document.body.getAttribute('data-sidebar-expanded');
        if (sidebarExpandedPref === '0' && !sidebar.classList.contains('collapsed')) {
            sidebar.classList.add('collapsed');
            content.classList.add('expanded');
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
        }
        
        // Handle desktop toggle click
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Toggle sidebar collapsed state
                sidebar.classList.toggle('collapsed');
                content.classList.toggle('expanded');
                
                // Update icon direction
                if (toggleIcon) {
                    if (sidebar.classList.contains('collapsed')) {
                        toggleIcon.classList.remove('fa-chevron-left');
                        toggleIcon.classList.add('fa-chevron-right');
                    } else {
                        toggleIcon.classList.remove('fa-chevron-right');
                        toggleIcon.classList.add('fa-chevron-left');
                    }
                }
                
                // Fix for overlay - ensure it's hidden when sidebar is collapsed
                if (overlay) {
                    if (sidebar.classList.contains('collapsed')) {
                        overlay.style.opacity = '0';
                        overlay.style.visibility = 'hidden';
                    } else if (window.innerWidth <= 992) {
                        // Only show overlay on mobile
                        overlay.style.opacity = '1';
                        overlay.style.visibility = 'visible';
                    }
                }
                
                // Save preference if user is logged in
                if (typeof updateUserSettings === 'function') {
                    const isExpanded = !sidebar.classList.contains('collapsed');
                    updateUserSettings({ sidebar_expanded: isExpanded ? 1 : 0 });
                }
            });
        }
        
        // Handle mobile toggle click
        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.remove('collapsed');
                
                // Show overlay on mobile when sidebar is opened
                if (overlay && window.innerWidth <= 992) {
                    overlay.style.opacity = '1';
                    overlay.style.visibility = 'visible';
                }
            });
        }
        
        // Handle overlay click to close sidebar on mobile
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.add('collapsed');
                overlay.style.opacity = '0';
                overlay.style.visibility = 'hidden';
            });
        }
        
        // Modified: Only close sidebar when clicking outside on mobile
        // Remove the event listener that closes the sidebar when clicking outside
        // This allows the sidebar to stay open when navigating
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) { // Only apply on mobile
                if (!sidebar.contains(e.target) && 
                    e.target !== sidebarToggleBtn && 
                    e.target !== mobileSidebarToggle &&
                    !e.target.closest('#sidebarToggleBtn') &&
                    !e.target.closest('#mobileSidebarToggle')) {
                    sidebar.classList.add('collapsed');
                    
                    // Hide overlay when sidebar is closed
                    if (overlay) {
                        overlay.style.opacity = '0';
                        overlay.style.visibility = 'hidden';
                    }
                }
            }
        });
        
        // Handle window resize to manage overlay visibility
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                // Hide overlay on larger screens
                if (overlay) {
                    overlay.style.opacity = '0';
                    overlay.style.visibility = 'hidden';
                }
            } else if (!sidebar.classList.contains('collapsed') && overlay) {
                // Show overlay on smaller screens when sidebar is open
                overlay.style.opacity = '1';
                overlay.style.visibility = 'visible';
            }
        });
    }
}

/**
 * Update user settings via AJAX
 * @param {Object} settings - Object containing settings to update
 */
function updateUserSettings(settings) {
    // Only proceed if we have settings to update
    if (!settings || Object.keys(settings).length === 0) {
        return;
    }
    
    // Create form data
    const formData = new FormData();
    
    // Add settings to form data
    for (const [key, value] of Object.entries(settings)) {
        formData.append(key, value);
    }
    
    // Send AJAX request
    fetch('update_settings_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Settings updated successfully');
        } else {
            console.error('Error updating settings:', data.message);
        }
    })
    .catch(error => {
        console.error('Error updating settings:', error);
    });
}

// Initialize all functions when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize theme system
    initThemeSystem();
    
    // Initialize tooltips
    initTooltips();
    
    // Initialize DataTables
    initDataTables();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize card animations
    initCardAnimations();
    
    // Initialize password toggle
    initPasswordToggle();
    
    // Initialize confirm delete
    initConfirmDelete();
    
    // Initialize status toggle
    initStatusToggle();
    
    // Initialize print buttons
    initPrintButtons();
    
    // Highlight active nav
    highlightActiveNav();
    
    // Initialize export buttons
    initExportButtons();
    
    // Initialize sidebar toggle
    initSidebarToggle();
    
    // Track sidebar state for overlay management
    trackSidebarState();
});

/**
 * Track sidebar state to properly manage overlay
 */
function trackSidebarState() {
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebar) {
        // Set initial state
        if (!sidebar.classList.contains('collapsed') && window.innerWidth <= 992) {
            document.body.classList.add('sidebar-active');
        } else {
            document.body.classList.remove('sidebar-active');
        }
        
        // Create a MutationObserver to watch for changes to the sidebar's classes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    if (!sidebar.classList.contains('collapsed') && window.innerWidth <= 992) {
                        document.body.classList.add('sidebar-active');
                    } else {
                        document.body.classList.remove('sidebar-active');
                    }
                }
            });
        });
        
        // Start observing the sidebar for class changes
        observer.observe(sidebar, { attributes: true });
        
        // Update on window resize
        window.addEventListener('resize', function() {
            if (!sidebar.classList.contains('collapsed') && window.innerWidth <= 992) {
                document.body.classList.add('sidebar-active');
            } else {
                document.body.classList.remove('sidebar-active');
            }
        });
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }
}

/**
 * Initialize DataTables for tables
 */
function initDataTables() {
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').each(function() {
            // Check if this table has already been initialized as a DataTable
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    language: {
                        search: "<i class='fas fa-search'></i> _INPUT_",
                        searchPlaceholder: "Search records...",
                        lengthMenu: "_MENU_ records per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ records",
                        infoEmpty: "Showing 0 to 0 of 0 records",
                        infoFiltered: "(filtered from _MAX_ total records)"
                    },
                    dom: '<"row"<"col-md-6"l><"col-md-6"f>><"table-responsive"t><"row"<"col-md-6"i><"col-md-6"p>>',
                    pagingType: "simple_numbers"
                });
            }
        });
    }
}

/**
 * Initialize form validation for Bootstrap forms
 */
function initFormValidation() {
    // Fetch all forms with the class 'needs-validation'
    const forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Initialize card animations
 */
function initCardAnimations() {
    const dashboardCards = document.querySelectorAll('.dashboard-card');
    
    dashboardCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.dashboard-icon');
            if (icon) {
                icon.classList.add('animate__animated', 'animate__pulse');
            }
        });
        
        card.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.dashboard-icon');
            if (icon) {
                icon.classList.remove('animate__animated', 'animate__pulse');
            }
        });
    });
}

/**
 * Initialize password toggle visibility
 */
function initPasswordToggle() {
    const togglePassword = document.querySelectorAll('.toggle-password');
    
    togglePassword.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = document.querySelector(this.getAttribute('data-target'));
            
            if (passwordInput) {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the icon
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            }
        });
    });
}

/**
 * Initialize confirm delete functionality
 */
function initConfirmDelete() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('href');
            const itemName = this.getAttribute('data-item-name') || 'this item';
            
            Swal.fire({
                title: 'Are you sure?',
                text: `You are about to delete ${itemName}. This action cannot be undone!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
}

/**
 * Initialize status toggle functionality
 */
function initStatusToggle() {
    const statusToggles = document.querySelectorAll('.status-toggle');
    
    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const itemId = this.getAttribute('data-id');
            const itemType = this.getAttribute('data-type');
            const status = this.checked ? 'Active' : 'Inactive';
            const url = `ajax/update_status.php?type=${itemType}&id=${itemId}&status=${status}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        const toast = document.createElement('div');
                        toast.className = 'position-fixed bottom-0 end-0 p-3';
                        toast.style.zIndex = '5';
                        toast.innerHTML = `
                            <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-check-circle me-2"></i> Status updated successfully!
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
                    } else {
                        // Show error and revert toggle
                        this.checked = !this.checked;
                        alert('Failed to update status. Please try again.');
                    }
                })
                .catch(error => {
                    // Show error and revert toggle
                    this.checked = !this.checked;
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
        });
    });
}

/**
 * Initialize print buttons
 */
function initPrintButtons() {
    const printButtons = document.querySelectorAll('.btn-print');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetSelector = this.getAttribute('data-print-target');
            if (!targetSelector) {
                console.error('Print target not specified');
                return;
            }
            
            // Get the table ID without the # prefix
            const tableID = targetSelector.replace('#', '');
            
            // Get the page title or card title for the print title
            let title = '';
            const pageTitle = document.querySelector('h1');
            if (pageTitle) {
                title = pageTitle.textContent.trim();
            } else {
                const cardTitle = document.querySelector('.card-title');
                if (cardTitle) {
                    title = cardTitle.textContent.trim();
                }
            }
            
            // Call the enhanced printTable function from export-table.js
            if (typeof printTable === 'function') {
                printTable(tableID, title);
            } else {
                console.error('printTable function not found. Make sure export-table.js is loaded.');
            }
        });
    });
}

/**
 * Highlight active navigation item
 */
function highlightActiveNav() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href) && href !== '#' && href !== '/') {
            link.classList.add('active');
        }
    });
}

/**
 * Initialize export to Excel buttons
 */
function initExportButtons() {
    const exportButtons = document.querySelectorAll('.btn-export-excel');
    
    exportButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const tableId = this.getAttribute('data-table-id');
            const filename = this.getAttribute('data-filename') || 'export';
            
            // Get the page title or card title for the export title
            let title = '';
            const pageTitle = document.querySelector('h1');
            if (pageTitle) {
                title = pageTitle.textContent.trim();
            } else {
                const cardTitle = document.querySelector('.card-title');
                if (cardTitle) {
                    title = cardTitle.textContent.trim();
                }
            }
            
            // Call the enhanced exportTableToExcel function from export-table.js
            if (typeof exportTableToExcel === 'function') {
                exportTableToExcel(tableId, filename);
            } else {
                console.error('exportTableToExcel function not found. Make sure export-table.js is loaded.');
            }
        });
    });
}

/**
 * Export HTML table to Excel
 * @param {string} tableID - The ID of the table to export
 * @param {string} filename - The filename for the downloaded Excel file
 */
function exportTableToExcel(tableID, filename = '') {
    const table = document.getElementById(tableID);
    if (!table) {
        console.error('Table not found with ID:', tableID);
        return;
    }

    // Check if XLSX library is loaded
    if (typeof XLSX === 'undefined') {
        console.error('XLSX library not found. Make sure SheetJS is properly loaded.');
        alert('Excel export library not found. Please contact the administrator.');
        return;
    }

    // Clone the table to avoid modifying the original
    const tableClone = table.cloneNode(true);
    
    // Remove action buttons column if it exists
    const headerRow = tableClone.querySelector('thead tr');
    const bodyRows = tableClone.querySelectorAll('tbody tr');
    
    if (headerRow && headerRow.lastElementChild && headerRow.lastElementChild.textContent.trim().toLowerCase() === 'actions') {
        const actionColumnIndex = headerRow.cells.length - 1;
        headerRow.deleteCell(actionColumnIndex);
        
        bodyRows.forEach(row => {
            if (row.cells.length > actionColumnIndex) {
                row.deleteCell(actionColumnIndex);
            }
        });
    }

    // Generate filename if not provided
    if (!filename) {
        const now = new Date();
        filename = 'export_' + now.getFullYear() + '-' + 
                  String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                  String(now.getDate()).padStart(2, '0') + '.xlsx';
    } else if (!filename.endsWith('.xlsx')) {
        filename += '.xlsx';
    }
    
    try {
    // Convert to XLSX format
    const ws = XLSX.utils.table_to_sheet(tableClone);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
    
    // Save the file
    XLSX.writeFile(wb, filename);
    } catch (error) {
        console.error('Error exporting to Excel:', error);
        alert('Failed to export to Excel. Please try again or contact support.');
    }
} 

/**
 * Initialize theme system with enhanced functionality
 */
function initThemeSystem() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const body = document.body;
    const themeOptions = document.querySelectorAll('.theme-option');
    const themeIndicator = document.querySelector('.theme-indicator');
    
    // Function to apply theme based on preference with transition effect
    function applyTheme(theme, savePreference = true) {
        // Add transition class for smooth theme change
        body.classList.add('theme-transition');
        
        // Remove existing theme classes
        body.classList.remove('dark-mode', 'high-contrast-mode', 
                            'font-size-small', 'font-size-normal', 
                            'font-size-large', 'font-size-xlarge');
        
        // Apply dark mode if needed
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            if (darkModeToggle) {
                darkModeToggle.innerHTML = '<i class="fas fa-moon"></i><span class="theme-indicator"></span>';
                darkModeToggle.setAttribute('title', 'Theme: Dark Mode');
                darkModeToggle.classList.remove('btn-light');
                darkModeToggle.classList.add('btn-dark');
                
                // Update indicator
                const indicator = darkModeToggle.querySelector('.theme-indicator');
                if (indicator) {
                    indicator.style.backgroundColor = '#6c757d';
                }
            }
        } else {
            if (darkModeToggle) {
                darkModeToggle.innerHTML = '<i class="fas fa-sun"></i><span class="theme-indicator"></span>';
                darkModeToggle.setAttribute('title', 'Theme: Light Mode');
                darkModeToggle.classList.remove('btn-dark');
                darkModeToggle.classList.add('btn-light');
                
                // Update indicator
                const indicator = darkModeToggle.querySelector('.theme-indicator');
                if (indicator) {
                    indicator.style.backgroundColor = theme === 'system' ? '#6c757d' : '#ffc107';
                }
            }
        }
        
        // Update theme check marks in dropdown
        updateThemeChecks(theme);
        
        // Store theme preference if requested
        if (savePreference) {
            localStorage.setItem('theme', theme);
            
            // If on settings page, update radio button to match
            const themeRadios = document.querySelectorAll('input[name="theme_preference"]');
            if (themeRadios.length > 0) {
                for (const radio of themeRadios) {
                    if (radio.value === theme) {
                        radio.checked = true;
                        break;
                    }
                }
                
                // Trigger preview update if applicable
                const event = new Event('change');
                document.querySelector('input[name="theme_preference"]:checked').dispatchEvent(event);
            }
        }
        
        // Remove transition class after animation completes
        setTimeout(() => {
            body.classList.remove('theme-transition');
        }, 500);
    }
    
    // Update check marks in theme dropdown
    function updateThemeChecks(activeTheme) {
        const lightCheck = document.querySelector('.light-check');
        const darkCheck = document.querySelector('.dark-check');
        const systemCheck = document.querySelector('.system-check');
        
        if (lightCheck) lightCheck.style.visibility = activeTheme === 'light' ? 'visible' : 'hidden';
        if (darkCheck) darkCheck.style.visibility = activeTheme === 'dark' ? 'visible' : 'hidden';
        if (systemCheck) systemCheck.style.visibility = activeTheme === 'system' ? 'visible' : 'hidden';
    }
    
    // Check for saved theme preference or respect OS preference
    function initializeTheme() {
        // Get user preference if logged in
        const userThemePref = document.body.getAttribute('data-theme-preference');
        
        // Get saved preference from localStorage
        const savedTheme = localStorage.getItem('theme');
        
        // Check system preference
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        let theme = 'light'; // Default theme
        let actualTheme = 'light'; // The actual theme to apply (light/dark)
        
        // Determine which theme to use based on priorities
        if (userThemePref && userThemePref !== 'system') {
            // User explicit preference takes highest priority
            theme = userThemePref;
            actualTheme = theme;
        } else if (savedTheme && savedTheme !== 'system') {
            // Saved local preference takes second priority
            theme = savedTheme;
            actualTheme = theme;
        } else if (userThemePref === 'system' || savedTheme === 'system' || (!userThemePref && !savedTheme)) {
            // System preference
            theme = 'system';
            actualTheme = prefersDark ? 'dark' : 'light';
        }
        
        // Apply the determined theme
        applyTheme(theme, false);
        
        // Apply actual dark/light mode
        if (actualTheme === 'dark') {
            body.classList.add('dark-mode');
        }
        
        // Update theme indicator color based on theme
        if (themeIndicator) {
            if (theme === 'system') {
                themeIndicator.style.backgroundColor = '#6c757d'; // Gray for system
            } else if (theme === 'dark') {
                themeIndicator.style.backgroundColor = '#6c757d'; // Dark gray for dark mode
            } else {
                themeIndicator.style.backgroundColor = '#ffc107'; // Yellow for light mode
            }
        }
    }
    
    // Initialize theme on page load
    initializeTheme();
    
    // Toggle dark mode when button is clicked (legacy support)
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function(e) {
            // Only toggle if directly clicking the button (not the dropdown)
            if (e.target === darkModeToggle || e.target.tagName === 'I') {
                e.stopPropagation(); // Prevent dropdown from opening
                
                const currentTheme = body.classList.contains('dark-mode') ? 'dark' : 'light';
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                applyTheme(newTheme);
                
                // Show toast notification
                showThemeToast(newTheme);
            }
        });
    }
    
    // Theme options in dropdown
    themeOptions.forEach(option => {
        option.addEventListener('click', function() {
            const theme = this.getAttribute('data-theme');
            applyTheme(theme);
            
            // Show toast notification
            showThemeToast(theme);
        });
    });
    
    // Show toast notification when theme changes
    function showThemeToast(theme) {
        // Create toast container if it doesn't exist
        let toastContainer = document.getElementById('theme-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'theme-toast-container';
            toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '11';
            document.body.appendChild(toastContainer);
        }
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-dark border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        // Set toast content based on theme
        let icon, message;
        switch(theme) {
            case 'dark':
                icon = 'moon';
                message = 'Dark mode enabled';
                break;
            case 'light':
                icon = 'sun';
                message = 'Light mode enabled';
                break;
            case 'system':
                icon = 'laptop';
                message = 'Using system preference';
                break;
        }
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${icon} me-2"></i> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        // Add toast to container
        toastContainer.appendChild(toast);
        
        // Initialize and show toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: 2000
        });
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', function() {
            toast.remove();
        });
    }
    
    // Listen for system preference changes
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    darkModeMediaQuery.addEventListener('change', e => {
        // Only apply if the user preference is set to "system"
        const userThemePref = document.body.getAttribute('data-theme-preference');
        const savedTheme = localStorage.getItem('theme');
        
        if ((userThemePref === 'system' || !userThemePref) && 
            (savedTheme === 'system' || !savedTheme)) {
            // Apply the appropriate theme
            if (e.matches) {
                body.classList.add('dark-mode');
                if (darkModeToggle) {
                    darkModeToggle.innerHTML = '<i class="fas fa-moon"></i><span class="theme-indicator"></span>';
                    darkModeToggle.classList.remove('btn-light');
                    darkModeToggle.classList.add('btn-dark');
                }
            } else {
                body.classList.remove('dark-mode');
                if (darkModeToggle) {
                    darkModeToggle.innerHTML = '<i class="fas fa-sun"></i><span class="theme-indicator"></span>';
                    darkModeToggle.classList.remove('btn-dark');
                    darkModeToggle.classList.add('btn-light');
                }
            }
            
            // Update theme indicator
            const indicator = document.querySelector('.theme-indicator');
            if (indicator) {
                indicator.style.backgroundColor = '#6c757d'; // Gray for system preference
            }
        }
    });
    
    // Apply other user preferences if available
    function applyUserPreferences() {
        // Apply table compact preference
        const tableCompactPref = document.body.getAttribute('data-table-compact');
        if (tableCompactPref === '1') {
            document.querySelectorAll('.table').forEach(table => {
                table.classList.add('table-compact');
            });
        }
        
        // Apply table hover preference
        const tableHoverPref = document.body.getAttribute('data-table-hover');
        if (tableHoverPref === '1') {
            document.querySelectorAll('.table').forEach(table => {
                table.classList.add('table-hover');
            });
        }
        
        // Apply font size preference
        const fontSizePref = document.body.getAttribute('data-font-size');
        if (fontSizePref && fontSizePref !== 'normal') {
            document.body.classList.add(`font-size-${fontSizePref}`);
        }
        
        // Apply high contrast preference
        const highContrastPref = document.body.getAttribute('data-high-contrast');
        if (highContrastPref === '1') {
            document.body.classList.add('high-contrast-mode');
        }
        
        // Apply color blind mode preference
        const colorBlindPref = document.body.getAttribute('data-color-blind-mode');
        if (colorBlindPref === '1') {
            document.body.classList.add('color-blind-mode');
        }
        
        // Apply sidebar expanded preference
        const sidebarExpandedPref = document.body.getAttribute('data-sidebar-expanded');
        const sidebar = document.querySelector('.sidebar');
        const content = document.getElementById('content');
        const toggleIcon = document.querySelector('#sidebarToggleBtn i');
        
        if (sidebarExpandedPref === '0' && sidebar) {
            sidebar.classList.add('collapsed');
            if (content) content.classList.add('expanded');
            if (toggleIcon) {
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
            }
        }
        
        // Apply animation settings
        const enableAnimations = document.body.getAttribute('data-enable-animations');
        if (enableAnimations === '0') {
            document.body.classList.add('animations-disabled');
        }
        
        // Apply animation speed
        const animationSpeed = document.body.getAttribute('data-animation-speed');
        if (animationSpeed) {
            // Remove existing animation speed classes
            document.body.classList.remove('animation-speed-slow', 'animation-speed-normal', 'animation-speed-fast');
            
            // Add the appropriate class
            document.body.classList.add(`animation-speed-${animationSpeed}`);
        }
        
        // Apply motion reduction settings
        const motionReduce = document.body.getAttribute('data-motion-reduce');
        if (motionReduce) {
            if (motionReduce === 'reduce') {
                document.body.classList.add('motion-reduce');
            } else if (motionReduce === 'disable') {
                document.body.classList.add('motion-disable');
            }
        }
        
        // Apply card style settings
        const cardStyle = document.body.getAttribute('data-card-style');
        if (cardStyle && cardStyle !== 'default') {
            document.querySelectorAll('.card').forEach(card => {
                card.classList.add(`card-style-${cardStyle}`);
            });
        }
        
        // Apply focus visibility settings
        const focusVisible = document.body.getAttribute('data-focus-visible');
        if (focusVisible === '1') {
            document.body.classList.add('focus-visible');
        } else {
            document.body.classList.remove('focus-visible');
        }
    }
    
    // Apply other preferences
    applyUserPreferences();
} 