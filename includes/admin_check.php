<?php
// Verificar que el usuario esté autenticado y sea administrador
// Este archivo retorna el objeto Usuario si todo está correcto

session_start();

if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header("Location: ../index.php");
    exit();
}

include_once '../config/database.php';
include_once '../classes/Usuario.php';

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    die("Error de conexión a la base de datos");
}

$usuario = new Usuario($db);

// Verificar token y cargar datos del usuario
if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Verificar que sea administrador
if(!$usuario->esAdmin()) {
    header("Location: ../index.php?error=acceso_denegado");
    exit();
}

// Si llegamos aquí, el usuario es admin y está autenticado
// Retornar el objeto usuario para que esté disponible en el archivo que incluye este
return $usuario;
?>
