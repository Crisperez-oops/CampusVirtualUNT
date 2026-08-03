<?php
/**
 * battles.php
 * Portada de Classroom Battles: a la izquierda, crear o unirse a una sala;
 * a la derecha, la explicación de cómo funciona el juego (estilo Kahoot).
 *
 * La caja "Unirme" llama de verdad a api/batalla_unirse.php (ver batallas.js).
 * La caja "Crear batalla" enlaza a crear_batalla.php, que ya arma la sala.
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';

requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$usuario   = Usuario::obtenerPorId($usuarioId);
$apodoBase = $usuario['nombre'] ?? 'Jugador';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Classroom Battles · CampusVirtual UNITRU</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;1,500&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/battles.css?v=2">
</head>
<body>

<?php require __DIR__ . '/vistas/topbar.php'; ?>

<main class="batallas-layout">

    <!-- Columna izquierda: acción -->
    <section class="batallas-col-izq" aria-label="Crear o unirte a una batalla">
        <p class="batallas-kicker">⚡ Classroom Battles</p>
        <h1 class="batallas-titulo">Reta a tu clase<br>en tiempo real</h1>
        <p class="batallas-sub">Crea una sala para tus compañeros o únete con un código.</p>

        <div class="batallas-cajas">
            <a href="crear_batalla.php" class="batalla-caja batalla-caja-crear">
                <span class="batalla-caja-icono">🛠️</span>
                <span class="batalla-caja-cuerpo">
                    <span class="batalla-caja-titulo">Crear batalla</span>
                    <span class="batalla-caja-desc">Arma una sala y comparte el código con tu clase</span>
                </span>
                <span class="batalla-caja-flecha" aria-hidden="true">→</span>
            </a>

            <form class="batalla-caja batalla-caja-unirse" id="formUnirse" autocomplete="off">
                <span class="batalla-caja-icono">🎮</span>
                <span class="batalla-caja-cuerpo">
                    <span class="batalla-caja-titulo">Unirme a una batalla</span>
                    <span class="batalla-caja-desc">Ingresa el código de 6 dígitos que te compartió tu profesor o compañero</span>
                </span>

                <span class="batalla-campo">
                    <label for="codigoBatalla">Código de la sala</label>
                    <input type="text" id="codigoBatalla" name="codigo" inputmode="numeric" maxlength="6" pattern="\d{6}" placeholder="000000" required>
                </span>

                <span class="batalla-campo">
                    <label for="apodoBatalla">Tu apodo (opcional)</label>
                    <input type="text" id="apodoBatalla" name="apodo" maxlength="24" placeholder="<?= htmlspecialchars($apodoBase) ?>">
                </span>

                <button type="submit" class="batalla-boton" id="btnUnirse">
                    <span id="btnUnirseTexto">Unirme</span>
                </button>
                <p class="batalla-error oculto" id="errorUnirse" role="alert"></p>
            </form>
        </div>
    </section>

    <!-- Columna derecha: qué es esto -->
    <section class="batallas-col-der" aria-label="Cómo funciona Classroom Battles">
        <div class="batallas-panel">
            <p class="batallas-panel-kicker">¿Qué es esto?</p>
            <h2 class="batallas-panel-titulo">Trivia en vivo. Una competencia para tu campus</h2>
            <p class="batallas-panel-texto">
                Un profesor o un compañero crea una sala y le pone preguntas de su curso.
                El resto se une desde su celular o laptop con un código de 6 dígitos, y
                todos responden al mismo tiempo mientras las preguntas se proyectan en
                pantalla. Cada respuesta correcta suma puntos — entre más rápido
                respondas, más vale.
            </p>

            <ul class="batallas-features">
                <li>
                    <span class="batallas-features-icono">📱</span>
                    <div>
                        <p class="batallas-features-titulo">Cada quien juega desde su dispositivo</p>
                        <p class="batallas-features-desc">No necesitas instalar nada, solo el código de la sala.</p>
                    </div>
                </li>
                <li>
                    <span class="batallas-features-icono">⏱️</span>
                    <div>
                        <p class="batallas-features-titulo">Preguntas cronometradas</p>
                        <p class="batallas-features-desc">Cada ronda tiene un tiempo límite.</p>
                    </div>
                </li>
                <li>
                    <span class="batallas-features-icono">🏆</span>
                    <div>
                        <p class="batallas-features-titulo">Ranking en vivo</p>
                        <p class="batallas-features-desc">La tabla de posiciones se actualiza después de cada pregunta.</p>
                    </div>
                </li>
                <li>
                    <span class="batallas-features-icono">🎉</span>
                    <div>
                        <p class="batallas-features-titulo">Podio final</p>
                        <p class="batallas-features-desc">Al terminar, se corona a los tres primeros puestos de la sala.</p>
                    </div>
                </li>
            </ul>

            <div class="batallas-pasos">
                <div class="batallas-paso">
                    <span class="batallas-paso-num">1</span>
                    <p>Crea una sala o únete con un código</p>
                </div>
                <div class="batallas-paso">
                    <span class="batallas-paso-num">2</span>
                    <p>Responde cada pregunta antes de que se acabe el tiempo</p>
                </div>
                <div class="batallas-paso">
                    <span class="batallas-paso-num">3</span>
                    <p>Mira el ranking actualizarse y pelea por el podio</p>
                </div>
            </div>
        </div>
    </section>

</main>

<script src="assets/js/battles.js"></script>
</body>
</html>