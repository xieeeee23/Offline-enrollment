<?php
require_once 'config.php';

/**
 * Get database connection
 * 
 * @return mysqli Database connection object
 */
function getConnection() {
    global $conn;
    return $conn;
}

/**
 * Log user action to database
 * 
 * @param int $user_id The ID of the user performing the action
 * @param string $action The action being performed (e.g., LOGIN, ENROLL)
 * @param string $description Description of the action
 * @return bool True on success, false on failure
 */
function logAction($user_id, $action, $description) {
    global $conn;
    $user_id = (int) $user_id;
    $action = mysqli_real_escape_string($conn, $action);
    $description = mysqli_real_escape_string($conn, $description);
    
    $query = "INSERT INTO logs (user_id, action, description) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "iss", $user_id, $action, $description);
    return mysqli_stmt_execute($stmt);
}

/**
 * Check if user is logged in and has required role
 * 
 * @param array $allowed_roles Array of roles allowed to access the page
 * @return bool True if user has access, false otherwise
 */
function checkAccess($allowed_roles = []) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // If no specific roles are required, just check if logged in
    if (empty($allowed_roles)) {
        return true;
    }
    
    // Check if user's role is in the allowed roles
    return in_array($_SESSION['role'], $allowed_roles);
}

/**
 * Redirect to a specified page
 * 
 * @param string $page The page to redirect to
 */
function redirect($url) {
    // Check if URL is absolute or relative
    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        // Absolute URL, use as is
        header("Location: $url");
    } else {
        // Relative URL, determine base path
        $script_name = $_SERVER['SCRIPT_NAME'];
        $script_dir = dirname($script_name);
        
        // If we're in a subdirectory of the project
        if (strpos($script_dir, '/modules/') !== false) {
            // We're in a module subdirectory, go back to root
            $base_path = substr($script_dir, 0, strpos($script_dir, '/modules/'));
        } else {
            // We're already at root
            $base_path = $script_dir;
        }
        
        // Ensure base path ends with a slash
        if (substr($base_path, -1) !== '/') {
            $base_path .= '/';
        }
        
        // Remove leading slash from URL if present
        if (substr($url, 0, 1) === '/') {
            $url = substr($url, 1);
        }
        
        // Build the full URL
        $full_url = $base_path . $url;
        
        // Redirect
        header("Location: $full_url");
    }
    exit;
}

/**
 * Get user information by ID
 * 
 * @param int $user_id The ID of the user
 * @return array|null User data as associative array or null if not found
 */
