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

// Obtener datos del POST
$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !isset($data['id']) || !isset($data['calificacion']) || !isset($data['comentario'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit();
}

$id_comentario = intval($data['id']);
$calificacion = intval($data['calificacion']);
$comentario = trim($data['comentario']);

// Validar calificación
if($calificacion < 1 || $calificacion > 5) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Calificación debe ser entre 1 y 5"]);
    exit();
}

// Validar comentario
if(strlen($comentario) < 10) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "El comentario debe tener al menos 10 caracteres"]);
    exit();
}

try {
    // Verificar que el comentario pertenece al usuario
    $sql_check = "SELECT id FROM comentarios WHERE id = ? AND id_usuario = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_comentario, $id_usuario);
    $stmt_check->execute();
    
    if($stmt_check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Comentario no encontrado"]);
        exit();
    }
    
    // Actualizar comentario
    $sql = "UPDATE comentarios 
            SET calificacion = ?, comentario = ?, fecha_modificacion = NOW() 
            WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isii", $calificacion, $comentario, $id_comentario, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Reseña actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>