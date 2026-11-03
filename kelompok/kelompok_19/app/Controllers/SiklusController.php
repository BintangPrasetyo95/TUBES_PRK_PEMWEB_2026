
<?php
// app/Controllers/SiklusController.php
// Tugas: Menerima request, memanggil Model Siklus, mengembalikan JSON atau halaman web.
// Diisi oleh: Hengky.

require_once __DIR__ . "/../Config/Database.php";
require_once __DIR__ . "/../Models/SiklusModel.php";

class SiklusController
{
    private $model;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $db = $database->getConnection();
        $this->model = new SiklusModel($db);
    }

    // TODO: method index() - ambil semua data siklus
    public function index()
    {
        try {
            $data = $this->model->read();

            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Data siklus berhasil diambil.',
                'data' => $data
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Gagal mengambil data siklus.'
            ]);
        }
    }

    // TODO: method show($id) - ambil satu siklus berdasarkan id
    public function show($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'ID siklus tidak valid.'
                ]);
                return;
            }

            $data = $this->model->readSingle($id);

            if (!$data) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Data siklus tidak ditemukan.'
                ]);
                return;
            }

            $this->sendResponse(200, [
                'success' => true,
                'message' => 'Data siklus berhasil ditemukan.',
                'data' => $data
            ]);
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Gagal mengambil detail siklus.'
            ]);
        }
    }

    // TODO: method create() - tambah data siklus baru
    public function create()
    {
        try {
            $data = $this->getRequestData();

            if (
                !isset($data['lahan_id']) ||
                !filter_var($data['lahan_id'], FILTER_VALIDATE_INT) ||
                $data['lahan_id'] <= 0 ||
                empty($data['nama_tanaman']) ||
                empty($data['tanggal_tanam'])
            ) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Lahan, nama tanaman, dan tanggal tanam wajib diisi.'
                ]);
                return;
            }

            $data['nama_tanaman'] = trim($data['nama_tanaman']);
            $data['status'] = $data['status'] ?? 'Aktif';

            $statusValid = ['Aktif', 'Panen', 'Selesai', 'Gagal'];

            if (!in_array($data['status'], $statusValid, true)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Status siklus tidak valid.'
                ]);
                return;
            }

            $result = $this->model->create($data);

            if ($result) {
                $this->sendResponse(201, [
                    'success' => true,
                    'message' => 'Siklus tanam berhasil ditambahkan.',
                    'id' => $result
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Gagal menambahkan siklus tanam.'
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Terjadi kesalahan database saat menambahkan siklus.'
            ]);
        }
    }

    // TODO: method update($id) - ubah data siklus
    public function update($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'ID siklus tidak valid.'
                ]);
                return;
            }

            $existingData = $this->model->readSingle($id);

            if (!$existingData) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Data siklus tidak ditemukan.'
                ]);
                return;
            }

            $data = $this->getRequestData();

            if (
                !isset($data['lahan_id']) ||
                !filter_var($data['lahan_id'], FILTER_VALIDATE_INT) ||
                $data['lahan_id'] <= 0 ||
                empty($data['nama_tanaman']) ||
                empty($data['tanggal_tanam']) ||
                !isset($data['status'])
            ) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Data siklus belum lengkap.'
                ]);
                return;
            }

            $data['nama_tanaman'] = trim($data['nama_tanaman']);

            $statusValid = ['Aktif', 'Panen', 'Selesai', 'Gagal'];

            if (!in_array($data['status'], $statusValid, true)) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'Status siklus tidak valid.'
                ]);
                return;
            }

            $result = $this->model->update($id, $data);

            if ($result) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Data siklus berhasil diperbarui.'
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Gagal memperbarui data siklus.'
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Terjadi kesalahan database saat memperbarui siklus.'
            ]);
        }
    }

    // TODO: method delete($id) - hapus data siklus
    public function delete($id)
    {
        try {
            if (!filter_var($id, FILTER_VALIDATE_INT) || $id <= 0) {
                $this->sendResponse(400, [
                    'success' => false,
                    'message' => 'ID siklus tidak valid.'
                ]);
                return;
            }

            $existingData = $this->model->readSingle($id);

            if (!$existingData) {
                $this->sendResponse(404, [
                    'success' => false,
                    'message' => 'Data siklus tidak ditemukan.'
                ]);
                return;
            }

            $result = $this->model->delete($id);

            if ($result) {
                $this->sendResponse(200, [
                    'success' => true,
                    'message' => 'Data siklus berhasil dihapus.'
                ]);
            } else {
                $this->sendResponse(500, [
                    'success' => false,
                    'message' => 'Gagal menghapus data siklus.'
                ]);
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            $this->sendResponse(500, [
                'success' => false,
                'message' => 'Terjadi kesalahan database saat menghapus siklus.'
            ]);
        }
    }

    private function getRequestData()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->sendResponse(400, [
                'success' => false,
                'message' => 'Format JSON tidak valid.'
            ]);
            exit;
        }

        return $data;
    }

    private function sendResponse($statusCode, $data)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
