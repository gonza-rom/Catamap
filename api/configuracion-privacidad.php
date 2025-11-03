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

$id_usuario = $usuario->id;
$data = json_decode(file_get_contents("php://input"));

if(!$data) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos inválidos"));
    exit();
}

$perfil_publico = isset($data->perfil_publico) ? intval($data->perfil_publico) : 1;
$favoritos_publicos = isset($data->favoritos_publicos) ? intval($data->favoritos_publicos) : 1;
$comentarios_publicos = isset($data->comentarios_publicos) ? intval($data->comentarios_publicos) : 1;
$mostrar_estadisticas = isset($data->mostrar_estadisticas) ? intval($data->mostrar_estadisticas) : 1;

// Verificar si ya existe configuración
$sql_check = "SELECT id FROM configuracion_privacidad WHERE id_usuario = ?";
$stmt_check = $conexion->prepare($sql_check);
$stmt_check->bind_param("i", $id_usuario);
$stmt_check->execute();
$existe = $stmt_check->get_result()->num_rows > 0;

if($existe) {
    // Actualizar
    $sql = "UPDATE configuracion_privacidad 
            SET perfil_publico = ?, 
                favoritos_publicos = ?, 
                comentarios_publicos = ?, 
                mostrar_estadisticas = ?
            WHERE id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiii", $perfil_publico, $favoritos_publicos, $comentarios_publicos, $mostrar_estadisticas, $id_usuario);
} else {
    // Insertar
    $sql = "INSERT INTO configuracion_privacidad 
            (id_usuario, perfil_publico, favoritos_publicos, comentarios_publicos, mostrar_estadisticas) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiii", $id_usuario, $perfil_publico, $favoritos_publicos, $comentarios_publicos, $mostrar_estadisticas);
}

if($stmt->execute()) {
    echo json_encode(array(
        "success" => true,
        "message" => "Configuración actualizada correctamente"
    ));
} else {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error al guardar la configuración"));
}
?>