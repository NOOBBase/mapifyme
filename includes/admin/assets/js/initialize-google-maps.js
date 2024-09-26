document.addEventListener('DOMContentLoaded', function () {
  // Initialize all Google Maps
  function initializeGoogleMaps() {
    const mapContainers = document.querySelectorAll('[data-mapifyme-map]');

    mapContainers.forEach(function (container) {
      const latitude = parseFloat(container.getAttribute('data-latitude')) || 0;
      const longitude =
        parseFloat(container.getAttribute('data-longitude')) || 0;
      const draggableMarker =
        container.getAttribute('data-draggable') === 'true';
      const mapId = container.id;

      // Create the map options
      const mapOptions = {
        zoom: 13,
        center: { lat: latitude, lng: longitude },
      };

      // Initialize the Google Map
      const map = new google.maps.Map(
        document.getElementById(mapId),
        mapOptions
      );

      // Create the marker
      const marker = new google.maps.Marker({
        position: { lat: latitude, lng: longitude },
        map: map,
        draggable: draggableMarker,
      });

      // Marker drag event to update latitude/longitude and reverse geocode
      if (draggableMarker) {
        marker.addListener('dragend', function () {
          const latLng = marker.getPosition();
          const newLat = latLng.lat();
          const newLng = latLng.lng();

          // Update the latitude and longitude fields in the form
          document.getElementById('mapifyme_latitude').value = newLat;
          document.getElementById('mapifyme_longitude').value = newLng;

          // Trigger reverse geocoding to get the updated address
          reverseGeocode(newLat, newLng);
        });
      }
    });
  }

  // Reverse geocoding to get the address and update form fields
  function reverseGeocode(lat, lng) {
    const geocoder = new google.maps.Geocoder();
    geocoder.geocode(
      { location: { lat: lat, lng: lng } },
      function (results, status) {
        if (status === 'OK' && results[0]) {
          const address = results[0].address_components;
          // Update address fields with the geocoded address
          document.getElementById('mapifyme_street').value =
            getAddressComponent(address, 'route');
          document.getElementById('mapifyme_city').value = getAddressComponent(
            address,
            'locality'
          );
          document.getElementById('mapifyme_state').value = getAddressComponent(
            address,
            'administrative_area_level_1'
          );
          document.getElementById('mapifyme_zip').value = getAddressComponent(
            address,
            'postal_code'
          );
          document.getElementById('mapifyme_country').value =
            getAddressComponent(address, 'country');

          // Optionally update the full address field
          document.getElementById('mapifyme_search_address').value =
            results[0].formatted_address;
        } else {
          alert('Failed to retrieve address: ' + status);
        }
      }
    );
  }

  // Helper function to extract a specific address component
  function getAddressComponent(components, type) {
    const component = components.find((c) => c.types.includes(type));
    return component ? component.long_name : '';
  }

  // Save location data via AJAX
  jQuery(document).ready(function ($) {
    $('#mapifyme_save_location').on('click', function () {
      const postId = $('#post_ID').val();
      const lat = $('#mapifyme_latitude').val();
      const lon = $('#mapifyme_longitude').val();

      if (!lat || !lon) {
        alert('Please set both latitude and longitude.');
        return;
      }

      // Collect address data
      const address = {
        latitude: lat,
        longitude: lon,
        street: $('#mapifyme_street').val(),
        city: $('#mapifyme_city').val(),
        state: $('#mapifyme_state').val(),
        zip: $('#mapifyme_zip').val(),
        country: $('#mapifyme_country').val(),
      };

      // Send the geotag data to the server for saving
      updateGeotagData(postId, address);
    });
  });

  // Function to update geotag data via AJAX
  function updateGeotagData(postId, data) {
    jQuery.ajax({
      url: mapifymeGeotag.ajax_url,
      method: 'POST',
      data: {
        action: 'mapifyme_update_geotag_data',
        nonce: mapifymeGeotag.nonce,
        post_id: postId,
        geotag_data: data,
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
        } else {
          alert('Failed to update geotag data.');
        }
      },
      error: function () {
        alert('Error updating geotag data.');
      },
    });
  }

  // Initialize Google Maps on page load
  initializeGoogleMaps();
});
