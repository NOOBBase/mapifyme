document.addEventListener('DOMContentLoaded', function () {
  initializeGeolocationMaps();
});

function initializeGeolocationMaps(retries = 5) {
  if (typeof mapifymeGeotag === 'undefined') {
    console.error('mapifymeGeotag object not found.');
    return;
  }

  const mapContainers = document.querySelectorAll(
    `[data-mapifyme-geolocation]`
  );

  mapContainers.forEach(function (mapContainer) {
    const mapContainerId = mapContainer.id;
    const showLatitude =
      mapContainer.getAttribute('data-show-latitude') === 'true';
    const showLongitude =
      mapContainer.getAttribute('data-show-longitude') === 'true';
    const showAddress =
      mapContainer.getAttribute('data-show-address') === 'true';

    // Ensure the infoContainer exists based on the dynamically generated ID
    const infoContainer = document.getElementById(`${mapContainerId}-info`);

    if (!mapContainer) {
      console.error(`Map container with ID ${mapContainerId} not found.`);

      if (retries > 0) {
        setTimeout(function () {
          initializeGeolocationMaps(retries - 1);
        }, 100);
      } else {
        console.error('Max retries reached. Map initialization failed.');
      }
      return;
    }

    if (!infoContainer) {
      console.error(`Info container with ID ${mapContainerId}-info not found.`);
      return;
    }

    // Ensure the browser supports geolocation
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const latitude = position.coords.latitude;
          const longitude = position.coords.longitude;

          // Set draggableMarker based on a condition or default it to false
          const draggableMarker =
            mapContainer.getAttribute('data-draggable') === 'true';

          // Display the current latitude and longitude
          if (showLatitude || showLongitude) {
            let infoText =
              '<strong>' +
              (mapifymeGeotag.current_location_label ||
                'Your Current Location:') +
              '</strong><br>';
            if (showLatitude) {
              infoText += 'Latitude: ' + latitude + '<br>';
            }
            if (showLongitude) {
              infoText += 'Longitude: ' + longitude + '<br>';
            }
            infoContainer.innerHTML = infoText;
          }

          // Initialize the map using Leaflet
          const map = L.map(mapContainer).setView([latitude, longitude], 13);

          // Add OpenStreetMap tiles
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution:
              'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
          }).addTo(map);

          // Use the localized custom popup template
          let popupContent =
            mapifymeGeotag.custom_popup_template || '<b>You are here!</b>';

          // Replace placeholders in the custom popup template
          if (mapifymeGeotag.custom_popup_template) {
            popupContent = popupContent
              .replace('{latitude}', latitude)
              .replace('{longitude}', longitude);
            popupContent = mapifymeGeotag.address
              ? popupContent.replace('{address}', mapifymeGeotag.address)
              : popupContent.replace('{address}', ''); // Use address if available
          }

          // Add marker at the current location
          const marker = L.marker([latitude, longitude], {
            draggable: draggableMarker,
          }).addTo(map);
          marker.bindPopup(popupContent).openPopup();

          // Optionally fetch and display the address using reverse geocoding
          if (showAddress) {
            reverseGeocode(latitude, longitude, function (address) {
              const addressLabel = mapifymeGeotag.address_label || 'Address';
              infoContainer.innerHTML +=
                `<br><strong>${addressLabel}:</strong><br>` + address;

              // Update the popup content with the address
              if (mapifymeGeotag.custom_popup_template) {
                const updatedPopupContent = mapifymeGeotag.custom_popup_template
                  .replace('{latitude}', latitude)
                  .replace('{longitude}', longitude)
                  .replace('{address}', address);
                marker.setPopupContent(updatedPopupContent).openPopup();
              }
            });
          }
        },
        function (error) {
          console.error('Error fetching location:', error.message);
          infoContainer.innerHTML = `<p>${mapifymeGeotag.location_error}</p>`;
        }
      );
    } else {
      console.error('Geolocation is not supported by this browser.');
      infoContainer.innerHTML = `<p>${mapifymeGeotag.geolocation_not_supported}</p>`;
    }
  });
}

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

  if (typeof jQuery !== 'undefined') {
    jQuery
      .get(mapifymeGeotag.reverse_geocode_api_url, {
        lat: lat,
        lon: lon,
        format: 'json',
        addressdetails: 1,
      })
      .done(function (data) {
        if (data && data.display_name) {
          const address = data.display_name;
          mapifymeGeotag.address = address;
          console.log('Address:', address);
          jQuery(document).trigger('mapifymeAddressFetched', {
            mapId: mapId,
            address: address,
          });
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
