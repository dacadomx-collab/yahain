// terrenos/js/app.js — Controlador de la vitrina pública de terrenos.
// Consulta data/listings.json e inyecta tarjetas bajo la arquitectura ARF-Grid
// ya definida en assets/css/main.css (.arf-grid / .arf-col-3 / .card).

document.addEventListener('DOMContentLoaded', () => {
    cargarTerrenos();
});

async function cargarTerrenos() {
    const contenedor = document.getElementById('gridTerrenos');
    if (!contenedor) {
        return;
    }

    try {
        const respuesta = await fetch('data/listings.json');
        if (!respuesta.ok) {
            throw new Error(`Estado HTTP ${respuesta.status}`);
        }
        const terrenos = await respuesta.json();

        contenedor.innerHTML = '';

        terrenos.forEach((terreno) => {
            const portada = (terreno.imagenes && terreno.imagenes.length > 0)
                ? terreno.imagenes[0]
                : '';

            const articulo = document.createElement('article');
            articulo.className = 'card arf-col-3 terreno-card';

            articulo.innerHTML = `
                <div class="terreno-card__media">
                    <span class="badge-estatus terreno-card__badge">${terreno.id}</span>
                    <img src="${portada}" alt="${terreno.titulo}" loading="lazy" width="800" height="500">
                </div>
                <div class="terreno-card__body">
                    <span class="terreno-card__precio">${terreno.precioFormateado}</span>
                    <h2 class="terreno-card__titulo">${terreno.titulo}</h2>
                    <div class="terreno-card__specs">
                        <div>
                            <span>Superficie</span>
                            <strong>${Number(terreno.superficieM2).toLocaleString('es-MX')} m²</strong>
                        </div>
                        <div>
                            <span>Escala</span>
                            <strong>${terreno.superficieAcres} Acres</strong>
                        </div>
                    </div>
                </div>
            `;

            articulo.addEventListener('click', () => {
                window.location.href = `ficha.html?id=${encodeURIComponent(terreno.id)}`;
            });

            contenedor.appendChild(articulo);
        });
    } catch (error) {
        console.error('Error recuperando el catálogo de terrenos:', error);
        contenedor.innerHTML = `
            <p class="aviso-catalogo">No fue posible cargar el catálogo en este momento. Por favor intente de nuevo más tarde.</p>
        `;
    }
}
