// Export functions

/**
 * Get school information from the page
 * @returns {Object} School information object with name, address, and logo
 */
function getSchoolInfo() {
    const schoolInfo = {
        name: 'THE KRISLIZZ INTERNATIONAL ACADEMY INC.',
        address1: 'School Address Line 1',
        address2: 'School Address Line 2',
        logoUrl: '../assets/images/logo.jpg'
    };
    
    // Try to get the logo from various places
    // First try the footer logo
    const footerLogo = document.querySelector('.footer-logo');
    if (footerLogo) {
        schoolInfo.logoUrl = footerLogo.getAttribute('src');
    }
    
    // Try the navbar logo if footer logo not found
    if (!schoolInfo.logoUrl || schoolInfo.logoUrl === 'null') {
        const navbarLogo = document.querySelector('.navbar-logo');
        if (navbarLogo) {
            schoolInfo.logoUrl = navbarLogo.getAttribute('src');
        }
    }
    
    // If still not found, try a direct path
    if (!schoolInfo.logoUrl || schoolInfo.logoUrl === 'null') {
        // Try to determine the relative path based on the current URL
        const currentPath = window.location.pathname;
        const pathSegments = currentPath.split('/');
        let basePath = '';
        
        if (pathSegments.length > 3) {
            // We're likely in a subdirectory
            for (let i = 0; i < pathSegments.length - 2; i++) {
                basePath += '../';
            }
        }
        
        schoolInfo.logoUrl = basePath + 'assets/images/logo.jpg';
    }
    
    // Try to get school name from footer
    const footerSchoolName = document.querySelector('.footer h6');
    if (footerSchoolName) {
        schoolInfo.name = footerSchoolName.textContent.trim();
        
        // Try to get address from footer
        const footerSchoolAddress = document.querySelector('.footer p.small');
        if (footerSchoolAddress) {
            schoolInfo.address1 = footerSchoolAddress.textContent.trim();
        }
    }
    
    return schoolInfo;
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
    
    // Get school info for header
    const schoolInfo = getSchoolInfo();
    
    // Create a new workbook
    const wb = XLSX.utils.book_new();
    
    // Convert table to worksheet
    const ws = XLSX.utils.table_to_sheet(tableClone);
    
    // Add header with school information (metadata)
    XLSX.utils.sheet_add_aoa(ws, [
        [schoolInfo.name],
        ['REPORT GENERATED: ' + new Date().toLocaleString()],
        ['']  // Empty row for spacing
    ], { origin: 'A1' });
    
    // Adjust column widths for better readability
    const columnWidths = [];
    const range = XLSX.utils.decode_range(ws['!ref']);
    
    // Calculate column widths based on content
    for (let C = range.s.c; C <= range.e.c; ++C) {
        let maxColWidth = 10; // Default minimum width
        
        for (let R = range.s.r; R <= range.e.r; ++R) {
            const cellAddress = XLSX.utils.encode_cell({r: R, c: C});
            if (ws[cellAddress] && ws[cellAddress].v) {
                const cellValue = String(ws[cellAddress].v);
                maxColWidth = Math.max(maxColWidth, cellValue.length * 1.1); // Add some padding
            }
        }
        
        // Cap width at 50 characters
        columnWidths[C] = Math.min(maxColWidth, 50);
    }
    
    // Apply column widths
    ws['!cols'] = columnWidths.map(width => ({ wch: width }));
    
    // Add the worksheet to the workbook
    XLSX.utils.book_append_sheet(wb, ws, "Report");
    
    // Save the file
    XLSX.writeFile(wb, filename);
}

/**
 * Print a specific table with enhanced styling and school branding
 * @param {string} tableID - The ID of the table to print
 * @param {string} title - Optional title for the printed page
 */
