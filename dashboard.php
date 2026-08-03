<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
requerirSesion();

$usuarioId = (int) $_SESSION['usuario_id'];
$usuario = Usuario::obtenerPorId($usuarioId);
$inicialNombre = mb_strtoupper(mb_substr($usuario['nombre'] ?? '?', 0, 1));
$perfilDash = Perfil::obtenerPorUsuario($usuarioId);
$fotoDash = $perfilDash['foto'] ?? null;
$mesActual = (int) date('n');
$anioActual = date('Y');
$diaActual = (int) date('j');
$fotoPerfil = $fotoDash;
$tieneFoto = !empty($fotoPerfil);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<?php require __DIR__ . '/vistas/topbar.php'; ?>

<main class="dash-main">

    <!-- Pestañas de navegación -->
    <div class="dash-tabs">
        <button class="dash-tab active" onclick="mostrarSeccion('inicio',this)">🏠 Inicio</button>
        <button class="dash-tab" onclick="mostrarSeccion('tareas',this)">✅ Tareas</button>
        <button class="dash-tab" onclick="mostrarSeccion('calendario',this)">📅 Calendario</button>
        <button class="dash-tab" onclick="mostrarSeccion('cursos',this)">🎓 Cursos</button>
        <button class="dash-tab" onclick="mostrarSeccion('notas',this)">📝 Notas</button>
    </div>

    <!-- Secciones -->
    <?php require __DIR__ . '/vistas/dashboard/sec_inicio.php'; ?>
    <?php require __DIR__ . '/vistas/dashboard/sec_tareas.php'; ?>
    <?php require __DIR__ . '/vistas/dashboard/sec_calendario.php'; ?>
    <?php require __DIR__ . '/vistas/dashboard/sec_cursos.php'; ?>
    <?php require __DIR__ . '/vistas/dashboard/sec_notas.php'; ?>
</main>

<?php require __DIR__ . '/vistas/dashboard/modal_curso.php'; ?>

<script>
function mostrarSeccion(id, btn) {
    document.querySelectorAll('.dash-seccion').forEach(s => s.classList.remove('activa'));
    document.getElementById('sec-' + id).classList.add('activa');
    document.querySelectorAll('.dash-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    document.getElementById('topbar-titulo').textContent = 
        {inicio:'Inicio',tareas:'Tareas',calendario:'Calendario',cursos:'Cursos',notas:'Notas'}[id]||id;
    if (id==='calendario') renderCalendario();
    if (id==='tareas') renderTareas();
    if (id==='notas') renderNotas();
    if (id==='cursos') renderCursos();
    if (id==='inicio') renderInicio();
}
</script>
<script>
const HOY_DIA=<?=$diaActual?>;const HOY_MES=<?=$mesActual?>;const HOY_ANIO=<?=$anioActual?>;
</script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
