-- Migration (güvenli versiyon): Sadece eksik olanları ekler
-- MySQL 8+ destekler: ADD COLUMN IF NOT EXISTS

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('admin', 'firma') NOT NULL DEFAULT 'admin' AFTER name,
    ADD COLUMN IF NOT EXISTS alt_firma_id INT NULL AFTER role;

-- Foreign key zaten varsa düşür, sonra yeniden ekle
ALTER TABLE users DROP FOREIGN KEY IF EXISTS fk_users_altfirma;

ALTER TABLE users
    ADD CONSTRAINT fk_users_altfirma
    FOREIGN KEY (alt_firma_id) REFERENCES alt_firma(id) ON DELETE SET NULL;

-- bekleyen_istekler tablosu (zaten varsa atla)
CREATE TABLE IF NOT EXISTS bekleyen_istekler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alt_firma_id INT NOT NULL,
    istek_tipi ENUM('teslim', 'odeme') NOT NULL,
    is_id INT NULL,
    tarih DATE NOT NULL,
    tutar DECIMAL(15,2) NULL,
    aciklama TEXT,
    durum ENUM('beklemede', 'onaylandi', 'reddedildi') DEFAULT 'beklemede',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY fk_bi_altfirma (alt_firma_id) REFERENCES alt_firma(id) ON DELETE CASCADE,
    FOREIGN KEY fk_bi_is (is_id) REFERENCES yikama_isleri(id) ON DELETE CASCADE,
    INDEX idx_bi_altfirma (alt_firma_id),
    INDEX idx_bi_durum (durum)
) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
