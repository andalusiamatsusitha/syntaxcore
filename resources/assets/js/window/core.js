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
            header: true,
            menus: [],
            footer: {
                active: true,
                icons: {
                    ypadding: '10px',
                    xpadding: '12px',
                    dimension: '20px',
                    text: '<span>Menu</span>'
                }
            }
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
        // alert(`[WindowCore] Initialized in mode: "${this.mode}"`);

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
                ${(this.options.header) ? `
                <header class="py-2 px-4 bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>activities</span>
                        <span style="font-size: 12px;">${this.state.initializedAt.toLocaleTimeString()}</span>
                        <span>icon</span>
                    </div>
                </header>
                ` : ''}
                
                <main id="wd-workspace" class="wd-workspace flex-grow-1 p-4 overflow-auto position-relative">
                    ini harus tempat window
                </main>

                ${(this.options.footer.active) ? `
                <footer class="bg-primary-subtle border-top">
                    <div class="d-flex align-items-center gap-2">
                        <div id="wp-menu" class="wp-menu" style="padding: ${this.options.footer.icons.ypadding ?? '0px'} ${this.options.footer.icons.xpadding ?? '0px'};">
                            <i class="fa-brands fa-microsoft d-block" style="font-size: ${this.options.footer.icons.dimension ?? '0px'};"></i>
                            ${this.options.footer.icons.text ?? ''}
                        </div>
                        <div class="d-flex align-items-center gap-2" id="wd-active-content">
                        </div>
                    </div>
                </footer>
                ` : ''}
            </div>
        `;
    }

    /**
     * Mendaftarkan event listener pada tombol-tombol dinamis
     */
    attachEventListeners() {
        const btnMenu = document.getElementById('wp-menu');
        if (btnMenu) btnMenu.addEventListener('click', () => this.openMenuWindow());
    }

    openMenuWindow() {
        const divWorkspace = document.getElementById('wd-workspace');
        if (!divWorkspace) return;

        const checkDivExist = divWorkspace.querySelector('#wd-menu-window');
        if (checkDivExist) {
            checkDivExist.remove();
            return;
        }

        const divMenuWindow = document.createElement('div');
        divMenuWindow.id = 'wd-menu-window';
        divMenuWindow.className = 'wd-menu-window card shadow-lg position-absolute border';
        divMenuWindow.style.bottom = '10px';
        divMenuWindow.style.left = '10px';
        divMenuWindow.style.width = '280px';
        divMenuWindow.style.zIndex = '1050';
        divMenuWindow.style.borderRadius = '8px';

        // Ambil daftar menu yang sudah difilter oleh backend sesuai level pengguna
        const menus = Array.isArray(this.options.menus) ? this.options.menus : [];

        const menuItemsHtml = (menus.length > 0)
            ? menus.map(item => `
                <button type="button" class="btn btn-sm text-start d-flex align-items-center gap-2 px-3 py-2 border-0 rounded text-dark" style="transition: background 0.15s;" data-menu-id="${item.id}" data-action="${item.action}">
                    <i class="${item.icon} text-primary" style="width: 20px; text-align: center;"></i>
                    <span class="small fw-medium">${item.title}</span>
                </button>
            `).join('')
            : '<div class="p-3 text-muted small text-center">Tidak ada menu untuk level akun ini</div>';

        divMenuWindow.innerHTML = `
            <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center rounded-top">
                <span class="small fw-bold"><i class="fa-solid fa-layer-group me-1"></i> Applications</span>
                <span class="badge bg-primary" style="font-size: 10px;">${this.options.userName || 'User'}</span>
            </div>
            <div class="card-body p-2 bg-white">
                <div id="wp-menu-list" class="d-flex flex-column gap-1">
                    ${menuItemsHtml}
                </div>
            </div>
        `;

        divWorkspace.appendChild(divMenuWindow);

        // Pasang event listener saat item menu diklik
        divMenuWindow.querySelectorAll('[data-menu-id]').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.getAttribute('data-action');
                const title = btn.querySelector('span')?.innerText || action;
                this.onMenuItemClick(action, title);
                divMenuWindow.remove();
            });

            btn.addEventListener('mouseenter', () => btn.style.backgroundColor = '#f1f5f9');
            btn.addEventListener('mouseleave', () => btn.style.backgroundColor = 'transparent');
        });

        // Event listener klik di luar menu untuk menutup otomatis
        const handleOutsideClick = (e) => {
            const btnMenu = document.getElementById('wp-menu');
            if (divMenuWindow && !divMenuWindow.contains(e.target) && (!btnMenu || !btnMenu.contains(e.target))) {
                divMenuWindow.remove();
                document.removeEventListener('click', handleOutsideClick);
            }
        };
        setTimeout(() => document.addEventListener('click', handleOutsideClick), 10);
    }

    /**
     * Handler saat salah satu item menu diklik
     */
    onMenuItemClick(action, title) {
        console.log(`[WindowCore] Menu clicked: ${title} (${action})`);
        alert(`Membuka: ${title} [action: ${action}]`);
    }

    /**
     * Contoh memanggil API backend SyntaxCore secara aman dengan CSRF token
     */
    // async checkApiStatus() {
    //     const badge = document.getElementById('wd-status-badge');
    //     if (badge) {
    //         badge.className = 'badge bg-warning text-dark small';
    //         badge.textContent = 'Connecting...';
    //     }

    //     try {
    //         // Memanfaatkan wrapper SyntaxCore.api jika tersedia
    //         let data = null;
    //         if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
    //             data = await window.SyntaxCore.api('/api/v1/status');
    //         } else {
    //             const res = await fetch('/api/v1/status');
    //             data = await res.json();
    //         }

    //         if (badge && data) {
    //             badge.className = 'badge bg-success small';
    //             badge.textContent = `API Online (${data.api_version || 'v1'})`;
    //         }
    //     } catch (error) {
    //         console.error('[WindowCore] Gagal menghubungi API:', error);
    //         if (badge) {
    //             badge.className = 'badge bg-danger small';
    //             badge.textContent = 'API Offline';
    //         }
    //     }
    // }

    /**
     * Contoh memanipulasi DOM dan state
     */
    // addWidget() {
    //     const widgetContainer = document.getElementById('wd-widget-container');
    //     if (!widgetContainer) return;

    //     const count = widgetContainer.children.length + 1;
    //     const col = document.createElement('div');
    //     col.className = 'col-md-6';
    //     col.innerHTML = `
    //         <div class="card border bg-light">
    //             <div class="card-body p-3">
    //                 <div class="d-flex justify-content-between align-items-center">
    //                     <strong class="small">Widget #${count}</strong>
    //                     <button type="button" class="btn-close btn-sm" aria-label="Close"></button>
    //                 </div>
    //                 <p class="small text-muted mb-0 mt-2">Dibuat pada ${new Date().toLocaleTimeString()}</p>
    //             </div>
    //         </div>
    //     `;

    //     col.querySelector('.btn-close').addEventListener('click', () => col.remove());
    //     widgetContainer.appendChild(col);
    // }

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