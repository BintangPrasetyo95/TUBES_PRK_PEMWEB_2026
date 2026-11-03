<?php
// app/Controllers/LahanController.php
// Tugas: Menerima request, memanggil Model Lahan, mengembalikan JSON atau halaman web.
// Diisi oleh: Faqih.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/LahanModel.php";

class LahanController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new LahanModel($db);
    }

    // TODO: method index() - ambil semua data lahan
    // TODO: method show($id) - ambil satu lahan berdasarkan id
    // TODO: method create() - tambah data lahan baru
    // TODO: method update($id) - ubah data lahan
    // TODO: method delete($id) - hapus data lahan
}
