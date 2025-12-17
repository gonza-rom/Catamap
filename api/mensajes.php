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
$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener conversaciones o mensajes
if($method === 'GET') {
    if(isset($_GET['conversacion_con'])) {
        // Obtener mensajes de una conversación específica
        $id_otro_usuario = intval($_GET['conversacion_con']);
        
        $sql = "SELECT m.*, 
                       u_rem.nombre as remitente_nombre, u_rem.imagen_perfil as remitente_imagen,
                       u_dest.nombre as destinatario_nombre, u_dest.imagen_perfil as destinatario_imagen
                FROM mensajes m
                INNER JOIN usuarios u_rem ON m.id_remitente = u_rem.id
                INNER JOIN usuarios u_dest ON m.id_destinatario = u_dest.id
                WHERE (m.id_remitente = ? AND m.id_destinatario = ?)
                   OR (m.id_remitente = ? AND m.id_destinatario = ?)
                ORDER BY m.fecha_envio ASC";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiii", $id_usuario, $id_otro_usuario, $id_otro_usuario, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $mensajes = [];
        while($row = $result->fetch_assoc()) {
            $mensajes[] = [
                'id' => intval($row['id']),
                'id_remitente' => intval($row['id_remitente']),
                'id_destinatario' => intval($row['id_destinatario']),
                'mensaje' => $row['mensaje'],
                'leido' => intval($row['leido']),
                'fecha_envio' => $row['fecha_envio'],
                'remitente_nombre' => $row['remitente_nombre'],
                'remitente_imagen' => $row['remitente_imagen'],
                'es_mio' => ($row['id_remitente'] == $id_usuario)
            ];
        }
        
        // Marcar mensajes como leídos
        $sql_update = "UPDATE mensajes SET leido = 1 
                       WHERE id_destinatario = ? AND id_remitente = ? AND leido = 0";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("ii", $id_usuario, $id_otro_usuario);
        $stmt_update->execute();
        
        echo json_encode([
            'success' => true,
            'mensajes' => $mensajes
        ]);
    } else if(isset($_GET['count'])) {
        // Obtener solo conteo de no leídos
        $sql = "SELECT COUNT(*) as total FROM mensajes WHERE id_destinatario = ? AND leido = 0";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'total_no_leidos' => intval($row['total'])
        ]);
    } else {
        // Obtener lista de conversaciones
        $sql = "SELECT DISTINCT
                    CASE 
                        WHEN m.id_remitente = ? THEN m.id_destinatario
                        ELSE m.id_remitente
                    END as id_otro_usuario,
                    u.nombre, u.imagen_perfil,
                    (SELECT COUNT(*) FROM mensajes 
                     WHERE id_destinatario = ? AND id_remitente = u.id AND leido = 0) as no_leidos,
                    (SELECT mensaje FROM mensajes m2 
                     WHERE (m2.id_remitente = ? AND m2.id_destinatario = u.id)
                        OR (m2.id_remitente = u.id AND m2.id_destinatario = ?)
                     ORDER BY m2.fecha_envio DESC LIMIT 1) as ultimo_mensaje,
                    (SELECT fecha_envio FROM mensajes m2 
                     WHERE (m2.id_remitente = ? AND m2.id_destinatario = u.id)
                        OR (m2.id_remitente = u.id AND m2.id_destinatario = ?)
                     ORDER BY m2.fecha_envio DESC LIMIT 1) as fecha_ultimo
                FROM mensajes m
                INNER JOIN usuarios u ON u.id = CASE 
                    WHEN m.id_remitente = ? THEN m.id_destinatario
                    ELSE m.id_remitente
                END
                WHERE m.id_remitente = ? OR m.id_destinatario = ?
                ORDER BY fecha_ultimo DESC";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiiiiiiii", $id_usuario, $id_usuario, $id_usuario, $id_usuario, 
                          $id_usuario, $id_usuario, $id_usuario, $id_usuario, $id_usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversaciones = [];
        while($row = $result->fetch_assoc()) {
            $conversaciones[] = [
                'id_otro_usuario' => intval($row['id_otro_usuario']),
                'nombre' => $row['nombre'],
                'imagen_perfil' => $row['imagen_perfil'],
                'no_leidos' => intval($row['no_leidos']),
                'ultimo_mensaje' => $row['ultimo_mensaje'],
                'fecha_ultimo' => $row['fecha_ultimo']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'conversaciones' => $conversaciones
        ]);
    }
    exit();
}

// POST: Enviar mensaje
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id_destinatario']) || empty($data['mensaje'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Datos incompletos"]);
        exit();
    }
    
    $id_destinatario = intval($data['id_destinatario']);
    $mensaje = trim($data['mensaje']);
    
    if(strlen($mensaje) < 1 || strlen($mensaje) > 1000) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Mensaje debe tener entre 1 y 1000 caracteres"]);
        exit();
    }
    
    // No puedes enviarte mensajes a ti mismo
    if($id_usuario === $id_destinatario) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "No puedes enviarte mensajes a ti mismo"]);
        exit();
    }
    
    $sql = "INSERT INTO mensajes (id_remitente, id_destinatario, mensaje) VALUES (?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iis", $id_usuario, $id_destinatario, $mensaje);
    
    if($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado',
            'id' => $stmt->insert_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al enviar mensaje"]);
    }
    exit();
}

// DELETE: Eliminar conversación completa
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if(empty($data['id_otro_usuario'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "ID de usuario requerido"]);
        exit();
    }
    
    $id_otro_usuario = intval($data['id_otro_usuario']);
    
    $sql = "DELETE FROM mensajes 
            WHERE (id_remitente = ? AND id_destinatario = ?)
               OR (id_remitente = ? AND id_destinatario = ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiii", $id_usuario, $id_otro_usuario, $id_otro_usuario, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Conversación eliminada'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al eliminar conversación"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>