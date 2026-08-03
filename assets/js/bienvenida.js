/**
 * bienvenida.js — lógica de la pantalla de bienvenida (index.php)
 * -----------------------------------------------------------------
 * Todo aquí es progresivo: si un elemento no existe en el DOM,
 * la función correspondiente simplemente no hace nada. Respeta
 * prefers-reduced-motion para toda animación decorativa.
 */
(function () {
    'use strict';

    var prefiereMenosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- Corrige el scroll del tablón al recargar ---------------- */
    // NUEVO: evita que el navegador restaure un scrollLeft viejo del tablón
    // de anuncios, lo que dejaba la primera tarjeta recortada al cargar.
    function corregirScrollTablon() {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        var tablon = document.getElementById('tablonAnuncios') || document.querySelector('.hub-tablon-lista');
        if (tablon) {
            tablon.scrollLeft = 0;
        }
    }

    /* ---------------- Estrellas (noche) ---------------- */
    function pintarEstrellas() {
        var contenedor = document.getElementById('particulas');
        if (!contenedor || prefiereMenosMovimiento) return;
        var total = 26;
        for (var i = 0; i < total; i++) {
            var e = document.createElement('span');
            e.className = 'estrella';
            e.style.top = Math.random() * 100 + '%';
            e.style.left = Math.random() * 100 + '%';
            e.style.animationDuration = (2 + Math.random() * 3).toFixed(2) + 's';
            e.style.animationDelay = (Math.random() * 3).toFixed(2) + 's';
            contenedor.appendChild(e);
        }
    }

    /* ---------------- Garúa (tarde nublada) ---------------- */
    function pintarGarua() {
        var contenedor = document.getElementById('garua');
        if (!contenedor || prefiereMenosMovimiento) return;
        var total = 34;
        for (var i = 0; i < total; i++) {
            var g = document.createElement('span');
            g.className = 'gota';
            g.style.left = Math.random() * 100 + '%';
            g.style.animationDuration = (1.4 + Math.random() * 1.2).toFixed(2) + 's';
            g.style.animationDelay = (Math.random() * 2).toFixed(2) + 's';
            contenedor.appendChild(g);
        }
    }

    /* ---------------- Reloj de campanario ---------------- */
    function iniciarReloj() {
        var el = document.getElementById('relojCampanario');
        if (!el) return;
        function actualizar() {
            var ahora = new Date();
            var hh = String(ahora.getHours()).padStart(2, '0');
            var mm = String(ahora.getMinutes()).padStart(2, '0');
            el.textContent = hh + ':' + mm;
        }
        actualizar();
        setInterval(actualizar, 15000);
    }

    /* ---------------- Campana / easter egg ---------------- */
    function iniciarCampana() {
        var boton = document.getElementById('avatarCampanario');
        var campana = document.getElementById('campanaIcono');
        if (!boton || !campana) return;
        boton.addEventListener('click', function () {
            if (!prefiereMenosMovimiento) {
                campana.classList.remove('sonando');
                // forzar reflow para poder repetir la animación
                void campana.offsetWidth;
                campana.classList.add('sonando');
            }
        });
    }

    /* ---------------- Buscador en vivo del directorio ---------------- */
    function normalizar(texto) {
        return texto
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filtrarPuertas(consulta) {
        var puertas = document.querySelectorAll('#puertasGrid .puerta');
        var vacio = document.getElementById('puertasVacio');
        var q = normalizar(consulta.trim());
        var visibles = 0;

        puertas.forEach(function (p) {
            var titulo = normalizar(p.dataset.titulo || '');
            var desc = normalizar(p.dataset.desc || '');
            var coincide = q === '' || titulo.indexOf(q) !== -1 || desc.indexOf(q) !== -1;
            p.classList.toggle('oculto', !coincide);
            if (coincide) visibles++;
        });

        if (vacio) vacio.classList.toggle('oculto', visibles !== 0);
    }

    /* ---------------- Paleta de comandos (Ctrl/Cmd + K) ---------------- */
    function iniciarComandos() {
        var datosEl = document.getElementById('datosPuertas');
        var modal = document.getElementById('modalComandos');
        var input = document.getElementById('inputComandos');
        var lista = document.getElementById('listaComandos');
        var boton = document.getElementById('abrirComandos');
        if (!datosEl || !modal || !input || !lista) return;

        var modulos = [];
        try {
            modulos = JSON.parse(datosEl.textContent);
        } catch (e) {
            modulos = [];
        }

        var seleccionado = 0;
        var visiblesActuales = modulos;

        function render(filtro) {
            var q = normalizar((filtro || '').trim());
            visiblesActuales = modulos.filter(function (m) {
                return q === '' || normalizar(m.titulo).indexOf(q) !== -1 || normalizar(m.desc).indexOf(q) !== -1;
            });
            lista.innerHTML = '';
            visiblesActuales.forEach(function (m, i) {
                var li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', i === seleccionado ? 'true' : 'false');
                li.innerHTML =
                    '<span class="icono">' + m.icono + '</span>' +
                    '<span class="titulo">' + m.titulo + '</span>' +
                    '<span class="desc">' + m.desc + '</span>';
                li.addEventListener('click', function () { ir(m); });
                lista.appendChild(li);
            });
        }

        function ir(modulo) {
            if (modulo && modulo.href) window.location.href = modulo.href;
        }

        function abrir() {
            modal.hidden = false;
            input.value = '';
            seleccionado = 0;
            render('');
            // Forzar reflow antes de agregar la clase, para que la transición sí se dispare
            void modal.offsetWidth;
            modal.classList.add('mostrar');
            setTimeout(function () { input.focus(); }, 0);
            // también refleja la búsqueda en el grid de puertas de la página
            filtrarPuertas('');
        }

        function cerrar() {
            modal.classList.remove('mostrar');
            var prefiereMenosMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var espera = prefiereMenosMovimiento ? 0 : 220;
            setTimeout(function () { modal.hidden = true; }, espera);
        }

        if (boton) boton.addEventListener('click', abrir);

        document.addEventListener('keydown', function (ev) {
            var esAtajoAbrir = (ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'k';
            if (esAtajoAbrir) {
                ev.preventDefault();
                modal.hidden ? abrir() : cerrar();
                return;
            }
            if (!modal.hidden && ev.key === 'Escape') {
                cerrar();
            }
        });

        modal.addEventListener('click', function (ev) {
            if (ev.target === modal) cerrar();
        });

        input.addEventListener('input', function () {
            seleccionado = 0;
            render(input.value);
        });

        input.addEventListener('keydown', function (ev) {
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                seleccionado = Math.min(seleccionado + 1, visiblesActuales.length - 1);
                render(input.value);
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                seleccionado = Math.max(seleccionado - 1, 0);
                render(input.value);
            } else if (ev.key === 'Enter') {
                ev.preventDefault();
                ir(visiblesActuales[seleccionado]);
            }
        });
    }

    /* ---------------- Buscador visible bajo el hero (si se agrega input propio) ---------------- */
    function iniciarBuscadorGrid() {
        // Si en el futuro agregas un <input> visible fuera del modal,
        // solo dale id="buscadorPuertas" y este bloque lo conecta solo.
        var input = document.getElementById('buscadorPuertas');
        if (!input) return;
        input.addEventListener('input', function () {
            filtrarPuertas(input.value);
        });
    }

    /* ---------------- Reto del día ---------------- */
    function iniciarRetoDelDia() {
        var seccion = document.getElementById('retoDelDia');
        if (!seccion) return;
        var opciones = document.querySelectorAll('#retoOpciones .hub-reto-opcion');
        var resultado = document.getElementById('retoResultado');
        var correcta = parseInt(seccion.dataset.correcta, 10);
        var clave = 'sv_reto_' + seccion.dataset.fecha;

        function bloquear(indiceElegido) {
            opciones.forEach(function (btn, i) {
                btn.disabled = true;
                if (i === correcta) btn.classList.add('correcta');
                if (i === indiceElegido && indiceElegido !== correcta) btn.classList.add('incorrecta');
            });
        }

        function mostrarResultado(indiceElegido) {
            resultado.classList.remove('oculto');
            resultado.textContent = indiceElegido === correcta
                ? '¡Correcto! +10 puntos por hoy.'
                : 'No era esa — la próxima vez será.';
            resultado.classList.toggle('acierto', indiceElegido === correcta);
        }

        // Si ya respondió hoy, restaura el estado guardado en vez de dejarlo responder de nuevo.
        var guardado = null;
        try { guardado = JSON.parse(localStorage.getItem(clave)); } catch (e) { guardado = null; }
        if (guardado && typeof guardado.indice === 'number') {
            bloquear(guardado.indice);
            mostrarResultado(guardado.indice);
            return;
        }

        opciones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var indice = parseInt(btn.dataset.indice, 10);
                bloquear(indice);
                mostrarResultado(indice);
                try {
                    localStorage.setItem(clave, JSON.stringify({ indice: indice }));
                } catch (e) { /* almacenamiento no disponible, no pasa nada */ }
            });
        });
    }

    /* ---------------- Favoritos (persisten en localStorage por navegador) ---------------- */
    function iniciarFavoritos() {
        var CLAVE = 'sv_favoritos_puertas';
        var franja = document.getElementById('franjaFavoritos');
        var listaEl = document.getElementById('listaFavoritos');
        var datosEl = document.getElementById('datosPuertas');
        if (!franja || !listaEl || !datosEl) return;

        var modulos = [];
        try { modulos = JSON.parse(datosEl.textContent); } catch (e) { modulos = []; }

        function leerFavoritos() {
            try { return JSON.parse(localStorage.getItem(CLAVE)) || []; } catch (e) { return []; }
        }
        function guardarFavoritos(lista) {
            try { localStorage.setItem(CLAVE, JSON.stringify(lista)); } catch (e) { /* sin storage disponible */ }
        }

        var favoritos = leerFavoritos().filter(function (href) {
            return modulos.some(function (m) { return m.href === href; });
        });

        function actualizarBotonesPin() {
            document.querySelectorAll('.puerta-fav').forEach(function (btn) {
                var esFavorito = favoritos.indexOf(btn.dataset.href) !== -1;
                btn.classList.toggle('activo', esFavorito);
                btn.setAttribute('aria-pressed', esFavorito ? 'true' : 'false');
            });
        }

        function renderFranja() {
            if (favoritos.length === 0) {
                franja.classList.add('oculto');
                listaEl.innerHTML = '';
                return;
            }
            franja.classList.remove('oculto');
            listaEl.innerHTML = '';
            favoritos.forEach(function (href) {
                var mod = modulos.filter(function (m) { return m.href === href; })[0];
                if (!mod) return;
                var chip = document.createElement('div');
                chip.className = 'hub-favorito-chip';
                chip.draggable = true;
                chip.dataset.href = href;
                chip.setAttribute('role', 'link');
                chip.setAttribute('tabindex', '0');
                chip.innerHTML =
                    '<span class="icono">' + mod.icono + '</span>' +
                    '<span class="titulo">' + mod.titulo + '</span>' +
                    '<button type="button" class="hub-favorito-quitar" aria-label="Quitar ' + mod.titulo + ' de favoritos">×</button>';
                listaEl.appendChild(chip);
            });
        }

        function alternarFavorito(href) {
            var i = favoritos.indexOf(href);
            if (i === -1) favoritos.push(href); else favoritos.splice(i, 1);
            guardarFavoritos(favoritos);
            actualizarBotonesPin();
            renderFranja();
        }

        // Botones de pin en el directorio principal
        document.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.puerta-fav');
            if (!btn) return;
            ev.preventDefault();
            alternarFavorito(btn.dataset.href);
        });

        // Clic en un chip de la franja de favoritos: navega, salvo que sea el botón de quitar
        listaEl.addEventListener('click', function (ev) {
            var quitar = ev.target.closest('.hub-favorito-quitar');
            var chip = ev.target.closest('.hub-favorito-chip');
            if (!chip) return;
            if (quitar) {
                alternarFavorito(chip.dataset.href);
                return;
            }
            window.location.href = chip.dataset.href;
        });

        listaEl.addEventListener('keydown', function (ev) {
            var chip = ev.target.closest('.hub-favorito-chip');
            if (chip && (ev.key === 'Enter' || ev.key === ' ')) {
                ev.preventDefault();
                window.location.href = chip.dataset.href;
            }
        });

        // Reordenar arrastrando chips dentro de la franja
        var arrastrando = null;
        listaEl.addEventListener('dragstart', function (ev) {
            arrastrando = ev.target.closest('.hub-favorito-chip');
            if (arrastrando) ev.dataTransfer.effectAllowed = 'move';
        });
        listaEl.addEventListener('dragover', function (ev) {
            ev.preventDefault();
            var sobre = ev.target.closest('.hub-favorito-chip');
            if (!sobre || sobre === arrastrando || !arrastrando) return;
            var rect = sobre.getBoundingClientRect();
            var mitad = rect.left + rect.width / 2;
            if (ev.clientX < mitad) {
                listaEl.insertBefore(arrastrando, sobre);
            } else {
                listaEl.insertBefore(arrastrando, sobre.nextSibling);
            }
        });
        listaEl.addEventListener('drop', function (ev) {
            ev.preventDefault();
        });
        listaEl.addEventListener('dragend', function () {
            favoritos = Array.prototype.slice.call(listaEl.querySelectorAll('.hub-favorito-chip')).map(function (c) {
                return c.dataset.href;
            });
            guardarFavoritos(favoritos);
            arrastrando = null;
        });

        actualizarBotonesPin();
        renderFranja();
    }

    /* ---------------- Atajos de una sola tecla sobre cada puerta ---------------- */
    function iniciarAtajosPuertas() {
        document.addEventListener('keydown', function (ev) {
            var modal = document.getElementById('modalComandos');
            if (modal && !modal.hidden) return; // no interferir con la paleta de comandos
            var activo = document.activeElement;
            var enCampo = activo && (activo.tagName === 'INPUT' || activo.tagName === 'TEXTAREA');
            if (enCampo) return;

            var puertas = document.querySelectorAll('#puertasGrid .puerta');
            puertas.forEach(function (p) {
                var atajo = p.querySelector('.puerta-atajo');
                if (atajo && atajo.textContent.trim().toLowerCase() === ev.key.toLowerCase()) {
                    window.location.href = p.dataset.href;
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        corregirScrollTablon(); // NUEVO — corre primero
        pintarEstrellas();
        pintarGarua();
        iniciarReloj();
        iniciarCampana();
        iniciarComandos();
        iniciarBuscadorGrid();
        iniciarAtajosPuertas();
        iniciarRetoDelDia();
        iniciarFavoritos();
    });

    // NUEVO: refuerzo — algunos navegadores restauran el scroll DESPUÉS
    // de DOMContentLoaded, así que lo corregimos otra vez en 'load'.
    window.addEventListener('load', corregirScrollTablon);
})();