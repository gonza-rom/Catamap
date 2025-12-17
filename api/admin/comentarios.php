<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar comentarios
if($method === 'GET') {
    $estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filtro por estado
    $where_clause = !empty($estado_filter) ? "WHERE c.estado = ?" : "";
    
    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM comentarios c {$where_clause}";
    if(!empty($estado_filter)) {
        $stmt_count = $conexion->prepare($sql_count);
        $stmt_count->bind_param("s", $estado_filter);
        $stmt_count->execute();
        $total_result = $stmt_count->get_result();
    } else {
        $total_result = $conexion->query($sql_count);
    }
    $total = $total_result->fetch_assoc()['total'];
    
    // Obtener comentarios
    $sql = "SELECT c.*, 
            u.nombre as usuario_nombre, u.email as usuario_email,
            lt.nombre as lugar_nombre
            FROM comentarios c
            LEFT JOIN usuarios u ON c.id_usuario = u.id
            LEFT JOIN lugares_turisticos lt ON c.id_lugar = lt.id
            {$where_clause}
            ORDER BY c.fecha_creacion DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($sql);
    if(!empty($estado_filter)) {
        $stmt->bind_param("sii", $estado_filter, $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comentarios = [];
    while($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $comentarios,
        'pagination' => [
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    exit();
}

// PUT: Aprobar/rechazar comentario
if($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de comentario requerido"]);
        exit();
    }
    
    if(empty($data['estado'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Estado requerido"]);
        exit();
    }
    
    $id_comentario = intval($data['id']);
    $nuevo_estado = $data['estado'];
    
    // Validar estados permitidos (deben coincidir con el ENUM de la BD)
    $estados_validos = ['aprobado', 'pendiente', 'rechazado'];
    if(!in_array($nuevo_estado, $estados_validos)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Estado inválido"]);
        exit();
    }
    
    // Actualizar directamente el campo estado
    $sql = "UPDATE comentarios SET estado = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $id_comentario);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Comentario actualizado correctamente"]);
        } else {
            // Verificar si el comentario existe pero ya tenía ese estado
            $check_sql = "SELECT estado FROM comentarios WHERE id = ?";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bind_param("i", $id_comentario);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows > 0) {
                echo json_encode(["success" => true, "message" => "El comentario ya tenía ese estado"]);
            } else {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Comentario no encontrado"]);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al actualizar comentario: " . $stmt->error]);
    }
    exit();
}

// DELETE: Eliminar comentario
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de comentario requerido"]);
        exit();
    }
    
    $id_comentario = intval($data['id']);
    
    $sql = "DELETE FROM comentarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_comentario);
    
    if($stmt->execute()) {
        if($stmt->affected_rows > 0) {
            echo json_encode(["success" => true, "message" => "Comentario eliminado correctamente"]);
        } else {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Comentario no encontrado"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar comentario: " . $stmt->error]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>