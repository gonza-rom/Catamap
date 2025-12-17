<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar lugares turísticos
if($method === 'GET') {
    $estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
    $categoria_filter = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if(!empty($estado_filter)) {
        $where_conditions[] = "lt.estado = ?";
        $params[] = $estado_filter;
        $types .= 's';
    }
    
    if($categoria_filter > 0) {
        $where_conditions[] = "lt.id_categoria = ?";
        $params[] = $categoria_filter;
        $types .= 'i';
    }
    
    if(!empty($busqueda)) {
        $where_conditions[] = "(lt.nombre LIKE ? OR lt.descripcion LIKE ?)";
        $search_term = "%{$busqueda}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM lugares_turisticos lt {$where_clause}";
    $stmt_count = $conexion->prepare($sql_count);
    if(!empty($params)) {
        $bindParams = [];
        $bindParams[] = &$types;
        for ($i = 0; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array([$stmt_count, 'bind_param'], $bindParams);
    }
    $stmt_count->execute();
    $total_result = $stmt_count->get_result();
    $total = $total_result->fetch_assoc()['total'];
    
    // Obtener lugares
    $sql = "SELECT lt.*, 
            c.nombre as categoria_nombre,
            d.nombre as departamento_nombre,
            'Sistema' as creador_nombre,
            (SELECT COUNT(*) FROM favoritos WHERE id_lugar = lt.id) as total_favoritos,
            (SELECT COUNT(*) FROM comentarios WHERE id_lugar = lt.id) as total_comentarios
            FROM lugares_turisticos lt
            LEFT JOIN categorias c ON lt.id_categoria = c.id_categoria
            LEFT JOIN departamentos d ON lt.id_departamento = d.id
            {$where_clause}
            ORDER BY lt.id DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conexion->prepare($sql);
    $bindParams = [];
    $bindParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lugares = [];
    while($row = $result->fetch_assoc()) {
        $lugares[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $lugares,
        'pagination' => [
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    exit();
}

// PUT: Editar lugar
if($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de lugar requerido"]);
        exit();
    }
    
    $id_lugar = intval($data['id']);
    
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
    
    if(isset($data['direccion'])) {
        $updates[] = "direccion = ?";
        $params[] = $data['direccion'];
        $types .= 's';
    }
    
    if(isset($data['estado'])) {
        $estados_validos = ['aprobado', 'pendiente', 'rechazado'];
        if(!in_array($data['estado'], $estados_validos)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Estado inválido"]);
            exit();
        }
        $updates[] = "estado = ?";
        $params[] = $data['estado'];
        $types .= 's';
    }
    
    if(isset($data['id_categoria'])) {
        $updates[] = "id_categoria = ?";
        $params[] = intval($data['id_categoria']);
        $types .= 'i';
    }
    
    if(isset($data['id_departamento'])) {
        $updates[] = "id_departamento = ?";
        $params[] = intval($data['id_departamento']);
        $types .= 'i';
    }
    
    if(empty($updates)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No hay campos para actualizar"]);
        exit();
    }
    
    $params[] = $id_lugar;
    $types .= 'i';
    
    $sql = "UPDATE lugares_turisticos SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    
    // Bind dynamic parameters securely
    $bindParams = [];
    $bindParams[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bindParams[] = &$params[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bindParams);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Lugar actualizado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al actualizar lugar"]);
    }
    exit();
}

// DELETE: Eliminar lugar
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de lugar requerido"]);
        exit();
    }
    
    $id_lugar = intval($data['id']);
    
    // Eliminar también favoritos y comentarios relacionados
    $conexion->begin_transaction();
    
    try {
        $sql_favoritos = "DELETE FROM favoritos WHERE id_lugar = ?";
        $stmt = $conexion->prepare($sql_favoritos);
        $stmt->bind_param("i", $id_lugar);
        $stmt->execute();
        
        $sql_comentarios = "DELETE FROM comentarios WHERE id_lugar = ?";
        $stmt = $conexion->prepare($sql_comentarios);
        $stmt->bind_param("i", $id_lugar);
        $stmt->execute();
        
        $sql_lugar = "DELETE FROM lugares_turisticos WHERE id = ?";
        $stmt = $conexion->prepare($sql_lugar);
        $stmt->bind_param("i", $id_lugar);
        $stmt->execute();
        
        $conexion->commit();
        echo json_encode(["success" => true, "message" => "Lugar eliminado correctamente"]);
    } catch(Exception $e) {
        $conexion->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar lugar"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
