// ============================================
// SIDEBAR DE BÚSQUEDA
// ============================================

// La variable 'sidebar' ya está declarada en sidebar-categorias.js
// No la redeclaramos para evitar conflictos

// Crear HTML para el sidebar de búsqueda
const sidebarBusqueda = document.createElement("div");
sidebarBusqueda.id = "sidebarBusqueda";
sidebarBusqueda.className = "sidebar-busqueda";
sidebarBusqueda.innerHTML = `
  <div class="busqueda-header">
    <h3>🔍 Buscar Lugares</h3>
    <span id="closeBusqueda" class="close-btn">&times;</span>
  </div>
  <div class="busqueda-content">
    <div class="search-input-wrapper">
      <input 
        type="text" 
        id="searchInputSidebar" 
        class="search-input" 
        placeholder="Buscar por nombre, categoría..."
      >
      <button id="clearSearchSidebar" class="clear-search-btn" style="display: none;">✕</button>
    </div>
    <div id="searchResultsSidebar" class="search-results"></div>
  </div>
`;
document.body.appendChild(sidebarBusqueda);

// ============================================
// FUNCIONALIDAD DEL BUSCADOR
// ============================================

let allPlaces = [];

// Recopilar todos los lugares
function recopilarLugares() {
  allPlaces = [];
  for (const depto in lugaresTuristicos) {
    lugaresTuristicos[depto].forEach(lugar => {
      allPlaces.push({
        ...lugar,
        departamento: depto
      });
    });
  }
}

// Reemplazar la función buscarLugaresSidebar existente
function buscarLugaresSidebar(query) {
  const searchResultsSidebar = document.getElementById("searchResultsSidebar");
  const clearSearchSidebar = document.getElementById("clearSearchSidebar");

  if (!query.trim()) {
    searchResultsSidebar.innerHTML = "";
    clearSearchSidebar.style.display = "none";
    return;
  }

  clearSearchSidebar.style.display = "block";

  const queryLower = query.toLowerCase();
  const resultados = allPlaces.filter(lugar =>
    lugar.nombre.toLowerCase().includes(queryLower) ||
    lugar.descripcion.toLowerCase().includes(queryLower) ||
    lugar.categoria.toLowerCase().includes(queryLower) ||
    lugar.departamento.toLowerCase().includes(queryLower)
  );

  mostrarResultadosSidebar(resultados, query);
}

// Mostrar resultados
function mostrarResultadosSidebar(resultados, query) {
  const searchResultsSidebar = document.getElementById("searchResultsSidebar");

  if (resultados.length === 0) {
    searchResultsSidebar.innerHTML = `<div class="no-results">No se encontraron lugares</div>`;
    return;
  }

  let html = `<div class="results-header">${resultados.length} resultado${resultados.length !== 1 ? 's' : ''}</div>`;
  
  resultados.forEach(lugar => {
    html += `
      <div class="result-item-sidebar" data-lat="${lugar.lat}" data-lng="${lugar.lng}" data-id="${lugar.id}">
        <div class="result-icon">${lugar.icono}</div>
        <div class="result-info">
          <div class="result-nombre">${lugar.nombre}</div>
          <div class="result-categoria">${lugar.categoria}</div>
          <div class="result-departamento">${lugar.departamento}</div>
        </div>
      </div>
    `;
  });

  searchResultsSidebar.innerHTML = html;

  // Agregar eventos a los resultados
  document.querySelectorAll(".result-item-sidebar").forEach(item => {
    item.addEventListener("click", () => {
      const lat = parseFloat(item.dataset.lat);
      const lng = parseFloat(item.dataset.lng);
      const id = item.dataset.id;
      const nombre = item.querySelector('.result-nombre').textContent;
      const categoria = item.querySelector('.result-categoria').textContent;

      // Centrar mapa en el lugar
      map.flyTo([lat, lng], 18);

      // Cerrar sidebar de búsqueda
      sidebarBusqueda.classList.remove("show");

      // Buscar el lugar completo en allPlaces para obtener datos adicionales
      const lugarData = allPlaces.find(l => l.id == id);
      
      // Marcar automáticamente la categoría en los toggles
      const catCheckbox = document.querySelector(`input[value="${categoria}"]`);
      if (catCheckbox && !catCheckbox.checked) {
        catCheckbox.checked = true;
        const toggleSwitch = catCheckbox.closest(".toggle-switch");
        toggleSwitch.classList.add("active");
        
        // Agregar marcadores de esa categoría al mapa
        if (marcadoresPorCategoria[categoria]) {
          marcadoresPorCategoria[categoria].forEach(m => {
            m.addTo(map);
          });
        }
      }
      
      // Crear popup con información
      setTimeout(() => {
        const icono = lugarData.icono;
        
        // Crear icono personalizado
        const iconoPersonalizado = L.divIcon({
          html: `<div style="font-size:30px;line-height:30px;">${icono}</div>`,
          className: '',
          iconSize: [30, 30],
          iconAnchor: [15, 30],
          popupAnchor: [0, -30]
        });

        const popupHTML = `
          <b>${nombre}</b><br>
          <small>${categoria}</small><br>
          <p>${lugarData.descripcion}</p>
          <img src="${lugarData.imagen}" style="width:150px;height:auto;border-radius:5px;margin-top:5px;"><br>
          <button class="route-btn" data-lat="${lat}" data-lng="${lng}">🧭 Ir aquí</button>
          <button class="detail-btn" data-id="${id}">ℹ️ Ver detalle</button>
        `;
        
        const marker = L.marker([lat, lng], { icon: iconoPersonalizado }).bindPopup(popupHTML).openPopup();
      }, 500);
    });
  });
}

