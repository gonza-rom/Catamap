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

  // 🔹 Definimos el icono personalizado
  const userIcon = L.icon({
    iconUrl: '../img/ubicacion.png', // poné tu logo o icono acá
    iconSize: [30, 30],        // tamaño del icono
    iconAnchor: [20, 40],      // punto exacto de anclaje (abajo-centro)
    popupAnchor: [0, -40]      // dónde aparece el popup respecto al icono
  });

  // 🔹 Usamos el icono en el marcador
  L.marker(e.latlng, { icon: userIcon })
    .addTo(map)
    .bindPopup('📍 Estás aquí')
    .openPopup();
});

map.on('locationerror', () => {
  console.warn('Usuario no permitió geolocalización o no disponible.');
});

  // --- función global para trazar ruta (comprueba L.Routing) ---
  window.trazarRuta = function(destLat, destLng) {
    if (!ubicacionUsuario) {
      alert('⚠️ Primero permite acceder a tu ubicación en el navegador.');
      return;
    }
    if (!L || !L.Routing) {
      alert('⚠️ Routing Machine n o está disponible. Revisa la consola y el orden de scripts.');
      return;
    }
    // limpiar ruta anterior
    if (controlRuta) {
      try { map.removeControl(controlRuta); } catch(e){ console.warn(e); }
      controlRuta = null;
    }
    controlRuta = L.Routing.control({
      waypoints: [ L.latLng(ubicacionUsuario.lat, ubicacionUsuario.lng), L.latLng(destLat, destLng) ],
      routeWhileDragging: false,
      showAlternatives: true,
      createMarker: () => null // no crea marcadores extra
    }).addTo(map);
  };

  // --- cargar GeoJSON y mascara (como tenías) ---
  fetch('../data/departamentos-catamarca.json')
    .then(r => r.json())
    .then(geojson => {
      const geojsonLayer = L.geoJSON(geojson, {
        style: { color:'#000', weight:1, fillColor:'#66bb6a', fillOpacity:0 },
        onEachFeature: (feature, layer) => {
          layer.on('mouseover', () => layer.setStyle({ fillColor:'#E07B39', fillOpacity:0}) );
          layer.on('mouseout', () => geojsonLayer.resetStyle(layer) );
        }
      }).addTo(map);

      const world = turf.polygon([[[-180,-90],[-180,90],[180,90],[180,-90],[-180,-90]]]);
      const catamarca = turf.combine(geojson);
      const mask = turf.difference(world, catamarca.features[0]);
      L.geoJSON(mask, { style:{ color:'#FFFFFF', fillColor:'#ffcba8', fillOpacity:1, weight:1 } }).addTo(map);

      map.fitBounds(geojsonLayer.getBounds());
      map.setMaxBounds(geojsonLayer.getBounds());
      map.setMinZoom(map.getBoundsZoom(geojsonLayer.getBounds()));
    })
    .catch(err => console.error('Error cargando GeoJSON:', err));

