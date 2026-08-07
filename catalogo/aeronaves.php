<?php

declare(strict_types=1);

// =============================================================================
// catalogo/aeronaves.php — Vista "Selección VIP · Aeronaves"
// Estado de datos: CONECTADO a la tabla `aeronaves` vía api/conexion.php.
// La galería de imágenes no vive en la BD (Mandamiento 9 — sin autorización
// para añadir esa columna/tabla); se resuelve por convención de carpetas:
// assets/img/aeronaves/{slug}/{exterior,interior,detalles}/*.jpg
// =============================================================================

require_once __DIR__ . '/../api/conexion.php';
require_once __DIR__ . '/../helpers/token_validator.php';

$slug  = $_GET['slug'] ?? 'agusta-a109-k2';
$token = isset($_GET['token']) ? trim((string) $_GET['token']) : null;

$producto = null;
$errorConexion = false;
$mensajeTokenInvalido = null;

$MENSAJES_TOKEN = [
    'no_encontrado'      => 'Este enlace privado no existe.',
    'inactivo'            => 'Este enlace privado ha sido revocado por su broker.',
    'expirado_tiempo'      => 'Este enlace privado ha expirado. Por favor contacte a su broker asignado para solicitar un nuevo acceso.',
    'expirado_aperturas'   => 'Este enlace privado alcanzó su límite de accesos. Por favor contacte a su broker asignado para solicitar un nuevo acceso.',
];

