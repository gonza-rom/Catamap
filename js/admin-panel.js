// Admin Panel JavaScript - VERSIÓN MEJORADA CON SWEETALERT2
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

// ============ SWEETALERT2 UTILITIES ============
function showSuccess(title, text = '') {
    Swal.fire({
        icon: 'success',
        title: title,
        text: text,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

function showError(title, text = '') {
    Swal.fire({
        icon: 'error',
        title: title,
        text: text,
        confirmButtonColor: '#dc3545'
    });
}

function showConfirm(title, text, confirmText = 'Sí, continuar') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar'
    });
}

function showLoading(text = 'Procesando...') {
    Swal.fire({
        title: text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
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
            showError('Error', 'No se pudieron cargar las estadísticas');
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
            showError('Error', 'No se pudieron cargar los usuarios');
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

    showLoading('Guardando cambios...');

    $.ajax({
        url: '../api/admin/usuarios.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            Swal.close();
            if (response.success) {
                $('#editarUsuarioModal').modal('hide');
                showSuccess('¡Actualizado!', 'Usuario actualizado correctamente');
                loadUsuarios();
            } else {
                showError('Error', response.message || 'No se pudo actualizar');
            }
        },
        error: function () {
            Swal.close();
            showError('Error', 'Error al actualizar usuario');
        }
    });
}

function eliminarUsuario(id, nombre) {
    showConfirm(
        '¿Eliminar usuario?',
        `¿Estás seguro de eliminar a "${nombre}"? Esta acción no se puede deshacer.`,
        'Sí, eliminar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Eliminando usuario...');
            
            $.ajax({
                url: '../api/admin/usuarios.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Eliminado!', 'Usuario eliminado correctamente');
                        loadUsuarios();
                    } else {
                        showError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al eliminar usuario');
                }
            });
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
                saveLugaresState(response.data);
                renderLugaresTable(response.data, response.pagination);
            }
        },
        error: function () {
            showError('Error', 'No se pudieron cargar los lugares');
        }
    });
}

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
                <td><strong>${lugar.nombre}</strong></td>
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

function editarLugar(id) {
    if (window.currentLugares) {
        const l = window.currentLugares.find(x => x.id == id);
        if (l) showEditarLugarModal(l);
        else showError('Error', 'Lugar no encontrado en memoria');
    } else {
        showError('Error', 'Datos no cargados. Recarga la página.');
    }
}

