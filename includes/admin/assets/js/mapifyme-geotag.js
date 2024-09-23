// mapifyme-geotag.js
jQuery(document).ready(function ($) {
  /**
   * Function to update the hidden latitude and longitude fields when address is selected
   * @param {number} lat - Latitude
   * @param {number} lon - Longitude
   * @param {number} postId - Post ID
   */
  function updateFieldsAndSave(lat, lon, postId) {
    // Update the latitude and longitude fields
    $('#mapifyme_latitude').val(lat);
    $('#mapifyme_longitude').val(lon);
    console.log(`Updated fields: Latitude = ${lat}, Longitude = ${lon}`);

    // Trigger reverse geocoding to update address fields
    reverseGeocode(lat, lon, postId);

    // Update map marker position if map exists (Search Tab logic reuse)
    if (typeof window.updateMapMarker === 'function') {
      window.updateMapMarker(lat, lon);
    }
  }

  /**
   * Geocoding: Address to Coordinates
   */
  $('#mapifyme-fetch-coordinates').on('click', function () {
    const street = $('#mapifyme_street').val().trim();
    const city = $('#mapifyme_city').val().trim();
    const state = $('#mapifyme_state').val().trim();
    const zip = $('#mapifyme_zip').val().trim();
    const country = $('#mapifyme_country').val().trim();
    const postId = $('#post_ID').val();

    let query = `${street}, ${city}, ${state}, ${zip}, ${country}`.trim();
    console.log('Sending Address Query:', query);

    if (query) {
      $.get(mapifymeGeotag.geocode_api_url, {
        q: query,
        format: 'json',
        addressdetails: 1,
      })
        .done(function (data) {
          if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lon = parseFloat(data[0].lon);
            console.log(
              `Received coordinates: Latitude = ${lat}, Longitude = ${lon}`
            );

            updateFieldsAndSave(lat, lon, postId); // Save geotag and update fields
          } else {
            alert(__('Coordinates not found for this address.', 'mapifyme'));
            console.warn('No coordinates found for the provided address.');
          }
        })
        .fail(function () {
          alert(__('Error fetching coordinates.', 'mapifyme'));
          console.error('AJAX request failed while fetching coordinates.');
        });
    } else {
      alert(__('Please enter a complete address.', 'mapifyme'));
      console.warn('Incomplete address entered.');
    }
  });

  /**
   * Reverse Geocoding: Coordinates to Address
   */
  $('#mapifyme-fetch-address').on('click', function () {
    const lat = $('#mapifyme_latitude').val().trim();
    const lon = $('#mapifyme_longitude').val().trim();
    const postId = $('#post_ID').val();

    if (lat && lon) {
      reverseGeocode(parseFloat(lat), parseFloat(lon), postId);
      console.log(`Fetching address for Latitude = ${lat}, Longitude = ${lon}`);
    } else {
      alert(__('Please enter both latitude and longitude.', 'mapifyme'));
      console.warn('Latitude or Longitude not provided.');
    }
  });

  /**
   * Optional: Automatically perform reverse geocoding after geocoding
   */
  function autoReverseGeocode(lat, lon, postId) {
    reverseGeocode(lat, lon, postId);
    console.log('Automatically performing reverse geocoding.');
  }
});
