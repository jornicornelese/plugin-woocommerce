(function (document) {
    document.addEventListener("DOMContentLoaded", function () {

        let liveWebshop = document.getElementById('woocommerce_biller_business_invoice_live-webShopUID');
        let liveUsername = document.getElementById('woocommerce_biller_business_invoice_live-username');
        let livePassword = document.getElementById('woocommerce_biller_business_invoice_live-password');
        let sandboxWebshop = document.getElementById('woocommerce_biller_business_invoice_sandbox-webShopUID');
        let sandboxUsername = document.getElementById('woocommerce_biller_business_invoice_sandbox-username');
        let sandboxPassword = document.getElementById('woocommerce_biller_business_invoice_sandbox-password');

        let switcher = document.getElementById('woocommerce_biller_business_invoice_mode');
        if(switcher){
            switcher.addEventListener('change', changeMode);
            updateFields(switcher.value);
        }
        function changeMode(e){
            updateFields(e.target.value)
        }
        function updateFields(mode){
            if(mode === 'live') {
                liveWebshop.parentElement.parentElement.parentElement.classList.remove("hidden");
                liveUsername.parentElement.parentElement.parentElement.classList.remove("hidden");
                livePassword.parentElement.parentElement.parentElement.classList.remove("hidden");
                sandboxWebshop.parentElement.parentElement.parentElement.classList.add("hidden");
                sandboxUsername.parentElement.parentElement.parentElement.classList.add("hidden");
                sandboxPassword.parentElement.parentElement.parentElement.classList.add("hidden");
            }else{
                liveWebshop.parentElement.parentElement.parentElement.classList.add("hidden");
                liveUsername.parentElement.parentElement.parentElement.classList.add("hidden");
                livePassword.parentElement.parentElement.parentElement.classList.add("hidden");
                sandboxWebshop.parentElement.parentElement.parentElement.classList.remove("hidden");
                sandboxUsername.parentElement.parentElement.parentElement.classList.remove("hidden");
                sandboxPassword.parentElement.parentElement.parentElement.classList.remove("hidden");
            }
        }
    });
})(document);
