<?php
// Script to add relative path to all PHP files in modules directory

function processDirectory($dir) {
    echo "Scanning directory: $dir\n";
    
    if (!is_dir($dir)) {
        echo "Error: Directory $dir does not exist.\n";
        return;
    }
    
    $files = scandir($dir);
    
    if ($files === false) {
        echo "Error: Could not scan directory $dir.\n";
        return;
    }
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            processDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            fixFile($path);
        }
    }
}

function fixFile($file) {
    echo "Processing: $file\n";
    
    if (!file_exists($file)) {
        echo "  - Error: File does not exist.\n";
        return;
    }
    
    $content = file_get_contents($file);
    
    if ($content === false) {
        echo "  - Error: Could not read file.\n";
        return;
    }
    
    $changes = false;
    
    // Skip if file already has $relative_path defined but still update BASE_URL references
    if (strpos($content, '$relative_path') === false) {
        // Add relative path definition after the first PHP tag
        $pattern = '/^<\?php\s+/';
        $replacement = "<?php\n\$relative_path = '../../';\n";
        
        $new_content = preg_replace($pattern, $replacement, $content, 1);
        
        if ($new_content === null) {
            echo "  - Error: Preg replace failed.\n";
            return;
        }
        
        $content = $new_content;
        $changes = true;
        echo "  - Added relative path variable\n";
        
        // Replace includes/header.php with $relative_path . 'includes/header.php'
        $content = str_replace("require_once 'includes/header.php'", "require_once \$relative_path . 'includes/header.php'", $content);
        $content = str_replace("require_once \"includes/header.php\"", "require_once \$relative_path . 'includes/header.php'", $content);
        $content = str_replace("include 'includes/header.php'", "include \$relative_path . 'includes/header.php'", $content);
        $content = str_replace("include \"includes/header.php\"", "include \$relative_path . 'includes/header.php'", $content);
        
        // Replace includes/footer.php with $relative_path . 'includes/footer.php'
        $content = str_replace("require_once 'includes/footer.php'", "require_once \$relative_path . 'includes/footer.php'", $content);
        $content = str_replace("require_once \"includes/footer.php\"", "require_once \$relative_path . 'includes/footer.php'", $content);
        $content = str_replace("include 'includes/footer.php'", "include \$relative_path . 'includes/footer.php'", $content);
        $content = str_replace("include \"includes/footer.php\"", "include \$relative_path . 'includes/footer.php'", $content);
        
        // Replace includes/config.php with $relative_path . 'includes/config.php'
        $content = str_replace("require_once 'includes/config.php'", "require_once \$relative_path . 'includes/config.php'", $content);
        $content = str_replace("require_once \"includes/config.php\"", "require_once \$relative_path . 'includes/config.php'", $content);
        $content = str_replace("include 'includes/config.php'", "include \$relative_path . 'includes/config.php'", $content);
        $content = str_replace("include \"includes/config.php\"", "include \$relative_path . 'includes/config.php'", $content);
        
        // Replace includes/functions.php with $relative_path . 'includes/functions.php'
        $content = str_replace("require_once 'includes/functions.php'", "require_once \$relative_path . 'includes/functions.php'", $content);
        $content = str_replace("require_once \"includes/functions.php\"", "require_once \$relative_path . 'includes/functions.php'", $content);
        $content = str_replace("include 'includes/functions.php'", "include \$relative_path . 'includes/functions.php'", $content);
        $content = str_replace("include \"includes/functions.php\"", "include \$relative_path . 'includes/functions.php'", $content);
    } else {
        echo "  - Already has relative path defined\n";
    }
    
    // Replace BASE_URL with $relative_path in href and src attributes
    $pattern = '/<a href="<\?php echo BASE_URL; \?>([^"]+)"/';
    $replacement = '<a href="<?php echo $relative_path; ?>$1"';
    $new_content = preg_replace($pattern, $replacement, $content);
    
    if ($new_content !== $content) {
        $content = $new_content;
        $changes = true;
        echo "  - Updated BASE_URL references in href attributes\n";
    }
    
    // Replace other BASE_URL references
    if (strpos($content, 'BASE_URL') !== false) {
        $content = str_replace('$export_url = BASE_URL', '$export_url = $relative_path', $content);
        $changes = true;
        echo "  - Updated other BASE_URL references\n";
    }
    
    if ($changes) {
        // Write the updated content back to the file
        $result = file_put_contents($file, $content);
        
        if ($result === false) {
            echo "  - Error: Could not write to file.\n";
            return;
        }
        
        echo "  - Updated successfully\n";
    } else {
        echo "  - No changes needed\n";
    }
}

echo "Starting path fix script...\n";
echo "=========================\n\n";

// Start processing from the modules directory
processDirectory('modules');

echo "\nAll files processed successfully!\n";
?> 