<?php

declare(strict_types=1);

// =============================================================================
// api/crear_token.php — Generador de Enlaces Efímeros VIP (Broker/Admin)
// Mandamiento #14: POST requiere autenticación real — Bearer JWT + rol.
// Mandamiento #5: Contrato de API Estricto.
//
// POST /api/crear_token.php
// Body: {
//   "catalogo_asignado": "aeronaves",
//   "nombre_cliente": "Juan Pérez",       // opcional
//   "expira_en_horas": 72,                // opcional — NULL = sin límite de tiempo
//   "max_aperturas": 3                    // opcional — NULL = sin límite de aperturas
// }
// Al menos uno de expira_en_horas / max_aperturas debe venir (Mandamiento UX:
// "a prueba de tontos" — un enlace sin ningún límite no es "efímero").
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth_middleware.php'; // expone $authPayload, exige Bearer JWT válido
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/token_validator.php';

requireRole(['broker', 'admin', 'super_admin'], $authPayload);

asfl_log('REQUEST', ['endpoint' => 'crear_token.php', 'broker' => $authPayload['sub'] ?? null]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

const CATALOGOS_VALIDOS = [
    'aeronaves', 'relojes', 'bienes-inmuebles', 'arte',
    'subastas-arte', 'relaciones-publicas', 'consejo-diplomatico',
];

try {
    $payload = json_decode(file_get_contents('php://input') ?: '[]', true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$catalogoAsignado = sanitize_string((string) ($payload['catalogo_asignado'] ?? ''), 50);
$nombreCliente     = isset($payload['nombre_cliente']) ? sanitize_string((string) $payload['nombre_cliente'], 150) : null;
$expiraEnHoras     = isset($payload['expira_en_horas']) ? sanitize_int($payload['expira_en_horas'], 0) : null;
$maxAperturas      = isset($payload['max_aperturas']) ? sanitize_int($payload['max_aperturas'], 0) : null;

if (!in_array($catalogoAsignado, CATALOGOS_VALIDOS, true)) {
    send_error('catalogo_asignado inválido. Valores permitidos: ' . implode(', ', CATALOGOS_VALIDOS), 422);
}

if (($expiraEnHoras === null || $expiraEnHoras <= 0) && ($maxAperturas === null || $maxAperturas <= 0)) {
    send_error('Debe especificar expira_en_horas y/o max_aperturas (mayor a 0). Un enlace VIP siempre debe caducar.', 422);
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $token    = bin2hex(random_bytes(32));
    $expiraAt = ($expiraEnHoras !== null && $expiraEnHoras > 0)
        ? (new DateTimeImmutable("+{$expiraEnHoras} hours"))->format('Y-m-d H:i:s')
        : null;

    $stmt = $pdo->prepare(
        'INSERT INTO tokens_acceso
            (token, id_broker, nombre_cliente, catalogo_asignado, expira_at, max_aperturas, estatus)
         VALUES
            (:token, :id_broker, :nombre_cliente, :catalogo_asignado, :expira_at, :max_aperturas, \'activo\')'
    );
    $stmt->execute([
        'token'             => $token,
        'id_broker'         => (int) $authPayload['sub'],
        'nombre_cliente'    => $nombreCliente,
        'catalogo_asignado' => $catalogoAsignado,
        'expira_at'         => $expiraAt,
        'max_aperturas'     => ($maxAperturas !== null && $maxAperturas > 0) ? $maxAperturas : null,
    ]);

    $env      = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $baseUrl  = rtrim((string) ($env['FRONTEND_URL'] ?? ''), '/');
    $urlAcceso = "{$baseUrl}/catalogo/{$catalogoAsignado}.php?token={$token}";

    asfl_log('RESPONSE', ['endpoint' => 'crear_token.php', 'catalogo' => $catalogoAsignado, 'token_creado' => true]);

    send_success('Enlace VIP generado.', [
        'token'             => $token,
        'url_acceso'        => $urlAcceso,
        'catalogo_asignado' => $catalogoAsignado,
        'nombre_cliente'    => $nombreCliente,
        'expira_at'         => $expiraAt,
        'max_aperturas'     => $maxAperturas,
    ], 201);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [crear_token.php] ' . $e->getMessage());
    send_error('Error al generar el enlace VIP.', 500);
}
