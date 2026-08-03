<?php
/**
 * logout.php
 * Cierra la sesión nativa de PHP por completo.
 */

require_once __DIR__ . '/config/constantes.php';
iniciarSesionSegura();

$_SESSION = [];
session_destroy();

header('Location: /proyecto/login.php');
exit;
