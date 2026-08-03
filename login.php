<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';

$googleClientIdRaw = env('GOOGLE_CLIENT_ID', 'TU_CLIENT_ID');
$googleRedirectUri = rtrim(env('APP_URL', 'http://localhost/proyecto'), '/') . '/google_callback.php';
iniciarSesionSegura();
if (!empty($_SESSION['usuario_id'])) { header('Location: /proyecto/index.php'); exit; }

$error = '';
$bloqueado = false;

// Obtener IP real del cliente (ngrok la pasa en X-Forwarded-For)
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ip = explode(',', $ip)[0]; // Tomar solo la primera si hay múltiples
$ip = trim($ip);

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

// Rate limiting: IP (10 intentos/15min) + Sesión (5 intentos/15min)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Sesión inválida. Recarga la página.'; $bloqueado = true;
    }
    if (!$bloqueado) {
    $ahora = time();
    
    // Capa 1: Rate limiting por IP (previene bypass rotando cookies)
    $db = Database::obtenerConexion();
    $stmtIP = $db->prepare("SELECT intentos, primer_intento FROM login_intentos WHERE ip = ? AND primer_intento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmtIP->execute([$ip]);
    $ipData = $stmtIP->fetch();
    if ($ipData && (int)$ipData['intentos'] >= 10) { $bloqueado = true; $error = 'Demasiados intentos desde esta red. Espera 15 minutos.'; }
    
    // Capa 2: Rate limiting por sesión
    if (!$bloqueado) {
    $intentos = $_SESSION['login_intentos'] ?? ['count' => 0, 'first' => $ahora];
    if ($ahora - $intentos['first'] > 900) { $intentos = ['count' => 0, 'first' => $ahora]; }
    if ($intentos['count'] >= 5) { $bloqueado = true; $error = 'Demasiados intentos. Espera 15 minutos.'; }
    else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (!is_string($email) || !is_string($password)) { $error = 'Datos inválidos'; }
        else {
            $email = trim($email);
            if (empty($email) || empty($password)) { $error = 'Completa todos los campos'; }
            else {
                $intentos['count']++;
                $_SESSION['login_intentos'] = $intentos;
                // Incrementar contador IP
                if ($ipData) {
                    $db->prepare("UPDATE login_intentos SET intentos = intentos + 1 WHERE ip = ?")->execute([$ip]);
                } else {
                    $db->prepare("INSERT INTO login_intentos (ip, intentos) VALUES (?, 1)")->execute([$ip]);
                }
                $usuario = Usuario::autenticar($email, $password);
            if ($usuario) {
                session_regenerate_id(true);
                unset($_SESSION['login_intentos'], $_SESSION['csrf_token']);
                // Limpiar intentos de esta IP al iniciar sesión exitoso
                $db->prepare("DELETE FROM login_intentos WHERE ip = ?")->execute([$ip]);
                $_SESSION['usuario_id']=(int)$usuario['id'];$_SESSION['usuario_nombre']=$usuario['nombre'];$_SESSION['usuario_email']=$usuario['email'];$_SESSION['usuario_rol']=$usuario['rol']??'vendedor';$_SESSION['facultad_id']=(int)$usuario['facultad_id'];$_SESSION['usuario_facultad_id']=(int)$usuario['facultad_id'];$_SESSION['facultad_nombre']=$usuario['facultad_nombre'];$_SESSION['facultad_codigo']=$usuario['facultad_codigo'];$_SESSION['facultad_color']=$usuario['facultad_color'];$_SESSION['avatar_color']=$usuario['avatar_color'];
                header('Location: /proyecto/index.php'); exit;
            }
            $error = 'Credenciales incorrectas';
        }
    }
    }
    }
    }
}
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0,viewport-fit=cover"><title>CampusVirtual · UNITRU</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{min-height:100vh;min-height:100dvh;display:flex;align-items:center;justify-content:center;font-family:'Inter',-apple-system,sans-serif;position:relative;overflow-y:auto;overflow-x:hidden}
.bg{position:fixed;inset:0;background:linear-gradient(135deg,rgba(15,23,42,.88),rgba(30,41,59,.82),rgba(15,23,42,.92)),url('entrada-principal-de-la-universidad-nacional-de-trujillo.jpg') center/cover no-repeat;z-index:-1}
.login-container{display:flex;max-width:840px;width:92%;background:rgba(255,255,255,.94);border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.35),0 2px 8px rgba(0,0,0,.1);animation:fadeUp .5s ease}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.login-left{flex:1;padding:36px 32px;display:flex;flex-direction:column;justify-content:center;background:linear-gradient(135deg,#f8fafc,#eef2ff)}
.login-left h2{font-size:22px;font-weight:700;color:#0f172a;margin-bottom:4px}
.login-left .subtitle{font-size:15px;color:#3B5BDB;font-weight:600;margin-bottom:14px}
.login-left p{font-size:12.5px;color:#475569;line-height:1.6;margin-bottom:18px}
.login-features{display:flex;flex-direction:column;gap:8px}
.login-feat{display:flex;align-items:center;gap:9px;font-size:12.5px;color:#334155}
.login-feat-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.login-right{flex:1;padding:36px 32px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
.login-logo{width:60px;height:60px;background:linear-gradient(135deg,#3B5BDB,#8B5CF6);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;color:#fff;margin-bottom:14px}
.login-right h3{font-size:19px;font-weight:700;color:#0f172a;margin-bottom:2px}
.login-right .text{font-size:12.5px;color:#64748b;margin-bottom:20px}
.btn-google{display:flex;align-items:center;justify-content:center;gap:10px;background:#fff;color:#3c4043;padding:12px 24px;border-radius:10px;text-decoration:none;font-weight:500;font-size:13.5px;border:1px solid #dadce0;transition:all .2s;width:100%;max-width:300px}
.btn-google:hover{background:#f8f9fa;box-shadow:0 4px 12px rgba(0,0,0,.08);transform:translateY(-1px)}
.btn-google svg{flex-shrink:0}
.login-footer{margin-top:14px;font-size:11px;color:#94a3b8}

@media(max-width:600px){
    body{align-items:flex-start;padding:14px}
    .login-container{flex-direction:column;max-width:390px;width:100%;border-radius:16px;box-shadow:0 12px 36px rgba(0,0,0,.3)}
    .login-left{padding:22px 18px;text-align:center}
    .login-left h2{font-size:18px}
    .login-left .subtitle{font-size:13px;margin-bottom:8px}
    .login-left p{font-size:11.5px;margin-bottom:10px}
    .login-feat{font-size:11px;gap:7px;justify-content:center}
    .login-feat-icon{width:24px;height:24px;font-size:13px;border-radius:6px}
    .login-right{padding:18px 18px 24px}
    .login-logo{width:44px;height:44px;font-size:18px;border-radius:12px;margin-bottom:10px}
    .login-right h3{font-size:16px}
    .login-right .text{font-size:11.5px;margin-bottom:14px}
    .btn-google{padding:10px 16px;font-size:12.5px;max-width:100%}
    input[name=email],input[name=password]{padding:9px 11px!important;font-size:12.5px!important;border-radius:8px!important}
    button[type=submit]{padding:9px!important;font-size:12.5px!important;border-radius:8px!important}
}

@media(max-width:370px){
    body{padding:8px}
    .login-container{max-width:100%;border-radius:14px}
    .login-left{padding:16px 12px}
    .login-left h2{font-size:16px}
    .login-left .subtitle{font-size:12px}
    .login-left p{font-size:10.5px;margin-bottom:6px}
    .login-features{gap:5px}
    .login-feat{font-size:10px;gap:5px}
    .login-feat-icon{width:20px;height:20px;font-size:11px;border-radius:5px}
    .login-right{padding:14px 12px 20px}
    .login-logo{width:38px;height:38px;font-size:16px;border-radius:10px;margin-bottom:8px}
    .login-right h3{font-size:15px}
    .login-right .text{font-size:10.5px;margin-bottom:10px}
    .btn-google{padding:9px 12px;font-size:11.5px;gap:7px}
    .btn-google svg{width:18px;height:18px}
    input[name=email],input[name=password]{padding:8px 10px!important;font-size:11.5px!important}
    button[type=submit]{padding:8px!important;font-size:11.5px!important}
}
</style></head><body>
<div class="bg"></div>
<div class="login-container">
    <div class="login-left">
        <h2>CampusVirtual UNITRU</h2>
        <div class="subtitle">Tu Hub Social Universitario</div>
        <p>Conecta con estudiantes de la Universidad Nacional de Trujillo. Comparte, colabora y crece con tu comunidad.</p>
        <div class="login-features">
            <div class="login-feat"><div class="login-feat-icon" style="background:rgba(59,91,219,.1);color:#3B5BDB">📰</div> Feed social con publicaciones, likes y comentarios</div>
            <div class="login-feat"><div class="login-feat-icon" style="background:rgba(14,159,110,.1);color:#0E9F6E">👥</div> Conecta con compañeros de todas las facultades</div>
            <div class="login-feat"><div class="login-feat-icon" style="background:rgba(139,92,246,.1);color:#8B5CF6">🎓</div> Grupos de estudio, foros y marketplace</div>
            <div class="login-feat"><div class="login-feat-icon" style="background:rgba(245,158,11,.1);color:#F59E0B">💼</div> Bolsa de empleos y perfil profesional</div>
        </div>
    </div>
    <div class="login-right">
        <div class="login-logo">CV</div>
        <h3>Iniciar sesión</h3>
        <p class="text">Usa tu correo institucional @unitru.edu.pe</p>
        <a href="https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=<?= urlencode($googleClientIdRaw) ?>&redirect_uri=<?= urlencode($googleRedirectUri) ?>&scope=openid%20profile%20email&prompt=select_account&state=<?= $_SESSION['csrf_token'] ?? '' ?>" class="btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Ingresar con Google
        </a>
        <div style="display:flex;align-items:center;gap:10px;width:100%;max-width:300px;margin:16px 0;color:#94a3b8;font-size:12px"><span style="flex:1;height:1px;background:#e2e8f0"></span>o con email<span style="flex:1;height:1px;background:#e2e8f0"></span></div>
        <?php if($error): ?><div style="color:#dc2626;font-size:12px;margin-bottom:8px;background:#fef2f2;padding:8px 12px;border-radius:8px;width:100%;max-width:300px"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <form method="POST" style="width:100%;max-width:300px;display:flex;flex-direction:column;gap:8px">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="email" name="email" placeholder="correo@ejemplo.com" required style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f8fafc">
            <input type="password" name="password" placeholder="Contraseña" required style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-family:inherit;outline:none;background:#f8fafc">
            <button type="submit" style="width:100%;padding:10px;background:var(--acento);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit">Ingresar</button>
        </form>
        <p class="login-footer">Solo @unitru.edu.pe</p>
    </div>
</div>
</body></html>
