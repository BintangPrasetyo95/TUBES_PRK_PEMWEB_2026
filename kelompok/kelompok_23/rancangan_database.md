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

## Aturan Relasi Data Master

### Kategori dan Produk

Kolom products.category_id mengacu pada categories.id.
Satu kategori dapat digunakan oleh banyak produk.
Setiap produk wajib memiliki kategori yang terdaftar.

Relasi ini menggunakan ON DELETE RESTRICT.
Kategori yang masih digunakan oleh produk tidak dapat dihapus.
Produk perlu dipindahkan ke kategori lain atau dihapus terlebih dahulu.

ON UPDATE CASCADE membuat category_id pada produk mengikuti perubahan
id kategori jika id tersebut diperbarui.

### Pengguna dan Transaksi

Kolom transactions.user_id mengacu pada users.id.
Satu pengguna dapat tercatat sebagai kasir pada banyak transaksi.
Setiap transaksi wajib terhubung dengan pengguna yang terdaftar.

Relasi ini menggunakan ON DELETE RESTRICT.
Pengguna yang memiliki riwayat transaksi tidak dapat dihapus.
Untuk menonaktifkan akun tersebut, gunakan kolom is_active
agar riwayat transaksi tetap tersimpan.

ON UPDATE CASCADE membuat user_id pada transaksi mengikuti perubahan
id pengguna jika id tersebut diperbarui.

Tabel users tidak memiliki relasi langsung dengan categories atau products.
Hubungan pengguna dengan penjualan dicatat melalui tabel transactions.