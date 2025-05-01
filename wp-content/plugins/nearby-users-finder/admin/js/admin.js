// Define the initialization function that Google Maps will call
function initGoogleMaps() {
    jQuery(document).ready(function($) {
        console.log('Admin JS loaded');
        
        // Initialize Google Places Autocomplete
        const addressInput = document.getElementById('address');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        
        console.log('Form elements:', {
            addressInput: addressInput,
            latitudeInput: latitudeInput,
            longitudeInput: longitudeInput
        });

        if (addressInput) {
            console.log('Initializing Google Places Autocomplete');
            const autocomplete = new google.maps.places.Autocomplete(addressInput);
            
            // When a place is selected, update the hidden lat/lng fields
            autocomplete.addListener('place_changed', function() {
                const place = autocomplete.getPlace();
                console.log('Place selected:', place);
                
                if (place.geometry && place.geometry.location) {
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    
                    console.log('Setting coordinates:', { lat, lng });
                    
                    if (latitudeInput && longitudeInput) {
                        latitudeInput.value = lat;
                        longitudeInput.value = lng;
                        
                        // Verify values were set
                        console.log('Input values after setting:', {
                            lat: latitudeInput.value,
                            lng: longitudeInput.value
                        });
                    } else {
                        console.error('Latitude or longitude input elements not found');
                    }
                } else {
                    console.error('No geometry found in place object:', place);
                }
            });
        } else {
            console.error('Address input element not found');
        }

        // Add form submission debugging
        $('form').on('submit', function(e) {
            console.log('Form submitting. Form data:', {
                latitude: $('#latitude').val(),
                longitude: $('#longitude').val(),
                address: $('#address').val()
            });
        });
    });
}

// Fallback in case the callback doesn't work
if (window.google && window.google.maps) {
    initGoogleMaps();
}
