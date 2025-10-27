const fs = require('fs');
const path = require('path');

// Archivos
const inputFile = path.join(__dirname, 'lugares_insert.sql');
const outputFile = path.join(__dirname, 'lugares_insert_filtrado.sql');

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

// Leer el archivo
const sql = fs.readFileSync(inputFile, 'utf-8');

// Procesar línea por línea
const lines = sql.split('\n');

const nuevasLineas = lines.map(line => {
  const match = line.match(/VALUES\s*\(\s*'(.*?)'/i);
  if (!match) return line; // Si no es un insert válido, devolver línea sin cambios

  const nombre = match[1];
  const nuevaCategoria = asignarCategoria(nombre);

  // Reemplazar el último valor numérico de id_categoria
  return line.replace(/, (\d+)\);$/, `, ${nuevaCategoria});`);
});

// Guardar archivo filtrado
fs.writeFileSync(outputFile, nuevasLineas.join('\n'), 'utf-8');
console.log(`Se generó ${outputFile} con categorías reasignadas.`);