function printTable(tableID, title = '') {
    const table = document.getElementById(tableID);
    if (!table) {
        console.error('Table not found with ID:', tableID);
        return;
    }
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Get the page title from the current document if not provided
    if (!title) {
        const pageTitle = document.querySelector('h1');
        if (pageTitle) {
            title = pageTitle.textContent;
        } else {
            const cardTitle = document.querySelector('.card-title');
            if (cardTitle) {
                title = cardTitle.textContent;
            } else {
                title = 'Report';
            }
        }
    }
    
    // Get school information
    const schoolInfo = getSchoolInfo();
    
    // Get current date and time formatted nicely
    const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit' };
    const currentDate = new Date().toLocaleDateString('en-US', dateOptions);
    const currentTime = new Date().toLocaleTimeString('en-US', timeOptions);
    
    // Create the print content with enhanced styling
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>${title}</title>
            <style>
                @page {
                    size: portrait;
                    margin: 0.5in;
                }
                body {
                    font-family: 'Arial', 'Helvetica', sans-serif;
                    margin: 0;
                    padding: 0;
                    color: #333;
                    background-color: #fff;
                }
                .print-container {
                    max-width: 100%;
                    margin: 0 auto;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    padding-bottom: 15px;
                    border-bottom: 3px double #333;
                    position: relative;
                }
                .school-info {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 15px;
                }
                .school-logo-container {
                    width: 100px;
                    height: 100px;
                    margin-right: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .school-logo {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: contain;
                }
                .school-details {
                    text-align: center;
                }
                .school-name {
                    font-size: 24pt;
                    font-weight: bold;
                    margin-bottom: 5px;
                    color: #003366;
                    text-transform: uppercase;
                }
                .school-address {
                    font-size: 10pt;
                    margin-bottom: 2px;
                    color: #555;
                }
                .school-slogan {
                    font-size: 11pt;
                    font-style: italic;
                    margin-top: 5px;
                    color: #666;
                }
                .print-title {
                    margin: 15px 0 5px;
                    font-size: 18pt;
                    font-weight: bold;
                    text-transform: uppercase;
                    color: #003366;
                    border-bottom: 1px solid #ccc;
                    padding-bottom: 5px;
                }
                .print-subtitle {
                    margin: 5px 0 15px;
                    font-size: 12pt;
                    color: #555;
                }
                .print-footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 10px;
                    border-top: 1px solid #ddd;
                    font-size: 9pt;
                    color: #666;
                    position: relative;
                    page-break-inside: avoid;
                }
                .watermark {
                    position: fixed;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%) rotate(-45deg);
                    opacity: 0.07;
                    font-size: 120px;
                    font-weight: bold;
                    color: #003366;
                    z-index: -1;
                    pointer-events: none;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                    page-break-inside: auto;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                thead {
                    display: table-header-group;
                }
                tfoot {
                    display: table-footer-group;
                }
                table, th, td {
                    border: 1px solid #ddd;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    font-size: 10pt;
                }
                th {
                    background-color: #4e73df;
                    color: white;
                    font-weight: bold;
                    text-transform: uppercase;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                tr:hover {
                    background-color: #f1f1f1;
                }
                .no-print {
                    display: none;
                }
                .table-info {
                    margin-bottom: 15px;
                    font-size: 10pt;
                    background-color: #f8f9fa;
                    padding: 10px;
                    border-left: 4px solid #4e73df;
                }
                .page-number:before {
                    content: "Page " counter(page);
                }
                .summary-section {
                    margin-top: 25px;
                    font-size: 11pt;
                    background-color: #f8f9fa;
                    padding: 15px;
                    border-radius: 5px;
                    border-left: 4px solid #4e73df;
                }
                .signature-section {
                    margin-top: 50px;
                    display: flex;
                    justify-content: space-between;
                    page-break-inside: avoid;
                }
                .signature-box {
                    width: 45%;
                }
                .signature-line {
                    border-top: 1px solid #333;
                    margin-top: 40px;
                    padding-top: 5px;
                    text-align: center;
                    font-weight: bold;
                }
                .signature-title {
                    text-align: center;
                    font-size: 9pt;
                    color: #666;
                }
                .print-date {
                    position: absolute;
                    right: 0;
                    top: 0;
                    font-size: 9pt;
                    color: #666;
                    text-align: right;
                }
                .print-meta {
                    display: flex;
                    justify-content: space-between;
                    font-size: 9pt;
                    color: #666;
                    margin-bottom: 15px;
                }
                .print-meta-item {
                    display: flex;
                    align-items: center;
                }
                .print-meta-icon {
                    margin-right: 5px;
                    font-weight: bold;
                }
                .decorative-border {
                    height: 5px;
                    background: linear-gradient(to right, #003366, #4e73df, #003366);
                    margin: 10px 0 20px;
                    border-radius: 2px;
                }
                @media print {
                    thead {
                        display: table-header-group;
                    }
                    tfoot {
                        display: table-footer-group;
                    }
                    button {
                        display: none;
                    }
                    body {
                        margin: 0;
                    }
                    .decorative-border {
                        background: #003366;
                        height: 3px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-container">
                <div class="watermark">KLIA</div>
                <div class="print-header">
                    <div class="print-date">
                        ${currentDate}<br>${currentTime}
                    </div>
                    <div class="school-info">
    `;
    
    // Add school logo if available
    if (schoolInfo.logoUrl) {
        printContent += `
                        <div class="school-logo-container">
                            <img src="${schoolInfo.logoUrl}" class="school-logo" alt="School Logo">
                        </div>
                        <div class="school-details">
        `;
    } else {
        printContent += `
                        <div class="school-details" style="text-align: center; width: 100%;">
        `;
    }
    
    // Add school name and address
    printContent += `
                            <div class="school-name">${schoolInfo.name}</div>
                            <div class="school-address">${schoolInfo.address1}</div>
                            <div class="school-address">${schoolInfo.address2}</div>
                            <div class="school-slogan">Quality Education is our COMMITMENT</div>
                        </div>
                    </div>
                    <div class="decorative-border"></div>
                    <div class="print-title">${title}</div>
                    <div class="print-subtitle">Generated on ${currentDate} at ${currentTime}</div>
                </div>
                
                <div class="print-meta">
                    <div class="print-meta-item">
                        <span class="print-meta-icon">📄</span> Report ID: REP-${Math.floor(Math.random() * 10000).toString().padStart(4, '0')}
                    </div>
                    <div class="print-meta-item">
                        <span class="print-meta-icon">👤</span> Generated by: ${document.querySelector('.dropdown-toggle') ? document.querySelector('.dropdown-toggle').textContent.trim() : 'System User'}
                    </div>
                </div>
    `;
    
    // Add filter information if available
    const filterInfo = document.querySelector('.card-footer');
    if (filterInfo) {
        printContent += `
                <div class="table-info">
                    ${filterInfo.textContent}
                </div>
        `;
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
    
    // Modify the table to repeat header on each page
    const thead = tableClone.querySelector('thead');
    if (thead) {
        // Make sure thead will repeat on each page
        thead.style.display = 'table-header-group';
    }
    
    // Add the table to the print content
    printContent += tableClone.outerHTML;
    
    // Add summary section
    const rowCount = tableClone.querySelectorAll('tbody tr').length;
    printContent += `
                <div class="summary-section">
                    <p><strong>Summary:</strong> This report contains ${rowCount} record${rowCount !== 1 ? 's' : ''}.</p>
                </div>
                
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="signature-line">Prepared by</div>
                        <div class="signature-title">Registrar</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-line">Approved by</div>
                        <div class="signature-title">School Principal</div>
                    </div>
                </div>
                
                <div class="print-footer">
                    <div>THE KRISLIZZ INTERNATIONAL ACADEMY INC. - ENROLLMENT SYSTEM</div>
                    <div>© ${new Date().getFullYear()} All Rights Reserved</div>
                </div>
            </div>
            
            <script>
                window.onload = function() {
                    window.print();
                    window.onafterprint = function() {
                        window.close();
                    };
                }
            </script>
        </body>
        </html>
    `;
    
    // Write to the new window and trigger print
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}
