jQuery(document).ready(function ($) {
    
    if (ajax_object.verified === '1') {
        $('#apsispro-us-shortcode-generator').show();
        $('#apsispro-us-verified-msg').show();
    } else {
        $('#apsispro-us-shortcode-generator').hide();
        $('#apsispro-us-verified-msg').hide();
    }

    $('#us-generate-shortcode-button').click(function (event) {
        event.preventDefault(); // cancel default behavior

        $generatedCode = '[apsispro_user_sub id="'

        $firstMailinglist = true;

        $('.apsispro_us_mailinglist_checkboxes input').each(function () {

            if ($(this).is(':checked')) {
                if ($firstMailinglist) {
                    $generatedCode += $(this).val();
                    $firstMailinglist = false;
                } else {
                    $generatedCode += ',' + $(this).val();
                }
            }
        });

        $generatedCode += '"';

        $generatedCode += ' text="'

        $firstMailinglist = true;

        $('.apsispro_us_mailinglist_checkboxes input').each(function () {

            if ($(this).is(':checked')) {
                if ($firstMailinglist) {
                    $generatedCode += $(this).attr('name');
                    $firstMailinglist = false;
                } else {
                    $generatedCode += ',' + $(this).attr('name');
                }
            }
        });

        $generatedCode += '"]';

        $('#apsispro-us-generated-code').val($generatedCode);
    });

});
