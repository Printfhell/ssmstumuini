<?php
$servername = "localhost";
$username = "root";  // Default XAMPP
$password = "";
$dbname = "barangay_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
session_start();  // Start session for login
?>