BEGIN;

-- ------------------------------------------------------------------
-- pgcrypto: dipakai crypt()/gen_salt() untuk hash password bcrypt
-- di procedure tambah_pengguna(). Butuh role superuser.
-- ------------------------------------------------------------------
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ------------------------------------------------------------------
-- pengguna
-- ------------------------------------------------------------------
CREATE TABLE pengguna (
    id_pengguna          BIGSERIAL    NOT NULL,
    nama                 VARCHAR(100) NOT NULL,
    username             VARCHAR(50)  NOT NULL,
    password             VARCHAR(255) NOT NULL,
    role                 VARCHAR(20)  NOT NULL,
    nomor_kontak         VARCHAR(20)  NOT NULL,
    foto_profil          VARCHAR(255),
    CONSTRAINT pengguna_pkey PRIMARY KEY (id_pengguna),
    CONSTRAINT pengguna_username_key UNIQUE (username)
);

-- ------------------------------------------------------------------
-- Validasi insert pengguna
--
-- 1. Trigger  : menjaga SEMUA insert (termasuk INSERT manual), dan
--               menolak password yang belum berbentuk hash bcrypt.
-- 2. Procedure: satu-satunya cara resmi menambah user manual; menerima
--               password plaintext, memvalidasi, lalu meng-hash-nya.
--
-- Contoh:
--   CALL tambah_pengguna('Budi Santoso', 'budi2', 'rahasia123',
--                        'user', '081234567890');
-- ------------------------------------------------------------------
CREATE OR REPLACE FUNCTION validasi_pengguna() RETURNS TRIGGER
LANGUAGE plpgsql AS $$
BEGIN
    NEW.nama         := btrim(coalesce(NEW.nama, ''));
    NEW.username     := btrim(coalesce(NEW.username, ''));
    NEW.nomor_kontak := btrim(coalesce(NEW.nomor_kontak, ''));
    NEW.foto_profil  := nullif(btrim(coalesce(NEW.foto_profil, '')), '');

    IF NEW.nama = '' THEN
        RAISE EXCEPTION 'Nama wajib diisi.' USING ERRCODE = '22023';
    END IF;

    IF NEW.username !~ '^[A-Za-z0-9_.]{3,50}$' THEN
        RAISE EXCEPTION 'Username 3-50 karakter, hanya huruf, angka, titik, underscore.'
            USING ERRCODE = '22023';
    END IF;

    IF NEW.role IS NULL OR NEW.role NOT IN ('user', 'admin') THEN
        RAISE EXCEPTION 'Role harus "user" atau "admin".' USING ERRCODE = '22023';
    END IF;

    IF NEW.nomor_kontak !~ '^\+?[0-9]{8,20}$' THEN
        RAISE EXCEPTION 'Nomor kontak tidak valid.' USING ERRCODE = '22023';
    END IF;

    IF NEW.password IS NULL
       OR NEW.password !~ '^\$2[aby]\$[0-9]{2}\$[./A-Za-z0-9]{53}$' THEN
        RAISE EXCEPTION 'Password harus hash bcrypt. Pakai: CALL tambah_pengguna(...).'
            USING ERRCODE = '22023';
    END IF;

    RETURN NEW;
END;
$$;

CREATE TRIGGER trg_pengguna_validasi
    BEFORE INSERT ON pengguna
    FOR EACH ROW EXECUTE FUNCTION validasi_pengguna();

CREATE OR REPLACE PROCEDURE tambah_pengguna(
    IN  p_nama         VARCHAR,
    IN  p_username     VARCHAR,
    IN  p_password     TEXT,
    IN  p_role         VARCHAR DEFAULT 'user',
    IN  p_nomor_kontak VARCHAR DEFAULT NULL,
    IN  p_foto_profil  VARCHAR DEFAULT NULL,
    OUT o_id_pengguna  BIGINT
)
LANGUAGE plpgsql AS $$
DECLARE
    v_nama     VARCHAR := btrim(coalesce(p_nama, ''));
    v_username VARCHAR := btrim(coalesce(p_username, ''));
    v_kontak   VARCHAR := btrim(coalesce(p_nomor_kontak, ''));
    v_foto     VARCHAR := nullif(btrim(coalesce(p_foto_profil, '')), '');
