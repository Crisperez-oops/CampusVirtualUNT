<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/clases/Database.php';
iniciarSesionSegura();
iniciarSesionSegura();

if (empty($_SESSION['google_temp_user'])) {
    header('Location: login.php');
    exit;
}

$temp = $_SESSION['google_temp_user'];
$db = Database::obtenerConexion();
$facultades = $db->query("SELECT id, nombre FROM facultades ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['facultad_id'])) {
    $facultadId = (int)$_POST['facultad_id'];
    $color = ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6'][array_rand(range(0,4))];
    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

    $stmt = $db->prepare(
        "INSERT INTO usuarios (nombre, email, password, facultad_id, avatar_color, fecha_registro)
         VALUES (?,?,?,?,?,NOW())"
    );
    $stmt->execute([$temp['nombre'], $temp['email'], $hash, $facultadId, $color]);
    $uid = $db->lastInsertId();
    $db->prepare("INSERT INTO perfiles_habilidades (usuario_id) VALUES (?)")->execute([$uid]);

    // Descargar foto de Google (con validación anti-SSRF)
    if (!empty($temp['foto'])) {
        $fotoHost = parse_url($temp['foto'], PHP_URL_HOST);
        $fotoScheme = parse_url($temp['foto'], PHP_URL_SCHEME);
        if ($fotoScheme === 'https' && $fotoHost && preg_match('/(\.|^)googleusercontent\.com$/', $fotoHost)) {
            $dir = __DIR__ . '/assets/fotos/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ctx = stream_context_create(['http' => ['timeout' => 10, 'follow_location' => 0]]);
            $fotoData = @file_get_contents($temp['foto'], false, $ctx);
            if ($fotoData && strlen($fotoData) < 5 * 1024 * 1024) {
                $nombreArchivo = 'g_' . $uid . '_' . uniqid() . '.jpg';
                file_put_contents($dir . $nombreArchivo, $fotoData);
                require_once __DIR__ . '/clases/Perfil.php';
                Perfil::actualizar($uid, '', '', 'assets/fotos/' . $nombreArchivo);
            }
        }
    }

    unset($_SESSION['google_temp_user']);
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int)$uid;
    $_SESSION['usuario_nombre'] = $temp['nombre'];
    $_SESSION['usuario_email'] = $temp['email'];
    $_SESSION['facultad_id'] = $facultadId;
    $_SESSION['avatar_color'] = $color;

    header('Location: /proyecto/index.php');
    exit;
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Completar registro · CampusVirtual</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);font-family:'Inter',sans-serif;}
.comp-card{background:rgba(255,255,255,0.95);border-radius:16px;padding:40px;max-width:440px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;}
.comp-card h2{font-size:22px;font-weight:700;margin-bottom:8px;color:#1a1a2e;}
.comp-card p{color:#65676b;font-size:14px;margin-bottom:24px;}
.comp-card select{width:100%;padding:12px 16px;border:2px solid #e4e6eb;border-radius:10px;font-size:15px;font-family:inherit;outline:none;cursor:pointer;margin-bottom:16px;}
.comp-card select:focus{border-color:#1b74e4;}
.comp-btn{background:#1b74e4;color:#fff;border:none;padding:12px 32px;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;width:100%;font-family:inherit;transition:background .15s;}
.comp-btn:hover{background:#1664d0;}
</style></head><body>
<div class="comp-card">
    <div style="font-size:48px;margin-bottom:12px;">🎓</div>
    <h2>¡Casi listo, <?=htmlspecialchars($temp['nombre'])?>!</h2>
    <p>Selecciona tu facultad para completar el registro</p>
    <form method="POST">
        <select name="facultad_id" required>
            <option value="">-- Selecciona tu facultad --</option>
            <?php foreach($facultades as $f): ?>
            <option value="<?=$f['id']?>"><?=htmlspecialchars($f['nombre'])?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="comp-btn">Ingresar a CampusVirtual</button>
    </form>
</div>
</body></html>
