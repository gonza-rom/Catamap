const fs = require('fs');
const path = require('path');

const archivo = path.join(__dirname, 'lugares_insert.sql');
const salida = path.join(__dirname, 'lugares_insert_filtrado.sql');

// Leer el archivo
let sql = fs.readFileSync(archivo, 'utf-8');

// Función para determinar id_categoria según nombre
function asignarCategoria(nombre) {
  const n = nombre.toLowerCase();

  if (n.includes('plaza') || n.includes('plazoleta')) return 29;       // Plaza
  if (n.includes('refugio')) return 28;                                // Refugio
  if (n.includes('parroquia') || n.includes('templo') || n.includes('iglesia')) return 1; // Iglesia
  if (n.includes('cabaña') || n.includes('estancia') || n.includes('finca')) return 27;  // Cabaña
  if (n.includes('complejo')) return 24;                               // Camping
  if (n.includes('lago')) return 15;                                    // Laguna
  if (n.includes('mogote') || n.includes('morro') || n.includes('cerro')) return 14; // Cerro
  if (n.includes('turismo')) return 25;                                 // Información Turística
  if (n.includes('termas')) return 8;                                   // Termas
  if (n.includes('villa')) return 6;                                    // Pueblo
  if (n.includes('hostal') || n.includes('hosteria') || n.includes('hotel')) return 22; // Hotel
  if (n.includes('monumento')) return 18;                                // Cultura/Arqueología

  return 99; // Otros
}

// Reemplazar solo los que tienen id_categoria 99
const sqlActualizado = sql.replace(/VALUES\s*\(\s*'([^']*)'(.*?),\s*99\)/gi, (match, nombre, resto) => {
  const nuevaCategoria = asignarCategoria(nombre);
  return `VALUES ('${nombre}'${resto}, ${nuevaCategoria})`;
});

// Guardar en nuevo archivo
fs.writeFileSync(salida, sqlActualizado, 'utf-8');

console.log('Archivo actualizado creado en:', salida);
