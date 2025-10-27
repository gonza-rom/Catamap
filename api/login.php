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

// Obtener datos enviados
$data = json_decode(file_get_contents("php://input"));

// Validar datos requeridos
if(empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Email y contraseña son requeridos"
    ));
    exit();
}

// Asignar valores
$usuario->email = $data->email;
$usuario->password = $data->password;

// Intentar login
if($usuario->login()) {
    // Crear token de sesión
    $token = $usuario->crearTokenSesion();
    
    if($token) {
        // Crear sesión PHP
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_nombre'] = $usuario->nombre;
        $_SESSION['user_email'] = $usuario->email;
        $_SESSION['user_tipo'] = $usuario->tipo_usuario;
        $_SESSION['token'] = $token;
        
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Login exitoso",
            "data" => array(
                "id" => $usuario->id,
                "nombre" => $usuario->nombre,
                "email" => $usuario->email,
                "tipo_usuario" => $usuario->tipo_usuario,
                "token" => $token
            )
        ));
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Error al crear la sesión"
        ));
    }
} else {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Email o contraseña incorrectos"
    ));
}
?>