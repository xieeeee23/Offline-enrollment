<?php
// Analyze the students.php file line by line to find brace mismatches

$file_path = 'modules/registrar/students.php';
$lines = file($file_path);

if ($lines === false) {
    die("Error: Could not read the file $file_path");
}

$brace_count = 0;
$line_number = 0;
$problem_lines = [];

foreach ($lines as $line_number => $line) {
    $open_braces = substr_count($line, '{');
    $close_braces = substr_count($line, '}');
    $brace_count += $open_braces - $close_braces;
    
    // Store line numbers where brace count changes
    if ($open_braces > 0 || $close_braces > 0) {
        $problem_lines[] = [
            'line' => $line_number + 1, // Convert to 1-based line numbers
            'content' => trim($line),
            'open' => $open_braces,
            'close' => $close_braces,
            'balance' => $brace_count
        ];
    }
}

echo "Final brace balance: $brace_count\n";
echo ($brace_count === 0) ? "Braces are balanced.\n" : "Braces are NOT balanced.\n";

// Print lines around line 236
echo "\nLines around 236:\n";
$start = max(230, 1);
$end = min(240, count($lines));
for ($i = $start; $i <= $end; $i++) {
    echo $i . ": " . $lines[$i-1];
}

// Print the last 10 problematic lines
echo "\nLast 10 problematic lines:\n";
$last_problems = array_slice($problem_lines, -10);
foreach ($last_problems as $problem) {
    echo "Line {$problem['line']} (Open: {$problem['open']}, Close: {$problem['close']}, Balance: {$problem['balance']}): {$problem['content']}\n";
}

// Find lines with potential issues (more opens than closes)
echo "\nLines with potential issues:\n";
foreach ($problem_lines as $problem) {
    if ($problem['open'] > $problem['close']) {
        echo "Line {$problem['line']} has {$problem['open']} open braces and {$problem['close']} close braces: {$problem['content']}\n";
    }
}
?> 