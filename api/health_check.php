<?php

declare(strict_types=1);

// =============================================================================
// api/health_check.php — Auditoría de Servidor (TEMPORAL)
// Verifica conexión PDO real + existencia y estado de las 4 tablas del motor
// VIP. Solo expone conteos y booleanos — NUNCA filas, contraseñas ni PII.
//
// ⚠️ TEMPORAL: este endpoint es una herramienta de auditoría puntual. Una vez
// confirmado que el servidor está sano, debe eliminarse o protegerse detrás
// de auth (Mandamiento #14) — un endpoint público que confirma la existencia
// de tablas es información útil para un atacante en reconocimiento.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'health_check.php']);

// Mismo guardia de secreto compartido que api/seed.php — ver esa nota.
$envSeed    = parse_ini_file(dirname(__DIR__) . '/.env', false, INI_SCANNER_RAW) ?: [];
$seedSecret = (string) ($envSeed['SEED_SECRET'] ?? '');
$secretDado = (string) ($_GET['secret'] ?? '');

if ($seedSecret === '' || !hash_equals($seedSecret, $secretDado)) {
    send_error('No autorizado.', 403);
}

$checks = [
    'filesystem' => checkFilesystem(),
    'database'   => checkDatabaseConnection(),
];

if ($checks['database']['ok']) {
    $checks['tabla_aeronaves']     = checkTabla('aeronaves');
    $checks['tabla_usuarios']      = checkTabla('usuarios');
    $checks['tabla_tokens_acceso'] = checkTabla('tokens_acceso');
    $checks['tabla_activity_logs'] = checkTabla('activity_logs');
}

$allOk = !in_array(false, array_column($checks, 'ok'), true);

asfl_log('RESPONSE', ['endpoint' => 'health_check.php', 'all_ok' => $allOk]);

send_json_response(
    $allOk ? 'success' : 'error',
    $allOk ? 'Auditoría de servidor: todos los sistemas operativos.' : 'Uno o más checks fallaron.',
    $checks
);

// -----------------------------------------------------------------------------

function checkFilesystem(): array
{
    $root = dirname(__DIR__);
    $carpetasEsperadas = ['api', 'assets', 'catalogo', 'broker', 'helpers'];
    $faltantes = array_filter($carpetasEsperadas, fn ($c) => !is_dir($root . '/' . $c));

    return [
        'ok'      => $faltantes === [],
        'detalle' => $faltantes === []
            ? 'Estructura de directorios en orden.'
            : 'Carpetas faltantes: ' . implode(', ', $faltantes),
    ];
}

function checkDatabaseConnection(): array
{
    try {
        $database = new Database();
        $pdo      = $database->getConnection();
        $pdo->query('SELECT 1');

        return ['ok' => true, 'detalle' => 'Conexión PDO establecida contra la BD remota centralizada.'];
    } catch (\Throwable $e) {
        error_log('[' . date('Y-m-d H:i:s') . '] [health_check::checkDatabaseConnection] ' . $e->getMessage());
        return ['ok' => false, 'detalle' => 'No se pudo conectar a la base de datos remota.'];
    }
}

function checkTabla(string $tabla): array
{
    // Whitelist estricta — jamás interpolar un nombre de tabla dinámico sin validar.
    $tablasPermitidas = ['aeronaves', 'usuarios', 'tokens_acceso', 'activity_logs'];
    if (!in_array($tabla, $tablasPermitidas, true)) {
        return ['ok' => false, 'detalle' => 'Tabla no reconocida.'];
    }

    try {
        $database = new Database();
        $pdo      = $database->getConnection();

        $stmt  = $pdo->query("SELECT COUNT(*) FROM `{$tabla}`");
        $total = (int) $stmt->fetchColumn();

        return ['ok' => true, 'existe' => true, 'total_filas' => $total];
    } catch (\Throwable $e) {
        error_log('[' . date('Y-m-d H:i:s') . "] [health_check::checkTabla:{$tabla}] " . $e->getMessage());
        return ['ok' => false, 'existe' => false, 'detalle' => "La tabla `{$tabla}` no existe o no es accesible."];
    }
}
