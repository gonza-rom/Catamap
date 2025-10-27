<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

session_start();

include_once '../config/database.php';
include_once '../classes/Usuario.php';

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error de conexión a la base de datos"));
    exit();
}

$usuario = new Usuario($db);

// Obtener token de la sesión o del body
$token = isset($_SESSION['token']) ? $_SESSION['token'] : null;

if(!$token) {
    $data = json_decode(file_get_contents("php://input"));
    $token = isset($data->token) ? $data->token : null;
}

if($token) {
    // Eliminar token de la base de datos
    $usuario->eliminarToken($token);
}

// Destruir sesión PHP
session_unset();
session_destroy();

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "message" => "Sesión cerrada exitosamente"
));
?>