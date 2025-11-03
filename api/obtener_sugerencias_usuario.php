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
    $sql = "SELECT ls.*, d.nombre as departamento_nombre
            FROM lugares_sugeridos ls
            LEFT JOIN departamentos d ON ls.id_departamento = d.id
            WHERE ls.id_usuario = ?
            ORDER BY ls.fecha_sugerido DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sugerencias = [];
    while($row = $result->fetch_assoc()) {
        $sugerencias[] = [
            'id' => intval($row['id']),
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'direccion' => $row['direccion'],
            'imagen' => $row['imagen'],
            'estado' => $row['estado'],
            'motivo_rechazo' => $row['motivo_rechazo'] ?? null,
            'departamento_nombre' => $row['departamento_nombre'],
            'fecha_sugerido' => date('d/m/Y', strtotime($row['fecha_sugerido']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'sugerencias' => $sugerencias
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener sugerencias: ' . $e->getMessage()
    ]);
}
?>