function getUserById($user_id) {
    global $conn;
    $user_id = (int) $user_id;
    
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Get teacher information by user ID
 * 
 * @param int $user_id The ID of the user
 * @return array|null Teacher data as associative array or null if not found
 */
function getTeacherByUserId($user_id) {
    global $conn;
    $user_id = (int) $user_id;
    
    $query = "SELECT * FROM teachers WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

/**
 * Display alert message
 * 
 * @param string $message The message to display
 * @param string $type The type of alert (success, danger, warning, info)
 * @return string HTML for the alert
 */
function showAlert($message, $type = 'info') {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Format date for display
 * 
 * @param string $date The date to format
 * @param string $format The format to use
 * @return string Formatted date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Clean input data
 * 
 * @param string $data The data to clean
 * @param string $type The type of data (for standardization)
 * @return string Cleaned data
 */
function cleanInput($data, $type = 'text') {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    
    // Apply standardization based on type
    return standardizeInput($data, $type);
}

/**
 * Check if a value exists in the database
 * 
 * @param string $table The table to check
 * @param string $column The column to check
 * @param string $value The value to check for
 * @param int $exclude_id ID to exclude from the check (for updates)
 * @return bool True if value exists, false otherwise
 */
function valueExists($table, $column, $value, $exclude_id = null) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $value = mysqli_real_escape_string($conn, $value);
    
    $query = "SELECT * FROM $table WHERE $column = ?";
    $params = [$value];
    $types = "s";
    
    if ($exclude_id !== null) {
        $query .= " AND id != ?";
        $params[] = (int) $exclude_id;
        $types .= "i";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($result) > 0;
}

/**
 * Check if the current page matches a given page name
 * 
 * @param string $page_name The page name to check
 * @return bool True if current page matches, false otherwise
 */
function isActivePage($page_name) {
    // Get the current script name
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Check for direct match
    if ($current_page === $page_name) {
        return true;
    }
    
    // Check for module page matches (modules in URL)
    if (strpos($_SERVER['PHP_SELF'], '/modules/') !== false && strpos($page_name, '.php') !== false) {
        $page_without_ext = basename($page_name, '.php');
        if (strpos($_SERVER['PHP_SELF'], '/' . $page_without_ext . '.php') !== false) {
            return true;
        }
    }
    
    // Check for redirect.php with specific page parameter
    if ($current_page === 'redirect.php' && isset($_GET['page'])) {
        $page_without_ext = basename($page_name, '.php');
        if ($_GET['page'] === $page_without_ext) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has access to a specific module
 * 
 * @param string $module The module to check access for
 * @return bool True if user has access, false otherwise
 */
function hasAccess($module) {
    // If user is not logged in, they don't have access
    if (!isLoggedIn()) {
        return false;
    }
    
    // If user is admin, they have access to all modules
    if ($_SESSION['role'] == 'admin') {
        return true;
    }
    
    // Check if the user's role allows access to this module
    // Add module-specific access checks here
    switch($module) {
        case 'registrar':
            return in_array($_SESSION['role'], ['admin', 'registrar']);
        case 'finance':
            return in_array($_SESSION['role'], ['admin', 'finance', 'accountant']);
        case 'faculty':
            return in_array($_SESSION['role'], ['admin', 'teacher']);
        case 'admin':
            return $_SESSION['role'] == 'admin';
        default:
            return false;
    }
}

/**
 * Check if a record with multiple field values exists in the database
 * 
 * @param string $table The table to check
 * @param array $fields Associative array of field names and values to check
 * @param int $exclude_id ID to exclude from the check (for updates)
 * @return bool True if record exists, false otherwise
 */
function recordExists($table, $fields, $exclude_id = null) {
    global $conn;
    $table = mysqli_real_escape_string($conn, $table);
    
    $conditions = [];
    $params = [];
    $types = "";
    
    foreach ($fields as $field => $value) {
        $field = mysqli_real_escape_string($conn, $field);
        $conditions[] = "$field = ?";
        $params[] = $value;
        $types .= "s"; // Assuming all values are strings, adjust if needed
    }
    
    $query = "SELECT * FROM $table WHERE " . implode(" AND ", $conditions);
    
    if ($exclude_id !== null) {
        $query .= " AND id != ?";
        $params[] = (int) $exclude_id;
        $types .= "i";
    }
    
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        return mysqli_num_rows($result) > 0;
    }
    
    return false;
}

/**
 * Check for duplicate records and return a friendly message
 * 
 * @param string $table The table to check
 * @param array $fields Associative array of field names and values to check
 * @param string $entity_name The name of the entity (e.g., "student", "teacher")
 * @param int $exclude_id ID to exclude from the check (for updates)
 * @return string|null Error message if duplicate found, null otherwise
 */
function checkDuplicate($table, $fields, $entity_name, $exclude_id = null) {
    if (recordExists($table, $fields, $exclude_id)) {
        $field_desc = [];
        foreach ($fields as $field => $value) {
            if (!empty($value)) {
                $field_desc[] = "$field: $value";
            }
        }
        
        $message = "Duplicate record detected: A $entity_name with " . implode(", ", $field_desc) . " already exists in the system.";
        return $message;
    }
    
    return null;
}

/**
 * Create default profile image if it doesn't exist
 * 
 * @return bool True if default image exists or was created successfully, false otherwise
 */
function ensureDefaultProfileImage() {
    $default_image_path = 'assets/images/default-user.png';
    
    // Check if default image already exists
    if (file_exists($default_image_path)) {
        return true;
    }
    
    // Create a simple default profile image
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);
    
    // Set background color (light gray)
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    imagefill($image, 0, 0, $bg_color);
    
    // Set circle color (darker gray)
    $circle_color = imagecolorallocate($image, 200, 200, 200);
    
    // Draw head circle
    imagefilledellipse($image, $width/2, $height/2 - 15, 120, 120, $circle_color);
    
    // Draw body
    imagefilledellipse($image, $width/2, $height + 20, 180, 200, $circle_color);
    
    // Save the image
    return imagepng($image, $default_image_path) && imagedestroy($image);
}

/**
 * Enhanced duplicate record checking system
 * 
 * This function checks for duplicate records in a table based on the provided fields
 * and returns detailed information about the duplicate if found.
 * 
 * @param string $table The table to check
 * @param array $fields Associative array of field names and values to check
 * @param array $options Additional options for the check (unique_fields, exclude_id, entity_name)
 * @return array|null Array with duplicate information if found, null otherwise
 */
function checkDuplicateRecord($table, $fields, $options = []) {
    global $conn;
    
    // Default options
    $default_options = [
        'unique_fields' => array_keys($fields), // Fields to check for uniqueness
        'exclude_id' => null,                  // ID to exclude from check (for updates)
        'entity_name' => 'record',             // Name of the entity for error messages
        'return_record' => false,              // Whether to return the duplicate record
        'case_sensitive' => true               // Whether to perform case-sensitive comparison
    ];
    
    // Merge provided options with defaults
    $options = array_merge($default_options, $options);
    
    // Sanitize table name
    $table = mysqli_real_escape_string($conn, $table);
    
    // Build query conditions based on unique fields
    $conditions = [];
    $params = [];
    $types = "";
    
    foreach ($options['unique_fields'] as $field) {
        if (isset($fields[$field]) && $fields[$field] !== '') {
            $field = mysqli_real_escape_string($conn, $field);
            
            if ($options['case_sensitive']) {
                $conditions[] = "$field = ?";
            } else {
                $conditions[] = "LOWER($field) = LOWER(?)";
            }
            
            $params[] = $fields[$field];
            $types .= "s"; // Assuming all values are strings, adjust if needed
        }
    }
    
    // If no conditions, no need to check
    if (empty($conditions)) {
        return null;
    }
    
    $query = "SELECT * FROM $table WHERE " . implode(" AND ", $conditions);
    
    // Exclude the current record if updating
    if ($options['exclude_id'] !== null) {
        $query .= " AND id != ?";
        $params[] = (int) $options['exclude_id'];
        $types .= "i";
    }
    
    // Prepare and execute the query
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Duplicate found
            $duplicate_record = mysqli_fetch_assoc($result);
            
            // Build field descriptions for error message
            $field_desc = [];
            foreach ($options['unique_fields'] as $field) {
                if (isset($fields[$field]) && $fields[$field] !== '') {
                    $field_desc[] = "$field: " . htmlspecialchars($fields[$field]);
                }
            }
            
            $message = "Duplicate " . $options['entity_name'] . " detected with " . implode(", ", $field_desc);
            
            $response = [
                'duplicate' => true,
                'message' => $message,
                'fields' => $options['unique_fields'],
                'values' => array_intersect_key($fields, array_flip($options['unique_fields']))
            ];
            
            if ($options['return_record']) {
                $response['record'] = $duplicate_record;
            }
            
            return $response;
        }
    }
    
    return null;
}

/**
 * Validates input data against common validation rules
 * 
 * @param array $data The data to validate
 * @param array $rules The validation rules
 * @return array Array of validation errors, empty if no errors
 */
function validateData($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $field_rules) {
        $value = isset($data[$field]) ? $data[$field] : null;
        
        foreach ($field_rules as $rule => $rule_value) {
            switch ($rule) {
                case 'required':
                    if ($rule_value && (is_null($value) || $value === '')) {
                        $errors[$field][] = ucfirst($field) . ' is required.';
                    }
                    break;
                    
                case 'min_length':
                    if (!is_null($value) && strlen($value) < $rule_value) {
                        $errors[$field][] = ucfirst($field) . ' must be at least ' . $rule_value . ' characters.';
                    }
                    break;
                    
                case 'max_length':
                    if (!is_null($value) && strlen($value) > $rule_value) {
                        $errors[$field][] = ucfirst($field) . ' must not exceed ' . $rule_value . ' characters.';
                    }
                    break;
                    
                case 'email':
                    if ($rule_value && !is_null($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = 'Please enter a valid email address.';
                    }
                    break;
                    
                case 'numeric':
                    if ($rule_value && !is_null($value) && !is_numeric($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be a number.';
                    }
                    break;
                    
                case 'date':
                    if ($rule_value && !is_null($value)) {
                        $date = date_create_from_format('Y-m-d', $value);
                        if (!$date || date_format($date, 'Y-m-d') !== $value) {
                            $errors[$field][] = 'Please enter a valid date in YYYY-MM-DD format.';
                        }
                    }
                    break;
                    
                case 'in':
                    if (!is_null($value) && !in_array($value, $rule_value)) {
                        $errors[$field][] = ucfirst($field) . ' must be one of: ' . implode(', ', $rule_value) . '.';
                    }
                    break;
                    
                case 'unique':
                    if (!is_null($value) && $value !== '') {
                        $table = $rule_value['table'];
                        $column = $rule_value['column'];
                        $exclude_id = isset($rule_value['exclude_id']) ? $rule_value['exclude_id'] : null;
                        
                        if (valueExists($table, $column, $value, $exclude_id)) {
                            $errors[$field][] = ucfirst($field) . ' already exists in the system.';
                        }
                    }
                    break;
            }
        }
    }
    
    return $errors;
}

/**
 * Safe insert function that prevents duplicate records
 * 
 * @param string $table The table to insert into
 * @param array $data Associative array of column names and values
 * @param array $options Options for the insert (unique_fields, entity_name, etc.)
 * @return array Result of the operation with success status and message/ID
 */
function safeInsert($table, $data, $options = []) {
    global $conn;
    
    // Default options
    $default_options = [
        'unique_fields' => [],                  // Fields to check for uniqueness
        'entity_name' => 'record',              // Name of the entity for messages
        'log_action' => true,                   // Whether to log the action
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null // User ID for logging
    ];
    
    // Merge provided options with defaults
    $options = array_merge($default_options, $options);
    
    // If unique fields specified, check for duplicates
    if (!empty($options['unique_fields'])) {
        $duplicate_check = checkDuplicateRecord($table, $data, [
            'unique_fields' => $options['unique_fields'],
            'entity_name' => $options['entity_name']
        ]);
        
        if ($duplicate_check) {
            return [
                'success' => false,
                'message' => $duplicate_check['message'],
                'duplicate' => true,
                'duplicate_data' => $duplicate_check
            ];
        }
    }
    
    // Prepare column names and placeholders
    $columns = array_keys($data);
    $placeholders = array_fill(0, count($columns), '?');
    
    // Build the query
    $query = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
    
    // Prepare statement
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        // Determine types
        $types = '';
        $values = [];
        
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            $insert_id = mysqli_insert_id($conn);
            
            // Log the action if requested
            if ($options['log_action'] && $options['user_id']) {
                logAction(
                    $options['user_id'], 
                    'INSERT', 
                    "Added new {$options['entity_name']} with ID: $insert_id"
                );
            }
            
            return [
                'success' => true,
                'message' => ucfirst($options['entity_name']) . ' added successfully.',
                'insert_id' => $insert_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error adding ' . $options['entity_name'] . ': ' . mysqli_stmt_error($stmt)
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Error preparing statement: ' . mysqli_error($conn)
        ];
    }
}

/**
 * Safe update function that prevents duplicate records
 * 
 * @param string $table The table to update
 * @param array $data Associative array of column names and values
 * @param int $id The ID of the record to update
 * @param array $options Options for the update (unique_fields, entity_name, etc.)
 * @return array Result of the operation with success status and message
 */
function safeUpdate($table, $data, $id, $options = []) {
    global $conn;
    
    // Default options
    $default_options = [
        'unique_fields' => [],                  // Fields to check for uniqueness
        'entity_name' => 'record',              // Name of the entity for messages
        'id_column' => 'id',                    // Name of the ID column
        'log_action' => true,                   // Whether to log the action
        'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null // User ID for logging
    ];
    
    // Merge provided options with defaults
    $options = array_merge($default_options, $options);
    
    // If unique fields specified, check for duplicates
    if (!empty($options['unique_fields'])) {
        $duplicate_check = checkDuplicateRecord($table, $data, [
            'unique_fields' => $options['unique_fields'],
            'entity_name' => $options['entity_name'],
            'exclude_id' => $id
        ]);
        
        if ($duplicate_check) {
            return [
                'success' => false,
                'message' => $duplicate_check['message'],
                'duplicate' => true,
                'duplicate_data' => $duplicate_check
            ];
        }
    }
    
    // Prepare column assignments
    $assignments = [];
    foreach (array_keys($data) as $column) {
        $assignments[] = "$column = ?";
    }
    
    // Build the query
    $query = "UPDATE $table SET " . implode(', ', $assignments) . " WHERE {$options['id_column']} = ?";
    
    // Prepare statement
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        // Determine types and values
        $types = '';
        $values = [];
        
        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $values[] = $value;
        }
        
        // Add ID to values and types
        $values[] = $id;
        $types .= 'i';
        
        // Bind parameters
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        
        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            // Log the action if requested
            if ($options['log_action'] && $options['user_id']) {
                logAction(
                    $options['user_id'], 
                    'UPDATE', 
                    "Updated {$options['entity_name']} with ID: $id"
                );
            }
            
            return [
                'success' => true,
                'message' => ucfirst($options['entity_name']) . ' updated successfully.',
                'affected_rows' => mysqli_stmt_affected_rows($stmt)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Error updating ' . $options['entity_name'] . ': ' . mysqli_stmt_error($stmt)
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Error preparing statement: ' . mysqli_error($conn)
        ];
    }
}

