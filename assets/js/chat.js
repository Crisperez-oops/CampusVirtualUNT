/**
 * assets/js/chat.js
 * Chat 1 a 1 con polling corto controlado (1.5s) en vez de WebSockets,
 * ya que InfinityFree no soporta conexiones persistentes.
 *
 * Optimizaciones para no saturar CPU del hosting gratuito:
 *  - Solo se hace polling de la conversación ABIERTA, no de todas.
 *  - Se usa "desde_id" para traer solo mensajes nuevos (no historial completo).
 *  - El polling se PAUSA si la pestaña pierde foco (Page Visibility API)
 *    y se reanuda al volver, con una recarga inmediata para no perder mensajes.
 *  - Si el usuario cambia de conversación, el intervalo anterior se cancela.
 *
 * NUEVO — visto/no visto tipo WhatsApp:
 *  - Cada mensaje propio muestra ✓ (enviado) o ✓✓ (leído, en color).
 *  - Abrir o hacer polling de una conversación la marca como leída en el
 *    servidor automáticamente (lo hace api/chat_api.php); acá solo hay
 *    que reflejarlo en pantalla.
 *  - Un poll aparte, más espaciado (6s), revisa cuántos mensajes
 *    pendientes hay en el resto de conversaciones (las que NO tienes
 *    abiertas) para pintar la insignia de "mensaje pendiente" en la
 *    lista, sin necesidad de abrirlas.
 */

