jQuery(document).ready(function ($) {
  function toggleGoogleMapsApiKeyField() {
    var mapProvider = $('#map_provider').val();
    if (mapProvider === 'google_maps') {
      $('#google_maps_api_key').closest('tr').show();
    } else {
      $('#google_maps_api_key').closest('tr').hide();
    }
  }

  // Run on page load
  toggleGoogleMapsApiKeyField();

  // Run when the map provider is changed
  $('#map_provider').on('change', function () {
    toggleGoogleMapsApiKeyField();
  });
});