/**
 * Standardize name case (proper case)
 * 
 * @param string $name The name to standardize
 * @return string Standardized name
 */
function standardizeName($name) {
    if (empty($name)) {
        return $name;
    }
    
    // Convert to lowercase first
    $name = mb_strtolower($name, 'UTF-8');
    
    // Handle prefixes like Mc, Mac, etc.
    $name = preg_replace_callback('/\b(mc|mac)(\w)/', function($matches) {
        return $matches[1] . mb_strtoupper($matches[2], 'UTF-8');
    }, $name);
    
    // Handle apostrophes like O'Reilly
    $name = preg_replace_callback('/\b(\w+)\'(\w)/', function($matches) {
        return mb_convert_case($matches[1], MB_CASE_TITLE, 'UTF-8') . "'" . mb_strtoupper($matches[2], 'UTF-8');
    }, $name);
    
    // Handle hyphenated names like Smith-Jones
    $name = preg_replace_callback('/\b(\w+)-(\w)/', function($matches) {
        return mb_convert_case($matches[1], MB_CASE_TITLE, 'UTF-8') . "-" . mb_strtoupper($matches[2], 'UTF-8');
    }, $name);
    
    // Convert to title case (first letter of each word capitalized)
    $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    
    // Special case for Roman numerals (II, III, IV, etc.)
    $name = preg_replace_callback('/\b(Ii|Iii|Iv|Vi|Vii|Viii|Ix|Xi|Xii|Xiii|Xiv|Xv)\b/', function($matches) {
        return mb_strtoupper($matches[1], 'UTF-8');
    }, $name);
    
    return $name;
}

