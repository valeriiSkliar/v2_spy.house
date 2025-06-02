<?php

echo "=== Resend Contact Management Test Suite ===\n\n";

$testScripts = [
    'Basic Contact Management' => __DIR__ . '/test_resend_contact_management.php',
    'User Email Workflow' => __DIR__ . '/test_fixed_workflow.php'
];

foreach ($testScripts as $testName => $scriptPath) {
    echo "🧪 Running: $testName\n";
    echo str_repeat("=", 50) . "\n";

    if (file_exists($scriptPath)) {
        // Capture output and execution time
        $startTime = microtime(true);

        ob_start();
        include $scriptPath;
        $output = ob_get_clean();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        echo $output;
        echo "\n⏱️ Execution time: {$executionTime}ms\n";
    } else {
        echo "❌ Test script not found: $scriptPath\n";
    }

    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "✅ All tests completed!\n";
