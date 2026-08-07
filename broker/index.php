<?php

declare(strict_types=1);

// =============================================================================
// broker/index.php — Panel de Broker: Generador de Enlaces VIP
// Prototipo SPA-lite: login + generación de tokens, ambos vía fetch() contra
// api/auth_login.php y api/crear_token.php. No hay lógica de servidor aquí
// más allá de servir el shell HTML — toda la autenticación/estado vive en
// el JWT guardado en localStorage (assets/js/broker_panel.js).
// =============================================================================
?>
<!DOCTYPE html>
<html lang="es" class="tema-noche-forzado">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Broker · Colección Privada</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="../favicon.ico">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Montserrat:wght@300;400;500&display=swap" rel="stylesheet">

    <link rel="preload" href="../assets/css/main.css" as="style">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>

    <header class="site-header">
        <a class="site-header__brand" href="../index.html">Yahain</a>
        <span class="site-header__tagline">Panel de Broker</span>
        <button class="btn-vip btn-vip--secundario" id="btn-cerrar-sesion" type="button" hidden>Cerrar sesión</button>
    </header>

    <main class="container">

        <!-- ── LOGIN ─────────────────────────────────────────────────────────── -->
        <section class="card panel-broker" id="seccion-login">
            <h1 class="u-mt-sm">Acceso de Broker</h1>
            <p class="u-text-muted">Ingrese sus credenciales para generar enlaces de la Selección VIP.</p>

            <form id="form-login-broker">
                <div class="panel-broker__campo">
                    <label for="login-email">Correo electrónico</label>
                    <input type="email" id="login-email" name="email" required autocomplete="username">
                </div>
                <div class="panel-broker__campo">
                    <label for="login-password">Contraseña</label>
                    <input type="password" id="login-password" name="password" required autocomplete="current-password">
                </div>
                <button class="btn-vip" type="submit">Ingresar</button>
                <p class="form-acceso__mensaje u-text-muted" id="mensaje-login" role="status"></p>
            </form>
        </section>

        <!-- ── DASHBOARD (oculto hasta autenticar) ──────────────────────────────── -->
        <section class="card panel-broker" id="seccion-dashboard" hidden>
            <h1 class="u-mt-sm">Generar Enlace VIP</h1>
            <p class="u-text-muted">Defina el catálogo, el cliente y las reglas de caducidad del enlace efímero.</p>

            <form id="form-crear-token">
                <div class="panel-broker__campo">
                    <label for="token-catalogo">Catálogo</label>
                    <select id="token-catalogo" name="catalogo_asignado" required>
                        <option value="aeronaves">Aeronaves</option>
                        <option value="relojes">Relojes</option>
                        <option value="bienes-inmuebles">Bienes Inmuebles</option>
                        <option value="arte">Arte</option>
                        <option value="subastas-arte">Subastas de Arte</option>
                        <option value="relaciones-publicas">Relaciones Públicas</option>
                        <option value="consejo-diplomatico">Consejo Diplomático</option>
                    </select>
                </div>

                <div class="panel-broker__campo">
                    <label for="token-cliente">Nombre del cliente VIP (opcional)</label>
                    <input type="text" id="token-cliente" name="nombre_cliente" maxlength="150" placeholder="Ej. Juan Pérez">
                </div>

                <div class="panel-broker__fila-doble">
                    <div class="panel-broker__campo">
                        <label for="token-horas">Caduca en (horas)</label>
                        <input type="number" id="token-horas" name="expira_en_horas" min="1" placeholder="Ej. 72">
                    </div>
                    <div class="panel-broker__campo">
                        <label for="token-aperturas">Máximo de aperturas</label>
                        <input type="number" id="token-aperturas" name="max_aperturas" min="1" placeholder="Ej. 3">
                    </div>
                </div>
                <p class="u-text-muted u-text-small">Debe definir al menos una regla de caducidad — por tiempo, por aperturas, o ambas.</p>

                <button class="btn-vip" type="submit">Generar Enlace VIP</button>
                <p class="form-acceso__mensaje u-text-muted" id="mensaje-token" role="status"></p>
            </form>

            <div class="panel-broker__resultado" id="resultado-token" hidden>
                <span class="badge-estatus">Enlace generado</span>
                <p class="panel-broker__resultado-url" id="resultado-url"></p>
                <div class="panel-broker__acciones">
                    <button class="btn-vip btn-vip--secundario" id="btn-copiar-enlace" type="button">Copiar Enlace VIP</button>
                    <button class="btn-vip btn-vip--secundario" id="btn-copiar-token" type="button">Copiar Token</button>
                </div>
            </div>
        </section>

    </main>

    <footer class="site-footer">
        Panel interno — Colección Privada. Uso exclusivo de Brokers autorizados.
    </footer>

    <script src="../assets/js/broker_panel.js" defer></script>
</body>
</html>
