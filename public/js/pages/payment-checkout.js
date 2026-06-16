(function () {
    var form = document.getElementById('anetPaymentForm');
    if (!form) {
        return;
    }

    var statusBox = document.getElementById('paymentStatus');
    var submitButton = document.getElementById('paymentSubmitButton');
    var descriptorInput = document.getElementById('data_descriptor');
    var valueInput = document.getElementById('data_value');
    var isSubmitting = false;
    var originalLabel = submitButton ? submitButton.textContent : '';

    function showStatus(message, type) {
        if (!statusBox) {
            return;
        }

        statusBox.textContent = message;
        statusBox.className = 'alert alert-' + (type || 'danger');
        statusBox.classList.remove('d-none');
    }

    function clearStatus() {
        if (!statusBox) {
            return;
        }

        statusBox.textContent = '';
        statusBox.className = 'alert alert-danger d-none';
    }

    function digitsOnly(value) {
        return (value || '').replace(/\D+/g, '');
    }

    function readField(id) {
        var element = document.getElementById(id);
        return element ? element.value.trim() : '';
    }

    function setSubmittingState(active) {
        isSubmitting = active;
        if (!submitButton) {
            return;
        }

        submitButton.disabled = active;
        submitButton.textContent = active ? 'Processing...' : originalLabel;
    }

    form.addEventListener('submit', function (event) {
        if (isSubmitting) {
            return;
        }

        event.preventDefault();
        clearStatus();

        if (typeof window.Accept === 'undefined' || typeof window.Accept.dispatchData !== 'function') {
            showStatus('Secure payment could not be initialized. Please reload the page and try again.');
            return;
        }

        var cardNumber = digitsOnly(readField('card_number'));
        var month = digitsOnly(readField('expiry_month'));
        var year = digitsOnly(readField('expiry_year'));
        var cardCode = digitsOnly(readField('card_code'));
        var zip = readField('billing_zip');
        var firstName = readField('billing_first_name');
        var lastName = readField('billing_last_name');
        var fullName = (firstName + ' ' + lastName).trim();

        if (!cardNumber || !month || !year || !cardCode || !firstName || !lastName) {
            showStatus('Please complete all payment fields before submitting.');
            return;
        }

        if (month.length === 1) {
            month = '0' + month;
        }

        if (year.length === 2) {
            year = '20' + year;
        }

        setSubmittingState(true);
        showStatus('Securing your card details...', 'info');

        window.Accept.dispatchData({
            authData: {
                apiLoginID: form.getAttribute('data-api-login-id') || '',
                clientKey: form.getAttribute('data-client-key') || ''
            },
            cardData: {
                cardNumber: cardNumber,
                month: month,
                year: year,
                cardCode: cardCode,
                zip: zip,
                fullName: fullName
            }
        }, function (response) {
            var opaqueData;
            var messages;
            var messageText = [];
            var i;

            if (response && response.messages && response.messages.resultCode === 'Error') {
                messages = response.messages.message || [];
                for (i = 0; i < messages.length; i += 1) {
                    if (messages[i] && messages[i].text) {
                        messageText.push(messages[i].text);
                    }
                }

                setSubmittingState(false);
                showStatus(messageText.join(' ') || 'Payment information could not be validated.');
                return;
            }

            opaqueData = response && response.opaqueData ? response.opaqueData : null;
            if (!opaqueData || !opaqueData.dataDescriptor || !opaqueData.dataValue) {
                setSubmittingState(false);
                showStatus('Payment tokenization did not return the required payment token. Please try again.');
                return;
            }

            descriptorInput.value = opaqueData.dataDescriptor;
            valueInput.value = opaqueData.dataValue;

            var cardNumberField = document.getElementById('card_number');
            var cardCodeField = document.getElementById('card_code');
            if (cardNumberField) {
                cardNumberField.value = '';
            }
            if (cardCodeField) {
                cardCodeField.value = '';
            }

            showStatus('Submitting payment...', 'info');
            form.submit();
        });
    });
})();
