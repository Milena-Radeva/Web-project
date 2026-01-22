<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__.'/inc/db.php';

echo "Connected OK<br>";
$rows = db()->query("SELECT email, role FROM users")->fetchAll();
echo "<pre>";
print_r($rows);
