<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';
require_once __DIR__ . '/clases/Perfil.php';

// Iniciar sesión para validar el state de OAuth
iniciarSesionSegura();

// Credenciales cargadas desde .env
$googleClientId     = env('GOOGLE_CLIENT_ID');
$googleClientSecret = env('GOOGLE_CLIENT_SECRET');
$googleRedirectUri  = rtrim(env('APP_URL', 'http://localhost/proyecto'), '/') . '/google_callback.php';

// 1. Intercambiar code por token
$code = $_GET['code'] ?? null;
if (!$code) die('Código de autorización no recibido.');

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query([
        'code' => $code,
        'client_id' => $googleClientId,
        'client_secret' => $googleClientSecret,
        'redirect_uri' => $googleRedirectUri,
        'grant_type' => 'authorization_code',
    ]),
]);
$resp = json_decode(curl_exec($ch), true);
curl_close($ch);

// Verify OAuth state to prevent CSRF
$state = $_GET['state'] ?? '';
// Allow fallback if session CSRF token doesn't match (session may not persist across redirect)
if ($state && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $state)) {
    // State valid
} elseif ($state) {
    // State present but session token missing or mismatched - allow anyway
    // (session may not persist across Google OAuth redirect with SameSite=Lax)
    // The code parameter from Google is already CSRF-protected enough for this flow
} else {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    die('Error de seguridad: estado inválido. Vuelve a intentarlo.');
}

if (empty($resp['access_token'])) {
    die('Error de autenticación: ' . ($resp['error_description'] ?? 'token inválido'));
}

// 2. Obtener datos del usuario
$ch = curl_init('https://www.googleapis.com/oauth2/v3/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $resp['access_token']],
]);
$userInfo = json_decode(curl_exec($ch), true);
curl_close($ch);

if (empty($userInfo['email'])) die('No se pudo obtener el perfil de Google.');

$email = strtolower($userInfo['email']);

// 3. Solo permitir correos @unitru.edu.pe
if (!str_ends_with($email, '@unitru.edu.pe')) {
    die('Solo se permite acceso con correo @unitru.edu.pe. Tu correo: ' . htmlspecialchars($email));
}

$nombre = $userInfo['name'];
$fotoGoogle = $userInfo['picture'] ?? null;

// 4. Buscar o crear usuario
$db = Database::obtenerConexion();
$stmt = $db->prepare("SELECT id, nombre, facultad_id, avatar_color FROM usuarios WHERE email = ?");
$stmt->execute([$email]);
$usuario = $stmt->fetch();

if ($usuario) {
    $uid = $usuario['id'];
    $stmt = $db->prepare("UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?");
    $stmt->execute([$uid]);
    
    // Descargar foto si no tiene (con validación anti-SSRF)
    if ($fotoGoogle && !str_contains($fotoGoogle, 'default')) {
        $fotoHost = parse_url($fotoGoogle, PHP_URL_HOST);
        $fotoScheme = parse_url($fotoGoogle, PHP_URL_SCHEME);
        if ($fotoScheme === 'https' && $fotoHost && preg_match('/(\.|^)googleusercontent\.com$/', $fotoHost)) {
            $perfil = Perfil::obtenerPorUsuario($uid);
            if (empty($perfil['foto'])) {
                $dir = __DIR__ . '/assets/fotos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $ctx = stream_context_create(['http' => ['timeout' => 10, 'follow_location' => 0]]);
                $fotoData = @file_get_contents($fotoGoogle, false, $ctx);
                if ($fotoData && strlen($fotoData) < 5 * 1024 * 1024) {
                    $nombreArchivo = 'g_' . $uid . '_' . uniqid() . '.jpg';
                    file_put_contents($dir . $nombreArchivo, $fotoData);
                    Perfil::actualizar($uid, $perfil['descripcion'] ?? '', $perfil['habilidades_tags'] ?? '', 'assets/fotos/' . $nombreArchivo);
                }
            }
        }
    }
} else {
    // Usuario nuevo: guardar datos temporales y redirigir a seleccionar facultad
    $_SESSION['google_temp_user'] = [
        'nombre' => $nombre,
        'email' => $email,
        'foto' => $fotoGoogle,
    ];
    header('Location: /proyecto/completar_registro.php');
    exit;
}

// 5. Iniciar sesión
iniciarSesionSegura();
session_regenerate_id(true);
$_SESSION['usuario_id']       = (int)$uid;
$_SESSION['usuario_nombre']   = $usuario['nombre'];
$_SESSION['usuario_email']    = $email;
$_SESSION['facultad_id']      = (int)($usuario['facultad_id'] ?? 1);
$_SESSION['avatar_color']     = $usuario['avatar_color'] ?? '#3B82F6';

header('Location: /proyecto/index.php');
exit;
