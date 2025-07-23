# Back Subjects Table Fix

This document provides information about the fix for the error: "Table 'shs_enrollment.back_subjects' doesn't exist".

## The Issue

The system was trying to query the `back_subjects` table which didn't exist in the database. This was causing a fatal error in the students.php file.

## The Fix

We've implemented the following fixes:

1. Modified the `students.php` file to check if the `back_subjects` table exists before trying to query it
2. Added automatic table creation functionality that will create the table if it doesn't exist
3. Created SQL and PHP scripts to manually create the table if needed

## How to Use

The system should now work automatically. When you access the students page, it will:
1. Check if the `back_subjects` table exists
2. Create it if it doesn't exist
3. Show student data with back subjects information if available

## Manual Table Creation

If you still encounter issues, you can manually create the `back_subjects` table using one of these methods:

### Method 1: Run the PHP Script

Navigate to `http://localhost/Offline%20enrollment/database/create_back_subjects_table.php` in your browser.

### Method 2: Run the SQL Script

1. Open phpMyAdmin
2. Select the `shs_enrollment` database
3. Go to the SQL tab
4. Copy and paste the contents of `database/create_back_subjects_table.sql`
5. Click "Go" to execute the SQL

## Table Structure

The `back_subjects` table has the following structure:

| Column | Type | Description |
|--------|------|-------------|
| id | int(11) | Primary key |
| student_id | int(11) | Foreign key to students table |
| subject_id | int(11) | ID of the subject |
| school_year | varchar(20) | School year |
| semester | enum('First','Second') | Semester |
| status | enum('pending','completed') | Status of the back subject |
| date_added | timestamp | Date when the record was added |
| date_completed | timestamp | Date when the back subject was completed |

## Support

If you continue to experience issues, please contact the system administrator. 