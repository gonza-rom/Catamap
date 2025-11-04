// Si en algún momento cambias la carpeta del proyecto, puedes definir una variable base al inicio de tu JS:
const BASE_URL = '/catamap';

// Inicializar mapa
const map = L.map('map');

// 🔹 Capas base
const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
});

const voyager = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> &copy; <a href="https://carto.com/">CARTO</a>',
  subdomains: 'abcd'
});

const terrain = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
  attribution: 'Tiles © Esri — Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom'
});

const blanco = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/terrain/{z}/{x}/{y}{r}.png', {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> © <a href="https://carto.com/">CARTO</a>',
  subdomains: 'abcd'
});

// Agregar OSM por defecto
osm.addTo(map);

// Control de capas
const baseMaps = {
  "🗺️ Rutas": osm,
  "🌍 Moderno": voyager,
  "🏔️ Terreno": terrain,
  "⚪ Blanco": blanco
};

L.control.layers(baseMaps).addTo(map);

// --- variables de ruta / usuario ---
let ubicacionUsuario = null;
let controlRuta = null;

// --- geolocalización (intenta obtener usuario) ---
map.locate({ setView: false, maxZoom: 14 });

map.on('locationfound', e => {
  ubicacionUsuario = e.latlng;

  const userIcon = L.icon({
    iconUrl: '../img/ubicacion.png',
    iconSize: [30, 30],
    iconAnchor: [15, 30],
    popupAnchor: [0, -30]
  });

  L.marker(e.latlng, { icon: userIcon })
    .addTo(map)
    .bindPopup('📍 Estás aquí')
    .openPopup();

  // 🔹 Esperar un instante y luego centrar en el usuario
  setTimeout(() => {
    map.flyTo(e.latlng, 15); // flyTo hace una animación suave
  }, 500);
});

map.on('locationerror', () => {
  console.warn('Usuario no permitió geolocalización o no disponible.');
});

// --- función global para trazar/cancelar ruta ---
window.trazarRuta = function(destLat, destLng, btn) {
  if (!ubicacionUsuario) {
    alert('⚠️ Primero permite acceder a tu ubicación en el navegador.');
    return;
  }
  if (!L || !L.Routing) {
    alert('⚠️ Routing Machine no está disponible. Revisa la consola y el orden de scripts.');
    return;
  }

  // Si ya hay ruta activa => cancelar
  if (controlRuta) {
    try {
      // Remover todas las polylines del mapa (rutas)
      map.eachLayer(function(layer) {
        if (layer instanceof L.Polyline) {
          map.removeLayer(layer);
        }
      });

      // limpiar waypoints
      if (controlRuta.getPlan) {
        controlRuta.getPlan().setWaypoints([]);
      }

      // remover control del mapa
      map.removeControl(controlRuta);
    } catch(e) {
      console.warn(e);
    }

    controlRuta = null;

    // 🔹 ELIMINAR contenedores huérfanos (instrucciones)
    document.querySelectorAll(".leaflet-routing-container").forEach(c => c.remove());

    // Resetear todos los botones de ruta a su estado original
    document.querySelectorAll(".route-btn").forEach(button => {
      button.textContent = "🧭 Ir aquí";
      button.classList.remove("cancel-btn");
      button.classList.add("route-btn");
    });
    
    return;
  }

  // Si no hay ruta => crearla
  controlRuta = L.Routing.control({
    waypoints: [
      L.latLng(ubicacionUsuario.lat, ubicacionUsuario.lng),
      L.latLng(destLat, destLng)
    ],
    routeWhileDragging: false,
    showAlternatives: true,
    language: "es", // interfaz en español
    createMarker: () => null // no crea marcadores extra
  }).addTo(map);

  // Cambiar botón a "Cancelar"
  if (btn) {
    btn.textContent = "❌ Cancelar ruta";
    btn.classList.remove("route-btn");
    btn.classList.add("cancel-btn");
  }
};