/**
 * Standardize address to proper case
 * 
 * @param string $address The address to standardize
 * @return string Standardized address
 */
function standardizeAddress($address) {
    if (empty($address)) {
        return $address;
    }
    
    // Convert to lowercase first
    $address = mb_strtolower($address, 'UTF-8');
    
    // Convert to title case (first letter of each word capitalized)
    $address = mb_convert_case($address, MB_CASE_TITLE, 'UTF-8');
    
    // Special cases for address abbreviations
    $abbreviations = [
        '/\bSt\b/' => 'St.',
        '/\bAve\b/' => 'Ave.',
        '/\bRd\b/' => 'Rd.',
        '/\bBlvd\b/' => 'Blvd.',
        '/\bLn\b/' => 'Ln.',
        '/\bDr\b/' => 'Dr.',
        '/\bCt\b/' => 'Ct.',
        '/\bPl\b/' => 'Pl.',
        '/\bHwy\b/' => 'Hwy.',
        '/\bPkwy\b/' => 'Pkwy.',
        '/\bApt\b/' => 'Apt.',
        '/\bSte\b/' => 'Ste.',
        '/\bUnit\b/' => 'Unit',
        '/\bP\.O\. Box\b/i' => 'P.O. Box',
        '/\bPo Box\b/i' => 'P.O. Box',
    ];
    
    foreach ($abbreviations as $pattern => $replacement) {
        $address = preg_replace($pattern, $replacement, $address);
    }
    
    return $address;
}