try {
    $database = new Database();
    $pdo      = $database->getConnection();

    // Chequeo previo (sin efectos secundarios) — el consumo real (fingerprint,
    // contador, activity_logs) ocurre vía assets/js/token_acceso.js después
    // de que el navegador calcula el fingerprint del dispositivo.
    if ($token !== null && $token !== '') {
        $inspeccion = inspeccionarTokenAcceso($pdo, $token);
        if (!$inspeccion['valid']) {
            $mensajeTokenInvalido = $MENSAJES_TOKEN[$inspeccion['reason']] ?? 'Enlace privado inválido.';
        }
    }

    if ($mensajeTokenInvalido === null) {
        $stmt = $pdo->prepare(
            'SELECT * FROM aeronaves
             WHERE slug = :slug AND estatus != \'oculto\' AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $producto = $stmt->fetch() ?: null;
    }
} catch (\Throwable $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [catalogo/aeronaves.php] ' . $e->getMessage());
    $errorConexion = true;
}

// ── Resolver galería por convención de carpetas ──────────────────────────────
$galeria = [];
if ($producto !== null) {
    $baseFs  = dirname(__DIR__) . '/assets/img/aeronaves/' . $slug;
    $baseWeb = '../assets/img/aeronaves/' . $slug;

    foreach (['exterior', 'interior', 'detalles'] as $carpeta) {
        foreach (glob("{$baseFs}/{$carpeta}/*.jpg") ?: [] as $rutaAbsoluta) {
            $archivo   = basename($rutaAbsoluta);
            $galeria[] = [
                'src' => "{$baseWeb}/{$carpeta}/{$archivo}",
                'alt' => $producto['marca'] . ' ' . $producto['modelo'] . ' — ' . str_replace('-', ' ', pathinfo($archivo, PATHINFO_FILENAME)),
            ];
        }
    }
}

$titulo = $producto !== null
    ? htmlspecialchars($producto['marca'] . ' ' . $producto['modelo'], ENT_QUOTES, 'UTF-8')
    : 'Aeronave no disponible';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colección Privada · Aeronaves — <?= $titulo ?></title>
    <meta name="description" content="Selección VIP de aeronaves privadas. Acceso exclusivo por invitación.">
    <link rel="icon" href="../favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">

    <link rel="preload" href="../assets/css/main.css" as="style">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script src="../assets/js/theme_init.js"></script>
</head>
<body>

    <header class="site-header">
        <a class="site-header__brand" href="../index.html">Yahain</a>
        <span class="site-header__tagline">Colección Privada · Aeronaves</span>
        <div class="site-header__acciones">
            <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Cambiar tema">
                <svg class="theme-toggle__icon-sol" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"></circle>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"></path>
                </svg>
                <svg class="theme-toggle__icon-luna" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"></path>
                </svg>
            </button>
        </div>
    </header>

    <main class="container">

        <?php if ($errorConexion): ?>

        <section class="card u-mt-lg">
            <h1>Catálogo temporalmente no disponible</h1>
            <p class="u-text-muted">Estamos actualizando esta sección de la Colección Privada. Por favor contacte a su broker privado o vuelva a intentarlo en unos minutos.</p>
        </section>

        <?php elseif ($mensajeTokenInvalido !== null): ?>

        <section class="card u-mt-lg">
            <h1>Acceso no disponible</h1>
            <p class="u-text-muted"><?= htmlspecialchars($mensajeTokenInvalido, ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <?php elseif ($producto === null): ?>

        <section class="card u-mt-lg">
            <h1>Esta pieza ya no está disponible</h1>
            <p class="u-text-muted">Por favor contacte a su broker privado para conocer otras piezas de la Selección VIP.</p>
        </section>

        <?php else: ?>

        <div id="contenido-vip">

        <section class="producto-hero">
            <img src="<?= htmlspecialchars($producto['imagen_portada'], ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= $titulo ?> — vista exterior lateral"
                 width="1600" height="900" loading="eager">
            <div class="producto-hero__overlay">
                <p class="producto-hero__eyebrow">Selección VIP · Aeronaves</p>
                <h1 class="producto-hero__title"><?= $titulo ?></h1>
                <p class="producto-hero__subtitle"><?= htmlspecialchars($producto['descripcion_corta'], ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>

        <section class="arf-grid arf-grid--align-top">

            <article class="card arf-col-2">
                <span class="badge-estatus"><?= htmlspecialchars(ucfirst((string) $producto['estatus']), ENT_QUOTES, 'UTF-8') ?></span>
                <h2 class="u-mt-sm">Historia y Legado</h2>
                <p class="u-text-muted"><?= nl2br(htmlspecialchars($producto['descripcion_larga'], ENT_QUOTES, 'UTF-8')) ?></p>
                <a class="btn-vip" href="#contacto">Solicitar información privada</a>
            </article>

            <article class="card arf-col-2">
                <h2>Ficha Técnica</h2>
                <div class="producto-specs-wrap">
                <table class="producto-specs">
                    <tbody>
                        <tr><th scope="row">Matrícula</th><td><?= htmlspecialchars((string) $producto['matricula'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th scope="row">Tren de aterrizaje</th><td><?= htmlspecialchars((string) $producto['tren_aterrizaje'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                        <tr><th scope="row">Planta motriz</th><td><?= htmlspecialchars((string) $producto['planta_motriz'], ENT_QUOTES, 'UTF-8') ?> (<?= (int) $producto['potencia_motor_kw'] ?> kW c/u)</td></tr>
                        <tr><th scope="row">Capacidad</th><td><?= (int) $producto['capacidad_pilotos'] ?> piloto(s) + hasta <?= (int) $producto['capacidad_pasajeros'] ?> pasajeros</td></tr>
                        <tr><th scope="row">Velocidad de crucero</th><td><?= (int) $producto['velocidad_crucero_min_kmh'] ?> – <?= (int) $producto['velocidad_crucero_max_kmh'] ?> km/h</td></tr>
                        <tr><th scope="row">Techo de servicio</th><td><?= number_format((float) $producto['techo_servicio_m'], 0, ',', ',') ?> m</td></tr>
                        <tr><th scope="row">Peso máx. al despegue</th><td><?= number_format((float) $producto['peso_max_despegue_kg'], 0, ',', ',') ?> kg</td></tr>
                        <tr><th scope="row">Dimensiones de cabina</th><td><?= (float) $producto['cabina_alto_m'] ?> m alto × <?= (float) $producto['cabina_ancho_m'] ?> m ancho</td></tr>
                    </tbody>
                </table>
                </div>
            </article>

        </section>

        <?php if ($galeria !== []): ?>
        <section class="producto-galeria u-mt-lg">
            <h2>Galería</h2>
            <div class="arf-grid">
                <?php foreach ($galeria as $imagen): ?>
                <div class="arf-col-3">
                    <img src="<?= htmlspecialchars($imagen['src'], ENT_QUOTES, 'UTF-8') ?>"
                         alt="<?= htmlspecialchars($imagen['alt'], ENT_QUOTES, 'UTF-8') ?>"
                         loading="lazy" width="800" height="600">
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        </div><!-- /#contenido-vip -->

        <?php endif; ?>

    </main>

    <footer class="site-footer" id="contacto">
        Acceso exclusivo por invitación. Contacte a su broker privado para más información.
    </footer>

    <button class="btn-volver-arriba" id="btn-volver-arriba" type="button" aria-label="Volver arriba">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M12 19V5M5 12l7-7 7 7"></path>
        </svg>
    </button>

    <script src="../assets/js/main.js" defer></script>
    <?php if ($token !== null && $token !== '' && $mensajeTokenInvalido === null && $producto !== null): ?>
    <script src="../assets/js/token_acceso.js" defer></script>
    <?php endif; ?>

</body>
</html>
