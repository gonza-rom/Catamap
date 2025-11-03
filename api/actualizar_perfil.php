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

// Verificar si es una actualización de imagen
if(isset($_FILES['imagen_perfil'])) {
    $file = $_FILES['imagen_perfil'];
    
    // Validar tipo
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if(!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Tipo de archivo no permitido"]);
        exit();
    }
    
    // Validar tamaño (2MB)
    if($file['size'] > 2 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "El archivo es demasiado grande. Máximo 2MB"]);
        exit();
    }
    
    // Crear directorio si no existe
    $upload_dir = '../uploads/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
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
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'perfil_' . $id_usuario . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Mover archivo
    if(move_uploaded_file($file['tmp_name'], $filepath)) {
        $sql = "UPDATE usuarios SET imagen_perfil = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $filename, $id_usuario);
        
        if($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Imagen actualizada",
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
    exit();
}

// Si no es imagen, procesar datos JSON
$data = json_decode(file_get_contents("php://input"), true);

if(!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos inválidos"]);
    exit();
}

try {
    // Actualizar nombre
    if(isset($data['nombre'])) {
        $nombre = trim($data['nombre']);
        if(empty($nombre)) {
            throw new Exception("El nombre no puede estar vacío");
        }
        
        $sql = "UPDATE usuarios SET nombre = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $nombre, $id_usuario);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Nombre actualizado correctamente',
            'nombre' => $nombre
        ]);
        exit();
    }
    
    // Actualizar email
    if(isset($data['email'])) {
        $email = trim($data['email']);
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email inválido");
        }
        
        // Verificar si el email ya existe
        $sql_check = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
        $stmt_check = $conexion->prepare($sql_check);
        $stmt_check->bind_param("si", $email, $id_usuario);
        $stmt_check->execute();
        
        if($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("El email ya está registrado");
        }
        
        $sql = "UPDATE usuarios SET email = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $email, $id_usuario);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Email actualizado correctamente',
            'email' => $email
        ]);
        exit();
    }
    
    // Actualizar teléfono
    if(isset($data['telefono'])) {
        $telefono = trim($data['telefono']);
        
        $sql = "UPDATE usuarios SET telefono = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $telefono, $id_usuario);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Teléfono actualizado correctamente',
            'telefono' => $telefono
        ]);
        exit();
    }
    
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No se especificó qué actualizar"]);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>