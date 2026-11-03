<?php
// app/Models/PekerjaModel.php
// Tugas: Query SQL murni (PDO) untuk tabel pekerja.
// Diisi oleh: Devi Novita

class PekerjaModel
{
    private $conn;
    private $table_name = "pekerja";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM pekerja
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // TODO: method readSingle($id) -> SELECT * FROM pekerja WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // TODO: method create($data) -> INSERT INTO pekerja (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " (nama, peran, upah_harian, status) 
                  VALUES (:nama, :peran, :upah_harian, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':peran', $data['peran']);
        $stmt->bindParam(':upah_harian', $data['upah_harian']);
        $status = isset($data['status']) ? $data['status'] : 'Aktif';
        $stmt->bindParam(':status', $status);

        return $stmt->execute();
    }

    // TODO: method update($id, $data) -> UPDATE pekerja SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama = :nama, peran = :peran, upah_harian = :upah_harian, status = :status 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':peran', $data['peran']);
        $stmt->bindParam(':upah_harian', $data['upah_harian']);
        $stmt->bindParam(':status', $data['status']);

        return $stmt->execute();
    }

    // TODO: method delete($id) -> DELETE FROM pekerja WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getTotalUpah()
    {
        $query = "SELECT SUM(upah_harian) AS total_upah FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_upah'] ?? 0;
    }
}