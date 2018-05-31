var pendingResponse = (function() {

    var process = 0,
        checkout_page = '',
        pendingFor = 10000;

    var initialize = function() {
        jQuery('div.woocommerce:not(.widget)').block({message: null,overlayCSS: {background: '#fff',opacity: 0.6}});

        process++;
        pending();
    };

    var pending = function() {
        setTimeout(function() {
            check_status();
        }, pendingFor / 2);
    };

    var redirect = function(uri) {
        jQuery('div.woocommerce').unblock();

        if (!uri) {
            window.location.href = checkout_page;
            return;
        }

        window.location.href = uri;
    };

    var check_status = function() {
        jQuery.ajax({
            url: base.url + 'wc_gateway_ilpg_tranzila',
            method: 'post',
            data: {action: 'pending_request'},
            success: function(response) {
                var json_response = JSON.parse(response);

                if (!json_response)
                    return;

                if (json_response.result && json_response.result === 'failure') {
                    redirect((json_response.redirect) ? json_response.redirect : null);
                    return;
                }

                if (json_response.result && json_response.result === 'success') {
                    redirect((json_response.redirect) ? json_response.redirect : null);
                    return;
                }

                if (process === 2) {
                    redirect((json_response.redirect) ? json_response.redirect : null);
                    return
                }

                process++;
            },
            error: function(xhr) {
                alert('WAT!?@#?');
            }
        })
    };

    return {
        init: function() {
            if (process)
                return;

            initialize();
        }
    }
})();