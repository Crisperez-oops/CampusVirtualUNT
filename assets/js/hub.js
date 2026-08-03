/**
 * assets/js/hub.js
 * Interacción ligera con el mapa 2D del Hub.
 * No requiere polling: los conteos se calculan en el servidor
 * al cargar la página (suficiente para hosting compartido).
 */

function mostrarDetalleFacultad(facultadId) {
    // Redirige al buscador de talentos pre-filtrado por esa facultad
    window.location.href = 'networking.php?facultad_id=' + encodeURIComponent(facultadId);
}
