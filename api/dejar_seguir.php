<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/usuario.php';
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

$id_seguidor = $usuario->id;

// Obtener datos del POST
$data = json_decode(file_get_contents("php://input"), true);

if(!$data || !isset($data['id_usuario'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID de usuario requerido"]);
    exit();
}

$id_seguido = intval($data['id_usuario']);

try {
    $sql = "DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_seguidor, $id_seguido);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Has dejado de seguir a este usuario'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "No seguías a este usuario"]);
        }
    } else {
        throw new Exception('Error al dejar de seguir');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>