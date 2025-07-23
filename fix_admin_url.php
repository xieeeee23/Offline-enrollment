<?php
// Script to fix admin URL issues

echo "<h1>Fixing Admin URL Issues</h1>";

// Create a .htaccess file in the admin directory
$admin_dir = 'modules/admin';
if (!is_dir($admin_dir)) {
    mkdir($admin_dir, 0777, true);
    echo "<p>Created admin directory at $admin_dir</p>";
}

$htaccess_content = <<<EOT
# Fix URL duplication issues
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{THE_REQUEST} /offline%20enrollment/offline%20enrollment/modules/admin/ [NC]
RewriteRule ^(.*)$ /offline%20enrollment/modules/admin/$1 [L,R=301]
</IfModule>

# Ensure PHP files are processed correctly
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>
EOT;

if (file_put_contents("$admin_dir/.htaccess", $htaccess_content)) {
    echo "<p style='color: green;'>Successfully created .htaccess file in $admin_dir directory.</p>";
} else {
    echo "<p style='color: red;'>Failed to create .htaccess file in $admin_dir directory.</p>";
}

// Create a copy of users.php in the admin directory to ensure it exists
$users_file = "$admin_dir/users.php";
if (file_exists($users_file)) {
    echo "<p>Users file already exists at $users_file</p>";
    
    // Check if it's accessible
    $url = "http://localhost/offline%20enrollment/modules/admin/users.php";
    echo "<p>You can try accessing the users page at: <a href='$url'>$url</a></p>";
} else {
    echo "<p style='color: red;'>Users file does not exist at $users_file</p>";
}

// Create a simple index.php file in the admin directory
$index_content = <<<EOT
<?php
// Redirect to users.php
header("Location: users.php");
exit;
?>
EOT;

if (file_put_contents("$admin_dir/index.php", $index_content)) {
    echo "<p>Created index.php in $admin_dir directory.</p>";
} else {
    echo "<p style='color: red;'>Failed to create index.php in $admin_dir directory.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Try accessing the users page at <a href='modules/admin/users.php'>modules/admin/users.php</a></li>";
echo "<li>If that doesn't work, try <a href='modules/admin/'>modules/admin/</a></li>";
echo "<li>If issues persist, check your web server configuration</li>";
echo "</ol>";
?> 