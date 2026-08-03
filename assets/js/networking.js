/**
 * assets/js/networking.js
 * Buscador de Talentos asíncrono: llama a api/networking_api.php con
 * fetch() y pinta los resultados dinámicamente. Usa "debounce" para no
 * disparar una consulta por cada tecla (importante en hosting compartido
 * con límite de CPU como InfinityFree).
 */

(function () {
    const inputHabilidad = document.getElementById('inputHabilidad');
    const selectFacultad = document.getElementById('selectFacultad');
    const gridTalentos = document.getElementById('gridTalentos');

    let temporizadorDebounce = null;
    let controladorActual = null;

    function escapeHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function iniciales(nombre) {
        return (nombre || '?').trim().charAt(0).toUpperCase();
    }

    function pintarResultados(resultados) {
        if (!resultados || resultados.length === 0) {
            gridTalentos.innerHTML = '<div class="estado-vacio">No se encontraron estudiantes con esos filtros. Prueba con otra habilidad.</div>';
            return;
        }

        gridTalentos.innerHTML = resultados.map(function (estudiante) {
            const tags = (estudiante.habilidades_tags || '')
                .split(',')
                .map(function (t) { return t.trim(); })
                .filter(Boolean)
                .map(function (t) { return '<span class="tag-habilidad">' + escapeHtml(t) + '</span>'; })
                .join('');

            const descripcion = estudiante.descripcion
                ? escapeHtml(estudiante.descripcion)
                : 'Este estudiante aún no agregó una descripción.';

            const avatarHtml = estudiante.foto
                ? '<img src="' + escapeHtml(estudiante.foto) + '" alt="Avatar" style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;">'
                : '<div class="avatar-mini" style="background:' + escapeHtml(estudiante.avatar_color || '#3B82F6') + ';">' + iniciales(estudiante.nombre) + '</div>';

            return (
                '<div class="tarjeta-talento">' +
                    '<div class="tarjeta-talento-cabeza">' +
                        avatarHtml +
                        '<div>' +
                            '<div class="tarjeta-talento-nombre" style="cursor:pointer" onclick="window.location.href=\'perfil.php?id=' + estudiante.id + '\'">' + escapeHtml(estudiante.nombre) + '</div>' +
                            '<div class="tarjeta-talento-facultad" style="color:' + escapeHtml(estudiante.facultad_color || '#888') + ';">' +
                                escapeHtml(estudiante.facultad_nombre) +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tarjeta-talento-desc">' + descripcion + '</div>' +
                    (tags ? '<div class="tags-habilidades">' + tags + '</div>' : '') +
                    '<div style="display:flex;gap:6px;margin-top:8px;">' +
                        '<button class="btn-chatear" onclick="window.location.href=\'chat.php?con=' + estudiante.id + '\'">💬 Conversar</button>' +
                        (window.AMIGOS_IDS && window.AMIGOS_IDS.indexOf(estudiante.id) >= 0
                            ? '<button class="btn-chatear" style="background:#d1fae5;color:#065f46;cursor:default;">✓ Amigos</button>'
                            : window.PENDIENTES_ENVIADAS && window.PENDIENTES_ENVIADAS.indexOf(estudiante.id) >= 0
                                ? '<button class="btn-chatear" style="background:#fef3c7;color:#92400e;cursor:default;">⌛ Solicitud enviada</button>'
                                : '<button class="btn-chatear" style="background:var(--acento-suave);color:var(--acento);" onclick="solicitarAmistad(' + estudiante.id + ',this)">👥 Agregar</button>'
                        ) +
                    '</div>' +
                '</div>'
            );
        }).join('');
    }

    function buscarTalentos() {
        const habilidad = inputHabilidad.value.trim();
        const facultadId = selectFacultad.value;

        if (controladorActual) {
            controladorActual.abort();
        }
        controladorActual = new AbortController();

        const parametros = new URLSearchParams({
            facultad_id: facultadId,
            habilidad: habilidad,
        });

        gridTalentos.innerHTML = '<div class="estado-vacio">Buscando…</div>';

        fetch('api/networking_api.php?' + parametros.toString(), {
            method: 'GET',
            signal: controladorActual.signal,
            headers: { 'X-Requested-With': 'fetch' },
        })
            .then(function (respuesta) {
                if (respuesta.status === 401) {
                    window.location.href = 'login.php';
                    return null;
                }
                return respuesta.json();
            })
            .then(function (datos) {
                if (!datos) return;
                if (!datos.ok) {
                    gridTalentos.innerHTML = '<div class="estado-vacio">' + escapeHtml(datos.error || 'Ocurrió un error.') + '</div>';
                    return;
                }
                pintarResultados(datos.resultados);
            })
            .catch(function (err) {
                if (err.name === 'AbortError') return;
                gridTalentos.innerHTML = '<div class="estado-vacio">No se pudo conectar con el servidor.</div>';
            });
    }

    function buscarConDebounce() {
        clearTimeout(temporizadorDebounce);
        temporizadorDebounce = setTimeout(buscarTalentos, 350);
    }

    inputHabilidad.addEventListener('input', buscarConDebounce);
    selectFacultad.addEventListener('change', buscarTalentos);

    if (window.FACULTAD_PRESELECCIONADA && window.FACULTAD_PRESELECCIONADA > 0) {
        selectFacultad.value = String(window.FACULTAD_PRESELECCIONADA);
    }

    buscarTalentos();
})();

// Fuera del IIFE para que onclick pueda encontrarla
async function solicitarAmistad(receptorId, btn) {
    btn.disabled = true;
    btn.textContent = 'Enviando...';
    try {
        const r = await fetch('api/social_api.php?accion=solicitar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receptor_id: receptorId })
        });
        const data = await r.json();
        if (data.ok) {
            btn.textContent = '✓ Enviada';
            btn.style.background = '#34C759';
            btn.style.color = 'white';
            btn.style.border = 'none';
            if (window.PENDIENTES_ENVIADAS) window.PENDIENTES_ENVIADAS.push(receptorId);
        } else {
            btn.textContent = data.error || 'Ya enviada';
            btn.disabled = false;
            setTimeout(() => {
                btn.textContent = '👥 Agregar';
                btn.style.background = 'var(--acento-suave)';
                btn.style.color = 'var(--acento)';
            }, 2000);
        }
    } catch(e) {
        btn.textContent = 'Error';
        btn.disabled = false;
    }
}