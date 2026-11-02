<?php
// app/Views/layouts/header.php
// Tugas: Header HTML + Bootstrap CSS + CSS kustom.
// Diisi oleh: anggota yang mengerjakan UI layout.

// TODO: Tambahkan meta tag, title dinamis, link CSS Bootstrap & style.css
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'KebunKu - Sistem Informasi Manajemen Agrikultur'; ?></title>
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar akan di-include di sini -->