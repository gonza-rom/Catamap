<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/usuario.php';
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

try {
    $sql = "SELECT c.*, l.nombre as lugar_nombre, l.id as lugar_id
            FROM comentarios c
            INNER JOIN lugares_turisticos l ON c.id_lugar = l.id
            WHERE c.id_usuario = ?
            ORDER BY c.fecha_creacion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $resenas = [];
    while($row = $result->fetch_assoc()) {
        $resenas[] = [
            'id' => intval($row['id']),
            'lugar_id' => intval($row['lugar_id']),
            'lugar_nombre' => $row['lugar_nombre'],
            'calificacion' => intval($row['calificacion']),
            'comentario' => $row['comentario'],
            'aprobado' => intval($row['aprobado']),
            'fecha_creacion' => date('d/m/Y H:i', strtotime($row['fecha_creacion']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'resenas' => $resenas
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener reseñas: ' . $e->getMessage()
    ]);
}
?>