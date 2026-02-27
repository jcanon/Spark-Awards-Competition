(function (window) {
    if (!window) {
        return;
    }

    var spark = window.Spark || (window.Spark = {});

    var isValid = function (url) {
        var trimmed = (url || '').trim();
        if (!trimmed) {
            return true;
        }

        if (/^[A-Za-z0-9_-]{6,25}$/.test(trimmed) && !/^\d+$/.test(trimmed)) {
            return true;
        }

        var parsed;
        try {
            parsed = new URL(trimmed);
        } catch (error) {
            return false;
        }

        var host = (parsed.hostname || '').toLowerCase();
        var path = (parsed.pathname || '').replace(/^\/+|\/+$/g, '');

        if (host === 'youtu.be' || host === 'www.youtu.be') {
            var yShort = path.split('/')[0] || '';
            return /^[A-Za-z0-9_-]{6,25}$/.test(yShort);
        }

        if (host.endsWith('youtube.com') || host.endsWith('youtube-nocookie.com')) {
            if (path === 'watch') {
                var watchId = parsed.searchParams.get('v') || '';
                return /^[A-Za-z0-9_-]{6,25}$/.test(watchId);
            }
            var ytMatch = path.match(/^(embed|shorts|live)\/([^/?#]+)/i);
            return !!(ytMatch && /^[A-Za-z0-9_-]{6,25}$/.test(ytMatch[2]));
        }

        if (host.indexOf('vimeo.com') !== -1) {
            var vimeoMatch = path.match(/(?:^|\/)(?:video\/)?(\d+)(?:$|[/?#])/i);
            return !!(vimeoMatch && /^\d+$/.test(vimeoMatch[1]));
        }

        return false;
    };

    spark.videoEmbedUrl = {
        isValid: isValid
    };
})(window);