BEGIN
    IF v_nama = '' THEN
        RAISE EXCEPTION 'Nama wajib diisi.' USING ERRCODE = '22023';
    END IF;
    IF char_length(v_nama) > 100 THEN
        RAISE EXCEPTION 'Nama maksimal 100 karakter.' USING ERRCODE = '22023';
    END IF;

    IF v_username !~ '^[A-Za-z0-9_.]{3,50}$' THEN
        RAISE EXCEPTION 'Username 3-50 karakter, hanya huruf, angka, titik, underscore.'
            USING ERRCODE = '22023';
    END IF;
    IF EXISTS (SELECT 1 FROM pengguna WHERE username = v_username) THEN
        RAISE EXCEPTION 'Username "%" sudah dipakai.', v_username USING ERRCODE = '23505';
    END IF;

    IF p_password IS NULL OR char_length(p_password) < 8 THEN
        RAISE EXCEPTION 'Password minimal 8 karakter.' USING ERRCODE = '22023';
    END IF;
    IF octet_length(p_password) > 72 THEN
        RAISE EXCEPTION 'Password maksimal 72 byte (batas bcrypt).' USING ERRCODE = '22023';
    END IF;

    IF coalesce(p_role, 'user') NOT IN ('user', 'admin') THEN
        RAISE EXCEPTION 'Role harus "user" atau "admin".' USING ERRCODE = '22023';
    END IF;

    IF v_kontak !~ '^\+?[0-9]{8,20}$' THEN
        RAISE EXCEPTION 'Nomor kontak tidak valid.' USING ERRCODE = '22023';
    END IF;

    IF v_foto IS NOT NULL AND char_length(v_foto) > 255 THEN
        RAISE EXCEPTION 'Path foto maksimal 255 karakter.' USING ERRCODE = '22023';
    END IF;

    INSERT INTO pengguna (nama, username, password, role, nomor_kontak, foto_profil)
    VALUES (v_nama, v_username,
            crypt(p_password, gen_salt('bf', 10)),   -- bcrypt cost 10
            coalesce(p_role, 'user'), v_kontak, v_foto)
    RETURNING id_pengguna INTO o_id_pengguna;
END;
$$;

-- ------------------------------------------------------------------
-- kategori
-- ------------------------------------------------------------------
CREATE TABLE kategori (
    id_kategori          BIGSERIAL    NOT NULL,
    nama_kategori        VARCHAR(255) NOT NULL,
    CONSTRAINT kategori_pkey PRIMARY KEY (id_kategori)
);



-- ------------------------------------------------------------------
-- postingan
-- ------------------------------------------------------------------
CREATE TYPE stts_tampil AS ENUM ('tampil', 'disembunyikan', 'dihapus');
CREATE TABLE postingan (
    id_postingan         BIGSERIAL    NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    id_kategori          BIGINT       NOT NULL,
    nama_barang          VARCHAR(100) NOT NULL,
    deskripsi            TEXT         NOT NULL,
    foto                 VARCHAR(255) NOT NULL,
    lokasi_kejadian      VARCHAR(100) NOT NULL,
    waktu_kejadian       DATE         NOT NULL,
    jenis_postingan      VARCHAR(20)  NOT NULL,
    status_barang        VARCHAR(20)  NOT NULL,
    created_at           TIMESTAMP    NOT NULL,
    status_tampil        stts_tampil NOT NULL DEFAULT 'tampil',
    CONSTRAINT postingan_pkey PRIMARY KEY (id_postingan)
);

-- ------------------------------------------------------------------
-- FTS: Full-Text Search postingan
-- ------------------------------------------------------------------
ALTER TABLE postingan
    ADD COLUMN IF NOT EXISTS search_vector TSVECTOR
        GENERATED ALWAYS AS (
            to_tsvector('simple',
                coalesce(nama_barang, '') || ' ' ||
                coalesce(deskripsi, '')   || ' ' ||
                coalesce(lokasi_kejadian, '')
            )
        ) STORED;

CREATE INDEX IF NOT EXISTS idx_postingan_fts
    ON postingan USING GIN (search_vector);

-- ------------------------------------------------------------------
-- komentar
-- ------------------------------------------------------------------
CREATE TABLE komentar (
    id_komentar          BIGSERIAL    NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    id_postingan         BIGINT       NOT NULL,
    isi_komentar         TEXT         NOT NULL,
    waktu_komentar       TIMESTAMP    NOT NULL,
    status_tampil        stts_tampil NOT NULL DEFAULT 'tampil',
    CONSTRAINT komentar_pkey PRIMARY KEY (id_komentar)
);

-- ------------------------------------------------------------------
-- report_postingan
-- ------------------------------------------------------------------
CREATE TABLE report_postingan (
    id_report_postingan  BIGSERIAL    NOT NULL,
    id_postingan         BIGINT       NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    kategori_report      VARCHAR(30)  NOT NULL DEFAULT 'other',
    alasan_report        TEXT         NOT NULL DEFAULT '',
    waktu_report         TIMESTAMP    NOT NULL DEFAULT NOW(),
    status_report        VARCHAR(30)  NOT NULL DEFAULT 'pending',
    reviewed_by          BIGINT,
    reviewed_at          TIMESTAMP,
    catatan_admin        TEXT,
    updated_at           TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT report_postingan_pkey PRIMARY KEY (id_report_postingan),
    CONSTRAINT chk_report_postingan_kategori
        CHECK (kategori_report IN ('spam', 'fraud', 'harassment', 'misleading', 'privacy', 'inappropriate', 'other')),
    CONSTRAINT chk_report_postingan_status
        CHECK (status_report IN ('pending', 'reviewing', 'resolved', 'rejected'))
);

