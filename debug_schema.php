<?php
include 'includes/conexion.php';

$tables = ['categorias', 'lugares_turisticos', 'departamentos', 'usuarios'];

foreach ($tables as $table) {
    echo "Table: $table\n";
    $result = $conexion->query("DESCRIBE $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
        }
    } else {
        echo "Error describing $table: " . $conexion->error . "\n";
    }
    echo "-------------------\n";
}
?>