// ============================================
// FUNCIÓN PARA CREAR CONTENIDO DE POPUP
// ============================================
function crearPopupContenido(lugar) {
  const imagenUrl = lugar.imagen ? `../uploads/${lugar.imagen}` : '../img/placeholder.jpg';
  
  // Botón de favoritos solo si hay usuario logueado
  let favoritoBtn = '';
  if (typeof usuarioLogueado !== 'undefined' && usuarioLogueado) {
    const esFav = typeof Favoritos !== 'undefined' && Favoritos.esFavorito(lugar.id);
    const claseActivo = esFav ? 'active' : '';
    const icono = esFav ? 'bi-star-fill' : 'bi-star';
    
    favoritoBtn = `
      <button class="favorito-btn ${claseActivo}" 
              data-lugar-id="${lugar.id}" 
              onclick="event.stopPropagation(); if(typeof Favoritos !== 'undefined') Favoritos.toggle(${lugar.id})"
              title="${esFav ? 'Quitar de favoritos' : 'Agregar a favoritos'}">
        <i class="bi ${icono}"></i>
      </button>
    `;
  }

  return `
    <div class="popup-lugar" style="position: relative; min-width: 250px;">
      ${favoritoBtn}
      <img src="${imagenUrl}" 
           alt="${lugar.nombre}" 
           style="width:100%; max-width:200px; height:150px; object-fit:cover; border-radius:8px; margin-bottom:10px;"
           onerror="this.src='../img/placeholder.jpg'">
      <h4 style="font-size: 1rem ; margin: 8px 0; color: #333;">${lugar.nombre}</h4>
      <p style="color: #666; font-size: 0.9rem; margin: 5px 0;">${lugar.descripcion}</p>
      <p style="margin: 5px 0; color: #555;">
        <i class="bi bi-geo-alt-fill" style="color: #E07B39;"></i> 
        <small>${lugar.direccion || 'Catamarca'}</small>
      </p>
      <p style="margin: 5px 0; color: #555;">
        <i class="bi bi-tag-fill" style="color: #4CAF50;"></i> 
        <small>${lugar.categoria || 'General'}</small>
      </p>
      <div style="display: flex; gap: 8px; margin-top: 12px;">
        <button class="route-btn" 
                data-lat="${lugar.lat}" 
                data-lng="${lugar.lng}"
                style="flex: 1; padding: 8px 12px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s;">
          🧭 Ir aquí
        </button>
        <button class="detail-btn" 
                data-id="${lugar.id}"
                style="flex: 1; padding: 8px 12px; background: #E07B39; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s;">
          ℹ️ Ver detalle
        </button>
      </div>
    </div>
  `;
}

// --- cargar GeoJSON con máscara pero sin tapar rutas ---
fetch('../data/departamentos-catamarca.json')
.then(r => r.json())
.then(geojson => {
  const geojsonLayer = L.geoJSON(geojson, {
    style: { color:'#000', weight:2, fillColor:'#ffffff', fillOpacity:0 },
    onEachFeature: (feature, layer) => {
      // Hover visual - solo borde
      layer.on('mouseover', () => layer.setStyle({ color:'#E07B39', weight:3 }) );
      layer.on('mouseout', () => geojsonLayer.resetStyle(layer) );

      // Click en departamento
      layer.on('click', () => {
        const depto = feature.properties.departamento.toUpperCase();
        const lugares = lugaresTuristicos[depto];

        if (lugares && lugares.length > 0) {
          let html = `
            <h4 style="margin: 0 0 15px 0; color: #333; border-bottom: 2px solid #E07B39; padding-bottom: 8px;">
              ${depto}
            </h4>
            <div class="popup-content" style="max-height: 300px; overflow-y: auto;">
          `;
          
          lugares.forEach(lugar => {
            html += crearPopupContenido(lugar);
            html += '<hr style="margin: 15px 0; border: none; border-top: 1px solid #eee;">';
          });
          
          html += `</div>`;
          
          layer.bindPopup(html, { 
            maxWidth: 350, 
            minWidth: 300,
            className: 'custom-popup' 
          }).openPopup();
        } else {
          layer.bindPopup(`
            <div style="text-align: center; padding: 20px;">
              <i class="bi bi-geo-alt" style="font-size: 3rem; color: #ddd;"></i>
              <h4 style="margin: 10px 0; color: #666;">${depto}</h4>
              <p style="color: #999;">No hay lugares turísticos cargados</p>
            </div>
          `).openPopup();
        }
      });
    }
  }).addTo(map);

  // Ajustar vista y límites solo para Catamarca
  const bounds = geojsonLayer.getBounds();
  map.fitBounds(bounds);
  
  // Establecer límites máximos para que no se vea fuera de Catamarca
  map.setMaxBounds(bounds);
  
  // Zoom mínimo para ver todo Catamarca
  const minZoom = map.getBoundsZoom(bounds);
  map.setMinZoom(minZoom);
  
  // Zoom máximo
  map.setMaxZoom(18);
})
.catch(err => console.error('Error cargando GeoJSON:', err));

// --- Delegación global para los botones ---
document.addEventListener("click", e => {
  // Botón de detalle
  if (e.target.classList.contains("detail-btn") || e.target.closest(".detail-btn")) {
    const btn = e.target.classList.contains("detail-btn") ? e.target : e.target.closest(".detail-btn");
    const id = btn.dataset.id;
    window.location.href = `${BASE_URL}/pages/detalle-lugar.php?id=${id}`;
  }

  // Botón de ruta
  if (e.target.classList.contains("route-btn") || e.target.closest(".route-btn")) {
    const btn = e.target.classList.contains("route-btn") ? e.target : e.target.closest(".route-btn");
    const lat = parseFloat(btn.dataset.lat);
    const lng = parseFloat(btn.dataset.lng);
    window.trazarRuta(lat, lng, btn);
  }
});

