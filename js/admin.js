jQuery(document).ready(function($) {
    $('#test-api-button').on('click', function(e) {
        e.preventDefault();
        var apiKey = $('#api_key').val();

        $.ajax({
            url: ctsAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'test_translation_api',
                api_key: apiKey,
                nonce: ctsAdmin.nonce
            },
            beforeSend: function() {
                $('#api-test-result').text(ctsAdmin.testing);
            },
            success: function(response) {
                $('#api-test-result').text(response.data);
            },
            error: function() {
                $('#api-test-result').text(ctsAdmin.error);
            }
        });
    });
});
