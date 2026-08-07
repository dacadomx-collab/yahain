// assets/js/theme_init.js — Aplica el tema guardado ANTES del primer paint.
// Sin defer, cargado justo después de main.css, para evitar parpadeo
// (FOUC) entre Modo Noche (predeterminado) y Modo Día.
(function () {
    var saved = window.localStorage.getItem('yahain_theme');
    var theme = saved === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', theme);
})();
