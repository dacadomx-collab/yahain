<?php

declare(strict_types=1);

// =============================================================================
// api/auth_login.php — Login JWT Enterprise (Access + Refresh + Device Binding)
// Endpoint: POST /api/auth_login.php
// Mandamiento #2: Seguridad Nivel Militar | Mandamiento #14: CORS ≠ Auth
//
// Schema real: tabla `usuarios` (ver knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md y
// knowledge/01_master_database_setup.sql) — roles: super_admin | admin | broker.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/input_sanitizer.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';
require_once __DIR__ . '/../validators/validator.php';

asfl_log('REQUEST', ['endpoint' => 'auth_login.php', 'method' => $_SERVER['REQUEST_METHOD']]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Método no permitido.', 405);
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    send_error('Payload JSON inválido.', 400);
}

$email    = sanitize_email((string) ($payload['email'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if (!is_valid_email($email)) {
    send_error('Correo electrónico inválido.', 422);
}
if ($password === '') {
    send_error('La contraseña es requerida.', 422);
}

// device_id: preferir el enviado por el cliente (UUID persistido en el
// dispositivo); si no llega, derivar uno desde IP + User-Agent.
$deviceId = sanitize_string((string) ($payload['device_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? '')));
if ($deviceId === '') {
    $deviceId = jwtMakeDeviceId();
}

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $stmt = $pdo->prepare('SELECT `id`, `email`, `password_hash`, `role` FROM `usuarios` WHERE `email` = :email AND `deleted_at` IS NULL LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($user === false || !password_verify($password, (string) $user['password_hash'])) {
        asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'error', 'reason' => 'credenciales_invalidas']);
        send_error('Credenciales inválidas.', 401);
    }

    $env        = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
    $secret     = (string) ($env['JWT_SECRET'] ?? '');
    $accessTtl  = (int) ($env['JWT_ACCESS_TTL'] ?? 900);
    $refreshTtl = (int) ($env['JWT_REFRESH_TTL'] ?? 2592000);

    if ($secret === '') {
        send_error('Configuración de seguridad incompleta.', 500);
    }

    $claims = [
        'sub'   => (int) $user['id'],
        'email' => (string) $user['email'],
        'role'  => (string) $user['role'],
    ];

    $accessToken  = jwtEncodeAccess($claims, $secret, $deviceId, $accessTtl);
    $refreshToken = jwtEncodeRefresh($claims, $secret, $deviceId, $refreshTtl);

    asfl_log('RESPONSE', ['endpoint' => 'auth_login.php', 'status' => 'success', 'user_id' => $user['id']]);

    send_success('Autenticación exitosa.', [
        'access_token'  => $accessToken,
        'refresh_token' => $refreshToken,
        'device_id'     => $deviceId,
        'expires_in'    => $accessTtl,
        'role'          => $user['role'],
    ]);

} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [auth_login] ' . $e->getMessage());
    send_error('Error interno al procesar el inicio de sesión.', 500);
}
