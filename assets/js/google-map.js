// Remove the wrapping in document.addEventListener if not needed
// Define initializeGoogleMap in the global scope
function initializeGoogleMap(
  mapContainerId,
  latitude,
  longitude,
  popupContentArray, // Array of marker data
  enableGeolocation,
  showGetLocationButton,
  zoomLevel
) {
  // Ensure popupContentArray is an array
  if (!Array.isArray(popupContentArray)) {
    popupContentArray = [popupContentArray];
  }

  var mapElement = document.getElementById(mapContainerId);
  if (mapElement) {
    var mapOptions = {
      zoom: parseInt(zoomLevel) || 16,
      center: { lat: parseFloat(latitude), lng: parseFloat(longitude) },
    };

    var map = new google.maps.Map(mapElement, mapOptions);

    var markers = []; // Keep track of markers

    // Loop through the popupContentArray to add markers
    popupContentArray.forEach(function (markerData) {
      var marker = new google.maps.Marker({
        position: {
          lat: parseFloat(markerData.latitude),
          lng: parseFloat(markerData.longitude),
        },
        map: map,
        draggable: false,
      });

      var infoWindow = new google.maps.InfoWindow({
        content: markerData.popup_content,
      });

      marker.addListener('click', function () {
        infoWindow.open(map, marker);
      });

      markers.push(marker);
    });

    // Handle geolocation
    if (enableGeolocation) {
      getCurrentLocation(map, markers, popupContentArray);
    }

    // Add the current location button if needed
    if (enableGeolocation && showGetLocationButton === true) {
      addCurrentLocationButton(map, markers, popupContentArray);
    }
  } else {
    console.error('Map element not found.');
  }
}

// Function to get the user's current location and update the map
function getCurrentLocation(map, markers, popupContentArray) {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function (position) {
        var currentLatLng = {
          lat: position.coords.latitude,
          lng: position.coords.longitude,
        };

        // Reverse geocoding to fetch the address
        fetchAddress(currentLatLng.lat, currentLatLng.lng, function (address) {
          var updatedPopupContent;

          if (markers.length === 1) {
            // Single marker: update its position
            var marker = markers[0];

            updatedPopupContent = popupContentArray[0].popup_content
              .replace('{latitude}', currentLatLng.lat)
              .replace('{longitude}', currentLatLng.lng)
              .replace('{address}', address || 'Address not available');

            marker.setPosition(currentLatLng);

            var infoWindow = new google.maps.InfoWindow({
              content: updatedPopupContent,
            });

            marker.addListener('click', function () {
              infoWindow.open(map, marker);
            });
          } else {
            // Multiple markers: add a new marker at current location
            updatedPopupContent =
              '<strong>Your Current Location</strong><br>' +
              'Latitude: ' +
              currentLatLng.lat +
              '<br>' +
              'Longitude: ' +
              currentLatLng.lng +
              '<br>' +
              'Address: ' +
              (address || 'Address not available');

            var marker = new google.maps.Marker({
              position: currentLatLng,
              map: map,
              draggable: false,
              icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png', // Different icon for current location
            });

            var infoWindow = new google.maps.InfoWindow({
              content: updatedPopupContent,
            });

            marker.addListener('click', function () {
              infoWindow.open(map, marker);
            });

            markers.push(marker);
          }

          map.setCenter(currentLatLng);

          // Update form fields if they exist
          var latitudeField = document.getElementById('mapifyme_latitude');
          var longitudeField = document.getElementById('mapifyme_longitude');

          if (latitudeField && longitudeField) {
            latitudeField.value = currentLatLng.lat;
            longitudeField.value = currentLatLng.lng;
          }
        });
      },
      function (error) {
        alert('Error fetching your location: ' + error.message);
      }
    );
  } else {
    alert('Geolocation is not supported by this browser.');
  }
}

// Function to add a "Get Current Location" button to the map
function addCurrentLocationButton(map, markers, popupContentArray) {
  var currentLocationButton = document.createElement('button');
  currentLocationButton.textContent = 'Get Current Location';
  currentLocationButton.classList.add('current-location-btn');
  map.controls[google.maps.ControlPosition.TOP_CENTER].push(
    currentLocationButton
  );

  currentLocationButton.addEventListener('click', function () {
    getCurrentLocation(map, markers, popupContentArray);
  });
}

// Function to fetch the address using reverse geocoding
function fetchAddress(lat, lng, callback) {
  var geocoder = new google.maps.Geocoder();
  geocoder.geocode(
    { location: { lat: lat, lng: lng } },
    function (results, status) {
      if (status === 'OK' && results[0]) {
        callback(results[0].formatted_address);
      } else {
        console.error('Geocoder failed due to: ' + status);
        callback(null);
      }
    }
  );
}

// Expose the function globally
window.initializeGoogleMap = initializeGoogleMap;
