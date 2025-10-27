<?php
session_start();

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../classes/Usuario.php';

function verificarSesion() {
    // Verificar si hay sesión activa
    if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
        return false;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if(!$db) {
        return false;
    }
    
    $usuario = new Usuario($db);
    
    // Verificar token en la base de datos
    if($usuario->verificarToken($_SESSION['token'])) {
        // Actualizar datos de sesión por si han cambiado
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_nombre'] = $usuario->nombre;
        $_SESSION['user_email'] = $usuario->email;
        $_SESSION['user_tipo'] = $usuario->tipo_usuario;
        
        return true;
    }
    
    // Si el token no es válido, destruir sesión
    session_unset();
    session_destroy();
    return false;
}

function obtenerUsuarioSesion() {
    if(verificarSesion()) {
        return array(
            'id' => $_SESSION['user_id'],
            'nombre' => $_SESSION['user_nombre'],
            'email' => $_SESSION['user_email'],
            'tipo_usuario' => $_SESSION['user_tipo']
        );
    }
    return null;
}

function requiereLogin($redirect = true) {
    if(!verificarSesion()) {
        if($redirect) {
            header("Location: /login.html");
            exit();
        }
        return false;
    }
    return true;
}

function requiereTipoUsuario($tipos_permitidos, $redirect = true) {
    if(!verificarSesion()) {
        if($redirect) {
            header("Location: /login.html");
            exit();
        }
        return false;
    }
    
    if(!in_array($_SESSION['user_tipo'], $tipos_permitidos)) {
        if($redirect) {
            header("Location: /index.html");
            exit();
        }
        return false;
    }
    
    return true;
}
?>