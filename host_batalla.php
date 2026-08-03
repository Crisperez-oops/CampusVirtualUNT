<?php
/**
 * host_batalla.php
 * Pantalla del organizador: muestra el código, la lista de participantes
 * en vivo, controla el avance de preguntas y muestra el leaderboard.
 * Se sincroniza con la sala mediante long-polling a api/batalla_estado.php.
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/SalaBatalla.php';

requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$codigo    = preg_replace('/\D/', '', $_GET['codigo'] ?? '');
$sala      = $codigo ? SalaBatalla::obtenerPorCodigo($codigo) : null;

if (!$sala || (int) $sala['host_usuario_id'] !== $usuarioId) {
    http_response_code(404);
    die('Sala no encontrada o no tienes permiso para administrarla.');
}

$totalPreguntas = SalaBatalla::contarPreguntas((int) $sala['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Host · <?= htmlspecialchars($sala['titulo']) ?></title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
.hb-wrap { max-width: 880px; margin: 0 auto; padding: 28px 16px 80px; }
.hb-cab { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 22px; flex-wrap: wrap; gap: 14px; }
.hb-titulo { font-family: var(--fuente-display); font-size: 20px; font-weight: 700; }
.hb-codigo-box { background: var(--bg-panel); border: 1px solid var(--linea); border-radius: 12px; padding: 14px 22px; text-align:center; }
.hb-codigo-label { font-size: 10.5px; color: var(--texto-tenue); text-transform: uppercase; letter-spacing: .1em; margin-bottom: 4px; }
.hb-codigo { font-family: var(--fuente-display); font-size: 30px; font-weight: 700; color: var(--acento); letter-spacing: 0.2em; }

.hb-panel { background: var(--bg-panel); border: 1px solid var(--linea); border-radius: 14px; padding: 22px; margin-bottom: 18px; }
.hb-panel-titulo { font-family: var(--fuente-display); font-weight: 700; font-size: 14px; margin-bottom: 14px; display:flex; justify-content:space-between; align-items:center; }

.hb-estado-badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; background: var(--acento-suave); color: var(--acento); font-weight:600; text-transform: uppercase; letter-spacing: .05em; }

.hb-participantes-grid { display:flex; flex-wrap:wrap; gap: 8px; }
.hb-chip { display:flex; align-items:center; gap:6px; background: var(--bg-panel-alt); border:1px solid var(--linea); border-radius: 20px; padding: 5px 12px 5px 5px; font-size: 12.5px; }
.hb-chip-dot { width: 20px; height:20px; border-radius:50%; flex-shrink:0; }

.hb-progreso-preg { font-size: 12.5px; color: var(--texto-tenue); margin-bottom: 10px; }
.hb-pregunta-texto { font-family: var(--fuente-display); font-size: 16px; font-weight: 700; margin-bottom: 14px; }
.hb-timer { font-family: var(--fuente-display); font-size: 34px; font-weight: 700; color: var(--acento); text-align:center; margin: 10px 0; }
.hb-respuestas-count { text-align:center; font-size: 12.5px; color: var(--texto-tenue); margin-bottom: 14px; }

.hb-opciones-mini { display:grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
.hb-opcion-mini { background: var(--bg-panel-alt); border:1px solid var(--linea); border-radius: 8px; padding: 10px 12px; font-size: 12.5px; }
.hb-opcion-mini b { color: var(--acento); }

.hb-btn { padding: 11px 20px; border-radius: 9px; border: none; background: var(--acento); color:#fff; font-weight:700; font-family: var(--fuente-display); cursor:pointer; font-size: 13.5px; width: 100%; transition:all .2s; }
.hb-btn:hover { opacity:.9; transform:translateY(-1px); }
.hb-btn:disabled { opacity: .4; cursor: not-allowed; transform:none; }
.hb-btn-sec { background:transparent; border:1px solid var(--linea); color:var(--texto-principal); width:auto; }
.hb-btn-sec:hover { border-color:var(--acento); color:var(--acento); }
.hb-btn-back { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:1px solid var(--linea);background:transparent;color:var(--texto-tenue);cursor:pointer;font-size:13px;text-decoration:none;transition:all .2s }
.hb-btn-back:hover { border-color:var(--acento);color:var(--acento) }
.hb-add-questions{display:none;margin-top:12px;padding:16px;background:var(--bg-panel-alt);border-radius:10px;border:1px solid var(--linea)}
.hb-add-questions.open{display:block}
.hb-q-list{display:flex;flex-direction:column;gap:8px;margin-top:10px}
.hb-q-item{display:flex;align-items:center;gap:8px;background:var(--bg-panel);padding:8px 12px;border-radius:8px;border:1px solid var(--linea);font-size:12.5px}
.hb-q-item input{flex:1;background:transparent;border:none;color:var(--texto-principal);font-size:12.5px;outline:none;font-family:inherit}
.hb-q-item select{padding:4px 8px;border-radius:6px;border:1px solid var(--linea);background:var(--bg-panel-alt);color:var(--texto-principal);font-size:12px}
.hb-q-item button{background:none;border:none;color:var(--texto-tenue);cursor:pointer;font-size:14px}
.hb-q-item button:hover{color:#ef4444}

.hb-leaderboard { display:flex; flex-direction:column; gap: 8px; }
.hb-lb-fila { display:flex; align-items:center; gap: 10px; background: var(--bg-panel-alt); border:1px solid var(--linea); border-radius: 8px; padding: 9px 12px; }
.hb-lb-pos { font-family: var(--fuente-display); font-weight: 700; width: 22px; color: var(--texto-tenue); font-size: 13px; }
.hb-lb-pos.oro { color: #F59E0B; }
.hb-lb-dot { width: 22px; height:22px; border-radius:50%; flex-shrink:0; }
.hb-lb-nombre { flex:1; font-size: 13px; }
.hb-lb-puntos { font-family: var(--fuente-display); font-weight:700; font-size: 13.5px; color: var(--acento); }
</style>
</head>
<body>
<?php require __DIR__ . '/vistas/topbar.php'; ?>
<div class="hb-wrap">
    <div style="margin-bottom:14px">
        <a href="battles.php" class="hb-btn-back">← Volver al inicio</a>
    </div>
    <div class="hb-cab">
        <div>
            <div class="hb-titulo"><?= htmlspecialchars($sala['titulo']) ?></div>
            <div style="font-size:12.5px; color:var(--texto-tenue); margin-top:2px;" id="hb-info-preguntas"><?= $totalPreguntas ?> preguntas · Panel del organizador</div>
        </div>
        <div class="hb-codigo-box">
            <div class="hb-codigo-label">Código de sala</div>
            <div class="hb-codigo" id="hb-codigo"><?= htmlspecialchars($sala['codigo']) ?></div>
        </div>
    </div>

    <div class="hb-panel">
        <div class="hb-panel-titulo">
            <span>Estado de la batalla</span>
            <span class="hb-estado-badge" id="hb-estado-badge">esperando</span>
        </div>

        <div id="hb-zona-espera">
            <p style="font-size:13px; color:var(--texto-tenue); margin-bottom:16px;">
                Comparte el código con tus compañeros. Cuando estén listos, inicia la primera pregunta.
            </p>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button class="hb-btn" onclick="controlar('siguiente_pregunta')" style="flex:1">Iniciar batalla ▶</button>
                <button class="hb-btn hb-btn-sec" onclick="toggleAddQuestions()" style="flex:1">+ Agregar preguntas</button>
            </div>
            <div class="hb-add-questions" id="hb-add-questions">
                <div style="font-weight:700;font-size:13px;margin-bottom:8px">Agregar más preguntas</div>
                <div class="hb-q-list" id="hb-q-list"></div>
                <button class="hb-btn hb-btn-sec" onclick="agregarCampoPregunta()" style="margin-top:10px;width:auto;font-size:12px">+ Nueva pregunta</button>
                <button class="hb-btn" onclick="guardarPreguntas()" style="margin-top:10px">Guardar preguntas 💾</button>
                <div id="hb-q-msg" style="font-size:11px;margin-top:6px;color:var(--ok)"></div>
            </div>
        </div>

        <div id="hb-zona-pregunta" style="display:none;">
            <div class="hb-progreso-preg">Pregunta <span id="hb-num-preg">1</span> de <span id="hb-total-preg"><?= $totalPreguntas ?></span></div>
            <div class="hb-pregunta-texto" id="hb-texto-preg"></div>
            <div class="hb-timer" id="hb-timer">--</div>
            <div class="hb-respuestas-count"><span id="hb-conteo-resp">0</span> / <span id="hb-conteo-total">0</span> respondieron</div>
            <button class="hb-btn" onclick="controlar('cerrar_pregunta')">Cerrar pregunta y ver resultados</button>
        </div>

        <div id="hb-zona-resultados" style="display:none;">
            <div class="hb-opciones-mini" id="hb-opciones-resultado"></div>
            <p style="text-align:center; font-size:12px; color:var(--texto-tenue); margin-bottom:10px;">
                Pasando a la siguiente en <span id="hb-segundos-siguiente">5</span>s…
            </p>
            <button class="hb-btn" id="hb-btn-siguiente" onclick="controlar('siguiente_pregunta')">Saltar ahora ▶</button>
        </div>

        <div id="hb-zona-final" style="display:none;">
            <p style="font-size:14px; text-align:center; padding: 10px 0;">🏆 ¡Batalla finalizada! Estos son los resultados finales.</p>
            <a href="battles.php" class="hb-btn" style="display:block; text-align:center; text-decoration:none; box-sizing:border-box;">Volver al hub</a>
        </div>
    </div>

    <div class="hb-panel">
        <div class="hb-panel-titulo">Participantes (<span id="hb-total-participantes">0</span>)</div>
        <div class="hb-participantes-grid" id="hb-participantes"></div>
    </div>

    <div class="hb-panel">
        <div class="hb-panel-titulo">Tabla de líderes</div>
        <div class="hb-leaderboard" id="hb-leaderboard"></div>
    </div>
</div>

<script>
const CODIGO_SALA = <?= json_encode($sala['codigo']) ?>;
const SALA_ID = <?= (int) $sala['id'] ?>;
let estadoConocido = '', preguntaConocida = -1, respuestasConocidas = -1;
let pollActivo = true, pollTimer = null;
let preguntaCount = 0;

async function controlar(accion) {
    const fd = new FormData();
    fd.append('sala_id', <?= (int) $sala['id'] ?>);
    fd.append('accion', accion);
    await fetch('api/batalla_control.php', { method: 'POST', body: fd });
    consultarEstado(true);
}

function toggleAddQuestions(){
    document.getElementById('hb-add-questions').classList.toggle('open');
}

function agregarCampoPregunta(texto='', a='', b='', c='', d='', correcta='a', tiempo=20){
    preguntaCount++;
    var el=document.getElementById('hb-q-list');
    var div=document.createElement('div');
    div.className='hb-q-item';
    div.innerHTML='<span style="font-weight:700;color:var(--acento);min-width:20px">#'+preguntaCount+'</span>'+
        '<input type="text" class="q-texto" placeholder="Enunciado" value="'+escHtml(texto)+'">'+
        '<input type="text" class="q-a" placeholder="A" value="'+escHtml(a)+'" style="max-width:80px">'+
        '<input type="text" class="q-b" placeholder="B" value="'+escHtml(b)+'" style="max-width:80px">'+
        '<input type="text" class="q-c" placeholder="C" value="'+escHtml(c)+'" style="max-width:80px">'+
        '<input type="text" class="q-d" placeholder="D" value="'+escHtml(d)+'" style="max-width:80px">'+
        '<select class="q-correcta"><option value="a"'+('a'===correcta?' selected':'')+'>A</option><option value="b"'+('b'===correcta?' selected':'')+'>B</option><option value="c"'+('c'===correcta?' selected':'')+'>C</option><option value="d"'+('d'===correcta?' selected':'')+'>D</option></select>'+
        '<input type="number" class="q-tiempo" value="'+tiempo+'" min="5" max="120" style="max-width:50px" title="Segundos">'+
        '<button onclick="this.closest(\'.hb-q-item\').remove()" title="Eliminar">✕</button>';
    el.appendChild(div);
}

async function guardarPreguntas(){
    var items=document.querySelectorAll('#hb-q-list .hb-q-item');
    var preguntas=[];
    items.forEach(function(item){
        var t=item.querySelector('.q-texto').value.trim();
        var a=item.querySelector('.q-a').value.trim();
        var b=item.querySelector('.q-b').value.trim();
        var c=item.querySelector('.q-c').value.trim();
        var d=item.querySelector('.q-d').value.trim();
        var correcta=item.querySelector('.q-correcta').value;
        var tiempo=parseInt(item.querySelector('.q-tiempo').value)||20;
        if(!t||!a||!b)return;
        if((correcta==='c'&&!c)||(correcta==='d'&&!d))return;
        preguntas.push({texto:t,opcion_a:a,opcion_b:b,opcion_c:c,opcion_d:d,correcta:correcta,tiempo_limite_seg:Math.max(5,tiempo),puntos_base:1000});
    });
    if(!preguntas.length){document.getElementById('hb-q-msg').textContent='Agrega al menos una pregunta válida';return}
    var fd=new FormData();
    fd.append('sala_id',SALA_ID);
    fd.append('accion','agregar_preguntas');
    fd.append('preguntas',JSON.stringify(preguntas));
    var r=await fetch('api/batalla_control.php',{method:'POST',body:fd});
    var d=await r.json();
    document.getElementById('hb-q-msg').textContent=d.ok?'¡'+d.agregadas+' preguntas agregadas!':'Error: '+(d.error||'');
    if(d.ok){document.getElementById('hb-q-list').innerHTML='';preguntaCount=0;consultarEstado(true)}
}

function pintarParticipantes(lista) {
    document.getElementById('hb-total-participantes').textContent = lista.length;
    document.getElementById('hb-participantes').innerHTML = lista.length
        ? lista.map(p => `
            <div class="hb-chip">
                <div class="hb-chip-dot" style="background:${p.avatar_color}"></div>
                ${escHtml(p.apodo)}
            </div>`).join('')
        : '<p style="color:var(--texto-tenue); font-size:12.5px;">Nadie se ha unido todavía.</p>';
}

function pintarLeaderboard(lb) {
    const cont = document.getElementById('hb-leaderboard');
    if (!lb.length) {
        cont.innerHTML = '<p style="color:var(--texto-tenue); font-size:12.5px;">Aún no hay puntajes.</p>';
        return;
    }
    cont.innerHTML = lb.map((p, i) => `
        <div class="hb-lb-fila">
            <div class="hb-lb-pos ${i === 0 ? 'oro' : ''}">#${i + 1}</div>
            <div class="hb-lb-dot" style="background:${p.avatar_color}"></div>
            <div class="hb-lb-nombre">${escHtml(p.apodo)}</div>
            <div class="hb-lb-puntos">${p.puntaje_total} pts</div>
        </div>
    `).join('');
}

function escHtml(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function mostrarZona(zona) {
    ['espera','pregunta','resultados','final'].forEach(z => {
        document.getElementById('hb-zona-' + z).style.display = (z === zona) ? 'block' : 'none';
    });
}

async function consultarEstado(inmediato = false) {
    if (!pollActivo) return;
    try {
        const params = new URLSearchParams({
            codigo: CODIGO_SALA,
            estado_conocido: inmediato ? '' : estadoConocido,
            pregunta_conocida: inmediato ? -1 : preguntaConocida,
            respuestas_conocidas: inmediato ? -1 : respuestasConocidas,
        });
        const res = await fetch('api/batalla_estado.php?' + params.toString());
        const data = await res.json();

        if (!data.existe) { setTimeout(consultarEstado, 2000); return; }

        estadoConocido = data.estado;
        preguntaConocida = data.pregunta_actual_orden;
        respuestasConocidas = data.respuestas_recibidas;

        document.getElementById('hb-estado-badge').textContent = data.estado;
        pintarParticipantes(data.participantes || []);
        pintarLeaderboard(data.leaderboard);

        if (data.estado === 'esperando') mostrarZona('espera');

        if (data.estado === 'pregunta' && data.pregunta) {
            mostrarZona('pregunta');
            document.getElementById('hb-num-preg').textContent = data.pregunta.orden;
            document.getElementById('hb-total-preg').textContent = data.total_preguntas;
            document.getElementById('hb-texto-preg').textContent = data.pregunta.texto;
            document.getElementById('hb-timer').textContent = data.pregunta.segundos_restantes + 's';
            document.getElementById('hb-conteo-resp').textContent = data.respuestas_recibidas;
            document.getElementById('hb-conteo-total').textContent = data.total_participantes;
        }

        if (data.estado === 'resultados' && data.resultados) {
            mostrarZona('resultados');
            const r = data.resultados;
            const letras = ['a','b','c','d'];
            const nombres = { a: r.pregunta.opcion_a, b: r.pregunta.opcion_b, c: r.pregunta.opcion_c, d: r.pregunta.opcion_d };
            document.getElementById('hb-opciones-resultado').innerHTML = letras
                .filter(l => nombres[l])
                .map(l => `
                    <div class="hb-opcion-mini">
                        ${l.toUpperCase() === r.correcta.toUpperCase() ? '✅ ' : ''}${escHtml(nombres[l])}
                        <br><b>${r.conteo[l] || 0}</b> respuestas
                    </div>`).join('');
            document.getElementById('hb-segundos-siguiente').textContent = data.segundos_para_siguiente ?? 0;
            document.getElementById('hb-btn-siguiente').textContent =
                data.pregunta_actual_orden >= data.total_preguntas ? 'Ver resultados finales ahora 🏁' : 'Saltar ahora ▶';
        }

        if (data.estado === 'finalizado') {
            mostrarZona('final');
            pollActivo = false;
            clearTimeout(pollTimer);
            return;
        }
    } catch (e) { }
    if(pollActivo) pollTimer = setTimeout(function(){ consultarEstado() }, 1000);
}
consultarEstado(true);
</script>
</body>
</html>