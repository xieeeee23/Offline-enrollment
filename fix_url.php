<?php
// Script to fix URL duplication issues

echo "<h1>Fixing URL Duplication Issues</h1>";

// Check if .htaccess exists
if (file_exists('.htaccess')) {
    $htaccess_content = file_get_contents('.htaccess');
    echo "<p>Found existing .htaccess file.</p>";
} else {
    $htaccess_content = '';
    echo "<p>No existing .htaccess file found. Creating a new one.</p>";
}

// Add RewriteEngine rules to prevent URL duplication
$rewrite_rules = <<<EOT
# Begin URL Fix
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{THE_REQUEST} /offline%20enrollment/offline%20enrollment/ [NC]
RewriteRule ^offline\s+enrollment/offline\s+enrollment/(.*)$ /offline\s+enrollment/$1 [L,R=301]

# Fix other common URL issues
RewriteCond %{THE_REQUEST} //+ [NC]
RewriteRule ^.*$ $0 [R=301,L,NE]
</IfModule>
# End URL Fix
EOT;

// Check if rules already exist
if (strpos($htaccess_content, '# Begin URL Fix') !== false) {
    echo "<p>URL fix rules already exist in .htaccess. Updating them.</p>";
    
    // Remove existing rules
    $pattern = '/# Begin URL Fix.*# End URL Fix/s';
    $htaccess_content = preg_replace($pattern, '', $htaccess_content);
}

// Add the rules to the .htaccess file
$htaccess_content .= "\n" . $rewrite_rules . "\n";

// Write the updated content back to the file
if (file_put_contents('.htaccess', $htaccess_content)) {
    echo "<p style='color: green;'>Successfully updated .htaccess file with URL fix rules.</p>";
} else {
    echo "<p style='color: red;'>Failed to update .htaccess file. Please check file permissions.</p>";
}

// Create a redirect file for common problematic URLs
echo "<h2>Creating Redirect Files</h2>";

$redirect_content = <<<EOT
<?php
// Redirect to the correct location
\$correct_path = str_replace('/offline%20enrollment/offline%20enrollment/', '/offline%20enrollment/', \$_SERVER['REQUEST_URI']);
header("Location: \$correct_path");
exit;
?>
EOT;

// Create redirect.php in the root directory
if (file_put_contents('redirect.php', $redirect_content)) {
    echo "<p>Created redirect.php in root directory.</p>";
} else {
    echo "<p style='color: red;'>Failed to create redirect.php in root directory.</p>";
}

// Create a .htaccess file in the modules/admin directory
$admin_htaccess = <<<EOT
# Prevent URL duplication
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/offline%20enrollment/offline%20enrollment/modules/admin/
RewriteRule ^(.*)$ /offline%20enrollment/modules/admin/$1 [L,R=301]
</IfModule>
EOT;

if (!is_dir('modules/admin')) {
    mkdir('modules/admin', 0777, true);
    echo "<p>Created modules/admin directory.</p>";
}

if (file_put_contents('modules/admin/.htaccess', $admin_htaccess)) {
    echo "<p>Created .htaccess in modules/admin directory.</p>";
} else {
    echo "<p style='color: red;'>Failed to create .htaccess in modules/admin directory.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<p>The URL duplication issues should now be fixed. If you continue to experience problems:</p>";
echo "<ol>";
echo "<li>Restart your web server</li>";
echo "<li>Clear your browser cache</li>";
echo "<li>Try accessing the page again</li>";
echo "</ol>";

echo "<p><a href='modules/admin/users.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Try accessing Users Page</a></p>";
?> 