function showEditarLugarModal(lugar) {
    const modalHTML = `
        <div class="modal fade" id="editarLugarModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar: ${lugar.nombre}</h5>
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

    showLoading('Guardando cambios...');

    $.ajax({
        url: '../api/admin/lugares.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            Swal.close();
            $('#editarLugarModal').modal('hide');
            if (response.success) {
                showSuccess('¡Actualizado!', 'Lugar actualizado correctamente');
                loadLugares();
            } else {
                showError('Error', response.message || 'No se pudo actualizar');
            }
        },
        error: () => {
            Swal.close();
            showError('Error', 'Error al guardar');
        }
    });
}

function eliminarLugar(id, nombre) {
    showConfirm(
        '¿Eliminar lugar?',
        `¿Estás seguro de eliminar "${nombre}"? Esto también eliminará todos los favoritos y comentarios relacionados.`,
        'Sí, eliminar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Eliminando lugar...');
            
            $.ajax({
                url: '../api/admin/lugares.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Eliminado!', 'Lugar eliminado correctamente');
                        loadLugares();
                    } else {
                        showError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al eliminar lugar');
                }
            });
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
            showError('Error', 'No se pudieron cargar las sugerencias');
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
                <td><strong>${sug.nombre}</strong></td>
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
    showConfirm(
        '¿Aprobar sugerencia?',
        'Esta sugerencia será convertida en lugar turístico público',
        'Sí, aprobar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Aprobando sugerencia...');
            
            $.ajax({
                url: '../api/admin/lugares-sugeridos.php',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Aprobado!', 'Sugerencia aprobada y publicada');
                        loadSugerencias();
                        loadDashboard();
                    } else {
                        showError('Error', response.message || 'No se pudo aprobar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al aprobar sugerencia');
                }
            });
        }
    });
}

function rechazarSugerencia(id) {
    showConfirm(
        '¿Rechazar sugerencia?',
        'La sugerencia será marcada como rechazada',
        'Sí, rechazar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Rechazando sugerencia...');
            
            $.ajax({
                url: '../api/admin/lugares-sugeridos.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id, rechazar: true }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('Rechazado', 'Sugerencia rechazada');
                        loadSugerencias();
                    } else {
                        showError('Error', response.message || 'No se pudo rechazar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al rechazar sugerencia');
                }
            });
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
            showError('Error', 'No se pudieron cargar los comentarios');
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
                <td><strong>${com.lugar_nombre || '-'}</strong></td>
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
    showLoading('Actualizando comentario...');
    
    $.ajax({
        url: '../api/admin/comentarios.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ id, estado }),
        success: function (response) {
            Swal.close();
            if (response.success) {
                showSuccess('¡Actualizado!', 'Comentario actualizado');
                loadComentarios();
            } else {
                showError('Error', response.message || 'No se pudo actualizar');
            }
        },
        error: function () {
            Swal.close();
            showError('Error', 'Error al actualizar comentario');
        }
    });
}

function eliminarComentario(id) {
    showConfirm(
        '¿Eliminar comentario?',
        'Esta acción no se puede deshacer',
        'Sí, eliminar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Eliminando comentario...');
            
            $.ajax({
                url: '../api/admin/comentarios.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Eliminado!', 'Comentario eliminado');
                        loadComentarios();
                    } else {
                        showError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al eliminar comentario');
                }
            });
        }
    });
}

// ============ CATEGORÍAS ============
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
            showError('Error', 'No se pudieron cargar las categorías');
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
        const catId = cat.id_categoria || cat.id;
        const iconDisplay = cat.icono ? `<i class="${cat.icono}" style="font-size: 1.5rem; color: #667eea;"></i>` : '-';
        
        html += `
            <tr>
                <td>${catId}</td>
                <td><strong>${cat.nombre}</strong></td>
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
                                <label><i class="bi bi-tag"></i> Nombre *</label>
                                <input type="text" class="form-control" id="new-cat-nombre" required placeholder="Ej: Monumentos Históricos">
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-file-text"></i> Descripción</label>
                                <textarea class="form-control" id="new-cat-descripcion" rows="3" placeholder="Descripción de la categoría (opcional)"></textarea>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-palette"></i> Icono (Clase Bootstrap Icons)</label>
                                <input type="text" class="form-control" id="new-cat-icono" placeholder="Ej: bi bi-tree">
                                <small class="text-muted">Usa clases de Bootstrap Icons como: bi bi-tree, bi bi-mountain, bi bi-building, etc.</small>
                            </div>
                            <div class="form-group">
                                <label>Vista previa del icono:</label>
                                <div id="icon-preview" style="font-size: 2rem; color: #667eea; min-height: 40px;">
                                    <i class="bi bi-question-circle"></i>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarNuevaCategoria()">
                            <i class="bi bi-check-circle"></i> Crear Categoría
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#crearCategoriaModal').modal('show');
    
    // Preview de icono
    $('#new-cat-icono').on('input', function() {
        const iconClass = $(this).val().trim();
        if (iconClass) {
            $('#icon-preview').html(`<i class="${iconClass}"></i>`);
        } else {
            $('#icon-preview').html('<i class="bi bi-question-circle"></i>');
        }
    });
    
    $('#crearCategoriaModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarNuevaCategoria() {
    const data = {
        nombre: $('#new-cat-nombre').val().trim(),
        descripcion: $('#new-cat-descripcion').val().trim(),
        icono: $('#new-cat-icono').val().trim()
    };

    if (!data.nombre) {
        showError('Campo requerido', 'El nombre es obligatorio');
        return;
    }

    showLoading('Creando categoría...');

    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            Swal.close();
            if (response.success) {
                $('#crearCategoriaModal').modal('hide');
                showSuccess('¡Creada!', 'Categoría creada correctamente');
                loadCategorias();
            } else {
                showError('Error', response.message || 'No se pudo crear');
            }
        },
        error: function () {
            Swal.close();
            showError('Error', 'Error al crear categoría');
        }
    });
}

