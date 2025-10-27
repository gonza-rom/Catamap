// ============================================
// SIDEBAR DE CATEGORÍAS
// ============================================

// Obtener el botón de categorías y sidebar principal
const btnCategorias = document.getElementById("btnCategorias");
const sidebar = document.getElementById("sidebar");

// Crear HTML para el sidebar de categorías
const sidebarCategorias = document.createElement("div");
sidebarCategorias.id = "sidebarCategorias";
sidebarCategorias.className = "sidebar-categorias";
sidebarCategorias.innerHTML = `
  <div class="categorias-header">
    <h3>Filtrar Categorías</h3>
    <button id="closeCategorias" class="close-btn">&times;</button>
  </div>
  <div id="filterOptionsList" class="filter-options-list"></div>
  <div class="categorias-footer">
    <button id="limpiarFiltros" class="btn-limpiar">🔄 Limpiar filtros</button>
  </div>
`;
document.body.appendChild(sidebarCategorias);

// ============================================
// FUNCIÓN PARA RELLENAR LAS CATEGORÍAS
// ============================================

function poblarCategorias() {
  const filterOptionsList = document.getElementById("filterOptionsList");
  filterOptionsList.innerHTML = "";

  let categoriasUnicas = {};
  for (const depto in lugaresTuristicos) {
    lugaresTuristicos[depto].forEach(l => {
      if (!categoriasUnicas[l.categoria]) {
        categoriasUnicas[l.categoria] = l.icono;
      }
    });
  }

  for (const cat in categoriasUnicas) {
    const id = `cat-${cat.replace(/\s+/g, '-')}`;
    const filterOption = document.createElement("div");
    filterOption.className = "filter-option";
    filterOption.innerHTML = `
      <label>
        <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
          <span class="filter-icon">${categoriasUnicas[cat]}</span>
          <span class="filter-text">${cat}</span>
        </div>
        <div class="toggle-switch" data-toggle="${id}">
          <input type="checkbox" id="${id}" value="${cat}">
          <div class="toggle-slider"></div>
        </div>
      </label>
    `;
    filterOptionsList.appendChild(filterOption);
  }
}

// ============================================
// CREAR MARCADORES POR CATEGORÍA
// ============================================

let marcadoresPorCategoria = {};

function crearMarcadores() {
  for (const depto in lugaresTuristicos) {
    lugaresTuristicos[depto].forEach(lugar => {
      const icono = L.divIcon({
        html: `<div style="font-size:30px;line-height:30px;">${lugar.icono}</div>`,
        className: '',
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30]
      });

      const popupHTML = `
        <b>${lugar.nombre}</b><br>
        <small>${lugar.categoria}</small><br>
        <p>${lugar.descripcion}</p>
        <img src="${lugar.imagen}" style="width:150px;height:auto;border-radius:5px;margin-top:5px;"><br>
        <button class="route-btn" data-lat="${lugar.lat}" data-lng="${lugar.lng}">🧭 Ir aquí</button>
        <button class="detail-btn" data-id="${lugar.id}">ℹ️ Ver detalle</button>
      `;

      const marker = L.marker([lugar.lat, lugar.lng], { icon: icono }).bindPopup(popupHTML);

      if (!marcadoresPorCategoria[lugar.categoria]) {
        marcadoresPorCategoria[lugar.categoria] = [];
      }
      marcadoresPorCategoria[lugar.categoria].push(marker);
    });
  }
}

// ============================================
// EVENTOS
// ============================================

// Al hacer clic en "Categorías", abrir el sidebar de categorías
btnCategorias.addEventListener("click", (e) => {
  e.preventDefault();
  sidebarCategorias.classList.add("show");
  sidebar.classList.remove("show"); // Cerrar sidebar principal
});

// Cerrar sidebar de categorías
document.getElementById("closeCategorias").addEventListener("click", () => {
  sidebarCategorias.classList.remove("show");
});

// Mostrar/Ocultar marcadores según toggles
document.getElementById("filterOptionsList").addEventListener("click", (e) => {
  if (e.target.classList.contains("toggle-switch") || e.target.parentElement.classList.contains("toggle-switch")) {
    const toggleSwitch = e.target.classList.contains("toggle-switch") ? e.target : e.target.parentElement;
    const checkbox = toggleSwitch.querySelector("input[type='checkbox']");
    const categoria = checkbox.value;
    
    // Cambiar estado del checkbox
    checkbox.checked = !checkbox.checked;
    
    // Agregar/quitar clase active al toggle
    if (checkbox.checked) {
      toggleSwitch.classList.add("active");
    } else {
      toggleSwitch.classList.remove("active");
    }
    
    if (marcadoresPorCategoria[categoria]) {
      marcadoresPorCategoria[categoria].forEach(m => {
        if (checkbox.checked) {
          m.addTo(map);
        } else {
          map.removeLayer(m);
        }
      });
    }
  }
});

// Limpiar filtros
document.getElementById("limpiarFiltros").addEventListener("click", () => {
  // Desmarcar todos los checkboxes y quitar clase active
  document.querySelectorAll("#filterOptionsList input[type='checkbox']").forEach(checkbox => {
    checkbox.checked = false;
    checkbox.closest(".toggle-switch").classList.remove("active");
  });

  // Remover todos los marcadores
  for (const categoria in marcadoresPorCategoria) {
    marcadoresPorCategoria[categoria].forEach(marker => {
      map.removeLayer(marker);
    });
  }
});

// Inicializar
poblarCategorias();
crearMarcadores();