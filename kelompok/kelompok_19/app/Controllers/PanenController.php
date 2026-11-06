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

    // ---------- Helper ----------
    private function sukses($message, $extra = [], $code = 200)
    {
        \App\Config\Database::response(
            array_merge(['status' => 'success', 'message' => $message], $extra),
            $code
        );
    }

    private function gagal($message, $code)
    {
        \App\Config\Database::response(['status' => 'error', 'message' => $message], $code);
    }

    private function tanggalValid($tanggal)
    {
        if (!is_string($tanggal) || $tanggal === '') {
            return false;
        }
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        return $d && $d->format('Y-m-d') === $tanggal;
    }

    private function param($nama)
    {
        $nilai = $_GET[$nama] ?? '';
        return $nilai !== '' ? $nilai : null;
    }

    private function bacaBody()
    {
        $body = json_decode(file_get_contents('php://input'), true);
        return is_array($body) ? $body : [];
    }

    // Validasi + bersihkan input. Mengembalikan [data_bersih, daftar_error]
    private function validasi($input)
    {
        $errors = [];

        $siklusId = filter_var($input['siklus_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$siklusId || $siklusId < 1) {
            $errors[] = 'Siklus wajib dipilih.';
        }

        $tanggal = $input['tanggal_panen'] ?? null;
        if (!$this->tanggalValid($tanggal)) {
            $errors[] = 'Tanggal panen tidak valid (format YYYY-MM-DD).';
        }

        $jumlah = $input['jumlah_kg'] ?? null;
        if (!is_numeric($jumlah) || (float) $jumlah <= 0 || (float) $jumlah > 99999999.99) {
            $errors[] = 'Jumlah panen harus berupa angka lebih dari 0.';
        }

        $kualitas = $input['kualitas'] ?? 'A';
        if (!in_array($kualitas, ['A', 'B', 'C', 'Reject'], true)) {
            $errors[] = 'Kualitas harus A, B, C, atau Reject.';
        }

        $catatan = trim((string) ($input['catatan'] ?? ''));

        $data = [
            'siklus_id'     => $siklusId,
            'tanggal_panen' => $tanggal,
            'jumlah_kg'     => is_numeric($jumlah) ? (float) $jumlah : 0,
            'kualitas'      => $kualitas,
            'catatan'       => $catatan !== '' ? $catatan : null,
        ];
        return [$data, $errors];
    }

    // ---------- Endpoint ----------

    // GET /api/panen?dari=YYYY-MM-DD&sampai=YYYY-MM-DD
    // GET /api/panen?mode=siklus  (isi dropdown form)
    public function index()
    {
        $dari = $this->param('dari');
        $sampai = $this->param('sampai');

        if ($dari !== null && !$this->tanggalValid($dari)) {
            $this->gagal('Format tanggal "dari" tidak valid.', 400);
        }
        if ($sampai !== null && !$this->tanggalValid($sampai)) {
            $this->gagal('Format tanggal "sampai" tidak valid.', 400);
        }

        try {
            if (($_GET['mode'] ?? '') === 'siklus') {
                $this->sukses('OK', ['data' => $this->model->getSiklusOptions()]);
            }

            $this->sukses('OK', [
                'data'      => $this->model->read($dari, $sampai),
                'ringkasan' => $this->model->getRingkasan($dari, $sampai),
            ]);
        } catch (PDOException $e) {
            error_log('PanenController::index ' . $e->getMessage());
            $this->gagal('Gagal mengambil data panen.', 500);
        }
    }

    // GET /api/panen/{id}
    public function show($id)
    {
        $id = (int) $id;
        try {
            $data = $this->model->readSingle($id);
        } catch (PDOException $e) {
            error_log('PanenController::show ' . $e->getMessage());
            $this->gagal('Gagal mengambil data panen.', 500);
        }

        if (!$data) {
            $this->gagal('Data panen tidak ditemukan', 404);
        }
        $this->sukses('OK', ['data' => $data]);
    }

    // POST /api/panen
    public function create()
    {
        [$data, $errors] = $this->validasi($this->bacaBody());
        if ($errors) {
            $this->gagal(implode(' ', $errors), 422);
        }

        try {
            $this->model->create($data);
            $this->sukses('Data panen berhasil ditambahkan', ['id' => $this->model->getLastId()], 201);
        } catch (PDOException $e) {
            error_log('PanenController::create ' . $e->getMessage());
            $this->gagal('Gagal menambahkan data panen. Pastikan siklus yang dipilih valid.', 500);
        }
    }

    // PUT /api/panen/{id}
    public function update($id)
    {
        $id = (int) $id;
        [$data, $errors] = $this->validasi($this->bacaBody());
        if ($errors) {
            $this->gagal(implode(' ', $errors), 422);
        }

        try {
            if (!$this->model->readSingle($id)) {
                $this->gagal('Data panen tidak ditemukan', 404);
            }
            $this->model->update($id, $data);
            $this->sukses('Data panen berhasil diperbarui');
        } catch (PDOException $e) {
            error_log('PanenController::update ' . $e->getMessage());
            $this->gagal('Gagal memperbarui data panen. Pastikan siklus yang dipilih valid.', 500);
        }
    }

    // DELETE /api/panen/{id}
    public function delete($id)
    {
        $id = (int) $id;
        try {
            if (!$this->model->delete($id)) {
                $this->gagal('Data panen tidak ditemukan', 404);
            }
            $this->sukses('Data panen berhasil dihapus');
        } catch (PDOException $e) {
            error_log('PanenController::delete ' . $e->getMessage());
            $this->gagal('Gagal menghapus data panen.', 500);
        }
    }
}