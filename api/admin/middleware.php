<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../../config/database.php';
include_once '../../classes/Usuario.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "No autenticado"]);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error de conexión"]);
    exit();
}

$usuario = new Usuario($db);

// Verificar token
if(!$usuario->verificarToken($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token inválido"]);
    exit();
}

// Verificar que sea administrador
if(!$usuario->esAdmin()) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Acceso denegado. Se requieren permisos de administrador."]);
    exit();
}

// Si llegamos aquí, el usuario es admin y puede continuar
?>
