<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/Usuario.php';
include_once '../includes/conexion.php';

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
if(!$usuario->verificarToken($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token inválido"]);
    exit();
}

$id_usuario = $usuario->id;
$id_comentario = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id_comentario <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID inválido"]);
    exit();
}

try {
    $sql = "SELECT * FROM comentarios WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_comentario, $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Comentario no encontrado"]);
        exit();
    }
    
    $comentario = $result->fetch_assoc();
    echo json_encode($comentario);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>