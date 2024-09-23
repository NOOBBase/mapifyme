document.addEventListener('DOMContentLoaded', function () {
  // Global object to track initialized maps
  const initializedMaps = {};

  // Function to initialize maps for containers
  function initializeMaps() {
    const mapContainers = document.querySelectorAll('[data-mapifyme-map]');

    mapContainers.forEach(function (container) {
      const mapId = container.id;

      // Check if the map is already initialized
      if (initializedMaps[mapId]) {
        console.log(`Map with ID ${mapId} is already initialized.`);
        return; // Skip re-initializing
      }

      const latitude = container.getAttribute('data-latitude');
      const longitude = container.getAttribute('data-longitude');
      const templateClass = container.getAttribute('data-template');
      const popupHTML = container.getAttribute('data-popup-html');
      const draggableMarker =
        container.getAttribute('data-draggable') === 'true'; // Check if the marker should be draggable

      // Initialize map for each container
      const map = L.map(container.id).setView(
        [latitude || 0, longitude || 0],
        13
      );

      // Add OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
          'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      }).addTo(map);

      // Add a marker to the map with draggable functionality
      const marker = L.marker([latitude, longitude], {
        draggable: draggableMarker,
      }).addTo(map);
      marker.bindPopup(
        `<div class="${templateClass || 'default-template'}">${popupHTML}</div>`
      );

      // Handle marker dragging (if draggable)
      if (draggableMarker) {
        marker.on('dragend', function () {
          const latLng = marker.getLatLng();
          console.log(`Marker dragged to: ${latLng.lat}, ${latLng.lng}`);

          // Reuse the reverseGeocode function to update the address and database
          reverseGeocode(latLng.lat, latLng.lng, mapId);
        });
      }

      // Mark map as initialized
      initializedMaps[mapId] = map;

      // Force the map to resize after initialization
      setTimeout(function () {
        map.invalidateSize(); // Fixes partial load issue
      }, 200);
    });
  }

  // Attach initializeMaps to the global window object
  window.initializeMaps = initializeMaps;

  // Initialize all maps and geocoding logic
  initializeMaps();
});

// Function to update the address field from coordinates (reverse geocoding)
function reverseGeocode(lat, lon, postId) {
  // Check if mapifymeGeotag is defined and contains the reverse_geocode_api_url
  if (
    typeof mapifymeGeotag === 'undefined' ||
    !mapifymeGeotag.reverse_geocode_api_url
  ) {
    console.error(
      'mapifymeGeotag is not defined or missing reverse_geocode_api_url'
    );
    return;
  }

  $.get(mapifymeGeotag.reverse_geocode_api_url, {
    lat: lat,
    lon: lon,
    format: 'json',
    addressdetails: 1,
    timestamp: new Date().getTime(),
  })
    .done(function (data) {
      console.log('Reverse Geocode Response:', data);

      if (data && data.address) {
        // Update address fields based on the response
        const address = data.address;
        $('#mapifyme_street').val(address.road || '');
        $('#mapifyme_city').val(
          address.city || address.town || address.village || ''
        );
        $('#mapifyme_state').val(address.state || '');
        $('#mapifyme_zip').val(address.postcode || '');
        $('#mapifyme_country').val(address.country || '');

        // Update geotag data in the database
        updateGeotagData(postId, {
          latitude: lat,
          longitude: lon,
          street: address.road || '',
          city: address.city || address.town || address.village || '',
          state: address.state || '',
          zip: address.postcode || '',
          country: address.country || '',
        });
      } else {
        alert('Address not found for these coordinates.');
      }
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
      console.error('Reverse Geocode Request Failed:', textStatus, errorThrown);
      alert('Error fetching address from coordinates.');
    });
}

// Reuse the existing function to update geotag data via AJAX
function updateGeotagData(postId, data) {
  console.log('Sending Data to Update:', data); // Log data being sent to the server

  // Check if mapifymeGeotag and ajax_url are defined
  if (typeof mapifymeGeotag === 'undefined' || !mapifymeGeotag.ajax_url) {
    console.error('mapifymeGeotag is not defined or missing ajax_url');
    return;
  }

  $.ajax({
    url: mapifymeGeotag.ajax_url,
    method: 'POST',
    data: {
      action: 'mapifyme_update_geotag_data',
      nonce: mapifymeGeotag.nonce, // Include the nonce
      post_id: postId,
      geotag_data: data,
    },
    success: function (response) {
      if (response.success) {
        console.log('Geotag data updated successfully.');
        alert(response.data.message);
      } else {
        console.log('Failed to update geotag data.', response);
        alert(response.data || 'Failed to update geotag data.');
      }
    },
    error: function (jqXHR, textStatus, errorThrown) {
      console.error('Error updating geotag data:', textStatus, errorThrown);
      alert('Error updating geotag data.');
    },
  });
}