// --- Evento al abrir popup ---
map.on('popupopen', function(e){
  try {
    const el = e.popup.getElement ? e.popup.getElement() : e.popup._container;
    if (!el) return;

    // 🔹 Actualizar estado de botones de favoritos en el popup
    if (typeof Favoritos !== 'undefined') {
      const favBtns = el.querySelectorAll('.favorito-btn');
      favBtns.forEach(btn => {
        const idLugar = parseInt(btn.dataset.lugarId);
        const esFav = Favoritos.esFavorito(idLugar);
        
        if (esFav) {
          btn.classList.add('active');
          btn.querySelector('i').className = 'bi bi-star-fill';
        } else {
          btn.classList.remove('active');
          btn.querySelector('i').className = 'bi bi-star';
        }
      });
    }

    // 🔹 Botón de ruta
    const btnRoute = el.querySelector('.route-btn');
    if (btnRoute) {
      btnRoute.addEventListener('click', function handler(){
        const lat = parseFloat(this.dataset.lat);
        const lng = parseFloat(this.dataset.lng);
        window.trazarRuta(lat, lng, this);
      });
    }

    // 🔹 Botón de detalle
    const btnDetail = el.querySelector('.detail-btn');
    if (btnDetail) {
      btnDetail.addEventListener('click', function handler(){
        const id = this.dataset.id;
        window.location.href = `${BASE_URL}/pages/detalle-lugar.php?id=${id}`;
      });
    }

  } catch(err) {
    console.error('popupopen handler error:', err);
  }
});

// ============================================
// BOTÓN DE IR A MI UBICACIÓN
// ============================================

// Control personalizado para "Ir a mi ubicación"
const homeControl = L.control({ position: "bottomright" });

homeControl.onAdd = function () {
  const div = L.DomUtil.create("div", "leaflet-bar leaflet-control leaflet-control-custom");
  div.innerHTML = '<i class="fas fa-home" style="font-size:20px; padding:8px; cursor:pointer; color:#333;" title="Ir a mi ubicación"></i>';
  
  // Prevenir que el mapa se mueva al hacer click
  L.DomEvent.disableClickPropagation(div);

  // Acción al clickear
  div.onclick = function () {
    if (ubicacionUsuario) {
      // Centrar el mapa en la ubicación del usuario
      map.flyTo(ubicacionUsuario, 15);
    } else {
      alert('⚠️ Primero permite acceder a tu ubicación en el navegador.');
      // Intentar obtener la geolocalización nuevamente
      map.locate({ setView: true, maxZoom: 15 });
    }
  };

  return div;
};

homeControl.addTo(map);

// ============================================
// ANIMACIÓN PARA MARCADORES AL APARECER
// ============================================

// Guardar la función original de addTo
L.Marker.prototype.originalAddTo = L.Marker.prototype.addTo;

// Reemplazar addTo para agregar animación
L.Marker.prototype.addTo = function(map) {
  // Llamar a la función original
  this.originalAddTo(map);
  
  // Obtener el elemento del marcador
  const markerElement = this._icon;
  if (markerElement) {
    // Resetear la animación
    markerElement.style.animation = 'none';
    
    // Forzar reflow para reiniciar la animación
    void markerElement.offsetWidth;
    
    // Aplicar la animación
    markerElement.style.animation = 'markerPopIn 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
  }
  
  return this;
};

// Agregar CSS para la animación
const style = document.createElement('style');
style.innerHTML = `
  @keyframes markerPopIn {
    0% {
      transform: scale(0) rotate(-45deg);
      opacity: 0;
    }
    50% {
      transform: scale(1.2);
    }
    100% {
      transform: scale(1) rotate(0);
      opacity: 1;
    }
  }

  @keyframes markerBounce {
    0%, 100% {
      transform: translateY(0);
    }
    50% {
      transform: translateY(-10px);
    }
  }

  .leaflet-marker-icon {
    transition: transform 0.3s ease;
  }

  .leaflet-marker-icon:hover {
    transform: scale(1.3);
    animation: markerBounce 0.6s ease infinite;
  }

  /* Estilos para los botones dentro de los popups */
  .route-btn:hover, .detail-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
  }

  .route-btn:active, .detail-btn:active {
    transform: translateY(0);
  }

  .cancel-btn {
    background: #dc3545 !important;
  }

  .cancel-btn:hover {
    background: #c82333 !important;
  }

  /* Estilo para popup personalizado */
  .custom-popup .leaflet-popup-content-wrapper {
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  }

  .custom-popup .leaflet-popup-content {
    margin: 15px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  }

  /* Scrollbar personalizado para contenido de popup */
  .popup-content::-webkit-scrollbar {
    width: 6px;
  }

  .popup-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
  }

  .popup-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
  }

  .popup-content::-webkit-scrollbar-thumb:hover {
    background: #555;
  }
`;

