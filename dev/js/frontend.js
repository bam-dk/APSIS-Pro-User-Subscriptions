jQuery(document).ready(function ($) {

    $.ajaxSetup({async: false});

    /*
     Handle clicks on submit button in subscription form
     */
    $('.apsispro-user-subscription-form').submit(function (e) {
        var $currentForm = $(this);
        var email = $currentForm.find('.apsispro-us-signup-email').val();
        var name = $currentForm.find('.apsispro-us-signup-name').val();

        var listElement = $currentForm.find('.apsispro-us-signup-mailinglist-id');
        if (listElement.length > 0) {
            registerSubscriber('register', $currentForm, listElement.val(), email, name, false);
        }
        else {
            var noListID = true;
            $currentForm.find('.apsispro-us-signup-mailinglists-id').each(function () {
                if ($(this).is(':checked')) {
                    registerSubscriber('register', $currentForm, $(this).val(), email, name, false);
                    noListID = false;
                }
                else {
                    removeSubscriber($currentForm, $(this).val(), email);
                }
            });
            if ( noListID ) {
                if ( apsispro_us_ajax_object.default_list !== '' ) {
                    registerSubscriber('register', $currentForm, apsispro_us_ajax_object.default_list, email, name, true);
                }
            } else {
                if ( apsispro_us_ajax_object.default_subscriber_list !== '' ) {
                    registerSubscriber('default-sub', $currentForm, apsispro_us_ajax_object.default_subscriber_list, email, name, true);
                }
            }
        }

        return false;

    });

    /*
     Register subscriber to mailinglist with AJAX call
     */
    function registerSubscriber(mode, $currentForm, listid, email, name, reload) {
        var data = {
            'action': 'apsispro_us_action',
            'listid': listid,
            'email': email,
            'name': name,
            'mode': mode
        };

        $.post(apsispro_us_ajax_object.ajax_url, data, function (response) {

            if (response !== undefined || response !== -1) {
                var obj = jQuery.parseJSON(response);
                if (obj['Code'] === 1) { //Subscription successful
                    $currentForm.next('.apsispro-us-signup-response').text('');
                    window.location.reload();
                } else if (obj['Message'].indexOf('is not a valid e-mail address') > -1) { //E-mail address invalid
                    $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_email);
                } else { //Error
                    $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_standard);
                }
            } else { //Error
                $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_standard);
            }

        });
    }

    /*
     Remove subscriber from mailinglist with AJAX call
     */
    function removeSubscriber($currentForm, listid, email) {
        var data = {
            'action': 'apsispro_us_action',
            'listid': listid,
            'email': email,
            'mode': 'remove'
        };

        $.post(apsispro_us_ajax_object.ajax_url, data, function (response) {

            if (response !== undefined || response !== -1) {
                var obj = jQuery.parseJSON(response);
                if (obj['Code'] === 1) { //Removal successful
                    $currentForm.next('.apsispro-us-signup-response').text('');
                } else if (obj['Message'].indexOf('is not a valid e-mail address') > -1) { //E-mail address invalid
                    $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_email);
                } else { //Error
                    $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_standard);
                }
            } else { //Error
                $currentForm.next('.apsispro-us-signup-response').text(apsispro_us_ajax_object.error_msg_standard);
            }
            
        });
    }

});
