<?php
include '../includes/conexion.php'; 
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Mapa interactivo Catamarca</title>
  <!-- estilos -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />
  <link rel="stylesheet" href="../styles/mapa-catamaca.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <!-- SCRIPTS: orden estrictamente importante -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <!-- 🔹 Navbar -->
  <div id="map"></div>
    <div id="sidebar">
      <div class="sidebar-header">
        <h3>Menú</h3>
        <span id="closeSidebar">&times;</span> <!-- Botón de cerrar -->
      </div>
      <a href="/index.html">Inicio</a>
      <a href="/pages/mapa-catamarca.html">Mapa</a>
      <a href="/pages/contacto.html">Contacto</a>
      <a href="/pages/inicio-sesion.html">Iniciar Sesión</a>
    </div>
   <footer class="footer">
    <div class="footer-container">
        <div class="footer-logo">
        <img src="../img/CATAMAP.png" alt="Catamap Logo">
        </div>
        <div class="footer-links">
        <a href="/index.html">Inicio</a>
        <a href="/pages/mapa-catamarca.html">Mapa</a>
        <a href="/pages/contacto.html">Contacto</a>
        </div>
        <div class="footer-social">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-x-twitter"></i></a>
        </div>
        <p class="footer-copy">© 2025 CATAMAP - Todos los derechos reservados</p>
    </div>
    </footer>

  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
  <script>
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

// --- datos de ejemplo (tus lugares) ---
  const lugaresTuristicos = {
    "FRAY MAMERTO ESQUIU":[
      { nombre:"Dique Las Pirquitas", lat:-28.2706043, lng:-65.7388552, descripcion:"Dique histórico", imagen:"../img-catamarca/pirquitas.jpeg" },
      { nombre:"Huillapima", lat:-28.7259661, lng:-65.9783433, descripcion:"Paisajes naturales", imagen:"img/huillapima.jpg" }
    ],
    "BELEN":[
      { nombre:"Ruinas de Shincal de Quimivil", lat:-27.6875216, lng:-67.1807849, descripcion:"Ruinas arqueológicas", imagen:"img/shincal.jpg" },
      { nombre:"Tejidos Artesanales", lat:-27.1150, lng:-66.8810, descripcion:"Talleres tradicionales", imagen:"img/tejidos_belen.jpg" }
    ],
    "CAPITAL":[
      { nombre:"Dique El Jumeal", lat:-28.4565063, lng:-65.8091802, descripcion:"Dique recreativo", imagen:"../img-catamarca/dique-el-jumeal.webp" },
      { nombre:"Catedral Basílica de Nuestra Señora del Valle", lat:-28.4688896, lng:-65.7798856, descripcion:"Catedral histórica", imagen:"../img-catamarca/catedral-basilica.webp" }
    ]
  };



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
      alert('⚠️ Routing Machine no está disponible. Revisa la consola y el orden de scripts.');
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

// --- cargar GeoJSON y mascara (como tenías) ---
fetch('../data/departamentos-catamarca.json')
  .then(r => r.json())
  .then(geojson => {
    const geojsonLayer = L.geoJSON(geojson, {
      style: { color:'#000', weight:1, fillColor:'#66bb6a', fillOpacity:0.6 },
      onEachFeature: (feature, layer) => {
        // 🔹 Hover visual
        layer.on('mouseover', () => layer.setStyle({ fillColor:'#E07B39', fillOpacity:0 }) );
        layer.on('mouseout', () => geojsonLayer.resetStyle(layer) );

        // 🔹 Al hacer clic en un departamento mostramos lugares
        layer.on('click', () => {
          const depto = feature.properties.departamento.toUpperCase(); // ajusta al campo de tu GeoJSON
          if (lugaresTuristicos[depto]) {
            let html = `<h4>${depto}</h4>`;
            lugaresTuristicos[depto].forEach(lugar => {
              html += `
                <div style="margin:5px 0; border-bottom:1px solid #ccc; padding-bottom:5px;">
                  <b>${lugar.nombre}</b><br>
                  <small>${lugar.descripcion}</small><br>
                  <img src="${lugar.imagen}" style="width:120px; border-radius:5px; margin-top:3px;"><br>
                  <button class="route-btn" data-lat="${lugar.lat}" data-lng="${lugar.lng}">🧭 Ir aquí</button>
                  <button class="detail-btn" data-nombre="${encodeURIComponent(lugar.nombre)}">ℹ️ Ver detalle</button>
                </div>
              `;
            });
            layer.bindPopup(html).openPopup();
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



  // --- agregar marcadores (popups con botón que no usa onclick inline) ---
  for (const depto in lugaresTuristicos) {
    lugaresTuristicos[depto].forEach(lugar => {
      const popupHTML = `
        <b>${lugar.nombre}</b><br>
        <small>${depto}</small><br>
        <p>${lugar.descripcion}</p>
        <img src="${lugar.imagen}" alt="${lugar.nombre}" style="width:150px;height:auto;border-radius:5px;margin-top:5px;"><br>
        <button class="route-btn" data-lat="${lugar.lat}" data-lng="${lugar.lng}">🧭 Ir aquí</button>
        <button class="detail-btn" data-nombre="${encodeURIComponent(lugar.nombre)}">ℹ️ Ver detalle</button>
      `;
      L.marker([lugar.lat, lugar.lng]).addTo(map).bindPopup(popupHTML);
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

  // --- control + modal + sidebar (tu implementación) ---
  // (dejo intacto lo que ya tenías para + y sidebar)
  // ...
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
        const nombre = this.dataset.nombre;
        // Redirigir a otra página con query string
        window.location.href = `/pages/detalle.html?lugar=${nombre}`;
      }, { once: true });
    }

  } catch(err) {
    console.error('popupopen handler error:', err);
  }
});
</script>
</body>

</html>