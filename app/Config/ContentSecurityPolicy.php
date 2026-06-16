<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    /**
     * Shared third-party hosts used by this app.
     *
     * @var list<string>
     */
    private array $authorizeNetHosts = [
        'https://accept.authorize.net',
        'https://test.authorize.net',
    ];

    /**
     * @var list<string>
     */
    private array $authorizeNetScriptHosts = [
        'https://js.authorize.net',
        'https://jstest.authorize.net',
    ];

    /**
     * @var list<string>
     */
    private array $googleHosts = [
        'https://www.google.com',
        'https://www.gstatic.com',
        'https://www.recaptcha.net',
    ];

    /**
     * @var list<string>
     */
    private array $mediaEmbedHosts = [
        'https://www.youtube.com',
        'https://www.youtube-nocookie.com',
        'https://player.vimeo.com',
    ];

    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context
     */
    public bool $reportOnly = false;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = false;

    // -------------------------------------------------------------------------
    // Sources allowed
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc;

    /**
     * Lists allowed scripts' URLs.
     *
     * @var list<string>|string
     */
    public $scriptSrc = [];

    /**
     * Lists allowed stylesheets' URLs.
     *
     * @var list<string>|string
     */
    public $styleSrc = [];

    /**
     * Defines the origins from which images can be loaded.
     *
     * @var list<string>|string
     */
    public $imageSrc = [];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $baseURI;

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = [];

    /**
     * Limits the origins that you can connect to (via XHR,
     * WebSockets, and EventSource).
     *
     * @var list<string>|string
     */
    public $connectSrc = [];

    /**
     * Specifies the origins that can serve web fonts.
     *
     * @var list<string>|string
     */
    public $fontSrc = [];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = [];

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors;

    /**
     * The frame-src directive restricts the URLs which may
     * be loaded into nested browsing contexts.
     *
     * @var list<string>|string|null
     */
    public $frameSrc = [];

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc;

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    public $objectSrc = 'self';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc;

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce tag for style
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce tag for script
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically
     */
    public bool $autoNonce = true;

    public function __construct()
    {
        parent::__construct();

        $this->scriptSrc = array_merge(
            ['self', "'unsafe-inline'", 'https://cdnjs.cloudflare.com'],
            $this->googleHosts,
            $this->authorizeNetScriptHosts
        );

        $this->styleSrc = ['self', 'https://fonts.googleapis.com', "'unsafe-inline'"];
        $this->imageSrc = ['self', 'data:', 'https:'];

        // Covers iframes and workers that can be created by third-party scripts.
        $this->childSrc = array_merge(['self'], $this->googleHosts, $this->mediaEmbedHosts);
        $this->connectSrc = array_merge(
            ['self'],
            $this->googleHosts,
            $this->authorizeNetHosts,
            $this->authorizeNetScriptHosts
        );
        $this->fontSrc = ['self', 'https://fonts.gstatic.com', 'data:'];

        // Required for checkout POST to Authorize.Net hosted payment page.
        $this->formAction = array_merge(['self'], $this->authorizeNetHosts);

        $this->frameSrc = array_merge(['self'], $this->googleHosts, $this->mediaEmbedHosts);
    }
}
