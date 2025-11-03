
<?php
header("Content-Type: application/json; charset=UTF-8");
include_once '../includes/conexion.php';

try {
    $sql = "SELECT id, nombre FROM departamentos ORDER BY nombre";
    $result = $conexion->query($sql);
    
    $departamentos = [];
    while($row = $result->fetch_assoc()) {
        $departamentos[] = [
            'id' => intval($row['id']),
            'nombre' => $row['nombre']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'departamentos' => $departamentos
    ]);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener departamentos: ' . $e->getMessage()
    ]);
}
?>