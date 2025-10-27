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
$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener comentarios de un lugar
if($method === 'GET') {
    $id_lugar = isset($_GET['id_lugar']) ? intval($_GET['id_lugar']) : 0;
    
    if($id_lugar <= 0) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "ID de lugar inválido"));
        exit();
    }
    
    $sql = "SELECT c.*, u.nombre as usuario_nombre, u.imagen_perfil
            FROM comentarios c
            INNER JOIN usuarios u ON c.id_usuario = u.id
            WHERE c.id_lugar = ? AND c.aprobado = 1
            ORDER BY c.fecha_creacion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_lugar);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comentarios = [];
    while($row = $result->fetch_assoc()) {
        $comentarios[] = [
            'id' => intval($row['id']),
            'usuario_nombre' => $row['usuario_nombre'],
            'usuario_imagen' => $row['imagen_perfil'],
            'calificacion' => intval($row['calificacion']),
            'comentario' => $row['comentario'],
            'fecha_creacion' => $row['fecha_creacion']
        ];
    }
    
    // Obtener promedio
    $sql_prom = "SELECT AVG(calificacion) as promedio, COUNT(*) as total
                 FROM comentarios
                 WHERE id_lugar = ? AND aprobado = 1";
    $stmt_prom = $conexion->prepare($sql_prom);
    $stmt_prom->bind_param("i", $id_lugar);
    $stmt_prom->execute();
    $result_prom = $stmt_prom->get_result()->fetch_assoc();
    
    echo json_encode(array(
        "success" => true,
        "data" => $comentarios,
        "promedio" => round($result_prom['promedio'], 1),
        "total" => intval($result_prom['total'])
    ));
    exit();
}

// POST: Crear nuevo comentario
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->id_lugar) || empty($data->calificacion) || empty($data->comentario)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Datos incompletos"));
        exit();
    }
    
    $id_lugar = intval($data->id_lugar);
    $calificacion = intval($data->calificacion);
    $comentario = trim($data->comentario);
    
    // Validar calificación
    if($calificacion < 1 || $calificacion > 5) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Calificación debe ser entre 1 y 5"));
        exit();
    }
    
    // Validar comentario
    if(strlen($comentario) < 10) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El comentario debe tener al menos 10 caracteres"));
        exit();
    }
    
    if(strlen($comentario) > 1000) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "El comentario no puede exceder 1000 caracteres"));
        exit();
    }
    
    // Verificar si el lugar existe
    $sql_check_lugar = "SELECT id FROM lugares_turisticos WHERE id = ?";
    $stmt_check = $conexion->prepare($sql_check_lugar);
    $stmt_check->bind_param("i", $id_lugar);
    $stmt_check->execute();
    if($stmt_check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(array("success" => false, "message" => "Lugar no encontrado"));
        exit();
    }
    
    // Verificar si el usuario ya comentó este lugar
    $sql_check = "SELECT id FROM comentarios WHERE id_usuario = ? AND id_lugar = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        // Actualizar comentario existente
        $sql_update = "UPDATE comentarios 
                       SET calificacion = ?, comentario = ?, aprobado = 1, fecha_modificacion = NOW()
                       WHERE id_usuario = ? AND id_lugar = ?";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param("isii", $calificacion, $comentario, $id_usuario, $id_lugar);
        
        if($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "¡Tu opinión ha sido actualizada correctamente!"
            ));
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Error al actualizar la opinión"
            ));
        }
    } else {
        // Insertar nuevo comentario (AUTO-APROBADO para desarrollo)
        $sql_insert = "INSERT INTO comentarios (id_lugar, id_usuario, calificacion, comentario, aprobado) 
                       VALUES (?, ?, ?, ?, 1)";
        $stmt = $conexion->prepare($sql_insert);
        $stmt->bind_param("iiis", $id_lugar, $id_usuario, $calificacion, $comentario);
        
        if($stmt->execute()) {
            echo json_encode(array(
                "success" => true,
                "message" => "¡Tu opinión ha sido publicada correctamente!",
                "id" => $stmt->insert_id
            ));
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Error al guardar la opinión"
            ));
        }
    }
    exit();
}

// DELETE: Eliminar comentario (solo el propio)
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->id)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "ID de comentario requerido"));
        exit();
    }
    
    $id_comentario = intval($data->id);
    
    $sql = "DELETE FROM comentarios WHERE id = ? AND id_usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_comentario, $id_usuario);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            echo json_encode(array(
                "success" => true,
                "message" => "Comentario eliminado"
            ));
        } else {
            http_response_code(404);
            echo json_encode(array(
                "success" => false,
                "message" => "Comentario no encontrado o no tienes permiso para eliminarlo"
            ));
        }
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Error al eliminar comentario"
        ));
    }
    exit();
}

http_response_code(405);
echo json_encode(array("success" => false, "message" => "Método no permitido"));
?>