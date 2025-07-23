# Reports Module

This module provides comprehensive reporting functionality for the LocalEnroll Pro K-12 Enrollment System.

## Available Reports

### Student Reports
- **Student List Report**: View and export a complete list of students with filtering by grade level, section, and enrollment status.
- **Enrolled Students**: View only currently enrolled students.
- **Pending Enrollment**: View students with pending enrollment status.

### Teacher Reports
- **Teacher List Report**: View and export a complete list of teachers with filtering by department and status.
- **Active Teachers**: View only active teachers.

### Schedule Reports
- **Class Schedule Report**: View and export class schedules with filtering by grade level, section, teacher, and day.
- **Daily Schedules**: View schedules for specific days of the week.

## PDF Export Functionality

All reports can be exported to PDF format for printing or sharing. To enable PDF functionality:

1. Download TCPDF from [https://github.com/tecnickcom/TCPDF](https://github.com/tecnickcom/TCPDF)
2. Extract the contents to the `includes/tcpdf/` directory
3. Ensure the main TCPDF file (`tcpdf.php`) is directly in the `includes/tcpdf/` directory

If TCPDF is not installed, the system will display a warning message with installation instructions.

## User Access

- **Admin**: Full access to all reports
- **Registrar**: Full access to all reports
- **Teacher**: Access to schedule reports only

## Filtering Options

Most reports include filtering options to narrow down the data displayed:

- Filter students by grade level, section, and enrollment status
- Filter teachers by department and status
- Filter schedules by grade level, section, teacher, and day

## Usage

1. Navigate to the Reports module from the main dashboard
2. Select the desired report type
3. Apply filters if needed
4. View the report on screen or export to PDF
5. Print or save the PDF as needed 