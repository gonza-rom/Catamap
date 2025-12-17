// Admin Panel JavaScript
$(document).ready(function () {
    // Navigation
    $('.admin-nav-item[data-section]').click(function (e) {
        e.preventDefault();
        const section = $(this).data('section');

        // Update active nav item
        $('.admin-nav-item').removeClass('active');
        $(this).addClass('active');

        // Show corresponding section
        $('.admin-section').removeClass('active');
        $(`#${section}-section`).addClass('active');

        // Load section data
        loadSectionData(section);
    });

    // Load dashboard on page load
    loadDashboard();

    // Filter listeners
    $('#filter-usuario-busqueda, #filter-usuario-rol, #filter-usuario-estado').on('change keyup', debounce(loadUsuarios, 500));
    $('#filter-lugar-busqueda, #filter-lugar-estado').on('change keyup', debounce(loadLugares, 500));
    $('#filter-comentario-estado').on('change', loadComentarios);
});

// Load section data
function loadSectionData(section) {
    switch (section) {
        case 'dashboard':
            loadDashboard();
            break;
        case 'usuarios':
            loadUsuarios();
            break;
        case 'lugares':
            loadLugares();
            break;
        case 'sugerencias':
            loadSugerencias();
            break;
        case 'comentarios':
            loadComentarios();
            break;
        case 'categorias':
            loadCategorias();
            break;
        case 'departamentos':
            loadDepartamentos();
            break;
    }
}

// ============ DASHBOARD ============
function loadDashboard() {
    $.ajax({
        url: '../api/admin/estadisticas.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                renderStats(response.data);
                renderActividadReciente(response.data.actividad_reciente);
                renderUsuariosActivos(response.data.usuarios_activos);
            }
        },
        error: function () {
            showError('Error al cargar estadísticas');
        }
    });
}

function renderStats(data) {
    const statsHTML = `
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">${data.usuarios.total}</div>
                    <div class="stat-card-label">Total Usuarios</div>
                </div>
                <div class="stat-card-icon primary">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <small class="text-muted">
                ${data.usuarios.activos} activos | ${data.usuarios.suspendidos} suspendidos
            </small>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">${data.lugares.total}</div>
                    <div class="stat-card-label">Lugares Turísticos</div>
                </div>
                <div class="stat-card-icon success">
                    <i class="bi bi-geo-alt"></i>
                </div>
            </div>
            <small class="text-muted">
                ${data.lugares.aprobados} aprobados | ${data.lugares.pendientes} pendientes
            </small>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">${data.lugares_sugeridos.total}</div>
                    <div class="stat-card-label">Sugerencias Pendientes</div>
                </div>
                <div class="stat-card-icon warning">
                    <i class="bi bi-lightbulb"></i>
                </div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-value">${data.comentarios.total}</div>
                    <div class="stat-card-label">Comentarios</div>
                </div>
                <div class="stat-card-icon danger">
                    <i class="bi bi-chat-dots"></i>
                </div>
            </div>
            <small class="text-muted">
                ${data.comentarios.pendientes} pendientes de moderación
            </small>
        </div>
    `;

    $('#stats-container').html(statsHTML);
}

function renderActividadReciente(actividad) {
    if (!actividad || actividad.length === 0) {
        $('#actividad-reciente').html('<p class="text-muted">No hay actividad reciente</p>');
        return;
    }

    let html = '<div class="list-group">';
    actividad.forEach(item => {
        const icon = item.tipo === 'usuario' ? 'person-plus' :
            item.tipo === 'lugar' ? 'geo-alt' : 'chat';
        html += `
            <div class="list-group-item">
                <i class="bi bi-${icon} mr-2"></i>
                ${item.descripcion}
                <small class="text-muted d-block">${formatDate(item.fecha)}</small>
            </div>
        `;
    });
    html += '</div>';

    $('#actividad-reciente').html(html);
}

