var Biller = window.Biller || {};

document.addEventListener('DOMContentLoaded', function () {
    let captureUrl = document.getElementById('biller-order-capture-url').value,
        cancelUrl = document.getElementById('biller-order-cancel-url').value;

    let copyButton = document.getElementById('biller-copy-btn'),
        captureButton = document.getElementById('biller-capture-button'),
        cancelButton = document.getElementById('biller-cancel-button');

    copyButton.addEventListener('click', function () {
        let paymentLink = document.getElementById('biller-payment-link-input');
        paymentLink.select();
        document.execCommand("copy", false);
    });

    captureButton.addEventListener('click', function () {
        Biller.Ajax.post(captureUrl, null, function (response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert(response.message);
            }
        }, 'json', true);
    });
    cancelButton.addEventListener('click', function () {
        Biller.Ajax.post(cancelUrl, null, function (response) {
            if (response.success) {
                window.location.reload();
            } else {
                alert(response.message);
            }
        }, 'json', true);
    });

    // Hook on the delete_refund WooCommerce ajax success to refresh order page and show detach notes
    jQuery(function ($) {
        $(document).ajaxSuccess(function (event, xhr, settings) {
            if (
                settings &&
                settings.data &&
                settings.data.indexOf('woocommerce_delete_refund') !== -1
            ) {
                window.location.reload();
            }
        });
    });
});
