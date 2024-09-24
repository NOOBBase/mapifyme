jQuery(document).ready(function ($) {
  function toggleGoogleMapsApiKey() {
    var mapProvider = $('#map_provider').val();
    if (mapProvider === 'google_maps') {
      $('#google_maps_api_key_wrapper').closest('tr').show(); // This hides the entire row
    } else {
      $('#google_maps_api_key_wrapper').closest('tr').hide(); // Hides the row when Leaflet is selected
    }
  }

  // Initial call to hide/show the field on page load
  toggleGoogleMapsApiKey();

  // Trigger toggling when the map provider changes
  $('#map_provider').change(function () {
    toggleGoogleMapsApiKey();
  });
});
