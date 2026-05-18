<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../models/PendingRequest.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/AuthMiddleware.php';

$user   = AuthMiddleware::requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts  = explode('/', trim($path, '/'));

$pendingId = null;
$action    = null;
$idx = array_search('pending', $parts);
if ($idx !== false && isset($parts[$idx + 1]) && is_numeric($parts[$idx + 1])) {
    $pendingId = (int) $parts[$idx + 1];
    if (isset($parts[$idx + 2])) $action = $parts[$idx + 2];
}

$model = new PendingRequest();

// GET /api/pending?alt_firma_id=X
// Admin: tümünü görür. Firma: sadece kendi isteklerini görür.
if ($method === 'GET' && $pendingId === null) {
    $altFirmaId = isset($_GET['alt_firma_id']) ? (int)$_GET['alt_firma_id'] : 0;
    if (!$altFirmaId) Response::error('alt_firma_id gerekli', 'VALIDATION_ERROR', 422);

    if ($user['role'] === 'firma' && (int)$user['alt_firma_id'] !== $altFirmaId) {
        Response::error('Bu firmaya erişim yetkiniz yok', 'FORBIDDEN', 403);
    }

    $list = $model->getByAltFirma($altFirmaId);
    Response::success(['requests' => $list]);
}

// POST /api/pending  (firma or admin)
if ($method === 'POST' && $pendingId === null) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['istek_tipi']) || empty($input['alt_firma_id']) || empty($input['tarih'])) {
        Response::error('Eksik alanlar: istek_tipi, alt_firma_id, tarih zorunlu', 'VALIDATION_ERROR', 422);
    }

    // firma kullanıcısı sadece kendi firması için istek gönderebilir
    if ($user['role'] === 'firma' && (int)$input['alt_firma_id'] !== (int)$user['alt_firma_id']) {
        Response::error('Bu firma için istek gönderme yetkiniz yok', 'FORBIDDEN', 403);
    }

    if ($input['istek_tipi'] === 'odeme' && empty($input['tutar'])) {
        Response::error('Ödeme tutarı zorunlu', 'VALIDATION_ERROR', 422);
    }

    $id = $model->create($input);
    if (!$id) Response::serverError('İstek kaydedilemedi');

    Response::success(['id' => $id], 'İsteğiniz admin onayına gönderildi', 201);
}

// POST /api/pending/{id}/approve  (admin only)
if ($method === 'POST' && $pendingId !== null && $action === 'approve') {
    AuthMiddleware::requireAdmin();
    $req = $model->getById($pendingId);
    if (!$req) Response::notFound('İstek bulunamadı');
    if ($req['durum'] !== 'beklemede') Response::error('Bu istek zaten işlenmiş', 'ALREADY_PROCESSED', 409);
    if (!$model->approve($pendingId)) Response::serverError('Onaylanamadı');
    Response::success(null, 'İstek onaylandı');
}

// POST /api/pending/{id}/reject  (admin only)
if ($method === 'POST' && $pendingId !== null && $action === 'reject') {
    AuthMiddleware::requireAdmin();
    $req = $model->getById($pendingId);
    if (!$req) Response::notFound('İstek bulunamadı');
    if ($req['durum'] !== 'beklemede') Response::error('Bu istek zaten işlenmiş', 'ALREADY_PROCESSED', 409);
    if (!$model->reject($pendingId)) Response::serverError('Reddedilemedi');
    Response::success(null, 'İstek reddedildi');
}

Response::error('Geçersiz endpoint', 'INVALID_ENDPOINT', 404);
