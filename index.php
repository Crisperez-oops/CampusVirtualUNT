<?php
/**
 * index.php — Sala de Estar Virtual (pantalla de bienvenida)
 * -----------------------------------------------------------
 * Punto de entrada principal. Si el usuario no tiene sesión activa,
 * lo mandamos a login.php. Si ya inició sesión, le mostramos esta
 * "sala" de bienvenida con acceso rápido a cada módulo del campus.
 *
 * Usa los mismos nombres de sesión que vistas/topbar.php:
 *   $_SESSION['usuario_id'], $_SESSION['usuario_nombre'],
 *   $_SESSION['facultad'] (opcional). La foto de perfil se obtiene
 *   con Perfil::obtenerPorUsuario(), igual que en el topbar.
 *
 * NUEVO en esta versión:
 *   - Franja "Resumen del día" (tareas / mensajes / próximo evento).
 *   - Buscador que filtra las puertas del directorio en vivo.
 *   - Paleta de comandos (Ctrl/Cmd + K) para saltar a cualquier módulo.
 *   - Insignias de pendientes sobre las puertas correspondientes.
 *   - Mini-lista de "compañeros conectados ahora".
 *   - Ambiente del patio según la hora (sol / garúa / estrellas).
 *
 *   Los datos de tareas, mensajes, eventos y compañeros conectados
 *   se piden de forma defensiva (class_exists / method_exists) para
 *   que la página no truene si esas clases todavía no existen en tu
 *   proyecto. Busca los bloques marcados "TODO backend" para conectar
 *   tu lógica real cuando la tengas lista.
 */

require_once __DIR__ . '/config/constantes.php';
iniciarSesionSegura();

