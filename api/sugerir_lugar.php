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

// Validar datos del formulario
$nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;
$id_categoria = isset($_POST['id_categoria']) ? intval($_POST['id_categoria']) : 0;
$id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;

// Validaciones
if(empty($nombre)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "El nombre es requerido"]);
    exit();
}

if(strlen($descripcion) < 50) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "La descripción debe tener al menos 50 caracteres"]);
    exit();
}

if($lat == 0 || $lng == 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Debes marcar la ubicación en el mapa"]);
    exit();
}

if($id_categoria <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Selecciona una categoría"]);
    exit();
}

if($id_departamento <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Selecciona un departamento"]);
    exit();
}

// Manejo de imagen
$imagen_nombre = null;
if(isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['imagen'];
    
    // Validar tipo
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    if(!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Tipo de archivo no permitido"]);
        exit();
    }
    
    // Validar tamaño (5MB)
    if($file['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "La imagen no puede superar los 5MB"]);
        exit();
    }
    
    // Crear directorio si no existe
    $upload_dir = '../uploads/sugerencias/';
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $imagen_nombre = 'sugerencia_' . $id_usuario . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $imagen_nombre;
    
    // Mover archivo
    if(!move_uploaded_file($file['tmp_name'], $filepath)) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al subir la imagen"]);
        exit();
    }
}

try {
    // Insertar sugerencia
    $sql = "INSERT INTO lugares_sugeridos 
            (id_usuario, nombre, descripcion, direccion, lat, lng, id_categoria, id_departamento, imagen, estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isssddiis", 
        $id_usuario, 
        $nombre, 
        $descripcion, 
        $direccion, 
        $lat, 
        $lng, 
        $id_categoria, 
        $id_departamento, 
        $imagen_nombre
    );

    if($stmt->execute()) {
        $id_sugerencia = $stmt->insert_id;
        
        echo json_encode([
            "success" => true,
            "message" => "Sugerencia enviada exitosamente. Será revisada pronto.",
            "id" => $id_sugerencia
        ]);
    } else {
        throw new Exception("Error al guardar la sugerencia");
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false, 
        "message" => $e->getMessage()
    ]);
}
?>