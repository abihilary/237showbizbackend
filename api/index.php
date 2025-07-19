<?php

ini_set('display_errors', 1); // SHOW errors
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL); // REPORT all types of errors

$request = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$method = $_SERVER['REQUEST_METHOD'];

if (preg_match('#^api/comments?(/.*)?$#', $request)) {
    require __DIR__ . '/routes/comment_route.php';

} elseif (preg_match('#^api/subscribers?(/.*)?$#', $request)) {
    require __DIR__ . '/routes/subscriber_route.php';

} elseif (preg_match('#^api/comment_interaction?(/.*)?$#', $request)) {
    require __DIR__ . '/routes/comment_interaction_route.php';

} elseif (preg_match('#^api/home?(/.*)?$#', $request)) {
    require __DIR__ . '/routes/home.php';

} elseif (preg_match('#^api/artist?(/.*)?$#', $request)) {
    require __DIR__ . '/routes/artist_rout.php';

} else {
    http_response_code(404);
    echo json_encode(['error' => "404 - Endpoint /$request not found"]);
    exit;
}
