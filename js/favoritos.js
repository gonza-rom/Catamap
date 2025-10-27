// Sistema de gestión de favoritos
const Favoritos = {
    // Verificar si un lugar está en favoritos
    esFavorito: function(idLugar) {
        return favoritosUsuario.includes(parseInt(idLugar));
    },

    // Agregar a favoritos
    agregar: async function(idLugar) {
        if (!usuarioLogueado) {
            this.mostrarNotificacion('Debes iniciar sesión para agregar favoritos', 'warning');
            setTimeout(() => {
                window.location.href = '../index.php';
            }, 2000);
            return false;
        }

        try {
            const response = await fetch('../api/favoritos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_lugar: idLugar
                })
            });

            const data = await response.json();

            if (data.success) {
                favoritosUsuario.push(parseInt(idLugar));
                this.actualizarUI(idLugar, true);
                this.cargarListaFavoritos();
                this.mostrarNotificacion('✅ Agregado a favoritos', 'success');
                
                // Actualizar en el mapa si existe la función global
                if (typeof window.actualizarFavoritoEnMapa === 'function') {
                    window.actualizarFavoritoEnMapa(idLugar, true);
                }
                
                return true;
            } else {
                this.mostrarNotificacion(data.message || 'Ya está en favoritos', 'warning');
                return false;
            }
        } catch (error) {
            console.error('Error al agregar favorito:', error);
            this.mostrarNotificacion('❌ Error al agregar a favoritos', 'error');
            return false;
        }
    },

    // Eliminar de favoritos
    eliminar: async function(idLugar) {
        try {
            const response = await fetch('../api/favoritos.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_lugar: idLugar
                })
            });

            const data = await response.json();

            if (data.success) {
                const index = favoritosUsuario.indexOf(parseInt(idLugar));
                if (index > -1) {
                    favoritosUsuario.splice(index, 1);
                }
                this.actualizarUI(idLugar, false);
                this.cargarListaFavoritos();
                this.mostrarNotificacion('🗑️ Eliminado de favoritos', 'success');
                
                // Actualizar en el mapa si existe la función global
                if (typeof window.actualizarFavoritoEnMapa === 'function') {
                    window.actualizarFavoritoEnMapa(idLugar, false);
                }
                
                return true;
            } else {
                this.mostrarNotificacion(data.message || 'Error al eliminar', 'error');
                return false;
            }
        } catch (error) {
            console.error('Error al eliminar favorito:', error);
            this.mostrarNotificacion('❌ Error al eliminar de favoritos', 'error');
            return false;
        }
    },

    // Toggle favorito
    toggle: async function(idLugar) {
        if (this.esFavorito(idLugar)) {
            return await this.eliminar(idLugar);
        } else {
            return await this.agregar(idLugar);
        }
    },

    // Actualizar UI de un botón específico
    actualizarUI: function(idLugar, esFavorito) {
        const botones = document.querySelectorAll(`[data-lugar-id="${idLugar}"] .favorito-btn, .favorito-btn[data-lugar-id="${idLugar}"]`);
        botones.forEach(boton => {
            if (esFavorito) {
                boton.classList.add('active');
                const icono = boton.querySelector('i');
                if (icono) {
                    icono.className = 'bi bi-star-fill';
                }
                boton.title = 'Quitar de favoritos';
            } else {
                boton.classList.remove('active');
                const icono = boton.querySelector('i');
                if (icono) {
                    icono.className = 'bi bi-star';
                }
                boton.title = 'Agregar a favoritos';
            }
        });
    },

    // Cargar lista de favoritos en el panel
    cargarListaFavoritos: async function() {
        if (!usuarioLogueado) {
            return;
        }

        try {
            const response = await fetch('../api/favoritos.php');
            const data = await response.json();

            if (data.success) {
                const container = document.getElementById('favoritosList');
                
                if (!container) {
                    console.warn('Container favoritosList no encontrado');
                    return;
                }
                
                if (data.data.length === 0) {
                    container.innerHTML = `
                        <div class="login-required">
                            <i class="bi bi-heart" style="font-size: 3rem; color: #ddd;"></i>
                            <h4>No tienes favoritos aún</h4>
                            <p>Explora el mapa y guarda tus lugares favoritos</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                data.data.forEach(lugar => {
                    const imagenUrl = lugar.imagen ? `../uploads/${lugar.imagen}` : '../img/placeholder.jpg';
                    html += `
                        <div class="favorito-item" data-lugar-id="${lugar.id_lugar}" onclick="Favoritos.irALugar(${lugar.lat}, ${lugar.lng}, ${lugar.id_lugar})">
                            <img src="${imagenUrl}" alt="${lugar.nombre}" onerror="this.src='../img/placeholder.jpg'">
                            <div class="favorito-item-info">
                                <h4>${lugar.nombre}</h4>
                                <p><i class="bi bi-geo-alt"></i> ${lugar.departamento || 'Catamarca'}</p>
                                <p><i class="bi bi-tag"></i> ${lugar.categoria || 'General'}</p>
                            </div>
                            <button class="favorito-btn active" 
                                    data-lugar-id="${lugar.id_lugar}"
                                    onclick="event.stopPropagation(); Favoritos.toggle(${lugar.id_lugar})"
                                    title="Quitar de favoritos">
                                <i class="bi bi-star-fill"></i>
                            </button>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }
        } catch (error) {
            console.error('Error al cargar favoritos:', error);
            const container = document.getElementById('favoritosList');
            if (container) {
                container.innerHTML = `
                    <div class="login-required">
                        <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
                        <h4>Error al cargar favoritos</h4>
                        <p>Por favor, intenta nuevamente más tarde</p>
                    </div>
                `;
            }
        }
    },

    // Ir a un lugar en el mapa
    irALugar: function(lat, lng, idLugar) {
        // Cerrar panel de favoritos
        const panel = document.getElementById('favoritosPanel');
        if (panel) {
            panel.classList.remove('open');
        }

        // Usar la función global del mapa
        if (typeof window.irALugarEnMapa === 'function') {
            window.irALugarEnMapa(lat, lng, idLugar);
        } else if (typeof map !== 'undefined') {
            map.setView([lat, lng], 16);
            
            setTimeout(() => {
                if (typeof marcadoresLugares !== 'undefined' && marcadoresLugares[idLugar]) {
                    marcadoresLugares[idLugar].openPopup();
                }
            }, 500);
        } else {
            console.warn('Mapa no disponible');
        }
    },

    // Crear botón de favorito para popup
    crearBotonFavorito: function(idLugar) {
        const esFav = this.esFavorito(idLugar);
        const clase = esFav ? 'active' : '';
        const icono = esFav ? 'bi-star-fill' : 'bi-star';
        const titulo = esFav ? 'Quitar de favoritos' : 'Agregar a favoritos';
        
        return `
            <button class="favorito-btn ${clase}" 
                    data-lugar-id="${idLugar}" 
                    onclick="event.stopPropagation(); Favoritos.toggle(${idLugar})"
                    title="${titulo}">
                <i class="bi ${icono}"></i>
            </button>
        `;
    },

    // Mostrar notificación
    mostrarNotificacion: function(mensaje, tipo) {
        // Crear contenedor de notificaciones si no existe
        let container = document.getElementById('notificaciones-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notificaciones-container';
            container.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }

        // Crear notificación
        const notificacion = document.createElement('div');
        notificacion.className = `notificacion notificacion-${tipo}`;
        notificacion.style.pointerEvents = 'auto';
        
        // Colores según tipo
        const colores = {
            success: '#28a745',
            error: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };

        // Iconos según tipo
        const iconos = {
            success: 'bi-check-circle-fill',
            error: 'bi-x-circle-fill',
            warning: 'bi-exclamation-triangle-fill',
            info: 'bi-info-circle-fill'
        };

        notificacion.style.cssText = `
            background: white;
            color: #333;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            border-left: 4px solid ${colores[tipo]};
            animation: slideIn 0.3s ease;
            pointer-events: auto;
        `;

        notificacion.innerHTML = `
            <i class="bi ${iconos[tipo]}" style="color: ${colores[tipo]}; font-size: 1.3rem;"></i>
            <span style="flex: 1;">${mensaje}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: #999; cursor: pointer; font-size: 1.2rem; padding: 0; line-height: 1;">
                <i class="bi bi-x"></i>
            </button>
        `;

        container.appendChild(notificacion);

        // Auto-eliminar después de 4 segundos
        setTimeout(() => {
            notificacion.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notificacion.remove(), 300);
        }, 4000);

        // Agregar estilos de animación si no existen
        if (!document.getElementById('notificaciones-styles')) {
            const style = document.createElement('style');
            style.id = 'notificaciones-styles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes slideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    },

    // Inicializar favoritos al cargar la página
    init: function() {
        if (usuarioLogueado) {
            this.cargarListaFavoritos();
            console.log('✅ Sistema de favoritos inicializado');
            console.log(`📊 Favoritos actuales: ${favoritosUsuario.length}`);
        } else {
            console.log('ℹ️ Usuario no autenticado - favoritos deshabilitados');
        }
    }
};

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof usuarioLogueado !== 'undefined' && usuarioLogueado) {
            Favoritos.init();
        }
    });
} else {
    if (typeof usuarioLogueado !== 'undefined' && usuarioLogueado) {
        Favoritos.init();
    }
}

// Hacer Favoritos accesible globalmente
window.Favoritos = Favoritos;

console.log('📦 Módulo de favoritos cargado correctamente');