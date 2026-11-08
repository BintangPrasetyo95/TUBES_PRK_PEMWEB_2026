# Database FEBMart

FEBMart menggunakan database MySQL bernama febmart.
Database ini menyimpan data akun, produk, dan penjualan.

## Tabel yang Digunakan

1. users: menyimpan akun admin dan kasir.
2. categories: menyimpan kategori produk.
3. products: menyimpan data produk, harga, stok, dan batas minimum stok.
4. transactions: menyimpan transaksi beserta pembayarannya.
5. transaction_details: menyimpan barang yang dibeli dalam setiap transaksi.

## Hubungan Antartabel

Setiap produk memiliki kategori melalui kolom category_id.
Kategori yang sama dapat digunakan oleh beberapa produk.

Setiap transaksi mencatat pengguna yang melayani melalui kolom user_id.
Daftar barang yang dibeli disimpan di transaction_details.

Kolom transaction_id menghubungkan detail barang dengan transaksinya.
Kolom product_id menghubungkan detail barang dengan data produk.

## Data untuk Dashboard

Dashboard akan menampilkan jumlah jenis produk, jumlah transaksi
hari ini, omzet hari ini, dan daftar produk yang stoknya menipis.

Jumlah produk dihitung dari banyaknya data pada tabel products,
bukan dari penjumlahan stok.

Jumlah transaksi dan omzet harian mengikuti kolom transaction_date.
Omzet dihitung dari kolom total. Jika belum ada transaksi,
dashboard menampilkan nilai nol.

Produk masuk daftar stok menipis jika stock sudah mencapai
atau berada di bawah min_stock. Jika stock bernilai nol,
produk ditandai sebagai habis.