function renderUsuariosActivos(usuarios) {
    if (!usuarios || usuarios.length === 0) {
        $('#usuarios-activos').html('<p class="text-muted">No hay datos</p>');
        return;
    }

    let html = '<div class="list-group">';
    usuarios.forEach(user => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <strong>${user.nombre}</strong>
                    <small class="d-block text-muted">${user.total_comentarios} comentarios</small>
                </div>
                <span class="badge badge-${getRolBadgeClass(user.rol)}">${user.rol}</span>
            </div>
        `;
    });
    html += '</div>';

    $('#usuarios-activos').html(html);
}

// ============ USUARIOS ============
function loadUsuarios(page = 1) {
    const busqueda = $('#filter-usuario-busqueda').val();
    const rol = $('#filter-usuario-rol').val();
    const estado = $('#filter-usuario-estado').val();

    $.ajax({
        url: '../api/admin/usuarios.php',
        method: 'GET',
        data: { busqueda, rol, estado, page, limit: 20 },
        success: function (response) {
            if (response.success) {
                renderUsuariosTable(response.data, response.pagination);
            }
        },
        error: function () {
            showError('Error al cargar usuarios');
        }
    });
}

function renderUsuariosTable(usuarios, pagination) {
    if (usuarios.length === 0) {
        $('#usuarios-table-container').html('<p class="text-muted">No se encontraron usuarios</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    usuarios.forEach(user => {
        html += `
            <tr>
                <td>${user.id}</td>
                <td>${user.nombre}</td>
                <td>${user.email}</td>
                <td><span class="badge badge-${getRolBadgeClass(user.rol)}">${user.rol}</span></td>
                <td><span class="badge badge-${getEstadoBadgeClass(user.estado)}">${user.estado}</span></td>
                <td>${formatDate(user.fecha_registro)}</td>
                <td>
                    <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="editarUsuario(${user.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="eliminarUsuario(${user.id}, '${user.nombre}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    html += renderPagination(pagination, 'loadUsuarios');

    $('#usuarios-table-container').html(html);
}

function editarUsuario(id) {
    // Get user data
    $.ajax({
        url: '../api/admin/usuarios.php',
        method: 'GET',
        data: { id },
        success: function (response) {
            if (response.success && response.data.length > 0) {
                const user = response.data[0];
                showEditarUsuarioModal(user);
            }
        }
    });
}

function showEditarUsuarioModal(user) {
    const modalHTML = `
        <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarUsuarioForm">
                            <input type="hidden" id="edit-user-id" value="${user.id}">
                            
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" class="form-control" id="edit-user-nombre" value="${user.nombre}">
                            </div>
                            
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" id="edit-user-email" value="${user.email}">
                            </div>
                            
                            <div class="form-group">
                                <label>Rol</label>
                                <select class="form-control" id="edit-user-rol">
                                    <option value="usuario" ${user.rol === 'usuario' ? 'selected' : ''}>Usuario</option>
                                    <option value="emprendedor" ${user.rol === 'emprendedor' ? 'selected' : ''}>Emprendedor</option>
                                    <option value="admin" ${user.rol === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" id="edit-user-estado">
                                    <option value="activo" ${user.estado === 'activo' ? 'selected' : ''}>Activo</option>
                                    <option value="suspendido" ${user.estado === 'suspendido' ? 'selected' : ''}>Suspendido</option>
                                    <option value="inactivo" ${user.estado === 'inactivo' ? 'selected' : ''}>Inactivo</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarUsuario()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#editarUsuarioModal').modal('show');
    $('#editarUsuarioModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarUsuario() {
    const data = {
        id: $('#edit-user-id').val(),
        nombre: $('#edit-user-nombre').val(),
        email: $('#edit-user-email').val(),
        rol: $('#edit-user-rol').val(),
        estado: $('#edit-user-estado').val()
    };

    $.ajax({
        url: '../api/admin/usuarios.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                $('#editarUsuarioModal').modal('hide');
                showSuccess('Usuario actualizado correctamente');
                loadUsuarios();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al actualizar usuario');
        }
    });
}

function eliminarUsuario(id, nombre) {
    if (!confirm(`¿Estás seguro de eliminar al usuario "${nombre}"?`)) return;

    $.ajax({
        url: '../api/admin/usuarios.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Usuario eliminado correctamente');
                loadUsuarios();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al eliminar usuario');
        }
    });
}

// ============ LUGARES ============
function loadLugares(page = 1) {
    const busqueda = $('#filter-lugar-busqueda').val();
    const estado = $('#filter-lugar-estado').val();

    $.ajax({
        url: '../api/admin/lugares.php',
        method: 'GET',
        data: { busqueda, estado, page, limit: 20 },
        success: function (response) {
            if (response.success) {
                saveLugaresState(response.data); // FIX: Save state for editing
                renderLugaresTable(response.data, response.pagination);
            }
        },
        error: function () {
            showError('Error al cargar lugares');
        }
    });
}

// Helper: Global state for lugares
function saveLugaresState(lugares) {
    window.currentLugares = lugares;
}

function renderLugaresTable(lugares, pagination) {
    if (lugares.length === 0) {
        $('#lugares-table-container').html('<p class="text-muted">No se encontraron lugares</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Departamento</th>
                    <th>Estado</th>
                    <th>Favoritos</th>
                    <th>Comentarios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    lugares.forEach(lugar => {
        html += `
            <tr>
                <td>${lugar.id}</td>
                <td>${lugar.nombre}</td>
                <td>${lugar.categoria_nombre || '-'}</td>
                <td>${lugar.departamento_nombre || '-'}</td>
                <td><span class="badge badge-${getEstadoBadgeClass(lugar.estado)}">${lugar.estado}</span></td>
                <td>${lugar.total_favoritos}</td>
                <td>${lugar.total_comentarios}</td>
                <td>
                    <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="editarLugar(${lugar.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="eliminarLugar(${lugar.id}, '${lugar.nombre}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    html += renderPagination(pagination, 'loadLugares');

    $('#lugares-table-container').html(html);
}

function eliminarLugar(id, nombre) {
    if (!confirm(`¿Estás seguro de eliminar "${nombre}"? Esto también eliminará todos los favoritos y comentarios relacionados.`)) return;

    $.ajax({
        url: '../api/admin/lugares.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Lugar eliminado correctamente');
                loadLugares();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al eliminar lugar');
        }
    });
}

// ============ SUGERENCIAS ============
function loadSugerencias() {
    $.ajax({
        url: '../api/admin/lugares-sugeridos.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                renderSugerenciasTable(response.data);
            }
        },
        error: function () {
            showError('Error al cargar sugerencias');
        }
    });
}

