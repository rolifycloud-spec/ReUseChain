jQuery(document).ready(function ($) {
    console.log('✅ sanctions-check.js loaded');

    $('#sanctions-check-button').on('click', function () {
        console.log('🔘 Button clicked');

        const userId = $(this).data('user-id');
        const output = $('#sanctions-check-result');
        output.html('⏳ Provjera u toku...');

        $.post(SanctionsCheck.ajax_url, {
            action: 'sanctions_check',
            nonce: SanctionsCheck.nonce,
            user_id: userId
        }, function (response) {
            console.log('📦 AJAX response:', response);
            if (response.success) {
                output.html(response.data);
            } else {
                output.html('<p style="color:red;">❌ Greška: ' + response.data.message + '</p>');
            }
        });
    });
});
