<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

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
if(
    empty($data->nombre) ||
    empty($data->email) ||
    empty($data->password) ||
    empty($data->tipo_usuario)
) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Datos incompletos. Nombre, email, password y tipo de usuario son requeridos."
    ));
    exit();
}

// Validar formato de email
if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Formato de email inválido"
    ));
    exit();
}

// Validar longitud de password
if(strlen($data->password) < 6) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "La contraseña debe tener al menos 6 caracteres"
    ));
    exit();
}

// Validar tipo de usuario
$tipos_validos = array('usuario', 'emprendedor', 'administrador');
if(!in_array($data->tipo_usuario, $tipos_validos)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Tipo de usuario inválido"
    ));
    exit();
}

// Asignar valores
$usuario->nombre = $data->nombre;
$usuario->email = $data->email;
$usuario->password = $data->password;
$usuario->tipo_usuario = $data->tipo_usuario;

// Verificar si el email ya existe
if($usuario->emailExists()) {
    http_response_code(409);
    echo json_encode(array(
        "success" => false,
        "message" => "El email ya está registrado"
    ));
    exit();
}

// Registrar usuario
if($usuario->registrar()) {
    http_response_code(201);
    echo json_encode(array(
        "success" => true,
        "message" => "Usuario registrado exitosamente",
        "data" => array(
            "id" => $usuario->id,
            "nombre" => $usuario->nombre,
            "email" => $usuario->email,
            "tipo_usuario" => $usuario->tipo_usuario
        )
    ));
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "No se pudo registrar el usuario"
    ));
}
?>