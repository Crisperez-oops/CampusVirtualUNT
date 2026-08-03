<?php
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoloader.php';
requerirSesion();

$usuarioId = (int)$_SESSION['usuario_id'];
$contenido = $_POST['contenido'] ?? '';

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['imagen'];
    $tmp = $file['tmp_name'];
    $size = $file['size'];
    if ($size > 20 * 1024 * 1024) { echo json_encode(['ok'=>false,'error'=>'Archivo muy grande (máx 20MB)']); exit; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','mp4','mov','avi','webm','mkv'];
    if (!in_array($ext, $allowed)) { echo json_encode(['ok'=>false,'error'=>'Formato no permitido. Usa JPG, PNG, GIF, MP4, MOV, WEBM']); exit; }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg','image/png','image/gif','image/webp','video/mp4','video/quicktime','video/x-msvideo','video/webm','video/x-matroska'];
    if (!in_array($mime, $allowedMimes)) { echo json_encode(['ok'=>false,'error'=>'Tipo de archivo no permitido']); exit; }
    
    $dir = __DIR__ . '/../assets/posts/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $nombre = 'post_' . $usuarioId . '_' . uniqid() . '.' . $ext;
    $ruta = $dir . $nombre;
    
    if (move_uploaded_file($tmp, $ruta)) {
        $tipo = in_array($ext, ['mp4','mov','avi','webm','mkv']) ? 'video' : 'imagen';
        $id = Publicacion::crear($usuarioId, $contenido, 'assets/posts/' . $nombre);
        echo json_encode(['ok'=>true, 'id'=>$id, 'tipo'=>$tipo]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Error al guardar']);
    }
} else {
    echo json_encode(['ok'=>false,'error'=>'No se recibió imagen']);
}
