<?php

declare(strict_types=1);

// =============================================================================
// api/validar_token.php — Consumo de Token de Acceso VIP
// Mandamiento #5: Contrato de API Estricto — {"status","message","data"}.
// Público (no requiere JWT) — la validez del `token` ES el mecanismo de auth
// del Cliente VIP. Si viene un Bearer JWT de Super Admin, activa God Mode.
//
// POST /api/validar_token.php   Body: {"token":"...", "fingerprint":"..."}
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/token_validator.php';

asfl_log('REQUEST', ['endpoint' => 'validar_token.php', 'method' => $_SERVER['REQUEST_METHOD'] ?? '']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$token       = sanitize_string((string) ($payload['token'] ?? ''), 64);
$fingerprint = isset($payload['fingerprint']) ? sanitize_string((string) $payload['fingerprint'], 128) : null;

if ($token === '') {
    send_error('El token de acceso es requerido.', 422);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $env       = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $jwtSecret = (string) ($env['JWT_SECRET'] ?? '');

    $godModePayload = $jwtSecret !== '' ? intentarGodMode($jwtSecret) : null;
    $esGodMode      = $godModePayload !== null;
    $idUsuarioGod   = $esGodMode ? (int) ($godModePayload['sub'] ?? 0) : null;

    if (!$esGodMode && $fingerprint === null) {
        send_error('El fingerprint del dispositivo es requerido.', 422);
    }

    $resultado = consumirTokenAcceso($pdo, $token, $fingerprint, $esGodMode, $idUsuarioGod);

    if (!$resultado['valid']) {
        $mensajes = [
            'no_encontrado'            => 'Este enlace privado no existe.',
            'inactivo'                 => 'Este enlace privado ha sido revocado por su broker.',
            'expirado_tiempo'          => 'Este enlace privado ha expirado. Por favor contacte a su broker asignado para solicitar un nuevo acceso.',
            'expirado_aperturas'       => 'Este enlace privado alcanzó su límite de accesos. Por favor contacte a su broker asignado para solicitar un nuevo acceso.',
            'dispositivo_no_coincide'  => 'Este enlace ya está vinculado a otro dispositivo. Solicite un nuevo acceso a su broker.',
        ];
        $mensaje = $mensajes[$resultado['reason']] ?? 'Enlace privado inválido.';

        asfl_log('RESPONSE', ['endpoint' => 'validar_token.php', 'valid' => false, 'reason' => $resultado['reason']]);
        send_error($mensaje, 410, ['reason' => $resultado['reason']]);
    }

    asfl_log('RESPONSE', ['endpoint' => 'validar_token.php', 'valid' => true, 'god_mode' => $esGodMode]);
    send_success('Acceso VIP verificado.', [
        'catalogo_asignado' => $resultado['catalogo_asignado'],
        'nombre_cliente'    => $resultado['nombre_cliente'],
        'god_mode'          => $esGodMode,
    ]);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [validar_token.php] ' . $e->getMessage());
    send_error('Error al validar el acceso.', 500);
}
