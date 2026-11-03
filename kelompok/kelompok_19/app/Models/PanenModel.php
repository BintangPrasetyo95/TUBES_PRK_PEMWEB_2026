<?php
// app/Models/PanenModel.php
// Tugas: Query SQL murni (PDO) untuk tabel panen.
// Diisi oleh: Putri.

class PanenModel
{
    private $conn;
    private $table_name = "panen";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM panen
    // TODO: method readSingle($id) -> SELECT * FROM panen WHERE id = ?
    // TODO: method create($data) -> INSERT INTO panen (...)
    // TODO: method update($id, $data) -> UPDATE panen SET ... WHERE id = ?
    // TODO: method delete($id) -> DELETE FROM panen WHERE id = ?
}
