import('./paymentMethod.js').then((ccvPaymentMethod) => {

    window.addEventListener("load", (event) => {
        const {registerPaymentMethod} = window.wc.wcBlocksRegistry;
        registerPaymentMethod(ccvPaymentMethod.default('ccvonlinepayments_applepay'));
    });
});
