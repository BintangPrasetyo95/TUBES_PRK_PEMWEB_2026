<?php
// app/Models/InventarisModel.php

class InventarisModel
{
    private $conn;
    private $table_name = "inventaris";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // TODO: method read() -> SELECT * FROM inventaris
    public function read()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    // TODO: method readSingle($id) -> SELECT * FROM inventaris WHERE id = ?
    public function readSingle($id)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    // TODO: method create($data) -> INSERT INTO inventaris (...)
    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nama, kategori, stok, satuan, harga_satuan) 
                  VALUES (:nama, :kategori, :stok, :satuan, :harga_satuan)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', htmlspecialchars(strip_tags($data['nama'])));
        $stmt->bindParam(':kategori', htmlspecialchars(strip_tags($data['kategori'])));
        $stmt->bindParam(':stok', htmlspecialchars(strip_tags($data['stok'])));
        $stmt->bindParam(':satuan', htmlspecialchars(strip_tags($data['satuan'])));
        $stmt->bindParam(':harga_satuan', htmlspecialchars(strip_tags($data['harga_satuan'])));

        return $stmt->execute();
    }
    // TODO: method update($id, $data) -> UPDATE inventaris SET ... WHERE id = ?
    public function update($id, $data)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama = :nama, kategori = :kategori, stok = :stok, satuan = :satuan, harga_satuan = :harga_satuan 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', htmlspecialchars(strip_tags($data['nama'])));
        $stmt->bindParam(':kategori', htmlspecialchars(strip_tags($data['kategori'])));
        $stmt->bindParam(':stok', htmlspecialchars(strip_tags($data['stok'])));
        $stmt->bindParam(':satuan', htmlspecialchars(strip_tags($data['satuan'])));
        $stmt->bindParam(':harga_satuan', htmlspecialchars(strip_tags($data['harga_satuan'])));
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    // TODO: method delete($id) -> DELETE FROM inventaris WHERE id = ?
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ==========================================
    // METHOD UNTUK TABEL TRANSAKSI INVENTARIS
    // ==========================================

    // method readTransaksi() -> SELECT JOIN transaksi dan master barang
    public function readTransaksi()
    {
        $query = "SELECT t.*, i.nama as nama_barang 
                  FROM transaksi_inventaris t 
                  JOIN inventaris i ON t.inventaris_id = i.id 
                  ORDER BY t.tanggal DESC, t.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // method createTransaksi($data) -> INSERT INTO transaksi_inventaris
    public function createTransaksi($data)
    {
        $query = "INSERT INTO transaksi_inventaris 
                  (inventaris_id, siklus_id, jenis, jumlah, tanggal) 
                  VALUES (:inventaris_id, :siklus_id, :jenis, :jumlah, :tanggal)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':inventaris_id', $data['inventaris_id'], PDO::PARAM_INT);
        $stmt->bindParam(':jenis', htmlspecialchars(strip_tags($data['jenis'])));
        $stmt->bindParam(':jumlah', htmlspecialchars(strip_tags($data['jumlah'])));
        $stmt->bindParam(':tanggal', htmlspecialchars(strip_tags($data['tanggal'])));

        // Logika khusus untuk membiarkan siklus_id berisi NULL saat barang "Masuk" (Kulakan)
        $siklus_id = !empty($data['siklus_id']) ? htmlspecialchars(strip_tags($data['siklus_id'])) : null;
        $stmt->bindParam(':siklus_id', $siklus_id, $siklus_id ? PDO::PARAM_INT : PDO::PARAM_NULL);

        return $stmt->execute();
    }
}