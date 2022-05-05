(function (document) {
    document.addEventListener("DOMContentLoaded", function () {
        let billingCompany = document.getElementById('billing_company');

        if (billingCompany) {
            let previousValue = billingCompany.value;
            setBillerCompany(billingCompany.value, previousValue);

            billingCompany.addEventListener('blur', function (event) {
                setBillerCompany(event.target.value, previousValue);
            });

            billingCompany.addEventListener('focusin', function (event) {
                previousValue = event.target.value;
            });
        }

        function setBillerCompany(billingCompany, previousValue) {
            let billerCompanyName = document.getElementById('biller_company_name')
            if (billerCompanyName && (billerCompanyName.value === '' || previousValue === billerCompanyName.value)) {
                billerCompanyName.value = billingCompany;
            }
        }
    });
})(document);
