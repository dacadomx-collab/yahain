<?php

declare(strict_types=1);

// =============================================================================
// api/aeronaves.php — Catálogo de Aeronaves (Lectura Pública)
// Mandamiento #14: CORS ≠ Auth. GET de catálogo es público — no requiere JWT.
// Mandamiento #5: Contrato de API Estricto — {"status","message","data"}.
//
// GET /api/aeronaves.php             → lista todas las aeronaves visibles
// GET /api/aeronaves.php?slug=xxx    → detalle de una aeronave por slug
// =============================================================================

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/asfl_logger.php';

asfl_log('REQUEST', ['endpoint' => 'aeronaves.php', 'method' => $_SERVER['REQUEST_METHOD'] ?? '']);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Método no permitido.', 405);
}

$slug = isset($_GET['slug']) ? (string) $_GET['slug'] : null;

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    if ($slug !== null) {
        $stmt = $pdo->prepare(
            'SELECT * FROM aeronaves
             WHERE slug = :slug AND estatus != \'oculto\' AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $aeronave = $stmt->fetch();

        if ($aeronave === false) {
            send_error('Aeronave no encontrada.', 404);
        }

        asfl_log('RESPONSE', ['endpoint' => 'aeronaves.php', 'slug' => $slug, 'found' => true]);
        send_success('Aeronave encontrada.', $aeronave);
    }

    $stmt = $pdo->query(
        'SELECT id, slug, marca, modelo, matricula, descripcion_corta,
                capacidad_pilotos, capacidad_pasajeros, precio, moneda,
                precio_a_consultar, imagen_portada, estatus
         FROM aeronaves
         WHERE estatus != \'oculto\' AND deleted_at IS NULL
         ORDER BY created_at DESC'
    );
    $aeronaves = $stmt->fetchAll();

    asfl_log('RESPONSE', ['endpoint' => 'aeronaves.php', 'total' => count($aeronaves)]);
    send_success('Catálogo de aeronaves obtenido.', $aeronaves);
} catch (\PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [aeronaves.php] ' . $e->getMessage());
    send_error('Error al consultar el catálogo de aeronaves.', 500);
}
