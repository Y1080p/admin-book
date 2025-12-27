<?php
// 修复 api.php 的 CORS 配置
$apiFile = 'api.php';
$content = file_get_contents($apiFile);

// 查找并替换 CORS 配置部分
$oldCors = 'if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    

}

header(\'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS\');';

$newCors = '// 允许所有 localhost 和 127.0.0.1 的请求
if (strpos($origin, \'localhost\') !== false || strpos($origin, \'127.0.0.1\') !== false) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} elseif (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true);
}

header(\'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS\');';

$content = str_replace($oldCors, $newCors, $content);

// 写回文件
file_put_contents($apiFile, $content);

echo "CORS 配置已修复\n";
?>