function renderSugerenciasTable(sugerencias) {
    if (sugerencias.length === 0) {
        $('#sugerencias-table-container').html('<p class="text-muted">No hay sugerencias pendientes</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Categoría</th>
                    <th>Departamento</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    sugerencias.forEach(sug => {
        html += `
            <tr>
                <td>${sug.id}</td>
                <td>${sug.nombre}</td>
                <td>${sug.usuario_nombre || '-'}</td>
                <td>${sug.categoria_nombre || '-'}</td>
                <td>${sug.departamento_nombre || '-'}</td>
                <td>${formatDate(sug.fecha_sugerido)}</td>
                <td>
                    <button class="btn-admin btn-admin-sm btn-admin-success" onclick="aprobarSugerencia(${sug.id})">
                        <i class="bi bi-check-circle"></i> Aprobar
                    </button>
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="rechazarSugerencia(${sug.id})">
                        <i class="bi bi-x-circle"></i> Rechazar
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';

    $('#sugerencias-table-container').html(html);
}

function aprobarSugerencia(id) {
    if (!confirm('¿Aprobar esta sugerencia y convertirla en lugar turístico?')) return;

    $.ajax({
        url: '../api/admin/lugares-sugeridos.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Sugerencia aprobada correctamente');
                loadSugerencias();
                loadDashboard(); // Refresh stats
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al aprobar sugerencia');
        }
    });
}

function rechazarSugerencia(id) {
    if (!confirm('¿Rechazar esta sugerencia?')) return;

    $.ajax({
        url: '../api/admin/lugares-sugeridos.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id, rechazar: true }),
        success: function (response) {
            if (response.success) {
                showSuccess('Sugerencia rechazada');
                loadSugerencias();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al rechazar sugerencia');
        }
    });
}

// ============ COMENTARIOS ============
function loadComentarios(page = 1) {
    const estado = $('#filter-comentario-estado').val();

    $.ajax({
        url: '../api/admin/comentarios.php',
        method: 'GET',
        data: { estado, page, limit: 20 },
        success: function (response) {
            if (response.success) {
                renderComentariosTable(response.data, response.pagination);
            }
        },
        error: function () {
            showError('Error al cargar comentarios');
        }
    });
}

function renderComentariosTable(comentarios, pagination) {
    if (comentarios.length === 0) {
        $('#comentarios-table-container').html('<p class="text-muted">No se encontraron comentarios</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Lugar</th>
                    <th>Comentario</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    comentarios.forEach(com => {
        html += `
            <tr>
                <td>${com.id}</td>
                <td>${com.usuario_nombre || '-'}</td>
                <td>${com.lugar_nombre || '-'}</td>
                <td>${com.comentario.substring(0, 50)}...</td>
                <td><span class="badge badge-${getEstadoBadgeClass(com.estado)}">${com.estado}</span></td>
                <td>${formatDate(com.fecha_creacion)}</td>
                <td>
                    ${com.estado !== 'aprobado' ? `
                        <button class="btn-admin btn-admin-sm btn-admin-success" onclick="cambiarEstadoComentario(${com.id}, 'aprobado')">
                            <i class="bi bi-check"></i>
                        </button>
                    ` : ''}
                    ${com.estado !== 'rechazado' ? `
                        <button class="btn-admin btn-admin-sm btn-admin-warning" onclick="cambiarEstadoComentario(${com.id}, 'rechazado')">
                            <i class="bi bi-x"></i>
                        </button>
                    ` : ''}
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="eliminarComentario(${com.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    html += renderPagination(pagination, 'loadComentarios');

    $('#comentarios-table-container').html(html);
}

function cambiarEstadoComentario(id, estado) {
    $.ajax({
        url: '../api/admin/comentarios.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ id, estado }),
        success: function (response) {
            if (response.success) {
                showSuccess('Comentario actualizado');
                loadComentarios();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al actualizar comentario');
        }
    });
}

function eliminarComentario(id) {
    if (!confirm('¿Eliminar este comentario?')) return;

    $.ajax({
        url: '../api/admin/comentarios.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Comentario eliminado');
                loadComentarios();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al eliminar comentario');
        }
    });
}

// ============ CATEGORIAS ============
function loadCategorias() {
    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                renderCategoriasTable(response.data);
            }
        },
        error: function () {
            showError('Error al cargar categorías');
        }
    });
}

