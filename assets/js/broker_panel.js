// assets/js/broker_panel.js — Panel de Broker: login + generación de tokens VIP.
// Estado de sesión vive en localStorage (yahain_access_token). Sin backend de
// sesión propio — cada llamada protegida manda el JWT como Bearer.

document.addEventListener('DOMContentLoaded', () => {
    const seccionLogin     = document.getElementById('seccion-login');
    const seccionDashboard = document.getElementById('seccion-dashboard');
    const btnCerrarSesion  = document.getElementById('btn-cerrar-sesion');

    const formLogin    = document.getElementById('form-login-broker');
    const mensajeLogin = document.getElementById('mensaje-login');

    const formToken     = document.getElementById('form-crear-token');
    const mensajeToken  = document.getElementById('mensaje-token');
    const resultadoBox  = document.getElementById('resultado-token');
    const resultadoUrl  = document.getElementById('resultado-url');
    const btnCopiarLink = document.getElementById('btn-copiar-enlace');
    const btnCopiarTok  = document.getElementById('btn-copiar-token');

    function mostrarDashboard() {
        seccionLogin.hidden = true;
        seccionDashboard.hidden = false;
        btnCerrarSesion.hidden = false;
    }

    function mostrarLogin() {
        seccionLogin.hidden = false;
        seccionDashboard.hidden = true;
        btnCerrarSesion.hidden = true;
    }

    function getToken() {
        return window.localStorage.getItem('yahain_access_token');
    }

    if (getToken()) {
        mostrarDashboard();
    }

    btnCerrarSesion.addEventListener('click', () => {
        window.localStorage.removeItem('yahain_access_token');
        mostrarLogin();
    });

    // ── LOGIN ──────────────────────────────────────────────────────────────────
    formLogin.addEventListener('submit', async (event) => {
        event.preventDefault();
        mensajeLogin.textContent = 'Verificando…';

        const email    = document.getElementById('login-email').value.trim();
        const password = document.getElementById('login-password').value;

        try {
            const respuesta = await fetch('../api/auth_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
            });
            const datos = await respuesta.json();

            if (datos.status === 'success' && datos.data && datos.data.access_token) {
                window.localStorage.setItem('yahain_access_token', datos.data.access_token);
                mensajeLogin.textContent = '';
                formLogin.reset();
                mostrarDashboard();
                return;
            }

            mensajeLogin.textContent = datos.message || 'Credenciales inválidas.';
        } catch {
            mensajeLogin.textContent = 'No se pudo conectar con el servidor.';
        }
    });

    // ── GENERAR TOKEN ────────────────────────────────────────────────────────
    formToken.addEventListener('submit', async (event) => {
        event.preventDefault();
        mensajeToken.textContent = 'Generando enlace…';
        resultadoBox.hidden = true;

        const catalogoAsignado = document.getElementById('token-catalogo').value;
        const nombreCliente    = document.getElementById('token-cliente').value.trim() || null;
        const horas             = document.getElementById('token-horas').value;
        const aperturas         = document.getElementById('token-aperturas').value;

        if (horas === '' && aperturas === '') {
            mensajeToken.textContent = 'Defina al menos una regla de caducidad.';
            return;
        }

        const body = {
            catalogo_asignado: catalogoAsignado,
            nombre_cliente: nombreCliente,
        };
        if (horas !== '') {
            body.expira_en_horas = Number(horas);
        }
        if (aperturas !== '') {
            body.max_aperturas = Number(aperturas);
        }

        try {
            const respuesta = await fetch('../api/crear_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: 'Bearer ' + getToken(),
                },
                body: JSON.stringify(body),
            });

            if (respuesta.status === 401) {
                window.localStorage.removeItem('yahain_access_token');
                mostrarLogin();
                mensajeLogin.textContent = 'Su sesión expiró. Ingrese nuevamente.';
                return;
            }

            const datos = await respuesta.json();

            if (datos.status === 'success' && datos.data) {
                mensajeToken.textContent = '';
                resultadoUrl.textContent = datos.data.url_acceso;
                resultadoBox.hidden = false;
                resultadoBox.dataset.token = datos.data.token;
                resultadoBox.dataset.url = datos.data.url_acceso;
                formToken.reset();
                return;
            }

            mensajeToken.textContent = datos.message || 'No se pudo generar el enlace.';
        } catch {
            mensajeToken.textContent = 'No se pudo conectar con el servidor.';
        }
    });

    async function copiarAlPortapapeles(texto, boton, etiquetaOriginal) {
        try {
            await navigator.clipboard.writeText(texto);
            boton.textContent = 'Copiado ✓';
            setTimeout(() => { boton.textContent = etiquetaOriginal; }, 2000);
        } catch {
            boton.textContent = 'No se pudo copiar';
        }
    }

    btnCopiarLink.addEventListener('click', () => {
        copiarAlPortapapeles(resultadoBox.dataset.url || '', btnCopiarLink, 'Copiar Enlace VIP');
    });

    btnCopiarTok.addEventListener('click', () => {
        copiarAlPortapapeles(resultadoBox.dataset.token || '', btnCopiarTok, 'Copiar Token');
    });
});
