<?php

$host = "localhost";
$db_username = "root";
<<<<<<< HEAD
$db_password = "";
=======
$db_password = "1234";
>>>>>>> ec6e2a9d4e2eb89c5c81c4322fa7ed5ea1e535de
$db_name = "febmart";

$conn = new mysqli($host, $db_username, $db_password, $db_name);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");