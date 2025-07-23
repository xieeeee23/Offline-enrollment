<?php
// Include database connection
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if track parameter is provided
if (!isset($_GET['track'])) {
    // Return empty array if no track is provided
    echo json_encode([]);
    exit;
}

// Get track from query parameter
$track = cleanInput($_GET['track']);

// Connect to database
$conn = getConnection();

// Get strands for the selected track
$query = "SELECT strand_code, strand_name FROM shs_strands WHERE track_name = ? AND status = 'Active' ORDER BY strand_name";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $track);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$strands = [];
while ($row = mysqli_fetch_assoc($result)) {
    $strands[] = $row;
}

// Return strands as JSON
header('Content-Type: application/json');
echo json_encode($strands);
exit;
?> 