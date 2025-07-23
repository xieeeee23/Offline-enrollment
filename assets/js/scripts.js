/**
 * Custom scripts for the enrollment system
 */

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        });
    }
    
    // Close sidebar on small screens when clicking outside
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 768 && !e.target.closest('.sidebar, #sidebarToggle')) {
            document.body.classList.remove('sidebar-toggled');
            document.querySelector('.sidebar').classList.remove('toggled');
        }
    });
    
    // Prevent sidebar collapse on larger screens
    window.addEventListener('resize', function() {
        if (window.innerWidth < 768) {
            document.querySelectorAll('.sidebar .collapse').forEach(function(collapse) {
                if (collapse.classList.contains('show')) {
                    collapse.classList.remove('show');
                }
            });
        }
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Handle form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Add active class to nav items based on current page
    const currentLocation = window.location.href;
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        if (link.href === currentLocation) {
            link.classList.add('active');
            // If it's in a collapse menu, expand the parent
            const parent = link.closest('.collapse');
            if (parent) {
                parent.classList.add('show');
                const parentToggle = document.querySelector(`[data-bs-target="#${parent.id}"]`);
                if (parentToggle) {
                    parentToggle.classList.remove('collapsed');
                    parentToggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });
});

// Function to show a confirmation dialog
function confirmAction(message, callback) {
    if (typeof Swal !== 'undefined') {
        // Use SweetAlert2 if available
        Swal.fire({
            title: 'Confirmation',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                callback(true);
            } else {
                callback(false);
            }
        });
    } else {
        // Fallback to browser confirm
        const confirmed = confirm(message);
        callback(confirmed);
    }
}

// Function to show a notification
function showNotification(message, type = 'success') {
    if (typeof Swal !== 'undefined') {
        // Use SweetAlert2 if available
        Swal.fire({
            title: type.charAt(0).toUpperCase() + type.slice(1),
            text: message,
            icon: type,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    } else {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add the notification to the page
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 500);
        }, 3000);
    }
}

// Export table to Excel
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error(`Table with ID ${tableId} not found`);
        return;
    }
    
    if (typeof XLSX === 'undefined') {
        console.error('XLSX library is not loaded');
        return;
    }
    
    try {
        // Create a workbook with a worksheet
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        
        // Add the worksheet to the workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
        
        // Generate the Excel file and trigger download
        XLSX.writeFile(wb, `${filename}.xlsx`);
    } catch (error) {
        console.error('Error exporting table to Excel:', error);
    }
}

// Initialize export buttons
document.addEventListener('DOMContentLoaded', function() {
    const exportButtons = document.querySelectorAll('.btn-export-excel');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tableId = this.getAttribute('data-table-id');
            const filename = this.getAttribute('data-filename') || 'export';
            exportTableToExcel(tableId, filename);
        });
    });
}); 