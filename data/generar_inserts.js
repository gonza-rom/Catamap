const fs = require('fs');
const path = require('path');

// Ruta a tu archivo JSON
const jsonPath = path.join(__dirname, 'lugares_filtrados.json');

// Leer y parsear el JSON
const rawData = fs.readFileSync(jsonPath, 'utf-8');
const data = JSON.parse(rawData);

// Si tu JSON tiene estructura tipo FeatureCollection
const lugares = data.features || data;

// Función para determinar id_categoria según nombre
function asignarCategoria(nombre) {
  const n = nombre.toLowerCase();

  if (n.includes('plaza') || n.includes('plazoleta')) return 29;       // Plaza
  if (n.includes('refugio')) return 28;                                // Refugio
  if (n.includes('parroquia') || n.includes('templo') || n.includes('iglesia')) return 1; // Iglesia
  if (n.includes('cabaña') || n.includes('estancia') || n.includes('finca')) return 27;  // Cabaña
  if (n.includes('complejo')) return 24;                               // Camping
  if (n.includes('lago')) return 15;                                    // Laguna
  if (n.includes('mogote') || n.includes('morro')) return 14;           // Cerro
  if (n.includes('turismo')) return 25;                                 // Información Turística
  if (n.includes('termas')) return 8;                                   // Termas
  if (n.includes('villa')) return 6;                                    // Pueblo
  if (n.includes('hostal')) return 22;                                  // Hotel
  if (n.includes('monumento')) return 18;                                // Cultura/Arqueología

  return 99; // Otros
}

// Filtrar lugares válidos y generar inserts
const inserts = lugares
  .filter(f => f.properties && f.properties.name && f.geometry && f.geometry.coordinates)
  .map(f => {
    const nombre = f.properties.name.replace(/'/g, "''");
    const lat = f.geometry.coordinates[1];
    const lng = f.geometry.coordinates[0];
    const id_categoria = asignarCategoria(nombre);

    return `INSERT INTO lugares (nombre, descripcion, departamento, direccion, lat, lng, imagen, id_categoria) VALUES ('${nombre}', '', '', '', ${lat}, ${lng}, '', ${id_categoria});`;
  });

// Guardar archivo SQL
fs.writeFileSync(path.join(__dirname, 'lugares_insert.sql'), inserts.join('\n'), 'utf-8');
console.log(`Se generaron ${inserts.length} inserts en lugares_insert.sql`);