// ============================================
// EVENTOS
// ============================================

// Botón de búsqueda en el sidebar principal
const btnBuscar = document.getElementById("btnBuscar");
if (btnBuscar) {
  btnBuscar.addEventListener("click", (e) => {
    e.preventDefault();
    sidebarBusqueda.classList.add("show");
    sidebar.classList.remove("show"); // Cerrar sidebar principal
    document.getElementById("searchInputSidebar").focus();
  });
}

// Cerrar sidebar de búsqueda
document.getElementById("closeBusqueda").addEventListener("click", () => {
  sidebarBusqueda.classList.remove("show");
});

// Input de búsqueda
document.getElementById("searchInputSidebar").addEventListener("input", (e) => {
  buscarLugaresSidebar(e.target.value);
});

// Limpiar búsqueda
document.getElementById("clearSearchSidebar").addEventListener("click", () => {
  document.getElementById("searchInputSidebar").value = "";
  document.getElementById("searchResultsSidebar").innerHTML = "";
  document.getElementById("clearSearchSidebar").style.display = "none";
  document.getElementById("searchInputSidebar").focus();
});

// ============================================
// AGREGAR AL FINAL DE js/buscar-lugares.js
// ============================================

// Función mejorada para mostrar resultados con notificación
function mostrarResultadosSidebar(resultados, query) {
  const searchResultsSidebar = document.getElementById("searchResultsSidebar");

  if (resultados.length === 0) {
    searchResultsSidebar.innerHTML = `<div class="no-results">No se encontraron lugares</div>`;
    return;
  }

  let html = `<div class="results-header">${resultados.length} resultado${resultados.length !== 1 ? 's' : ''}</div>`;
  
  resultados.forEach(lugar => {
    html += `
      <div class="result-item-sidebar" data-lat="${lugar.lat}" data-lng="${lugar.lng}" data-id="${lugar.id}">
        <div class="result-icon">${lugar.icono}</div>
        <div class="result-info">
          <div class="result-nombre">${lugar.nombre}</div>
          <div class="result-categoria">${lugar.categoria}</div>
          <div class="result-departamento">${lugar.departamento}</div>
        </div>
      </div>
    `;
  });

  searchResultsSidebar.innerHTML = html;

  // Agregar eventos a los resultados
  document.querySelectorAll(".result-item-sidebar").forEach(item => {
    item.addEventListener("click", () => {
      const lat = parseFloat(item.dataset.lat);
      const lng = parseFloat(item.dataset.lng);
      const id = item.dataset.id;
      const nombre = item.querySelector('.result-nombre').textContent;
      const categoria = item.querySelector('.result-categoria').textContent;

      // 🔹 MOSTRAR NOTIFICACIÓN DEL LUGAR BUSCADO
      mostrarNotificacionBusqueda(nombre, categoria);

      // Centrar mapa en el lugar
      map.flyTo([lat, lng], 18);

      // Cerrar sidebar de búsqueda
      sidebarBusqueda.classList.remove("show");

      // Buscar el lugar completo en allPlaces para obtener datos adicionales
      const lugarData = allPlaces.find(l => l.id == id);
      
      // Marcar automáticamente la categoría en los toggles
      const catCheckbox = document.querySelector(`input[value="${categoria}"]`);
      if (catCheckbox && !catCheckbox.checked) {
        catCheckbox.checked = true;
        const toggleSwitch = catCheckbox.closest(".toggle-switch");
        toggleSwitch.classList.add("active");
        
        // Agregar marcadores de esa categoría al mapa
        if (marcadoresPorCategoria[categoria]) {
          marcadoresPorCategoria[categoria].forEach(m => {
            m.addTo(map);
          });
        }
      }
      
      // Crear popup con información
      setTimeout(() => {
        const icono = lugarData.icono;
        
        // Crear icono personalizado
        const iconoPersonalizado = L.divIcon({
          html: `<div style="font-size:30px;line-height:30px;">${icono}</div>`,
          className: '',
          iconSize: [30, 30],
          iconAnchor: [15, 30],
          popupAnchor: [0, -30]
        });

        const popupHTML = crearPopupContenido(lugarData);
        
        const marker = L.marker([lat, lng], { icon: iconoPersonalizado }).bindPopup(popupHTML).openPopup().addTo(map);
      }, 500);
    });
  });
}

