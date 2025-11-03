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

try {
    // Obtener a quiénes sigo
    $sql_siguiendo = "SELECT u.id, u.nombre, u.email, u.imagen_perfil
                      FROM seguidores s
                      INNER JOIN usuarios u ON s.id_seguido = u.id
                      WHERE s.id_seguidor = ?
                      ORDER BY s.fecha_inicio DESC";
    
    $stmt = $conexion->prepare($sql_siguiendo);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $siguiendo = [];
    while($row = $result->fetch_assoc()) {
        $avatar = !empty($row['imagen_perfil']) 
            ? '../uploads/' . $row['imagen_perfil']
            : 'https://ui-avatars.com/api/?name=' . urlencode($row['nombre']) . '&size=50&background=e67e22&color=fff';
            
        $siguiendo[] = [
            'id' => intval($row['id']),
            'nombre' => $row['nombre'],
            'email' => $row['email'],
            'imagen_perfil' => $avatar
        ];
    }
    
    echo json_encode([
        'success' => true,
        'siguiendo' => $siguiendo
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener siguiendo: ' . $e->getMessage()
    ]);
}
?>