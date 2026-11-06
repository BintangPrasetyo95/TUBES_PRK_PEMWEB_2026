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
    public function index()
    {
        $stmt = $this->model->read();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        \App\Config\Database::response($data);
    }

    // TODO: method show($id) - ambil satu lahan berdasarkan id
    public function show($id)
    {
        $stmt = $this->model->readSingle($id);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            \App\Config\Database::response($data);
        } else {
            \App\Config\Database::response(['message' => 'Data tidak ditemukan'], 404);
        }
    }

    // TODO: method create() - tambah data lahan baru
    public function create()
    {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->nama) && !empty($data->lokasi) && !empty($data->luas)) {
            if ($this->model->create($data)) {
                \App\Config\Database::response(['message' => 'Data berhasil ditambahkan'], 201);
            } else {
                \App\Config\Database::response(['message' => 'Gagal menambahkan data'], 500);
            }
        } else {
            \App\Config\Database::response(['message' => 'Data tidak lengkap'], 400);
        }
    }

    // TODO: method update($id) - ubah data lahan
    public function update($id)
    {
        $data = json_decode(file_get_contents("php://input"));
        if ($this->model->update($id, $data)) {
            \App\Config\Database::response(['message' => 'Data berhasil diupdate']);
        } else {
            \App\Config\Database::response(['message' => 'Gagal mengupdate data'], 500);
        }
    }

    // TODO: method delete($id) - hapus data lahan
    public function delete($id)
    {
        if ($this->model->delete($id)) {
            \App\Config\Database::response(['message' => 'Data berhasil dihapus']);
        } else {
            \App\Config\Database::response(['message' => 'Gagal menghapus data'], 500);
        }
    }
}