// --- cargar GeoJSON y máscara ---
fetch('../data/departamentos-catamarca.json')
  .then(r => r.json())
  .then(geojson => {
    const geojsonLayer = L.geoJSON(geojson, {
      style: { color:'#000', weight:1, fillColor:'#66bb6a', fillOpacity:0.6 },
      onEachFeature: (feature, layer) => {

        // Hover visual
        layer.on('mouseover', () => layer.setStyle({ fillColor:'#E07B39', fillOpacity:0 }) );
        layer.on('mouseout', () => geojsonLayer.resetStyle(layer) );

        // Click en departamento
        layer.on('click', () => {
          const depto = feature.properties.departamento.toUpperCase();
          if (lugaresTuristicos[depto]) {

            let html = `<h4>${depto}</h4><div class="popup-content">`;
            lugaresTuristicos[depto].forEach(lugar => {
              html += `
                <div class="popup-lugar">
                  <b>${lugar.nombre}</b><br>
                  <small>${lugar.descripcion}</small><br>
                  <img src="${lugar.imagen}" alt="${lugar.nombre}" style="width:120px; border-radius:5px; margin-top:3px;"><br>
                  <button class="route-btn" data-lat="${lugar.lat}" data-lng="${lugar.lng}">🧭 Ir aquí</button>
                  <button class="detail-btn" data-id="${lugar.id}">ℹ️ Ver detalle</button>
                </div>
              `;
            });
            html += `</div>`; // cierre popup-content

            // bindPopup con scroll y maxWidth / maxHeight
            layer.bindPopup(html, {
              maxWidth: 300,
              maxHeight: 350,
              className: 'custom-popup'
            }).openPopup();

          } else {
            layer.bindPopup(`<b>${depto}</b><br>No hay lugares turísticos cargados.`).openPopup();
          }
        });

      }
    }).addTo(map);

    // Máscara
    const world = turf.polygon([[[-180,-90],[-180,90],[180,90],[180,-90],[-180,-90]]]);
    const catamarca = turf.combine(geojson);
    const mask = turf.difference(world, catamarca.features[0]);
    L.geoJSON(mask, { style:{ color:'#FFFFFF', fillColor:'#ffcba8', fillOpacity:1, weight:1 } }).addTo(map);

    map.fitBounds(geojsonLayer.getBounds());
    map.setMaxBounds(geojsonLayer.getBounds());
    map.setMinZoom(map.getBoundsZoom(geojsonLayer.getBounds()));
  })
  .catch(err => console.error('Error cargando GeoJSON:', err));


// --- Delegación global para los botones ---
document.addEventListener("click", e => {
  // Botón de detalle
  if (e.target.classList.contains("detail-btn")) {
    const id = e.target.dataset.id;
    window.location.href = `${BASE_URL}/pages/detalle-lugar.php?id=${id}`;
  }

  // Botón de ruta
  if (e.target.classList.contains("route-btn")) {
    const lat = parseFloat(e.target.dataset.lat);
    const lng = parseFloat(e.target.dataset.lng);
    window.trazarRuta(lat, lng);
  }
});

  
// --- agregar marcadores con emojis según categoría ---
for (const depto in lugaresTuristicos) {
  lugaresTuristicos[depto].forEach(lugar => {
    // 🔹 Creamos un divIcon con el emoji de la categoría
    const icono = L.divIcon({
      html: `<div style="font-size:30px; line-height:30px;">${lugar.icono}</div>`,
      className: '',       // importante dejar vacío
      iconSize: [30, 30],
      iconAnchor: [15, 30],
      popupAnchor: [0, -30]
    });

    // 🔹 Creamos el popup
    const popupHTML = `
      <b>${lugar.nombre}</b><br>
      <small>${lugar.categoria}</small><br>
      <p>${lugar.descripcion}</p>
      <img src="${lugar.imagen}" alt="${lugar.nombre}" style="width:150px;height:auto;border-radius:5px;margin-top:5px;"><br>
      <button class="route-btn" data-lat="${lugar.lat}" data-lng="${lugar.lng}">🧭 Ir aquí</button>
      <button class="detail-btn" data-id="${lugar.id}">ℹ️ Ver detalle</button>
    `;

    // 🔹 Agregamos el marcador al mapa con divIcon
    L.marker([lugar.lat, lugar.lng], { icon: icono }).addTo(map).bindPopup(popupHTML);
  });
}

  // --- al abrir cualquier popup enganchamos el botón para trazar ruta ---
  map.on('popupopen', function(e){
    try {
      const el = e.popup.getElement ? e.popup.getElement() : e.popup._container;
      if (!el) return;
      const btn = el.querySelector('.route-btn');
      if (btn) {
        btn.addEventListener('click', function handler(){
          const lat = parseFloat(this.dataset.lat);
          const lng = parseFloat(this.dataset.lng);
          window.trazarRuta(lat, lng);
        }, { once: true });
      }
    } catch(err) {
      console.error('popupopen handler error:', err);
    }
  });


    // 🔹 Crear control hamburguesa