/**
 * Standardize email to lowercase
 * 
 * @param string $email The email to standardize
 * @return string Standardized email
 */
function standardizeEmail($email) {
    return mb_strtolower(trim($email), 'UTF-8');
}

/**
 * Standardize phone number format
 * 
 * @param string $phone The phone number to standardize
 * @return string Standardized phone number
 */
function standardizePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format based on length
    $length = strlen($phone);
    
    if ($length == 10) {
        // Format as (XXX) XXX-XXXX for 10-digit numbers
        return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    } elseif ($length == 11 && $phone[0] == '1') {
        // Format as 1-XXX-XXX-XXXX for 11-digit numbers starting with 1
        return '1-' . substr($phone, 1, 3) . '-' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    } else {
        // Return as is if it doesn't match expected formats
        return $phone;
    }
}

/**
 * Standardize input based on field type
 * 
 * @param string $value The value to standardize
 * @param string $field_type The type of field (name, email, phone, address, text, uppercase, lowercase)
 * @return string Standardized value
 */
function standardizeInput($value, $field_type = 'text') {
    if (empty($value)) {
        return $value;
    }
    
    switch (strtolower($field_type)) {
        case 'name':
            return standardizeName($value);
        
        case 'email':
            return standardizeEmail($value);
        
        case 'phone':
            return standardizePhone($value);
        
        case 'address':
            return standardizeAddress($value);
        
        case 'uppercase':
            return mb_strtoupper(trim($value), 'UTF-8');
        
        case 'lowercase':
            return mb_strtolower(trim($value), 'UTF-8');
        
        case 'text':
        default:
            return trim($value);
    }
}

/**
 * Apply user settings as data attributes for use in JavaScript and CSS
 * 
 * @param array $settings User settings array
 * @return string HTML attributes string
 */
