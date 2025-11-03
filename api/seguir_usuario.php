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

// No puedes seguirte a ti mismo
if($id_seguidor === $id_seguido) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No puedes seguirte a ti mismo"]);
    exit();
}

try {
    // Verificar si ya sigue al usuario
    $sql_check = "SELECT id FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_seguidor, $id_seguido);
    $stmt_check->execute();
    $ya_sigue = $stmt_check->get_result()->num_rows > 0;

    if($ya_sigue) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Ya sigues a este usuario"]);
        exit();
    }

    // Seguir al usuario
    $sql_insert = "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("ii", $id_seguidor, $id_seguido);
    
    if($stmt_insert->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Ahora sigues a este usuario'
        ]);
    } else {
        throw new Exception('Error al seguir');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>