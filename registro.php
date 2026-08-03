<?php
/**
 * registro.php
 * Formulario de registro + procesamiento. Solo acepta correos
 * @unitru.edu.pe (validado en Usuario::registrar con regex estricto).
 */

require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/autoloader.php';
require_once __DIR__ . '/helpers/CSRF.php';

iniciarSesionSegura();

// Si ya hay sesión activa, mándalo directo al hub
if (!empty($_SESSION['usuario_id'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = null;
$exito = false;
$nombrePost = '';
$emailPost = '';
$facultadPost = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCSRF();
    $nombrePost   = $_POST['nombre'] ?? '';
    $emailPost    = $_POST['email'] ?? '';
    $facultadPost = $_POST['facultad_id'] ?? '';
    $password     = $_POST['password'] ?? '';
    $password2    = $_POST['password2'] ?? '';

    if ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $resultado = Usuario::registrar(
            $nombrePost,
            $emailPost,
            $password,
            (int) $facultadPost
        );

        if ($resultado['ok']) {
            $exito = true;
        } else {
            $error = $resultado['error'];
        }
    }
}

$facultades = Facultad::obtenerTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Crear cuenta · CampusVirtual UNITRU</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-logo">CV</div>
                <div>
                    <h1>CampusVirtual</h1>
                    <p>UNITRU · Hub Social</p>
                </div>
            </div>

            <?php if ($exito): ?>
                <div class="auth-alert auth-alert-ok">
                    ¡Cuenta creada! Ya puedes <a href="login.php">iniciar sesión</a>.
                </div>
            <?php else: ?>

                <?php if ($error): ?>
                    <div class="auth-alert auth-alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="registro.php" class="auth-form" novalidate>
                    <label>
                        <span>Nombre completo</span>
                        <input type="text" name="nombre" placeholder="Ej. María Pérez Sánchez"
                               value="<?= htmlspecialchars($nombrePost) ?>" required minlength="3" maxlength="150">
                    </label>

                    <label>
                        <span>Correo institucional</span>
                        <input type="email" name="email" placeholder="tunombre@unitru.edu.pe"
                               value="<?= htmlspecialchars($emailPost) ?>"
                               pattern="[a-zA-Z0-9._%+\-]+@unitru\.edu\.pe" required
                               title="Debe ser un correo @unitru.edu.pe">
                        <small>Solo se aceptan correos @unitru.edu.pe</small>
                    </label>

                    <label>
                        <span>Facultad</span>
                        <select name="facultad_id" required>
                            <option value="">Selecciona tu facultad</option>
                            <?php foreach ($facultades as $f): ?>
                                <option value="<?= (int) $f['id'] ?>" <?= ($facultadPost == $f['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="auth-grid-2">
                        <label>
                            <span>Contraseña</span>
                            <input type="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8">
                        </label>
                        <label>
                            <span>Confirmar</span>
                            <input type="password" name="password2" placeholder="Repite tu contraseña" required minlength="8">
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">Crear cuenta</button>
                    <?= campoCSRF() ?>
                </form>

                <p class="auth-footer">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
