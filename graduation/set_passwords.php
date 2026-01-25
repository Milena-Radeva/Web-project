<?php
require_once __DIR__ . '/inc/db.php';

$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
$studHash  = password_hash('student123', PASSWORD_BCRYPT);

db()->prepare("UPDATE users SET pass_hash=? WHERE email='admin@uni.test'")
   ->execute([$adminHash]);

db()->prepare("UPDATE users SET pass_hash=? WHERE email='stud1@uni.test'")
   ->execute([$studHash]);

echo "Passwords reset OK";
