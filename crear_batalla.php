<?php
/**
 * crear_batalla.php
 * El docente/host arma el quiz: título + preguntas dinámicas, y crea la sala.
 * Al guardar, redirige a host_batalla.php con el código generado.
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/clases/SalaBatalla.php';

requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo'] ?? '');
    $preguntas = $_POST['preguntas'] ?? [];

    $preguntasValidas = [];
    foreach ($preguntas as $p) {
        $texto = trim($p['texto'] ?? '');
        $a = trim($p['opcion_a'] ?? '');
        $b = trim($p['opcion_b'] ?? '');
        $c = trim($p['opcion_c'] ?? '');
        $d = trim($p['opcion_d'] ?? '');
        $correcta = strtolower(trim($p['correcta'] ?? ''));
        if ($texto === '' || $a === '' || $b === '' || !in_array($correcta, ['a','b','c','d'], true)) {
            continue;
        }
        if (($correcta === 'c' && $c === '') || ($correcta === 'd' && $d === '')) {
            continue;
        }
        $preguntasValidas[] = [
            'texto'             => $texto,
            'opcion_a'          => $a,
            'opcion_b'          => $b,
            'opcion_c'          => $c,
            'opcion_d'          => $d,
            'correcta'          => $correcta,
            'tiempo_limite_seg' => max(5, (int) ($p['tiempo_limite_seg'] ?? 20)),
            'puntos_base'       => max(100, (int) ($p['puntos_base'] ?? 1000)),
        ];
    }

    if (empty($preguntasValidas)) {
        $error = 'Agrega al menos una pregunta válida (texto, opción A, opción B y marca la correcta).';
    } else {
        $sala = SalaBatalla::crear($usuarioId, $titulo);
        SalaBatalla::agregarPreguntas($sala['id'], $preguntasValidas);
        header('Location: host_batalla.php?codigo=' . $sala['codigo']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear Classroom Battle</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/battles.css">
</head>
<body><?php require __DIR__ . '/vistas/topbar.php'; ?>
<style>
.cb-wrap{max-width:800px;margin:0 auto;padding:24px 16px 80px;animation:cbFadeIn .5s ease}
@keyframes cbFadeIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.cb-header{text-align:center;margin-bottom:28px}
.cb-titulo-pagina{font-family:'Fraunces',serif;font-size:26px;font-weight:700;margin-bottom:6px;background:linear-gradient(135deg,var(--acento),#8B5CF6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.cb-sub{color:var(--texto-tenue);font-size:13px;margin-bottom:0}
.cb-panel{background:var(--bg-panel);border:1px solid var(--linea);border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.04);transition:box-shadow .3s,border-color .3s}
.cb-panel:hover{border-color:var(--acento);box-shadow:0 4px 20px rgba(108,140,255,.08)}
.cb-campo{margin-bottom:14px}
.cb-campo label{display:block;font-size:11.5px;font-weight:600;color:var(--texto-tenue);margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em}
.cb-campo input,.cb-campo select{width:100%;background:var(--bg-panel-alt);border:1px solid var(--linea);color:var(--texto-principal);padding:10px 12px;border-radius:8px;font-family:var(--fuente-cuerpo);font-size:13.5px;outline:none;box-sizing:border-box;transition:border-color .2s,box-shadow .2s}
.cb-campo input:focus,.cb-campo select:focus{border-color:var(--acento);box-shadow:0 0 0 3px rgba(108,140,255,.1)}
.cb-pregunta-card{background:var(--bg-panel-alt);border:1px solid var(--linea);border-radius:14px;padding:20px;margin-bottom:14px;position:relative;transition:all .3s ease;animation:cbCardIn .4s cubic-bezier(.25,.8,.25,1) both}
@keyframes cbCardIn{from{opacity:0;transform:translateY(10px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.cb-pregunta-card:hover{border-color:var(--acento);box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-1px)}
.cb-pregunta-num{font-family:'Fraunces',serif;font-weight:700;font-size:13px;color:var(--acento);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.cb-pregunta-num::before{content:'';display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--acento);animation:cbPulse 2s ease-in-out infinite}
@keyframes cbPulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(1.5)}}
.cb-fila2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cb-fila4{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cb-borrar-pregunta{position:absolute;top:14px;right:14px;width:28px;height:28px;border-radius:50%;background:transparent;border:1px solid transparent;color:var(--texto-tenue);cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;transition:all .2s}
.cb-borrar-pregunta:hover{background:rgba(248,113,113,.1);border-color:rgba(248,113,113,.3);color:#ef4444}
.cb-btn{padding:10px 20px;border-radius:10px;border:1px solid var(--linea);background:transparent;color:var(--texto-principal);cursor:pointer;font-family:var(--fuente-cuerpo);font-size:13.5px;font-weight:600;transition:all .2s;display:inline-flex;align-items:center;gap:6px}
.cb-btn:hover{border-color:var(--acento);color:var(--acento);transform:translateY(-1px)}
.cb-btn-acento{background:var(--acento);color:#fff;border-color:var(--acento);font-weight:700;box-shadow:0 2px 8px rgba(108,140,255,.3)}
.cb-btn-acento:hover{opacity:.9;color:#fff;box-shadow:0 4px 16px rgba(108,140,255,.4);transform:translateY(-2px)}
.cb-acciones{display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:10px}
.cb-error{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.3);color:#ef4444;padding:12px 18px;border-radius:12px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;animation:cbShake .4s ease}
@keyframes cbShake{0%,100%{transform:translateX(0)}25%{transform:translateX(-6px)}75%{transform:translateX(6px)}}
.cb-tag{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;background:rgba(108,140,255,.1);color:var(--acento)}
.cb-counter{font-size:12px;color:var(--texto-tenue);text-align:right;margin-top:4px}
@media(max-width:768px){
    .cb-wrap{padding:12px 10px 80px}
    .cb-titulo-pagina{font-size:20px}
    .cb-panel{padding:16px;border-radius:12px}
    .cb-pregunta-card{padding:14px;animation:none!important;opacity:1!important}
    .cb-pregunta-card:hover{transform:none!important}
    .cb-fila4{grid-template-columns:1fr}
    .cb-fila2{grid-template-columns:1fr}
    .cb-btn-acento:hover{transform:none!important}
    .cb-btn:hover{transform:none!important}
    @keyframes cbCardIn{from{opacity:1;transform:none}to{opacity:1;transform:none}}
}
</style>
</head>
<body>
<div class="cb-wrap">
    <div class="cb-header">
        <div class="cb-titulo-pagina">🎮 Crear Classroom Battle</div>
        <div class="cb-sub">Arma tu quiz. Al guardar se generará un código de 6 dígitos para que tu clase se una.</div>
    </div>

    <?php if ($error): ?><div class="cb-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST" id="form-crear-batalla">
        <div class="cb-panel">
            <div class="cb-campo">
                <label>📝 Título de la batalla</label>
                <input type="text" name="titulo" placeholder="ej: Repaso Final — Cálculo II" required style="font-size:15px;font-family:'Fraunces',serif;font-weight:600">
            </div>
        </div>

        <div id="preguntas-cont"></div>

        <div class="cb-acciones">
            <button type="button" class="cb-btn" onclick="agregarPregunta()">+ Agregar pregunta</button>
            <button type="submit" class="cb-btn cb-btn-acento">Crear batalla 🚀</button>
        </div>
    </form>
</div>

<template id="tpl-pregunta">
    <div class="cb-pregunta-card">
        <button type="button" class="cb-borrar-pregunta" onclick="this.closest('.cb-pregunta-card').remove()" title="Eliminar pregunta">✕</button>
        <div class="cb-pregunta-num">Pregunta __N__</div>
        <div class="cb-campo">
            <label>❓ Enunciado de la pregunta</label>
            <input type="text" name="preguntas[__I__][texto]" placeholder="¿Qué principio permite que una clase herede atributos y métodos de otra?" required>
        </div>
        <div class="cb-fila4" style="margin-bottom:12px;">
            <div class="cb-campo" style="margin-bottom:0;">
                <label><span class="cb-tag" style="background:rgba(239,68,68,.1);color:#ef4444">A</span> Opción A</label>
                <input type="text" name="preguntas[__I__][opcion_a]" placeholder="Respuesta A" required>
            </div>
            <div class="cb-campo" style="margin-bottom:0;">
                <label><span class="cb-tag" style="background:rgba(59,130,246,.1);color:#3B82F6">B</span> Opción B</label>
                <input type="text" name="preguntas[__I__][opcion_b]" placeholder="Respuesta B" required>
            </div>
            <div class="cb-campo" style="margin-bottom:0;">
                <label><span class="cb-tag" style="background:rgba(245,158,11,.1);color:#F59E0B">C</span> Opción C <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--texto-tenue)">(opcional)</span></label>
                <input type="text" name="preguntas[__I__][opcion_c]" placeholder="Respuesta C">
            </div>
            <div class="cb-campo" style="margin-bottom:0;">
                <label><span class="cb-tag" style="background:rgba(34,197,94,.1);color:#22C55E">D</span> Opción D <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--texto-tenue)">(opcional)</span></label>
                <input type="text" name="preguntas[__I__][opcion_d]" placeholder="Respuesta D">
            </div>
        </div>
        <div class="cb-fila2">
            <div class="cb-campo" style="margin-bottom:0;">
                <label>✅ Respuesta correcta</label>
                <select name="preguntas[__I__][correcta]" required>
                    <option value="a">A</option>
                    <option value="b">B</option>
                    <option value="c">C</option>
                    <option value="d">D</option>
                </select>
            </div>
            <div class="cb-campo" style="margin-bottom:0;">
                <label>⏱️ Tiempo límite</label>
                <input type="number" name="preguntas[__I__][tiempo_limite_seg]" value="20" min="5" max="120" placeholder="Segundos">
                <div class="cb-counter">5 - 120 segundos</div>
            </div>
        </div>
    </div>
</template>

<script>
let contadorPreguntas = 0;

function agregarPregunta() {
    contadorPreguntas++;
    const tpl = document.getElementById('tpl-pregunta').innerHTML
        .replaceAll('__N__', contadorPreguntas)
        .replaceAll('__I__', contadorPreguntas - 1);
    const div = document.createElement('div');
    div.innerHTML = tpl;
    document.getElementById('preguntas-cont').appendChild(div.firstElementChild);
}

// Arranca con 3 preguntas vacías para agilizar
agregarPregunta();
agregarPregunta();
agregarPregunta();
</script>
</body>
</html>
