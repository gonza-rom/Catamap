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

// Obtener categorías
$sql_categorias = "SELECT id_categoria, nombre FROM categorias ORDER BY nombre";
$result_categorias = $conexion->query($sql_categorias);

// Obtener departamentos
$sql_departamentos = "SELECT id, nombre FROM departamentos ORDER BY nombre";
$result_departamentos = $conexion->query($sql_departamentos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sugerir Nuevo Lugar - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            padding: 40px;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #667eea;
            font-weight: 700;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 2px solid #f0f0f0;
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .form-section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.2rem;
        }
        #map {
            height: 400px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="bi bi-plus-circle"></i> Sugerir Nuevo Lugar</h2>
                <p class="text-muted">Ayúdanos a crecer el mapa de Catamarca compartiendo lugares increíbles</p>
            </div>

            <div class="info-box">
                <i class="bi bi-info-circle"></i>
                <strong>Importante:</strong> Tu sugerencia será revisada por nuestro equipo antes de ser publicada. 
                Por favor proporciona información precisa y verídica.
            </div>

            <form id="formSugerirLugar" enctype="multipart/form-data">
                <!-- Sección 1: Información Básica -->
                <div class="form-section">
                    <h4 class="form-section-title"><i class="bi bi-card-text"></i> Información Básica</h4>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre del Lugar *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required 
                               placeholder="Ej: Cascada del Río Chico">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required
                                  placeholder="Describe el lugar, qué lo hace especial, cómo llegar, qué actividades se pueden realizar..."></textarea>
                        <small class="form-text text-muted">Mínimo 50 caracteres</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_categoria">Categoría *</label>
                                <select class="form-control" id="id_categoria" name="id_categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php while($cat = $result_categorias->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id_categoria']; ?>">
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_departamento">Departamento *</label>
                                <select class="form-control" id="id_departamento" name="id_departamento" required>
                                    <option value="">Selecciona un departamento</option>
                                    <?php while($dep = $result_departamentos->fetch_assoc()): ?>
                                        <option value="<?php echo $dep['id']; ?>">
                                            <?php echo htmlspecialchars($dep['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección 2: Ubicación -->
                <div class="form-section">
                    <h4 class="form-section-title"><i class="bi bi-geo-alt"></i> Ubicación</h4>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección o Referencia</label>
                        <input type="text" class="form-control" id="direccion" name="direccion"
                               placeholder="Ej: Ruta 38, Km 15, o cerca del pueblo X">
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-cursor"></i> <strong>Haz clic en el mapa</strong> para marcar la ubicación exacta del lugar
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lat">Latitud *</label>
                                <input type="text" class="form-control" id="lat" name="lat" readonly required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="lng">Longitud *</label>
                                <input type="text" class="form-control" id="lng" name="lng" readonly required>
                            </div>
                        </div>
                    </div>

                    <div id="map"></div>
                </div>

                <!-- Sección 3: Imagen -->
                <div class="form-section">
                    <h4 class="form-section-title"><i class="bi bi-image"></i> Imagen del Lugar</h4>
                    
                    <div class="form-group">
                        <label for="imagen">Sube una foto del lugar</label>
                        <input type="file" class="form-control-file" id="imagen" name="imagen" 
                               accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="form-text text-muted">Formatos: JPG, PNG, WEBP. Tamaño máximo: 5MB</small>
                    </div>

                    <img id="previewImagen" class="preview-image" alt="Vista previa">
                </div>

                <!-- Botones -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-submit" id="btnEnviar">
                        <i class="bi bi-send"></i> Enviar Sugerencia
                    </button>
                    <a href="perfil.php" class="btn btn-secondary ml-3">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Inicializar mapa centrado en Catamarca
        const map = L.map('map').setView([-28.4696, -65.7795], 8);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker = null;

        // Evento de clic en el mapa
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(8);
            const lng = e.latlng.lng.toFixed(8);
            
            // Actualizar inputs
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            
            // Crear o actualizar marcador
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        // Preview de imagen
        document.getElementById('imagen').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validar tamaño
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Archivo muy grande',
                        text: 'La imagen no puede superar los 5MB'
                    });
                    e.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('previewImagen');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Enviar formulario
        document.getElementById('formSugerirLugar').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validar descripción
            const descripcion = document.getElementById('descripcion').value;
            if (descripcion.length < 50) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Descripción muy corta',
                    text: 'La descripción debe tener al menos 50 caracteres'
                });
                return;
            }

            // Validar ubicación
            const lat = document.getElementById('lat').value;
            const lng = document.getElementById('lng').value;
            if (!lat || !lng) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta la ubicación',
                    text: 'Por favor marca la ubicación en el mapa'
                });
                return;
            }

            const btnEnviar = document.getElementById('btnEnviar');
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';

            const formData = new FormData(this);

            try {
                const response = await fetch('../api/sugerir-lugar.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Sugerencia enviada!',
                        text: 'Tu sugerencia será revisada pronto. ¡Gracias por contribuir!',
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
                    text: error.message || 'No se pudo enviar la sugerencia'
                });
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<i class="bi bi-send"></i> Enviar Sugerencia';
            }
        });
    </script>
</body>
</html>