function renderCategoriasTable(categorias) {
    if (categorias.length === 0) {
        $('#categorias-table-container').html('<p class="text-muted">No hay categorías</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Icono</th>
                    <th>Lugares</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    categorias.forEach(cat => {
        // FIX: Handle id key
        const catId = cat.id_categoria || cat.id;
        // FIX: Render icon HTML
        const iconDisplay = cat.icono ? `<i class="${cat.icono} mr-2"></i> ${cat.icono}` : '-';

        html += `
            <tr>
                <td>${catId}</td>
                <td>${cat.nombre}</td>
                <td>${cat.descripcion || '-'}</td>
                <td>${iconDisplay}</td>
                <td>${cat.total_lugares}</td>
                <td>
                    <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="editarCategoria(${catId})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="eliminarCategoria(${catId}, '${cat.nombre}', ${cat.total_lugares})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';

    $('#categorias-table-container').html(html);
}

function eliminarCategoria(id, nombre, totalLugares) {
    if (totalLugares > 0) {
        alert(`No se puede eliminar "${nombre}" porque tiene ${totalLugares} lugares asociados.`);
        return;
    }

    if (!confirm(`¿Eliminar la categoría "${nombre}"?`)) return;

    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Categoría eliminada');
                loadCategorias();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al eliminar categoría');
        }
    });
}

// ============ DEPARTAMENTOS ============
function loadDepartamentos() {
    $.ajax({
        url: '../api/admin/departamentos.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                window.currentDepartamentos = response.data; // FIX: Save global state
                renderDepartamentosTable(response.data);
            }
        },
        error: function () {
            showError('Error al cargar departamentos');
        }
    });
}

function renderDepartamentosTable(departamentos) {
    if (departamentos.length === 0) {
        $('#departamentos-table-container').html('<p class="text-muted">No hay departamentos</p>');
        return;
    }

    let html = `
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Lugares</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    departamentos.forEach(dep => {
        html += `
            <tr>
                <td>${dep.id}</td>
                <td>${dep.nombre}</td>
                <td>${dep.total_lugares}</td>
                <td>
                    <button class="btn-admin btn-admin-sm btn-admin-primary" onclick="editarDepartamento(${dep.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn-admin btn-admin-sm btn-admin-danger" onclick="eliminarDepartamento(${dep.id}, '${dep.nombre}', ${dep.total_lugares})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table>';

    $('#departamentos-table-container').html(html);
}

function eliminarDepartamento(id, nombre, totalLugares) {
    if (totalLugares > 0) {
        alert(`No se puede eliminar "${nombre}" porque tiene ${totalLugares} lugares asociados.`);
        return;
    }

    if (!confirm(`¿Eliminar el departamento "${nombre}"?`)) return;

    $.ajax({
        url: '../api/admin/departamentos.php',
        method: 'DELETE',
        contentType: 'application/json',
        data: JSON.stringify({ id }),
        success: function (response) {
            if (response.success) {
                showSuccess('Departamento eliminado');
                loadDepartamentos();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al eliminar departamento');
        }
    });
}

// ============ REFRESH FUNCTIONS ============
function refreshUsuarios() { loadUsuarios(); }
function refreshLugares() { loadLugares(); }
function refreshSugerencias() { loadSugerencias(); }
function refreshComentarios() { loadComentarios(); }

// ============ UTILITY FUNCTIONS ============
function renderPagination(pagination, loadFunction) {
    if (pagination.total_pages <= 1) return '';

    let html = '<div class="admin-pagination">';

    // Previous button
    html += `<button ${pagination.page === 1 ? 'disabled' : ''} onclick="${loadFunction}(${pagination.page - 1})">
        <i class="bi bi-chevron-left"></i>
    </button>`;

    // Page numbers
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="active">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.page) <= 2) {
            html += `<button onclick="${loadFunction}(${i})">${i}</button>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += `<button disabled>...</button>`;
        }
    }

    // Next button
    html += `<button ${pagination.page === pagination.total_pages ? 'disabled' : ''} onclick="${loadFunction}(${pagination.page + 1})">
        <i class="bi bi-chevron-right"></i>
    </button>`;

    html += '</div>';
    return html;
}

function getRolBadgeClass(rol) {
    switch (rol) {
        case 'admin': return 'danger';
        case 'emprendedor': return 'primary';
        default: return 'info';
    }
}

function getEstadoBadgeClass(estado) {
    switch (estado) {
        case 'activo':
        case 'aprobado': return 'success';
        case 'pendiente': return 'warning';
        case 'suspendido':
        case 'rechazado':
        case 'inactivo': return 'danger';
        default: return 'info';
    }
}

function formatDate(dateString) {
    if (!dateString) return '-';
    // FIX: Handle dates that might be in different format or timezone
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString; // Fallback
    return date.toLocaleDateString('es-AR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert('Error: ' + message);
}

// ============ NEW MODAL FUNCTIONS ============

// --- CATEGORIAS ---

function showCrearCategoriaModal() {
    const modalHTML = `
        <div class="modal fade" id="crearCategoriaModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Categoría</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="crearCategoriaForm">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" class="form-control" id="new-cat-nombre" required>
                            </div>
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea class="form-control" id="new-cat-descripcion"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Icono (Clase FontAwesome/Bootstrap)</label>
                                <input type="text" class="form-control" id="new-cat-icono" placeholder="Ej: bi bi-tree">
                                <small class="text-muted">Usa clases de Bootstrap Icons o FontAwesome</small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarNuevaCategoria()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#crearCategoriaModal').modal('show');
    $('#crearCategoriaModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarNuevaCategoria() {
    const data = {
        nombre: $('#new-cat-nombre').val(),
        descripcion: $('#new-cat-descripcion').val(),
        icono: $('#new-cat-icono').val()
    };

    if (!data.nombre) {
        alert("El nombre es obligatorio");
        return;
    }

    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                $('#crearCategoriaModal').modal('hide');
                showSuccess('Categoría creada');
                loadCategorias();
            } else {
                showError(response.message);
            }
        },
        error: function () {
            showError('Error al crear categoría');
        }
    });
}

function editarCategoria(id) {
    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                const categoria = response.data.find(c => c.id_categoria == id || c.id == id);
                if (categoria) {
                    showEditarCategoriaModal(categoria);
                } else {
                    showError("Categoría no encontrada");
                }
            }
        }
    });
}

function showEditarCategoriaModal(cat) {
    const catId = cat.id_categoria || cat.id;

    const modalHTML = `
        <div class="modal fade" id="editarCategoriaModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Categoría</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarCategoriaForm">
                            <input type="hidden" id="edit-cat-id" value="${catId}">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" class="form-control" id="edit-cat-nombre" value="${cat.nombre}" required>
                            </div>
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea class="form-control" id="edit-cat-descripcion">${cat.descripcion || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Icono</label>
                                <input type="text" class="form-control" id="edit-cat-icono" value="${cat.icono || ''}">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarCategoriaEditada()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#editarCategoriaModal').modal('show');
    $('#editarCategoriaModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarCategoriaEditada() {
    const data = {
        id: $('#edit-cat-id').val(),
        nombre: $('#edit-cat-nombre').val(),
        descripcion: $('#edit-cat-descripcion').val(),
        icono: $('#edit-cat-icono').val()
    };

    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            if (response.success) {
                $('#editarCategoriaModal').modal('hide');
                showSuccess('Categoría actualizada');
                loadCategorias();
            } else {
                showError(response.message);
            }
        },
        error: function (xhr) {
            let msg = 'Error al actualizar categoría';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg += ': ' + xhr.responseJSON.message;
            }
            showError(msg);
        }
    });
}

// --- LUGARES ---

function editarLugar(id) {
    if (window.currentLugares) {
        const l = window.currentLugares.find(x => x.id == id);
        if (l) showEditarLugarModal(l);
        else showError("Lugar no encontrado en memoria");
    } else {
        showError("Datos no cargados. Recarga la página.");
    }
}

function showEditarLugarModal(lugar) {
    const modalHTML = `
        <div class="modal fade" id="editarLugarModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Lugar: ${lugar.nombre}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarLugarForm">
                            <input type="hidden" id="edit-lugar-id" value="${lugar.id}">
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" id="edit-lugar-estado">
                                    <option value="aprobado" ${lugar.estado === 'aprobado' ? 'selected' : ''}>Aprobado</option>
                                    <option value="pendiente" ${lugar.estado === 'pendiente' ? 'selected' : ''}>Pendiente</option>
                                    <option value="rechazado" ${lugar.estado === 'rechazado' ? 'selected' : ''}>Rechazado</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarLugarEditado()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#editarLugarModal').modal('show');
    $('#editarLugarModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarLugarEditado() {
    const data = {
        id: $('#edit-lugar-id').val(),
        estado: $('#edit-lugar-estado').val()
    };

    $.ajax({
        url: '../api/admin/lugares.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            $('#editarLugarModal').modal('hide');
            if (response.success) {
                showSuccess('Lugar actualizado');
                loadLugares();
            } else {
                showError(response.message);
            }
        },
        error: () => showError('Error al guardar')
    });
}

// --- DEPARTAMENTOS ---

function editarDepartamento(id) {
    if (window.currentDepartamentos) {
        const dep = window.currentDepartamentos.find(d => d.id == id);
        if (dep) showEditarDepartamentoModal(dep);
    } else {
        $.ajax({
            url: '../api/admin/departamentos.php', success: (res) => {
                if (res.success) {
                    const dep = res.data.find(d => d.id == id);
                    if (dep) showEditarDepartamentoModal(dep);
                }
            }
        });
    }
}

function showEditarDepartamentoModal(dep) {
    const modalHTML = `
        <div class="modal fade" id="editarDepartamentoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Departamento</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarDepartamentoForm">
                            <input type="hidden" id="edit-dep-id" value="${dep.id}">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input type="text" class="form-control" id="edit-dep-nombre" value="${dep.nombre}" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarDepartamentoEditado()">Guardar</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#editarDepartamentoModal').modal('show');
    $('#editarDepartamentoModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarDepartamentoEditado() {
    const data = {
        id: $('#edit-dep-id').val(),
        nombre: $('#edit-dep-nombre').val()
    };

    $.ajax({
        url: '../api/admin/departamentos.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            $('#editarDepartamentoModal').modal('hide');
            showSuccess('Departamento actualizado');
            loadDepartamentos();
        },
        error: () => showError('Error al actualizar')
    });
}