CREATE UNIQUE INDEX uq_report_postingan_aktif
    ON report_postingan (id_pengguna, id_postingan)
    WHERE status_report IN ('pending', 'reviewing');

-- ------------------------------------------------------------------
-- claim
-- ------------------------------------------------------------------
CREATE TABLE claim (
    id_claim             BIGSERIAL    NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    id_postingan         BIGINT       NOT NULL,
    bukti_claim          TEXT         NOT NULL,
    status_claim         VARCHAR(255) NOT NULL,
    waktu_claim          TIMESTAMP    NOT NULL,
    foto_bukti           VARCHAR(255),
    CONSTRAINT claim_pkey PRIMARY KEY (id_claim)
);

-- ------------------------------------------------------------------
-- report_komentar
-- ------------------------------------------------------------------
CREATE TABLE report_komentar (
    id_report_komentar   BIGSERIAL    NOT NULL,
    id_komentar          BIGINT       NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    kategori_report      VARCHAR(30)  NOT NULL DEFAULT 'other',
    alasan_report        TEXT         NOT NULL DEFAULT '',
    waktu_report         TIMESTAMP    NOT NULL DEFAULT NOW(),
    status_report        VARCHAR(30)  NOT NULL DEFAULT 'pending',
    reviewed_by          BIGINT,
    reviewed_at          TIMESTAMP,
    catatan_admin        TEXT,
    updated_at           TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT report_komentar_pkey PRIMARY KEY (id_report_komentar),
    CONSTRAINT chk_report_komentar_kategori
        CHECK (kategori_report IN ('spam', 'fraud', 'harassment', 'misleading', 'privacy', 'inappropriate', 'other')),
    CONSTRAINT chk_report_komentar_status
        CHECK (status_report IN ('pending', 'reviewing', 'resolved', 'rejected'))
);

CREATE UNIQUE INDEX uq_report_komentar_aktif
    ON report_komentar (id_pengguna, id_komentar)
    WHERE status_report IN ('pending', 'reviewing');

-- ------------------------------------------------------------------
-- log_moderasi (Audit Trail tindakan Admin)
-- ------------------------------------------------------------------
CREATE TABLE log_moderasi (
    id_log               BIGSERIAL    NOT NULL,
    id_admin             BIGINT       NOT NULL,
    jenis_target         VARCHAR(20)  NOT NULL,
    id_target            BIGINT       NOT NULL,
    jenis_aksi           VARCHAR(30)  NOT NULL,
    alasan               TEXT,
    status_sebelum       VARCHAR(30),
    status_sesudah       VARCHAR(30),
    waktu_aksi           TIMESTAMP    NOT NULL DEFAULT NOW(),
    CONSTRAINT log_moderasi_pkey PRIMARY KEY (id_log),
    CONSTRAINT chk_log_moderasi_target CHECK (jenis_target IN ('postingan', 'komentar')),
    CONSTRAINT chk_log_moderasi_aksi   CHECK (jenis_aksi IN ('review', 'reject', 'hide', 'restore'))
);

-- ------------------------------------------------------------------
-- Referential integrity
-- ------------------------------------------------------------------
ALTER TABLE postingan
    ADD CONSTRAINT fk_postingan_id_pengguna
    FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE postingan
    ADD CONSTRAINT fk_postingan_id_kategori
    FOREIGN KEY (id_kategori) REFERENCES kategori (id_kategori) ON DELETE RESTRICT;

ALTER TABLE komentar
    ADD CONSTRAINT fk_komentar_id_pengguna
    FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE komentar
    ADD CONSTRAINT fk_komentar_id_postingan
    FOREIGN KEY (id_postingan) REFERENCES postingan (id_postingan) ON DELETE RESTRICT;

ALTER TABLE report_postingan
    ADD CONSTRAINT fk_report_postingan_id_postingan
    FOREIGN KEY (id_postingan) REFERENCES postingan (id_postingan) ON DELETE RESTRICT;

ALTER TABLE report_postingan
    ADD CONSTRAINT fk_report_postingan_id_pengguna
    FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE claim
    ADD CONSTRAINT fk_claim_id_pengguna
    FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE claim
    ADD CONSTRAINT fk_claim_id_postingan
    FOREIGN KEY (id_postingan) REFERENCES postingan (id_postingan) ON DELETE RESTRICT;

