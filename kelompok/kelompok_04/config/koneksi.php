<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_log_ternak_k04";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

$is_https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

$protocol = $is_https ? "https://" : "http://";
$domain   = $_SERVER['HTTP_HOST'];

$doc_root    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dir_koneksi = str_replace('\\', '/', __DIR__);
$rel_path    = str_replace($doc_root, '', $dir_koneksi);
$base_path   = str_replace('/config', '', $rel_path);

$base_url = rtrim($protocol . $domain . $base_path, '/');
?>