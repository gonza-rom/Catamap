<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar usuarios con filtros
if($method === 'GET') {
    $rol_filter = isset($_GET['rol']) ? $_GET['rol'] : '';
    $estado_filter = isset($_GET['estado']) ? $_GET['estado'] : '';
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;
    
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if(!empty($rol_filter)) {
        $where_conditions[] = "rol = ?";
        $params[] = $rol_filter;
        $types .= 's';
    }
    
    if(!empty($estado_filter)) {
        $where_conditions[] = "estado = ?";
        $params[] = $estado_filter;
        $types .= 's';
    }
    
    if(!empty($busqueda)) {
        $where_conditions[] = "(nombre LIKE ? OR email LIKE ?)";
        $search_term = "%{$busqueda}%";
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM usuarios {$where_clause}";
    $stmt_count = $conexion->prepare($sql_count);
    if(!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_result = $stmt_count->get_result();
    $total = $total_result->fetch_assoc()['total'];
    
    // Obtener usuarios
    $sql = "SELECT id, nombre, email, rol, tipo_usuario, estado, imagen_perfil, 
            fecha_registro, ultimo_acceso, telefono
            FROM usuarios 
            {$where_clause}
            ORDER BY fecha_registro DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while($row = $result->fetch_assoc()) {
        $usuarios[] = [
            'id' => intval($row['id']),
            'nombre' => $row['nombre'],
            'email' => $row['email'],
            'rol' => $row['rol'],
            'tipo_usuario' => $row['tipo_usuario'],
            'estado' => $row['estado'],
            'imagen_perfil' => $row['imagen_perfil'],
            'fecha_registro' => $row['fecha_registro'],
            'ultimo_acceso' => $row['ultimo_acceso'],
            'telefono' => $row['telefono']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $usuarios,
        'pagination' => [
            'total' => intval($total),
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
    exit();
}

// PUT: Editar usuario
if($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de usuario requerido"]);
        exit();
    }
    
    $id_usuario = intval($data['id']);
    
    // Construir query dinámicamente según campos enviados
    $updates = [];
    $params = [];
    $types = '';
    
    if(isset($data['nombre'])) {
        $updates[] = "nombre = ?";
        $params[] = $data['nombre'];
        $types .= 's';
    }
    
    if(isset($data['email'])) {
        $updates[] = "email = ?";
        $params[] = $data['email'];
        $types .= 's';
    }
    
    if(isset($data['rol'])) {
        $roles_validos = ['usuario', 'emprendedor', 'admin'];
        if(!in_array($data['rol'], $roles_validos)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Rol inválido"]);
            exit();
        }
        $updates[] = "rol = ?";
        $updates[] = "tipo_usuario = ?";
        $params[] = $data['rol'];
        $params[] = $data['rol'];
        $types .= 'ss';
    }
    
    if(isset($data['estado'])) {
        $estados_validos = ['activo', 'suspendido', 'inactivo'];
        if(!in_array($data['estado'], $estados_validos)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Estado inválido"]);
            exit();
        }
        $updates[] = "estado = ?";
        $params[] = $data['estado'];
        $types .= 's';
    }
    
    if(isset($data['telefono'])) {
        $updates[] = "telefono = ?";
        $params[] = $data['telefono'];
        $types .= 's';
    }
    
    if(empty($updates)) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No hay campos para actualizar"]);
        exit();
    }
    
    $params[] = $id_usuario;
    $types .= 'i';
    
    $sql = "UPDATE usuarios SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Usuario actualizado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al actualizar usuario"]);
    }
    exit();
}

// DELETE: Eliminar usuario
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de usuario requerido"]);
        exit();
    }
    
    $id_usuario = intval($data['id']);
    
    // No permitir eliminar al propio usuario admin
    if($id_usuario === $usuario->id) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No puedes eliminarte a ti mismo"]);
        exit();
    }
    
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Usuario eliminado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar usuario"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
