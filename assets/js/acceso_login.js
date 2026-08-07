// assets/js/acceso_login.js — Portada VIP: valida el token/clave ingresado
// contra api/validar_token.php y redirige al catálogo asignado si es válido.

document.addEventListener('DOMContentLoaded', () => {
    const form    = document.getElementById('form-acceso-vip');
    const input   = document.getElementById('input-token-vip');
    const mensaje = document.getElementById('mensaje-acceso-vip');

    if (!form || !input || !mensaje) {
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

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const token = input.value.trim();
        if (token === '') {
            return;
        }

        mensaje.textContent = 'Verificando acceso…';
        form.querySelector('.form-acceso__submit').disabled = true;

        try {
            const fingerprint = await computeFingerprint();
            const respuesta = await fetch('api/validar_token.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, fingerprint }),
            });
            const datos = await respuesta.json();

            if (datos.status === 'success' && datos.data && datos.data.catalogo_asignado) {
                mensaje.textContent = 'Acceso verificado. Redirigiendo…';
                window.location.href = `catalogo/${datos.data.catalogo_asignado}.php?token=${encodeURIComponent(token)}`;
                return;
            }

            mensaje.textContent = datos.message || 'Clave de acceso inválida.';
        } catch {
            mensaje.textContent = 'No se pudo verificar el acceso. Intente nuevamente.';
        } finally {
            form.querySelector('.form-acceso__submit').disabled = false;
        }
    });
});
