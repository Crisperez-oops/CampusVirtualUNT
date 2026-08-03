<?php
require_once __DIR__ . '/config/constantes.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/clases/Database.php';
require_once __DIR__ . '/clases/Usuario.php';

requerirSesion();
$usuario = Usuario::obtenerPorId((int) $_SESSION['usuario_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unirse a Classroom Battle</title>
<link rel="stylesheet" href="assets/css/estilo.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
.ub-caja { background: var(--bg-panel); border: 1px solid var(--linea); border-radius: 16px; padding: 32px; width: 100%; max-width: 380px; text-align:center; }
.ub-titulo { font-family: var(--fuente-display); font-size: 20px; font-weight:700; margin-bottom: 6px; }
.ub-sub { color: var(--texto-tenue); font-size: 12.5px; margin-bottom: 22px; }
.ub-codigo-input {
    width: 100%; text-align:center; letter-spacing: 0.35em; font-family: var(--fuente-display);
    font-size: 26px; font-weight:700; background: var(--bg-panel-alt); border: 1px solid var(--linea);
    color: var(--texto-principal); padding: 14px; border-radius: 10px; outline:none; box-sizing:border-box; margin-bottom: 14px;
}
.ub-codigo-input:focus { border-color: var(--acento); }
.ub-apodo-input {
    width:100%; background: var(--bg-panel-alt); border: 1px solid var(--linea); color: var(--texto-principal);
    padding: 10px 12px; border-radius: 8px; font-size: 13.5px; outline:none; box-sizing:border-box; margin-bottom: 16px;
}
.ub-btn { width:100%; padding: 12px; border-radius: 9px; border: none; background: var(--acento); color:#0B0F1A; font-weight:700; font-family: var(--fuente-display); cursor:pointer; font-size: 14px; }
.ub-btn:hover { opacity:.9; }
.ub-error { color: var(--error); font-size: 12.5px; margin-top: 10px; min-height: 16px; }
</style>
</head>
<body>
<?php require __DIR__ . '/vistas/topbar.php'; ?>
<div class="ub-caja">
    <div class="ub-titulo">🎮 Unirme a una batalla</div>
    <div class="ub-sub">Ingresa el código de 6 dígitos que te dio tu profesor u organizador.</div>

    <input type="text" id="ub-codigo" class="ub-codigo-input" maxlength="6" inputmode="numeric" placeholder="000000">
    <input type="text" id="ub-apodo" class="ub-apodo-input" maxlength="60"
           value="<?= htmlspecialchars($usuario['nombre'] ?? '') ?>" placeholder="Tu apodo en la batalla">

    <button class="ub-btn" onclick="unirse()">Entrar a la sala</button>
    <div class="ub-error" id="ub-error"></div>
</div>

<script>
document.getElementById('ub-codigo').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 6);
});

async function unirse() {
    const codigo = document.getElementById('ub-codigo').value.trim();
    const apodo  = document.getElementById('ub-apodo').value.trim();
    const errBox = document.getElementById('ub-error');
    errBox.textContent = '';

    if (codigo.length !== 6) {
        errBox.textContent = 'El código debe tener 6 dígitos.';
        return;
    }

    try {
        const fd = new FormData();
        fd.append('codigo', codigo);
        fd.append('apodo', apodo);
        const res = await fetch('api/batalla_unirse.php', { method: 'POST', body: fd });

        // Si el servidor no respondió JSON (error 500, redirect a login, 404, etc.)
        // mostramos el texto crudo para poder diagnosticarlo en vez de ocultarlo.
        const textoBruto = await res.text();
        let data;
        try {
            data = JSON.parse(textoBruto);
        } catch (parseErr) {
            console.error('Respuesta no-JSON del servidor (status ' + res.status + '):', textoBruto);
            errBox.textContent = `Error del servidor (HTTP ${res.status}). Revisa la consola para más detalle.`;
            return;
        }

        if (!data.ok) {
            errBox.textContent = data.error || 'No se pudo unir a la sala.';
            return;
        }
        window.location.href = `jugar_batalla.php?codigo=${data.codigo}`;
    } catch (e) {
        console.error('Fallo de red al llamar batalla_unirse.php:', e);
        errBox.textContent = 'Error de conexión. Intenta de nuevo.';
    }
}

document.getElementById('ub-codigo').addEventListener('keydown', e => {
    if (e.key === 'Enter') unirse();
});
</script>
</body>
</html>
