// assets/js/token_acceso.js — Consumo de token VIP en el cliente (fingerprint)
// Solo actúa si la URL trae ?token=. Si no hay token, la página se sirve sin
// gating (acceso directo interno). El chequeo de expiración/aperturas ya se
// hizo server-side al renderizar; este script SOLO se encarga de confirmar
// el device fingerprint (algo que requiere ejecutarse en el navegador) y de
// registrar la apertura en activity_logs a través de api/validar_token.php.

(function () {
    const params = new URLSearchParams(window.location.search);
    const token = params.get('token');
    if (!token) {
        return;
    }

    async function computeFingerprint() {
        const raw = [
            navigator.userAgent,
            screen.width + 'x' + screen.height,
            Intl.DateTimeFormat().resolvedOptions().timeZone,
            navigator.language,
        ].join('|');
        const encoded = new TextEncoder().encode(raw);
        const hashBuffer = await crypto.subtle.digest('SHA-256', encoded);
        return Array.from(new Uint8Array(hashBuffer)).map((b) => b.toString(16).padStart(2, '0')).join('');
    }

    function mostrarAccesoNoDisponible(mensaje) {
        const contenidoVip = document.getElementById('contenido-vip');
        if (contenidoVip) {
            contenidoVip.remove();
        }
        const main = document.querySelector('main.container');
        if (!main) {
            return;
        }
        const seccion = document.createElement('section');
        seccion.className = 'card u-mt-lg';
        const h1 = document.createElement('h1');
        h1.textContent = 'Acceso no disponible';
        const p = document.createElement('p');
        p.className = 'u-text-muted';
        p.textContent = mensaje;
        seccion.appendChild(h1);
        seccion.appendChild(p);
        main.appendChild(seccion);
    }

    (async function validarAcceso() {
        let fingerprint = '';
        try {
            fingerprint = await computeFingerprint();
        } catch {
            fingerprint = 'sin-soporte-subtlecrypto';
        }

        const headers = { 'Content-Type': 'application/json' };
        const adminToken = window.localStorage.getItem('yahain_access_token');
        if (adminToken) {
            headers.Authorization = 'Bearer ' + adminToken;
        }

        try {
            const respuesta = await fetch('../api/validar_token.php', {
                method: 'POST',
                headers,
                body: JSON.stringify({ token, fingerprint }),
            });
            const datos = await respuesta.json();

            if (datos.status !== 'success') {
                mostrarAccesoNoDisponible(
                    datos.message || 'Este enlace privado ha expirado. Por favor contacte a su broker asignado para solicitar un nuevo acceso.'
                );
            }
        } catch {
            // Fallo de red: no se bloquea la vista — el chequeo server-side inicial ya filtró tokens inválidos.
        }
    })();
})();
