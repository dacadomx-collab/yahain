// terrenos/js/ficha.js — Ficha técnica individual de un terreno.
// Reutiliza los componentes ya estilizados en assets/css/main.css:
// .producto-hero (imagen + overlay), .producto-galeria (grid con lightbox
// vía assets/js/main.js) y .producto-specs (tabla de especificaciones).

document.addEventListener('DOMContentLoaded', () => {
    renderizarFicha();
});

async function renderizarFicha() {
    const contenedor = document.getElementById('detailContainer');
    if (!contenedor) {
        return;
    }

    const parametros = new URLSearchParams(window.location.search);
    const idTerreno = parametros.get('id');

    if (!idTerreno) {
        contenedor.innerHTML = `
            <section class="card u-mt-lg">
                <h1>Identificador ausente</h1>
                <p class="u-text-muted">Seleccione un terreno válido desde el catálogo principal.</p>
            </section>
        `;
        return;
    }

    try {
        const respuesta = await fetch('data/listings.json');
        if (!respuesta.ok) {
            throw new Error(`Estado HTTP ${respuesta.status}`);
        }
        const terrenos = await respuesta.json();
        const terreno = terrenos.find((item) => item.id === idTerreno);

        if (!terreno) {
            contenedor.innerHTML = `
                <section class="card u-mt-lg">
                    <h1>Terreno no localizado</h1>
                    <p class="u-text-muted">El código ${idTerreno} no forma parte del catálogo activo de YAHAIN Lands.</p>
                </section>
            `;
            return;
        }

        document.title = `${terreno.titulo} | YAHAIN Lands`;

        const imagenes = (terreno.imagenes && terreno.imagenes.length > 0)
            ? terreno.imagenes
            : [];

        const mensajeWhatsapp = encodeURIComponent(
            `Hola YAHAIN, me interesa recibir más información sobre el terreno "${terreno.titulo}" (Código ${terreno.id}).`
        );
        const urlWhatsapp = `https://wa.me/526245550199?text=${mensajeWhatsapp}`;

        const caracteristicasHtml = (terreno.caracteristicas && terreno.caracteristicas.length > 0)
            ? terreno.caracteristicas.map((item) => `<li>${item}</li>`).join('')
            : '';

        const galeriaHtml = imagenes.slice(1).map((src) => `
            <div class="arf-col-3">
                <img src="${src}" alt="${terreno.titulo}" loading="lazy" width="800" height="600">
            </div>
        `).join('');

        contenedor.innerHTML = `
            <section class="producto-hero">
                <img src="${imagenes[0] || ''}" alt="${terreno.titulo}" width="1600" height="900" loading="eager">
                <div class="producto-hero__overlay">
                    <p class="producto-hero__eyebrow">YAHAIN Lands · ${terreno.ubicacion}</p>
                    <h1 class="producto-hero__title">${terreno.titulo}</h1>
                </div>
            </section>

            <section class="arf-grid arf-grid--align-top">

                <article class="card arf-col-2">
                    <span class="badge-estatus">${terreno.id}</span>
                    <h2 class="u-mt-sm">Descripción Ejecutiva</h2>
                    <p class="u-text-muted">${terreno.descripcion}</p>
                    <a class="btn-vip btn-vip--whatsapp u-mt-sm" href="${urlWhatsapp}" target="_blank" rel="noopener noreferrer">
                        Contactar a YAHAIN vía WhatsApp
                    </a>
                </article>

                <article class="card arf-col-2">
                    <h2>Ficha Técnica</h2>
                    <div class="producto-specs-wrap">
                        <table class="producto-specs">
                            <tbody>
                                <tr><th scope="row">Precio de lista</th><td>${terreno.precioFormateado}</td></tr>
                                <tr><th scope="row">Superficie</th><td>${Number(terreno.superficieM2).toLocaleString('es-MX')} m²</td></tr>
                                <tr><th scope="row">Escala</th><td>${terreno.superficieAcres} Acres</td></tr>
                                <tr><th scope="row">Ubicación</th><td>${terreno.ubicacion}</td></tr>
                            </tbody>
                        </table>
                    </div>
                    ${caracteristicasHtml ? `<h2 class="u-mt-sm">Características</h2><ul>${caracteristicasHtml}</ul>` : ''}
                </article>

            </section>

            ${galeriaHtml ? `
            <section class="producto-galeria u-mt-lg">
                <h2>Galería</h2>
                <div class="arf-grid">${galeriaHtml}</div>
            </section>
            ` : ''}
        `;
    } catch (error) {
        console.error('Error procesando la ficha del terreno:', error);
        contenedor.innerHTML = `
            <section class="card u-mt-lg">
                <h1>Error de acceso</h1>
                <p class="u-text-muted">No se pudieron recuperar los datos del catálogo.</p>
            </section>
        `;
    }
}
