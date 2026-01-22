<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../inc/auth.php';
require_login(); // и студентите трябва да са логнати

header('Content-Type: application/json; charset=utf-8');

$rows = db()->query("SELECT key_code, quote_text, source_text FROM citations ORDER BY id DESC")->fetchAll();
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