function applyUserSettings($settings) {
    $attributes = [];
    
    // Theme preference
    if (isset($settings['theme_preference'])) {
        $attributes[] = 'data-theme-preference="' . htmlspecialchars($settings['theme_preference']) . '"';
    }
    
    // High contrast mode
    if (isset($settings['high_contrast'])) {
        $attributes[] = 'data-high-contrast="' . (int)$settings['high_contrast'] . '"';
    }
    
    // Font size
    if (isset($settings['font_size'])) {
        $attributes[] = 'data-font-size="' . htmlspecialchars($settings['font_size']) . '"';
    }
    
    // Sidebar expanded
    if (isset($settings['sidebar_expanded'])) {
        $attributes[] = 'data-sidebar-expanded="' . (int)$settings['sidebar_expanded'] . '"';
    }
    
    // Table compact
    if (isset($settings['table_compact'])) {
        $attributes[] = 'data-table-compact="' . (int)$settings['table_compact'] . '"';
    }
    
    // Animation settings
    if (isset($settings['enable_animations'])) {
        $attributes[] = 'data-enable-animations="' . (int)$settings['enable_animations'] . '"';
    }
    
    if (isset($settings['animation_speed'])) {
        $attributes[] = 'data-animation-speed="' . htmlspecialchars($settings['animation_speed']) . '"';
    }
    
    // Card style
    if (isset($settings['card_style'])) {
        $attributes[] = 'data-card-style="' . htmlspecialchars($settings['card_style']) . '"';
    }
    
    // Color blind mode
    if (isset($settings['color_blind_mode'])) {
        $attributes[] = 'data-color-blind-mode="' . (int)$settings['color_blind_mode'] . '"';
    }
    
    // Motion sensitivity
    if (isset($settings['motion_reduce'])) {
        $attributes[] = 'data-motion-reduce="' . htmlspecialchars($settings['motion_reduce']) . '"';
    }
    
    // Focus indicators
    if (isset($settings['focus_visible'])) {
        $attributes[] = 'data-focus-visible="' . (int)$settings['focus_visible'] . '"';
    }
    
    // Table hover effect
    if (isset($settings['table_hover'])) {
        $attributes[] = 'data-table-hover="' . (int)$settings['table_hover'] . '"';
    }
    
    // Add any additional user settings as data attributes
    foreach ($settings as $key => $value) {
        // Skip already processed settings
        if (in_array($key, [
            'theme_preference', 'high_contrast', 'font_size', 'sidebar_expanded', 
            'table_compact', 'enable_animations', 'animation_speed', 'card_style',
            'color_blind_mode', 'motion_reduce', 'focus_visible', 'table_hover'
        ])) {
            continue;
        }
        
        // Add as data attribute
        $attributes[] = 'data-' . str_replace('_', '-', $key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return ' ' . implode(' ', $attributes);
}

/**
 * Get user settings for the current user
 * 
 * @param int $user_id User ID (optional, defaults to current user)
 * @return array User settings
 */
function getUserSettings($user_id = null) {
    global $conn;
    
    // Use current user if not specified
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    // Default settings
    $default_settings = [
        'theme_preference' => 'system',
        'sidebar_expanded' => 1,
        'table_compact' => 0,
        'font_size' => 'normal',
        'high_contrast' => 0,
        'color_blind_mode' => 0,
        'enable_animations' => 1,
        'animation_speed' => 'normal',
        'card_style' => 'default',
        'motion_reduce' => 'none',
        'focus_visible' => 1,
        'table_hover' => 1
    ];
    
    // Return defaults if no user ID
    if (!$user_id) {
        return $default_settings;
    }
    
    // Get user settings from database - query only the base columns we know exist
    $query = "SELECT id FROM users WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // User exists, now check which settings columns exist
        $columns_query = "SHOW COLUMNS FROM users";
        $columns_result = mysqli_query($conn, $columns_query);
        $existing_columns = [];
        
        while ($column = mysqli_fetch_assoc($columns_result)) {
            $existing_columns[] = $column['Field'];
        }
        
        // Build a query with only the existing settings columns
        $select_columns = [];
        foreach ($default_settings as $key => $value) {
            if (in_array($key, $existing_columns)) {
                $select_columns[] = $key;
            }
        }
        
        if (!empty($select_columns)) {
            $settings_query = "SELECT " . implode(', ', $select_columns) . " FROM users WHERE id = ?";
            $settings_stmt = mysqli_prepare($conn, $settings_query);
            mysqli_stmt_bind_param($settings_stmt, "i", $user_id);
            mysqli_stmt_execute($settings_stmt);
            $settings_result = mysqli_stmt_get_result($settings_stmt);
            
            if ($settings = mysqli_fetch_assoc($settings_result)) {
                return array_merge($default_settings, $settings);
            }
        }
        
        return $default_settings;
    }
    
    return $default_settings;
}

/**
 * Update user settings
 * 
 * @param int $user_id User ID
 * @param array $settings Settings to update
 * @return bool True on success, false on failure
 */
function updateUserSettings($user_id, $settings) {
    global $conn;
    
    // Validate settings
    $valid_settings = [
        'theme_preference' => ['light', 'dark', 'system'],
        'sidebar_expanded' => [0, 1],
        'table_compact' => [0, 1],
        'font_size' => ['small', 'normal', 'large', 'xlarge'],
        'high_contrast' => [0, 1],
        'color_blind_mode' => [0, 1],
        'enable_animations' => [0, 1],
        'animation_speed' => ['slow', 'normal', 'fast'],
        'card_style' => ['default', 'flat', 'bordered'],
        'motion_reduce' => ['none', 'reduce', 'disable'],
        'focus_visible' => [0, 1],
        'table_hover' => [0, 1]
    ];
    
    $update_fields = [];
    $types = '';
    $values = [];
    
    // Build update query
    foreach ($settings as $key => $value) {
        // Skip if not a valid setting
        if (!isset($valid_settings[$key])) {
            continue;
        }
        
        // Validate value
        if (is_array($valid_settings[$key]) && !in_array($value, $valid_settings[$key])) {
            // Use default value if invalid
            switch ($key) {
                case 'theme_preference':
                    $value = 'system';
                    break;
                case 'font_size':
                    $value = 'normal';
                    break;
                case 'animation_speed':
                    $value = 'normal';
                    break;
                case 'card_style':
                    $value = 'default';
                    break;
                case 'motion_reduce':
                    $value = 'none';
                    break;
                default:
                    $value = 0;
            }
        }
        
        // Add to update fields
        $update_fields[] = "$key = ?";
        
        // Determine parameter type
        if (is_int($value) || in_array($key, [
            'sidebar_expanded', 'table_compact', 'high_contrast',
            'color_blind_mode', 'enable_animations', 'focus_visible',
            'table_hover'
        ])) {
            $types .= 'i';
            $value = (int)$value;
        } else {
            $types .= 's';
        }
        
        $values[] = $value;
    }
    
    // Return if no valid fields to update
    if (empty($update_fields)) {
        return false;
    }
    
    // Add user ID to values
    $types .= 'i';
    $values[] = $user_id;
    
    // Build and execute query
    $query = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    
    // Bind parameters dynamically
    $bind_params = array($stmt, $types);
    foreach ($values as $key => $value) {
        $bind_params[] = &$values[$key];
    }
    
    call_user_func_array('mysqli_stmt_bind_param', $bind_params);
    
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // If successful, also update the user's session settings
    if ($result && isset($_SESSION['user_settings'])) {
        foreach ($settings as $key => $value) {
            if (isset($valid_settings[$key])) {
                $_SESSION['user_settings'][$key] = $value;
            }
        }
    }
    
    return $result;
}

/**
 * Get system theme based on user preferences and system settings
 * 
 * @param array $user_settings User settings array
 * @return string 'light' or 'dark'
 */
function getEffectiveTheme($user_settings) {
    // Default to light mode
    $theme = 'light';
    
    // Get user preference
    $preference = $user_settings['theme_preference'] ?? 'system';
    
    if ($preference === 'dark') {
        $theme = 'dark';
    } elseif ($preference === 'system') {
        // This will be handled client-side with JavaScript
        // We'll return 'system' to indicate that
        $theme = 'system';
    }
    
    return $theme;
}

/**
 * Get the current school year
 * 
 * @param mysqli $conn Database connection
 * @return string Current school year (e.g., "2023-2024")
 */
function getCurrentSchoolYear($conn) {
    // Check if there's a setting for current school year
    $query = "SELECT value FROM settings WHERE setting_key = 'current_school_year' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['value'];
    }
    
    // If no setting found, determine current school year based on date
    $current_month = (int)date('n'); // 1-12
    $current_year = (int)date('Y');
    
    // If current month is June or later, school year starts in current year
    // Otherwise, school year started in previous year
    if ($current_month >= 6) {
        return $current_year . '-' . ($current_year + 1);
    } else {
        return ($current_year - 1) . '-' . $current_year;
    }
}

