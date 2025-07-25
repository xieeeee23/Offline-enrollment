// Export to Excel function

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

    // Check if table is a DataTable
    let dataTable;
    let tableData;
    let headers = [];
    
    if ($.fn.DataTable.isDataTable('#' + tableID)) {
        dataTable = $('#' + tableID).DataTable();
        
        // Get visible column headers (excluding Actions column)
        $('#' + tableID + ' thead th').each(function(index) {
            if ($(this).text().trim().toLowerCase() !== 'actions') {
                headers.push($(this).text().trim());
            }
        });
        
        // Get filtered and visible data from DataTable
        tableData = [];
        dataTable.rows({search: 'applied'}).every(function() {
            const rowData = this.data();
            const cleanedRow = [];
            
            // Process each cell in the row
            for (let i = 0; i < rowData.length; i++) {
                // Skip the Actions column
                if ($('#' + tableID + ' thead th').eq(i).text().trim().toLowerCase() !== 'actions') {
                    // Clean HTML from cell data
                    const cellData = rowData[i];
                    const div = document.createElement('div');
                    div.innerHTML = cellData;
                    cleanedRow.push(div.textContent.trim());
                }
            }
            
            tableData.push(cleanedRow);
        });
    } else {
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
        
        // Use the modified table
        return exportTableToExcelLegacy(tableClone, filename);
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
    
    // Create workbook with the filtered data
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet([headers].concat(tableData));
    
    // Add some styling
    const range = XLSX.utils.decode_range(ws['!ref']);
    for (let C = range.s.c; C <= range.e.c; ++C) {
        const address = XLSX.utils.encode_col(C) + '1';
        if (!ws[address]) continue;
        ws[address].s = {
            font: { bold: true },
            fill: { fgColor: { rgb: "EFEFEF" } }
        };
    }
    
    XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
    
    // Save the file
    XLSX.writeFile(wb, filename);
}

/**
 * Legacy export function for non-DataTable tables
 */
function exportTableToExcelLegacy(table, filename = '') {
    // Generate filename if not provided
    if (!filename) {
        const now = new Date();
        filename = 'export_' + now.getFullYear() + '-' + 
                  String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                  String(now.getDate()).padStart(2, '0') + '.xlsx';
    } else if (!filename.endsWith('.xlsx')) {
        filename += '.xlsx';
    }
    
    // Convert to XLSX format
    const ws = XLSX.utils.table_to_sheet(table);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
    
    // Save the file
    XLSX.writeFile(wb, filename);
}
