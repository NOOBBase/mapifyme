document.addEventListener('DOMContentLoaded', function () {
  const initializedMaps = {};

  function initializeMaps() {
    const mapContainers = document.querySelectorAll('[data-mapifyme-map]');
    console.log('Found', mapContainers.length, 'map container(s)');

    mapContainers.forEach(function (container) {
      const mapId = container.id;
      if (initializedMaps[mapId]) {
        console.log(`Map with ID ${mapId} is already initialized.`);
        return;
      }

      const latitude = parseFloat(container.getAttribute('data-latitude'));
      const longitude = parseFloat(container.getAttribute('data-longitude'));
      const zoomLevel = parseInt(container.getAttribute('data-zoom'), 10) || 13;
      const markersData = container.getAttribute('data-markers');
      const markers = markersData ? JSON.parse(markersData) : [];

      initMap(container, latitude, longitude, zoomLevel, markers);
    });
  }

  function initMap(container, latitude, longitude, zoomLevel, markers = []) {
    console.log(
      `Initializing Leaflet map for ${container.id} at ${latitude}, ${longitude} with zoom ${zoomLevel}`
    );

    const map = L.map(container.id).setView([latitude, longitude], zoomLevel);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data &copy; OpenStreetMap contributors',
    }).addTo(map);

    if (markers.length > 0) {
      markers.forEach(function (markerData) {
        const postMarker = L.marker([
          markerData.latitude,
          markerData.longitude,
        ]).addTo(map);
        const popupContent = document.createElement('div');
        popupContent.innerHTML = markerData.popup_content;
        postMarker.bindPopup(popupContent.innerHTML);
      });
    }

    // Ensure the map container size is properly updated after initialization
    setTimeout(function () {
      map.invalidateSize();
    }, 200);

    initializedMaps[container.id] = map;
  }

  window.initializeMaps = initializeMaps;
  initializeMaps();
});
