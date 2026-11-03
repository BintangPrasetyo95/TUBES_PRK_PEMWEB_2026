<?php
// app/Controllers/InventarisController.php
// Tugas: Menerima request, memanggil Model Inventaris, mengembalikan JSON atau halaman web.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/InventarisModel.php";

class InventarisController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new InventarisModel($db);
    }

    // ==========================================
    // 1. ENDPOINT MASTER INVENTARIS
    // ==========================================
    // method index() - ambil semua data inventaris
    public function index()
    {
        $stmt = $this->model->read();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    }
    // method show($id) - ambil satu inventaris berdasarkan id
    public function show($id)
    {
        $data = $this->model->readSingle($id);
        
        if ($data) {
            echo json_encode(['status' => 'success', 'data' => $data]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan']);
        }
    }
    // method create() - tambah data inventaris baru
    public function create()
    {
        // Menangkap data JSON mentah yang dikirim oleh Fetch API dari browser
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->model->create($input)) {
            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil ditambahkan']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambah data barang']);
        }
    }
    // method update($id) - ubah data inventaris
    public function update($id)
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->model->update($id, $input)) {
            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil diperbarui']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui data barang']);
        }
    }
    // method delete($id) - hapus data inventaris
    public function delete($id)
    {
        if ($this->model->delete($id)) {
            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil dihapus']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data barang']);
        }
    }

    // ==========================================
    // 2. ENDPOINT TRANSAKSI INVENTARIS
    // ==========================================

    // method indexTransaksi() - ambil riwayat transaksi masuk/keluar
    public function indexTransaksi()
    {
        $stmt = $this->model->readTransaksi();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    // method createTransaksi() - catat pergerakan barang (kulakan/pemakaian)
    public function createTransaksi()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($this->model->createTransaksi($input)) {
            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dicatat']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mencatat transaksi']);
        }
    }
}
