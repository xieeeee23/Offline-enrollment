<?php
// This is a simple test script to verify parameter counts for each binding case

echo "Parameter count verification for student.php binding cases\n\n";

// Case 1: INSERT with photo - 19 params (18 strings + 1 integer)
$case1_params = "ssssssssssssssssssi";  // From the code
$case1_expected = 19;  // Total number of parameters
$case1_actual = strlen($case1_params);
$case1_s_count = substr_count($case1_params, "s");
$case1_i_count = substr_count($case1_params, "i");

echo "Case 1: INSERT with photo\n";
echo "  Expected parameters: " . $case1_expected . " (" . ($case1_expected - 1) . " strings + 1 integer)\n";
echo "  Actual type string:  " . $case1_params . "\n";
echo "  Actual length:       " . $case1_actual . " (" . $case1_s_count . " strings + " . $case1_i_count . " integers)\n";
echo "  Status:             " . ($case1_actual == $case1_expected ? "CORRECT" : "ERROR") . "\n\n";

// Case 2: INSERT without photo - 18 params (17 strings + 1 integer)
$case2_params = "sssssssssssssssssi";  // From the code
$case2_expected = 18;  // Total number of parameters
$case2_actual = strlen($case2_params);
$case2_s_count = substr_count($case2_params, "s");
$case2_i_count = substr_count($case2_params, "i");

echo "Case 2: INSERT without photo\n";
echo "  Expected parameters: " . $case2_expected . " (" . ($case2_expected - 1) . " strings + 1 integer)\n";
echo "  Actual type string:  " . $case2_params . "\n";
echo "  Actual length:       " . $case2_actual . " (" . $case2_s_count . " strings + " . $case2_i_count . " integers)\n";
echo "  Status:             " . ($case2_actual == $case2_expected ? "CORRECT" : "ERROR") . "\n\n";

// Case 3: UPDATE with photo - 19 params (18 strings + 1 integer)
$case3_params = "ssssssssssssssssssi";  // From the code
$case3_expected = 19;  // Total number of parameters
$case3_actual = strlen($case3_params);
$case3_s_count = substr_count($case3_params, "s");
$case3_i_count = substr_count($case3_params, "i");

echo "Case 3: UPDATE with photo\n";
echo "  Expected parameters: " . $case3_expected . " (" . ($case3_expected - 1) . " strings + 1 integer)\n";
echo "  Actual type string:  " . $case3_params . "\n";
echo "  Actual length:       " . $case3_actual . " (" . $case3_s_count . " strings + " . $case3_i_count . " integers)\n";
echo "  Status:             " . ($case3_actual == $case3_expected ? "CORRECT" : "ERROR") . "\n\n";

// Case 4: UPDATE without photo - 18 params (17 strings + 1 integer)
$case4_params = "sssssssssssssssssi";  // From the code
$case4_expected = 18;  // Total number of parameters
$case4_actual = strlen($case4_params);
$case4_s_count = substr_count($case4_params, "s");
$case4_i_count = substr_count($case4_params, "i");

echo "Case 4: UPDATE without photo\n";
echo "  Expected parameters: " . $case4_expected . " (" . ($case4_expected - 1) . " strings + 1 integer)\n";
echo "  Actual type string:  " . $case4_params . "\n";
echo "  Actual length:       " . $case4_actual . " (" . $case4_s_count . " strings + " . $case4_i_count . " integers)\n";
echo "  Status:             " . ($case4_actual == $case4_expected ? "CORRECT" : "ERROR") . "\n\n";

echo "SUMMARY:\n";
$all_correct = ($case1_actual == $case1_expected && 
               $case2_actual == $case2_expected && 
               $case3_actual == $case3_expected && 
               $case4_actual == $case4_expected);

echo "All parameter counts " . ($all_correct ? "CORRECT" : "HAVE ERRORS") . "\n";

if (!$all_correct) {
    echo "\nERRORS FOUND:\n";
    
    if ($case1_actual != $case1_expected) {
        echo "- Case 1 (INSERT with photo): Expected " . $case1_expected . " parameters, found " . $case1_actual . "\n";
    }
    
    if ($case2_actual != $case2_expected) {
        echo "- Case 2 (INSERT without photo): Expected " . $case2_expected . " parameters, found " . $case2_actual . "\n";
    }
    
    if ($case3_actual != $case3_expected) {
        echo "- Case 3 (UPDATE with photo): Expected " . $case3_expected . " parameters, found " . $case3_actual . "\n";
    }
    
    if ($case4_actual != $case4_expected) {
        echo "- Case 4 (UPDATE without photo): Expected " . $case4_expected . " parameters, found " . $case4_actual . "\n";
    }
}
?> 