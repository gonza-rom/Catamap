// Función para mostrar notificaciones con jQuery
        function mostrarMensaje(mensaje, tipo = 'info') {
            const colores = {
                success: 'alert-success',
                error: 'alert-danger',
                warning: 'alert-warning',
                info: 'alert-info'
            };

            const alerta = $(`
                <div class="alert ${colores[tipo]} alert-dismissible fade show" role="alert" style="position: fixed; top: 80px; right: 20px; z-index: 9999; min-width: 300px;">
                    ${mensaje}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            `);

            $('body').append(alerta);
            
            setTimeout(() => {
                alerta.alert('close');
            }, 5000);
        }

        // Actualizar UI según estado de autenticación
        function actualizarUI() {
            const usuario = Auth.obtenerUsuario();
            const authContainer = $('#authButtonContainer');

            if (usuario) {
                // Usuario autenticado
                authContainer.html(`
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> ${usuario.nombre}
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="./pages/perfil.php">
                                <i class="bi bi-person"></i> Mi Perfil
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="./pages/sugerir-lugar.php">
                                <i class="bi bi-plus-circle"></i> Sugerir Lugar
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="./pages/configuracion-privacidad.php">
                                <i class="bi bi-shield-lock"></i> Privacidad
                            </a>
                            ${usuario.tipo_usuario === 'emprendedor' ? `
                                <a class="dropdown-item" href="./pages/mis-emprendimientos.php">
                                    <i class="bi bi-briefcase"></i> Mis Emprendimientos
                                </a>
                            ` : ''}
                            ${usuario.tipo_usuario === 'administrador' ? `
                                <a class="dropdown-item" href="./pages/admin.php">
                                    <i class="bi bi-gear"></i> Administración
                                </a>
                            ` : ''}
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" id="btnLogout">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a>

                        </div>
                    </li>
                `);
            } else {
                // Usuario no autenticado
                authContainer.html(`
                    <a class="btn btn-primary ml-xl-4" data-toggle="modal" data-target="#loginModal">
                        Iniciar Sesión
                    </a>
                `);
            }
        }

        // Inicializar al cargar la página
        $(document).ready(function() {
            actualizarUI();

            // Alternar entre formularios de login y registro
            $('#showRegister').click(function(e) {
                e.preventDefault();
                $('#loginForm').addClass('d-none');
                $('#registerForm').removeClass('d-none');
                $('#modalTitle').text('Crea tu cuenta en Catamap');
            });

            $('#showLogin').click(function(e) {
                e.preventDefault();
                $('#registerForm').addClass('d-none');
                $('#loginForm').removeClass('d-none');
                $('#modalTitle').text('Inicia sesión en tu cuenta');
            });

            // Manejar el formulario de login
            $('#loginForm').submit(async function(e) {
                e.preventDefault();
                
                const btnLogin = $('#btnLogin');
                const email = $('#loginEmail').val();
                const password = $('#loginPassword').val();
                
                btnLogin.prop('disabled', true).text('Iniciando sesión...');
                
                try {
                    const result = await Auth.login(email, password);
                    
                    if (result.success) {
                        mostrarMensaje('¡Inicio de sesión exitoso!', 'success');
                        $('#loginModal').modal('hide');
                        actualizarUI();
                        
                        // Limpiar formulario
                        $('#loginForm')[0].reset();
                    } else {
                        mostrarMensaje(result.message, 'error');
                    }
                } catch (error) {
                    mostrarMensaje('Error al iniciar sesión', 'error');
                } finally {
                    btnLogin.prop('disabled', false).text('Iniciar Sesión');
                }
            });

            // Manejar el formulario de registro
            $('#registerForm').submit(async function(e) {
                e.preventDefault();
                
                const btnRegister = $('#btnRegister');
                const nombre = $('#registerName').val();
                const email = $('#registerEmail').val();
                const password = $('#registerPassword').val();
                const confirmPassword = $('#registerConfirmPassword').val();
                const tipoUsuario = $('#tipoUsuario').val();
                
                // Validar que las contraseñas coincidan
                if (password !== confirmPassword) {
                    mostrarMensaje('Las contraseñas no coinciden', 'error');
                    return;
                }
                
                // Validar tipo de usuario
                if (!tipoUsuario) {
                    mostrarMensaje('Selecciona un tipo de usuario', 'error');
                    return;
                }
                
                btnRegister.prop('disabled', true).text('Creando cuenta...');
                
                try {
                    const result = await Auth.registrar(nombre, email, password, tipoUsuario);
                    
                    if (result.success) {
                        mostrarMensaje('¡Cuenta creada exitosamente!', 'success');
                        
                        // Auto-login después del registro
                        setTimeout(async () => {
                            const loginResult = await Auth.login(email, password);
                            if (loginResult.success) {
                                $('#loginModal').modal('hide');
                                actualizarUI();
                                $('#registerForm')[0].reset();
                            }
                        }, 1000);
                    } else {
                        mostrarMensaje(result.message, 'error');
                        btnRegister.prop('disabled', false).text('Crear Cuenta');
                    }
                } catch (error) {
                    mostrarMensaje('Error al crear la cuenta', 'error');
                    btnRegister.prop('disabled', false).text('Crear Cuenta');
                }
            });

            // Manejar el logout
            $(document).on('click', '#btnLogout', async function(e) {
                e.preventDefault();
                
                try {
                    await Auth.logout();
                    mostrarMensaje('Sesión cerrada correctamente', 'success');
                    actualizarUI();
                } catch (error) {
                    mostrarMensaje('Error al cerrar sesión', 'error');
                }
            });

            // Limpiar formularios al cerrar el modal
            $('#loginModal').on('hidden.bs.modal', function() {
                $('#loginForm')[0].reset();
                $('#registerForm')[0].reset();
                $('#registerForm').addClass('d-none');
                $('#loginForm').removeClass('d-none');
                $('#modalTitle').text('Inicia sesión en tu cuenta');
            });
        });