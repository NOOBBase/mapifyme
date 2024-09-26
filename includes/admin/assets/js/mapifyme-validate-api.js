jQuery(document).ready(function ($) {
  $('#google_maps_api_key').on('change', function () {
    var apiKey = $(this).val();

    // Disable the save button while validation is in progress
    $('input[type="submit"]').prop('disabled', true);

    // Clear previous messages
    $('#api-validation-result').remove();

    // Perform the API key validation
    $.ajax({
      url: mapifymeApiValidation.ajax_url,
      method: 'POST',
      data: {
        action: 'mapifyme_validate_google_api_key',
        api_key: apiKey,
        nonce: mapifymeApiValidation.nonce,
      },
      success: function (response) {
        if (response.success) {
          $('#google_maps_api_key_wrapper').after(
            '<p id="api-validation-result" style="color:green;">' +
              response.data.message +
              '</p>'
          );
        } else {
          $('#google_maps_api_key_wrapper').after(
            '<p id="api-validation-result" style="color:red;">' +
              response.data.message +
              '</p>'
          );
        }

        // Enable the save button again
        $('input[type="submit"]').prop('disabled', false);
      },
      error: function () {
        $('#google_maps_api_key_wrapper').after(
          '<p id="api-validation-result" style="color:red;">Error validating the API key.</p>'
        );
        $('input[type="submit"]').prop('disabled', false);
      },
    });
  });
});
