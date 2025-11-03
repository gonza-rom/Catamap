<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

include_once '../config/database.php';
include_once '../classes/Usuario.php';
include_once '../includes/conexion.php';

// Log para debug
error_log("POST recibido: " . print_r($_POST, true));

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

$id_usuario = $_SESSION['user_id'];

try {
    // Obtener datos del POST con validación
    $perfil_publico = (isset($_POST['perfil_publico']) && $_POST['perfil_publico'] === 'true') ? 1 : 0;
    $favoritos_publicos = (isset($_POST['favoritos_publicos']) && $_POST['favoritos_publicos'] === 'true') ? 1 : 0;
    $comentarios_publicos = (isset($_POST['comentarios_publicos']) && $_POST['comentarios_publicos'] === 'true') ? 1 : 0;
    $mostrar_estadisticas = (isset($_POST['mostrar_estadisticas']) && $_POST['mostrar_estadisticas'] === 'true') ? 1 : 0;
    
    // Log de valores procesados
    error_log("Valores procesados - Perfil: $perfil_publico, Favoritos: $favoritos_publicos, Comentarios: $comentarios_publicos, Estadísticas: $mostrar_estadisticas");
    
    // Verificar si ya existe configuración
    $sql_check = "SELECT id FROM configuracion_privacidad WHERE id_usuario = ?";
    $stmt_check = $conexion->prepare($sql_check);
    
    if(!$stmt_check) {
        throw new Exception("Error al preparar consulta: " . $conexion->error);
    }
    
    $stmt_check->bind_param("i", $id_usuario);
    $stmt_check->execute();
    $existe = $stmt_check->get_result()->num_rows > 0;
    
    error_log("Usuario $id_usuario - Configuración existe: " . ($existe ? 'SI' : 'NO'));
    
    if ($existe) {
        // Actualizar configuración existente
        $sql = "UPDATE configuracion_privacidad 
                SET perfil_publico = ?, 
                    favoritos_publicos = ?, 
                    comentarios_publicos = ?,
                    mostrar_estadisticas = ?
                WHERE id_usuario = ?";
        $stmt = $conexion->prepare($sql);
        
        if(!$stmt) {
            throw new Exception("Error al preparar UPDATE: " . $conexion->error);
        }
        
        $stmt->bind_param("iiiii", $perfil_publico, $favoritos_publicos, $comentarios_publicos, $mostrar_estadisticas, $id_usuario);
        error_log("Ejecutando UPDATE");
    } else {
        // Insertar nueva configuración
        $sql = "INSERT INTO configuracion_privacidad 
                (id_usuario, perfil_publico, favoritos_publicos, comentarios_publicos, mostrar_estadisticas) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        
        if(!$stmt) {
            throw new Exception("Error al preparar INSERT: " . $conexion->error);
        }
        
        $stmt->bind_param("iiiii", $id_usuario, $perfil_publico, $favoritos_publicos, $comentarios_publicos, $mostrar_estadisticas);
        error_log("Ejecutando INSERT");
    }
    
    if ($stmt->execute()) {
        error_log("Configuración guardada exitosamente para usuario $id_usuario");
        echo json_encode([
            'success' => true,
            'message' => 'Configuración de privacidad guardada correctamente',
            'debug' => [
                'perfil_publico' => $perfil_publico,
                'favoritos_publicos' => $favoritos_publicos,
                'comentarios_publicos' => $comentarios_publicos,
                'mostrar_estadisticas' => $mostrar_estadisticas
            ]
        ]);
    } else {
        throw new Exception('Error al ejecutar query: ' . $stmt->error);
    }
    
} catch(Exception $e) {
    error_log("Error en guardar_privacidad.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar configuración: ' . $e->getMessage()
    ]);
}
?>