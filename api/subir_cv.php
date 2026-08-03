<?php
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cv_pdf'])) {
    $file = $_FILES['cv_pdf'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        header('Location: ../empleos.php?error=upload');
        exit;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        header('Location: ../empleos.php?error=formato');
        exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($mime !== 'application/pdf') {
        header('Location: ../empleos.php?error=mime');
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        header('Location: ../empleos.php?error=tamano');
        exit;
    }
    $dir = __DIR__ . '/../assets/cvs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nombre = uniqid('cv_') . '_' . substr(md5($file['name']), 0, 8) . '.pdf';
    $destino = $dir . $nombre;
    if (move_uploaded_file($file['tmp_name'], $destino)) {
        EstudianteCV::subir($usuarioId, 'assets/cvs/' . $nombre, [
            'habilidades_tags' => $_POST['habilidades_tags'] ?? '',
            'linkedin_url' => $_POST['linkedin_url'] ?? '',
            'github_url' => $_POST['github_url'] ?? '',
            'portafolio_url' => $_POST['portafolio_url'] ?? '',
        ]);
        header('Location: ../empleos.php?ok=cv_subido');
    } else {
        header('Location: ../empleos.php?error=guardar');
    }
    exit;
}
http_response_code(400);
