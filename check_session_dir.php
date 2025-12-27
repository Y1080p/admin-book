<?php
header('Content-Type: text/html; charset=utf-8');

$savePath = ini_get('session.save_path');
echo "<h1>Session Directory Check</h1>";

echo "<h2>Session Save Path</h2>";
echo "<pre>" . ($savePath ?: 'NOT SET (using system default)') . "</pre>";

if (empty($savePath)) {
    // 如果没有设置，查找系统默认路径
    $defaultPath = sys_get_temp_dir();
    echo "<p class='warning'>Session save path is empty, checking default temp directory: $defaultPath</p>";
    $savePath = $defaultPath;
}

echo "<h2>Directory Information</h2>";
if (is_dir($savePath)) {
    echo "<p class='success'>✓ Directory exists</p>";
    echo "<pre>";
    echo "Readable: " . (is_readable($savePath) ? 'YES' : 'NO') . "\n";
    echo "Writable: " . (is_writable($savePath) ? 'YES' : 'NO') . "\n";
    echo "</pre>";

    // 列出session文件
    echo "<h2>Session Files in $savePath</h2>";
    $files = glob($savePath . '/sess_*');
    if (empty($files)) {
        echo "<p class='error'>No session files found!</p>";
    } else {
        echo "<p>" . count($files) . " session files found:</p>";
        echo "<ul>";
        foreach (array_slice($files, 0, 10) as $file) {
            $size = filesize($file);
            $mtime = date('Y-m-d H:i:s', filemtime($file));
            $filename = basename($file);
            echo "<li>$filename (Size: $size bytes, Modified: $mtime)</li>";
        }
        if (count($files) > 10) {
            echo "<li>... and " . (count($files) - 10) . " more</li>";
        }
        echo "</ul>";
    }
} else {
    echo "<p class='error'>✗ Directory does NOT exist!</p>";
    echo "<p>Please create the directory or set a writable session.save_path in php.ini</p>";
}

?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    h1 { color: #333; }
    h2 { color: #555; margin-top: 30px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    ul { background: #f9f9f9; padding: 15px 30px; border-radius: 5px; }
</style>
