jQuery(document).ready(function($) {
    function initializeMap() {
        if (typeof google === 'undefined') {
            setTimeout(initializeMap, 100);
            return;
        }

        const addressInput = document.getElementById('search-address');
        if (addressInput) {
            const autocomplete = new google.maps.places.Autocomplete(addressInput);
        }
    }

    initializeMap();

    $('#helper-location-form').on('submit', function(e) {
        e.preventDefault();
        const resultsContainer = $('#nearby-helpers-results');
        resultsContainer.html('<p>' + nearbyHelpersData.i18n.searching + '</p>');
        
        const address = $('#search-address').val();
        const geocoder = new google.maps.Geocoder();
        
        geocoder.geocode({ address: address }, function(results, status) {
            if (status === 'OK') {
                const latitude = results[0].geometry.location.lat();
                const longitude = results[0].geometry.location.lng();
                
                $.ajax({
                    url: nearbyHelpersData.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'find_nearby_helpers',
                        nonce: nearbyHelpersData.nonce,
                        latitude: latitude,
                        longitude: longitude
                    },
                    success: function(response) {
                        if (response.success) {
                            displayResults(response.data);
                        } else {
                            resultsContainer.html('<p>' + nearbyHelpersData.i18n.error + '</p>');
                        }
                    },
                    error: function() {
                        resultsContainer.html('<p>' + nearbyHelpersData.i18n.error + '</p>');
                    }
                });
            } else {
                resultsContainer.html('<p>Could not find that address. Please try again.</p>');
            }
        });
    });
});

function displayResults(helpers) {
    const resultsContainer = jQuery('#nearby-helpers-results');
    
    if (!helpers || helpers.length === 0) {
        resultsContainer.html('<p>' + nearbyHelpersData.i18n.noResults + '</p>');
        return;
    }

    const helperList = jQuery('<div>').addClass('nearby-helpers-grid');
    
    helpers.forEach(function(helper) {
        const helperCard = jQuery('<div>').addClass('helper-card');
        
        if (helper.profile_picture) {
            jQuery('<div>')
                .addClass('helper-profile-picture')
                .append(
                    jQuery('<img>')
                        .attr('src', helper.profile_picture)
                        .attr('alt', helper.full_name)
                )
                .appendTo(helperCard);
        }
        
        const details = jQuery('<div>').addClass('helper-details');
        
        jQuery('<h3>').text(helper.full_name).appendTo(details);
        
        if (helper.telephone) {
            jQuery('<p>').html(`<strong>Phone:</strong> ${helper.telephone}`).appendTo(details);
        }
        
        if (helper.email) {
            jQuery('<p>').html(`<strong>Email:</strong> ${helper.email}`).appendTo(details);
        }
        
        const distance = parseFloat(helper.distance);
        const distanceText = !isNaN(distance) ? `${distance.toFixed(1)} miles` : 'Distance not available';
        jQuery('<p>').html(`<strong>Distance:</strong> ${distanceText}`).appendTo(details);
        
        if (helper.additional_info) {
            jQuery('<p>').html(`<strong>Additional Info:</strong> ${helper.additional_info}`).appendTo(details);
        }
        
        details.appendTo(helperCard);
        helperCard.appendTo(helperList);
    });
    
    resultsContainer.empty().append(helperList);
}