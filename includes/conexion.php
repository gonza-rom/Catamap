<?php
//declarar las variables en donde se guardaran los valores de la conexion
$servidor = "localhost";     // Servidor local de XAMPP
$usuario = "root";       // Usuario por defecto
$password = "";        // Sin contraseña por defecto
$bd = "catamap";       // Nombre de tu base de datos

//agregamos el puerto 3307, xq ahi esta corriendo mysql con xampp
$conexion = new mysqli($servidor, $usuario, $password, $bd, 3306);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: establecer charset
$conexion->set_charset("utf8mb4");
?>
