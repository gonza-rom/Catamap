<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';
error_reporting(0); // Suppress warnings to avoid JSON corruption
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

// GET: Listar lugares sugeridos pendientes
// GET: Listar lugares sugeridos pendientes (CORREGIDO)
if($method === 'GET') {
    $estado_filter = isset($_GET['estado']) ? $_GET['estado'] : 'pendiente';
    
    $sql = "SELECT ls.*, 
            u.nombre as usuario_nombre, 
            u.email as usuario_email,
            u.imagen_perfil as usuario_imagen,
            c.nombre as categoria_nombre,
            d.nombre as departamento_nombre
            FROM lugares_sugeridos ls
            LEFT JOIN usuarios u ON ls.id_usuario = u.id
            LEFT JOIN categorias c ON ls.id_categoria = c.id_categoria
            LEFT JOIN departamentos d ON ls.id_departamento = d.id
            WHERE ls.estado = ?
            ORDER BY ls.fecha_sugerido DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $estado_filter);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sugerencias = [];
    while($row = $result->fetch_assoc()) {
        // Asegurar que la ruta de imagen esté completa
        if(!empty($row['imagen'])) {
            $row['imagen_url'] = '../uploads/' . $row['imagen'];
        } else {
            $row['imagen_url'] = '../img/placeholder.webp';
        }
        $sugerencias[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $sugerencias
    ]);
    exit();
}

// POST: Aprobar lugar sugerido (convertir a lugar turístico)
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de sugerencia requerido"]);
        exit();
    }
    
    $id_sugerencia = intval($data['id']);
    
    // Obtener datos de la sugerencia
    $sql_get = "SELECT * FROM lugares_sugeridos WHERE id = ?";
    $stmt = $conexion->prepare($sql_get);
    $stmt->bind_param("i", $id_sugerencia);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Sugerencia no encontrada"]);
        exit();
    }
    
    $sugerencia = $result->fetch_assoc();
    
    $conexion->begin_transaction();
    
    try {
        // Insertar en lugares_turisticos
        $sql_insert = "INSERT INTO lugares_turisticos 
                       (nombre, descripcion, direccion, lat, lng, imagen, 
                        id_categoria, id_departamento, estado)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'aprobado')";
        
        $stmt = $conexion->prepare($sql_insert);
        $stmt->bind_param("sssddsii", 
            $sugerencia['nombre'],
            $sugerencia['descripcion'],
            $sugerencia['direccion'],
            $sugerencia['lat'],
            $sugerencia['lng'],
            $sugerencia['imagen'],
            $sugerencia['id_categoria'],
            $sugerencia['id_departamento']
        );
        $stmt->execute();
        
        // Actualizar estado de la sugerencia
        $sql_update = "UPDATE lugares_sugeridos SET estado = 'aprobado' WHERE id = ?";
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param("i", $id_sugerencia);
        $stmt->execute();
        
        $conexion->commit();
        echo json_encode(["success" => true, "message" => "Lugar aprobado y publicado correctamente"]);
    } catch(Exception $e) {
        $conexion->rollback();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al aprobar lugar: " . $e->getMessage()]);
    }
    exit();
}

// DELETE: Rechazar/eliminar sugerencia
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de sugerencia requerido"]);
        exit();
    }
    
    $id_sugerencia = intval($data['id']);
    $rechazar = isset($data['rechazar']) && $data['rechazar'] === true;
    
    if($rechazar) {
        // Solo marcar como rechazado
        $sql = "UPDATE lugares_sugeridos SET estado = 'rechazado' WHERE id = ?";
    } else {
        // Eliminar completamente
        $sql = "DELETE FROM lugares_sugeridos WHERE id = ?";
    }
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_sugerencia);
    
    if($stmt->execute()) {
        $mensaje = $rechazar ? "Sugerencia rechazada" : "Sugerencia eliminada";
        echo json_encode(["success" => true, "message" => $mensaje]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al procesar sugerencia"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