(function () {
    const INTERVALO_POLLING_MS = 1500;  // conversación abierta
    const INTERVALO_RESUMEN_MS = 6000;  // insignias del resto de la lista

    const chatMensajes = document.getElementById('chatMensajes');
    const chatCabecera = document.getElementById('chatCabecera');
    const chatCabeceraAvatar = document.getElementById('chatCabeceraAvatar');
    const chatCabeceraNombre = document.getElementById('chatCabeceraNombre');
    const formChat = document.getElementById('formChat');
    const inputMensaje = document.getElementById('inputMensaje');
    const listaConversaciones = document.getElementById('listaConversaciones');

    const usuarioActualId = window.USUARIO_ACTUAL_ID;

    let contactoActualId = null;
    let ultimoIdVisto = 0;
    let intervaloPolling = null;
    let intervaloResumen = null;
    let enVuelo = false;
    let cargandoHistorial = false;

    // Ids de MIS PROPIOS mensajes que todavía muestran un solo check
    let idsPropiosSinConfirmar = new Set();

    function escapeHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function formatearHora(fechaSql) {
        // fechaSql viene como "YYYY-MM-DD HH:MM:SS" desde MySQL
        const fecha = new Date(fechaSql.replace(' ', 'T'));
        if (isNaN(fecha.getTime())) return '';
        return fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    }

    function detenerPolling() {
        if (intervaloPolling) {
            clearInterval(intervaloPolling);
            intervaloPolling = null;
        }
    }

    function iniciarPolling() {
        detenerPolling();
        intervaloPolling = setInterval(function () {
            // Si la pestaña no está visible, no gastamos CPU/ancho de banda
            if (document.hidden) return;
            cargarMensajesNuevos();
        }, INTERVALO_POLLING_MS);
    }

    /**
     * Pinta un mensaje y devuelve el elemento (para poder actualizarlo
     * después, p. ej. cuando se confirma que ya lo leyeron).
     */
    function pintarMensaje(msg) {
        const esPropio = parseInt(msg.emisor_id, 10) === usuarioActualId;
        const burbuja = document.createElement('div');
        burbuja.className = 'burbuja ' + (esPropio ? 'burbuja-propia' : 'burbuja-ajena');
        if (msg.id) burbuja.dataset.msgId = msg.id;

        let checkHtml = '';
        if (esPropio) {
            const yaVisto = !!msg.visto_en;
            checkHtml = '<span class="burbuja-check' + (yaVisto ? ' visto' : '') + '">' + (yaVisto ? '✓✓' : '✓') + '</span>';
            if (!yaVisto && msg.id) {
                idsPropiosSinConfirmar.add(parseInt(msg.id, 10));
            }
        }

        burbuja.innerHTML = escapeHtml(msg.mensaje) +
            '<span class="burbuja-hora">' + formatearHora(msg.creado_en) + '</span>' +
            checkHtml;
        chatMensajes.appendChild(burbuja);
        return burbuja;
    }

    function estaCercaDelFinal() {
        // Si el usuario hizo scroll hacia arriba para leer historial,
        // no lo interrumpimos saltando al final en cada poll.
        const margen = 80;
        return chatMensajes.scrollTop + chatMensajes.clientHeight >= chatMensajes.scrollHeight - margen;
    }

    /** Aplica los ids que el servidor confirmó como leídos (✓ -> ✓✓). */
    function aplicarConfirmadosLeidos(idsConfirmados) {
        if (!idsConfirmados || idsConfirmados.length === 0) return;
        idsConfirmados.forEach(function (id) {
            idsPropiosSinConfirmar.delete(id);
            const burbuja = chatMensajes.querySelector('[data-msg-id="' + id + '"] .burbuja-check');
            if (!burbuja) return;
            burbuja.textContent = '✓✓';
            burbuja.classList.add('visto', 'recien-confirmado');
        });
    }

    function cargarHistorialCompleto(contactoId) {
        cargandoHistorial = true;
        detenerPolling();
        chatMensajes.innerHTML = '<div class="chat-vacio">Cargando conversación…</div>';
        idsPropiosSinConfirmar = new Set();

        fetch('api/chat_api.php?receptor_id=' + encodeURIComponent(contactoId), {
            headers: { 'X-Requested-With': 'fetch' },
        })
            .then(function (r) {
                if (r.status === 401) { window.location.href = 'login.php'; return null; }
                return r.json();
            })
            .then(function (datos) {
                if (!datos) return;
                chatMensajes.innerHTML = '';

                if (!datos.ok) {
                    chatMensajes.innerHTML = '<div class="chat-vacio">' + escapeHtml(datos.error || 'Error al cargar el chat.') + '</div>';
                    cargandoHistorial = false;
                    return;
                }

                if (datos.mensajes.length === 0) {
                    chatMensajes.innerHTML = '<div class="chat-vacio">Aún no hay mensajes. ¡Saluda primero! 👋</div>';
                } else {
                    datos.mensajes.forEach(pintarMensaje);
                    ultimoIdVisto = datos.mensajes[datos.mensajes.length - 1].id;
                }

                chatMensajes.scrollTop = chatMensajes.scrollHeight;
                cargandoHistorial = false;
                iniciarPolling();

                // Esta conversación ya se marcó como leída en el servidor
                // (chat_api.php lo hace en cada GET) — reflejarlo ya mismo
                // en la lista, sin esperar al próximo poll de resumen.
                ocultarBadgeDeContacto(contactoId);
            })
            .catch(function () {
                cargandoHistorial = false;
                chatMensajes.innerHTML = '<div class="chat-vacio">No se pudo conectar con el servidor.</div>';
            });
    }

    function cargarMensajesNuevos() {
        if (!contactoActualId || enVuelo || cargandoHistorial) return;
        enVuelo = true;

        const parametros = new URLSearchParams({
            receptor_id: contactoActualId,
            desde_id: ultimoIdVisto,
        });
        if (idsPropiosSinConfirmar.size > 0) {
            parametros.set('confirmar', Array.from(idsPropiosSinConfirmar).join(','));
        }

        fetch('api/chat_api.php?' + parametros.toString(), {
            headers: { 'X-Requested-With': 'fetch' },
        })
            .then(function (r) {
                if (r.status === 401) { window.location.href = 'login.php'; return null; }
                return r.json();
            })
            .then(function (datos) {
                if (!datos || !datos.ok) return;

                aplicarConfirmadosLeidos(datos.confirmados_leidos);

                if (datos.mensajes.length === 0) return;

                const debeHacerScroll = estaCercaDelFinal();
                datos.mensajes.forEach(pintarMensaje);
                ultimoIdVisto = datos.mensajes[datos.mensajes.length - 1].id;

                if (debeHacerScroll) {
                    chatMensajes.scrollTop = chatMensajes.scrollHeight;
                }

                // Llegaron mensajes nuevos de esta conversación (la tengo
                // abierta), así que el servidor ya los marcó como leídos:
                // no debe quedar insignia pendiente para este contacto.
                ocultarBadgeDeContacto(contactoActualId);
            })
            .catch(function () { /* fallo silencioso: se reintenta en el próximo ciclo */ })
            .finally(function () { enVuelo = false; });
    }

    /* ---------------- Insignias de pendientes en la lista ---------------- */

    function ocultarBadgeDeContacto(contactoId) {
        const item = listaConversaciones.querySelector('.item-conversacion[data-usuario-id="' + contactoId + '"]');
        if (!item) return;
        item.classList.remove('no-leido');
        const badge = item.querySelector('.conv-badge');
        if (badge) {
            badge.classList.add('oculto');
            badge.textContent = '0';
        }
    }

    function actualizarBadge(contactoId, cantidad) {
        // Nunca pisamos la conversación que está abierta ahora mismo:
        // esa ya se está marcando como leída por su propio polling.
        if (contactoId === contactoActualId) return;

        const item = listaConversaciones.querySelector('.item-conversacion[data-usuario-id="' + contactoId + '"]');
        if (!item) return; // el contacto todavía no tiene fila en la lista (primer mensaje que recibes de alguien nuevo)

        const badge = item.querySelector('.conv-badge');
        if (!badge) return;

        if (cantidad > 0) {
            item.classList.add('no-leido');
            badge.classList.remove('oculto');
            badge.textContent = cantidad > 9 ? '9+' : String(cantidad);
        } else {
            item.classList.remove('no-leido');
            badge.classList.add('oculto');
        }
    }

    function consultarResumenPendientes() {
        if (document.hidden) return;
        fetch('api/chat_api.php?resumen=1', { headers: { 'X-Requested-With': 'fetch' } })
            .then(function (r) { return r.json(); })
            .then(function (datos) {
                if (!datos || !datos.ok) return;
                const porContacto = datos.no_leidos_por_contacto || {};
                // Actualiza cada fila de la lista con lo que diga el resumen;
                // si un contacto ya no aparece ahí, es que no tiene pendientes.
                listaConversaciones.querySelectorAll('.item-conversacion').forEach(function (item) {
                    const id = item.dataset.usuarioId;
                    actualizarBadge(id, porContacto[id] || 0);
                });
            })
            .catch(function () { /* se reintenta en el próximo ciclo */ });
    }

    function iniciarPollingResumen() {
        consultarResumenPendientes();
        intervaloResumen = setInterval(consultarResumenPendientes, INTERVALO_RESUMEN_MS);
    }

    /* ---------------- Abrir conversación ---------------- */

    function abrirConversacion(contactoId, nombre, color) {
        detenerPolling();
        contactoActualId = parseInt(contactoId, 10);
        ultimoIdVisto = 0;

        chatCabecera.style.display = 'flex';
        chatCabeceraAvatar.style.background = color || '#3B82F6';
        chatCabeceraAvatar.textContent = (nombre || '?').trim().charAt(0).toUpperCase();
        chatCabeceraNombre.textContent = nombre || '';
        formChat.style.display = 'flex';

        // Marca visualmente cuál conversación está activa en la lista
        document.querySelectorAll('.item-conversacion').forEach(function (el) {
            el.classList.toggle('activa', parseInt(el.dataset.usuarioId, 10) === contactoActualId);
        });

        cargarHistorialCompleto(contactoActualId);
    }

    // Delegación de eventos: un solo listener para todos los items de conversación
    listaConversaciones.addEventListener('click', function (evento) {
        const item = evento.target.closest('.item-conversacion');
        if (!item) return;
        abrirConversacion(item.dataset.usuarioId, item.dataset.usuarioNombre, item.dataset.usuarioColor);
    });

    formChat.addEventListener('submit', function (evento) {
        evento.preventDefault();
        const texto = inputMensaje.value.trim();
        if (!texto || !contactoActualId) return;

        inputMensaje.value = '';
        inputMensaje.focus();

        // Optimismo en la UI: pintamos el mensaje al instante, sin esperar al servidor
        const burbujaLocal = pintarMensaje({
            emisor_id: usuarioActualId,
            mensaje: texto,
            creado_en: new Date().toISOString().slice(0, 19).replace('T', ' '),
        });
        chatMensajes.scrollTop = chatMensajes.scrollHeight;

        fetch('api/chat_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ receptor_id: contactoActualId, mensaje: texto }),
        })
            .then(function (r) { return r.json(); })
            .then(function (datos) {
                if (datos && datos.ok && datos.id) {
                    ultimoIdVisto = Math.max(ultimoIdVisto, datos.id);
                    // Ya sabemos el id real: lo anotamos en la burbuja optimista
                    // y lo metemos a la lista de "esperando doble check".
                    burbujaLocal.dataset.msgId = datos.id;
                    idsPropiosSinConfirmar.add(parseInt(datos.id, 10));
                }
                if (!datos || !datos.ok) {
                    console.warn('No se pudo enviar el mensaje:', datos && datos.error);
                }
            })
            .catch(function () {
                console.warn('Error de red al enviar el mensaje.');
            });
    });

    // Pausa el polling si el usuario cambia de pestaña/minimiza, y
    // refresca inmediatamente al volver (en vez de esperar el próximo ciclo).
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) return;
        if (contactoActualId) cargarMensajesNuevos();
        consultarResumenPendientes();
    });

    // Si venimos de networking.php con ?con=ID, esa conversación ya está
    // pre-pintada en el HTML como "activa": la abrimos automáticamente.
    if (window.CONTACTO_INICIAL_ID) {
        const itemInicial = document.querySelector('.item-conversacion[data-usuario-id="' + window.CONTACTO_INICIAL_ID + '"]');
        if (itemInicial) {
            abrirConversacion(itemInicial.dataset.usuarioId, itemInicial.dataset.usuarioNombre, itemInicial.dataset.usuarioColor);
        }
    }

    iniciarPollingResumen();

    // Si el usuario cierra/recarga la página, no es necesario limpiar los
    // intervalos manualmente: el navegador destruye el contexto JS completo.
})();