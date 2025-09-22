<?php
// admin_logout.php - Cerrar sesión administrativa
require_once __DIR__ . '/config.php';
session_start();

// Destruir sesión
session_destroy();

// Redirigir al login
header('Location: admin_login.php');
exit;