/**
 * Get the current semester
 * 
 * @param mysqli $conn Database connection
 * @return string Current semester ("First" or "Second")
 */
function getCurrentSemester($conn) {
    // Check if there's a setting for current semester
    $query = "SELECT value FROM settings WHERE setting_key = 'current_semester' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['value'];
    }
    
    // If no setting found, determine current semester based on date
    $current_month = (int)date('n'); // 1-12
    
    // First semester: June to October
    // Second semester: November to March
    // April to May is summer/break
    if ($current_month >= 6 && $current_month <= 10) {
        return 'First';
    } elseif ($current_month >= 11 || $current_month <= 3) {
        return 'Second';
    } else {
        // During summer/break, default to First semester for the next school year
        return 'First';
    }
}

/**
 * Get sections by grade level
 * 
 * @param string $grade_level The grade level (can be "11", "12", "Grade 11", or "Grade 12")
 * @param mysqli $conn The database connection
 * @return array Array of sections with id, name, and display properties
 */
function getSectionsByGradeLevel($grade_level, $conn) {
    // Normalize grade level format (handle both "11" and "Grade 11" formats)
    if ($grade_level === '11' || $grade_level === '12') {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    // Extract the grade number for formatting
    $grade_number = str_replace('Grade ', '', $grade_level);
    
    // Get all active sections for the specified grade level
    $query = "SELECT s.id, s.name, s.strand, s.school_year, s.semester, ss.strand_code 
              FROM sections s 
              LEFT JOIN shs_strands ss ON s.strand = ss.strand_code 
              WHERE s.grade_level = ? AND s.status = 'Active' 
              ORDER BY ss.strand_code, s.name";
    
    $sections = [];
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $grade_level);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Format the section name as STRAND-GRADE (e.g., ABM-11A)
            $strand_code = !empty($row['strand_code']) ? $row['strand_code'] : 'GEN';
            $section_name = $row['name'];
            
            // Create a formatted display name
            $display_name = formatSectionDisplay($section_name, $grade_level, $conn);
            
            $sections[] = [
                'id' => $row['id'],
                'name' => $section_name,
                'display' => $display_name,
                'strand' => $row['strand'],
                'school_year' => $row['school_year'],
                'semester' => $row['semester']
            ];
        }
    }
    
    return $sections;
}

