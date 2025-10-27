const fs = require("fs");
const path = require("path");

// Ruta del archivo filtrado
const inputPath = path.join(__dirname, "lugares_filtrados.json");
const outputPath = path.join(__dirname, "lugares.sql");

const rawData = fs.readFileSync(inputPath, "utf-8");
const lugares = JSON.parse(rawData);

let inserts = "-- Inserts de lugares turísticos\n";

for (const lugar of lugares) {
  const osm_id = lugar.id || "NULL";
  const nombre = lugar.name.replace(/'/g, "''"); // escapamos comillas simples
  const categoria = lugar.categoria ? lugar.categoria.replace(/'/g, "''") : "otro";
  const lat = lugar.lat || "NULL";
  const lon = lugar.lon || "NULL";

  inserts += `INSERT INTO lugares (osm_id, nombre, categoria, lat, lon) VALUES (${osm_id}, '${nombre}', '${categoria}', ${lat}, ${lon});\n`;
}

fs.writeFileSync(outputPath, inserts, "utf-8");

console.log(`✅ Archivo lugares.sql generado con ${lugares.length} inserts`);