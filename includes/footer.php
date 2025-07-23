<?php if (isset($_SESSION['user_id'])): ?>
            </div><!-- /.main-content -->
        </div><!-- /.content -->
    </div><!-- /.wrapper -->
<?php else: ?>
    </div><!-- /.container -->
<?php endif; ?>

<footer class="footer mt-4 py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                    <img src="<?php echo $relative_path; ?>assets/images/logo.jpg" alt="KLIA Logo" class="footer-logo me-2">
                    <div class="d-none d-sm-block">
                        <h6 class="mb-0">THE KRISLIZZ INTERNATIONAL</h6>
                        <p class="small mb-0">ACADEMY INC.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center mb-3 mb-md-0">
                <p class="mb-0 small">Quality Education is our COMMITMENT</p>
                <p class="small text-muted">&copy; <?php echo date('Y'); ?> All Rights Reserved</p>
            </div>
            <div class="col-md-4 text-center text-md-end">
                <p class="mb-0 small">ENROLLMENT SYSTEM</p>
                <p class="small text-muted">Version 2.0</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- AOS Animation Library -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>

<!-- Animate.css -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>assets/js/custom.js"></script>

<!-- Bootstrap core JavaScript -->
<script src="<?php echo $relative_path; ?>assets/js/jquery.min.js"></script>
<script src="<?php echo $relative_path; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $relative_path; ?>assets/js/datatables.min.js"></script>

<!-- SheetJS - Excel Export Library -->
<script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>

<!-- Custom Export Functions -->
<script src="<?php echo $relative_path; ?>assets/js/export-table.js"></script>

<!-- Requirements Print Enhancement -->
<script src="<?php echo $relative_path; ?>assets/js/requirements-print.js"></script>

<!-- Students Print Enhancement -->
<script src="<?php echo $relative_path; ?>assets/js/students-print.js"></script>

<!-- Custom scripts for all pages -->
<script src="<?php echo $relative_path; ?>assets/js/scripts.js"></script>

<script>
    // Initialize AOS animations
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true
    });
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize DataTables with enhanced styling
    $(document).ready(function() {
        $('.data-table').each(function() {
            // Check if this table has already been initialized as a DataTable
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    language: {
                        search: "<i class='fas fa-search'></i> _INPUT_",
                        searchPlaceholder: "Search records...",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        infoEmpty: "Showing 0 to 0 of 0 entries",
                        infoFiltered: "(filtered from _MAX_ total entries)"
                    },
                    dom: '<"top"lf>rt<"bottom"ip>',
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    pageLength: 10,
                    initComplete: function() {
                        // Add custom styling to the DataTables elements
                        $('.dataTables_filter input').addClass('form-control form-control-sm');
                        $('.dataTables_length select').addClass('form-select form-select-sm');
                    }
                });
            }
        });
        
        // Close sidebar on small screens when clicking outside
        $(document).on('click', function(e) {
            if ($(window).width() < 768 && !$(e.target).closest('.sidebar, #sidebarToggle').length) {
                $('body').removeClass('sidebar-toggled');
                $('.sidebar').removeClass('toggled');
            }
        });
        
        // Prevent the sidebar from being collapsed on larger screens
        $(window).resize(function() {
            if ($(window).width() < 768) {
                $('.sidebar .collapse').collapse('hide');
            }
        });
        
        // Toggle dropdown menus on click
        $('.sidebar .dropdown').on('click', function(e) {
            $(this).find('.dropdown-menu').toggle();
            e.stopPropagation();
        });
        
        // Fix for dropdown navigation items
        $('.sidebar .nav-link[data-bs-toggle="collapse"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const target = $(this).data('bs-target');
            $(target).collapse('toggle');
        });
        
        // Prevent dropdown items from closing the dropdown when clicked
        $('.sidebar .collapse .nav-link').on('click', function(e) {
            e.stopPropagation();
        });
        
        // Fix for Bootstrap dropdown navigation
        $('.nav-link[data-bs-toggle="collapse"]').on('click', function(e) {
            // Prevent the default Bootstrap collapse behavior
            e.stopPropagation();
            
            // Get the target collapse element
            const collapseTarget = $($(this).data('bs-target'));
            
            // Toggle the collapse manually
            if (collapseTarget.hasClass('show')) {
                // If it's open, close it
                collapseTarget.removeClass('show');
            } else {
                // If it's closed, open it
                collapseTarget.addClass('show');
            }
        });
    });
    
    // Smooth scrolling for all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== "#" && href.startsWith('#')) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Dropdown animation
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(function (dropdownToggleEl) {
        dropdownToggleEl.addEventListener('show.bs.dropdown', function (e) {
            const dropdownMenu = e.target.nextElementSibling;
            if (dropdownMenu) {
                dropdownMenu.classList.add('animate__animated', 'animate__fadeIn', 'animate__faster');
            }
        });
    });
    
    // Form control focus effect
    document.querySelectorAll('.form-control, .form-select').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('input-focused');
        });
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('input-focused');
        });
    });
    
    // Custom confirm dialog using SweetAlert2
    window.customConfirm = function(message, callback) {
        Swal.fire({
            title: 'Confirmation',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4e73df',
            cancelButtonColor: '#e74a3b',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            if (result.isConfirmed) {
                callback(true);
            } else {
                callback(false);
            }
        });
    };
    
    // Custom alert using SweetAlert2
    window.customAlert = function(title, message, icon = 'info') {
        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            confirmButtonColor: '#4e73df'
        });
    };
</script>

<?php if (isset($extra_js)) echo $extra_js; ?>

</body>
</html> 