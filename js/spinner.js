// ============================================
// SPINNER DE LOADING
// ============================================

// Crear HTML del spinner
const spinnerHTML = `
  <div id="loadingSpinner" class="loading-spinner">
    <div class="spinner-content">
      <div class="spinner-circle"></div>
      <p class="spinner-text">Cargando...</p>
    </div>
  </div>
`;

document.body.insertAdjacentHTML('beforeend', spinnerHTML);

// Funciones para mostrar/ocultar spinner
window.showSpinner = function(text = 'Cargando...') {
  const spinner = document.getElementById('loadingSpinner');
  const spinnerText = spinner.querySelector('.spinner-text');
  spinnerText.textContent = text;
  spinner.classList.add('show');
};

window.hideSpinner = function() {
  const spinner = document.getElementById('loadingSpinner');
  spinner.classList.remove('show');
};

// Mostrar spinner al cargar la página
document.addEventListener('DOMContentLoaded', () => {
  hideSpinner();
});

// Mostrar spinner cuando se carga GeoJSON
const originalFetch = window.fetch;
window.fetch = function(...args) {
  // Si es una petición a GeoJSON o datos
  if (args[0].includes('.json')) {
    showSpinner('Cargando mapa...');
  }
  
  return originalFetch.apply(this, args).then(response => {
    hideSpinner();
    return response;
  }).catch(error => {
    hideSpinner();
    throw error;
  });
};

// Mostrar spinner al trazar ruta
const originalTrazarRuta = window.trazarRuta;
window.trazarRuta = function(destLat, destLng, btn) {
  if (destLat && destLng && !window.controlRuta) {
    showSpinner('Calculando ruta...');
    setTimeout(() => hideSpinner(), 1000);
  }
  return originalTrazarRuta.call(this, destLat, destLng, btn);
};