if (empty($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/clases/Perfil.php';
require_once __DIR__ . '/clases/Chat.php';
require_once __DIR__ . '/clases/Tarea.php';
require_once __DIR__ . '/clases/Evento.php';
require_once __DIR__ . '/clases/Anuncio.php';

$usuarioId = (int) $_SESSION['usuario_id'];

$nombreUsuario   = $_SESSION['usuario_nombre'] ?? 'Estudiante';
$facultadUsuario = $_SESSION['facultad_nombre'] ?? null;

$perfilActual = Perfil::obtenerPorUsuario($usuarioId);
$fotoPerfil   = !empty($perfilActual['foto'])
    ? ltrim($perfilActual['foto'], '/')
    : 'assets/fotos/default.png';

// --- Saludo con jerga peruana ---
$hora = (int) date('G');
if ($hora >= 5 && $hora < 12) {
    $momento = 'manana';
    $saludos = ['Buenos días, causa', 'Habla, qué tal', 'Arriba Perú', 'Qué fue causa', 'Tempranito nomás', 'Amaneciste pilas', 'Buen día, pe'];
} elseif ($hora >= 12 && $hora < 19) {
    $momento = 'tarde';
    $saludos = ['Buenas, pe', 'Habla, causa', 'Qué tal, oe', 'Todo bien, causa', 'Al toque nomás', 'Quiubo, causa', 'Dale nomás'];
} else {
    $momento = 'noche';
    $saludos = ['Buenas noches, pe', 'Habla, causa', 'Qué fue, oe', 'Tranqui nomás', 'Todo relax', 'Quiubo', 'Échale nomás'];
}
$saludo = $saludos[array_rand($saludos)];

// --- Fecha legible en español sin depender de extensiones de locale ---
$dias  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$fechaTexto = $dias[date('w')] . ', ' . date('j') . ' de ' . $meses[(int)date('n')];

/* -----------------------------------------------------------
 * Resumen del día — TODO backend
 * Cambia estas condiciones por tus clases reales cuando existan
 * (p. ej. Tarea::contarPendientes(), Evento::proximoPara()).
 * Mientras tanto, degradan a null y la franja lo muestra como "—"
 * en vez de romper la página. Chat::contarNoLeidos() ya existe de
 * verdad (ver clases/Chat.php), así que esa sí trae datos reales.
 * --------------------------------------------------------- */
$tareasPendientes = null;
$mensajesNuevos   = null;
$proximoEvento    = null; // esperado: ['titulo' => ..., 'cuando' => ...]

if (class_exists('Tarea') && method_exists('Tarea', 'contarPendientes')) {
    $tareasPendientes = Tarea::contarPendientes($usuarioId);
}
if (class_exists('Chat') && method_exists('Chat', 'contarNoLeidos')) {
    $mensajesNuevos = Chat::contarNoLeidos($usuarioId);
}
if (class_exists('Evento') && method_exists('Evento', 'proximoPara')) {
    $proximoEvento = Evento::proximoPara($usuarioId);
}

/* -----------------------------------------------------------
 * Compañeros conectados ahora — TODO backend
 * Esperado: arreglo de ['nombre' => ..., 'foto' => ...]
 * --------------------------------------------------------- */
$companerosConectados = [];
if (class_exists('Usuario') && method_exists('Usuario', 'listarConectadosAhora')) {
    $companerosConectados = Usuario::listarConectadosAhora($usuarioId, 6);
}

/* -----------------------------------------------------------
 * Racha de conexión
 * Si tienes una clase real (p. ej. Usuario::registrarVisitaYObtenerRacha()
 * guardando esto en tu BD por usuarioId), este bloque la usa y ya. Mientras
 * tanto cae en una cookie por navegador: es una aproximación razonable,
 * pero no es exacta entre dispositivos — para eso, conecta el TODO backend.
 * --------------------------------------------------------- */
$racha = 1;
if (class_exists('Usuario') && method_exists('Usuario', 'registrarVisitaYObtenerRacha')) {
    $racha = (int) Usuario::registrarVisitaYObtenerRacha($usuarioId);
} else {
    // TODO backend: reemplaza este bloque por Usuario::registrarVisitaYObtenerRacha($usuarioId)
    $hoy = date('Y-m-d');
    $rachaPrevia = isset($_COOKIE['sv_racha_dias']) ? (int) $_COOKIE['sv_racha_dias'] : 0;
    $ultimaVisita = $_COOKIE['sv_ultima_visita'] ?? null;

    if ($ultimaVisita === $hoy) {
        $racha = max($rachaPrevia, 1);
    } elseif ($ultimaVisita === date('Y-m-d', strtotime('-1 day'))) {
        $racha = $rachaPrevia + 1;
    } else {
        $racha = 1;
    }
    setcookie('sv_ultima_visita', $hoy, time() + 60 * 60 * 24 * 90, '/');
    setcookie('sv_racha_dias', (string) $racha, time() + 60 * 60 * 24 * 90, '/');
}

/* -----------------------------------------------------------
 * Reto del día
 * Banco de preguntas rotando automáticamente según el día del año,
 * sin necesitar backend. Cuando tengas un módulo real de trivia,
 * reemplaza $bancoRetos por tu propia fuente (BD, API, etc.).
 * --------------------------------------------------------- */
$bancoRetos = [
    ['pregunta' => '¿Cuál de estos NO es un lenguaje de programación?', 'opciones' => ['Python', 'Kotlin', 'Photoshop', 'Rust'], 'correcta' => 2],
    ['pregunta' => '¿Qué significa "HTTP"?', 'opciones' => ['HyperText Transfer Protocol', 'High Transfer Text Program', 'Home Tool Transfer Protocol', 'HyperTransfer Text Process'], 'correcta' => 0],
    ['pregunta' => '¿Cuál es la capital de Perú?', 'opciones' => ['Cusco', 'Arequipa', 'Lima', 'Trujillo'], 'correcta' => 2],
    ['pregunta' => '¿Qué unidad mide la resistencia eléctrica?', 'opciones' => ['Voltio', 'Amperio', 'Ohmio', 'Vatio'], 'correcta' => 2],
    ['pregunta' => '¿Cuál de estas estructuras de datos es "primero en entrar, primero en salir"?', 'opciones' => ['Pila (Stack)', 'Cola (Queue)', 'Árbol', 'Grafo'], 'correcta' => 1],
    ['pregunta' => '¿En qué año llegó el hombre a la Luna?', 'opciones' => ['1965', '1969', '1971', '1975'], 'correcta' => 1],
    ['pregunta' => '¿Qué significa "CPU"?', 'opciones' => ['Central Process Unit', 'Central Processing Unit', 'Computer Personal Unit', 'Central Programming Utility'], 'correcta' => 1],
];
$retoHoy = $bancoRetos[((int) date('z')) % count($bancoRetos)];

/* -----------------------------------------------------------
 * Anuncios del tablón — TODO backend
 * Reemplaza este arreglo de ejemplo por Anuncio::listarActivos()
 * (o el nombre que le des) cuando tengas la tabla lista.
 *
 * (Esta parte se había borrado por accidente — sin la asignación,
 * $anuncios quedaba indefinida y el tablón nunca se mostraba).
 * --------------------------------------------------------- */
$anuncios = [];
if (class_exists('Anuncio') && method_exists('Anuncio', 'listarActivos')) {
    $anuncios = Anuncio::listarActivos();
} else {
    // EJEMPLO — bórralo cuando conectes tu propia fuente de anuncios.
    $anuncios = [
        [
            'titulo' => 'Matrícula del ciclo',
            'texto'  => 'Recuerda completar tu matrícula antes del viernes para no perder tu vacante.',
            'autor'  => 'Secretaría Académica',
            'fecha'  => date('Y-m-d'),
        ],
        [
            'titulo' => 'Mantenimiento del Chat',
            'texto'  => 'El módulo de Chat estará en mantenimiento el domingo de 2:00 a 4:00 a.m.',
            'autor'  => 'Soporte CampusVirtual',
            'fecha'  => date('Y-m-d', strtotime('-1 day')),
        ],
    ];
}

// --- Puertas de la sala: cada módulo del campus, pintado como una fachada
//     colonial distinta (el color identifica el módulo, no es decorativo) ---
$puertas = [
    [
        'href'   => 'index_campovirtual.php',
        'icono'  => '🗺️',
        'titulo' => 'Hub Virtual',
        'desc'   => 'Explora el mapa del campus en 2D',
        'color'  => 'var(--azul-colonial)',
        'atajo'  => 'H',
    ],
    [
        'href'   => 'dashboard.php',
        'icono'  => '📋',
        'titulo' => 'Mi panel',
        'desc'   => 'Tareas, calendario y cursos',
        'color'  => 'var(--ocre)',
        'atajo'  => 'P',
        'badge'  => $tareasPendientes,
    ],
    [
        'href'   => 'chat.php',
        'icono'  => '💬',
        'titulo' => 'Chat',
        'desc'   => 'Conversa con tus compañeros',
        'color'  => 'var(--terracota)',
        'atajo'  => 'C',
        'badge'  => $mensajesNuevos,
    ],
    [
        'href'   => 'networking.php',
        'icono'  => '🌐',
        'titulo' => 'Networking',
        'desc'   => 'Conecta con otras facultades',
        'color'  => 'var(--oliva)',
        'atajo'  => 'N',
    ],
    [
        'href'   => 'battles.php',
        'icono'  => '⚡',
        'titulo' => 'Classroom Battles',
        'desc'   => 'Reta a tu clase en tiempo real',
        'color'  => 'var(--granate)',
        'atajo'  => 'B',
    ],
    [
        'href'   => 'perfil.php',
        'icono'  => '👤',
        'titulo' => 'Mi perfil',
        'desc'   => 'Edita tu información y foto',
        'color'  => 'var(--marfil)',
        'atajo'  => 'M',
    ],
    [
        'href'   => 'mapa3d.php',
        'icono'  => '🏛️',
        'titulo' => 'Mapa 3D',
        'desc'   => 'Recorre la portada principal en primera persona',
        'color'  => 'var(--azul-colonial)',
        'atajo'  => '3',
    ],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sala Virtual · Bienvenida</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,500;0,600;1,500&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/bienvenida.css">
<link rel="manifest" href="manifest.json">
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('sw.js');}</script>
</head>
<body data-momento="<?= $momento ?>">

<a href="#hub-principal" class="salto-contenido">Saltar al contenido</a>

<?php require __DIR__ . '/vistas/topbar.php'; ?>

<main class="hub-contenedor" id="hub-principal">

    <!-- Hero: la ventana de la sala -->
    <section class="hub-hero" aria-label="Bienvenida">

        <div class="hub-hero-ambiente" id="ambiente" aria-hidden="true">
            <div class="hub-hero-estrellas" id="particulas"></div>
            <div class="hub-hero-garua" id="garua"></div>
            <div class="hub-hero-rayos" id="rayos"></div>
        </div>

        <div class="hub-hero-contenido">
            <p class="hub-hero-fecha">
                <?= htmlspecialchars($fechaTexto) ?>
                <span class="hub-hero-reloj" id="relojCampanario" aria-live="off"></span>
                <span class="hub-racha" title="Días seguidos entrando al campus">
                    🔥 <?= (int) $racha ?> <?= $racha === 1 ? 'día' : 'días' ?>
                </span>
            </p>
            <h1 class="hub-hero-saludo">
                <?= htmlspecialchars($saludo) ?>, <span class="acento-texto"><?= htmlspecialchars($nombreUsuario) ?></span>
            </h1>
            <p class="hub-hero-sub">
                Bienvenido de nuevo a tu campus virtual
                <?php if ($facultadUsuario): ?>
                    · <?= htmlspecialchars($facultadUsuario) ?>
                <?php endif; ?>
            </p>

            <button type="button" class="hub-buscar-abrir" id="abrirComandos">
                <span aria-hidden="true">🔍</span> Buscar en el directorio…
                <kbd class="hub-buscar-atajo">Ctrl K</kbd>
            </button>
        </div>

        <button type="button" class="hub-hero-avatar" id="avatarCampanario" aria-label="Foto de perfil de <?= htmlspecialchars($nombreUsuario) ?>. Toca para hacer sonar la campana.">
            <div class="hub-hero-reja"></div>
            <div class="hub-hero-foto">
                <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="">
            </div>
            <span class="hub-hero-campana" id="campanaIcono" aria-hidden="true">🔔</span>
        </button>
    </section>
    <!-- Reto del día -->
    <section class="hub-reto" aria-label="Reto del día" id="retoDelDia" data-fecha="<?= date('Y-m-d') ?>" data-correcta="<?= (int) $retoHoy['correcta'] ?>">
        <div class="hub-reto-encabezado">
            <span class="hub-reto-icono">⚡</span>
            <div>
                <p class="hub-reto-titulo">Reto del día</p>
                <p class="hub-reto-pregunta"><?= htmlspecialchars($retoHoy['pregunta']) ?></p>
            </div>
        </div>
        <div class="hub-reto-opciones" id="retoOpciones">
            <?php foreach ($retoHoy['opciones'] as $i => $op): ?>
                <button type="button" class="hub-reto-opcion" data-indice="<?= $i ?>"><?= htmlspecialchars($op) ?></button>
            <?php endforeach; ?>
        </div>
        <p class="hub-reto-resultado oculto" id="retoResultado" role="status"></p>
    </section>

    <!-- Resumen del día -->
    <section class="hub-resumen" aria-label="Resumen del día">
        <div class="hub-resumen-item">
            <span class="hub-resumen-icono">📋</span>
            <div>
                <p class="hub-resumen-valor"><?= $tareasPendientes !== null ? (int) $tareasPendientes : '—' ?></p>
                <p class="hub-resumen-label">Tareas pendientes</p>
            </div>
        </div>
        <div class="hub-resumen-item">
            <span class="hub-resumen-icono">💬</span>
            <div>
                <p class="hub-resumen-valor"><?= $mensajesNuevos !== null ? (int) $mensajesNuevos : '—' ?></p>
                <p class="hub-resumen-label">Mensajes nuevos</p>
            </div>
        </div>
        <div class="hub-resumen-item hub-resumen-evento">
            <span class="hub-resumen-icono">🗓️</span>
            <div>
                <p class="hub-resumen-valor hub-resumen-valor-texto">
                    <?= $proximoEvento ? htmlspecialchars($proximoEvento['titulo']) : 'Sin eventos próximos' ?>
                </p>
                <p class="hub-resumen-label">
                    <?= $proximoEvento ? htmlspecialchars($proximoEvento['cuando']) : 'Tu calendario está libre' ?>
                </p>
            </div>
        </div>

         <?php if (!empty($companerosConectados)): ?>
         <div class="hub-resumen-item hub-resumen-conectados">
             <div class="hub-avatares-stack" aria-hidden="true">
                 <?php foreach (array_slice($companerosConectados, 0, 5) as $c): ?>
                     <span class="hub-avatar-stack-item" style="background:<?= htmlspecialchars($c['avatar_color'] ?? '#3B82F6') ?>;position:relative;" title="<?= htmlspecialchars($c['nombre']) ?>">
                         <?= mb_strtoupper(mb_substr($c['nombre'] ?? '?', 0, 1)) ?>
                         <span class="hub-online-dot"></span>
                     </span>
                 <?php endforeach; ?>
             </div>
            <div>
                <p class="hub-resumen-valor"><?= count($companerosConectados) ?></p>
                <p class="hub-resumen-label">Compañeros conectados</p>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <!-- Favoritos (se llena por JS desde localStorage; oculto si no hay ninguno) -->
    <section class="hub-favoritos oculto" id="franjaFavoritos" aria-label="Tus accesos favoritos">
        <p class="hub-favoritos-titulo">⭐ Tus favoritos <span class="hub-favoritos-ayuda">— arrastra para reordenar</span></p>
        <div class="hub-favoritos-lista" id="listaFavoritos"></div>
    </section>

    <!-- Accesos rápidos -->
    <section aria-label="Accesos rápidos">
        <div class="hub-encabezado">
            <h2>¿A dónde quieres ir?</h2>
            <p>Accede directo a cualquier módulo del campus</p>
        </div>

        <div class="puertas-grid" id="puertasGrid">
            <?php foreach ($puertas as $i => $p): ?>
            <div class="puerta"
                 style="--i: <?= $i ?>; --color-puerta: <?= $p['color'] ?>;"
                 data-href="<?= htmlspecialchars($p['href']) ?>"
                 data-titulo="<?= htmlspecialchars(mb_strtolower($p['titulo'])) ?>"
                 data-desc="<?= htmlspecialchars(mb_strtolower($p['desc'])) ?>">
                <button type="button" class="puerta-fav" data-href="<?= htmlspecialchars($p['href']) ?>" aria-pressed="false" aria-label="Marcar «<?= htmlspecialchars($p['titulo']) ?>» como favorito">📌</button>
                <?php if (!empty($p['badge'])): ?>
                    <span class="puerta-badge"><?= (int) $p['badge'] > 9 ? '9+' : (int) $p['badge'] ?></span>
                <?php endif; ?>
                <a href="<?= htmlspecialchars($p['href']) ?>" class="puerta-enlace">
                    <span class="puerta-icono"><?= $p['icono'] ?></span>
                    <span class="puerta-titulo"><?= htmlspecialchars($p['titulo']) ?></span>
                    <span class="puerta-desc"><?= htmlspecialchars($p['desc']) ?></span>
                    <kbd class="puerta-atajo"><?= htmlspecialchars($p['atajo']) ?></kbd>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <p class="puertas-vacio oculto" id="puertasVacio">Ningún módulo coincide con tu búsqueda.</p>
    </section>

</main>

<!-- Paleta de comandos / índice rápido -->
<div class="hub-comandos" id="modalComandos" hidden>
    <div class="hub-comandos-panel" role="dialog" aria-modal="true" aria-label="Índice rápido del campus">
        <div class="hub-comandos-input-fila">
            <span aria-hidden="true">🔍</span>
            <input type="text" id="inputComandos" placeholder="Escribe el nombre de un módulo…" autocomplete="off">
            <kbd>Esc</kbd>
        </div>
        <ul class="hub-comandos-lista" id="listaComandos"></ul>
    </div>
</div>

<!-- Datos de los módulos para la paleta de comandos -->
<script id="datosPuertas" type="application/json"><?= json_encode(array_map(function ($p) {
    return [
        'href'   => $p['href'],
        'icono'  => $p['icono'],
        'titulo' => $p['titulo'],
        'desc'   => $p['desc'],
        'atajo'  => $p['atajo'],
    ];
}, $puertas), JSON_UNESCAPED_UNICODE) ?></script>

<script src="assets/js/bienvenida.js"></script>
</body>
</html>