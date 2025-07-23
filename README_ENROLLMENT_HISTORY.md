# Enrollment History Table Fix

This document provides information about the fix for the error: "Table 'shs_enrollment.enrollment_history' doesn't exist".

## The Issue

The system was trying to query the `enrollment_history` table which didn't exist in the database. This was causing a fatal error in the student_print.php file.

## The Fix

We've implemented the following fixes:

1. Modified the `student_print.php` file to check if the `enrollment_history` table exists before trying to query it
2. Added similar checks for other tables like `requirements`, `requirement_types`, `irregular_students`, and `subjects`
3. Created SQL and PHP scripts to manually create the missing tables if needed

## How to Use

The system should now work automatically. When you access the student profile page, it will:
1. Check if the required tables exist
2. Skip queries for tables that don't exist
3. Show student data with available information

## Manual Table Creation

If you want to create the `enrollment_history` table to store student enrollment history, you can use one of these methods:

### Method 1: Run the PHP Script

Navigate to `http://localhost/Offline%20enrollment/database/create_enrollment_history_table.php` in your browser.

### Method 2: Run the SQL Script

1. Open phpMyAdmin
2. Select the `shs_enrollment` database
3. Go to the SQL tab
4. Copy and paste the contents of `database/create_enrollment_history_table.sql`
5. Click "Go" to execute the SQL

## Table Structure

The `enrollment_history` table has the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) | Primary key |
| student_id | int(11) | Foreign key to students table |
| school_year | varchar(20) | School year |
| semester | enum('First','Second') | Semester |
| grade_level | varchar(20) | Grade level |
| section | varchar(50) | Section |
| strand | varchar(20) | Strand code |
| status | varchar(20) | Enrollment status |
| date_enrolled | date | Date when enrolled |
| enrolled_by | int(11) | User ID who enrolled the student |
| date_created | timestamp | Date when the record was added |

## Support

If you continue to experience issues, please contact the system administrator. 