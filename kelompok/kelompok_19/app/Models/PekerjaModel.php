<?php
// app/Models/PekerjaModel.php
// Tugas: Query SQL murni (PDO) untuk tabel pekerja.
// Diisi oleh: Devi.

class PekerjaModel
{
    private $conn;
    private $table_name = "pekerja";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM pekerja
    // TODO: method readSingle($id) -> SELECT * FROM pekerja WHERE id = ?
    // TODO: method create($data) -> INSERT INTO pekerja (...)
    // TODO: method update($id, $data) -> UPDATE pekerja SET ... WHERE id = ?
    // TODO: method delete($id) -> DELETE FROM pekerja WHERE id = ?
}
