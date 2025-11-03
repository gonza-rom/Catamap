<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/Usuario.php';
include_once '../includes/conexion.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "No autenticado"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error de conexión"));
    exit();
}

$usuario = new Usuario($db);
if(!$usuario->verificarToken($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Token inválido"));
    exit();
}

$id_seguidor = $usuario->id;
$method = $_SERVER['REQUEST_METHOD'];

if($method !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Método no permitido"));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if(empty($data->id_usuario)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "ID de usuario requerido"));
    exit();
}

$id_seguido = intval($data->id_usuario);

// No puedes seguirte a ti mismo
if($id_seguidor === $id_seguido) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "No puedes seguirte a ti mismo"));
    exit();
}

// Verificar que el usuario a seguir existe
$sql_check = "SELECT id FROM usuarios WHERE id = ? AND estado = 'activo'";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id_seguido);
$stmt_check->execute();

if($stmt_check->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "Usuario no encontrado"));
    exit();
}

// Verificar si ya sigue al usuario
$sql_existe = "SELECT id FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
$stmt_existe = $conexion->prepare($sql_existe);
$stmt_existe->bind_param("ii", $id_seguidor, $id_seguido);
$stmt_existe->execute();
$ya_sigue = $stmt_existe->get_result()->num_rows > 0;

if($ya_sigue) {
    // Dejar de seguir
    $sql_delete = "DELETE FROM seguidores WHERE id_seguidor = ? AND id_seguido = ?";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $id_seguidor, $id_seguido);
    
    if($stmt_delete->execute()) {
        echo json_encode(array(
            "success" => true,
            "siguiendo" => false,
            "message" => "Has dejado de seguir a este usuario"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al dejar de seguir"));
    }
} else {
    // Comenzar a seguir
    $sql_insert = "INSERT INTO seguidores (id_seguidor, id_seguido) VALUES (?, ?)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("ii", $id_seguidor, $id_seguido);
    
    if($stmt_insert->execute()) {
        echo json_encode(array(
            "success" => true,
            "siguiendo" => true,
            "message" => "Ahora sigues a este usuario"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al seguir"));
    }
}
?>