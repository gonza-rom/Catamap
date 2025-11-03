<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Usuario.php';
require_once '../includes/conexion.php';

// Verificar sesión
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!$db) {
    die("Error de conexión a la base de datos");
}

$usuario = new Usuario($db);
if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$id_usuario = $usuario->id;

// Obtener configuración actual
$sql = "SELECT * FROM configuracion_privacidad WHERE id_usuario = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows > 0) {
    $config = $result->fetch_assoc();
} else {
    // Crear configuración por defecto
    $sql_insert = "INSERT INTO configuracion_privacidad (id_usuario) VALUES (?)";
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bind_param("i", $id_usuario);
    $stmt_insert->execute();
    
    $config = [
        'perfil_publico' => 1,
        'favoritos_publicos' => 1,
        'comentarios_publicos' => 1,
        'mostrar_estadisticas' => 1
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Privacidad - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .config-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .config-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .config-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .config-header h2 {
            color: #667eea;
            font-weight: 700;
        }
        .privacy-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .privacy-item-info {
            flex: 1;
        }
        .privacy-item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .privacy-item-desc {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        .custom-switch {
            padding-left: 0;
        }
        .custom-control-label {
            cursor: pointer;
        }
        .info-alert {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .link-perfil-publico {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .link-perfil-publico input {
            background: white;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container config-container">
        <div class="config-card">
            <div class="config-header">
                <h2><i class="bi bi-shield-lock"></i> Configuración de Privacidad</h2>
                <p class="text-muted">Controla qué información es visible públicamente</p>
            </div>

            <div class="info-alert">
                <i class="bi bi-info-circle"></i>
                <strong>Nota:</strong> Estos ajustes controlan qué pueden ver otros usuarios en tu perfil público.
            </div>

            <form id="formPrivacidad">
                <!-- Perfil Público -->
                <div class="privacy-item">
                    <div class="privacy-item-info">
                        <div class="privacy-item-title">
                            <i class="bi bi-person-circle"></i> Perfil Público
                        </div>
                        <p class="privacy-item-desc">
                            Permite que otros usuarios vean tu perfil y actividad
                        </p>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="perfil_publico" 
                               name="perfil_publico" <?php echo $config['perfil_publico'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="perfil_publico"></label>
                    </div>
                </div>

                <!-- Favoritos Públicos -->
                <div class="privacy-item">
                    <div class="privacy-item-info">
                        <div class="privacy-item-title">
                            <i class="bi bi-heart-fill"></i> Favoritos Públicos
                        </div>
                        <p class="privacy-item-desc">
                            Muestra tus lugares favoritos en tu perfil público
                        </p>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="favoritos_publicos" 
                               name="favoritos_publicos" <?php echo $config['favoritos_publicos'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="favoritos_publicos"></label>
                    </div>
                </div>

                <!-- Comentarios Públicos -->
                <div class="privacy-item">
                    <div class="privacy-item-info">
                        <div class="privacy-item-title">
                            <i class="bi bi-chat-dots-fill"></i> Opiniones Públicas
                        </div>
                        <p class="privacy-item-desc">
                            Permite que otros vean las opiniones que has dejado
                        </p>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="comentarios_publicos" 
                               name="comentarios_publicos" <?php echo $config['comentarios_publicos'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="comentarios_publicos"></label>
                    </div>
                </div>

                <!-- Mostrar Estadísticas -->
                <div class="privacy-item">
                    <div class="privacy-item-info">
                        <div class="privacy-item-title">
                            <i class="bi bi-bar-chart-fill"></i> Mostrar Estadísticas
                        </div>
                        <p class="privacy-item-desc">
                            Muestra contadores de favoritos, opiniones y seguidores en tu perfil
                        </p>
                    </div>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="mostrar_estadisticas" 
                               name="mostrar_estadisticas" <?php echo $config['mostrar_estadisticas'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="mostrar_estadisticas"></label>
                    </div>
                </div>

                <!-- Link del perfil público -->
                <div class="link-perfil-publico">
                    <label><strong><i class="bi bi-link-45deg"></i> Enlace de tu perfil público:</strong></label>
                    <input type="text" readonly value="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/catamap/pages/perfil-publico.php?user=' . $id_usuario; ?>" 
                           onclick="this.select(); document.execCommand('copy'); mostrarMensaje('Enlace copiado', 'success');">
                    <small class="text-muted">Haz clic para copiar el enlace</small>
                </div>

                <!-- Botones -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnGuardar">
                        <i class="bi bi-check-circle"></i> Guardar Cambios
                    </button>
                    <a href="perfil.php" class="btn btn-secondary btn-lg ml-2">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function mostrarMensaje(mensaje, tipo) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });

            Toast.fire({
                icon: tipo,
                title: mensaje
            });
        }

        document.getElementById('formPrivacidad').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btnGuardar = document.getElementById('btnGuardar');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';

            const formData = {
                perfil_publico: document.getElementById('perfil_publico').checked ? 1 : 0,
                favoritos_publicos: document.getElementById('favoritos_publicos').checked ? 1 : 0,
                comentarios_publicos: document.getElementById('comentarios_publicos').checked ? 1 : 0,
                mostrar_estadisticas: document.getElementById('mostrar_estadisticas').checked ? 1 : 0
            };

            try {
                const response = await fetch('../api/configuracion-privacidad.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'Tu configuración de privacidad ha sido actualizada',
                        confirmButtonColor: '#667eea'
                    }).then(() => {
                        window.location.href = 'perfil.php';
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo guardar la configuración'
                });
                btnGuardar.disabled = false;
                btnGuardar.innerHTML = '<i class="bi bi-check-circle"></i> Guardar Cambios';
            }
        });
    </script>
</body>
</html>