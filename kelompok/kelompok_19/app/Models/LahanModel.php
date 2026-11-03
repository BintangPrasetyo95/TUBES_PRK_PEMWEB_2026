<?php
// app/Models/LahanModel.php
// Tugas: Query SQL murni (PDO) untuk tabel lahan.
// Diisi oleh: Faqih.

class LahanModel
{
    private $conn;
    private $table_name = "lahan";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM lahan
    // TODO: method readSingle($id) -> SELECT * FROM lahan WHERE id = ?
    // TODO: method create($data) -> INSERT INTO lahan (...)
    // TODO: method update($id, $data) -> UPDATE lahan SET ... WHERE id = ?
    // TODO: method delete($id) -> DELETE FROM lahan WHERE id = ?
}
