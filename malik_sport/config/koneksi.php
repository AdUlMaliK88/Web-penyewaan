<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'malik_sport_db';

try {
    $koneksi = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $koneksi->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>