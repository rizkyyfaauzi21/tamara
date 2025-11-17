<?php
// public/download/download.php

if (!isset($_GET['file'])) {
    http_response_code(400);
    die("No file specified.");
}

$filename = basename($_GET['file']); // keamanan
$fullpath = __DIR__ . "/../uploads/invoice/" . $filename;

if (!file_exists($fullpath)) {
    http_response_code(404);
    die("File not found.");
}

$mime = mime_content_type($fullpath);
header("Content-Type: " . $mime);
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Length: " . filesize($fullpath));

readfile($fullpath);
exit;
