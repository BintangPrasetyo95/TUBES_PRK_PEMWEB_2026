<?php
// app/Controllers/PekerjaController.php
// Tugas: Menerima request, memanggil Model Pekerja, mengembalikan JSON atau halaman web.
// Diisi oleh: Devi.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/PekerjaModel.php";

class PekerjaController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new PekerjaModel($db);
    }

    // TODO: method index() - ambil semua data pekerja
    // TODO: method show($id) - ambil satu pekerja berdasarkan id
    // TODO: method create() - tambah data pekerja baru
    // TODO: method update($id) - ubah data pekerja
    // TODO: method delete($id) - hapus data pekerja
}
