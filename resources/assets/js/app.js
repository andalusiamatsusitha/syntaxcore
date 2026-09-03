/**
 * SyntaxCore Standard API Client (Native fetch wrapper)
 */
const SyntaxCore = {
    async api(endpoint, options = {}) {
        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };

        const config = {
            method: options.method || 'GET',
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

window.SyntaxCore = SyntaxCore;
