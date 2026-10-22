-- ==================================================================
-- database/dummy.sql  -- data uji coba PunyaSiapa
-- Semua akun memakai password: password123
-- Jalankan setelah database/example.sql:
--   psql -U postgres -d punyasiapa -f database/dummy.sql
-- Aman dijalankan berulang (users/kategori/postingan/komentar dijaga).
-- ==================================================================

BEGIN;

-- ------------------------------------------------------------------
-- Pengguna  (password semua: password123)
-- ------------------------------------------------------------------
INSERT INTO pengguna (nama, username, password, role, nomor_kontak) VALUES
    ('Admin PunyaSiapa', 'admin', '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'admin', '081100000001'),
    ('Budi Santoso',     'budi',  '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'user',  '081200000002'),
    ('Siti Rahma',       'siti',  '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'user',  '081300000003'),
    ('Andi Pratama',     'andi',  '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'user',  '081400000004'),
    ('Rina Marlina',     'rina',  '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'user',  '081500000005'),
    ('Dewi Lestari',     'dewi',  '$2y$12$EdueDEpguyEA7VPCuJf5JeYFkUmo2012lPk./g/g1csXEf9Qa.2/i', 'user',  '081600000006')
ON CONFLICT (username) DO NOTHING;

-- ------------------------------------------------------------------
-- Kategori (hanya bila belum ada)
-- ------------------------------------------------------------------
INSERT INTO kategori (nama_kategori)
SELECT v.nama
FROM (VALUES ('Elektronik'), ('Dokumen'), ('Aksesoris'), ('Pakaian'), ('Lainnya')) AS v(nama)
WHERE NOT EXISTS (SELECT 1 FROM kategori k WHERE k.nama_kategori = v.nama);

-- ------------------------------------------------------------------
-- Postingan (status_tampil default = 'tampil')
-- ------------------------------------------------------------------
INSERT INTO postingan
    (id_pengguna, id_kategori, nama_barang, deskripsi, foto,
     lokasi_kejadian, waktu_kejadian, jenis_postingan, status_barang, created_at)
SELECT u.id_pengguna, k.id_kategori, d.nama_barang, d.deskripsi, d.foto,
       d.lokasi, d.waktu, d.jenis, d.status, d.created
FROM (VALUES
    -- ---------- KEHILANGAN ----------
    ('budi', 'Elektronik', 'Laptop Lenovo ThinkPad T14',
     'Laptop hitam dengan stiker kampus di tutupnya. Terakhir dipakai di ruang kelas.',
     '/uploads/laptop-thinkpad.jpg', 'Gedung AH Lantai 2',
     DATE '2026-09-12', 'kehilangan', 'aktif', TIMESTAMP '2026-09-12 08:30:00'),

    ('siti', 'Dokumen', 'KTM atas nama Siti Rahma',
     'KTM jatuh di area parkir. Mohon dikembalikan ke pihak yang menemukan.',
     '/uploads/ktm-siti.jpg', 'Parkir Motor Gedung B',
     DATE '2026-09-14', 'kehilangan', 'aktif', TIMESTAMP '2026-09-14 10:15:00'),

    ('andi', 'Aksesoris', 'Dompet kulit cokelat',
     'Berisi KTP dan kartu mahasiswa. Hilang saat upacara bendera.',
     '/uploads/dompet-cokelat.jpg', 'Lapangan Utama',
     DATE '2026-09-15', 'kehilangan', 'selesai', TIMESTAMP '2026-09-15 07:45:00'),

    ('rina', 'Pakaian', 'Jaket hoodie abu-abu',
     'Jaket hoodie abu-abu ukuran L, ada bordir nama di bagian lengan.',
     '/uploads/hoodie-abu.jpg', 'Perpustakaan Lantai 3',
     DATE '2026-09-16', 'kehilangan', 'aktif', TIMESTAMP '2026-09-16 13:20:00'),

    ('budi', 'Elektronik', 'Earbuds TWS putih',
     'Earbuds putih beserta case-nya, tertinggal di meja kantin.',
     '/uploads/earbuds.jpg', 'Kantin Gedung C',
     DATE '2026-09-18', 'kehilangan', 'aktif', TIMESTAMP '2026-09-18 12:05:00'),

    ('dewi', 'Lainnya', 'Kunci motor Honda',
     'Kunci motor dengan gantungan beruang, hilang di area parkir.',
     '/uploads/kunci-motor.jpg', 'Parkir Motor Gedung A',
     DATE '2026-09-19', 'kehilangan', 'selesai', TIMESTAMP '2026-09-19 16:40:00'),

    ('andi', 'Aksesoris', 'Jam tangan Casio',
     'Jam tangan digital hitam, tali karet.',
     '/uploads/jam-casio.jpg', 'Gedung Teknik Sipil',
     DATE '2026-09-20', 'kehilangan', 'aktif', TIMESTAMP '2026-09-20 09:10:00'),

    -- ---------- DITEMUKAN ----------
    ('rina', 'Dokumen', 'Kartu ATM BNI ditemukan',
     'Ditemukan kartu ATM BNI atas nama orang lain di lantai lobi.',
     '/uploads/atm-bni.jpg', 'Lobi Gedung A',
     DATE '2026-09-17', 'ditemukan', 'aktif', TIMESTAMP '2026-09-17 09:00:00'),

    ('siti', 'Elektronik', 'Powerbank 10000mAh',
     'Powerbank warna hitam ditemukan tertinggal di colokan dinding kelas.',
     '/uploads/powerbank.jpg', 'Lab Komputer 2',
     DATE '2026-09-18', 'ditemukan', 'aktif', TIMESTAMP '2026-09-18 15:30:00'),

    ('dewi', 'Aksesoris', 'Botol minum Tupperware',
     'Botol minum biru, sudah diserahkan ke pos keamanan.',
     '/uploads/botol.jpg', 'Kantin Gedung C',
     DATE '2026-09-19', 'ditemukan', 'selesai', TIMESTAMP '2026-09-19 11:20:00'),

    ('budi', 'Pakaian', 'Syal merah',
     'Syal merah tertinggal di kursi kelas.',
     '/uploads/syal.jpg', 'Gedung AH Lantai 1',
     DATE '2026-09-20', 'ditemukan', 'aktif', TIMESTAMP '2026-09-20 14:00:00'),

    ('rina', 'Lainnya', 'Payung biru',
     'Payung biru ditemukan di rak sepatu.',
     '/uploads/payung.jpg', 'Musala Kampus',
     DATE '2026-09-21', 'ditemukan', 'aktif', TIMESTAMP '2026-09-21 08:05:00')
) AS d(username, kategori, nama_barang, deskripsi, foto, lokasi, waktu, jenis, status, created)
JOIN pengguna u ON u.username = d.username
JOIN kategori k ON k.nama_kategori = d.kategori
WHERE NOT EXISTS (
    SELECT 1 FROM postingan p
    WHERE p.id_pengguna = u.id_pengguna AND p.nama_barang = d.nama_barang
);

-- Tandai 2 postingan sebagai soft-deleted, untuk menguji filter "dihapus".
UPDATE postingan SET status_tampil = 'dihapus'
WHERE nama_barang IN ('Jam tangan Casio', 'Payung biru');

-- ------------------------------------------------------------------
-- Komentar (opsional, untuk pengujian berikutnya)
-- ------------------------------------------------------------------
INSERT INTO komentar (id_pengguna, id_postingan, isi_komentar, waktu_komentar, status_tampil)
SELECT u.id_pengguna, p.id_postingan, c.isi, c.waktu, 'tampil'
FROM (VALUES
    ('budi', 'Laptop Lenovo ThinkPad T14', 'Ciri tambahan: ada stiker Polinema di sudut kanan.', TIMESTAMP '2026-09-12 09:00:00'),
    ('siti', 'Laptop Lenovo ThinkPad T14', 'Semoga cepat ketemu ya.', TIMESTAMP '2026-09-12 09:30:00'),
    ('andi', 'Kartu ATM BNI ditemukan', 'Coba serahkan ke bank terdekat saja.', TIMESTAMP '2026-09-17 10:00:00'),
    ('rina', 'Powerbank 10000mAh', 'Sepertinya itu punya teman saya.', TIMESTAMP '2026-09-18 16:00:00')
) AS c(username, barang, isi, waktu)
JOIN pengguna u ON u.username = c.username
JOIN postingan p ON p.nama_barang = c.barang
WHERE NOT EXISTS (
    SELECT 1 FROM komentar x
    WHERE x.id_postingan = p.id_postingan AND x.isi_komentar = c.isi
);

COMMIT;
