<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/Usuario.php';
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
$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener favoritos del usuario
if($method === 'GET') {
    $sql = "SELECT f.id, f.id_lugar, f.fecha_agregado,
                   l.nombre, l.descripcion, l.direccion, l.lat, l.lng, l.imagen,
                   c.nombre AS categoria, c.icono,
                   d.nombre AS departamento
            FROM favoritos f
            INNER JOIN lugares_turisticos l ON f.id_lugar = l.id
            LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
            LEFT JOIN departamentos d ON l.id_departamento = d.id
            WHERE f.id_usuario = ?
            ORDER BY f.fecha_agregado DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $favoritos = [];
    while($row = $result->fetch_assoc()) {
        $favoritos[] = [
            'id' => intval($row['id']),
            'id_lugar' => intval($row['id_lugar']),
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'direccion' => $row['direccion'],
            'lat' => floatval($row['lat']),
            'lng' => floatval($row['lng']),
            'imagen' => $row['imagen'],
            'categoria' => $row['categoria'],
            'icono' => $row['icono'],
            'departamento' => $row['departamento'],
            'fecha_agregado' => $row['fecha_agregado']
        ];
    }
    
    echo json_encode(array(
        "success" => true,
        "data" => $favoritos
    ));
    exit();
}

// POST: Agregar a favoritos
if($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->id_lugar)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "ID de lugar requerido"));
        exit();
    }
    
    $id_lugar = intval($data->id_lugar);
    
    // Verificar si ya existe
    $sql_check = "SELECT id FROM favoritos WHERE id_usuario = ? AND id_lugar = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        echo json_encode(array(
            "success" => false,
            "message" => "Ya está en favoritos"
        ));
        exit();
    }
    
    // Agregar a favoritos
    $sql_insert = "INSERT INTO favoritos (id_usuario, id_lugar) VALUES (?, ?)";
    $stmt = $conexion->prepare($sql_insert);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Agregado a favoritos",
            "id" => $stmt->insert_id
        ));
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Error al agregar a favoritos"
        ));
    }
    exit();
}

// DELETE: Eliminar de favoritos
if($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->id_lugar)) {
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "ID de lugar requerido"));
        exit();
    }
    
    $id_lugar = intval($data->id_lugar);
    
    $sql = "DELETE FROM favoritos WHERE id_usuario = ? AND id_lugar = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "message" => "Eliminado de favoritos"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Error al eliminar de favoritos"
        ));
    }
    exit();
}

http_response_code(405);
echo json_encode(array("success" => false, "message" => "Método no permitido"));
?>