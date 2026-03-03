<!doctype html>
<html lang="en" data-theme="{{ $config->get('ui.theme', 'light') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="color-scheme" content="{{ $config->get('ui.theme', 'light') }}">
    <title>{{ $config->get('ui.title') ?? config('app.name') . ' - API Docs' }}</title>

    <script src="https://unpkg.com/@stoplight/elements@8.4.2/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements@8.4.2/styles.min.css">

    <script>
        const originalFetch = window.fetch;

        // Intercept ALL requests:
        // 1. Add XSRF-TOKEN for Sanctum
        // 2. Auto-inject Bearer token from localStorage (like Postman environment variables)
        // 3. Auto-capture token from login response
        window.fetch = (url, options) => {
            const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
            const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";

            const getCookieValue = (key) => {
                const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
                return cookie?.split("=")[1];
            };

            const updateFetchHeaders = (headers, headerKey, headerValue) => {
                if (headers instanceof Headers) {
                    headers.set(headerKey, headerValue);
                } else if (Array.isArray(headers)) {
                    headers.push([headerKey, headerValue]);
                } else if (headers) {
                    headers[headerKey] = headerValue;
                }
            };

            // Clone options to avoid mutation
            const modifiedOptions = options ? { ...options } : {};

            // Ensure headers exist (preserve existing headers)
            if (!modifiedOptions.headers) {
                modifiedOptions.headers = {};
            } else if (modifiedOptions.headers instanceof Headers) {
                // Convert Headers to object for easier manipulation
                const headersObj = {};
                modifiedOptions.headers.forEach((value, key) => {
                    headersObj[key] = value;
                });
                modifiedOptions.headers = headersObj;
            } else if (Array.isArray(modifiedOptions.headers)) {
                // Convert array to object
                const headersObj = {};
                modifiedOptions.headers.forEach(([key, value]) => {
                    headersObj[key] = value;
                });
                modifiedOptions.headers = headersObj;
            }

            // Auto-inject Authorization token from localStorage (like Postman environment)
            const savedToken = localStorage.getItem('api_token');
            if (savedToken && url.includes('/api/') && !url.includes('/api/login')) {
                modifiedOptions.headers['Authorization'] = `Bearer ${savedToken}`;
                console.log('🔐 Auto-injected token to:', url);
            }

            // Add CSRF token if available
            const csrfToken = getCookieValue(CSRF_TOKEN_COOKIE_KEY);
            if (csrfToken) {
                modifiedOptions.headers[CSRF_TOKEN_HEADER_KEY] = decodeURIComponent(csrfToken);
            }

            // Execute fetch with modified options
            return originalFetch(url, modifiedOptions).then(response => {
                // Auto-capture token from login response
                if (url.includes('/api/login') && response.ok) {
                    response.clone().json().then(data => {
                        var res = data;
                        if (res.data && res.data.access_token) {
                            const token = res.data.access_token;
                            localStorage.setItem('api_token', token);
                            console.log('%c✅ LOGIN BERHASIL - TOKEN AUTO-SAVED!', 'color: #4CAF50; font-size: 14px; font-weight: bold;');
                            console.log('%c✅ Token akan otomatis di-inject ke semua request!', 'color: #2196F3;');
                            console.log('%c🚀 Langsung test endpoint lain sekarang!', 'color: #FF5722;');
                            console.log('Token:', token.substring(0, 40) + '...');
                        }
                    }).catch(err => console.error('Error parsing login response:', err));
                }
                return response;
            });
        };

        console.log('%c🚀 AUTO-INJECT TOKEN ENABLED!', 'color: #FF5722; font-size: 16px; font-weight: bold;');
        console.log('%cToken akan otomatis masuk ke semua API request (seperti Postman environment)', 'color: #2196F3;');
    </script>

    <style>
        html, body { margin:0; height:100%; }
        body { background-color: var(--color-canvas); }
        /* issues about the dark theme of stoplight/mosaic-code-viewer using web component:
         * https://github.com/stoplightio/elements/issues/2188#issuecomment-1485461965
         */
        [data-theme="dark"] .token.property {
            color: rgb(128, 203, 196) !important;
        }
        [data-theme="dark"] .token.operator {
            color: rgb(255, 123, 114) !important;
        }
        [data-theme="dark"] .token.number {
            color: rgb(247, 140, 108) !important;
        }
        [data-theme="dark"] .token.string {
            color: rgb(165, 214, 255) !important;
        }
        [data-theme="dark"] .token.boolean {
            color: rgb(121, 192, 255) !important;
        }
        [data-theme="dark"] .token.punctuation {
            color: #dbdbdb !important;
        }
    </style>
</head>
<body style="height: 100vh; overflow-y: hidden">
<elements-api
    id="docs"
    tryItCredentialsPolicy="{{ $config->get('ui.try_it_credentials_policy', 'include') }}"
    router="hash"
    @if($config->get('ui.hide_try_it')) hideTryIt="true" @endif
    @if($config->get('ui.hide_schemas')) hideSchemas="true" @endif
    @if($config->get('ui.logo')) logo="{{ $config->get('ui.logo') }}" @endif
    @if($config->get('ui.layout')) layout="{{ $config->get('ui.layout') }}" @endif
/>
<script>
    (async () => {
        const docs = document.getElementById('docs');
        docs.apiDescriptionDocument = @json($spec);

        // Helper function to clear token
        window.clearToken = function() {
            localStorage.removeItem('api_token');
            console.log('✅ Token cleared! Login again to get new token.');
            alert('✅ Token dihapus!\n\nLogin ulang untuk mendapatkan token baru.');
        };

        // Helper function to get current token
        window.getToken = function() {
            const token = localStorage.getItem('api_token');
            if (token) {
                console.log('%cCurrent Token:', 'color: #4CAF50; font-weight: bold;');
                console.log(token);
                return token;
            } else {
                console.log('%c❌ No token found. Please login first.', 'color: #f44336;');
                return null;
            }
        };

        // Check if token exists on page load
        const existingToken = localStorage.getItem('api_token');
        if (existingToken) {
            console.log('%c✅ Token tersimpan ditemukan!', 'color: #4CAF50; font-weight: bold;');
            console.log('%cToken akan auto-inject ke semua request', 'color: #2196F3;');
            console.log('Token:', existingToken.substring(0, 40) + '...');
        } else {
            console.log('%cℹ️ Belum ada token. Login dulu untuk mendapatkan token.', 'color: #FF9800;');
        }

        // Log helper functions
        console.log('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #9E9E9E;');
        console.log('%c🚀 EWS API Documentation', 'color: #4CAF50; font-size: 16px; font-weight: bold;');
        console.log('%cAuto-Inject Token: ENABLED ✅', 'color: #2196F3; font-weight: bold;');
        console.log('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #9E9E9E;');
        console.log('%cHelper Functions:', 'color: #FF5722; font-weight: bold;');
        console.log('  • getToken()   - Lihat token yang tersimpan');
        console.log('  • clearToken() - Hapus token (logout)');
        console.log('%c━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━', 'color: #9E9E9E;');
    })();
</script>

@if($config->get('ui.theme', 'light') === 'system')
    <script>
        var mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        function updateTheme(e) {
            if (e.matches) {
                window.document.documentElement.setAttribute('data-theme', 'dark');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'dark');
            } else {
                window.document.documentElement.setAttribute('data-theme', 'light');
                window.document.getElementsByName('color-scheme')[0].setAttribute('content', 'light');
            }
        }

        mediaQuery.addEventListener('change', updateTheme);
        updateTheme(mediaQuery);
    </script>
@endif
</body>
</html>
