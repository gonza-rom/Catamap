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

// Obtener datos del POST (puede venir como JSON o form data)
$campo = '';
$valor = '';

if(isset($_POST['campo']) && isset($_POST['valor'])) {
    $campo = $_POST['campo'];
    $valor = trim($_POST['valor']);
} else {
    $data = json_decode(file_get_contents("php://input"), true);
    if($data && isset($data['campo']) && isset($data['valor'])) {
        $campo = $data['campo'];
        $valor = trim($data['valor']);
    }
}

if(empty($campo)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Datos incompletos"]);
    exit();
}

// Validar campo permitido
$campos_permitidos = ['nombre', 'telefono'];
if(!in_array($campo, $campos_permitidos)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campo no permitido"]);
    exit();
}

// Validar nombre
if($campo === 'nombre' && empty($valor)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "El nombre no puede estar vacío"]);
    exit();
}

try {
    $sql = "UPDATE usuarios SET $campo = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $valor, $id_usuario);
    
    if($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => ucfirst($campo) . ' actualizado correctamente',
            $campo => $valor
        ]);
    } else {
        throw new Exception('Error al actualizar');
    }
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>