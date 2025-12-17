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

// Función para validar email
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

// Función para validar contraseña fuerte
function validarPasswordFuerte(password) {
    const errores = [];

    if (password.length < 8) {
        errores.push('Debe tener al menos 8 caracteres');
    }
    if (!/[A-Z]/.test(password)) {
        errores.push('Debe contener al menos una mayúscula');
    }
    if (!/[a-z]/.test(password)) {
        errores.push('Debe contener al menos una minúscula');
    }
    if (!/[0-9]/.test(password)) {
        errores.push('Debe contener al menos un número');
    }

    return errores;
}

// Actualizar UI según estado de autenticación
function actualizarUI() {
    const usuario = Auth.obtenerUsuario();
    const authContainer = $('#authButtonContainer');

    if (usuario) {
        // Determinar prefijo de ruta (si estamos en pages/ o en root)
        const inPages = window.location.pathname.includes('/pages/');
        const prefix = inPages ? './' : './pages/';

        // Usuario autenticado
        authContainer.html(`
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> ${usuario.nombre}
                    <span id="badgeMensajesGlobal" class="badge badge-danger ml-1" style="display:none; font-size:0.7em;">0</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="${prefix}perfil.php">
                        <i class="bi bi-person"></i> Mi Perfil
                    </a>
                    <a class="dropdown-item d-flex justify-content-between align-items-center" href="${prefix}mensajes.php">
                        <div><i class="bi bi-chat-dots"></i> Mensajes</div>
                        <span id="badgeMensajesMenu" class="badge badge-danger" style="display:none">0</span>
                    </a>
                    ${usuario.tipo_usuario === 'administrador' || usuario.tipo_usuario === 'admin' ? `
                        <a class="dropdown-item" href="${prefix}admin.php">
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

        // Iniciar polling de mensajes
        actualizarBadgeMensajes();
        if (!window.pollingMensajes) {
            window.pollingMensajes = setInterval(actualizarBadgeMensajes, 10000); // Cada 10s
        }
    } else {
        if (window.pollingMensajes) {
            clearInterval(window.pollingMensajes);
            window.pollingMensajes = null;
        }

        // Usuario no autenticado
        authContainer.html(`
            <a class="btn btn-primary ml-xl-4" data-toggle="modal" data-target="#loginModal">
                Iniciar Sesión
            </a>
        `);
    }
}

function actualizarBadgeMensajes() {
    // Determinar ruta de API correcta
    const inPages = window.location.pathname.includes('/pages/');
    const apiUrl = inPages ? '../api/mensajes.php?count=1' : './api/mensajes.php?count=1';

    $.get(apiUrl, function (data) {
        if (data.success && data.total_no_leidos > 0) {
            $('#badgeMensajesGlobal').text(data.total_no_leidos).show();
            $('#badgeMensajesMenu').text(data.total_no_leidos).show();
        } else {
            $('#badgeMensajesGlobal').hide();
            $('#badgeMensajesMenu').hide();
        }
    }).fail(function () {
        // Silenciosamente fallar si no hay sesión o error
    });
}

// Función para toggle de visibilidad de contraseña
function togglePasswordVisibility(inputId, iconId) {
    const input = $(`#${inputId}`);
    const icon = $(`#${iconId}`);

    if (input.attr('type') === 'password') {
        input.attr('type', 'text');
        icon.removeClass('bi-eye').addClass('bi-eye-slash');
    } else {
        input.attr('type', 'password');
        icon.removeClass('bi-eye-slash').addClass('bi-eye');
    }
}

// Variables globales para el CAPTCHA
let captchaResultadoLogin = 0;
let captchaResultadoRegister = 0;

// Función para generar CAPTCHA matemático
function generarCaptcha(tipo) {
    const num1 = Math.floor(Math.random() * 10) + 1;
    const num2 = Math.floor(Math.random() * 10) + 1;
    const resultado = num1 + num2;

    if (tipo === 'login') {
        $('#captchaQuestionLogin').text(`${num1} + ${num2} =`);
        captchaResultadoLogin = resultado;
        $('#captchaLogin').val('');
    } else if (tipo === 'register') {
        $('#captchaQuestionRegister').text(`${num1} + ${num2} =`);
        captchaResultadoRegister = resultado;
        $('#captchaRegister').val('');
    }
}

// Inicializar al cargar la página
$(document).ready(function () {
    actualizarUI();

    // Generar CAPTCHA inicial
    generarCaptcha('login'); // Preparamos el del login por defecto

    // Alternar entre formularios de login y registro
    $('#showRegister').click(function (e) {
        e.preventDefault();
        $('#loginForm').addClass('d-none');
        $('#registerForm').removeClass('d-none');
        $('#modalTitle').text('Crea tu cuenta en Catamap');
        generarCaptcha('register'); // Nuevo captcha para registro
    });

    $('#showLogin').click(function (e) {
        e.preventDefault();
        $('#registerForm').addClass('d-none');
        $('#loginForm').removeClass('d-none');
        $('#modalTitle').text('Inicia sesión en tu cuenta');
        generarCaptcha('login'); // Nuevo captcha para login
    });

    // Validación en tiempo real del email de login
    $('#loginEmail').on('blur', function () {
        const email = $(this).val().trim();
        if (email && !validarEmail(email)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Email inválido</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación en tiempo real del email de registro
    $('#registerEmail').on('blur', function () {
        const email = $(this).val().trim();
        if (email && !validarEmail(email)) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Email inválido</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validación de contraseña en registro
    $('#registerPassword').on('input', function () {
        const password = $(this).val();
        const errores = validarPasswordFuerte(password);

        if (errores.length > 0 && password.length > 0) {
            $(this).addClass('is-invalid');
            const mensajeError = errores.join('<br>');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after(`<div class="invalid-feedback">${mensajeError}</div>`);
            } else {
                $(this).next('.invalid-feedback').html(mensajeError);
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Validar que las contraseñas coincidan
    $('#registerConfirmPassword').on('input', function () {
        const password = $('#registerPassword').val();
        const confirmPassword = $(this).val();

        if (confirmPassword && password !== confirmPassword) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Las contraseñas no coinciden</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Toggle password visibility - Login
    $(document).on('click', '#toggleLoginPassword', function () {
        togglePasswordVisibility('loginPassword', 'toggleLoginPasswordIcon');
    });

    // Toggle password visibility - Register
    $(document).on('click', '#toggleRegisterPassword', function () {
        togglePasswordVisibility('registerPassword', 'toggleRegisterPasswordIcon');
    });

    // Toggle password visibility - Confirm Password
    $(document).on('click', '#toggleConfirmPassword', function () {
        togglePasswordVisibility('registerConfirmPassword', 'toggleConfirmPasswordIcon');
    });

    // Manejar el formulario de login
    $('#loginForm').submit(async function (e) {
        e.preventDefault();

        const btnLogin = $('#btnLogin');
        const email = $('#loginEmail').val().trim();
        const password = $('#loginPassword').val();
        const captchaInput = parseInt($('#captchaLogin').val());

        // Validación de CAPTCHA
        if (captchaInput !== captchaResultadoLogin) {
            $('#captchaLogin').addClass('is-invalid');
            mostrarMensaje('La verificación de seguridad es incorrecta', 'error');
            generarCaptcha('login'); // Regenerar captcha
            return;
        } else {
            $('#captchaLogin').removeClass('is-invalid');
        }

        // Validaciones antes de enviar
        if (!email) {
            mostrarMensaje('El email es requerido', 'error');
            return;
        }

        if (!validarEmail(email)) {
            mostrarMensaje('Email inválido', 'error');
            return;
        }

        if (!password) {
            mostrarMensaje('La contraseña es requerida', 'error');
            return;
        }

        btnLogin.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Iniciando sesión...');

        try {
            const result = await Auth.login(email, password);

            if (result.success) {
                mostrarMensaje('¡Inicio de sesión exitoso!', 'success');
                $('#loginModal').modal('hide');

                if ($('#authButtonContainer').length === 0) {
                    window.location.reload();
                } else {
                    actualizarUI();
                }

                // Limpiar formulario
                $('#loginForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
            } else {
                mostrarMensaje(result.message || 'Email o contraseña incorrectos', 'error');
                generarCaptcha('login'); // Regenerar por seguridad
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error al iniciar sesión. Intenta nuevamente.', 'error');
        } finally {
            btnLogin.prop('disabled', false).html('Iniciar Sesión');
        }
    });

    // Manejar el formulario de registro
    $('#registerForm').submit(async function (e) {
        e.preventDefault();

        const btnRegister = $('#btnRegister');
        const nombre = $('#registerName').val().trim();
        const email = $('#registerEmail').val().trim();
        const password = $('#registerPassword').val();
        const confirmPassword = $('#registerConfirmPassword').val();
        const captchaInput = parseInt($('#captchaRegister').val());

        // Limpiar mensajes de error previos
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Validación de CAPTCHA
        if (captchaInput !== captchaResultadoRegister) {
            $('#captchaRegister').addClass('is-invalid');
            mostrarMensaje('La verificación de seguridad es incorrecta', 'error');
            generarCaptcha('register'); // Regenerar captcha
            return;
        }

        // Validaciones
        if (!nombre) {
            $('#registerName').addClass('is-invalid').after('<div class="invalid-feedback">El nombre es requerido</div>');
            mostrarMensaje('El nombre es requerido', 'error');
            return;
        }

        if (nombre.length < 3) {
            $('#registerName').addClass('is-invalid').after('<div class="invalid-feedback">El nombre debe tener al menos 3 caracteres</div>');
            mostrarMensaje('El nombre debe tener al menos 3 caracteres', 'error');
            return;
        }

        if (!email) {
            $('#registerEmail').addClass('is-invalid').after('<div class="invalid-feedback">El email es requerido</div>');
            mostrarMensaje('El email es requerido', 'error');
            return;
        }

        if (!validarEmail(email)) {
            $('#registerEmail').addClass('is-invalid').after('<div class="invalid-feedback">Email inválido</div>');
            mostrarMensaje('El email no es válido', 'error');
            return;
        }

        if (!password) {
            $('#registerPassword').addClass('is-invalid').after('<div class="invalid-feedback">La contraseña es requerida</div>');
            mostrarMensaje('La contraseña es requerida', 'error');
            return;
        }

        // Validar contraseña fuerte
        const erroresPassword = validarPasswordFuerte(password);
        if (erroresPassword.length > 0) {
            $('#registerPassword').addClass('is-invalid').after(`<div class="invalid-feedback">${erroresPassword.join('<br>')}</div>`);
            mostrarMensaje('La contraseña no cumple los requisitos de seguridad', 'error');
            return;
        }

        if (password !== confirmPassword) {
            $('#registerConfirmPassword').addClass('is-invalid').after('<div class="invalid-feedback">Las contraseñas no coinciden</div>');
            mostrarMensaje('Las contraseñas no coinciden', 'error');
            return;
        }

        btnRegister.prop('disabled', true).html('<span class="spinner-border spinner-border-sm mr-2"></span>Creando cuenta...');

        try {
            // Siempre registrar como 'usuario'
            const result = await Auth.registrar(nombre, email, password, 'usuario');

            if (result.success) {
                mostrarMensaje('¡Cuenta creada exitosamente! Iniciando sesión...', 'success');

                // Auto-login después del registro
                setTimeout(async () => {
                    const loginResult = await Auth.login(email, password);
                    if (loginResult.success) {
                        $('#loginModal').modal('hide');

                        if ($('#authButtonContainer').length === 0) {
                            window.location.reload();
                        } else {
                            actualizarUI();
                        }
                        $('#registerForm')[0].reset();
                        $('.is-invalid').removeClass('is-invalid');
                        $('.invalid-feedback').remove();
                    }
                }, 1000);
            } else {
                mostrarMensaje(result.message || 'Error al crear la cuenta', 'error');
                btnRegister.prop('disabled', false).html('Crear Cuenta');
                generarCaptcha('register'); // Regenerar si falla
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error al crear la cuenta. Intenta nuevamente.', 'error');
            btnRegister.prop('disabled', false).html('Crear Cuenta');
        }
    });

    // Manejar el logout
    $(document).on('click', '#btnLogout', async function (e) {
        e.preventDefault();

        try {
            await Auth.logout();
            mostrarMensaje('Sesión cerrada correctamente', 'success');
            actualizarUI();

            // Redirigir al inicio
            setTimeout(() => {
                window.location.href = '/catamap/index.php';
            }, 1000);
        } catch (error) {
            console.error('Error:', error);
            mostrarMensaje('Error al cerrar sesión', 'error');
        }
    });

    // Limpiar formularios al cerrar el modal
    $('#loginModal').on('hidden.bs.modal', function () {
        $('#loginForm')[0].reset();
        $('#registerForm')[0].reset();
        $('#registerForm').addClass('d-none');
        $('#loginForm').removeClass('d-none');
        $('#modalTitle').text('Inicia sesión en tu cuenta');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        generarCaptcha('login'); // Resetear captcha
    });
});