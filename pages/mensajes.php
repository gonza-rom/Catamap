<?php
session_start();
require_once '../config/database.php';
require_once '../classes/Usuario.php';

// Verificar sesión
if(!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

if(!$usuario->verificarToken($_SESSION['token'])) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

$chat_con = isset($_GET['chat']) ? intval($_GET['chat']) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - CataMap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .mensajes-container {
            max-width: 1200px;
            margin: 30px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
            height: calc(100vh - 60px);
            display: flex;
        }
        .conversaciones-lista {
            width: 350px;
            border-right: 2px solid #e9ecef;
            display: flex;
            flex-direction: column;
        }
        .conversaciones-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .conversacion-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .conversacion-item:hover {
            background: #f8f9fa;
        }
        .conversacion-item.active {
            background: #e3f2fd;
            border-left: 4px solid #667eea;
        }
        .conversacion-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .badge-no-leidos {
            background: #e74c3c;
            color: white;
            border-radius: 20px;
            padding: 3px 8px;
            font-size: 0.75rem;
        }
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .mensajes-contenedor {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f1f3f5;
            display: flex;
            flex-direction: column;
        }
        .mensaje {
            max-width: 60%;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .mensaje.propio {
            align-self: flex-end;
            align-items: flex-end;
        }
        .mensaje-bubble {
            background: white;
            padding: 12px 16px;
            border-radius: 18px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .mensaje.propio .mensaje-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .mensaje-fecha {
            font-size: 0.75rem;
            color: #999;
            margin-top: 4px;
        }
        .mensaje-input-area {
            padding: 20px;
            background: white;
            border-top: 2px solid #e9ecef;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
    <div class="mensajes-container">
        <!-- Lista de conversaciones -->
        <div class="conversaciones-lista">
            <div class="conversaciones-header">
                <h4 class="mb-0"><i class="bi bi-chat-dots"></i> Mensajes</h4>
            </div>
            <div style="flex: 1; overflow-y: auto;" id="conversacionesLista">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3">Cargando...</p>
                </div>
            </div>
        </div>

        <!-- Área de chat -->
        <div class="chat-area" id="chatArea">
            <div class="empty-state">
                <i class="bi bi-chat-square-text"></i>
                <h4>Selecciona una conversación</h4>
                <p>Elige un contacto para empezar a chatear</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let conversacionActual = <?php echo $chat_con; ?>;
        let nombreUrl = "<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>";
        let avatarUrlParam = "<?php echo isset($_GET['avatar']) ? htmlspecialchars($_GET['avatar']) : ''; ?>";
        let intervaloActualizacion;
        let usuarioActualInfo = { nombre: '', avatar: '' };

        $(document).ready(function() {
            cargarConversaciones();
            
            if(conversacionActual > 0) {
                // Si tenemos datos por URL, abrimos el chat directamente
                if(nombreUrl && nombreUrl !== '') {
                    abrirChat(conversacionActual, nombreUrl, avatarUrlParam);
                }
            }
        });

        function cargarConversaciones() {
            $.get('../api/mensajes.php', function(data) {
                if(data.success) {
                    let html = '';
                    
                    if(data.conversaciones.length === 0) {
                        html = '<div class="text-center py-5 text-muted"><i class="bi bi-inbox" style="font-size: 3rem;"></i><p class="mt-3">No tienes mensajes</p></div>';
                    } else {
                        data.conversaciones.forEach(function(conv) {
                            var avatar = conv.imagen_perfil ? 
                                '../uploads/' + conv.imagen_perfil : 
                                'https://ui-avatars.com/api/?name=' + encodeURIComponent(conv.nombre) + '&size=50&background=667eea&color=fff';
                            
                            var nombreSafe = conv.nombre.replace(/'/g, "\\'");
                            var avatarSafe = conv.imagen_perfil || '';
                            var activeClass = (conv.id_otro_usuario == conversacionActual) ? 'active' : '';
                            var badge = conv.no_leidos > 0 ? '<span class="badge-no-leidos">' + conv.no_leidos + '</span>' : '';
                            
                            html += '<div class="conversacion-item ' + activeClass + '" ' +
                                     'onclick="abrirChat(' + conv.id_otro_usuario + ', \'' + nombreSafe + '\', \'' + avatarSafe + '\')">' +
                                    '<img src="' + avatar + '" class="conversacion-avatar">' +
                                    '<div style="flex: 1;">' +
                                        '<div class="d-flex justify-content-between align-items-start">' +
                                            '<strong>' + conv.nombre + '</strong>' +
                                            badge +
                                        '</div>' +
                                        '<small class="text-muted text-truncate d-block">' + conv.ultimo_mensaje + '</small>' +
                                    '</div>' +
                                '</div>';
                            
                            if(conv.id_otro_usuario == conversacionActual && !usuarioActualInfo.nombre) {
                                abrirChat(conv.id_otro_usuario, conv.nombre, conv.imagen_perfil);
                            }
                        });
                    }
                    
                    $('#conversacionesLista').html(html);
                }
            });
        }

        function abrirChat(idUsuario, nombre, avatar) {
            conversacionActual = idUsuario;
            usuarioActualInfo = { nombre: nombre, avatar: avatar };
            
            if(intervaloActualizacion) {
                clearInterval(intervaloActualizacion);
            }
            
            $('.conversacion-item').removeClass('active');
            $('.conversacion-item').each(function() {
                if($(this).attr('onclick') && $(this).attr('onclick').includes(idUsuario)) {
                    $(this).addClass('active');
                }
            });
            
            var avatarUrl = avatar ? '../uploads/' + avatar : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(nombre) + '&size=50&background=667eea&color=fff';
            
            var layoutHtml = 
                '<div class="chat-header">' +
                    '<div class="d-flex align-items-center">' +
                        '<img src="' + avatarUrl + '" class="conversacion-avatar mr-3">' +
                        '<div>' +
                            '<h5 class="mb-0">' + nombre + '</h5>' +
                            '<small class="text-muted">' +
                                '<a href="perfil-publico.php?user=' + idUsuario + '" target="_blank">Ver perfil</a>' +
                            '</small>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="mensajes-contenedor" id="mensajesContenedor">' +
                    '<div class="text-center py-5">' +
                        '<div class="spinner-border text-primary" role="status"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="mensaje-input-area">' +
                    '<form onsubmit="enviarMensaje(event, ' + idUsuario + ')">' +
                        '<div class="input-group">' +
                            '<input type="text" class="form-control" id="mensajeInput" placeholder="Escribe un mensaje..." required autocomplete="off">' +
                            '<div class="input-group-append">' +
                                '<button class="btn btn-primary" type="submit">' +
                                    '<i class="bi bi-send"></i> Enviar' +
                                '</button>' +
                            '</div>' +
                        '</div>' +
                    '</form>' +
                '</div>';
            
            $('#chatArea').html(layoutHtml);
            
            cargarMensajes(idUsuario);
            
            intervaloActualizacion = setInterval(function() {
                cargarMensajes(idUsuario, false);
            }, 3000);
        }

        function cargarMensajes(idUsuario, scrollToBottom) {
            if (scrollToBottom === undefined) scrollToBottom = true;
            
            $.get('../api/mensajes.php?conversacion_con=' + idUsuario, function(data) {
                if(data.success) {
                    let html = '';
                    
                    if(data.mensajes.length === 0) {
                        html = '<div class="text-center py-5 text-muted"><i class="bi bi-chat" style="font-size: 3rem;"></i><p class="mt-3">No hay mensajes aún. ¡Envía el primero!</p></div>';
                    } else {
                        data.mensajes.forEach(function(msg) {
                            var fecha = new Date(msg.fecha_envio);
                             var fechaStr = fecha.toLocaleString('es-AR', { 
                                day: '2-digit', 
                                month: '2-digit', 
                                hour: '2-digit', 
                                minute: '2-digit' 
                            });
                            
                            var clasePropio = msg.es_mio ? 'propio' : '';
                            
                            html += '<div class="mensaje ' + clasePropio + '">' +
                                    '<div class="mensaje-bubble">' + msg.mensaje + '</div>' +
                                    '<div class="mensaje-fecha">' + fechaStr + '</div>' +
                                    '</div>';
                        });
                    }
                    
                    var contenedor = $('#mensajesContenedor');
                    var isScrolledToBottom = contenedor[0].scrollHeight - contenedor.scrollTop() <= contenedor.outerHeight() + 50;
                    
                    contenedor.html(html);
                    
                    if(scrollToBottom || isScrolledToBottom) {
                        contenedor.scrollTop(contenedor[0].scrollHeight);
                    }
                }
            });
        }

        function enviarMensaje(event, idDestinatario) {
            event.preventDefault();
            
            var input = $('#mensajeInput');
            var mensaje = input.val().trim();
            
            if(!mensaje) return;
            
            input.val('');
            input.focus();
            
            $.ajax({
                url: '../api/mensajes.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    id_destinatario: idDestinatario,
                    mensaje: mensaje
                }),
                success: function(data) {
                    if(data.success) {
                        cargarMensajes(idDestinatario, true);
                        cargarConversaciones();
                    } else {
                        alert('Error al enviar mensaje');
                        input.val(mensaje);
                    }
                },
                error: function() {
                     alert('Error de conexión');
                     input.val(mensaje);
                }
            });
        }
    </script>
</body>
</html>