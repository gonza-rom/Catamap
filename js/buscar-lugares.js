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

// Buscar lugares
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

// Inicializar
recopilarLugares();