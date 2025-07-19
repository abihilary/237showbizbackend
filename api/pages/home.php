<?php

ini_set('display_errors', 1); // SHOW errors
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL); // REPORT all types of errors


// ❌ This line is missing a semicolon
// require '/helpers/dbConnect.php'

// ✅ Fixed version:
require __DIR__ . '/../helpers/dbConnect.php';
require __DIR__ . '/../pages/subscriber.php';




$method = $_SERVER['REQUEST_METHOD'];
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$pathParts = explode('/', $path);

// URL structure: /api/comments or /api/comments/{comment_id}
$commentId = $pathParts[2] ?? null;

switch ($method) {

    case 'GET':
                echo json_encode([
                "name" => "hilary",
                "age" => "25",
                "location" => "cameroon"
            ]);

        break;

echo json_encode([
    "name" => "hilary",
    "age" => "25",
    "location" => "cameroon"
]);
?>
