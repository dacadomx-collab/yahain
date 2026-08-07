<?php

declare(strict_types=1);

// =============================================================================
// helpers/token_validator.php — Motor de Validación de Tokens VIP
// Fuente única de verdad para consumir un token efímero de `tokens_acceso`,
// usada tanto por api/validar_token.php (llamada JS) como por
// catalogo/*.php (chequeo previo server-side, sin efectos secundarios).
//
// Mandamiento #9: `activity_logs` es INMUTABLE — este helper solo hace INSERT
// sobre ella, nunca UPDATE/DELETE (los triggers de BD lo rechazarían igual).
// =============================================================================

/**
 * Verifica el estado de un token SIN consumirlo (no incrementa aperturas,
 * no vincula fingerprint, no escribe en activity_logs). Útil para el render
 * inicial server-side de una vista — decide si mostrar contenido o la
 * pantalla de "enlace expirado" antes de que el JS del cliente confirme
 * el fingerprint.
 *
 * @return array{valid:bool,reason:string,token_row:array<string,mixed>|null}
 */
function inspeccionarTokenAcceso(PDO $pdo, string $token): array
{
    $stmt = $pdo->prepare('SELECT * FROM tokens_acceso WHERE token = :token LIMIT 1');
    $stmt->execute(['token' => $token]);
    $row = $stmt->fetch();

    if ($row === false) {
        return ['valid' => false, 'reason' => 'no_encontrado', 'token_row' => null];
    }

    if ($row['estatus'] !== 'activo') {
        return ['valid' => false, 'reason' => 'inactivo', 'token_row' => $row];
    }

    if ($row['expira_at'] !== null && strtotime((string) $row['expira_at']) < time()) {
        return ['valid' => false, 'reason' => 'expirado_tiempo', 'token_row' => $row];
    }

    if ($row['max_aperturas'] !== null && (int) $row['aperturas_actuales'] >= (int) $row['max_aperturas']) {
        return ['valid' => false, 'reason' => 'expirado_aperturas', 'token_row' => $row];
    }

    return ['valid' => true, 'reason' => 'ok', 'token_row' => $row];
}

/**
 * Consume el token: valida expiración/aperturas, aplica device binding por
 * fingerprint, incrementa el contador de aperturas y deja rastro inmutable
 * en activity_logs. Es la ÚNICA función que debe escribir en tokens_acceso
 * o activity_logs relacionado a un acceso VIP.
 *
 * @param bool $godMode  Si true (Super Admin autenticado), NO incrementa
 *                        aperturas_actuales ni aplica bloqueo por fingerprint
 *                        — solo deja constancia de la inspección en el log.
 * @return array{valid:bool,reason:string,catalogo_asignado:?string,nombre_cliente:?string}
 */
function consumirTokenAcceso(PDO $pdo, string $token, ?string $fingerprint, bool $godMode = false, ?int $idUsuarioGodMode = null): array
{
    $inspeccion = inspeccionarTokenAcceso($pdo, $token);
    $row        = $inspeccion['token_row'];

    if (!$inspeccion['valid'] || $row === null) {
        if ($row !== null) {
            registrarActivityLog($pdo, (int) $row['id'], null, 'acceso_bloqueado_' . $inspeccion['reason']);
        }
        return ['valid' => false, 'reason' => $inspeccion['reason'], 'catalogo_asignado' => null, 'nombre_cliente' => null];
    }

    $idToken = (int) $row['id'];

    if ($godMode) {
        registrarActivityLog($pdo, $idToken, $idUsuarioGodMode, 'god_mode_inspeccion');

        return [
            'valid'             => true,
            'reason'            => 'god_mode',
            'catalogo_asignado' => (string) $row['catalogo_asignado'],
            'nombre_cliente'    => $row['nombre_cliente'] !== null ? (string) $row['nombre_cliente'] : null,
        ];
    }

    // ── Device Binding por fingerprint ────────────────────────────────────────
    if ($row['device_fingerprint'] === null) {
        // Primer acceso: se vincula el dispositivo actual al token.
        $stmt = $pdo->prepare('UPDATE tokens_acceso SET device_fingerprint = :fp WHERE id = :id');
        $stmt->execute(['fp' => $fingerprint, 'id' => $idToken]);
    } elseif (!hash_equals((string) $row['device_fingerprint'], (string) $fingerprint)) {
        registrarActivityLog($pdo, $idToken, null, 'acceso_bloqueado_dispositivo_no_coincide');

        return ['valid' => false, 'reason' => 'dispositivo_no_coincide', 'catalogo_asignado' => null, 'nombre_cliente' => null];
    }

    // ── Consumir apertura + marcar expirado si llega al límite ──────────────
    $nuevasAperturas = (int) $row['aperturas_actuales'] + 1;
    $agotoLimite     = $row['max_aperturas'] !== null && $nuevasAperturas >= (int) $row['max_aperturas'];

    $stmt = $pdo->prepare(
        'UPDATE tokens_acceso
         SET aperturas_actuales = :aperturas, estatus = :estatus
         WHERE id = :id'
    );
    $stmt->execute([
        'aperturas' => $nuevasAperturas,
        'estatus'   => $agotoLimite ? 'expirado' : 'activo',
        'id'        => $idToken,
    ]);

    registrarActivityLog($pdo, $idToken, null, 'apertura_enlace');

    return [
        'valid'             => true,
        'reason'            => 'ok',
        'catalogo_asignado' => (string) $row['catalogo_asignado'],
        'nombre_cliente'    => $row['nombre_cliente'] !== null ? (string) $row['nombre_cliente'] : null,
    ];
}

/** Inserta un evento inmutable en activity_logs (nunca UPDATE/DELETE). */
function registrarActivityLog(PDO $pdo, ?int $idTokenAcceso, ?int $idUsuario, string $tipoEvento, ?string $entidadAfectada = null): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO activity_logs
            (id_token_acceso, id_usuario, tipo_evento, entidad_afectada, ip_address, user_agent, created_at)
         VALUES
            (:id_token, :id_usuario, :tipo_evento, :entidad, :ip, :ua, NOW())'
    );
    $stmt->execute([
        'id_token'   => $idTokenAcceso,
        'id_usuario' => $idUsuario,
        'tipo_evento' => $tipoEvento,
        'entidad'    => $entidadAfectada,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua'         => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
    ]);
}

/**
 * Intenta autenticar como Super Admin vía header Authorization: Bearer.
 * A diferencia de api/auth_middleware.php, NO aborta si falta o es inválido
 * — retorna null y el llamador sigue el flujo normal de Cliente VIP.
 */
function intentarGodMode(string $jwtSecret): ?array
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($authHeader === '' && function_exists('apache_request_headers')) {
        $headers    = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }

    $payload = jwtDecodeTyped(substr($authHeader, 7), $jwtSecret, JWT_TYPE_ACCESS);
    if ($payload === null || ($payload['role'] ?? '') !== 'super_admin') {
        return null;
    }

    return $payload;
}