ALTER TABLE report_komentar
    ADD CONSTRAINT fk_report_komentar_id_komentar
    FOREIGN KEY (id_komentar) REFERENCES komentar (id_komentar) ON DELETE RESTRICT;

ALTER TABLE report_komentar
    ADD CONSTRAINT fk_report_komentar_id_pengguna
    FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE report_postingan
    ADD CONSTRAINT fk_report_postingan_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE report_komentar
    ADD CONSTRAINT fk_report_komentar_reviewed_by
    FOREIGN KEY (reviewed_by) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;

ALTER TABLE log_moderasi
    ADD CONSTRAINT fk_log_moderasi_id_admin
    FOREIGN KEY (id_admin) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT;
-- ------------------------------------------------------------------
-- View baca: postingan + nama kategori + nama pembuat
-- Dipakai oleh app/models/Postingan.php (all, allByUser, find).
-- ------------------------------------------------------------------
CREATE OR REPLACE VIEW v_postingan AS
SELECT
    p.id_postingan,
    p.id_pengguna,
    p.id_kategori,
    p.nama_barang,
    p.deskripsi,
    p.foto,
    p.lokasi_kejadian,
    p.waktu_kejadian,
    p.jenis_postingan,
    p.status_barang,
    p.created_at,
    p.status_tampil,
    p.search_vector,
    k.nama_kategori,
    u.nama     AS nama_pembuat,
    u.username AS username_pembuat
FROM postingan p
JOIN kategori k ON k.id_kategori = p.id_kategori
JOIN pengguna u ON u.id_pengguna = p.id_pengguna;

-- ------------------------------------------------------------------
-- View baca: gabungan report_postingan + report_komentar untuk Admin
-- dan halaman Laporan Saya (dipakai oleh app/models/Report.php).
-- ------------------------------------------------------------------
CREATE OR REPLACE VIEW v_laporan_moderasi AS
SELECT
    'postingan'::VARCHAR(20)        AS jenis_target,
    r.id_report_postingan           AS id_report,
    r.id_postingan                  AS id_target,
    p.id_postingan                  AS id_postingan_induk,
    p.jenis_postingan               AS jenis_postingan,
    p.nama_barang::TEXT             AS ringkasan_target,
    p.status_tampil                 AS status_tampil_target,
    p.id_pengguna                   AS id_pemilik_target,
    pemilik.nama                    AS nama_pemilik_target,
    r.id_pengguna                   AS id_pelapor,
    pelapor.nama                    AS nama_pelapor,
    r.kategori_report,
    r.alasan_report,
    r.status_report::VARCHAR(30)    AS status_report,
    r.waktu_report,
    r.reviewed_by,
    adm.nama                        AS nama_admin,
    r.reviewed_at,
    r.catatan_admin,
    r.updated_at
FROM report_postingan r
JOIN postingan p        ON p.id_postingan = r.id_postingan
JOIN pengguna pemilik   ON pemilik.id_pengguna = p.id_pengguna
JOIN pengguna pelapor   ON pelapor.id_pengguna = r.id_pengguna
LEFT JOIN pengguna adm  ON adm.id_pengguna = r.reviewed_by

UNION ALL

SELECT
    'komentar'::VARCHAR(20)         AS jenis_target,
    r.id_report_komentar            AS id_report,
    r.id_komentar                   AS id_target,
    k.id_postingan                  AS id_postingan_induk,
    p.jenis_postingan               AS jenis_postingan,
    LEFT(k.isi_komentar, 150)::TEXT AS ringkasan_target,
    k.status_tampil                 AS status_tampil_target,
    k.id_pengguna                   AS id_pemilik_target,
    pemilik.nama                    AS nama_pemilik_target,
    r.id_pengguna                   AS id_pelapor,
    pelapor.nama                    AS nama_pelapor,
    r.kategori_report,
    r.alasan_report,
    r.status_report::VARCHAR(30)    AS status_report,
    r.waktu_report,
    r.reviewed_by,
    adm.nama                        AS nama_admin,
    r.reviewed_at,
    r.catatan_admin,
    r.updated_at
FROM report_komentar r
JOIN komentar k         ON k.id_komentar = r.id_komentar
JOIN postingan p        ON p.id_postingan = k.id_postingan
JOIN pengguna pemilik   ON pemilik.id_pengguna = k.id_pengguna
JOIN pengguna pelapor   ON pelapor.id_pengguna = r.id_pengguna
LEFT JOIN pengguna adm  ON adm.id_pengguna = r.reviewed_by;

COMMIT;
