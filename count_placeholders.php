<?php
// Count placeholders in the SQL queries

echo "Counting placeholders in the SQL queries\n\n";

// Case 4: UPDATE without photo
$query = "UPDATE students SET lrn = ?, first_name = ?, middle_name = ?, last_name = ?, dob = ?, 
        gender = ?, religion = ?, address = ?, contact_number = ?, email = ?, father_name = ?, 
        father_occupation = ?, mother_name = ?, mother_occupation = ?, grade_level = ?, section = ?, 
        enrollment_status = ? WHERE id = ?";

$placeholders = substr_count($query, '?');
$expected_params = 18; // 17 strings + 1 integer
$type_string = "sssssssssssssssssi"; // Updated to match the code
$type_string_length = strlen($type_string);

echo "Case 4: UPDATE without photo\n";
echo "  Query: " . str_replace("\n", " ", $query) . "\n";
echo "  Placeholders in query: " . $placeholders . "\n";
echo "  Expected parameters: " . $expected_params . "\n";
echo "  Type string: " . $type_string . "\n";
echo "  Type string length: " . $type_string_length . "\n";
echo "  Status: " . ($placeholders == $type_string_length ? "CORRECT" : "ERROR") . "\n\n";

// Count actual parameters
$params = [
    '$lrn', '$first_name', '$middle_name', '$last_name', '$dob',
    '$gender', '$religion', '$address', '$contact_number', '$email', 
    '$father_name', '$father_occupation', '$mother_name', '$mother_occupation',
    '$grade_level', '$section', '$enrollment_status', '$edit_id'
];

echo "  Actual parameters (" . count($params) . "): " . implode(", ", $params) . "\n";

// Display corrected type string for errors
if ($placeholders != $type_string_length) {
    $corrected_type_string = str_repeat("s", $placeholders - 1) . "i"; // Assuming the last param is an int
    echo "  Corrected type string: " . $corrected_type_string . " (length: " . strlen($corrected_type_string) . ")\n";
}

// Now let's also check the UPDATE with photo case
echo "\n\nCase 3: UPDATE with photo\n";
$query_with_photo = "UPDATE students SET lrn = ?, first_name = ?, middle_name = ?, last_name = ?, dob = ?, 
        gender = ?, religion = ?, address = ?, contact_number = ?, email = ?, father_name = ?, 
        father_occupation = ?, mother_name = ?, mother_occupation = ?, grade_level = ?, section = ?, 
        enrollment_status = ?, photo = ? WHERE id = ?";

$placeholders_photo = substr_count($query_with_photo, '?');
$expected_params_photo = 19; // 18 strings + 1 integer
$type_string_photo = "ssssssssssssssssssi"; // From the code
$type_string_photo_length = strlen($type_string_photo);

echo "  Placeholders in query: " . $placeholders_photo . "\n";
echo "  Expected parameters: " . $expected_params_photo . "\n";
echo "  Type string: " . $type_string_photo . "\n";
echo "  Type string length: " . $type_string_photo_length . "\n";
echo "  Status: " . ($placeholders_photo == $type_string_photo_length ? "CORRECT" : "ERROR") . "\n";
?> 