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

// Obtener datos del POST (puede venir como JSON o form data)
$id_comentario = 0;

if(isset($_POST['id'])) {
    $id_comentario = intval($_POST['id']);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if($data && isset($data['id'])) {
        $id_comentario = intval($data['id']);
    }
}

if($id_comentario <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID requerido"]);
    exit();
}

try {
    $sql = "DELETE FROM comentarios WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_comentario, $id_usuario);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Reseña eliminada correctamente'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Comentario no encontrado"]);
        }
    } else {
        throw new Exception('Error al eliminar');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>