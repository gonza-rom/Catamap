const fs = require('fs');
const path = require('path');
const turf = require('@turf/turf');

const archivoSQL = path.join(__dirname, 'lugares_insert_filtrado.sql');
const archivoGeoJSON = path.join(__dirname, 'departamentos-catamarca.json');
const salidaSQL = path.join(__dirname, 'lugares_insert_final4.sql');

// Leer archivos
const sql = fs.readFileSync(archivoSQL, 'utf-8');
const departamentosGeo = JSON.parse(fs.readFileSync(archivoGeoJSON, 'utf-8'));

// Lookup de departamentos y sus IDs
const deptosIDs = {
  "CAPITAL": 1,
  "ANDALGALÁ": 2,
  "ANTOFAGASTA DE LA SIERRA": 3,
  "AMBATO": 4,
  "BELEN": 5,
  "CAPAYAN": 6,
  "FRAY MAMERTO ESQUIÚ": 7,
  "LA PAZ": 8,
  "EL ALTO": 9,
  "PACLIN": 10,
  "POMAN": 11,
  "SANTA ROSA": 12,
  "SANTA MARIA": 13,
  "TINOGASTA": 14,
  "VALLE VIEJO": 15,
  "DESCONOCIDO": 16,
  "ANCASTI": 17
};

// Función para asignar categoría
function asignarCategoria(nombre) {
  const n = nombre.toLowerCase();
  if (n.includes('plaza') || n.includes('plazoleta')) return 29;
  if (n.includes('refugio')) return 28;
  if (n.includes('parroquia') || n.includes('templo') || n.includes('iglesia')) return 1;
  if (n.includes('cabaña') || n.includes('estancia') || n.includes('finca')) return 27;
  if (n.includes('complejo')) return 24;
  if (n.includes('lago')) return 15;
  if (n.includes('mogote') || n.includes('morro') || n.includes('cerro')) return 14;
  if (n.includes('turismo')) return 25;
  if (n.includes('termas')) return 8;
  if (n.includes('villa')) return 6;
  if (n.includes('hostal') || n.includes('hosteria') || n.includes('hotel')) return 22;
  if (n.includes('monumento')) return 18;
  return 99;
}

// Función para buscar el departamento según coordenadas
function buscarDepartamento(lat, lng) {
  const point = turf.point([lng, lat]);
  for (const feature of departamentosGeo.features) {
    const polygon = turf.polygon(feature.geometry.coordinates);
    if (turf.booleanPointInPolygon(point, polygon)) {
      return feature.properties.departamento;
    }
  }
  return 'DESCONOCIDO';
}

// Procesar SQL
let insertsFinales = [];
sql.split(/;\s*\n/).forEach(linea => {
  const match = linea.match(/INSERT INTO lugares\s*\(nombre, descripcion, departamento, direccion, lat, lng, imagen, id_categoria\)\s*VALUES\s*\(\s*'([^']*)'(.*?),\s*([0-9]+)\)/i);
  if (match) {
    const nombre = match[1];
    const resto = match[2];
    const catId = match[3];

    const nuevaCategoria = catId == 99 ? asignarCategoria(nombre) : catId;

    // Extraer lat y lng
    const coords = resto.match(/(-?\d+\.\d+),\s*(-?\d+\.\d+)/);
    let deptoId = 16; // DESCONOCIDO
    if (coords) {
      const lat = parseFloat(coords[1]);
      const lng = parseFloat(coords[2]);
      const deptoNombre = buscarDepartamento(lat, lng);
      deptoId = deptosIDs[deptoNombre] || 16;
    }

    // Nuevo insert para lugares_turisticos
    const insertFinal = `INSERT INTO lugares_turisticos (nombre, descripcion, direccion, lat, lng, imagen, id_categoria, id_departamento) VALUES ('${nombre}', '', '', ${coords[1]}, ${coords[2]}, '../img-catamarca/default.jpg', ${nuevaCategoria}, ${deptoId});`;
    insertsFinales.push(insertFinal);
  }
});

// Guardar el SQL final
fs.writeFileSync(salidaSQL, insertsFinales.join('\n'), 'utf-8');
console.log('Archivo final generado:', salidaSQL);
