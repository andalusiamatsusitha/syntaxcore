/**
 * SyntaxCore Standard API Client (Native fetch wrapper with CSRF support)
 */
const SyntaxCore = {
    /**
     * Retrieve CSRF token from HTML meta tag or global variable.
     */
    csrfToken() {
        if (typeof document !== 'undefined') {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta && meta.content) {
                return meta.content;
            }
        }
        if (typeof window !== 'undefined' && window.csrfToken) {
            return window.csrfToken;
        }
        return null;
    },

    /**
     * Perform HTTP API request with automatic JSON handling and CSRF attachment.
     */
    async api(endpoint, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        // Automatically attach X-CSRF-TOKEN for state-changing requests
        if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            const token = this.csrfToken();
            if (token) {
                defaultHeaders['X-CSRF-TOKEN'] = token;
            }
        }

        const config = {
            method: method,
            headers: { ...defaultHeaders, ...(options.headers || {}) },
            ...options,
        };

        if (options.body && typeof options.body === 'object') {
            config.body = JSON.stringify(options.body);
        }

        const response = await fetch(endpoint, config);
        const data = await response.json().catch(() => null);

        if (!response.ok) {
            throw new Error(data?.message || `HTTP error ${response.status}`);
        }

        return data;
    }
};

if (typeof window !== 'undefined') {
    window.SyntaxCore = SyntaxCore;
}
