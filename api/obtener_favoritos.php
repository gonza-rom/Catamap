<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/usuario.php';
include_once '../includes/conexion.php';

// Verificar autenticación
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "No autenticado"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error de conexión"));
    exit();
}

$usuario = new Usuario($db);
if(!$usuario->verificarToken($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Token inválido"));
    exit();
}

$id_usuario = $usuario->id;

// Obtener favoritos con información del lugar
$sql = "SELECT 
            f.id,
            f.id_lugar,
            f.fecha_agregado,
            l.nombre,
            l.descripcion,
            l.imagen,
            l.lat,
            l.lng,
            c.nombre as categoria
        FROM favoritos f
        INNER JOIN lugares_turisticos l ON f.id_lugar = l.id
        LEFT JOIN categorias c ON l.id_categoria = c.id
        WHERE f.id_usuario = ?
        ORDER BY f.fecha_agregado DESC";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

$favoritos = array();
while($row = $result->fetch_assoc()) {
    $favoritos[] = array(
        "id" => $row['id'],
        "id_lugar" => $row['id_lugar'],
        "nombre" => $row['nombre'],
        "descripcion" => $row['descripcion'],
        "imagen" => $row['imagen'],
        "categoria" => $row['categoria'] ?? 'Sin categoría',
        "lat" => floatval($row['lat']),
        "lng" => floatval($row['lng']),
        "fecha_agregado" => date('d/m/Y', strtotime($row['fecha_agregado']))
    );
}

echo json_encode(array(
    "success" => true,
    "favoritos" => $favoritos,
    "total" => count($favoritos)
));
?>