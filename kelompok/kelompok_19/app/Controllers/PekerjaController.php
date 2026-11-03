<?php
// app/Controllers/PekerjaController.php
// Tugas: Menerima request, memanggil Model Pekerja, mengembalikan JSON atau halaman web.
// Diisi oleh: Devi Novita

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

    public function index()
    {
        header('Content-Type: application/json');
        $data = $this->model->read();
        $totalUpah = $this->model->getTotalUpah();
        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'total_upah' => $totalUpah
        ]);
    }

    public function create()
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if ($this->model->create($input)) {
            echo json_encode(['status' => 'success', 'message' => 'Data pekerja berhasil ditambahkan']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan data pekerja']);
        }
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        if ($this->model->update($id, $input)) {
            echo json_encode(['status' => 'success', 'message' => 'Data pekerja berhasil diperbarui']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data pekerja']);
        }
    }

    public function delete($id)
    {
        header('Content-Type: application/json');
        if ($this->model->delete($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Data pekerja berhasil dihapus']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data pekerja']);
        }
    }

    // TODO: method index() - ambil semua data pekerja
    // TODO: method show($id) - ambil satu pekerja berdasarkan id
    // TODO: method create() - tambah data pekerja baru
    // TODO: method update($id) - ubah data pekerja
    // TODO: method delete($id) - hapus data pekerja
}