const hamburgerControl = L.control({ position: "topleft" }); // "topleft" lo pone arriba izquierda

hamburgerControl.onAdd = function () {
  const div = L.DomUtil.create("div", "leaflet-bar leaflet-control leaflet-control-custom");
  div.innerHTML = '<i class="fas fa-bars" style="font-size:20px; padding:8px; cursor:pointer;"></i>';

  // Prevenir que el mapa se mueva al hacer click
  L.DomEvent.disableClickPropagation(div);

  // Acción al clickear
  div.onclick = function () {
    document.getElementById("sidebar").classList.toggle("show");
  };

  return div;
};

hamburgerControl.addTo(map);

// Botón cerrar
document.getElementById("closeSidebar").addEventListener("click", () => {
  document.getElementById("sidebar").classList.remove("show");
});


// 🔹 Control para "Agregar Parada"
const addStopControl = L.control({ position: "topleft" });

addStopControl.onAdd = function () {
  const div = L.DomUtil.create("div", "leaflet-bar leaflet-control leaflet-control-custom");
  div.innerHTML = '<i class="fas fa-map-marker-alt" style="font-size:20px; padding:8px; cursor:pointer;" title="Agregar Parada"></i>';
  
  L.DomEvent.disableClickPropagation(div);

  div.onclick = function () {
    alert("Haz clic en el mapa para agregar una parada 🚩");
    enableAddStopMode();
  };

  return div;
};

addStopControl.addTo(map);


function enableAddStopMode() {
  // Escuchar un solo click en el mapa
  map.once("click", function (e) {
    const marker = L.marker(e.latlng, { draggable: true }).addTo(map);

    // Popup con input de nombre
    marker.bindPopup(`
      <b>Nueva Parada</b><br>
      <input type="text" id="stopName" placeholder="Nombre de la parada" style="width:120px;">
      <br><button id="saveStop">Guardar</button>
    `).openPopup();

    // Cuando el popup se abra, escuchar al botón "Guardar"
    marker.on("popupopen", () => {
      document.getElementById("saveStop").addEventListener("click", () => {
        const stopName = document.getElementById("stopName").value || "Parada sin nombre";
        marker.setPopupContent(`<b>${stopName}</b><br>📍 ${e.latlng.lat.toFixed(5)}, ${e.latlng.lng.toFixed(5)}`);
        marker.openPopup();
      });
    });
  });
}


  // --- al abrir cualquier popup enganchamos el botón para trazar ruta ---
  map.on('popupopen', function(e){
    try {
      const el = e.popup.getElement ? e.popup.getElement() : e.popup._container;
      if (!el) return;
      const btn = el.querySelector('.route-btn');
      if (btn) {
        btn.addEventListener('click', function handler(){
          const lat = parseFloat(this.dataset.lat);
          const lng = parseFloat(this.dataset.lng);
          window.trazarRuta(lat, lng);
        }, { once: true });
      }
    } catch(err) {
      console.error('popupopen handler error:', err);
    }
  });


map.on('popupopen', function(e){
  try {
    const el = e.popup.getElement ? e.popup.getElement() : e.popup._container;
    if (!el) return;

    // 🔹 Botón de ruta
    const btnRoute = el.querySelector('.route-btn');
    if (btnRoute) {
      btnRoute.addEventListener('click', function handler(){
        const lat = parseFloat(this.dataset.lat);
        const lng = parseFloat(this.dataset.lng);
        window.trazarRuta(lat, lng);
      }, { once: true });
    }

    // 🔹 Botón de detalle
    const btnDetail = el.querySelector('.detail-btn');
    if (btnDetail) {
      btnDetail.addEventListener('click', function handler(){
        const id = this.dataset.id; // toma el id correcto del atributo data-id
        window.location.href = `${BASE_URL}/pages/detalle-lugar.php?id=${id}`;
      }, { once: true });
    }

  } catch(err) {
    console.error('popupopen handler error:', err);
  }
});