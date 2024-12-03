const ccvPaymentMethod = (paymentMethodName) => {
    const data = wc.wcSettings.getPaymentMethodData(paymentMethodName);

    return {
        name: paymentMethodName,
        paymentMethodId: paymentMethodName,
        label: window.wp.element.createElement(() =>
            window.wp.element.createElement(
                "span",
                null,
                window.wp.element.createElement("img", {
                    src: data.icon,
                    alt: data.title,
                    style: { float: 'left', marginRight: '10px', minWidth: "80px", objectPosition: "center" }
                }),
                "  " + data.title
            )
        ),
        content: window.wp.element.createElement('div', null, `${data.description}`),
        edit: window.wp.element.createElement('div', null, `${data.description}`),
        ariaLabel: data.title,
        canMakePayment: () => true,
        supports: {
            features: ['products'],
        },
    }
}
export default ccvPaymentMethod
