<?php
/**
 * linkedin_callback.php
 * Recibe el code de LinkedIn OAuth2, obtiene token + perfil, y crea/víncula usuario.
 * 
 * Configuración necesaria en tu LinkedIn App:
 *   Redirect URI: http://localhost/proyecto%20uni/linkedin_callback.php
 *   Scopes: openid profile email
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';

// ⚠️ REEMPLAZA con tus credenciales reales de LinkedIn App
define('LINKEDIN_CLIENT_ID',     'TU_CLIENT_ID');
define('LINKEDIN_CLIENT_SECRET', 'TU_CLIENT_SECRET');
define('LINKEDIN_REDIRECT_URI',  'http://localhost/proyecto%20uni/linkedin_callback.php');

$code = $_GET['code'] ?? null;
$error = $_GET['error'] ?? null;

if ($error) {
    die('Error de LinkedIn: ' . htmlspecialchars($error));
}

if (!$code) {
    die('No se recibió código de autorización.');
}

// 1. Intercambiar code por access_token
$ch = curl_init('https://www.linkedin.com/oauth/v2/accessToken');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'client_id' => LINKEDIN_CLIENT_ID,
        'client_secret' => LINKEDIN_CLIENT_SECRET,
        'redirect_uri' => LINKEDIN_REDIRECT_URI,
    ]),
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $err = json_decode($response, true);
    die('Error al obtener token: ' . ($err['error_description'] ?? $response));
}

$token = json_decode($response, true);
$accessToken = $token['access_token'];

// 2. Obtener perfil del usuario (OpenID Connect userinfo)
$ch = curl_init('https://api.linkedin.com/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
]);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

if (!$userInfo || empty($userInfo['email'])) {
    die('No se pudo obtener el perfil de LinkedIn.');
}

// 3. Vincular o crear usuario en CampusVirtual
$email = strtolower($userInfo['email']);
$nombre = $userInfo['name'] ?? $userInfo['given_name'] . ' ' . $userInfo['family_name'];
$linkedinId = $userInfo['sub'];
$fotoLinkedIn = $userInfo['picture'] ?? null;

$db = Database::obtenerConexion();

// Buscar si ya existe usuario con ese email
$stmt = $db->prepare("SELECT id, nombre, avatar_color FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
    // Usuario existe: iniciar sesión directamente
    $uid = $usuario['id'];
    $stmt = $db->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?");
    $stmt->execute([$uid]);

    // Descargar foto de LinkedIn si no tiene foto
    if ($fotoLinkedIn) {
        require_once __DIR__ . '/clases/Perfil.php';
        $fotoHost = parse_url($fotoLinkedIn, PHP_URL_HOST);
        $fotoScheme = parse_url($fotoLinkedIn, PHP_URL_SCHEME);
        if ($fotoScheme === 'https' && $fotoHost && preg_match('/(\.|^)(licdn\.com|linkedin\.com)$/', $fotoHost)) {
        $perfil = Perfil::obtenerPorUsuario($uid);
        if (empty($perfil['foto'])) {
            $dir = __DIR__ . '/assets/fotos/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ctx = stream_context_create(['http' => ['timeout' => 10, 'follow_location' => 0]]);
            $fotoData = @file_get_contents($fotoLinkedIn, false, $ctx);
            if ($fotoData && strlen($fotoData) < 5 * 1024 * 1024) {
                $nombreArchivo = 'li_' . $uid . '_' . uniqid() . '.jpg';
                file_put_contents($dir . $nombreArchivo, $fotoData);
                Perfil::actualizar($uid, $perfil['descripcion'] ?? '', $perfil['habilidades_tags'] ?? '', 'assets/fotos/' . $nombreArchivo);
            }
        }
    }
    }
} else {
    // Usuario nuevo: registrar con datos de LinkedIn
    // Asignar a la primera facultad
    $facultad = $db->query("SELECT id FROM facultades LIMIT 1")->fetch();
    $facultadId = $facultad['id'] ?? 1;

    $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $color = ['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6'][array_rand(['#3B82F6','#EF4444','#10B981','#F59E0B','#8B5CF6'])];

    $stmt = $db->prepare(
        "INSERT INTO usuarios (nombre, email, password, facultad_id, avatar_color, fecha_registro)
         VALUES (?,?,?,?,?,NOW())"
    );
    $stmt->execute([$nombre, $email, $hash, $facultadId, $color]);
    $uid = $db->lastInsertId();

    // Crear perfil vacío
    $db->prepare("INSERT INTO perfiles_habilidades (usuario_id) VALUES (?)")->execute([$uid]);

    // Descargar foto de LinkedIn
    if ($fotoLinkedIn) {
        $dir = __DIR__ . '/assets/fotos/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fotoData = @file_get_contents($fotoLinkedIn);
        if ($fotoData) {
            $nombreArchivo = 'li_' . $uid . '_' . uniqid() . '.jpg';
            file_put_contents($dir . $nombreArchivo, $fotoData);
            require_once __DIR__ . '/clases/Perfil.php';
            Perfil::actualizar($uid, '', '', 'assets/fotos/' . $nombreArchivo);
        }
    }
}

// 4. Iniciar sesión
$usuarioFull = Usuario::obtenerPorId($uid);
iniciarSesionSegura();
session_regenerate_id(true);
$_SESSION['usuario_id']       = (int)$uid;
$_SESSION['usuario_nombre']   = $usuarioFull['nombre'];
$_SESSION['usuario_email']    = $usuarioFull['email'];
$_SESSION['facultad_id']      = (int)$usuarioFull['facultad_id'];
$_SESSION['avatar_color']     = $usuarioFull['avatar_color'];

header('Location: /proyecto%20uni/index.php');
exit;
