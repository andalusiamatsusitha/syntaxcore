/**
 * SyntaxCore Window & UI Core
 * 
 * Contoh implementasi Class OOP di JavaScript yang bersih & modern:
 * 1. Menggunakan Constructor & State Management
 * 2. Terintegrasi dengan Namespace window.SyntaxCore
 * 3. Tetap kompatibel dengan window.Core
 * 4. Mendukung interaksi dengan backend API (CSRF-aware)
 */
class WindowCore {
    /**
     * @param {string} mode Mode inisialisasi (misal: 'init', 'workspace')
     * @param {Object} options Opsi konfigurasi container dan UI
     */
    constructor(mode = 'init', options = {}) {
        this.mode = mode;
        this.options = Object.assign({
            containerId: 'wd-content',
            title: 'SyntaxCore Dynamic Window',
        }, options);

        this.container = null;
        this.state = {
            initializedAt: new Date(),
            apiStatus: 'unknown',
            windows: []
        };

        this.init();
    }

    /**
     * Inisialisasi awal saat class di-instansiasi
     */
    init() {
        console.log(`[WindowCore] Initialized in mode: "${this.mode}"`);

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.mount());
        } else {
            this.mount();
        }
    }

    /**
     * Menghubungkan class ke elemen container DOM (#wd-content)
     */
    mount() {
        this.container = document.getElementById(this.options.containerId);

        if (!this.container) {
            console.warn(`[WindowCore] Container #${this.options.containerId} tidak ditemukan.`);
            return;
        }

        // Render struktur layout dinamis: Header, Workspace, dan Footer
        this.renderShell();
        this.attachEventListeners();
    }

    /**
     * Render layout dasar dinamis (Header, Body/Workspace, Footer)
     */
    renderShell() {
        if (!this.container) return;

        this.container.innerHTML = `
            <div class="d-flex flex-column h-100" style="background-color: #f1f5f9;">
                <!-- 1. Header Dinamis -->
                <header class="navbar navbar-expand navbar-dark bg-dark px-3 py-2 shadow-sm d-flex justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary">SyntaxCore</span>
                        <span class="navbar-brand mb-0 h6 fs-6 fw-bold">Admin Window Manager</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="wd-status-badge" class="badge bg-secondary small">Checking API...</span>
                        <button id="wd-btn-test-api" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-arrow-repeat"></i> Test API
                        </button>
                        <form action="/admin/logout" method="POST" class="m-0">
                            <input type="hidden" name="_token" value="${this.getCsrfToken()}">
                            <button type="submit" class="btn btn-sm btn-danger">Logout</button>
                        </form>
                    </div>
                </header>

                <!-- 2. Workspace Dinamis (Tempat konten halaman admin) -->
                <main id="wd-workspace" class="flex-grow-1 p-4 overflow-auto">
                    <div class="row g-4">
                        <div class="col-12 col-md-8">
                            <div class="card shadow-sm border-0">
                                <div class="card-body p-4">
                                    <h4 class="fw-bold mb-2">Selamat Datang di Dynamic Workspace</h4>
                                    <p class="text-muted">
                                        Komponen ini dirender secara dinamis oleh JavaScript <code>new Core("${this.mode}")</code>
                                        pada container <code>#${this.options.containerId}</code>.
                                    </p>
                                    <hr>
                                    <div class="d-flex gap-2">
                                        <button id="wd-btn-add-widget" class="btn btn-primary btn-sm">Tambah Widget Dinamis</button>
                                        <button id="wd-btn-clear" class="btn btn-outline-secondary btn-sm">Clear Workspace</button>
                                    </div>
                                    <div id="wd-widget-container" class="mt-3 row g-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <div class="card shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3">State Client Info</h6>
                                    <ul class="list-group list-group-flush small">
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Mode:</span>
                                            <span class="fw-semibold">${this.mode}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Container:</span>
                                            <code>#${this.options.containerId}</code>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Waktu Inisialisasi:</span>
                                            <span>${this.state.initializedAt.toLocaleTimeString()}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- 3. Footer Dinamis -->
                <footer class="bg-white border-top px-3 py-2 text-muted small d-flex justify-content-between align-items-center">
                    <span>SyntaxCore UI Client v1.0</span>
                    <span>Ready</span>
                </footer>
            </div>
        `;
    }

    /**
     * Mendaftarkan event listener pada tombol-tombol dinamis
     */
    attachEventListeners() {
        // Tombol Test API Backend
        const btnTest = document.getElementById('wd-btn-test-api');
        if (btnTest) {
            btnTest.addEventListener('click', () => this.checkApiStatus());
        }

        // Tombol Tambah Widget
        const btnWidget = document.getElementById('wd-btn-add-widget');
        if (btnWidget) {
            btnWidget.addEventListener('click', () => this.addWidget());
        }

        // Tombol Clear
        const btnClear = document.getElementById('wd-btn-clear');
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                const widgetContainer = document.getElementById('wd-widget-container');
                if (widgetContainer) widgetContainer.innerHTML = '';
            });
        }

        // Jalankan pengecekan status API di background
        this.checkApiStatus();
    }

    /**
     * Contoh memanggil API backend SyntaxCore secara aman dengan CSRF token
     */
    async checkApiStatus() {
        const badge = document.getElementById('wd-status-badge');
        if (badge) {
            badge.className = 'badge bg-warning text-dark small';
            badge.textContent = 'Connecting...';
        }

        try {
            // Memanfaatkan wrapper SyntaxCore.api jika tersedia
            let data = null;
            if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                data = await window.SyntaxCore.api('/api/v1/status');
            } else {
                const res = await fetch('/api/v1/status');
                data = await res.json();
            }

            if (badge && data) {
                badge.className = 'badge bg-success small';
                badge.textContent = `API Online (${data.api_version || 'v1'})`;
            }
        } catch (error) {
            console.error('[WindowCore] Gagal menghubungi API:', error);
            if (badge) {
                badge.className = 'badge bg-danger small';
                badge.textContent = 'API Offline';
            }
        }
    }

    /**
     * Contoh memanipulasi DOM dan state
     */
    addWidget() {
        const widgetContainer = document.getElementById('wd-widget-container');
        if (!widgetContainer) return;

        const count = widgetContainer.children.length + 1;
        const col = document.createElement('div');
        col.className = 'col-md-6';
        col.innerHTML = `
            <div class="card border bg-light">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="small">Widget #${count}</strong>
                        <button type="button" class="btn-close btn-sm" aria-label="Close"></button>
                    </div>
                    <p class="small text-muted mb-0 mt-2">Dibuat pada ${new Date().toLocaleTimeString()}</p>
                </div>
            </div>
        `;

        col.querySelector('.btn-close').addEventListener('click', () => col.remove());
        widgetContainer.appendChild(col);
    }

    /**
     * Helper untuk mengambil CSRF token dari meta tag
     */
    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }
}

// 1. Masukkan ke dalam namespace resmi SyntaxCore
window.SyntaxCore = window.SyntaxCore || {};
window.SyntaxCore.WindowCore = WindowCore;

// 2. Alias backwards-compatible agar `new Core(...)` tetap berfungsi langsung
window.Core = WindowCore;