// 🔹 FUNCIÓN PARA MOSTRAR NOTIFICACIÓN DE BÚSQUEDA
function mostrarNotificacionBusqueda(nombre, categoria) {
  // Crear contenedor de notificaciones si no existe
  let container = document.getElementById('notificaciones-busqueda');
  if (!container) {
    container = document.createElement('div');
    container.id = 'notificaciones-busqueda';
    container.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 10001;
      display: flex;
      flex-direction: column;
      gap: 10px;
      pointer-events: none;
      max-width: 90%;
      width: 500px;
    `;
    document.body.appendChild(container);
  }

  // Crear notificación
  const notificacion = document.createElement('div');
  notificacion.className = 'notificacion-busqueda';
  notificacion.style.pointerEvents = 'auto';
  
  notificacion.style.cssText = `
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 25px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 15px;
    animation: slideDown 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: auto;
    backdrop-filter: blur(10px);
  `;

  notificacion.innerHTML = `
    <div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
      <i class="bi bi-search" style="font-size: 1.5rem;"></i>
    </div>
    <div style="flex: 1;">
      <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 3px;">📍 ${nombre}</div>
      <div style="font-size: 0.9rem; opacity: 0.9;"><i class="bi bi-tag"></i> ${categoria}</div>
    </div>
    <button onclick="this.parentElement.remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; cursor: pointer; font-size: 1.3rem; padding: 5px 10px; border-radius: 50%; line-height: 1; transition: all 0.2s;">
      <i class="bi bi-x"></i>
    </button>
  `;

  container.appendChild(notificacion);

  // Auto-eliminar después de 5 segundos
  // setTimeout(() => {
  //   notificacion.style.animation = 'slideUp 0.5s ease forwards';
  //   setTimeout(() => notificacion.remove(), 500);
  // }, 5000);

  // Agregar estilos de animación si no existen
  if (!document.getElementById('notificaciones-busqueda-styles')) {
    const style = document.createElement('style');
    style.id = 'notificaciones-busqueda-styles';
    style.textContent = `
      @keyframes slideDown {
        from {
          transform: translateY(-100px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
      }
      @keyframes slideUp {
        from {
          transform: translateY(0);
          opacity: 1;
        }
        to {
          transform: translateY(-100px);
          opacity: 0;
        }
      }
      .notificacion-busqueda:hover {
        transform: scale(1.02);
        box-shadow: 0 12px 32px rgba(0,0,0,0.4);
      }
      .notificacion-busqueda button:hover {
        background: rgba(255,255,255,0.3) !important;
        transform: rotate(90deg);
      }
    `;
    document.head.appendChild(style);
  }
}




// Inicializar
recopilarLugares();


