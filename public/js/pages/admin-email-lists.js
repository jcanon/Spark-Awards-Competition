(function () {
    var copyButton = document.getElementById('copyEmailListBtn');
    var textArea = document.getElementById('emailListTextarea');
    if (!copyButton || !textArea) {
        return;
    }

    copyButton.addEventListener('click', function () {
        textArea.focus();
        textArea.select();
        var onSuccess = function () {
            copyButton.textContent = 'Copied';
            setTimeout(function () {
                copyButton.textContent = 'Copy Email List';
            }, 1200);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textArea.value).then(onSuccess).catch(function () {
                try {
                    if (document.execCommand('copy')) {
                        onSuccess();
                    }
                } catch (e) {
                    // Fallback keeps selected text for manual copy.
                }
            });
            return;
        }

        try {
            if (document.execCommand('copy')) {
                onSuccess();
            }
        } catch (e) {
            // Fallback keeps selected text for manual copy.
        }
    });
})();
