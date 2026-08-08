<?php

declare(strict_types=1);

// =============================================================================
// api/seed.php — Auto-Seeder de Datos de Prueba (TEMPORAL — 1 CLIC)
// Crea (si no existen) el Super Admin, el Broker de prueba y el token VIP777.
// Idempotente: correrlo varias veces no duplica registros.
//
// ⚠️ TEMPORAL: usa credenciales de demostración conocidas (ya compartidas en
// esta conversación). Eliminar este archivo antes de considerar el proyecto
// listo para clientes reales — un seeder público es superficie de ataque.
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'seed.php']);

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    $resultado = [];

    // ── Super Admin: David Cabrera ────────────────────────────────────────────
    $resultado['super_admin'] = crearUsuarioSiNoExiste(
        $pdo,
        'David Cabrera',
        'david.cabrera@yahain.tourfindy.com',
        'DavidCEO_Yahain2026!',
        'super_admin'
    );

    // ── Broker de prueba ───────────────────────────────────────────────────────
    $resultado['broker'] = crearUsuarioSiNoExiste(
        $pdo,
        'Broker de Prueba',
        'broker.prueba@yahain.tourfindy.com',
        'ClaveTemporal2026!',
        'broker'
    );

    // ── Token VIP777 ──────────────────────────────────────────────────────────
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => 'broker.prueba@yahain.tourfindy.com']);
    $idBroker = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT id, estatus FROM tokens_acceso WHERE token = :token LIMIT 1');
    $stmt->execute(['token' => 'VIP777']);
    $tokenExistente = $stmt->fetch();

    if ($tokenExistente === false) {
        $stmt = $pdo->prepare(
            'INSERT INTO tokens_acceso
                (token, id_broker, nombre_cliente, catalogo_asignado, expira_at, max_aperturas, aperturas_actuales, estatus)
             VALUES
                (:token, :id_broker, :nombre_cliente, :catalogo, :expira_at, :max_aperturas, 0, \'activo\')'
        );
        $stmt->execute([
            'token'          => 'VIP777',
            'id_broker'      => $idBroker,
            'nombre_cliente' => 'Cliente de Prueba',
            'catalogo'       => 'aeronaves',
            'expira_at'      => (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s'),
            'max_aperturas'  => 20,
        ]);
        $resultado['token_vip777'] = ['creado' => true, 'detalle' => 'Token VIP777 creado — 20 aperturas, expira en 30 días.'];
    } else {
        $resultado['token_vip777'] = ['creado' => false, 'detalle' => "Ya existía (estatus: {$tokenExistente['estatus']})."];
    }

    // ── ?reset=1: libera el device binding y reinicia el contador de VIP777 ────
    // Útil en desarrollo cuando el token quedó vinculado al fingerprint de otro
    // navegador/dispositivo de prueba. NUNCA usar esto contra un token real de
    // producción — reabre el enlace a cualquier dispositivo.
    if (($_GET['reset'] ?? '') === '1') {
        $stmt = $pdo->prepare(
            'UPDATE tokens_acceso
             SET device_fingerprint = NULL, aperturas_actuales = 0, estatus = \'activo\'
             WHERE token = :token'
        );
        $stmt->execute(['token' => 'VIP777']);

        $resultado['token_vip777_reset'] = [
            'reset' => true,
            'detalle' => 'device_fingerprint limpiado y aperturas_actuales reiniciado a 0 para VIP777.',
        ];
    }

    $baseUrl = 'http://localhost/Yahain';

    asfl_log('RESPONSE', ['endpoint' => 'seed.php', 'resultado' => $resultado]);

    send_success('Seed de datos de prueba completado.', [
        'resumen' => $resultado,
        'credenciales' => [
            'super_admin' => ['email' => 'david.cabrera@yahain.tourfindy.com', 'password' => 'DavidCEO_Yahain2026!'],
            'broker'      => ['email' => 'broker.prueba@yahain.tourfindy.com', 'password' => 'ClaveTemporal2026!'],
        ],
        'enlaces_de_prueba' => [
            'login_vip'          => "{$baseUrl}/index.html (ingresar VIP777)",
            'catalogo_directo'   => "{$baseUrl}/catalogo/aeronaves.php?token=VIP777",
            'panel_broker'       => "{$baseUrl}/broker/index.php",
        ],
    ]);
} catch (\Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [seed.php] ' . $e->getMessage());
    send_error('Error al ejecutar el seed.', 500);
}

function crearUsuarioSiNoExiste(PDO $pdo, string $nombre, string $email, string $passwordPlano, string $role): array
{
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);

    if ($stmt->fetchColumn() !== false) {
        return ['creado' => false, 'detalle' => "Ya existía: {$email}"];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (nombre_completo, email, password_hash, role, estatus)
         VALUES (:nombre, :email, :hash, :role, \'activo\')'
    );
    $stmt->execute([
        'nombre' => $nombre,
        'email'  => $email,
        'hash'   => password_hash($passwordPlano, PASSWORD_BCRYPT),
        'role'   => $role,
    ]);

    return ['creado' => true, 'detalle' => "Usuario {$role} creado: {$email}"];
}
