document.addEventListener('DOMContentLoaded', function () {
  const initializedMaps = {};

  function initializeMaps() {
    const mapContainers = document.querySelectorAll('[data-mapifyme-map]');

    mapContainers.forEach(function (container) {
      const mapId = container.id;

      if (initializedMaps[mapId]) {
        console.log(`Map with ID ${mapId} is already initialized.`);
        return;
      }

      const latitude = container.getAttribute('data-latitude');
      const longitude = container.getAttribute('data-longitude');
      const templateClass = container.getAttribute('data-template');
      const popupHTML = container.getAttribute('data-popup-html');
      const draggableMarker =
        container.getAttribute('data-draggable') === 'true';

      if (!latitude || !longitude) {
        if (navigator.geolocation) {
          navigator.geolocation.getCurrentPosition(
            function (position) {
              const lat = position.coords.latitude;
              const lon = position.coords.longitude;
              initMap(
                container,
                lat,
                lon,
                templateClass,
                popupHTML,
                draggableMarker
              );

              // Optionally reverse geocode current location
              reverseGeocode(lat, lon, mapId);
            },
            function (error) {
              console.error('Geolocation error: ', error.message);
              alert('Unable to retrieve your location.');
            }
          );
        } else {
          console.error('Geolocation is not supported by this browser.');
          alert('Geolocation is not supported by your browser.');
        }
      } else {
        // Initialize map with provided lat/lng
        initMap(
          container,
          latitude,
          longitude,
          templateClass,
          popupHTML,
          draggableMarker
        );
      }
    });
  }

  function initMap(
    container,
    latitude,
    longitude,
    templateClass,
    popupHTML,
    draggableMarker
  ) {
    const mapId = container.id;
    const map = L.map(mapId).setView([latitude, longitude], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data &copy; OpenStreetMap contributors',
    }).addTo(map);

    const marker = L.marker([latitude, longitude], {
      draggable: draggableMarker,
    }).addTo(map);
    marker.bindPopup(
      `<div class="${templateClass || 'default-template'}">${popupHTML}</div>`
    );

    if (draggableMarker) {
      marker.on('dragend', function () {
        const latLng = marker.getLatLng();
        reverseGeocode(latLng.lat, latLng.lng, mapId);
      });
    }

    initializedMaps[mapId] = map;

    setTimeout(function () {
      map.invalidateSize();
    }, 200);
  }

  window.initializeMaps = initializeMaps;
  initializeMaps();
});

function reverseGeocode(lat, lon, mapId) {
  if (
    typeof mapifymeGeotag === 'undefined' ||
    !mapifymeGeotag.reverse_geocode_api_url
  ) {
    console.error(
      'mapifymeGeotag is not defined or missing reverse_geocode_api_url'
    );
    return;
  }

  // Ensure jQuery is loaded before calling $.get()
  if (typeof jQuery !== 'undefined') {
    // Make the reverse geocode request
    jQuery
      .get(mapifymeGeotag.reverse_geocode_api_url, {
        lat: lat,
        lon: lon,
        format: 'json',
        addressdetails: 1,
      })
      .done(function (data) {
        if (data && data.address) {
          const address = data.address;
          // Update your address fields or log them
          console.log('Address:', address);
        } else {
          alert('Address not found for these coordinates.');
        }
      })
      .fail(function (jqXHR, textStatus, errorThrown) {
        alert('Error fetching address.');
      });
  } else {
    console.error('jQuery is not loaded');
  }
}
