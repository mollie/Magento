var MollieComponents = Class.create();
MollieComponents.prototype = {
    components: {},

    initialize: function (profile_id, testmode, locale) {
        if (!window.Nodelist && !window.NodeList.prototype.each && window.NodeList.prototype.forEach) {
            NodeList.prototype.each = NodeList.prototype.forEach;
        }

        try {
            this.mollie = Mollie(profile_id, {
                testMode: testmode,
                locale: locale
            });

            this.components.cardHolder = this.mollie.createComponent('cardHolder');
            this.components.cardNumber = this.mollie.createComponent('cardNumber');
            this.components.expiryDate = this.mollie.createComponent('expiryDate');
            this.components.verificationCode = this.mollie.createComponent('verificationCode');
        } catch (error) {
            console.error(error);
        }
    },

    getMollieToken: function() {
        if ($$('input:checked[name=payment[method]]')[0].value !== 'mollie_creditcard') {
            return this.parentSave();
        }

        this.mollie.createToken().then( function (result) {
            if (result.error) {
                alert(result.error.message);
                return false;
            }

            var tokenFields = $$('[name=cardToken]');
            if (result.token && tokenFields && tokenFields.length) {
                var tokenField = tokenFields.shift();
                tokenField.removeAttribute('disabled');
                tokenField.value = result.token;
            }

            this.parentSave();
        }.bind(this));
    },

    mount: function () {
        payment.addAfterInitFunction('mollie_components', function () {
            this.parentSave = payment.save.bind(payment);
            payment.save = this.getMollieToken.bind(this);
        }.bind(this));

        this.mountElement(this.components.cardHolder, '#card-holder');
        this.mountElement(this.components.cardNumber, '#card-number');
        this.mountElement(this.components.expiryDate, '#expiry-date');
        this.mountElement(this.components.verificationCode, '#verification-code');
    },

    mountElement: function (element, id) {
        element.mount(id);

        var errorElement = document.querySelector(id + '-error');
        element.addEventListener('change', function (event) {
            if (event.error && event.touched) {
                errorElement.textContent = event.error;
                errorElement.style.display = 'block';
            } else {
                errorElement.textContent = '';
                errorElement.style.display = 'none';
            }
        });
    }
};
