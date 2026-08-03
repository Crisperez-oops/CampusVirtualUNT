<?php
/**
 * jugar_batalla.php
 * Pantalla del participante. Si no se ha unido aún a esta sala en la
 * sesión actual, lo manda a unirse primero.
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/SalaBatalla.php';

requerirSesion();

$codigo = preg_replace('/\D/', '', $_GET['codigo'] ?? '');
$sala   = $codigo ? SalaBatalla::obtenerPorCodigo($codigo) : null;

if (!$sala) {
    header('Location: unirse_batalla.php');
    exit;
}

$claveSesion = 'batalla_participante_id_' . $sala['id'];
if (!isset($_SESSION[$claveSesion])) {
    header('Location: unirse_batalla.php');
    exit;
}

$participanteId = (int) $_SESSION[$claveSesion];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Jugando · <?= htmlspecialchars($sala['titulo']) ?></title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { min-height: 100vh; }
.jb-wrap { max-width: 560px; margin: 0 auto; padding: 24px 16px 80px; }
.jb-topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; }
.jb-codigo { font-size: 12px; color: var(--texto-tenue); }
.jb-puntaje { font-family: var(--fuente-display); font-weight: 700; color: var(--acento); font-size: 15px; }

.jb-panel { background: var(--bg-panel); border: 1px solid var(--linea); border-radius: 16px; padding: 26px; text-align: center; }

.jb-espera-icono { font-size: 40px; margin-bottom: 10px; }
.jb-espera-txt { font-size: 14px; color: var(--texto-tenue); }

.jb-progreso-preg { font-size: 12px; color: var(--texto-tenue); margin-bottom: 8px; }
.jb-timer { font-family: var(--fuente-display); font-size: 40px; font-weight: 700; color: var(--acento); margin-bottom: 6px; }
.jb-timer-track { height: 6px; background: var(--bg-panel-alt); border-radius: 6px; overflow: hidden; margin-bottom: 18px; }
.jb-timer-fill { height: 100%; background: var(--acento); transition: width 1s linear; }
.jb-pregunta-texto { font-family: var(--fuente-display); font-size: 18px; font-weight: 700; margin-bottom: 20px; line-height: 1.35; }

.jb-opciones { display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.jb-opcion-btn {
    padding: 20px 14px; border-radius: 12px; border: 2px solid var(--linea); background: var(--bg-panel-alt);
    color: var(--texto-principal); font-size: 14px; font-weight: 600; cursor: pointer; transition: transform .1s, border-color .15s;
}
.jb-opcion-btn:hover { border-color: var(--acento); transform: translateY(-2px); }
.jb-opcion-btn:disabled { opacity: .5; cursor: not-allowed; transform:none; }
.jb-opcion-btn.elegida { border-color: var(--acento); background: var(--acento-suave); color: var(--acento); }
.jb-opcion-a { border-color: #EF4444; } .jb-opcion-b { border-color: #3B82F6; }
.jb-opcion-c { border-color: #F59E0B; } .jb-opcion-d { border-color: #22C55E; }

.jb-resultado-icono { font-size: 44px; margin-bottom: 10px; }
.jb-resultado-txt { font-family: var(--fuente-display); font-size: 20px; font-weight: 700; margin-bottom: 6px; }
.jb-resultado-puntos { font-size: 14px; color: var(--texto-tenue); margin-bottom: 18px; }
.jb-resultado-ok { color: var(--ok); }
.jb-resultado-mal { color: var(--error); }

.jb-lb-mini { display:flex; flex-direction:column; gap: 6px; text-align:left; margin-top: 6px; }
.jb-lb-mini-fila { display:flex; align-items:center; gap: 10px; background: var(--bg-panel-alt); border:1px solid var(--linea); border-radius: 8px; padding: 8px 12px; font-size: 13px; }
.jb-lb-mini-fila.yo { border-color: var(--acento); }
.jb-lb-mini-dot { width: 18px; height:18px; border-radius:50%; flex-shrink:0; }
.jb-lb-mini-nombre { flex:1; }
.jb-lb-mini-puntos { font-weight:700; color: var(--acento); }
</style>
</head>
<body>
<?php require __DIR__ . '/vistas/topbar.php'; ?>
<div class="jb-wrap">
    <div class="jb-topbar">
        <span class="jb-codigo">Sala <?= htmlspecialchars($sala['codigo']) ?></span>
        <span class="jb-puntaje">⭐ <span id="jb-mi-puntaje">0</span> pts</span>
    </div>

    <!-- ESPERA -->
    <div class="jb-panel" id="jb-zona-espera">
        <div class="jb-espera-icono">⏳</div>
        <div style="font-family: var(--fuente-display); font-weight:700; font-size:16px; margin-bottom:6px;">
            Esperando a que el organizador inicie…
        </div>
        <div class="jb-espera-txt">¡Prepárate! La primera pregunta está por llegar.</div>
        <a href="battles.php" style="display:inline-block;margin-top:14px;padding:8px 20px;border-radius:8px;border:1px solid var(--linea);color:var(--texto-tenue);text-decoration:none;font-size:12.5px">← Salir</a>
    </div>

    <!-- PREGUNTA -->
    <div class="jb-panel" id="jb-zona-pregunta" style="display:none; text-align:left;">
        <div class="jb-progreso-preg">Pregunta <span id="jb-num-preg"></span> de <span id="jb-total-preg"></span></div>
        <div style="text-align:center;">
            <div class="jb-timer" id="jb-timer">--</div>
        </div>
        <div class="jb-timer-track"><div class="jb-timer-fill" id="jb-timer-fill" style="width:100%;"></div></div>
        <div class="jb-pregunta-texto" id="jb-texto-preg"></div>
        <div class="jb-opciones" id="jb-opciones"></div>
    </div>

    <!-- RESULTADO DE MI RESPUESTA / PREGUNTA CERRADA -->
    <div class="jb-panel" id="jb-zona-resultado" style="display:none;">
        <div class="jb-resultado-icono" id="jb-resultado-icono">🤔</div>
        <div class="jb-resultado-txt" id="jb-resultado-txt"></div>
        <div class="jb-resultado-puntos" id="jb-resultado-puntos"></div>
        <p style="font-size:11.5px; color:var(--texto-tenue); margin: 4px 0 12px;">
            Siguiente pregunta en <span id="jb-segundos-siguiente">5</span>s…
        </p>
        <div class="jb-lb-mini" id="jb-lb-mini"></div>
    </div>

    <!-- FINAL -->
    <div class="jb-panel" id="jb-zona-final" style="display:none;">
        <div class="jb-resultado-icono">🏁</div>
        <div class="jb-resultado-txt">¡Batalla finalizada!</div>
        <div class="jb-lb-mini" id="jb-lb-final"></div>
        <a href="battles.php" style="display:inline-block;margin-top:16px;padding:10px 24px;border-radius:10px;background:var(--acento);color:#fff;font-weight:700;text-decoration:none;font-size:13px">← Volver al inicio</a>
    </div>
</div>

<script>
const CODIGO_SALA = <?= json_encode($sala['codigo']) ?>;
const SALA_ID = <?= (int) $sala['id'] ?>;
const PARTICIPANTE_ID = <?= (int) $participanteId ?>;

let estadoConocido = '', preguntaConocida = -1, respuestasConocidas = -1;
let yaRespondiPreguntaOrden = -1;
let cuentaRegresivaInterval = null;
let pollTimer = null;

function mostrarZona(zona) {
    ['espera','pregunta','resultado','final'].forEach(z => {
        document.getElementById('jb-zona-' + z).style.display = (z === zona) ? 'block' : 'none';
    });
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function pintarLeaderboardMini(contenedorId, lb) {
    const cont = document.getElementById(contenedorId);
    cont.innerHTML = lb.map((p, i) => `
        <div class="jb-lb-mini-fila ${p.id === PARTICIPANTE_ID ? 'yo' : ''}">
            <span>#${i + 1}</span>
            <div class="jb-lb-mini-dot" style="background:${p.avatar_color}"></div>
            <div class="jb-lb-mini-nombre">${escHtml(p.apodo)}</div>
            <div class="jb-lb-mini-puntos">${p.puntaje_total} pts</div>
        </div>
    `).join('');

    const yo = lb.find(p => p.id === PARTICIPANTE_ID);
    if (yo) document.getElementById('jb-mi-puntaje').textContent = yo.puntaje_total;
}

async function enviarRespuesta(letra, boton) {
    document.querySelectorAll('.jb-opcion-btn').forEach(b => b.disabled = true);
    boton.classList.add('elegida');

    const fd = new FormData();
    fd.append('sala_id', SALA_ID);
    fd.append('participante_id', PARTICIPANTE_ID);
    fd.append('opcion', letra);

    try {
        const res = await fetch('api/batalla_responder.php', { method: 'POST', body: fd });
        const data = await res.json();

        mostrarZona('resultado');
        clearInterval(cuentaRegresivaInterval);

        if (data.ok) {
            document.getElementById('jb-resultado-icono').textContent = data.es_correcta ? '✅' : '❌';
            document.getElementById('jb-resultado-txt').textContent = data.es_correcta ? '¡Correcto!' : 'Incorrecto';
            document.getElementById('jb-resultado-txt').className =
                'jb-resultado-txt ' + (data.es_correcta ? 'jb-resultado-ok' : 'jb-resultado-mal');
            document.getElementById('jb-resultado-puntos').textContent =
                data.es_correcta ? `+${data.puntos} puntos` : 'Esta vez no sumaste puntos.';
        } else {
            document.getElementById('jb-resultado-icono').textContent = '⏱️';
            document.getElementById('jb-resultado-txt').textContent = 'Respuesta registrada';
            document.getElementById('jb-resultado-puntos').textContent = data.error || '';
        }
    } catch (e) {
        // si falla la red, igual queda deshabilitado; el estado se resincroniza en el próximo poll
    }
}

function iniciarTimerVisual(segundosRestantes, segundosTotal) {
    clearInterval(cuentaRegresivaInterval);
    let restante = segundosRestantes;

    const pintar = () => {
        document.getElementById('jb-timer').textContent = restante + 's';
        document.getElementById('jb-timer-fill').style.width = Math.max(0, (restante / segundosTotal) * 100) + '%';
    };
    pintar();

    cuentaRegresivaInterval = setInterval(() => {
        restante = Math.max(0, restante - 1);
        pintar();
        if (restante <= 0) clearInterval(cuentaRegresivaInterval);
    }, 1000);
}

async function consultarEstado(inmediato = false) {
    try {
        const params = new URLSearchParams({
            codigo: CODIGO_SALA,
            estado_conocido: inmediato ? '' : estadoConocido,
            pregunta_conocida: inmediato ? -1 : preguntaConocida,
            respuestas_conocidas: inmediato ? -1 : respuestasConocidas,
        });
        const res = await fetch('api/batalla_estado.php?' + params.toString());
        const data = await res.json();

        if (!data.existe) { setTimeout(() => consultarEstado(), 2000); return; }

        estadoConocido = data.estado;
        preguntaConocida = data.pregunta_actual_orden;
        respuestasConocidas = data.respuestas_recibidas;

        const yo = (data.leaderboard || []).find(p => p.id === PARTICIPANTE_ID);
        if (yo) document.getElementById('jb-mi-puntaje').textContent = yo.puntaje_total;

        if (data.estado === 'esperando') {
            mostrarZona('espera');
        }

        if (data.estado === 'pregunta' && data.pregunta) {
            if (yaRespondiPreguntaOrden !== data.pregunta.orden) {
                yaRespondiPreguntaOrden = -1; // nueva pregunta, aún no respondida
            }

            if (yaRespondiPreguntaOrden !== data.pregunta.orden) {
                mostrarZona('pregunta');
                document.getElementById('jb-num-preg').textContent = data.pregunta.orden;
                document.getElementById('jb-total-preg').textContent = data.total_preguntas;
                document.getElementById('jb-texto-preg').textContent = data.pregunta.texto;

                const opciones = [
                    ['a', data.pregunta.opcion_a], ['b', data.pregunta.opcion_b],
                    ['c', data.pregunta.opcion_c], ['d', data.pregunta.opcion_d],
                ].filter(([, txt]) => txt);

                document.getElementById('jb-opciones').innerHTML = opciones.map(([letra, txt]) => `
                    <button class="jb-opcion-btn jb-opcion-${letra}" onclick="marcarRespondida(${data.pregunta.orden}); enviarRespuesta('${letra}', this)">
                        ${letra.toUpperCase()}) ${escHtml(txt)}
                    </button>
                `).join('');

                iniciarTimerVisual(data.pregunta.segundos_restantes, data.pregunta.tiempo_limite_seg);
            }
        }

        if (data.estado === 'resultados' && data.resultados) {
            clearInterval(cuentaRegresivaInterval);
            // Si el jugador no alcanzó a responder, se lo indicamos aquí.
            if (document.getElementById('jb-zona-resultado').style.display === 'none') {
                mostrarZona('resultado');
                document.getElementById('jb-resultado-icono').textContent = '⏱️';
                document.getElementById('jb-resultado-txt').textContent = '¡Tiempo terminado!';
                document.getElementById('jb-resultado-puntos').textContent = 'No alcanzaste a responder esta pregunta.';
            }
            const segEl = document.getElementById('jb-segundos-siguiente');
            if (segEl) segEl.textContent = data.segundos_para_siguiente ?? 0;
            pintarLeaderboardMini('jb-lb-mini', data.leaderboard);
        }

        if (data.estado === 'finalizado') {
            mostrarZona('final');
            pintarLeaderboardMini('jb-lb-final', data.leaderboard);
            clearTimeout(pollTimer);
            return;
        }

    } catch (e) {
        pollTimer = setTimeout(() => consultarEstado(), 2000);
        return;
    }
    pollTimer = setTimeout(() => consultarEstado(), 1000);
}

function marcarRespondida(orden) { yaRespondiPreguntaOrden = orden; }

consultarEstado(true);
</script>
</body>
</html>