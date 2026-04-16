<?php

function notfound() {
    header('HTTP/1.0 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    print("\n\nNo such attachment ID.\n\n\n");
    exit(0);
}

function sanitize_header_value($value) {
    if (!is_string($value)) {
        return false;
    }

    $value = str_replace(array("\r", "\n"), '', $value);
    $value = trim($value);

    if ($value === '') {
        return false;
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
        return false;
    }

    return $value;
}

function header_token_pattern() {
    return "[A-Za-z0-9!#$%&'*+.^_`|~-]+";
}

function is_valid_content_type($value) {
    $token = header_token_pattern();
    return preg_match('/\A' . $token . '\/' . $token . '(?:\s*;\s*' . $token . '=(?:' . $token . '|"[^"]*"))*\z/', $value) === 1;
}

function is_valid_content_disposition($value) {
    $token = header_token_pattern();
    return preg_match('/\A(?:inline|attachment)(?:\s*;\s*' . $token . '=(?:' . $token . '|"[^"]*"))*\z/i', $value) === 1;
}

if (!isset($_REQUEST['id'])) {
    notfound();
}

$id = (int) $_REQUEST['id'];
$thousandsdir = (int) ($id / 1000);
$hundredsdir = (int) (($id % 1000) / 100);
$path = "attachments/$thousandsdir/$hundredsdir/$id";

if (!file_exists("$path/data")) {
    notfound();
}

$content_disposition = false;
if (file_exists("$path/content-disposition")) {
    $content_disposition = file_get_contents("$path/content-disposition");
}

$content_type = false;
if (file_exists("$path/content-type")) {
    $content_type = file_get_contents("$path/content-type");
}

$content_disposition = sanitize_header_value($content_disposition);
if ($content_disposition === false || !is_valid_content_disposition($content_disposition)) {
    $content_disposition = 'attachment';
}

$content_type = sanitize_header_value($content_type);
if ($content_type === false || !is_valid_content_type($content_type)) {
    $content_type = 'application/octet-stream';
}

header('X-Content-Type-Options: nosniff');
header("Content-Disposition: $content_disposition");
header("Content-Type: $content_type");

$flen = filesize("$path/data");
if ($flen !== false) {
    header("Content-Length: $flen");
}

if (@readfile("$path/data") === false) {
    notfound();
}

exit(0);

?>
