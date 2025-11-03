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
$id_lugar = isset($_POST['id_lugar']) ? intval($_POST['id_lugar']) : 0;

if($id_lugar <= 0) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "ID de lugar inválido"));
    exit();
}

// Verificar si el lugar existe
$sql_check_lugar = "SELECT id FROM lugares_turisticos WHERE id = ?";
$stmt_check = $conexion->prepare($sql_check_lugar);
$stmt_check->bind_param("i", $id_lugar);
$stmt_check->execute();

if($stmt_check->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(array("success" => false, "message" => "Lugar no encontrado"));
    exit();
}

// Verificar si ya es favorito
$sql_check_fav = "SELECT id FROM favoritos WHERE id_usuario = ? AND id_lugar = ?";
$stmt_check_fav = $conexion->prepare($sql_check_fav);
$stmt_check_fav->bind_param("ii", $id_usuario, $id_lugar);
$stmt_check_fav->execute();
$es_favorito = $stmt_check_fav->get_result()->num_rows > 0;

if($es_favorito) {
    // Eliminar de favoritos
    $sql = "DELETE FROM favoritos WHERE id_usuario = ? AND id_lugar = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    
    if($stmt->execute()) {
        echo json_encode(array(
            "success" => true,
            "action" => "removed",
            "message" => "Lugar eliminado de favoritos"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al eliminar de favoritos"));
    }
} else {
    // Agregar a favoritos
    $sql = "INSERT INTO favoritos (id_usuario, id_lugar) VALUES (?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $id_lugar);
    
    if($stmt->execute()) {
        // Verificar si es el primer favorito para otorgar insignia
        $sql_count = "SELECT COUNT(*) as total FROM favoritos WHERE id_usuario = ?";
        $stmt_count = $conexion->prepare($sql_count);
        $stmt_count->bind_param("i", $id_usuario);
        $stmt_count->execute();
        $count = $stmt_count->get_result()->fetch_assoc()['total'];
        
        if($count == 1) {
            // Otorgar insignia "Explorador Novato"
            $sql_insignia = "INSERT IGNORE INTO usuarios_insignias (id_usuario, id_insignia) 
                             SELECT ?, id FROM insignias WHERE nombre = 'Explorador Novato'";
            $stmt_insignia = $conexion->prepare($sql_insignia);
            $stmt_insignia->bind_param("i", $id_usuario);
            $stmt_insignia->execute();
        }
        
        echo json_encode(array(
            "success" => true,
            "action" => "added",
            "message" => "Lugar agregado a favoritos"
        ));
    } else {
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error al agregar a favoritos"));
    }
}
?>