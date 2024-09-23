// initialize-maps.js
document.addEventListener('DOMContentLoaded', function () {
  // Global object to track initialized maps
  const initializedMaps = {};
  jQuery(document).ready(function ($) {
    // Save Location button click event
    $('#mapifyme_save_location').on('click', function () {
      const postId = $('#post_ID').val(); // Get the post ID
      const lat = $('#mapifyme_latitude').val(); // Get the latitude
      const lon = $('#mapifyme_longitude').val(); // Get the longitude

      if (!lat || !lon) {
        alert('Please set both latitude and longitude.');
        return;
      }

      // Collect the address fields if needed (street, city, etc.)
      const address = {
        latitude: lat,
        longitude: lon,
        street: $('#mapifyme_street').val(),
        city: $('#mapifyme_city').val(),
        state: $('#mapifyme_state').val(),
        zip: $('#mapifyme_zip').val(),
        country: $('#mapifyme_country').val(),
      };

      // Send the data to the backend for saving
      updateGeotagData(postId, address);
    });
  });

  /**
   * Function to initialize maps for containers
   */
  function initializeMaps() {
    const mapContainers = document.querySelectorAll('[data-mapifyme-map]');

    mapContainers.forEach(function (container) {
      const mapId = container.id;

      // Check if the map is already initialized
      if (initializedMaps[mapId]) {
        //console.log(`Map with ID ${mapId} is already initialized.`);
        return; // Skip re-initializing
      }

      const latitude = parseFloat(container.getAttribute('data-latitude')) || 0;
      const longitude =
        parseFloat(container.getAttribute('data-longitude')) || 0;
      const templateClass =
        container.getAttribute('data-template') || 'default-template';
      const popupHTML = container.getAttribute('data-popup-html') || '';
      const draggableMarker =
        container.getAttribute('data-draggable') === 'true'; // Check if the marker should be draggable

      // Initialize map for each container
      const map = L.map(container.id).setView([latitude, longitude], 13);

      // Add OpenStreetMap tiles
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
          'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      }).addTo(map);

      // Add a marker to the map with draggable functionality
      const marker = L.marker([latitude, longitude], {
        draggable: draggableMarker,
      }).addTo(map);
      marker.bindPopup(`<div class="${templateClass}">${popupHTML}</div>`);

      // Handle marker dragging (if draggable)
      if (draggableMarker) {
        marker.on('dragend', function () {
          const latLng = marker.getLatLng();
          console.log(
            `Marker dragged to: Latitude = ${latLng.lat}, Longitude = ${latLng.lng}`
          );

          // Update the hidden fields and perform reverse geocoding
          jQuery('#mapifyme_latitude').val(latLng.lat);
          jQuery('#mapifyme_longitude').val(latLng.lng);
          const postId = jQuery('#post_ID').val();
          reverseGeocode(latLng.lat, latLng.lng, postId);
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

  /**
   * Function to reverse geocode coordinates into an address
   * @param {number} lat - Latitude
   * @param {number} lon - Longitude
   * @param {number} postId - Post ID
   */
  /**
   * Function to reverse geocode coordinates into an address
   * @param {number} lat - Latitude
   * @param {number} lon - Longitude
   * @param {number} postId - Post ID (optional, can be null if no save is needed)
   */
  function reverseGeocode(lat, lon, postId) {
    console.log(`Reverse geocoding for Latitude = ${lat}, Longitude = ${lon}`);

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

    jQuery
      .get(mapifymeGeotag.reverse_geocode_api_url, {
        lat: lat,
        lon: lon,
        format: 'json',
        addressdetails: 1,
      })
      .done(function (data) {
        console.log('Reverse Geocode Response:', data);

        if (data && data.address) {
          // Update address fields based on the response
          const address = data.address;
          const fullAddress = `${address.road || ''}, ${
            address.city || address.town || address.village || ''
          }, ${address.state || ''}, ${address.postcode || ''}, ${
            address.country || ''
          }`.trim();

          // Update individual address fields in the form
          jQuery('#mapifyme_street').val(address.road || '');
          jQuery('#mapifyme_city').val(
            address.city || address.town || address.village || ''
          );
          jQuery('#mapifyme_state').val(address.state || '');
          jQuery('#mapifyme_zip').val(address.postcode || '');
          jQuery('#mapifyme_country').val(address.country || '');

          // **Update the Search Address input field in real time**
          jQuery('#mapifyme_search_address').val(fullAddress);

          // REMOVE the database update. Only update the form fields here.
        } else {
          alert('Address not found for these coordinates.');
        }
      })
      .fail(function () {
        alert('Error fetching address from coordinates.');
      });
  }

  /**
   * Function to update geotag data via AJAX
   * @param {number} postId - Post ID
   * @param {object} data - Geotag data
   */
  function updateGeotagData(postId, data) {
    console.log('Sending Data to Update:', data); // Log data being sent to the server

    // Check if mapifymeGeotag and ajax_url are defined
    if (typeof mapifymeGeotag === 'undefined' || !mapifymeGeotag.ajax_url) {
      console.error('mapifymeGeotag is not defined or missing ajax_url');
      return;
    }

    jQuery.ajax({
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

  // Attach functions to the global window object for accessibility
  window.initializeMaps = initializeMaps;
  window.reverseGeocode = reverseGeocode;
  window.updateGeotagData = updateGeotagData;

  // Initialize all maps and geocoding logic
  initializeMaps();
});
