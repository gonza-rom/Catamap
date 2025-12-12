<?php
include_once 'middleware.php';
include_once '../../includes/conexion.php';
error_reporting(0); // Suppress warnings to avoid JSON corruption
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];

$method = $_SERVER['REQUEST_METHOD'];

if($method === 'GET') {
    // Inicializar estructura de respuesta
    $stats = [
        'usuarios' => ['total' => 0, 'activos' => 0, 'suspendidos' => 0],
        'lugares' => ['total' => 0, 'aprobados' => 0, 'pendientes' => 0],
        'lugares_sugeridos' => ['total' => 0],
        'comentarios' => ['total' => 0, 'pendientes' => 0],
        'actividad_reciente' => [],
        'usuarios_activos' => []
    ];

    try {
        // 1. Estadísticas de Usuarios
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'suspendido' THEN 1 ELSE 0 END) as suspendidos
                FROM usuarios";
        $result = $conexion->query($sql);
        if($result && $row = $result->fetch_assoc()) {
            $stats['usuarios'] = $row;
        }

        // 2. Estadísticas de Lugares (Verificando que exista la tabla)
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
                FROM lugares_turisticos";
        $result = $conexion->query($sql);
        if($result && $row = $result->fetch_assoc()) {
            $stats['lugares'] = $row;
        }

        // 3. Lugares Sugeridos
        $sql = "SELECT COUNT(*) as total FROM lugares_sugeridos";
        $result = $conexion->query($sql);
        if($result && $row = $result->fetch_assoc()) {
            $stats['lugares_sugeridos']['total'] = $row['total'];
        }

        // 4. Comentarios
        $sql = "SELECT COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
                FROM comentarios";
        $result = $conexion->query($sql);
        if($result && $row = $result->fetch_assoc()) {
            $stats['comentarios'] = $row;
        }

        // 5. Actividad Reciente
        $sql = "(SELECT 'usuario' as tipo, 
                 CONCAT('Nuevo usuario: ', nombre) as descripcion, 
                 fecha_registro as fecha 
                 FROM usuarios ORDER BY fecha_registro DESC LIMIT 5)
                UNION ALL
                (SELECT 'lugar' as tipo, 
                 CONCAT('Nuevo lugar: ', nombre) as descripcion, 
                 CURRENT_TIMESTAMP as fecha 
                 FROM lugares_turisticos ORDER BY id DESC LIMIT 5)
                UNION ALL
                (SELECT 'comentario' as tipo, 
                 CONCAT('Nuevo comentario en lugar ID: ', id_lugar) as descripcion, 
                 fecha_creacion as fecha 
                 FROM comentarios ORDER BY fecha_creacion DESC LIMIT 5)
                ORDER BY fecha DESC LIMIT 10";
        $result = $conexion->query($sql);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats['actividad_reciente'][] = $row;
            }
        }

        // 6. Usuarios más activos
        $sql = "SELECT u.id, u.nombre, u.rol, COUNT(c.id) as total_comentarios
                FROM usuarios u
                JOIN comentarios c ON u.id = c.id_usuario
                GROUP BY u.id
                ORDER BY total_comentarios DESC
                LIMIT 5";
        $result = $conexion->query($sql);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats['usuarios_activos'][] = $row;
            }
        }

        echo json_encode(['success' => true, 'data' => $stats]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error servidor: ' . $e->getMessage()]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["success" => false, "message" => "Método no permitido"]);
?>
