/**
 * Students Print Functionality
 * Provides enhanced print output for student lists with visual styling
 */

// Print student list with enhanced visual styling
function printStudentList() {
    // Get the current filter values to include in the print output
    const search = document.getElementById('search')?.value || '';
    const grade = document.getElementById('grade')?.value || 'All Grades';
    const strand = document.getElementById('strand')?.value || 'All Strands';
    const section = document.getElementById('section')?.value || 'All Sections';
    const status = document.getElementById('status')?.value || 'All Status';

    // Get the table data
    const table = document.getElementById('dataTable');
    if (!table) return;
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Get the school logo URL - try different paths
    const currentPath = window.location.pathname;
    const pathSegments = currentPath.split('/');
    let logoPath = '';
    
    if (pathSegments.length > 3) {
        // We're likely in a subdirectory
        for (let i = 0; i < pathSegments.length - 2; i++) {
            logoPath += '../';
        }
    }
    
    const logoUrl = logoPath + 'assets/images/logo.jpg';
    
    // Get current date for the report
    const today = new Date();
    const dateStr = today.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Create the print content with enhanced styling
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Student List - ${dateStr}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 5px;
                    color: #333;
                    font-size: 8pt;
                }
                .header {
                    text-align: center;
                    margin-bottom: 5px;
                    position: relative;
                    padding-bottom: 5px;
                    border-bottom: 1px double #4e73df;
                }
                .header:after {
                    content: '';
                    display: block;
                    height: 1px;
                    width: 100%;
                    background: linear-gradient(to right, #003366, #4e73df, #003366);
                    position: absolute;
                    bottom: -1px;
                    left: 0;
                }
                .logo {
                    width: 40px;
                    height: auto;
                    margin-right: 5px;
                }
                .school-info {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 5px;
                }
                .school-details {
                    text-align: center;
                }
                .school-name {
                    font-size: 12pt;
                    font-weight: bold;
                    margin: 0;
                    color: #003366;
                    text-transform: uppercase;
                }
                .report-title {
                    font-size: 10pt;
                    margin: 3px 0 2px;
                    color: #4e73df;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                .report-date {
                    font-size: 8pt;
                    color: #666;
                    margin: 2px 0;
                }
                .filter-info {
                    margin: 5px 0;
                    padding: 3px;
                    background-color: #f8f9fa;
                    border-radius: 2px;
                    font-size: 7pt;
                    border-left: 2px solid #4e73df;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 3px;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
                    font-size: 7pt;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 2px 3px;
                    text-align: left;
                    font-size: 7pt;
                    line-height: 1.1;
                }
                th {
                    background-color: #4e73df;
                    color: white;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 7pt;
                    padding: 2px 3px;
                }
                tr:nth-child(even) {
                    background-color: #f2f2f2;
                }
                tr:hover {
                    background-color: #e9ecef;
                }
                .status {
                    padding: 2px 3px;
                    border-radius: 8px;
                    font-weight: bold;
                    display: inline-block;
                    min-width: 50px;
                    text-align: center;
                    font-size: 7pt;
                }
                .enrolled {
                    background-color: #28a745;
                    color: white;
                }
                .pending {
                    background-color: #ffc107;
                    color: #212529;
                }
                .withdrawn {
                    background-color: #dc3545;
                    color: white;
                }
                .footer {
                    margin-top: 10px;
                    text-align: center;
                    font-size: 7pt;
                    color: #6c757d;
                    border-top: 1px solid #ddd;
                    padding-top: 3px;
                }
                .signature-section {
                    margin-top: 10px;
                    display: flex;
                    justify-content: space-between;
                }
                .signature-box {
                    width: 28%;
                    text-align: center;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    margin-top: 10px;
                    padding-top: 1px;
                    font-size: 7pt;
                }
                .watermark {
                    position: fixed;
                    opacity: 0.02;
                    z-index: -1;
                    transform: rotate(-45deg);
                    font-size: 80px;
                    width: 100%;
                    text-align: center;
                    top: 50%;
                }
                .stats-container {
                    margin: 3px 0;
                    display: flex;
                    justify-content: space-between;
                }
                .stat-box {
                    background-color: #f8f9fa;
                    border-radius: 3px;
                    padding: 3px;
                    width: 22%;
                    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .stat-number {
                    font-size: 12pt;
                    font-weight: bold;
                    color: #4e73df;
                    margin: 0;
                }
                .stat-label {
                    font-size: 7pt;
                    color: #6c757d;
                    margin: 0;
                }
                .decorative-border {
                    height: 3px;
                    background: linear-gradient(to right, #003366, #4e73df, #003366);
                    margin: 5px 0 10px;
                    border-radius: 1px;
                }
                @media print {
                    .header {
                        position: fixed;
                        top: 0;
                        width: 100%;
                        background: white;
                    }
                    table { page-break-inside: auto }
                    tr { page-break-inside: avoid; page-break-after: auto }
                    thead { display: table-header-group }
                    tfoot { display: table-footer-group }
                    .decorative-border {
                        background: #003366;
                        height: 2px;
                    }
                    @page {
                        margin: 0.2cm;
                        size: landscape;
                    }
                }
            </style>
        </head>
        <body>
            <div class="watermark">KLIA</div>
            <div class="header">
                <div class="school-info">
                    <img src="${logoUrl}" class="logo" alt="School Logo">
                    <div class="school-details">
                        <h1 class="school-name">THE KRISLIZZ INTERNATIONAL ACADEMY INC.</h1>
                        <p style="margin: 2px 0; color: #555; font-size: 8pt;">School Address Line 1, School Address Line 2</p>
                    </div>
                </div>
                <div class="decorative-border"></div>
                <p class="report-title">STUDENT LIST REPORT</p>
                <p class="report-date">Generated on: ${dateStr}</p>
            </div>
            
            <div class="filter-info">
                <strong>Filters:</strong> 
                ${search ? `Search: "${search}" | ` : ''}
                Grade Level: ${grade} | 
                Strand: ${strand} | 
                Section: ${section} | 
                Status: ${status}
            </div>
    `;
    
    // Calculate statistics
    const rows = table.querySelectorAll('tbody tr');
    let totalStudents = rows.length;
    let enrolledCount = 0;
    let pendingCount = 0;
    let withdrawnCount = 0;
    
    rows.forEach(row => {
        const statusCell = row.querySelector('td:nth-child(6)');
        const statusText = statusCell?.textContent.trim().toLowerCase() || '';
        
        if (statusText.includes('enrolled')) enrolledCount++;
        else if (statusText.includes('pending')) pendingCount++;
        else if (statusText.includes('withdrawn')) withdrawnCount++;
    });
    
    // Add statistics section
    printContent += `
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-number">${totalStudents}</div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">${enrolledCount}</div>
                <div class="stat-label">Enrolled</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">${pendingCount}</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">${withdrawnCount}</div>
                <div class="stat-label">Withdrawn</div>
            </div>
        </div>
    `;
    
    // Clone the table and remove action buttons
    const tableClone = table.cloneNode(true);
    const actionCells = tableClone.querySelectorAll('td:last-child');
    actionCells.forEach(cell => {
        cell.remove();
    });
    
    // Remove the Actions header
    const headerRow = tableClone.querySelector('thead tr');
    if (headerRow && headerRow.lastElementChild) {
        headerRow.lastElementChild.remove();
    }
    
    // Format the status cells with colored badges
    const statusCells = tableClone.querySelectorAll('td:nth-child(6)');
    statusCells.forEach(cell => {
        const status = cell.textContent.trim().toLowerCase();
        cell.innerHTML = `<span class="status ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    });
    
    // Add the table to the print content
    printContent += tableClone.outerHTML;
    
    // Add signature section
    printContent += `
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Prepared by</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Verified by</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Approved by</div>
            </div>
        </div>
        
        <div class="footer">
            <p>© ${today.getFullYear()} THE KRISLIZZ INTERNATIONAL ACADEMY INC. All Rights Reserved.</p>
        </div>
        
        <script>
            window.onload = function() {
                window.print();
                setTimeout(function() {
                    window.close();
                }, 500);
            };
        </script>
        </body>
        </html>
    `;
    
    // Write to the new window and print
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}

/**
 * Export consolidated student report to Excel
 */
function exportConsolidatedReport() {
    // Get the current filter values
    const search = document.getElementById('search')?.value || '';
    const grade = document.getElementById('grade')?.value || '';
    const strand = document.getElementById('strand')?.value || '';
    const section = document.getElementById('section')?.value || '';
    const status = document.getElementById('status')?.value || '';
    
    // Redirect to the consolidated export endpoint with current filters
    let url = window.location.pathname + '?export=consolidated';
    
    if (search) url += '&search=' + encodeURIComponent(search);
    if (grade) url += '&grade=' + encodeURIComponent(grade);
    if (strand) url += '&strand=' + encodeURIComponent(strand);
    if (section) url += '&section=' + encodeURIComponent(section);
    if (status) url += '&status=' + encodeURIComponent(status);
    
    window.location.href = url;
} 