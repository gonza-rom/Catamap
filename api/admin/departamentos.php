<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar departamentos
if($method === 'GET') {
    $sql = "SELECT d.*, 
            COUNT(lt.id) as total_lugares
            FROM departamentos d
            LEFT JOIN lugares_turisticos lt ON d.id = lt.id_departamento
            GROUP BY d.id
            ORDER BY d.nombre ASC";
    
    $result = $conexion->query($sql);
    
    $departamentos = [];
    while($row = $result->fetch_assoc()) {
        $departamentos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $departamentos
    ]);
    exit();
}

// POST: Crear departamento
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['nombre'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nombre de departamento requerido"]);
        exit();
    }
    
    $nombre = trim($data['nombre']);
    
    $sql = "INSERT INTO departamentos (nombre) VALUES (?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $nombre);
    
    if($stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "Departamento creado correctamente",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al crear departamento"]);
    }
    exit();
}

// PUT: Editar departamento
if($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id']) || empty($data['nombre'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID y nombre de departamento requeridos"]);
        exit();
    }
    
    $id_departamento = intval($data['id']);
    $nombre = trim($data['nombre']);
    
    $sql = "UPDATE departamentos SET nombre = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nombre, $id_departamento);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Departamento actualizado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al actualizar departamento"]);
    }
    exit();
}

// DELETE: Eliminar departamento
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de departamento requerido"]);
        exit();
    }
    
    $id_departamento = intval($data['id']);
    
    // Verificar si hay lugares usando este departamento
    $sql_check = "SELECT COUNT(*) as total FROM lugares_turisticos WHERE id_departamento = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("i", $id_departamento);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    if($count > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "No se puede eliminar. Hay {$count} lugares en este departamento."
        ]);
        exit();
    }
    
    $sql = "DELETE FROM departamentos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_departamento);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Departamento eliminado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar departamento"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