/**
 * Get subjects by grade level
 * 
 * @param string $grade_level The grade level (can be "11", "12", "Grade 11", or "Grade 12")
 * @param mysqli $conn The database connection
 * @return array Array of subjects with id, code, name, and display properties
 */
function getSubjectsByGradeLevel($grade_level, $conn) {
    // Normalize grade level format (handle both "11" and "Grade 11" formats)
    if ($grade_level === '11' || $grade_level === '12') {
        $grade_level = 'Grade ' . $grade_level;
    }
    
    // Normalize grade level format (convert "Grade 11" to "11" if needed)
    $numeric_grade = $grade_level;
    if (strpos($grade_level, 'Grade ') === 0) {
        $numeric_grade = substr($grade_level, 6); // Extract "11" from "Grade 11"
    }
    
    // Get subjects for the specified grade level
    $query = "SELECT id, code, name FROM subjects WHERE (grade_level = ? OR grade_level = ?) AND status = 'active' ORDER BY name";
    
    $subjects = [];
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $grade_level, $numeric_grade);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $subjects[] = [
                'id' => $row['id'],
                'code' => $row['code'],
                'name' => $row['name'],
                'display' => $row['code'] . ' - ' . $row['name']
            ];
        }
    }
    
    return $subjects;
}

/**
 * Format subject display consistently across the system
 * 
 * @param string $subject The subject name or code
 * @param mysqli $conn The database connection
 * @return string Formatted subject display (e.g., "MATH101 - Mathematics")
 */
function formatSubjectDisplay($subject, $conn) {
    // Check if the subject is already in the database
    $stmt = mysqli_prepare($conn, "SELECT code, name FROM subjects WHERE name = ? OR code = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $subject, $subject);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            // Return the formatted subject with code and name
            return $row['code'] . ' - ' . $row['name'];
        }
        mysqli_stmt_close($stmt);
    }
    
    // If not found in database, return the original subject
    return $subject;
}

/**
 * Format section display consistently across the system
 * 
 * @param string $section The section name
 * @param string $grade_level The grade level (can be "11", "12", "Grade 11", or "Grade 12")
 * @param mysqli $conn The database connection
 * @return string Formatted section display (e.g., "STEM-11A")
 */
function formatSectionDisplay($section, $grade_level, $conn) {
    // Normalize grade level format (handle both "11" and "Grade 11" formats)
    $grade_number = $grade_level;
    if (strpos($grade_level, 'Grade ') === 0) {
        $grade_number = substr($grade_level, 6); // Extract "11" from "Grade 11"
    }
    
    // Get strand from the section name if available
    $strand = 'GEN'; // Default strand if none found
    $stmt = mysqli_prepare($conn, "SELECT s.strand_code FROM sections sec 
                                 LEFT JOIN shs_strands s ON sec.strand = s.strand_code 
                                 WHERE sec.name = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $section);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['strand_code'])) {
                $strand = $row['strand_code'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Extract section suffix if it exists (like A, B, C)
    $suffix = '';
    if (preg_match('/[A-Z]$/', $section)) {
        $suffix = substr($section, -1);
    }
    
    // Format the display as STRAND-GRADE+SUFFIX (e.g., "STEM-11A")
    return $strand . '-' . $grade_number . $suffix;
}

/**
 * Get current school year from the system
 * 
 * @param mysqli $conn The database connection
 * @return string Current school year
 */
?> 