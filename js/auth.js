// Configuración de la API
const API_URL = '/catamap/api';

// Objeto para manejar autenticación
const Auth = {
    // Guardar datos de usuario en localStorage
    guardarUsuario: function (userData) {
        localStorage.setItem('user', JSON.stringify(userData));
        localStorage.setItem('token', userData.token);
    },

    // Obtener datos de usuario
    obtenerUsuario: function () {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },

    // Obtener token
    obtenerToken: function () {
        return localStorage.getItem('token');
    },

    // Verificar si está autenticado
    estaAutenticado: function () {
        return this.obtenerToken() !== null;
    },

    // Limpiar datos de sesión
    limpiarSesion: function () {
        localStorage.removeItem('user');
        localStorage.removeItem('token');
    },

    // Registro de usuario
    registrar: async function (nombre, email, password, tipoUsuario) {
        try {
            const response = await fetch(`${API_URL}/register.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    nombre: nombre,
                    email: email,
                    password: password,
                    tipo_usuario: tipoUsuario
                })
            });

            const data = await response.json();

            if (data.success) {
                return { success: true, data: data.data, message: data.message };
            } else {
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Error en registro:', error);
            return { success: false, message: 'Error de conexión' };
        }
    },

    // Login de usuario
    login: async function (email, password) {
        try {
            const response = await fetch(`${API_URL}/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            });

            const data = await response.json();

            if (data.success) {
                this.guardarUsuario(data.data);
                return { success: true, data: data.data, message: data.message };
            } else {
                return { success: false, message: data.message };
            }
        } catch (error) {
            console.error('Error en login:', error);
            return { success: false, message: 'Error de conexión' };
        }
    },

    // Logout de usuario
    logout: async function () {
        try {
            const token = this.obtenerToken();

            await fetch(`${API_URL}/logout.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token
                })
            });

            this.limpiarSesion();
            return { success: true };
        } catch (error) {
            console.error('Error en logout:', error);
            this.limpiarSesion();
            return { success: true };
        }
    },

    // Redirigir si no está autenticado
    requiereLogin: function () {
        if (!this.estaAutenticado()) {
            window.location.href = '/login.html';
            return false;
        }
        return true;
    },

    // Redirigir si está autenticado
    requiereNoLogin: function () {
        if (this.estaAutenticado()) {
            window.location.href = '/index.html';
            return false;
        }
        return true;
    },

    // Obtener tipo de usuario
    obtenerTipoUsuario: function () {
        const user = this.obtenerUsuario();
        return user ? user.tipo_usuario : null;
    },

    // Verificar tipo de usuario
    esTipoUsuario: function (tipos) {
        const tipoActual = this.obtenerTipoUsuario();
        if (Array.isArray(tipos)) {
            return tipos.includes(tipoActual);
        }
        return tipoActual === tipos;
    }
};

// Función auxiliar para mostrar mensajes
function mostrarMensaje(mensaje, tipo = 'info') {
    const colores = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700'
    };

    const div = document.createElement('div');
    div.className = `fixed top-4 right-4 ${colores[tipo]} px-4 py-3 rounded border z-50 shadow-lg`;
    div.innerHTML = `
        <span class="block sm:inline">${mensaje}</span>
        <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.remove()">
            <svg class="fill-current h-6 w-6" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
            </svg>
        </span>
    `;

    document.body.appendChild(div);

    setTimeout(() => {
        div.remove();
    }, 5000);
}

// Exportar para uso global
window.Auth = Auth;
window.mostrarMensaje = mostrarMensaje;