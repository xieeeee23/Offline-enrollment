# LocalEnroll Pro - K-12 Enrollment System

LocalEnroll Pro is a comprehensive offline enrollment management system designed specifically for K-12 schools. It provides a user-friendly interface for managing student enrollments, teacher records, class schedules, and generating various reports.

## Features

### User Management
- **Role-based Access Control**: Admin, Registrar, and Teacher roles with appropriate permissions
- **User Authentication**: Secure login system with password hashing
- **User Profiles**: Manage personal information and account settings

### Student Management
- **Student Registration**: Add new students with comprehensive details
- **Student Records**: Maintain complete student information
- **Enrollment Status**: Track active and inactive students
- **Grade Level Management**: Organize students by grade levels and sections

### Teacher Management
- **Teacher Registration**: Add new teachers with qualification details
- **Teacher Records**: Maintain complete teacher information
- **Department Organization**: Group teachers by departments
- **Status Management**: Track active and inactive teachers

### Schedule Management
- **Class Scheduling**: Create and manage class schedules
- **Teacher Assignment**: Assign teachers to specific classes
- **Schedule Conflicts**: Prevent scheduling conflicts
- **Schedule Viewing**: View schedules by teacher, grade level, or section

### Reporting System
- **Student Reports**: Generate student lists by various criteria
- **Teacher Reports**: Create teacher directories and assignments
- **Schedule Reports**: Print class schedules
- **PDF Export**: Export all reports to PDF format

### System Utilities
- **Activity Logs**: Track all system activities
- **Data Backup**: Export and import system data
- **System Settings**: Configure system parameters

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP
- **Database**: MySQL
- **Server**: XAMPP (Apache)
- **Libraries**: TCPDF (for PDF generation), DataTables, SweetAlert2

## Installation

### Prerequisites
- XAMPP (or equivalent with PHP 7.4+ and MySQL)
- Web browser (Chrome, Firefox, Edge recommended)

### Steps
1. **Install XAMPP**: Download and install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Clone Repository**: Clone or download this repository to your `htdocs` folder
   ```
   git clone https://github.com/yourusername/localenroll-pro.git
   ```
   or extract the downloaded ZIP file to `htdocs/localenroll-pro`
3. **Start Services**: Start Apache and MySQL services from XAMPP Control Panel
4. **Database Setup**:
   - Open your browser and navigate to `http://localhost/phpmyadmin`
   - Create a new database named `localenroll_db`
   - Import the database schema from `database/localenroll_db.sql`
5. **Configure Application**:
   - Open `includes/config.php`
   - Update database connection details if necessary
   - Set appropriate base URL and system name
6. **Access Application**:
   - Open your browser and navigate to `http://localhost/localenroll-pro`
   - Login using default credentials:
     - Admin: admin / admin123
     - Registrar: registrar1 / admin123
     - Teacher: teacher1 / admin123

## Usage

### Admin Role
- Manage users, students, teachers, and schedules
- Configure system settings
- View activity logs
- Generate all types of reports

### Registrar Role
- Manage student enrollments
- View and edit teacher information
- View class schedules
- Generate student and schedule reports

### Teacher Role
- View assigned class schedules
- View student information for assigned classes
- Update personal profile

## Directory Structure

```
localenroll-pro/
├── assets/
│   ├── css/         # CSS files
│   ├── js/          # JavaScript files
│   └── images/      # Image files
├── database/        # Database schema and sample data
├── includes/        # Core PHP includes
│   ├── config.php   # Configuration file
│   ├── functions.php # Helper functions
│   ├── header.php   # Common header
│   └── footer.php   # Common footer
├── modules/         # Feature modules
│   ├── admin/       # Admin-specific features
│   ├── registrar/   # Registrar-specific features
│   ├── teacher/     # Teacher-specific features
│   └── reports/     # Reporting system
├── index.php        # Entry point
├── login.php        # Login page
├── logout.php       # Logout handler
├── dashboard.php    # Dashboard
├── profile.php      # User profile
└── README.md        # This file
```

## Security Features

- Password hashing using PHP's password_hash()
- Input sanitization to prevent SQL injection
- Session-based authentication
- Role-based access control
- Activity logging for audit trails

## Customization

- **System Name**: Update `SYSTEM_NAME` in `includes/config.php`
- **Logo**: Replace logo image in `assets/images/`
- **Theme**: Modify CSS in `assets/css/custom.css`
- **School-specific Fields**: Add or modify fields in student and teacher forms

## Offline Functionality

LocalEnroll Pro is designed to work completely offline, making it ideal for schools with limited or no internet connectivity. All data is stored locally on the server, and no external resources are required after installation.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, bug reports, or feature requests, please open an issue on the GitHub repository or contact the development team at support@localenrollpro.com.

---

&copy; 2023 LocalEnroll Pro. All rights reserved. 