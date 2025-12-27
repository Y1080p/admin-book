<?php
// 查看PHP error.log的辅助脚本
echo "=== PHP Error Log Viewer ===\n";

$logPaths = [
    'E:/phpstudy_pro/Extensions/Apache2.4.39/logs/error.log',
    'E:/phpstudy_pro/WWW/phpstudy_logs/apache_error.log',
    'C:/xampp/apache/logs/error.log',
];

foreach ($logPaths as $path) {
    if (file_exists($path)) {
        echo "Found log at: $path\n";
        echo "Last 20 lines:\n";
        echo "=================\n";
        $lines = array_slice(file($path), -20);
        foreach ($lines as $line) {
            // 只显示包含DEBUG、SESSION、LOGIN的行
            if (stripos($line, 'DEBUG') !== false ||
                stripos($line, 'SESSION') !== false ||
                stripos($line, 'LOGIN') !== false ||
                stripos($line, 'login') !== false) {
                echo $line . "\n";
            }
        }
        echo "=================\n";
        exit;
    }
}

echo "No error log found. Check paths:\n";
print_r($logPaths);
