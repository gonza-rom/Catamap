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

// Obtener datos completos del usuario directamente de la BD
$sql_usuario = "SELECT id, nombre, email, tipo_usuario, telefono, imagen_perfil, fecha_registro 
                FROM usuarios WHERE id = ?";
$stmt_usuario = $conexion->prepare($sql_usuario);
$stmt_usuario->bind_param("i", $usuario->id);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if($result_usuario->num_rows === 0) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$usuario_data = $result_usuario->fetch_assoc();

// Obtener favoritos del usuario
$sql_favoritos = "SELECT l.*, c.nombre AS categoria_nombre, d.nombre AS departamento_nombre, f.fecha_agregado
                  FROM favoritos f
                  INNER JOIN lugares_turisticos l ON f.id_lugar = l.id
                  LEFT JOIN categorias c ON l.id_categoria = c.id_categoria
                  LEFT JOIN departamentos d ON l.id_departamento = d.id
                  WHERE f.id_usuario = ?
                  ORDER BY f.fecha_agregado DESC";
$stmt = $conexion->prepare($sql_favoritos);
$stmt->bind_param("i", $usuario_data['id']);
$stmt->execute();
$result_favoritos = $stmt->get_result();

// Obtener estadísticas del usuario
$sql_stats = "SELECT 
                (SELECT COUNT(*) FROM favoritos WHERE id_usuario = ?) as total_favoritos";
