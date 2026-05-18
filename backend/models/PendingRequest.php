<?php

require_once __DIR__ . '/../config/database.php';

class PendingRequest
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getByAltFirma($altFirmaId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT bi.*, yi.aciklama as is_aciklama, yi.tarih as is_tarih, yi.toplam_tutar as is_tutar
                FROM bekleyen_istekler bi
                LEFT JOIN yikama_isleri yi ON bi.is_id = yi.id
                WHERE bi.alt_firma_id = :id
                ORDER BY bi.created_at DESC
            ");
            $stmt->execute(['id' => $altFirmaId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("PendingRequest getByAltFirma error: " . $e->getMessage());
            return [];
        }
    }

    public function getById($id)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM bekleyen_istekler WHERE id = :id LIMIT 1
            ");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: false;
        } catch (PDOException $e) {
            error_log("PendingRequest getById error: " . $e->getMessage());
            return false;
        }
    }

    public function create($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO bekleyen_istekler (alt_firma_id, istek_tipi, is_id, tarih, tutar, aciklama)
                VALUES (:alt_firma_id, :istek_tipi, :is_id, :tarih, :tutar, :aciklama)
            ");
            $stmt->execute([
                'alt_firma_id' => $data['alt_firma_id'],
                'istek_tipi'   => $data['istek_tipi'],
                'is_id'        => $data['is_id'] ?? null,
                'tarih'        => $data['tarih'],
                'tutar'        => $data['tutar'] ?? null,
                'aciklama'     => $data['aciklama'] ?? null,
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("PendingRequest create error: " . $e->getMessage());
            return false;
        }
    }

    public function approve($id)
    {
        try {
            $req = $this->getById($id);
            if (!$req) return false;

            $this->db->beginTransaction();

            if ($req['istek_tipi'] === 'teslim' && $req['is_id']) {
                $stmt = $this->db->prepare("
                    UPDATE yikama_isleri SET teslim_edildi = 1 WHERE id = :id
                ");
                $stmt->execute(['id' => $req['is_id']]);
            } elseif ($req['istek_tipi'] === 'odeme') {
                $stmt = $this->db->prepare("
                    INSERT INTO para_hareketleri (alt_firma_id, tarih, tutar, hareket_tipi, aciklama)
                    VALUES (:alt_firma_id, :tarih, :tutar, 'odeme', :aciklama)
                ");
                $stmt->execute([
                    'alt_firma_id' => $req['alt_firma_id'],
                    'tarih'        => $req['tarih'],
                    'tutar'        => $req['tutar'],
                    'aciklama'     => $req['aciklama'],
                ]);
            }

            $stmt = $this->db->prepare("
                UPDATE bekleyen_istekler SET durum = 'onaylandi' WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("PendingRequest approve error: " . $e->getMessage());
            return false;
        }
    }

    public function reject($id)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE bekleyen_istekler SET durum = 'reddedildi' WHERE id = :id
            ");
            $stmt->execute(['id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("PendingRequest reject error: " . $e->getMessage());
            return false;
        }
    }

    public function hasPendingTeslim($isId)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM bekleyen_istekler
                WHERE is_id = :is_id AND istek_tipi = 'teslim' AND durum = 'beklemede'
            ");
            $stmt->execute(['is_id' => $isId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}