function editarCategoria(id) {
    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'GET',
        success: function (response) {
            if (response.success) {
                const categoria = response.data.find(c => (c.id_categoria == id || c.id == id));
                if (categoria) {
                    showEditarCategoriaModal(categoria);
                } else {
                    showError('Error', 'Categoría no encontrada');
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
                        <h5 class="modal-title">Editar Categoría: ${cat.nombre}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarCategoriaForm">
                            <input type="hidden" id="edit-cat-id" value="${catId}">
                            <div class="form-group">
                                <label><i class="bi bi-tag"></i> Nombre *</label>
                                <input type="text" class="form-control" id="edit-cat-nombre" value="${cat.nombre}" required>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-file-text"></i> Descripción</label>
                                <textarea class="form-control" id="edit-cat-descripcion" rows="3">${cat.descripcion || ''}</textarea>
                            </div>
                            <div class="form-group">
                                <label><i class="bi bi-palette"></i> Icono</label>
                                <input type="text" class="form-control" id="edit-cat-icono" value="${cat.icono || ''}" placeholder="Ej: bi bi-tree">
                                <small class="text-muted">Clases de Bootstrap Icons</small>
                            </div>
                            <div class="form-group">
                                <label>Vista previa:</label>
                                <div id="edit-icon-preview" style="font-size: 2rem; color: #667eea; min-height: 40px;">
                                    ${cat.icono ? `<i class="${cat.icono}"></i>` : '<i class="bi bi-question-circle"></i>'}
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarCategoriaEditada()">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHTML);
    $('#editarCategoriaModal').modal('show');
    
    // Preview de icono
    $('#edit-cat-icono').on('input', function() {
        const iconClass = $(this).val().trim();
        if (iconClass) {
            $('#edit-icon-preview').html(`<i class="${iconClass}"></i>`);
        } else {
            $('#edit-icon-preview').html('<i class="bi bi-question-circle"></i>');
        }
    });
    
    $('#editarCategoriaModal').on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function guardarCategoriaEditada() {
    const data = {
        id: $('#edit-cat-id').val(),
        nombre: $('#edit-cat-nombre').val().trim(),
        descripcion: $('#edit-cat-descripcion').val().trim(),
        icono: $('#edit-cat-icono').val().trim()
    };

    if (!data.nombre) {
        showError('Campo requerido', 'El nombre es obligatorio');
        return;
    }

    showLoading('Guardando cambios...');

    $.ajax({
        url: '../api/admin/categorias.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            Swal.close();
            if (response.success) {
                $('#editarCategoriaModal').modal('hide');
                showSuccess('¡Actualizada!', 'Categoría actualizada correctamente');
                loadCategorias();
            } else {
                showError('Error', response.message || 'No se pudo actualizar');
            }
        },
        error: function (xhr) {
            Swal.close();
            let msg = 'Error al actualizar categoría';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                msg += ': ' + xhr.responseJSON.message;
            }
            showError('Error', msg);
        }
    });
}

function eliminarCategoria(id, nombre, totalLugares) {
    if (totalLugares > 0) {
        showError(
            'No se puede eliminar',
            `La categoría "${nombre}" tiene ${totalLugares} lugares asociados. Debes reasignar o eliminar esos lugares primero.`
        );
        return;
    }

    showConfirm(
        '¿Eliminar categoría?',
        `¿Estás seguro de eliminar la categoría "${nombre}"?`,
        'Sí, eliminar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Eliminando categoría...');
            
            $.ajax({
                url: '../api/admin/categorias.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Eliminada!', 'Categoría eliminada correctamente');
                        loadCategorias();
                    } else {
                        showError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al eliminar categoría');
                }
            });
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
                window.currentDepartamentos = response.data;
                renderDepartamentosTable(response.data);
            }
        },
        error: function () {
            showError('Error', 'No se pudieron cargar los departamentos');
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
                <td><strong>${dep.nombre}</strong></td>
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

function editarDepartamento(id) {
    if (window.currentDepartamentos) {
        const dep = window.currentDepartamentos.find(d => d.id == id);
        if (dep) showEditarDepartamentoModal(dep);
    } else {
        $.ajax({
            url: '../api/admin/departamentos.php',
            success: (res) => {
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
                        <h5 class="modal-title">Editar Departamento: ${dep.nombre}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="editarDepartamentoForm">
                            <input type="hidden" id="edit-dep-id" value="${dep.id}">
                            <div class="form-group">
                                <label><i class="bi bi-map"></i> Nombre *</label>
                                <input type="text" class="form-control" id="edit-dep-nombre" value="${dep.nombre}" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarDepartamentoEditado()">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
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
        nombre: $('#edit-dep-nombre').val().trim()
    };

    if (!data.nombre) {
        showError('Campo requerido', 'El nombre es obligatorio');
        return;
    }

    showLoading('Guardando cambios...');

    $.ajax({
        url: '../api/admin/departamentos.php',
        method: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function (response) {
            Swal.close();
            if (response.success) {
                $('#editarDepartamentoModal').modal('hide');
                showSuccess('¡Actualizado!', 'Departamento actualizado correctamente');
                loadDepartamentos();
            } else {
                showError('Error', response.message || 'No se pudo actualizar');
            }
        },
        error: () => {
            Swal.close();
            showError('Error', 'Error al actualizar');
        }
    });
}

function eliminarDepartamento(id, nombre, totalLugares) {
    if (totalLugares > 0) {
        showError(
            'No se puede eliminar',
            `El departamento "${nombre}" tiene ${totalLugares} lugares asociados. Debes reasignar o eliminar esos lugares primero.`
        );
        return;
    }

    showConfirm(
        '¿Eliminar departamento?',
        `¿Estás seguro de eliminar el departamento "${nombre}"?`,
        'Sí, eliminar'
    ).then((result) => {
        if (result.isConfirmed) {
            showLoading('Eliminando departamento...');
            
            $.ajax({
                url: '../api/admin/departamentos.php',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id }),
                success: function (response) {
                    Swal.close();
                    if (response.success) {
                        showSuccess('¡Eliminado!', 'Departamento eliminado correctamente');
                        loadDepartamentos();
                    } else {
                        showError('Error', response.message || 'No se pudo eliminar');
                    }
                },
                error: function () {
                    Swal.close();
                    showError('Error', 'Error al eliminar departamento');
                }
            });
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

    html += `<button ${pagination.page === 1 ? 'disabled' : ''} onclick="${loadFunction}(${pagination.page - 1})">
        <i class="bi bi-chevron-left"></i>
    </button>`;

    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.page) {
            html += `<button class="active">${i}</button>`;
        } else if (i === 1 || i === pagination.total_pages || Math.abs(i - pagination.page) <= 2) {
            html += `<button onclick="${loadFunction}(${i})">${i}</button>`;
        } else if (i === pagination.page - 3 || i === pagination.page + 3) {
            html += `<button disabled>...</button>`;
        }
    }

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
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
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