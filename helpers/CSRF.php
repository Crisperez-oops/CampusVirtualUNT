<?php
/**
 * helpers/CSRF.php
 * Protección contra Cross-Site Request Forgery
 */
function generarTokenCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function campoCSRF(): string {
    return '<input type="hidden" name="csrf_token" value="' . generarTokenCSRF() . '">';
}

function validarCSRF(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'error' => 'Token CSRF inválido. Recarga la página.']));
    }
}
