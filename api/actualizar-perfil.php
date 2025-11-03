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

// Manejar subida de imagen de perfil
if(isset($_FILES['imagen_perfil'])) {
    $file = $_FILES['imagen_perfil'];
    
    // Validar archivo
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    if(!in_array($file['type'], $allowed_types)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Tipo de archivo no permitido. Solo JPG, PNG y GIF"));
        exit();
    }
    
    if($file['size'] > 2 * 1024 * 1024) { // 2MB
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El archivo es demasiado grande. Máximo 2MB"));
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
            // Asegurar que el archivo existe antes de responder
            clearstatcache(); // Limpiar cache de PHP
            
            echo json_encode(array(
                "success" => true,
                "message" => "Imagen de perfil actualizada",
                "imagen" => $filename,
                "url" => $upload_dir . $filename
            ));
        } else {
            http_response_code(500);
            echo json_encode(array("success" => false, "message" => "Error al actualizar en base de datos"));
        }
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al subir el archivo"));
    }
    exit();
}

// Manejar actualización de datos JSON
$data = json_decode(file_get_contents("php://input"));

if(!$data) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Datos inválidos"));
    exit();
}

// Actualizar nombre
if(isset($data->nombre)) {
    $nombre = trim($data->nombre);
    
    if(empty($nombre)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El nombre no puede estar vacío"));
        exit();
    }
    
    $sql = "UPDATE usuarios SET nombre = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nombre, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Nombre actualizado correctamente"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al actualizar el nombre"));
    }
    exit();
}

// Actualizar email
if(isset($data->email)) {
    $email = trim($data->email);
    
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Email inválido"));
        exit();
    }
    
    // Verificar si el email ya existe
    $sql_check = "SELECT id FROM usuarios WHERE email = ? AND id != ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("si", $email, $id_usuario);
    $stmt_check->execute();
    
    if($stmt_check->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Este email ya está en uso"));
        exit();
    }
    
    $sql = "UPDATE usuarios SET email = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $email, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Email actualizado correctamente"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al actualizar el email"));
    }
    exit();
}

// Actualizar teléfono
if(isset($data->telefono)) {
    $telefono = trim($data->telefono);
    
    $sql = "UPDATE usuarios SET telefono = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $telefono, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Teléfono actualizado correctamente"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al actualizar el teléfono"));
    }
    exit();
}

// Cambiar contraseña
if(isset($data->password_actual) && isset($data->password_nueva)) {
    $password_actual = $data->password_actual;
    $password_nueva = $data->password_nueva;
    
    // Verificar contraseña actual
    $sql_check = "SELECT password FROM usuarios WHERE id = ?";
    $stmt_check = $conexion->prepare($sql_check);
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    $user = $result->fetch_assoc();
    
    if(!password_verify($password_actual, $user['password'])) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "La contraseña actual es incorrecta"));
        exit();
    }
    
    if(strlen($password_nueva) < 6) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "La nueva contraseña debe tener al menos 6 caracteres"));
        exit();
    }
    
    $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
    
    $sql = "UPDATE usuarios SET password = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $password_hash, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Contraseña actualizada correctamente"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al actualizar la contraseña"));
    }
    exit();
}

http_response_code(400);
echo json_encode(array("success" => false, "message" => "No se especificó qué actualizar"));
?>