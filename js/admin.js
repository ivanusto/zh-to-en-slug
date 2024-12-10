jQuery(document).ready(function($) {
    $('#test-api-button').on('click', function(e) {
        e.preventDefault();
        var apiKey = $('#api_key').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_translation_api',
                api_key: apiKey,
                nonce: $('#cts_nonce').val()
            },
            beforeSend: function() {
                $('#api-test-result').html('測試中...');
            },
            success: function(response) {
                $('#api-test-result').html(response.data);
            },
            error: function() {
                $('#api-test-result').html('測試失敗，請檢查網路連線');
            }
        });
    });
});
