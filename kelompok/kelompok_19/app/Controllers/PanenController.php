<?php
// app/Controllers/PanenController.php
// Tugas: Menerima request, memanggil Model Panen, mengembalikan JSON atau halaman web.
// Diisi oleh: Putri.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/PanenModel.php";

class PanenController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new PanenModel($db);
    }

    // TODO: method index() - ambil semua data panen
    // TODO: method show($id) - ambil satu panen berdasarkan id
    // TODO: method create() - tambah data panen baru
    // TODO: method update($id) - ubah data panen
    // TODO: method delete($id) - hapus data panen
}
