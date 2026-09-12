// assets/js/main.js — Switch Día/Noche + Botón "Volver Arriba" + Lightbox
// Componentes compartidos por todas las vistas públicas (index.html,
// catalogo/*.php, broker/index.php). No-op silencioso si el markup de un
// componente no está presente en la página actual.

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initBackToTop();
    initLightbox();
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

// ── LIGHTBOX DE GALERÍA (JS/CSS puro) ─────────────────────────────────────────
// Delegación de eventos en document: cubre tanto imágenes ya presentes al
// cargar (vistas PHP renderizadas en servidor) como imágenes inyectadas
// después vía fetch/JS (ej. terrenos/js/ficha.js).
const SELECTOR_IMAGENES_LIGHTBOX = '.producto-hero img, .producto-galeria img';

function initLightbox() {
    if (document.querySelector('.lightbox')) {
        return;
    }

    const overlay = document.createElement('div');
    overlay.className = 'lightbox';
    overlay.innerHTML = `
        <div class="lightbox__contenido">
            <button class="lightbox__cerrar" type="button" aria-label="Cerrar">✕</button>
            <img class="lightbox__imagen" src="" alt="">
        </div>
    `;
    document.body.appendChild(overlay);

    const imgLightbox = overlay.querySelector('.lightbox__imagen');
    const btnCerrar    = overlay.querySelector('.lightbox__cerrar');

    function abrir(src, alt) {
        imgLightbox.src = src;
        imgLightbox.alt = alt;
        overlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function cerrar() {
        overlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', (event) => {
        const img = event.target.closest(SELECTOR_IMAGENES_LIGHTBOX);
        if (img) {
            abrir(img.currentSrc || img.src, img.alt);
        }
    });

    btnCerrar.addEventListener('click', cerrar);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            cerrar();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
            cerrar();
        }
    });
}
