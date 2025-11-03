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

if(!$data || !isset($data['old_password']) || !isset($data['new_password'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit();
}

$old_password = $data['old_password'];
$new_password = $data['new_password'];

// Validar nueva contraseña
if(strlen($new_password) < 6) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "La nueva contraseña debe tener al menos 6 caracteres"]);
    exit();
}

try {
    // Verificar contraseña actual
    $sql = "SELECT password FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
        exit();
    }
    
    $row = $result->fetch_assoc();
    
    // Verificar contraseña actual
    if(!password_verify($old_password, $row['password'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "La contraseña actual es incorrecta"]);
        exit();
    }
    
    // Actualizar contraseña
    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $sql_update = "UPDATE usuarios SET password = ? WHERE id = ?";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bind_param("si", $new_password_hash, $id_usuario);
    
    if($stmt_update->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar la contraseña');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>