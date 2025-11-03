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

// Validar archivo
if(!isset($_FILES['avatar'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No se recibió ningún archivo"]);
    exit();
}

$file = $_FILES['avatar'];

// Validar tipo
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
if(!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Tipo de archivo no permitido. Solo JPG, PNG y GIF"]);
    exit();
}

// Validar tamaño (5MB)
if($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "El archivo es demasiado grande. Máximo 5MB"]);
    exit();
}

// Crear directorio si no existe
$upload_dir = '../uploads/';
if(!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generar nombre único
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
$filepath = $upload_dir . $filename;

// Eliminar imagen anterior si existe
$sql_old = "SELECT imagen_perfil FROM usuarios WHERE id = ?";
$stmt_old = $conexion->prepare($sql_old);
$stmt_old->bind_param("i", $id_usuario);
$stmt_old->execute();
$result_old = $stmt_old->get_result();
if($row_old = $result_old->fetch_assoc()) {
    if(!empty($row_old['imagen_perfil']) && file_exists($upload_dir . $row_old['imagen_perfil'])) {
        unlink($upload_dir . $row_old['imagen_perfil']);
    }
}

// Mover archivo
if(move_uploaded_file($file['tmp_name'], $filepath)) {
    $sql = "UPDATE usuarios SET imagen_perfil = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $filename, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "message" => "Avatar actualizado",
            "avatar_url" => $upload_dir . $filename . '?v=' . time()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al actualizar en base de datos"]);
    }
} else {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error al subir el archivo"]);
}
?>