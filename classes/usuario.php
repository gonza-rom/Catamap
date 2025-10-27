<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $tipo_usuario;
    public $fecha_registro;
    public $ultimo_acceso;
    public $estado;
    public $imagen_perfil;
    public $telefono;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Registrar nuevo usuario
    public function registrar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, email, password, tipo_usuario) 
                  VALUES (:nombre, :email, :password, :tipo_usuario)";
        
        $stmt = $this->conn->prepare($query);
        
        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->tipo_usuario = htmlspecialchars(strip_tags($this->tipo_usuario));
        
        // Hash del password
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);
        
        // Bind de parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":tipo_usuario", $this->tipo_usuario);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    // Login de usuario
    public function login() {
        $query = "SELECT id, nombre, email, password, tipo_usuario, estado 
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            // Verificar si está activo
            if($row['estado'] != 'activo') {
                return false;
            }
            
            // Verificar password
            if(password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->email = $row['email'];
                $this->tipo_usuario = $row['tipo_usuario'];
                
                // Actualizar último acceso
                $this->actualizarUltimoAcceso();
                
                return true;
            }
        }
        
        return false;
    }

    // Verificar si el email ya existe
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Actualizar último acceso
    private function actualizarUltimoAcceso() {
        $query = "UPDATE " . $this->table_name . " 
                  SET ultimo_acceso = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
    }

    // Obtener datos del usuario por ID
    public function obtenerPorId() {
        $query = "SELECT id, nombre, email, tipo_usuario, fecha_registro, 
                  imagen_perfil, telefono, estado 
                  FROM " . $this->table_name . " 
                  WHERE id = :id LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->tipo_usuario = $row['tipo_usuario'];
            $this->fecha_registro = $row['fecha_registro'];
            $this->imagen_perfil = $row['imagen_perfil'];
            $this->telefono = $row['telefono'];
            $this->estado = $row['estado'];
            
            return true;
        }
        
        return false;
    }

    // Crear token de sesión
    public function crearTokenSesion() {
        $token = bin2hex(random_bytes(32));
        
        $query = "INSERT INTO sesiones_usuarios 
                  (id_usuario, token, fecha_expiracion, ip_address, user_agent) 
                  VALUES (:id_usuario, :token, DATE_ADD(NOW(), INTERVAL 7 DAY), :ip, :user_agent)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $this->id);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":ip", $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(":user_agent", $_SERVER['HTTP_USER_AGENT']);
        
        if($stmt->execute()) {
            return $token;
        }
        
        return false;
    }

    // Verificar token de sesión
    public function verificarToken($token) {
        $query = "SELECT su.id_usuario, u.nombre, u.email, u.tipo_usuario 
                  FROM sesiones_usuarios su
                  INNER JOIN usuarios u ON su.id_usuario = u.id
                  WHERE su.token = :token 
                  AND su.fecha_expiracion > NOW()
                  AND u.estado = 'activo'
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            $this->id = $row['id_usuario'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->tipo_usuario = $row['tipo_usuario'];
            
            return true;
        }
        
        return false;
    }

    // Eliminar token de sesión (logout)
    public function eliminarToken($token) {
        $query = "DELETE FROM sesiones_usuarios WHERE token = :token";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        return $stmt->execute();
    }
}
?>