document.head.appendChild(style);

// ============================================
// INICIALIZAR FAVORITOS AL CARGAR
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  // Cargar favoritos si el usuario está logueado
  if (typeof usuarioLogueado !== 'undefined' && usuarioLogueado && typeof Favoritos !== 'undefined') {
    Favoritos.init();
    console.log('Sistema de favoritos inicializado');
  }
});

// ============================================
// FUNCIÓN GLOBAL PARA ACTUALIZAR UI DE FAVORITOS
// ============================================
window.actualizarFavoritoEnMapa = function(idLugar, esFavorito) {
  // Actualizar todos los botones de favoritos visibles en el mapa
  const botones = document.querySelectorAll(`.favorito-btn[data-lugar-id="${idLugar}"]`);
  botones.forEach(boton => {
    if (esFavorito) {
      boton.classList.add('active');
      boton.querySelector('i').className = 'bi bi-star-fill';
      boton.title = 'Quitar de favoritos';
    } else {
      boton.classList.remove('active');
      boton.querySelector('i').className = 'bi bi-star';
      boton.title = 'Agregar a favoritos';
    }
  });
};


// Al cargar la página, verificar si hay parámetros en la URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const lat = urlParams.get('lat');
    const lng = urlParams.get('lng');
    const id = urlParams.get('id');
    
    if (lat && lng) {
        // Esperar a que el mapa esté listo
        setTimeout(() => {
            window.irALugarEnMapa(parseFloat(lat), parseFloat(lng), parseInt(id));
        }, 2000);
    }
});

console.log('Mapa de Catamarca cargado correctamente');
console.log('Usuario logueado:', typeof usuarioLogueado !== 'undefined' ? usuarioLogueado : 'No');


// Función para ir a un lugar específico en el mapa
window.irALugarEnMapa = function(lat, lng, idLugar) {
    // Centrar el mapa en el lugar
    map.flyTo([lat, lng], 16, {
        duration: 2,
        easeLinearity: 0.25
    });

    // Buscar el lugar en los datos
    let lugarEncontrado = null;
    for (const depto in lugaresTuristicos) {
        const lugares = lugaresTuristicos[depto];
        lugarEncontrado = lugares.find(l => l.id === idLugar);
        if (lugarEncontrado) break;
    }

    if (lugarEncontrado) {
        // Crear un marcador temporal con animación
        const pulsingIcon = L.divIcon({
            className: 'pulsing-marker',
            html: `<div class="pulse-icon">
                     <i class="bi bi-geo-alt-fill" style="font-size: 2rem; color: #E07B39;"></i>
                   </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 40]
        });

        const markerTemp = L.marker([lat, lng], {
            icon: pulsingIcon,
            zIndexOffset: 1000
        }).addTo(map);

        // Mostrar popup después de un momento
        setTimeout(() => {
            const popupContent = crearPopupContenido(lugarEncontrado);
            markerTemp.bindPopup(popupContent, {
                maxWidth: 350,
                minWidth: 300,
                className: 'custom-popup'
            }).openPopup();

            // Remover el marcador temporal después de 30 segundos
            setTimeout(() => {
                map.removeLayer(markerTemp);
            }, 30000);
        }, 1000);
    }
};

// Al cargar la página, verificar si hay parámetros en la URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const lat = urlParams.get('lat');
    const lng = urlParams.get('lng');
    const id = urlParams.get('id');
    
    if (lat && lng && id) {
        // Esperar a que el mapa esté completamente cargado
        setTimeout(() => {
            window.irALugarEnMapa(parseFloat(lat), parseFloat(lng), parseInt(id));
        }, 1500);

        // Limpiar la URL después de procesar
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 3000);
    }
});

// CSS para el marcador pulsante
const pulsatingStyle = document.createElement('style');
pulsatingStyle.innerHTML = `
    .pulsing-marker {
        background: transparent;
        border: none;
    }

    .pulse-icon {
        position: relative;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .pulse-icon::before {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        background: #E07B39;
        border-radius: 50%;
        opacity: 0;
        animation: pulse 2s ease-out infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.5);
            opacity: 0.8;
        }
        50% {
            transform: scale(1.5);
            opacity: 0.4;
        }
        100% {
            transform: scale(2.5);
            opacity: 0;
        }
    }

    .pulse-icon i {
        position: relative;
        z-index: 10;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        animation: bounce 1s ease-in-out infinite;
    }

    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(pulsatingStyle);