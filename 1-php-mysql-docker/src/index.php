<?php
$dsn = 'mysql:host=db;dbname=mydb;charset=utf8';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO($dsn, $username, $password);
    echo "MySQL connection successful!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
