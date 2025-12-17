<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar categorías
if($method === 'GET') {
    $sql = "SELECT c.*, 
            COUNT(lt.id) as total_lugares
            FROM categorias c
            LEFT JOIN lugares_turisticos lt ON c.id_categoria = lt.id_categoria
            GROUP BY c.id_categoria
            ORDER BY c.nombre ASC";
    
    $result = $conexion->query($sql);
    
    $categorias = [];
    while($row = $result->fetch_assoc()) {
        // Convertir el icono a UTF-8 si es necesario
        if(!empty($row['icono'])) {
            $row['icono'] = mb_convert_encoding($row['icono'], 'UTF-8', 'UTF-8');
        }
        $categorias[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $categorias
    ], JSON_UNESCAPED_UNICODE); // Importante para emojis
    exit();
}

// POST: Crear categoría
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['nombre'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Nombre de categoría requerido"]);
        exit();
    }
    
    $nombre = trim($data['nombre']);
    $descripcion = isset($data['descripcion']) ? trim($data['descripcion']) : '';
    $icono = isset($data['icono']) ? trim($data['icono']) : '';
    
    $sql = "INSERT INTO categorias (nombre, descripcion, icono) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $nombre, $descripcion, $icono);
    
    if($stmt->execute()) {
        echo json_encode([
            "success" => true, 
            "message" => "Categoría creada correctamente",
            "id" => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al crear categoría"]);
    }
    exit();
}

// PUT: Editar categoría
if($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de categoría requerido"]);
        exit();
    }
    
    $id_categoria = intval($data['id']);
    
    $updates = [];
    $params = [];
    $types = '';
    
    if(isset($data['nombre'])) {
        $updates[] = "nombre = ?";
        $params[] = $data['nombre'];
        $types .= 's';
    }
    
    if(isset($data['descripcion'])) {
        $updates[] = "descripcion = ?";
        $params[] = $data['descripcion'];
        $types .= 's';
    }
    
    if(isset($data['icono'])) {
        $updates[] = "icono = ?";
        $params[] = $data['icono'];
        $types .= 's';
    }
    
    if(empty($updates)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No hay campos para actualizar"]);
        exit();
    }
    
    $params[] = $id_categoria;
    $types .= 'i';
    
    $sql = "UPDATE categorias SET " . implode(", ", $updates) . " WHERE id_categoria = ?";
    $stmt = $conexion->prepare($sql);
    
    if(!$stmt) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error de preparación SQL: " . $conexion->error]);
        exit();
    }
    
    // Bind dynamic parameters securely
    $bindParams = [];
    $bindParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Categoría actualizada correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "Error al actualizar categoría: " . $stmt->error
        ]);
    }
    exit();
}

// DELETE: Eliminar categoría
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de categoría requerido"]);
        exit();
    }
    
    $id_categoria = intval($data['id']);
    
    // Verificar si hay lugares usando esta categoría
    $sql_check = "SELECT COUNT(*) as total FROM lugares_turisticos WHERE id_categoria = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("i", $id_categoria);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    if($count > 0) {
        http_response_code(400);
        echo json_encode([
            "success" => false, 
            "message" => "No se puede eliminar. Hay {$count} lugares usando esta categoría."
        ]);
        exit();
    }
    
    $sql = "DELETE FROM categorias WHERE id_categoria = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_categoria);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Categoría eliminada correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar categoría"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