$stmt_stats = $conexion->prepare($sql_stats);
$stmt_stats->bind_param("i", $usuario_data['id']);
$stmt_stats->execute();
$estadisticas = $stmt_stats->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../styles/perfil2.css">
</head>
<body>
    <div class="container profile-container">
        <div class="row">
            <div class="col-lg-4">
                <div class="profile-card">
                    <div class="profile-header text-center">
                        <div class="profile-avatar-container">
                            <?php 
                            // Generar URL del avatar
                            if(!empty($usuario_data['imagen_perfil']) && file_exists('../uploads/' . $usuario_data['imagen_perfil'])) {
                                $avatar_url = '../uploads/' . $usuario_data['imagen_perfil'] . '?v=' . time();
                            } else {
                                $avatar_url = 'https://ui-avatars.com/api/?name=' . urlencode($usuario_data['nombre']) . '&size=150&background=e67e22&color=fff';
                            }
                            ?>
                            <img id="profileAvatar" 
                                 src="<?php echo $avatar_url; ?>" 
                                 class="profile-avatar" 
                                 alt="Avatar"
                                 data-imagen="<?php echo htmlspecialchars($usuario_data['imagen_perfil'] ?? ''); ?>">
                            <button class="edit-avatar-btn" onclick="cambiarFotoPerfil()">
                                <i class="bi bi-camera-fill"></i>
                            </button>
                            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        </div>
                        <h3 class="mt-3 mb-1"><?php echo htmlspecialchars($usuario_data['nombre']); ?></h3>
                        <p class="mb-2 opacity-75"><?php echo htmlspecialchars($usuario_data['email']); ?></p>
                        <span class="badge-tipo badge-<?php echo $usuario_data['tipo_usuario']; ?>">
                            <i class="bi bi-shield-check"></i> <?php echo ucfirst($usuario_data['tipo_usuario']); ?>
                        </span>
                    </div>

                    <div class="profile-body">
                        <div class="info-group">
                            <div class="info-label">
                                <i class="bi bi-calendar-check"></i>
                                Miembro desde
                            </div>
                            <div class="info-value">
                                <?php 
                                    $fecha = new DateTime($usuario_data['fecha_registro']);
                                    echo $fecha->format('d/m/Y'); 
                                ?>
                            </div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <a href="../index.php" class="btn btn-secondary btn-block mb-2">
                                <i class="bi bi-house"></i> Volver al Inicio
                            </a>
                            <a href="mapa-catamarca.php" class="btn btn-primary btn-block mb-2">
                                <i class="bi bi-map"></i> Ver Mapa
                            </a>
                            <a href="lugares.php" class="btn btn-info btn-block mb-2">
                                <i class="bi bi-geo-alt"></i> Lugares Turísticos
                            </a>
                            <button id="btnLogout" class="btn btn-danger btn-block">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-card">
                    <div class="profile-body">
                        <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="info-tab" data-toggle="tab" href="#info" role="tab">
                                    <i class="bi bi-person"></i> Información Personal
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="favoritos-tab" data-toggle="tab" href="#favoritos" role="tab">
                                    <i class="bi bi-heart-fill"></i> Mis Favoritos
                                    <span class="badge badge-primary"><?php echo $estadisticas['total_favoritos']; ?></span>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content" id="profileTabsContent">
                            <!-- Tab Información Personal -->
                            <div class="tab-pane fade show active" id="info" role="tabpanel">
                                <div class="info-group">
                                    <div class="info-label">
                                        <i class="bi bi-person-circle"></i> Nombre Completo
                                    </div>
                                    <div class="info-value">
                                        <span><?php echo htmlspecialchars($usuario_data['nombre']); ?></span>
                                        <button class="btn-edit" onclick="editarNombre()">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </div>
                                </div>

                                <div class="info-group">
                                    <div class="info-label">
                                        <i class="bi bi-envelope"></i> Correo Electrónico
                                    </div>
                                    <div class="info-value">
                                        <span><?php echo htmlspecialchars($usuario_data['email']); ?></span>
                                        <button class="btn-edit" onclick="editarEmail()">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </div>
                                </div>

                                <div class="info-group">
                                    <div class="info-label">
                                        <i class="bi bi-telephone"></i> Teléfono
                                    </div>
                                    <div class="info-value">
                                        <span><?php echo !empty($usuario_data['telefono']) ? htmlspecialchars($usuario_data['telefono']) : 'No especificado'; ?></span>
                                        <button class="btn-edit" onclick="editarTelefono()">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                    </div>
                                </div>

                                <hr>

                                <button class="btn btn-warning btn-block" onclick="cambiarPassword()">
                                    <i class="bi bi-key"></i> Cambiar Contraseña
                                </button>
                            </div>

                            <!-- Tab Favoritos -->
                            <div class="tab-pane fade" id="favoritos" role="tabpanel">
                                <h4 class="mb-4">
                                    <i class="bi bi-heart-fill" style="color: #e74c3c;"></i> 
                                    Lugares Favoritos
                                </h4>
                                
                                <div class="row" id="favoritosContainer">
                                    <?php if($result_favoritos->num_rows > 0): ?>
                                        <?php while($fav = $result_favoritos->fetch_assoc()): ?>
                                            <div class="col-md-6 mb-3" data-favorito-id="<?php echo $fav['id']; ?>">
                                                <div class="favorito-card">
                                                    <img src="<?php echo $fav['imagen'] ? '../uploads/'.$fav['imagen'] : '../img/placeholder.jpg'; ?>" 
                                                         class="favorito-img" 
                                                         alt="<?php echo htmlspecialchars($fav['nombre']); ?>"
                                                         onerror="this.src='../img/placeholder.jpg'">
                                                    <div class="favorito-content">
                                                        <h5 class="favorito-titulo"><?php echo htmlspecialchars($fav['nombre']); ?></h5>
                                                        <div class="favorito-info">
                                                            <i class="bi bi-geo-alt-fill"></i>
                                                            <span><?php echo htmlspecialchars($fav['departamento_nombre']); ?></span>
                                                        </div>
                                                        <div class="favorito-info">
                                                            <i class="bi bi-tag-fill"></i>
                                                            <span><?php echo htmlspecialchars($fav['categoria_nombre']); ?></span>
                                                        </div>
                                                        <div class="favorito-info">
                                                            <i class="bi bi-clock-fill"></i>
                                                            <span>Agregado el <?php echo date('d/m/Y', strtotime($fav['fecha_agregado'])); ?></span>
                                                        </div>
                                                        <div class="d-flex gap-2" style="gap: 10px;">
                                                            <button class="btn-favorito-action btn-ver-mapa" style="flex: 1;" 
                                                                    onclick="window.location.href='mapa-catamarca.php?lat=<?php echo $fav['lat']; ?>&lng=<?php echo $fav['lng']; ?>&id=<?php echo $fav['id']; ?>'">
                                                                <i class="bi bi-map"></i> Ver en Mapa
                                                            </button>
                                                            <button class="btn-favorito-action btn-quitar-favorito" style="flex: 1;" 
                                                                    onclick="quitarFavorito(<?php echo $fav['id']; ?>)">
                                                                <i class="bi bi-trash"></i> Quitar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <div class="col-12 text-center py-5">
                                            <i class="bi bi-heart" style="font-size: 5rem; color: #ecf0f1;"></i>
                                            <h4 class="mt-3 text-muted">No tienes lugares favoritos aún</h4>
                                            <p class="text-muted">Explora el mapa y guarda tus lugares favoritos</p>
                                            <a href="mapa-catamarca.php" class="btn btn-primary mt-3">
                                                <i class="bi bi-map"></i> Explorar Mapa
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/auth.js"></script>

    <script>
        function cambiarFotoPerfil() {
            document.getElementById('avatarInput').click();
        }

        document.getElementById('avatarInput').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (!file) return;

            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo muy grande',
                    text: 'La imagen no puede superar los 2MB',
                    confirmButtonColor: '#e67e22'
                });
                return;
            }

            if (!file.type.startsWith('image/')) {
                Swal.fire({
                    icon: 'error',
                    title: 'Archivo inválido',
                    text: 'Por favor selecciona una imagen válida',
                    confirmButtonColor: '#e67e22'
                });
                return;
            }

            const formData = new FormData();
            formData.append('imagen_perfil', file);

            Swal.fire({
                title: 'Subiendo imagen...',
                html: 'Por favor espera',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch('../api/actualizar-perfil.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Imagen actualizada!',
                        text: 'Recargando página...',
                        confirmButtonColor: '#e67e22',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Recargar la página completamente para ver los cambios
                        window.location.reload(true);
                    });
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo actualizar la imagen',
                    confirmButtonColor: '#e67e22'
                });
            }
        });

        // Resto de funciones...
        async function editarNombre() {
            const { value: nombre } = await Swal.fire({
                title: 'Editar Nombre',
                input: 'text',
                inputLabel: 'Nuevo nombre',
                inputValue: '<?php echo addslashes($usuario_data['nombre']); ?>',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Guardar',
                inputValidator: (value) => {
                    if (!value) return 'Por favor ingresa un nombre';
                }
            });
            if (nombre) await actualizarPerfil({ nombre: nombre });
        }

        async function editarEmail() {
            const { value: email } = await Swal.fire({
                title: 'Editar Email',
                input: 'email',
                inputLabel: 'Nuevo correo electrónico',
                inputValue: '<?php echo $usuario_data['email']; ?>',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Guardar',
                inputValidator: (value) => {
                    if (!value) return 'Por favor ingresa un email';
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return 'Email inválido';
                }
            });
            if (email) await actualizarPerfil({ email: email });
        }

        async function editarTelefono() {
            const { value: telefono } = await Swal.fire({
                title: 'Editar Teléfono',
                input: 'tel',
                inputLabel: 'Nuevo número de teléfono',
                inputValue: '<?php echo $usuario_data['telefono'] ?? ''; ?>',
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                cancelButtonText: 'Cancelar',
                confirmButtonText: 'Guardar'
            });
            if (telefono !== undefined) await actualizarPerfil({ telefono: telefono });
        }

        async function actualizarPerfil(datos) {
            Swal.fire({
                title: 'Actualizando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch('../api/actualizar-perfil.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(datos)
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        confirmButtonColor: '#e67e22'
                    }).then(() => location.reload());
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonColor: '#e67e22'
                });
            }
        }

        async function cambiarPassword() {
            const { value: formValues } = await Swal.fire({
                title: 'Cambiar Contraseña',
                html:
                    '<input id="swal-password-actual" type="password" class="swal2-input" placeholder="Contraseña actual">' +
                    '<input id="swal-password-nueva" type="password" class="swal2-input" placeholder="Nueva contraseña">' +
                    '<input id="swal-password-confirmar" type="password" class="swal2-input" placeholder="Confirmar contraseña">',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonColor: '#e67e22',
                preConfirm: () => {
                    const actual = document.getElementById('swal-password-actual').value;
                    const nueva = document.getElementById('swal-password-nueva').value;
                    const confirmar = document.getElementById('swal-password-confirmar').value;

                    if (!actual || !nueva || !confirmar) {
                        Swal.showValidationMessage('Completa todos los campos');
                        return false;
                    }
                    if (nueva.length < 6) {
                        Swal.showValidationMessage('Mínimo 6 caracteres');
                        return false;
                    }
                    if (nueva !== confirmar) {
                        Swal.showValidationMessage('Las contraseñas no coinciden');
                        return false;
                    }
                    return { actual, nueva };
                }
            });

            if (formValues) {
                await actualizarPerfil({
                    password_actual: formValues.actual,
                    password_nueva: formValues.nueva
                });
            }
        }

        async function quitarFavorito(idLugar) {
            const result = await Swal.fire({
                title: '¿Quitar de favoritos?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                confirmButtonText: 'Sí, quitar'
            });

            if (result.isConfirmed) {
                try {
                    const response = await fetch('../api/favoritos.php', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_lugar: idLugar })
                    });

                    const data = await response.json();

                    if (data.success) {
                        $(`[data-favorito-id="${idLugar}"]`).fadeOut(300, function() {
                            $(this).remove();
                            if ($('#favoritosContainer .col-md-6').length === 0) {
                                $('#favoritosContainer').html(`
                                    <div class="col-12 text-center py-5">
                                        <i class="bi bi-heart" style="font-size: 5rem; color: #ecf0f1;"></i>
                                        <h4 class="mt-3 text-muted">No tienes lugares favoritos aún</h4>
                                        <a href="mapa-catamarca.php" class="btn btn-primary mt-3">
                                            <i class="bi bi-map"></i> Explorar Mapa
                                        </a>
                                    </div>
                                `);
                            }
                        });

                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: error.message });
                }
            }
        }

        $('#btnLogout').click(async function(e) {
            e.preventDefault();
            const result = await Swal.fire({
                title: '¿Cerrar sesión?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                confirmButtonText: 'Sí, cerrar sesión'
            });

            if (result.isConfirmed) {
                await Auth.logout();
                window.location.href = '../index.php';
            }
        });
    </script>
</body>
</html>