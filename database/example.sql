BEGIN;
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
    status_tampil        VARCHAR(25),
    CONSTRAINT postingan_pkey PRIMARY KEY (id_postingan)
);

-- ------------------------------------------------------------------
-- komentar
-- ------------------------------------------------------------------
CREATE TABLE komentar (
    id_komentar          BIGSERIAL    NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    id_postingan         BIGINT       NOT NULL,
    isi_komentar         TEXT         NOT NULL,
    waktu_komentar       TIMESTAMP    NOT NULL,
    status_tampil        VARCHAR(25),
    CONSTRAINT komentar_pkey PRIMARY KEY (id_komentar)
);

-- ------------------------------------------------------------------
-- report_postingan
-- ------------------------------------------------------------------
CREATE TABLE report_postingan (
    id_report_postingan  BIGSERIAL    NOT NULL,
    id_postingan         BIGINT       NOT NULL,
    id_pengguna          BIGINT       NOT NULL,
    alasan_report        TEXT         NOT NULL,
    waktu_report         TIMESTAMP    NOT NULL,
    status_report        VARCHAR(255) NOT NULL,
    CONSTRAINT report_postingan_pkey PRIMARY KEY (id_report_postingan)
);

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
    alasan_report        TEXT         NOT NULL,
    waktu_report         TIMESTAMP    NOT NULL,
    status_report        VARCHAR(30)  NOT NULL,
    CONSTRAINT report_komentar_pkey PRIMARY KEY (id_report_komentar)
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

COMMIT;
