<?php
// 直接查看PHP错误日志
$logFile = 'E:\phpstudy_pro\Extensions\Apache2.4.39\logs\error.log';

echo "<h1>PHP Error Log (Last 100 lines)</h1>";

if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -100);

    echo "<h2>Filtering for DEBUG/SESSION/LOGIN keywords:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";

    $count = 0;
    foreach ($recentLines as $line) {
        if (stripos($line, 'DEBUG') !== false ||
            stripos($line, 'SESSION') !== false ||
            stripos($line, 'LOGIN') !== false ||
            stripos($line, 'API LOGIN') !== false) {
            echo htmlspecialchars($line) . "\n";
            $count++;
        }
    }

    if ($count === 0) {
        echo "<strong style='color: red;'>No DEBUG/SESSION/LOGIN logs found in recent entries!</strong>";
    }

    echo "</pre>";

    echo "<h2>All recent lines:</h2>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    foreach (array_slice($recentLines, -30) as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    echo "</pre>";

} else {
    echo "<p style='color: red;'>Log file not found: $logFile</p>";
}
