-- ============================================================
-- MIGRATION: Sesuaikan database arifa dengan desain form terbaru
-- Tanggal: 2026-08-29
-- ============================================================

USE `if0_41035429_arifa`;

-- ------------------------------------------------------------
-- 1. Buat tabel DOKTER (baru)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `dokter` (
  `id_dokter` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_dokter` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. Modify tabel PASIEN
--    - Tambah tanggal_lahir, jenis_kelamin
--    - Rename hp -> no_telepon
-- ------------------------------------------------------------
ALTER TABLE `pasien`
  ADD COLUMN `tanggal_lahir` DATE NULL AFTER `nama`,
  ADD COLUMN `jenis_kelamin` ENUM('Laki-laki','Perempuan') NULL AFTER `tanggal_lahir`,
  CHANGE `hp` `no_telepon` VARCHAR(20) NULL;

-- ------------------------------------------------------------
-- 3. Modify tabel JADWAL_DOKTER
--    - Tambah id_dokter (FK ke dokter)
--    - Hapus nama_dokter (sudah ada di tabel dokter)
-- ------------------------------------------------------------
ALTER TABLE `jadwal_dokter`
  ADD COLUMN `id_dokter` INT NULL AFTER `id`,
  ADD CONSTRAINT `fk_jadwal_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter`(`id_dokter`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Hapus kolom nama_dokter setelah FK dibuat
ALTER TABLE `jadwal_dokter`
  DROP COLUMN `nama_dokter`;

-- ------------------------------------------------------------
-- 4. Modify tabel ANTRIAN
--    - Tambah id_dokter (FK ke dokter) untuk nama dokter di form
-- ------------------------------------------------------------
ALTER TABLE `antrian`
  ADD COLUMN `id_dokter` INT NULL AFTER `id_jadwal`,
  ADD CONSTRAINT `fk_antrian_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter`(`id_dokter`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ------------------------------------------------------------
-- 5. Modify tabel REKAM_MEDIS
--    - Tambah tindakan (sesuai kolom di form Laporan)
--    - Tambah id_dokter (FK ke dokter)
-- ------------------------------------------------------------
ALTER TABLE `rekam_medis`
  ADD COLUMN `id_dokter` INT NULL AFTER `id_pasien`,
  ADD COLUMN `tindakan` TEXT NULL AFTER `diagnosa`,
  ADD CONSTRAINT `fk_rekam_dokter` FOREIGN KEY (`id_dokter`) REFERENCES `dokter`(`id_dokter`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================
-- SELESAI
-- ============================================================
