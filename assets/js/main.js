// assets/js/main.js — Yahain: Switch Día/Noche + Botón "Volver Arriba"
// Componentes compartidos por todas las vistas públicas (index.html,
// catalogo/*.php, broker/index.php). No-op silencioso si el markup de un
// componente no está presente en la página actual.

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initBackToTop();
});

// ── SWITCH DÍA / NOCHE ────────────────────────────────────────────────────────
function initThemeToggle() {
    const boton = document.getElementById('theme-toggle');
    if (!boton) {
        return;
    }

    function temaActual() {
        return document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
    }

    function actualizarAria() {
        const esNoche = temaActual() === 'dark';
        boton.setAttribute('aria-label', esNoche ? 'Cambiar a Modo Día' : 'Cambiar a Modo Noche');
        boton.setAttribute('aria-pressed', String(!esNoche));
    }

    actualizarAria();

    boton.addEventListener('click', () => {
        const nuevoTema = temaActual() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', nuevoTema);
        window.localStorage.setItem('yahain_theme', nuevoTema);
        actualizarAria();
    });
}

// ── BOTÓN FLOTANTE "VOLVER ARRIBA" ────────────────────────────────────────────
function initBackToTop() {
    const boton = document.getElementById('btn-volver-arriba');
    if (!boton) {
        return;
    }

    const UMBRAL_PX = 300;

    function actualizarVisibilidad() {
        boton.classList.toggle('is-visible', window.scrollY > UMBRAL_PX);
    }

    window.addEventListener('scroll', actualizarVisibilidad, { passive: true });
    actualizarVisibilidad();

    boton.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}
