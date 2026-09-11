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
            userName: 'Administrator',
            userRole: 'Administrator',
            roleSlug: 'admin',
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
        this.actionHandlers = {};
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
                <header class="py-2 px-4 bg-dark text-white user-select-none position-relative" style="font-size: 12px; line-height: 1.2;">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <!-- Sisi Kiri: Label Activities -->
                        <div class="d-flex align-items-center">
                            <span class="small text-white-50">Activities</span>
                        </div>

                        <!-- Sisi Tengah: Jam Berfungsi & Hari (Tepat di Tengah Header) -->
                        <div class="position-absolute start-50 translate-middle-x text-center" style="pointer-events: none;">
                            <span id="wd-header-clock" class="small fw-semibold text-white font-monospace" style="letter-spacing: 0.3px;">
                                ${this.formatHeaderClock()}
                            </span>
                        </div>

                        <!-- Sisi Kanan: Notifikasi, Username, Role, dan Tombol Logout Teks -->
                        <div class="d-flex align-items-center gap-2 small position-relative">
                            <!-- Bell Notifikasi -->
                            <div class="position-relative me-1" id="wd-notif-dropdown-wrapper">
                                <button type="button" id="wd-header-bell" class="btn btn-link text-white-50 text-decoration-none p-0 position-relative" style="font-size: 13px; line-height: 1; transition: color 0.15s ease;" title="Notifikasi Sistem">
                                    <i class="fa-regular fa-bell"></i>
                                    <span id="wd-header-notif-badge" class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle d-none" style="font-size: 8px; padding: 2px 4px; line-height: 1;">0</span>
                                </button>

                                <!-- Floating Notification Tray Dropdown -->
                                <div id="wd-header-notif-tray" class="d-none card shadow-lg position-absolute end-0 mt-2 border" style="width: 320px; z-index: 10000; top: 100%; border-radius: 8px; background: #ffffff; color: #1e293b;">
                                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
                                        <span class="fw-bold small text-dark"><i class="fa-regular fa-bell text-primary me-1"></i> Notifikasi</span>
                                        <button type="button" class="btn btn-link text-secondary text-decoration-none p-0" id="btn-mark-all-read" style="font-size: 11px;">
                                            Tandai dibaca
                                        </button>
                                    </div>
                                    <div id="wd-notif-list" class="list-group list-group-flush overflow-auto" style="max-height: 280px; font-size: 12px;">
                                        <div class="p-3 text-center text-muted small">Memuat notifikasi...</div>
                                    </div>
                                </div>
                            </div>

                            <span class="text-white-50 opacity-25">|</span>

                            <span class="text-white-50" id="wd-header-user-info">
                                <span class="text-white fw-medium">${this.options.userName || 'Administrator'}</span>
                                <span class="text-white-50 ms-1" style="font-size: 11px;">(${this.options.userRole || 'Administrator'})</span>
                            </span>
                            <span class="text-white-50 opacity-25">|</span>
                            <button type="button" id="wd-header-logout" class="btn btn-link text-white-50 text-decoration-none p-0 small" style="font-size: 12px; line-height: 1; transition: color 0.15s ease;" title="Keluar dari sesi admin">
                                Logout
                            </button>
                        </div>
                    </div>
                </header>
                ` : ''}
                
                <main id="wd-workspace" class="wd-workspace flex-grow-1 position-relative overflow-hidden" style="background-color: #f1f5f9;">
                    <!-- Layer Backdrop Wallpaper Desktop -->
                    <div id="wd-workspace-backdrop" class="position-absolute top-0 start-0 w-100 h-100" style="pointer-events: none; z-index: 0; background-size: cover; background-position: center; background-repeat: no-repeat; transition: background 0.3s ease, filter 0.3s ease, opacity 0.3s ease;"></div>
                    <!-- Layer Dimmer Desktop Overlay (agar window & teks tetap kontras) -->
                    <div id="wd-workspace-dimmer" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="pointer-events: none; z-index: 1; background: rgba(0, 0, 0, 0.15); transition: opacity 0.3s ease;"></div>
                    <!-- Snap Preview Element -->
                    <div id="wd-snap-preview" class="wd-snap-preview d-none"></div>
                    <!-- Drag & Drop File Upload Overlay -->
                    <div id="wd-drag-drop-overlay" class="position-absolute top-0 start-0 w-100 h-100 d-none d-flex flex-column align-items-center justify-content-center" style="z-index: 9999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); pointer-events: none; color: white;">
                        <div class="card p-4 text-center border-0 shadow-lg bg-dark text-white border border-primary border-2 border-dashed" style="max-width: 380px;">
                            <i class="fa-solid fa-cloud-arrow-up display-4 text-primary mb-3"></i>
                            <h5 class="fw-bold mb-1">Lepaskan Gambar di Sini</h5>
                            <p class="text-white-50 small mb-0">Gambar akan otomatis diunggah dan dijadikan wallpaper desktop.</p>
                        </div>
                    </div>
                    <!-- Hidden File Input untuk Quick Upload Langsung -->
                    <input type="file" id="wd-wallpaper-direct-upload" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" class="d-none">
                    <!-- Desktop Context Menu (Klik Kanan di Workspace) -->
                    <div id="wd-desktop-context-menu" class="wd-context-menu d-none">
                        <div class="wd-context-menu-item" id="wd-ctx-open-wallpaper">
                            <i class="fa-regular fa-image text-primary" style="width: 16px;"></i>
                            <span>Ganti Background Desktop...</span>
                        </div>
                        <div class="wd-context-menu-item" id="wd-ctx-direct-upload">
                            <i class="fa-solid fa-arrow-up-from-bracket text-info" style="width: 16px;"></i>
                            <span>Unggah Gambar Langsung...</span>
                        </div>
                        <div class="wd-context-menu-divider"></div>
                        <div class="wd-context-menu-item" id="wd-ctx-refresh">
                            <i class="fa-solid fa-arrows-rotate text-secondary" style="width: 16px;"></i>
                            <span>Segarkan Desktop</span>
                        </div>
                        <div class="wd-context-menu-divider"></div>
                        <div class="wd-context-menu-item danger" id="wd-ctx-reset-wallpaper">
                            <i class="fa-regular fa-trash-can text-danger" style="width: 16px;"></i>
                            <span>Hapus Background (Reset)</span>
                        </div>
                    </div>
                    <!-- Desktop Toast Notification Container -->
                    <div id="wd-toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index: 10050; pointer-events: none; max-width: 360px;"></div>
                </main>

                ${(this.options.footer.active) ? `
                <footer class="bg-primary-subtle border-top">
                    <div class="d-flex align-items-stretch justify-content-between h-100">
                        <div class="d-flex align-items-stretch gap-1 h-100">
                            <div id="wp-menu" class="wp-menu" style="padding: ${this.options.footer.icons.ypadding ?? '0px'} ${this.options.footer.icons.xpadding ?? '0px'};">
                                <i class="fa-brands fa-microsoft d-block" style="font-size: ${this.options.footer.icons.dimension ?? '0px'};"></i>
                                ${this.options.footer.icons.text ?? ''}
                            </div>
                            <div class="d-flex align-items-stretch gap-1 h-100" id="wd-active-content">
                            </div>
                        </div>
                        <!-- Tombol Pintas Pengaturan Wallpaper di Footer -->
                        <div class="d-flex align-items-center pe-2">
                            <button type="button" class="btn btn-sm btn-link text-secondary text-decoration-none p-1" id="wd-btn-wallpaper-footer" title="Ubah Background Desktop" style="font-size: 13px;">
                                <i class="fa-regular fa-image"></i>
                            </button>
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
        if (btnMenu) btnMenu.addEventListener('click', () => this.openMenuWindow(btnMenu));

        // Jalankan jam header realtime
        this.startHeaderClock();

        // Inisialisasi sistem notifikasi header
        this.initNotificationSystem();

        // Inisialisasi Wallpaper Desktop
        this.initDesktopWallpaper();

        // Inisialisasi Context Menu Desktop (Klik Kanan di Workspace)
        this.initDesktopContextMenu();

        // Inisialisasi Drag & Drop File Gambar Langsung ke Workspace
        this.initDesktopDragDrop();

        // Tombol Pintas Wallpaper di Footer
        const btnWpFooter = document.getElementById('wd-btn-wallpaper-footer');
        if (btnWpFooter) {
            btnWpFooter.addEventListener('click', () => this.openWallpaperWindow());
        }

        // Pasang event tombol teks logout
        const btnLogout = document.getElementById('wd-header-logout');
        if (btnLogout) {
            btnLogout.addEventListener('mouseenter', () => { btnLogout.style.color = '#ef4444'; });
            btnLogout.addEventListener('mouseleave', () => { btnLogout.style.color = ''; });
            btnLogout.addEventListener('click', (e) => {
                e.preventDefault();
                this.handleLogout();
            });
        }
    }

    /**
     * Memformat waktu untuk header bar dalam format: Day, hh:mm AM/PM (contoh: Fri, 12:05 PM)
     * 
     * @param {Date} date Objek tanggal/waktu
     * @returns {string} String waktu terformat
     */
    formatHeaderClock(date = new Date()) {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const day = days[date.getDay()];
        let hours = date.getHours();
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `${day}, ${hours}:${minutes} ${ampm}`;
    }

    /**
     * Memulai pembaruan jam header secara realtime setiap 1 detik
     */
    startHeaderClock() {
        if (this._clockInterval) {
            clearInterval(this._clockInterval);
        }

        const updateClock = () => {
            const clockEl = document.getElementById('wd-header-clock');
            if (clockEl) {
                clockEl.textContent = this.formatHeaderClock();
            }
        };

        updateClock();
        this._clockInterval = setInterval(updateClock, 1000);
    }

    /**
     * Menangani proses logout pengguna dari sistem dengan pengiriman form POST ber-CSRF token
     */
    handleLogout() {
        const confirmed = confirm('Apakah Anda yakin ingin logout dari sistem?');
        if (!confirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/logout';
        form.style.display = 'none';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = this.getCsrfToken();
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }

    openMenuWindow(btnMenu) {
        const divWorkspace = document.getElementById('wd-workspace');
        if (!divWorkspace) return;

        const checkDivExist = divWorkspace.querySelector('#wd-menu-window');
        if (checkDivExist) {
            checkDivExist.remove();
            btnMenu.classList.remove("wp-menu-active");
            return;
        }

        const divMenuWindow = document.createElement('div');
        divMenuWindow.id = 'wd-menu-window';
        divMenuWindow.className = 'wd-menu-window card shadow-lg position-absolute border-0';
        divMenuWindow.style.cssText = `
            bottom: 10px;
            left: 10px;
            width: 300px;
            max-height: 520px;
            z-index: 1050;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        `;

        // Ambil pohon menu yang sudah disusun oleh backend
        const menus = Array.isArray(this.options.menus) ? this.options.menus : [];
        const menuTreeHtml = (menus.length > 0)
            ? this.renderMenuTree(menus)
            : '<div class="p-3 text-muted small text-center">Tidak ada menu untuk level akun ini</div>';

        divMenuWindow.innerHTML = `
            <div class="card-header bg-dark text-white py-2 px-3 d-flex justify-content-between align-items-center rounded-top border-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-user text-primary fs-5"></i>
                    <div class="overflow-hidden">
                        <div class="fw-bold small text-truncate" style="max-width: 150px;">${this.options.userName || 'User'}</div>
                        <div class="text-white-50 lh-1" style="font-size: 10px;">${this.options.userRole || 'Role'}</div>
                    </div>
                </div>
                <span class="badge bg-primary" style="font-size: 10px; text-transform: uppercase;">${this.options.roleSlug || 'user'}</span>
            </div>
            <div class="card-body p-2 bg-white" style="overflow-y: auto; max-height: 440px;">
                <div id="wp-menu-list" class="d-flex flex-column gap-1">
                    ${menuTreeHtml}
                </div>
            </div>
        `;

        divWorkspace.appendChild(divMenuWindow);
        btnMenu.classList.add("wp-menu-active");

        // Pasang event listener untuk parent collapsible toggles
        divMenuWindow.querySelectorAll('.menu-parent-toggle').forEach(toggleBtn => {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const parentGroup = toggleBtn.closest('.menu-group');
                const childrenContainer = parentGroup?.querySelector(':scope > .menu-children');
                const chevron = toggleBtn.querySelector('.chevron-icon');

                if (childrenContainer) {
                    const isClosed = childrenContainer.classList.contains('d-none');
                    if (isClosed) {
                        childrenContainer.classList.remove('d-none');
                        childrenContainer.classList.add('d-flex');
                        if (chevron) chevron.style.transform = 'rotate(90deg)';
                    } else {
                        childrenContainer.classList.add('d-none');
                        childrenContainer.classList.remove('d-flex');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    }
                }
            });

            toggleBtn.addEventListener('mouseenter', () => toggleBtn.style.backgroundColor = '#f1f5f9');
            toggleBtn.addEventListener('mouseleave', () => toggleBtn.style.backgroundColor = 'transparent');
        });

        // Pasang event listener saat leaf menu item diklik
        divMenuWindow.querySelectorAll('.menu-leaf-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const menuId = parseInt(btn.getAttribute('data-menu-id'), 10);
                const action = btn.getAttribute('data-action') || '';
                const title = btn.querySelector('.menu-title')?.innerText || btn.innerText.trim();
                const route = btn.getAttribute('data-route') || null;
                const target = btn.getAttribute('data-target') || null;
                const type = btn.getAttribute('data-type') || null;

                const menuItem = this.findMenu(menuId) || {
                    id: menuId,
                    action: action,
                    title: title,
                    route: route,
                    target: target,
                    type: type,
                };

                divMenuWindow.remove();
                btnMenu.classList.remove("wp-menu-active");
                this.onMenuItemClick(menuItem, e);
            });

            btn.addEventListener('mouseenter', () => btn.style.backgroundColor = '#f1f5f9');
            btn.addEventListener('mouseleave', () => btn.style.backgroundColor = 'transparent');
        });

        // Event listener klik di luar menu untuk menutup otomatis
        const handleOutsideClick = (e) => {
            const btnMenu = document.getElementById('wp-menu');
            if (divMenuWindow && !divMenuWindow.contains(e.target) && (!btnMenu || !btnMenu.contains(e.target))) {
                divMenuWindow.remove();
                btnMenu.classList.remove("wp-menu-active");
                document.removeEventListener('click', handleOutsideClick);
            }
        };
        setTimeout(() => document.addEventListener('click', handleOutsideClick), 10);
    }

    /**
     * Render rekursif untuk struktur pohon menu bertingkat
     */
    renderMenuTree(items, level = 0) {
        if (!Array.isArray(items) || items.length === 0) {
            return '';
        }

        return items.map(item => {
            const hasChildren = Array.isArray(item.children) && item.children.length > 0;

            if (hasChildren) {
                const subItemsHtml = this.renderMenuTree(item.children, level + 1);
                return `
                    <div class="menu-group" data-menu-id="${item.id}">
                        <button type="button" class="btn btn-sm text-start d-flex align-items-center justify-content-between px-3 py-2 border-0 rounded text-dark w-100 menu-parent-toggle" style="transition: background 0.15s;">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <i class="${item.icon || 'fa-solid fa-folder'} text-primary" style="width: 18px; text-align: center;"></i>
                                <span class="small fw-semibold text-truncate">${item.title}</span>
                            </div>
                            <div class="d-flex align-items-center gap-1 ms-2">
                                ${item.badge ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">${item.badge}</span>` : ''}
                                <i class="fa-solid fa-chevron-right text-muted chevron-icon" style="font-size: 10px; transition: transform 0.2s ease;"></i>
                            </div>
                        </button>
                        <div class="menu-children ps-2 d-none flex-column gap-1 my-1 border-start border-2 ms-3 border-light-subtle">
                            ${subItemsHtml}
                        </div>
                    </div>
                `;
            } else {
                return `
                    <button type="button" class="btn btn-sm text-start d-flex align-items-center justify-content-between px-3 py-2 border-0 rounded text-dark w-100 menu-leaf-btn" style="transition: background 0.15s;" data-menu-id="${item.id}" data-action="${item.action || ''}" data-route="${item.route || ''}" data-target="${item.target || ''}" data-type="${item.type || ''}">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <i class="${item.icon || 'fa-solid fa-circle-dot'} text-primary" style="width: 18px; text-align: center;"></i>
                            <span class="small fw-medium text-truncate menu-title">${item.title}</span>
                        </div>
                        ${item.badge ? `<span class="badge bg-primary-subtle text-primary ms-2" style="font-size: 10px;">${item.badge}</span>` : ''}
                    </button>
                `;
            }
        }).join('');
    }

    /**
     * Cari objek menu berdasarkan ID atau Action secara rekursif
     */
    findMenu(idOrAction, items = this.options.menus) {
        if (!Array.isArray(items)) return null;

        for (const item of items) {
            if (item.id == idOrAction || item.action === idOrAction) {
                return item;
            }
            if (Array.isArray(item.children) && item.children.length > 0) {
                const found = this.findMenu(idOrAction, item.children);
                if (found) return found;
            }
        }
        return null;
    }

    /**
     * Mendaftarkan custom action handler JavaScript
     * Contoh: adminApp.registerAction('open_users', (item, app, e) => { ... })
     */
    registerAction(actionName, handler) {
        if (typeof handler === 'function') {
            this.actionHandlers[actionName] = handler;
        }
        return this;
    }

    /**
     * Handler utama saat salah satu item menu diklik.
     * Mendukung 4 mode aksi:
     * 1. Href / Direct redirect (window.location.href)
     * 2. Buka tab browser baru (window.open target="_blank")
     * 3. Buka Window di dalam workspace (#wd-workspace)
     * 4. Custom JavaScript Event / Callback Handler
     *
     * @param {Object|string} itemOrAction Objek menu atau string action identifier
     * @param {Event|null} event Event DOM asli (opsional)
     */
    onMenuItemClick(itemOrAction, event = null) {
        // Normalisasi input
        let item = null;
        if (typeof itemOrAction === 'object' && itemOrAction !== null) {
            item = itemOrAction;
        } else if (typeof itemOrAction === 'string' || typeof itemOrAction === 'number') {
            item = this.findMenu(itemOrAction) || {
                action: String(itemOrAction),
                title: String(itemOrAction),
                route: null
            };
        } else {
            return;
        }

        const action = item.action || '';
        const route = item.route || null;
        const target = item.target || null;
        const type = item.type || null;

        console.log(`[WindowCore] Menu clicked: "${item.title}" [action: ${action || '-'}, route: ${route || '-'}]`);

        // 1. Trigger Global Custom DOM Event
        const customEvent = new CustomEvent('syntaxcore:menu-click', {
            detail: { item, app: this, originalEvent: event },
            cancelable: true
        });
        const notCancelled = document.dispatchEvent(customEvent);
        if (!notCancelled) {
            console.log(`[WindowCore] Action for "${item.title}" dibatalkan oleh event listener.`);
            return;
        }

        // 2. Hook opsi konfigurasi instansiasi (options.onMenuItemClick)
        if (typeof this.options.onMenuItemClick === 'function') {
            const hookResult = this.options.onMenuItemClick(item, this, event);
            if (hookResult === false) {
                return; // Dibatalkan oleh callback hook user
            }
        }

        // 3. Cek Custom Action Handler yang didaftarkan via registerAction(actionName, fn)
        if (action && typeof this.actionHandlers[action] === 'function') {
            this.actionHandlers[action](item, this, event);
            return;
        }

        // 4. Deteksi Mode: New Tab, Href, Custom Event, atau Window di Workspace

        // A. MODE BUKA TAB BROWSER BARU
        const isNewTab = (target === '_blank') ||
            (type === 'new_tab') ||
            (action === 'new_tab') ||
            (action && (action.startsWith('newtab:') || action.startsWith('tab:')));
        if (isNewTab) {
            let url = route;
            if (action && (action.startsWith('newtab:') || action.startsWith('tab:'))) {
                url = action.split(':')[1];
            }
            if (url) {
                console.log(`[WindowCore] Membuka tab baru: ${url}`);
                window.open(url, '_blank', 'noopener,noreferrer');
            } else {
                console.warn(`[WindowCore] Menu "${item.title}" diset new_tab tetapi URL/route kosong.`);
            }
            return;
        }

        // B. MODE HREF (Redirect halaman di tab saat ini)
        const isHref = (target === '_self') ||
            (type === 'href') ||
            (action === 'href') ||
            (action && action.startsWith('href:'));
        if (isHref) {
            let url = route;
            if (action && action.startsWith('href:')) {
                url = action.substring(5);
            }
            if (url) {
                console.log(`[WindowCore] Navigasi halaman ke: ${url}`);
                window.location.href = url;
            } else {
                console.warn(`[WindowCore] Menu "${item.title}" diset href tetapi URL/route kosong.`);
            }
            return;
        }

        // C. MODE CUSTOM EVENT JAVASCRIPT
        const isCustomEvent = (type === 'event') ||
            (action && action.startsWith('event:'));
        if (isCustomEvent) {
            const eventName = action.startsWith('event:') ? action.substring(6) : (item.event_name || 'syntaxcore:custom-event');
            console.log(`[WindowCore] Triggering event: ${eventName}`);
            document.dispatchEvent(new CustomEvent(eventName, {
                detail: { item, app: this, originalEvent: event }
            }));
            return;
        }

        // D. MODE WINDOW DESKTOP DI DALAM #wd-workspace (DEFAULT)
        this.openWindow(item);
    }

    /**
     * Membuka atau memfokuskan jendela aplikasi di dalam workspace (#wd-workspace).
     * Struktur container sengaja dibuat unopinionated agar fleksibel dan desain visual bebas ditentukan oleh user.
     *
     * @param {Object} item Objek menu yang diklik
     * @returns {HTMLElement|null} Elemen window yang dibuat atau difokuskan
     */
    openWindow(item) {
        const workspace = document.getElementById('wd-workspace');
        if (!workspace) {
            console.warn('[WindowCore] Workspace #wd-workspace tidak ditemukan.');
            return null;
        }

        const winId = `wd-win-${item.id || item.action || 'app'}`;

        // Jika window dengan ID ini sudah ada di workspace, bawa ke paling depan (fokus)
        const existingWin = document.getElementById(winId);
        if (existingWin) {
            this.restoreAndFocusWindow(winId);
            return existingWin;
        }

        // Buat container window dasar
        const winEl = document.createElement('div');
        winEl.id = winId;
        winEl.className = 'wd-window card shadow position-absolute border';
        winEl.setAttribute('data-action', item.action || '');
        winEl.setAttribute('data-route', item.route || '');

        // Deteksi apakah ini modul Master Pengguna, Data Peran (Roles), Laporan Aktivitas, Profil Saya, Wallpaper Desktop, atau Modul CMS
        const isUsersModule = (item.action === 'open_users') || (item.route === '/admin/users');
        const isRolesModule = (item.action === 'open_roles') || (item.route === '/admin/roles');
        const isReportsModule = (item.action === 'open_reports') || (item.route === '/admin/reports');
        const isProfileModule = (item.action === 'open_profile') || (item.route === '/admin/profile');
        const isWallpaperModule = (item.action === 'open_wallpaper') || (item.id === 'wallpaper') || (item.id === 'wallpaper-settings');
        const isCmsNewsModule = (item.action === 'open_cms_news') || (item.route === '/admin/cms/news');
        const isCmsPagesModule = (item.action === 'open_cms_pages') || (item.route === '/admin/cms/pages');
        const isCmsTaxonomyModule = (item.action === 'open_cms_taxonomy') || (item.route === '/admin/cms/taxonomy');
        const isCmsMenusModule = (item.action === 'open_cms_menus') || (item.route === '/admin/cms/menus');
        const isCmsCommentsModule = (item.action === 'open_cms_comments') || (item.route === '/admin/cms/comments');

        const defaultWidth = isUsersModule ? 780 : (isRolesModule ? 840 : (isReportsModule ? 860 : (isProfileModule ? 780 : (isWallpaperModule ? 750 : (isCmsNewsModule ? 920 : (isCmsPagesModule ? 880 : (isCmsTaxonomyModule ? 820 : (isCmsMenusModule ? 820 : (isCmsCommentsModule ? 900 : 440)))))))));
        const defaultHeight = isUsersModule ? 520 : (isRolesModule ? 560 : (isReportsModule ? 540 : (isProfileModule ? 530 : (isWallpaperModule ? 540 : (isCmsNewsModule ? 600 : (isCmsPagesModule ? 580 : (isCmsTaxonomyModule ? 550 : (isCmsMenusModule ? 540 : (isCmsCommentsModule ? 580 : 250)))))))));

        // Posisi default bertingkat (cascade offset)
        const offset = (this.state.windows.length % 6) * 24 + 30;
        winEl.style.cssText = `
            top: ${offset}px;
            left: ${offset}px;
            width: ${defaultWidth}px;
            min-width: 380px;
            height: ${defaultHeight}px;
            min-height: 250px;
            z-index: ${100 + this.state.windows.length};
            background: #ffffff;
            border-radius: 8px;
        `;
        winEl.dataset.prevTop = `${offset}px`;
        winEl.dataset.prevLeft = `${offset}px`;
        winEl.dataset.prevWidth = `${defaultWidth}px`;
        winEl.dataset.prevHeight = `${defaultHeight}px`;
        winEl.dataset.isMaximized = 'false';
        winEl.dataset.snapState = 'none';

        // Layout dasar unopinionated: Header minimalis dan Body container
        winEl.innerHTML = `
            <div class="wd-window-header card-header bg-light d-flex justify-content-between align-items-center py-2 px-3 user-select-none border-bottom" style="cursor: move;">
                <div class="d-flex align-items-center gap-2 text-truncate me-2">
                    <i class="${item.icon || 'fa-solid fa-window-maximize'} text-primary small"></i>
                    <span class="wd-window-title small fw-semibold text-truncate">${item.title || 'Application Window'}</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 wd-window-minimize" title="Minimize" style="font-size: 11px; text-decoration: none;">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-link text-secondary p-0 wd-window-maximize" title="Maximize / Restore" style="font-size: 11px; text-decoration: none;">
                        <i class="fa-regular fa-square"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-close wd-window-close border-0" aria-label="Close" style="font-size: 10px;"></button>
                </div>
            </div>
            <div class="wd-window-body card-body p-3 overflow-auto" style="min-height: 180px;">
                <div class="text-muted small">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold text-dark">${item.title}</span>
                        ${item.badge ? `<span class="badge bg-primary-subtle text-primary">${item.badge}</span>` : ''}
                    </div>
                    <p class="mb-1 text-secondary">Endpoint Controller: <code>${item.route || 'Tidak ada endpoint'}</code></p>
                    <p class="mb-0 text-secondary" style="font-size: 11px;">Action: <code>${item.action || '-'}</code></p>
                </div>
            </div>
        `;

        // Pasang event minimize tombol minus
        winEl.querySelector('.wd-window-minimize')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.minimizeWindow(winId);
        });

        // Pasang event close tombol 'X'
        winEl.querySelector('.wd-window-close')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.closeWindow(winId);
        });

        // Pasang event maximize tombol kotak
        winEl.querySelector('.wd-window-maximize')?.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleMaximize(winEl);
        });

        // Double-click header untuk maximize / restore
        winEl.querySelector('.wd-window-header')?.addEventListener('dblclick', (e) => {
            if (!e.target.closest('button, a')) {
                this.toggleMaximize(winEl);
            }
        });

        // Bawa ke depan saat diklik
        winEl.addEventListener('mousedown', () => {
            this.focusWindow(winEl);
        });

        // Aktifkan fitur Drag & Move
        this.makeDraggable(winEl);

        // Aktifkan fitur Resize
        this.makeResizable(winEl);

        workspace.appendChild(winEl);

        // Catat instance window ke state
        this.state.windows.push({
            id: winId,
            item: item,
            element: winEl,
            openedAt: new Date()
        });

        // Tambahkan icon window ke toolbar footer di samping tombol menu
        this.addTaskbarItem(winId, item, winEl);

        // Jika modul Manajemen Pengguna, Peran, Laporan Aktivitas, Profil, Wallpaper, atau CMS, render antarmuka masing-masing
        if (isUsersModule) {
            this.renderUserManagement(winEl);
        } else if (isRolesModule) {
            this.renderRoleManagement(winEl);
        } else if (isReportsModule) {
            this.renderActivityReports(winEl);
        } else if (isProfileModule) {
            this.renderProfileManagement(winEl);
        } else if (isWallpaperModule) {
            this.renderWallpaperManagement(winEl);
        } else if (isCmsNewsModule) {
            this.renderCmsNews(winEl);
        } else if (isCmsPagesModule) {
            this.renderCmsPages(winEl);
        } else if (isCmsTaxonomyModule) {
            this.renderCmsTaxonomy(winEl);
        } else if (isCmsMenusModule) {
            this.renderCmsPublicMenus(winEl);
        } else if (isCmsCommentsModule) {
            this.renderCmsComments(winEl);
        }

        // Trigger custom event agar desain UI atau endpoint loader dapat di-hook oleh user
        document.dispatchEvent(new CustomEvent('syntaxcore:window-open', {
            detail: { windowElement: winEl, item, app: this }
        }));

        console.log(`[WindowCore] Window dibuka: "${item.title}" (#${winId})`);
        return winEl;
    }

    /**
     * Mengaktifkan kemampuan Drag & Move pada jendela di dalam workspace.
     * Mendukung unsnap saat ditarik dari status maximized/snapped,
     * serta deteksi Aero Snap ke tepi workspace (atas = maximize, kiri = 50%, kanan = 50%).
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    makeDraggable(winEl) {
        const header = winEl.querySelector('.wd-window-header');
        if (!header) return;

        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let initialLeft = 0;
        let initialTop = 0;
        let snapTarget = null;
        let wasSnapped = false;
        let hasUnsnapped = false;

        const onMouseDown = (e) => {
            // Abaikan jika klik pada tombol kontrol atau elemen interaktif
            if (e.target.closest('.wd-window-close, .wd-window-maximize, button, a, input, select')) {
                return;
            }

            e.preventDefault();
            this.focusWindow(winEl);

            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            initialLeft = winEl.offsetLeft;
            initialTop = winEl.offsetTop;
            snapTarget = null;
            wasSnapped = (winEl.dataset.snapState && winEl.dataset.snapState !== 'none') || (winEl.dataset.isMaximized === 'true');
            hasUnsnapped = false;

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        };

        const onMouseMove = (e) => {
            if (!isDragging) return;

            const workspace = document.getElementById('wd-workspace');
            if (!workspace) return;

            const deltaX = e.clientX - startX;
            const deltaY = e.clientY - startY;

            // Unsnap jika jendela sedang dalam status maximized / snapped dan user mulai menggeser
            if (wasSnapped && !hasUnsnapped) {
                if (Math.abs(deltaX) > 4 || Math.abs(deltaY) > 4) {
                    const prevWidth = parseFloat(winEl.dataset.prevWidth) || 440;
                    const prevHeight = parseFloat(winEl.dataset.prevHeight) || 250;
                    const currentWidth = winEl.offsetWidth;

                    // Hitung rasio posisi horizontal kursor relatif terhadap header saat ini
                    const headerRect = header.getBoundingClientRect();
                    const cursorOffsetX = e.clientX - headerRect.left;
                    const ratio = currentWidth > 0 ? Math.min(1, Math.max(0, cursorOffsetX / currentWidth)) : 0.5;

                    winEl.classList.remove('wd-window-snapping');
                    winEl.style.width = `${prevWidth}px`;
                    winEl.style.height = `${prevHeight}px`;
                    winEl.style.borderRadius = '8px';
                    winEl.dataset.isMaximized = 'false';
                    winEl.dataset.snapState = 'none';

                    const icon = winEl.querySelector('.wd-window-maximize i');
                    if (icon) icon.className = 'fa-regular fa-square';

                    // Hitung posisi window baru agar kursor tetap menempel pada posisi proporsional header
                    const wsRect = workspace.getBoundingClientRect();
                    const mouseWsX = e.clientX - wsRect.left;
                    const mouseWsY = e.clientY - wsRect.top;

                    let restoredLeft = mouseWsX - (prevWidth * ratio);
                    let restoredTop = Math.max(0, mouseWsY - 15);

                    const maxLeft = Math.max(0, workspace.clientWidth - 80);
                    const maxTop = Math.max(0, workspace.clientHeight - 40);
                    restoredLeft = Math.max(0, Math.min(restoredLeft, maxLeft));
                    restoredTop = Math.max(0, Math.min(restoredTop, maxTop));

                    winEl.style.left = `${restoredLeft}px`;
                    winEl.style.top = `${restoredTop}px`;

                    initialLeft = restoredLeft;
                    initialTop = restoredTop;
                    startX = e.clientX;
                    startY = e.clientY;
                    hasUnsnapped = true;
                    wasSnapped = false;
                    return;
                } else {
                    return;
                }
            }

            let newLeft = initialLeft + (e.clientX - startX);
            let newTop = initialTop + (e.clientY - startY);

            const maxLeft = Math.max(0, workspace.clientWidth - 80);
            const maxTop = Math.max(0, workspace.clientHeight - 40);
            newLeft = Math.max(0, Math.min(newLeft, maxLeft));
            newTop = Math.max(0, Math.min(newTop, maxTop));

            winEl.style.left = `${newLeft}px`;
            winEl.style.top = `${newTop}px`;

            // Deteksi batas workspace untuk Aero Snap Ghost Preview
            const wsRect = workspace.getBoundingClientRect();
            const SNAP_THRESHOLD = 20;

            if (e.clientY <= wsRect.top + SNAP_THRESHOLD) {
                snapTarget = 'top';
            } else if (e.clientX <= wsRect.left + SNAP_THRESHOLD) {
                snapTarget = 'left';
            } else if (e.clientX >= wsRect.right - SNAP_THRESHOLD) {
                snapTarget = 'right';
            } else {
                snapTarget = null;
            }

            this.updateSnapPreview(snapTarget);
        };

        const onMouseUp = () => {
            isDragging = false;
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);

            this.hideSnapPreview();

            if (snapTarget) {
                this.snapWindow(winEl, snapTarget);
                snapTarget = null;
            } else {
                // Simpan koordinat baru jika dilepas dalam keadaan floating normal
                winEl.dataset.prevTop = `${winEl.offsetTop}px`;
                winEl.dataset.prevLeft = `${winEl.offsetLeft}px`;
            }
        };

        header.addEventListener('mousedown', onMouseDown);
    }

    /**
     * Mengaktifkan kemampuan Resize 8 arah (Desktop-style border resize) pada jendela di dalam workspace
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    makeResizable(winEl) {
        // Buat 8 border resize handles: 4 tepi (n, s, e, w) dan 4 sudut (nw, ne, sw, se)
        const directions = ['n', 's', 'e', 'w', 'nw', 'ne', 'sw', 'se'];
        directions.forEach(dir => {
            const handle = document.createElement('div');
            handle.className = `wd-resize-handle wd-resize-handle-${dir}`;
            handle.dataset.direction = dir;
            winEl.appendChild(handle);
            handle.addEventListener('mousedown', (e) => initResize(e, dir));
        });

        const initResize = (e, direction) => {
            e.preventDefault();
            e.stopPropagation();

            // Nonaktifkan resize jika sedang maximized atau dalam status snap
            const isMaximized = winEl.dataset.isMaximized === 'true';
            const snapState = winEl.dataset.snapState || 'none';
            if (isMaximized || snapState !== 'none') {
                return;
            }

            this.focusWindow(winEl);

            const startX = e.clientX;
            const startY = e.clientY;
            const startWidth = winEl.offsetWidth;
            const startHeight = winEl.offsetHeight;
            const startLeft = winEl.offsetLeft;
            const startTop = winEl.offsetTop;
            const minWidth = 260;
            const minHeight = 150;
            const workspace = document.getElementById('wd-workspace');

            // Kunci cursor global saat dragging agar tidak flicker saat mouse bergerak cepat
            const cursorMap = {
                'n': 'ns-resize',
                's': 'ns-resize',
                'e': 'ew-resize',
                'w': 'ew-resize',
                'nw': 'nwse-resize',
                'se': 'nwse-resize',
                'ne': 'nesw-resize',
                'sw': 'nesw-resize'
            };
            const originalCursor = document.body.style.cursor;
            document.body.style.cursor = cursorMap[direction] || 'default';
            document.body.style.userSelect = 'none';

            const onMouseMove = (moveEvent) => {
                const deltaX = moveEvent.clientX - startX;
                const deltaY = moveEvent.clientY - startY;

                const wsWidth = workspace ? workspace.clientWidth : window.innerWidth;
                const wsHeight = workspace ? workspace.clientHeight : window.innerHeight;

                // --- 1. Horizontal Resizing ---
                if (direction.includes('e')) {
                    // Sisi Timur (Kanan): Melebarkan ke kanan
                    let newWidth = Math.max(minWidth, startWidth + deltaX);
                    const maxWidth = wsWidth - startLeft;
                    newWidth = Math.min(newWidth, Math.max(minWidth, maxWidth));
                    winEl.style.width = `${newWidth}px`;
                } else if (direction.includes('w')) {
                    // Sisi Barat (Kiri): Melebarkan ke kiri dan menggeser left coordinate
                    let newWidth = startWidth - deltaX;
                    let newLeft = startLeft + deltaX;

                    if (newWidth < minWidth) {
                        newLeft = startLeft + (startWidth - minWidth);
                        newWidth = minWidth;
                    }
                    if (newLeft < 0) {
                        newWidth = newWidth + newLeft;
                        newLeft = 0;
                    }
                    winEl.style.width = `${newWidth}px`;
                    winEl.style.left = `${newLeft}px`;
                }

                // --- 2. Vertical Resizing ---
                if (direction.includes('s')) {
                    // Sisi Selatan (Bawah): Meninggikan ke bawah
                    let newHeight = Math.max(minHeight, startHeight + deltaY);
                    const maxHeight = wsHeight - startTop;
                    newHeight = Math.min(newHeight, Math.max(minHeight, maxHeight));
                    winEl.style.height = `${newHeight}px`;
                } else if (direction.includes('n')) {
                    // Sisi Utara (Atas): Meninggikan ke atas dan menggeser top coordinate
                    let newHeight = startHeight - deltaY;
                    let newTop = startTop + deltaY;

                    if (newHeight < minHeight) {
                        newTop = startTop + (startHeight - minHeight);
                        newHeight = minHeight;
                    }
                    if (newTop < 0) {
                        newHeight = newHeight + newTop;
                        newTop = 0;
                    }
                    winEl.style.height = `${newHeight}px`;
                    winEl.style.top = `${newTop}px`;
                }
            };

            const onMouseUp = () => {
                document.body.style.cursor = originalCursor;
                document.body.style.userSelect = '';
                document.removeEventListener('mousemove', onMouseMove);
                document.removeEventListener('mouseup', onMouseUp);

                // Perbarui ukuran memori pre-snap agar saat maximize-restore memakai dimensi baru
                winEl.dataset.prevWidth = `${winEl.offsetWidth}px`;
                winEl.dataset.prevHeight = `${winEl.offsetHeight}px`;
                winEl.dataset.prevTop = `${winEl.offsetTop}px`;
                winEl.dataset.prevLeft = `${winEl.offsetLeft}px`;
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        };
    }

    /**
     * Mendapatkan atau membuat elemen Ghost Preview untuk Aero Snapping
     * 
     * @returns {HTMLElement|null}
     */
    getSnapPreview() {
        const workspace = document.getElementById('wd-workspace');
        if (!workspace) return null;
        let preview = workspace.querySelector('#wd-snap-preview');
        if (!preview) {
            preview = document.createElement('div');
            preview.id = 'wd-snap-preview';
            preview.className = 'wd-snap-preview d-none';
            workspace.appendChild(preview);
        }
        return preview;
    }

    /**
     * Memperbarui posisi dan bentuk tampilan Aero Snap Ghost Preview
     * 
     * @param {'top'|'left'|'right'|null} snapTarget
     */
    updateSnapPreview(snapTarget) {
        const preview = this.getSnapPreview();
        if (!preview) return;

        if (!snapTarget) {
            preview.classList.add('d-none');
            return;
        }

        preview.classList.remove('d-none');
        if (snapTarget === 'top') {
            preview.style.top = '0px';
            preview.style.left = '0px';
            preview.style.width = '100%';
            preview.style.height = '100%';
            preview.style.borderRadius = '0px';
        } else if (snapTarget === 'left') {
            preview.style.top = '0px';
            preview.style.left = '0px';
            preview.style.width = '50%';
            preview.style.height = '100%';
            preview.style.borderRadius = '0px';
        } else if (snapTarget === 'right') {
            preview.style.top = '0px';
            preview.style.left = '50%';
            preview.style.width = '50%';
            preview.style.height = '100%';
            preview.style.borderRadius = '0px';
        }
    }

    /**
     * Menyembunyikan tampilan Ghost Preview
     */
    hideSnapPreview() {
        const preview = this.getSnapPreview();
        if (preview) {
            preview.classList.add('d-none');
        }
    }

    /**
     * Snap window ke posisi tertentu (top/maximize, left half, right half)
     * 
     * @param {HTMLElement} winEl Elemen window
     * @param {'top'|'left'|'right'} position Posisi snapping
     */
    snapWindow(winEl, position) {
        if (!winEl) return;
        this.focusWindow(winEl);

        const icon = winEl.querySelector('.wd-window-maximize i');
        const currentSnap = winEl.dataset.snapState || 'none';
        const isMaximized = winEl.dataset.isMaximized === 'true';

        // Simpan ukuran dan posisi sebelumnya hanya jika belum dalam status snapped / maximized
        if (currentSnap === 'none' && !isMaximized) {
            winEl.dataset.prevTop = winEl.style.top || `${winEl.offsetTop}px`;
            winEl.dataset.prevLeft = winEl.style.left || `${winEl.offsetLeft}px`;
            winEl.dataset.prevWidth = winEl.style.width || `${winEl.offsetWidth}px`;
            winEl.dataset.prevHeight = winEl.style.height || `${winEl.offsetHeight}px`;
        }

        // Aktifkan transisi halus snapping
        winEl.classList.add('wd-window-snapping');
        setTimeout(() => winEl.classList.remove('wd-window-snapping'), 180);

        winEl.dataset.snapState = position;

        if (position === 'top') {
            winEl.dataset.isMaximized = 'true';
            winEl.style.top = '0px';
            winEl.style.left = '0px';
            winEl.style.width = '100%';
            winEl.style.height = '100%';
            winEl.style.borderRadius = '0px';
            if (icon) icon.className = 'fa-regular fa-clone';
        } else if (position === 'left') {
            winEl.dataset.isMaximized = 'false';
            winEl.style.top = '0px';
            winEl.style.left = '0px';
            winEl.style.width = '50%';
            winEl.style.height = '100%';
            winEl.style.borderRadius = '0px';
            if (icon) icon.className = 'fa-regular fa-square';
        } else if (position === 'right') {
            winEl.dataset.isMaximized = 'false';
            winEl.style.top = '0px';
            winEl.style.left = '50%';
            winEl.style.width = '50%';
            winEl.style.height = '100%';
            winEl.style.borderRadius = '0px';
            if (icon) icon.className = 'fa-regular fa-square';
        }

        document.dispatchEvent(new CustomEvent('syntaxcore:window-snap', {
            detail: { windowElement: winEl, position, app: this }
        }));
    }

    /**
     * Restore ukuran dan posisi window ke kondisi sebelum di-maximize atau di-snap
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    restoreWindow(winEl) {
        if (!winEl) return;
        this.focusWindow(winEl);

        const icon = winEl.querySelector('.wd-window-maximize i');

        winEl.classList.add('wd-window-snapping');
        setTimeout(() => winEl.classList.remove('wd-window-snapping'), 180);

        winEl.style.top = winEl.dataset.prevTop || '30px';
        winEl.style.left = winEl.dataset.prevLeft || '30px';
        winEl.style.width = winEl.dataset.prevWidth || '440px';
        winEl.style.height = winEl.dataset.prevHeight || '250px';
        winEl.style.borderRadius = '8px';
        winEl.dataset.isMaximized = 'false';
        winEl.dataset.snapState = 'none';

        if (icon) icon.className = 'fa-regular fa-square';

        document.dispatchEvent(new CustomEvent('syntaxcore:window-restore', {
            detail: { windowElement: winEl, app: this }
        }));
    }

    /**
     * Toggle maximize dan restore ukuran window
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    toggleMaximize(winEl) {
        if (!winEl) return;
        const isMax = winEl.dataset.isMaximized === 'true' || winEl.dataset.snapState === 'top';
        if (isMax) {
            this.restoreWindow(winEl);
        } else {
            this.snapWindow(winEl, 'top');
        }
    }

    /**
     * Bawa window ke posisi paling depan (teratas) dan tandai aktif di toolbar footer
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    focusWindow(winEl) {
        if (!winEl) return;

        // Pastikan window tidak dalam status minimized saat difokuskan
        if (winEl.dataset.isMinimized === 'true') {
            winEl.classList.remove('d-none');
            winEl.dataset.isMinimized = 'false';
            const taskbarItem = document.getElementById(`wd-taskbar-${winEl.id}`);
            if (taskbarItem) {
                taskbarItem.classList.remove('minimized');
            }
        }

        const highestZ = Math.max(100, ...this.state.windows.map(w => parseInt(w.element?.style.zIndex || 100, 10)));
        winEl.style.zIndex = highestZ + 1;

        // Update active indicator di taskbar footer
        this.updateTaskbarActive(winEl.id);

        document.dispatchEvent(new CustomEvent('syntaxcore:window-focus', {
            detail: { windowElement: winEl, winId: winEl.id, app: this }
        }));
    }

    /**
     * Tutup window tertentu berdasarkan ID dan bersihkan icon dari toolbar footer
     * 
     * @param {string} winId ID window
     */
    closeWindow(winId) {
        const winEl = document.getElementById(winId);
        if (winEl) {
            if (typeof window.tinymce !== 'undefined') {
                winEl.querySelectorAll('textarea').forEach(ta => {
                    if (ta.id && window.tinymce.get(ta.id)) {
                        window.tinymce.get(ta.id).remove();
                    }
                });
            }
            winEl.remove();
        }
        this.state.windows = this.state.windows.filter(w => w.id !== winId);

        // Hapus icon dari toolbar footer
        this.removeTaskbarItem(winId);

        document.dispatchEvent(new CustomEvent('syntaxcore:window-close', {
            detail: { winId, app: this }
        }));
    }

    /**
     * Minimize window (sembunyikan ke toolbar footer)
     * 
     * @param {string} winId ID window
     */
    minimizeWindow(winId) {
        const winEl = document.getElementById(winId);
        if (!winEl) return;

        winEl.classList.add('d-none');
        winEl.dataset.isMinimized = 'true';

        const taskbarItem = document.getElementById(`wd-taskbar-${winId}`);
        if (taskbarItem) {
            taskbarItem.classList.remove('active');
            taskbarItem.classList.add('minimized');
        }

        // Fokuskan window lain yang masih terlihat (jika ada)
        const visibleWindows = this.state.windows
            .filter(w => w.id !== winId && w.element && w.element.dataset.isMinimized !== 'true')
            .sort((a, b) => parseInt(b.element.style.zIndex || 0, 10) - parseInt(a.element.style.zIndex || 0, 10));

        if (visibleWindows.length > 0) {
            this.focusWindow(visibleWindows[0].element);
        } else {
            this.updateTaskbarActive(null);
        }

        document.dispatchEvent(new CustomEvent('syntaxcore:window-minimize', {
            detail: { windowElement: winEl, winId, app: this }
        }));
    }

    /**
     * Tampilkan dan fokuskan window yang diminimize atau berada di background
     * 
     * @param {string} winId ID window
     */
    restoreAndFocusWindow(winId) {
        const winEl = document.getElementById(winId);
        if (!winEl) return;

        if (winEl.dataset.isMinimized === 'true') {
            winEl.classList.remove('d-none');
            winEl.dataset.isMinimized = 'false';
            const taskbarItem = document.getElementById(`wd-taskbar-${winId}`);
            if (taskbarItem) {
                taskbarItem.classList.remove('minimized');
            }
        }

        this.focusWindow(winEl);
    }

    /**
     * Tambahkan icon window aktif ke toolbar footer (#wd-active-content) di sebelah tombol menu
     * 
     * @param {string} winId ID window
     * @param {Object} item Data menu / window
     * @param {HTMLElement} winEl Elemen DOM window
     */
    addTaskbarItem(winId, item, winEl) {
        const taskbarContainer = document.getElementById('wd-active-content');
        if (!taskbarContainer) return;

        let taskbarItem = document.getElementById(`wd-taskbar-${winId}`);
        if (!taskbarItem) {
            taskbarItem = document.createElement('div');
            taskbarItem.id = `wd-taskbar-${winId}`;
            taskbarItem.className = 'wd-taskbar-item active';
            taskbarItem.setAttribute('data-win-id', winId);
            taskbarItem.title = item.title || 'Window';

            const iconClass = item.icon || 'fa-solid fa-window-maximize';

            taskbarItem.innerHTML = `
                <i class="${iconClass}"></i>
                <span class="wd-taskbar-title small text-truncate" style="max-width: 120px;">${item.title || 'App'}</span>
            `;

            taskbarItem.addEventListener('click', (e) => {
                e.stopPropagation();
                const isCurrentlyActive = taskbarItem.classList.contains('active') && winEl.dataset.isMinimized !== 'true';
                if (isCurrentlyActive) {
                    // Jika window sedang aktif dan terdepan, klik pada taskbar akan me-minimize
                    this.minimizeWindow(winId);
                } else {
                    // Jika window sedang di background atau minimized, klik akan menampilkan & memfokuskannya
                    this.restoreAndFocusWindow(winId);
                }
            });

            taskbarContainer.appendChild(taskbarItem);
        }

        this.updateTaskbarActive(winId);

        document.dispatchEvent(new CustomEvent('syntaxcore:taskbar-add', {
            detail: { winId, item, taskbarElement: taskbarItem, app: this }
        }));
    }

    /**
     * Hapus icon dari toolbar footer saat window ditutup
     * 
     * @param {string} winId ID window
     */
    removeTaskbarItem(winId) {
        const taskbarItem = document.getElementById(`wd-taskbar-${winId}`);
        if (taskbarItem) {
            taskbarItem.remove();
        }

        // Jika masih ada window lain yang terbuka dan tidak minimized, fokuskan window teratas
        const remainingWindows = this.state.windows
            .filter(w => w.id !== winId && w.element && w.element.dataset.isMinimized !== 'true')
            .sort((a, b) => parseInt(b.element.style.zIndex || 0, 10) - parseInt(a.element.style.zIndex || 0, 10));

        if (remainingWindows.length > 0) {
            this.focusWindow(remainingWindows[0].element);
        } else {
            this.updateTaskbarActive(null);
        }

        document.dispatchEvent(new CustomEvent('syntaxcore:taskbar-remove', {
            detail: { winId, app: this }
        }));
    }

    /**
     * Perbarui penanda status aktif pada icon taskbar di footer
     * 
     * @param {string|null} activeWinId ID window yang sedang aktif
     */
    updateTaskbarActive(activeWinId) {
        const taskbarContainer = document.getElementById('wd-active-content');
        if (!taskbarContainer) return;

        taskbarContainer.querySelectorAll('.wd-taskbar-item').forEach(item => {
            if (activeWinId && item.getAttribute('data-win-id') === activeWinId) {
                item.classList.add('active');
                item.classList.remove('minimized');
            } else {
                item.classList.remove('active');
            }
        });
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
     * Render Modul Master Data Pengguna ke dalam Body Window
     * Menyediakan antarmuka CRUD pengguna lengkap (List, Search, Add, Edit, Delete)
     * yang terhubung ke REST API /admin/users secara asinkron dengan CSRF protection.
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    renderUserManagement(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        body.innerHTML = `
            <!-- 1. Toolbar Atas Modul Pengguna -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 320px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="user-search-input-${winEl.id}" placeholder="Cari nama atau email..." autocomplete="off">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="user-count-badge-${winEl.id}">Memuat...</span>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-user-${winEl.id}">
                        <i class="fa-solid fa-user-plus"></i>
                        <span class="d-none d-sm-inline">Tambah User</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-user-${winEl.id}" title="Muat Ulang Data">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Alert Container -->
            <div id="user-alert-${winEl.id}" class="d-none px-3 pt-2"></div>

            <!-- 3. Form Input Drawer / Panel (Tersembunyi secara default) -->
            <div id="user-form-panel-${winEl.id}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0">
                <form id="user-form-${winEl.id}" autocomplete="off">
                    <input type="hidden" id="user-form-id-${winEl.id}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark" id="user-form-title-${winEl.id}">Tambah Pengguna Baru</h6>
                        <button type="button" class="btn-close" id="btn-cancel-user-${winEl.id}" aria-label="Batal"></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Nama Lengkap</label>
                            <input type="text" class="form-control form-control-sm" id="user-input-name-${winEl.id}" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Alamat Email</label>
                            <input type="email" class="form-control form-control-sm" id="user-input-email-${winEl.id}" placeholder="nama@syntaxcore.com" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Peran Akun (Role)</label>
                            <select class="form-select form-select-sm" id="user-input-role-${winEl.id}" required>
                                <option value="3">Regular User</option>
                                <option value="2">Administrator</option>
                                <option value="1">Super Administrator</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1 text-secondary" id="user-label-password-${winEl.id}">Password</label>
                            <input type="password" class="form-control form-control-sm" id="user-input-password-${winEl.id}" placeholder="Minimal 6 karakter" autocomplete="new-password">
                            <div class="form-text" id="user-help-password-${winEl.id}" style="font-size: 11px;"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-close-form-${winEl.id}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3" id="btn-submit-user-${winEl.id}">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. Area Tabel Pengguna -->
            <div class="flex-grow-1 overflow-auto bg-white position-relative">
                <table class="table table-hover table-striped mb-0 align-middle" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom" style="z-index: 2;">
                        <tr>
                            <th class="py-2 px-3" style="width: 50px;">#</th>
                            <th class="py-2 px-3">Nama Pengguna</th>
                            <th class="py-2 px-3">Email</th>
                            <th class="py-2 px-3" style="width: 140px;">Peran / Role</th>
                            <th class="py-2 px-3 text-center" style="width: 70px;">Level</th>
                            <th class="py-2 px-3 text-end" style="width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="user-tbody-${winEl.id}">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Memuat data pengguna dari server...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 5. Footer Bar Status Modul -->
            <div class="py-1 px-3 bg-light border-top d-flex justify-content-between align-items-center text-muted flex-shrink-0" style="font-size: 11px;">
                <span>Modul Master Data Pengguna &bull; Terhubung ke <code>/admin/users</code></span>
                <span id="user-role-status-${winEl.id}">Otorisasi: Admin</span>
            </div>
        `;

        // Cache state data pada window element
        winEl._userState = {
            users: [],
            roles: [],
            filter: ''
        };

        const alertContainer = body.querySelector(`#user-alert-${winEl.id}`);
        const countBadge = body.querySelector(`#user-count-badge-${winEl.id}`);
        const tbody = body.querySelector(`#user-tbody-${winEl.id}`);
        const searchInput = body.querySelector(`#user-search-input-${winEl.id}`);
        const formPanel = body.querySelector(`#user-form-panel-${winEl.id}`);
        const formTitle = body.querySelector(`#user-form-title-${winEl.id}`);
        const userForm = body.querySelector(`#user-form-${winEl.id}`);
        const inputId = body.querySelector(`#user-form-id-${winEl.id}`);
        const inputName = body.querySelector(`#user-input-name-${winEl.id}`);
        const inputEmail = body.querySelector(`#user-input-email-${winEl.id}`);
        const inputRole = body.querySelector(`#user-input-role-${winEl.id}`);
        const inputPassword = body.querySelector(`#user-input-password-${winEl.id}`);
        const labelPassword = body.querySelector(`#user-label-password-${winEl.id}`);
        const helpPassword = body.querySelector(`#user-help-password-${winEl.id}`);
        const btnAdd = body.querySelector(`#btn-add-user-${winEl.id}`);
        const btnRefresh = body.querySelector(`#btn-refresh-user-${winEl.id}`);
        const btnCancel = body.querySelector(`#btn-cancel-user-${winEl.id}`);
        const btnCloseForm = body.querySelector(`#btn-close-form-${winEl.id}`);
        const roleStatus = body.querySelector(`#user-role-status-${winEl.id}`);

        const showAlert = (message, type = 'success') => {
            if (!alertContainer) return;
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 small mb-2 d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="fa-solid ${type === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            alertContainer.classList.remove('d-none');
            setTimeout(() => {
                alertContainer.classList.add('d-none');
            }, 4000);
        };

        const renderTable = () => {
            const query = (winEl._userState.filter || '').trim().toLowerCase();
            const filtered = winEl._userState.users.filter(u => {
                if (!query) return true;
                return (u.name && u.name.toLowerCase().includes(query)) ||
                    (u.email && u.email.toLowerCase().includes(query)) ||
                    (u.role_name && u.role_name.toLowerCase().includes(query));
            });

            if (countBadge) {
                countBadge.textContent = `${filtered.length} dari ${winEl._userState.users.length} User`;
            }

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fa-regular fa-folder-open d-block mb-1 fs-4 text-secondary"></i>
                            ${query ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada data pengguna.'}
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filtered.map(u => {
                let badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                if (u.role === 'superadmin' || u.level >= 3) {
                    badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                } else if (u.role === 'admin' || u.level === 2) {
                    badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                }

                return `
                    <tr data-user-id="${u.id}">
                        <td class="px-3 text-muted small">${u.id}</td>
                        <td class="px-3">
                            <div class="fw-semibold text-dark">${u.name}</div>
                        </td>
                        <td class="px-3 text-muted">
                            <code>${u.email}</code>
                        </td>
                        <td class="px-3">
                            <span class="badge ${badgeClass} text-uppercase" style="font-size: 11px;">${u.role_name || u.role}</span>
                        </td>
                        <td class="px-3 text-center">
                            <span class="badge bg-light text-dark border" style="font-size: 10px;">Lvl ${u.level || 1}</span>
                        </td>
                        <td class="px-3 text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light btn-edit-user text-primary" data-user-id="${u.id}" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-delete-user text-danger" data-user-id="${u.id}" title="Hapus User">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Pasang event edit
            tbody.querySelectorAll('.btn-edit-user').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const userId = parseInt(btn.getAttribute('data-user-id'), 10);
                    const user = winEl._userState.users.find(u => u.id === userId);
                    if (!user) return;

                    formTitle.textContent = `Edit Pengguna #${user.id} - ${user.name}`;
                    inputId.value = user.id;
                    inputName.value = user.name;
                    inputEmail.value = user.email;
                    inputRole.value = user.role_id || (user.role === 'superadmin' ? '1' : (user.role === 'admin' ? '2' : '3'));
                    inputPassword.value = '';
                    inputPassword.required = false;
                    labelPassword.textContent = 'Ganti Password (Opsional)';
                    helpPassword.textContent = 'Kosongkan jika tidak ingin mengubah password saat ini.';

                    formPanel.classList.remove('d-none');
                    inputName.focus();
                });
            });

            // Pasang event delete
            tbody.querySelectorAll('.btn-delete-user').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const userId = parseInt(btn.getAttribute('data-user-id'), 10);
                    const user = winEl._userState.users.find(u => u.id === userId);
                    if (!user) return;

                    const confirmed = confirm(`Apakah Anda yakin ingin menghapus pengguna "${user.name}" (${user.email})?`);
                    if (!confirmed) return;

                    try {
                        let result = null;
                        if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                            result = await window.SyntaxCore.api(`/admin/users/${userId}`, { method: 'DELETE' });
                        } else {
                            const res = await fetch(`/admin/users/${userId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.getCsrfToken()
                                }
                            });
                            result = await res.json();
                            if (!res.ok) throw new Error(result?.message || 'Gagal menghapus user');
                        }

                        showAlert(result?.message || 'Pengguna berhasil dihapus', 'success');
                        fetchData();
                    } catch (err) {
                        showAlert(err.message || 'Terjadi kesalahan saat menghapus pengguna', 'danger');
                    }
                });
            });
        };

        const fetchData = async () => {
            if (countBadge) countBadge.textContent = 'Memuat...';
            try {
                let data = null;
                if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                    data = await window.SyntaxCore.api('/admin/users');
                } else {
                    const res = await fetch('/admin/users', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    data = await res.json();
                }

                if (data && data.status === 'success') {
                    winEl._userState.users = Array.isArray(data.users) ? data.users : [];
                    winEl._userState.roles = Array.isArray(data.roles) ? data.roles : [];

                    // Populate role select
                    if (winEl._userState.roles.length > 0) {
                        inputRole.innerHTML = winEl._userState.roles.map(r => `
                            <option value="${r.id}">${r.name} (Level ${r.level})</option>
                        `).join('');
                    }

                    if (roleStatus && data.authorized_role) {
                        roleStatus.innerHTML = `Akses: <strong class="text-uppercase text-primary">${data.authorized_role}</strong> &bull; Total: <strong>${data.total ?? winEl._userState.users.length}</strong>`;
                    }

                    renderTable();
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat data pengguna: ${data?.message || 'Unknown error'}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal menghubungi server: ${err.message}</td></tr>`;
            }
        };

        // Event listener toolbar
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                winEl._userState.filter = e.target.value;
                renderTable();
            });
        }

        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                fetchData();
            });
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', () => {
                formTitle.textContent = 'Tambah Pengguna Baru';
                inputId.value = '';
                inputName.value = '';
                inputEmail.value = '';
                inputPassword.value = '';
                inputPassword.required = true;
                labelPassword.textContent = 'Password Akun';
                helpPassword.textContent = 'Wajib diisi minimal 6 karakter.';

                formPanel.classList.remove('d-none');
                inputName.focus();
            });
        }

        const hideForm = () => {
            formPanel.classList.add('d-none');
            userForm.reset();
        };

        if (btnCancel) btnCancel.addEventListener('click', hideForm);
        if (btnCloseForm) btnCloseForm.addEventListener('click', hideForm);

        // Submit form (Create / Update)
        if (userForm) {
            userForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const userId = inputId.value ? parseInt(inputId.value, 10) : null;
                const name = inputName.value.trim();
                const email = inputEmail.value.trim();
                const roleId = parseInt(inputRole.value, 10);
                const password = inputPassword.value;

                if (!name || !email) {
                    showAlert('Nama dan email wajib diisi', 'danger');
                    return;
                }

                if (!userId && !password) {
                    showAlert('Password wajib diisi untuk pengguna baru', 'danger');
                    return;
                }

                const payload = {
                    name,
                    email,
                    role_id: roleId
                };
                if (password) {
                    payload.password = password;
                }

                const submitBtn = body.querySelector(`#btn-submit-user-${winEl.id}`);
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
                }

                try {
                    const url = userId ? `/admin/users/${userId}` : '/admin/users';
                    const method = userId ? 'PUT' : 'POST';

                    let result = null;
                    if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                        result = await window.SyntaxCore.api(url, { method, body: payload });
                    } else {
                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        result = await res.json();
                        if (!res.ok) throw new Error(result?.message || 'Gagal menyimpan data');
                    }

                    showAlert(result?.message || (userId ? 'Pengguna berhasil diperbarui' : 'Pengguna berhasil ditambahkan'), 'success');
                    hideForm();
                    fetchData();
                } catch (err) {
                    showAlert(err.message || 'Terjadi kesalahan saat menyimpan data', 'danger');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan';
                    }
                }
            });
        }

        // Ambil data pertama kali saat window dibuka
        fetchData();
    }

    /**
     * Merender antarmuka Modul Data Peran (Roles) & Hak Akses di dalam window desktop.
     * Mengelola daftar peran, penambahan/perubahan peran, dan matriks izin menu (role_menu).
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    renderRoleManagement(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        body.innerHTML = `
            <!-- 1. Toolbar Atas Modul Peran & Hak Akses -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 320px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="role-search-input-${winEl.id}" placeholder="Cari peran, slug, deskripsi..." autocomplete="off">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="role-count-badge-${winEl.id}">Memuat...</span>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-role-${winEl.id}">
                        <i class="fa-solid fa-plus"></i>
                        <span class="d-none d-sm-inline">Tambah Peran</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-role-${winEl.id}" title="Muat Ulang Data">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Alert Container -->
            <div id="role-alert-${winEl.id}" class="d-none px-3 pt-2"></div>

            <!-- 3. Form Input Drawer / Panel (Tersembunyi secara default) -->
            <div id="role-form-panel-${winEl.id}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0" style="max-height: 70%; overflow-y: auto;">
                <form id="role-form-${winEl.id}" autocomplete="off">
                    <input type="hidden" id="role-form-id-${winEl.id}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark" id="role-form-title-${winEl.id}">Tambah Peran Baru</h6>
                        <button type="button" class="btn-close" id="btn-cancel-role-${winEl.id}" aria-label="Batal"></button>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Nama Peran</label>
                            <input type="text" class="form-control form-control-sm" id="role-input-name-${winEl.id}" placeholder="Contoh: Manager Operasional" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Slug Identifier</label>
                            <input type="text" class="form-control form-control-sm font-monospace" id="role-input-slug-${winEl.id}" placeholder="contoh: manager-operasional">
                            <div class="form-text text-muted" style="font-size: 10px;">Otomatis dibuat jika dikosongkan.</div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Tingkat Otoritas (Level)</label>
                            <select class="form-select form-select-sm" id="role-input-level-${winEl.id}" required>
                                <option value="1">Level 1 - User</option>
                                <option value="2">Level 2 - Admin</option>
                                <option value="3">Level 3 - Superadmin</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1 text-secondary">Deskripsi Peran</label>
                            <input type="text" class="form-control form-control-sm" id="role-input-desc-${winEl.id}" placeholder="Deskripsi wewenang peran ini dalam sistem...">
                        </div>
                    </div>

                    <!-- Hak Akses Menu & Modul Navigasi -->
                    <div class="border rounded p-2 bg-white mb-2" id="role-permissions-section-${winEl.id}">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                            <div>
                                <span class="small fw-bold text-dark"><i class="fa-solid fa-shield-halved text-primary me-1"></i> Hak Akses Menu & Navigasi</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-1" id="role-selected-menus-count-${winEl.id}">0 dipilih</span>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" id="btn-select-all-menus-${winEl.id}" style="font-size: 11px;">Pilih Semua</button>
                                <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" id="btn-deselect-all-menus-${winEl.id}" style="font-size: 11px;">Kosongkan</button>
                            </div>
                        </div>
                        <div id="role-menus-tree-${winEl.id}" class="row g-2 overflow-auto" style="max-height: 180px;">
                            <div class="col-12 text-center py-2 text-muted small">Memuat daftar menu...</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-close-role-form-${winEl.id}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3" id="btn-submit-role-${winEl.id}">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>

            <!-- 4. Area Tabel Peran -->
            <div class="flex-grow-1 overflow-auto bg-white position-relative">
                <table class="table table-hover table-striped mb-0 align-middle" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom" style="z-index: 2;">
                        <tr>
                            <th class="py-2 px-3" style="width: 50px;">#</th>
                            <th class="py-2 px-3">Nama Peran & Deskripsi</th>
                            <th class="py-2 px-3" style="width: 140px;">Slug</th>
                            <th class="py-2 px-3 text-center" style="width: 80px;">Level</th>
                            <th class="py-2 px-3 text-center" style="width: 90px;">Pengguna</th>
                            <th class="py-2 px-3 text-center" style="width: 110px;">Hak Akses</th>
                            <th class="py-2 px-3 text-end" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="role-tbody-${winEl.id}">
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Memuat data peran dari server...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 5. Footer Bar Status Modul -->
            <div class="py-1 px-3 bg-light border-top d-flex justify-content-between align-items-center text-muted flex-shrink-0" style="font-size: 11px;">
                <span>Modul Master Data Peran &bull; Terhubung ke <code>/admin/roles</code></span>
                <span id="role-status-bar-${winEl.id}">Otorisasi: Admin</span>
            </div>
        `;

        // Cache state data pada window element
        winEl._roleState = {
            roles: [],
            menus: [],
            filter: '',
            authorized_level: 1,
            authorized_role: ''
        };

        const alertContainer = body.querySelector(`#role-alert-${winEl.id}`);
        const countBadge = body.querySelector(`#role-count-badge-${winEl.id}`);
        const tbody = body.querySelector(`#role-tbody-${winEl.id}`);
        const searchInput = body.querySelector(`#role-search-input-${winEl.id}`);
        const formPanel = body.querySelector(`#role-form-panel-${winEl.id}`);
        const formTitle = body.querySelector(`#role-form-title-${winEl.id}`);
        const roleForm = body.querySelector(`#role-form-${winEl.id}`);
        const inputId = body.querySelector(`#role-form-id-${winEl.id}`);
        const inputName = body.querySelector(`#role-input-name-${winEl.id}`);
        const inputSlug = body.querySelector(`#role-input-slug-${winEl.id}`);
        const inputLevel = body.querySelector(`#role-input-level-${winEl.id}`);
        const inputDesc = body.querySelector(`#role-input-desc-${winEl.id}`);
        const menusTreeContainer = body.querySelector(`#role-menus-tree-${winEl.id}`);
        const selectedCountBadge = body.querySelector(`#role-selected-menus-count-${winEl.id}`);
        const btnAdd = body.querySelector(`#btn-add-role-${winEl.id}`);
        const btnRefresh = body.querySelector(`#btn-refresh-role-${winEl.id}`);
        const btnCancel = body.querySelector(`#btn-cancel-role-${winEl.id}`);
        const btnCloseForm = body.querySelector(`#btn-close-role-form-${winEl.id}`);
        const btnSelectAll = body.querySelector(`#btn-select-all-menus-${winEl.id}`);
        const btnDeselectAll = body.querySelector(`#btn-deselect-all-menus-${winEl.id}`);
        const statusBar = body.querySelector(`#role-status-bar-${winEl.id}`);
        const permissionsSection = body.querySelector(`#role-permissions-section-${winEl.id}`);

        const showAlert = (message, type = 'success') => {
            if (!alertContainer) return;
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 small mb-2 d-flex align-items-center justify-content-between" role="alert">
                    <div>
                        <i class="fa-solid ${type === 'success' ? 'fa-circle-check text-success' : 'fa-triangle-exclamation text-danger'} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            alertContainer.classList.remove('d-none');
            setTimeout(() => {
                alertContainer.classList.add('d-none');
            }, 4000);
        };

        const updateSelectedCount = () => {
            if (!menusTreeContainer || !selectedCountBadge) return;
            const totalChecked = menusTreeContainer.querySelectorAll(`.role-menu-chk-${winEl.id}:checked`).length;
            selectedCountBadge.textContent = `${totalChecked} dipilih`;
        };

        const renderMenuCheckboxes = (selectedMenuIds = []) => {
            if (!menusTreeContainer) return;

            const menus = winEl._roleState.menus || [];
            if (menus.length === 0) {
                menusTreeContainer.innerHTML = '<div class="col-12 text-center py-2 text-muted small">Tidak ada data menu tersedia.</div>';
                updateSelectedCount();
                return;
            }

            const roots = menus.filter(m => m.parent_id === null || m.parent_id === 0);

            menusTreeContainer.innerHTML = roots.map(root => {
                const children = menus.filter(m => m.parent_id === root.id);
                const isRootChecked = selectedMenuIds.includes(root.id);

                if (children.length > 0) {
                    return `
                        <div class="col-12 col-md-6 mb-1">
                            <div class="p-2 border rounded bg-light-subtle h-100">
                                <div class="form-check fw-semibold mb-1">
                                    <input class="form-check-input role-menu-chk-${winEl.id}" type="checkbox" value="${root.id}" id="chk-m-${winEl.id}-${root.id}" data-parent-node="true" ${isRootChecked ? 'checked' : ''}>
                                    <label class="form-check-label small" for="chk-m-${winEl.id}-${root.id}">
                                        <i class="${root.icon || 'fa-solid fa-folder'} me-1 text-primary"></i> ${root.title}
                                    </label>
                                </div>
                                <div class="ms-3 ps-2 border-start">
                                    ${children.map(child => {
                        const isChildChecked = selectedMenuIds.includes(child.id);
                        return `
                                            <div class="form-check my-1">
                                                <input class="form-check-input role-menu-chk-${winEl.id}" type="checkbox" value="${child.id}" id="chk-m-${winEl.id}-${child.id}" data-parent-id="${root.id}" ${isChildChecked ? 'checked' : ''}>
                                                <label class="form-check-label small text-secondary" for="chk-m-${winEl.id}-${child.id}">
                                                    <i class="${child.icon || 'fa-regular fa-circle'} me-1 small"></i> ${child.title}
                                                </label>
                                            </div>
                                        `;
                    }).join('')}
                                </div>
                            </div>
                        </div>
                    `;
                }

                return `
                    <div class="col-12 col-md-6 mb-1">
                        <div class="p-2 border rounded bg-light-subtle h-100">
                            <div class="form-check">
                                <input class="form-check-input role-menu-chk-${winEl.id}" type="checkbox" value="${root.id}" id="chk-m-${winEl.id}-${root.id}" ${isRootChecked ? 'checked' : ''}>
                                <label class="form-check-label small" for="chk-m-${winEl.id}-${root.id}">
                                    <i class="${root.icon || 'fa-solid fa-circle-notch'} me-1 text-primary"></i> ${root.title}
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Pasang event bubbling / auto-toggle parent-child
            menusTreeContainer.querySelectorAll(`.role-menu-chk-${winEl.id}`).forEach(chk => {
                chk.addEventListener('change', (e) => {
                    const isParentNode = chk.getAttribute('data-parent-node') === 'true';
                    const parentId = chk.getAttribute('data-parent-id');

                    if (isParentNode) {
                        // Jika parent dicentang/dilepas, sesuaikan semua anaknya
                        const childBoxes = menusTreeContainer.querySelectorAll(`input[data-parent-id="${chk.value}"]`);
                        childBoxes.forEach(cb => {
                            cb.checked = chk.checked;
                        });
                    } else if (parentId && chk.checked) {
                        // Jika anak dicentang, pastikan parent juga tercentang agar navigasi dapat diakses
                        const parentBox = menusTreeContainer.querySelector(`#chk-m-${winEl.id}-${parentId}`);
                        if (parentBox) parentBox.checked = true;
                    }

                    updateSelectedCount();
                });
            });

            updateSelectedCount();
        };

        const renderTable = () => {
            const query = (winEl._roleState.filter || '').trim().toLowerCase();
            const filtered = winEl._roleState.roles.filter(r => {
                if (!query) return true;
                return (r.name && r.name.toLowerCase().includes(query)) ||
                    (r.slug && r.slug.toLowerCase().includes(query)) ||
                    (r.description && r.description.toLowerCase().includes(query));
            });

            if (countBadge) {
                countBadge.textContent = `${filtered.length} dari ${winEl._roleState.roles.length} Peran`;
            }

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fa-regular fa-folder-open d-block mb-1 fs-4 text-secondary"></i>
                            ${query ? 'Tidak ada peran yang cocok dengan pencarian.' : 'Belum ada data peran.'}
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = filtered.map(r => {
                let badgeClass = 'bg-secondary-subtle text-secondary border border-secondary-subtle';
                if (r.slug === 'superadmin' || r.level >= 3) {
                    badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                } else if (r.slug === 'admin' || r.level === 2) {
                    badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                }

                const isSuperAdmin = (r.slug === 'superadmin');
                const hasUsers = (r.user_count > 0);
                const canDelete = !isSuperAdmin && !hasUsers;

                return `
                    <tr data-role-id="${r.id}">
                        <td class="px-3 text-muted small">${r.id}</td>
                        <td class="px-3">
                            <div class="fw-semibold text-dark">${r.name}</div>
                            ${r.description ? `<div class="text-muted small text-truncate" style="max-width: 260px;">${r.description}</div>` : ''}
                        </td>
                        <td class="px-3 text-muted">
                            <code>${r.slug}</code>
                        </td>
                        <td class="px-3 text-center">
                            <span class="badge ${badgeClass}" style="font-size: 10px;">Level ${r.level}</span>
                        </td>
                        <td class="px-3 text-center">
                            <span class="badge bg-light text-dark border" style="font-size: 11px;">
                                <i class="fa-solid fa-users me-1 text-muted"></i>${r.user_count ?? 0}
                            </span>
                        </td>
                        <td class="px-3 text-center">
                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 11px;">
                                <i class="fa-solid fa-key me-1"></i>${r.menu_ids ? r.menu_ids.length : 0} Menu
                            </span>
                        </td>
                        <td class="px-3 text-end">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-light btn-edit-role text-primary" data-role-id="${r.id}" title="Edit Data Peran">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-perms-role text-success" data-role-id="${r.id}" title="Kelola Hak Akses Menu">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </button>
                                <button type="button" class="btn btn-light btn-delete-role text-danger ${!canDelete ? 'disabled opacity-50' : ''}" data-role-id="${r.id}" ${!canDelete ? `disabled title="${isSuperAdmin ? 'Superadmin diproteksi' : 'Masih digunakan oleh pengguna'}"` : 'title="Hapus Peran"'}>
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            // Handler tombol Edit
            tbody.querySelectorAll('.btn-edit-role').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const roleId = parseInt(btn.getAttribute('data-role-id'), 10);
                    const role = winEl._roleState.roles.find(r => r.id === roleId);
                    if (!role) return;

                    formTitle.textContent = `Edit Peran #${role.id} - ${role.name}`;
                    inputId.value = role.id;
                    inputName.value = role.name;
                    inputSlug.value = role.slug;
                    inputLevel.value = role.level;
                    inputDesc.value = role.description || '';

                    // Jika superadmin, proteksi slug dan level
                    if (role.slug === 'superadmin') {
                        inputSlug.disabled = true;
                        inputLevel.disabled = true;
                    } else {
                        inputSlug.disabled = false;
                        inputLevel.disabled = false;
                    }

                    renderMenuCheckboxes(role.menu_ids || []);
                    formPanel.classList.remove('d-none');
                    inputName.focus();
                });
            });

            // Handler tombol Hak Akses
            tbody.querySelectorAll('.btn-perms-role').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const roleId = parseInt(btn.getAttribute('data-role-id'), 10);
                    const role = winEl._roleState.roles.find(r => r.id === roleId);
                    if (!role) return;

                    formTitle.textContent = `Hak Akses Menu: ${role.name} (${role.slug})`;
                    inputId.value = role.id;
                    inputName.value = role.name;
                    inputSlug.value = role.slug;
                    inputLevel.value = role.level;
                    inputDesc.value = role.description || '';

                    if (role.slug === 'superadmin') {
                        inputSlug.disabled = true;
                        inputLevel.disabled = true;
                    } else {
                        inputSlug.disabled = false;
                        inputLevel.disabled = false;
                    }

                    renderMenuCheckboxes(role.menu_ids || []);
                    formPanel.classList.remove('d-none');

                    // Highlight and scroll to permissions section
                    if (permissionsSection) {
                        permissionsSection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            });

            // Handler tombol Hapus
            tbody.querySelectorAll('.btn-delete-role').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const roleId = parseInt(btn.getAttribute('data-role-id'), 10);
                    const role = winEl._roleState.roles.find(r => r.id === roleId);
                    if (!role) return;

                    if (role.slug === 'superadmin') {
                        showAlert('Peran Super Administrator diproteksi dan tidak dapat dihapus.', 'danger');
                        return;
                    }

                    if (role.user_count > 0) {
                        showAlert(`Peran "${role.name}" masih digunakan oleh ${role.user_count} pengguna dan tidak dapat dihapus.`, 'danger');
                        return;
                    }

                    const confirmed = confirm(`Apakah Anda yakin ingin menghapus peran "${role.name}"? Tindakan ini tidak dapat dibatalkan.`);
                    if (!confirmed) return;

                    try {
                        let result = null;
                        if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                            result = await window.SyntaxCore.api(`/admin/roles/${roleId}`, { method: 'DELETE' });
                        } else {
                            const res = await fetch(`/admin/roles/${roleId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': this.getCsrfToken()
                                }
                            });
                            result = await res.json();
                            if (!res.ok) throw new Error(result?.message || 'Gagal menghapus peran');
                        }

                        showAlert(result?.message || 'Peran berhasil dihapus', 'success');
                        document.dispatchEvent(new CustomEvent('syntaxcore:roles-updated', { detail: { action: 'delete', roleId } }));
                        fetchData();
                    } catch (err) {
                        showAlert(err.message || 'Terjadi kesalahan saat menghapus peran', 'danger');
                    }
                });
            });
        };

        const fetchData = async () => {
            if (countBadge) countBadge.textContent = 'Memuat...';
            try {
                let data = null;
                if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                    data = await window.SyntaxCore.api('/admin/roles');
                } else {
                    const res = await fetch('/admin/roles', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    data = await res.json();
                }

                if (data && data.status === 'success') {
                    winEl._roleState.roles = Array.isArray(data.roles) ? data.roles : [];
                    winEl._roleState.menus = Array.isArray(data.menus) ? data.menus : [];
                    winEl._roleState.authorized_level = data.authorized_level || 1;
                    winEl._roleState.authorized_role = data.authorized_role || '';

                    // Sesuaikan dropdown level sesuai otorisasi
                    if (inputLevel && winEl._roleState.authorized_level < 3) {
                        const optSuper = inputLevel.querySelector('option[value="3"]');
                        if (optSuper) optSuper.disabled = true;
                    }

                    if (statusBar && data.authorized_role) {
                        statusBar.innerHTML = `Otorisasi: <strong class="text-uppercase text-primary">${data.authorized_role}</strong> (Level ${data.authorized_level || 1}) &bull; Total: <strong>${winEl._roleState.roles.length} Peran</strong>`;
                    }

                    renderTable();
                } else {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data peran: ${data?.message || 'Unknown error'}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal menghubungi server: ${err.message}</td></tr>`;
            }
        };

        // Event listener toolbar
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                winEl._roleState.filter = e.target.value;
                renderTable();
            });
        }

        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                fetchData();
            });
        }

        if (btnAdd) {
            btnAdd.addEventListener('click', () => {
                formTitle.textContent = 'Tambah Peran Baru';
                inputId.value = '';
                inputName.value = '';
                inputSlug.value = '';
                inputSlug.disabled = false;
                inputLevel.value = '1';
                inputLevel.disabled = false;
                inputDesc.value = '';

                renderMenuCheckboxes([]);
                formPanel.classList.remove('d-none');
                inputName.focus();
            });
        }

        const hideForm = () => {
            formPanel.classList.add('d-none');
            roleForm.reset();
            inputSlug.disabled = false;
            inputLevel.disabled = false;
        };

        if (btnCancel) btnCancel.addEventListener('click', hideForm);
        if (btnCloseForm) btnCloseForm.addEventListener('click', hideForm);

        // Tombol Pilih Semua & Kosongkan Hak Akses Menu
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', () => {
                menusTreeContainer.querySelectorAll(`.role-menu-chk-${winEl.id}`).forEach(chk => {
                    chk.checked = true;
                });
                updateSelectedCount();
            });
        }

        if (btnDeselectAll) {
            btnDeselectAll.addEventListener('click', () => {
                menusTreeContainer.querySelectorAll(`.role-menu-chk-${winEl.id}`).forEach(chk => {
                    chk.checked = false;
                });
                updateSelectedCount();
            });
        }

        // Submit form (Create / Update Peran & Hak Akses)
        if (roleForm) {
            roleForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const roleId = inputId.value ? parseInt(inputId.value, 10) : null;
                const name = inputName.value.trim();
                const slug = inputSlug.value.trim();
                const level = parseInt(inputLevel.value, 10) || 1;
                const description = inputDesc.value.trim();

                if (!name) {
                    showAlert('Nama peran wajib diisi', 'danger');
                    return;
                }

                // Ambil semua ID menu yang dicentang
                const checkedMenuIds = Array.from(menusTreeContainer.querySelectorAll(`.role-menu-chk-${winEl.id}:checked`))
                    .map(cb => parseInt(cb.value, 10));

                const payload = {
                    name,
                    slug,
                    level,
                    description,
                    menu_ids: checkedMenuIds
                };

                const submitBtn = body.querySelector(`#btn-submit-role-${winEl.id}`);
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
                }

                try {
                    const url = roleId ? `/admin/roles/${roleId}` : '/admin/roles';
                    const method = roleId ? 'PUT' : 'POST';

                    let result = null;
                    if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                        result = await window.SyntaxCore.api(url, { method, body: payload });
                    } else {
                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify(payload)
                        });
                        result = await res.json();
                        if (!res.ok) throw new Error(result?.message || 'Gagal menyimpan data');
                    }

                    showAlert(result?.message || (roleId ? 'Data peran berhasil diperbarui' : 'Peran baru berhasil ditambahkan'), 'success');
                    hideForm();
                    document.dispatchEvent(new CustomEvent('syntaxcore:roles-updated', { detail: { action: roleId ? 'update' : 'create', roleId, result } }));
                    fetchData();
                } catch (err) {
                    showAlert(err.message || 'Terjadi kesalahan saat menyimpan peran', 'danger');
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Simpan';
                    }
                }
            });
        }

        // Ambil data pertama kali saat window dibuka
        fetchData();
    }

    /**
     * Inisialisasi sistem notifikasi header (bell icon, dropdown tray, unread badge, & polling)
     */
    initNotificationSystem() {
        const bellBtn = document.getElementById('wd-header-bell');
        const tray = document.getElementById('wd-header-notif-tray');
        const badge = document.getElementById('wd-header-notif-badge');
        const listContainer = document.getElementById('wd-notif-list');
        const btnMarkAll = document.getElementById('btn-mark-all-read');

        if (!bellBtn || !tray) return;

        let notificationsCache = [];

        const fetchNotifications = async (silent = true) => {
            try {
                let data = null;
                if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                    data = await window.SyntaxCore.api('/admin/notifications');
                } else {
                    const res = await fetch('/admin/notifications', {
                        headers: { 'Accept': 'application/json' }
                    });
                    data = await res.json();
                }

                if (data && data.status === 'success') {
                    notificationsCache = Array.isArray(data.notifications) ? data.notifications : [];
                    const unreadCount = data.unread_count || 0;

                    if (badge) {
                        if (unreadCount > 0) {
                            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                            badge.classList.remove('d-none');
                        } else {
                            badge.classList.add('d-none');
                        }
                    }

                    renderNotifList();
                }
            } catch (err) {
                console.warn('[WindowCore] Gagal mengambil notifikasi:', err);
            }
        };

        const renderNotifList = () => {
            if (!listContainer) return;

            if (notificationsCache.length === 0) {
                listContainer.innerHTML = `
                    <div class="p-4 text-center text-muted small">
                        <i class="fa-regular fa-bell-slash d-block mb-1 fs-4 text-secondary opacity-50"></i>
                        Tidak ada notifikasi baru.
                    </div>
                `;
                return;
            }

            const iconMap = {
                success: 'fa-solid fa-circle-check text-success',
                warning: 'fa-solid fa-triangle-exclamation text-warning',
                danger: 'fa-solid fa-circle-exclamation text-danger',
                info: 'fa-solid fa-circle-info text-primary'
            };

            listContainer.innerHTML = notificationsCache.map(n => `
                <div class="list-group-item list-group-item-action py-2 px-3 notif-item ${n.is_read ? 'opacity-75' : 'bg-light-subtle fw-semibold'}" data-notif-id="${n.id}" style="cursor: pointer;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="${iconMap[n.type] || iconMap.info} mt-1"></i>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-dark small text-truncate ${n.is_read ? '' : 'fw-bold'}">${n.title}</span>
                                <span class="text-muted font-monospace" style="font-size: 10px;">${(n.created_at || '').split(' ')[1] || ''}</span>
                            </div>
                            <div class="text-secondary small fw-normal" style="font-size: 11px; line-height: 1.3;">
                                ${n.message}
                            </div>
                        </div>
                        ${!n.is_read ? '<span class="badge bg-primary rounded-circle p-1" style="font-size: 5px;" title="Belum dibaca">&bull;</span>' : ''}
                    </div>
                </div>
            `).join('');

            // Pasang event klik item notifikasi untuk tandai dibaca
            listContainer.querySelectorAll('.notif-item').forEach(item => {
                item.addEventListener('click', async () => {
                    const notifId = parseInt(item.getAttribute('data-notif-id'), 10);
                    const notif = notificationsCache.find(n => n.id === notifId);
                    if (notif && !notif.is_read) {
                        notif.is_read = true;
                        try {
                            if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                                await window.SyntaxCore.api(`/admin/notifications/${notifId}/read`, { method: 'POST' });
                            } else {
                                await fetch(`/admin/notifications/${notifId}/read`, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': this.getCsrfToken()
                                    }
                                });
                            }
                            fetchNotifications();
                        } catch (e) {
                            console.warn('[WindowCore] Gagal menandai notifikasi dibaca:', e);
                        }
                    }
                });
            });
        };

        // Toggle dropdown saat bell diklik
        bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            tray.classList.toggle('d-none');
            if (!tray.classList.contains('d-none')) {
                fetchNotifications();
            }
        });

        // Hover effect pada bell
        bellBtn.addEventListener('mouseenter', () => { bellBtn.style.color = '#ffffff'; });
        bellBtn.addEventListener('mouseleave', () => { bellBtn.style.color = ''; });

        // Klik di luar untuk menutup tray
        document.addEventListener('click', (e) => {
            if (!tray.contains(e.target) && !bellBtn.contains(e.target)) {
                tray.classList.add('d-none');
            }
        });

        // Tombol tandai semua dibaca
        if (btnMarkAll) {
            btnMarkAll.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                        await window.SyntaxCore.api('/admin/notifications/read-all', { method: 'POST' });
                    } else {
                        await fetch('/admin/notifications/read-all', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            }
                        });
                    }
                    notificationsCache.forEach(n => n.is_read = true);
                    if (badge) badge.classList.add('d-none');
                    renderNotifList();
                } catch (err) {
                    console.warn('[WindowCore] Gagal menandai semua notifikasi dibaca:', err);
                }
            });
        }

        // Ambil notifikasi pertama kali
        fetchNotifications();

        // Polling notifikasi setiap 30 detik
        if (this._notifInterval) clearInterval(this._notifInterval);
        this._notifInterval = setInterval(() => fetchNotifications(true), 30000);
    }

    /**
     * Menampilkan desktop toast notification yang melayang secara halus di sudut workspace
     * 
     * @param {string} title Judul notifikasi
     * @param {string} message Pesan notifikasi
     * @param {string} type Tipe notifikasi ('info', 'success', 'warning', 'danger')
     */
    showToast(title, message, type = 'info') {
        let toastContainer = document.getElementById('wd-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'wd-toast-container';
            toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
            toastContainer.style.cssText = 'z-index: 10050; pointer-events: none; max-width: 380px;';
            document.body.appendChild(toastContainer);
        }

        const iconMap = {
            success: 'fa-solid fa-circle-check text-success',
            warning: 'fa-solid fa-triangle-exclamation text-warning',
            danger: 'fa-solid fa-circle-exclamation text-danger',
            info: 'fa-solid fa-circle-info text-primary'
        };

        const toastEl = document.createElement('div');
        toastEl.className = 'card shadow-lg border mb-2 fade show';
        toastEl.style.cssText = 'pointer-events: auto; border-radius: 8px; font-size: 13px; animation: fadeIn 0.25s ease;';
        toastEl.innerHTML = `
            <div class="card-body p-3 d-flex align-items-start gap-2">
                <i class="${iconMap[type] || iconMap.info} mt-1 fs-6"></i>
                <div class="flex-grow-1 text-truncate">
                    <div class="fw-bold text-dark small text-truncate">${title}</div>
                    <div class="text-secondary small" style="white-space: normal;">${message}</div>
                </div>
                <button type="button" class="btn-close btn-sm p-1 ms-1" aria-label="Close"></button>
            </div>
        `;

        toastEl.querySelector('.btn-close')?.addEventListener('click', () => {
            toastEl.remove();
        });

        toastContainer.appendChild(toastEl);

        setTimeout(() => {
            if (toastEl.parentNode) {
                toastEl.classList.remove('show');
                setTimeout(() => toastEl.remove(), 250);
            }
        }, 4500);
    }

    /**
     * Merender antarmuka Modul Laporan Aktivitas (Audit Log) di dalam window desktop.
     * Menampilkan riwayat aksi pengguna, filter kategori, pencarian, dan informasi IP.
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    renderActivityReports(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        body.innerHTML = `
            <!-- 1. Toolbar Atas Modul Laporan Aktivitas -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 440px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="report-search-input-${winEl.id}" placeholder="Cari deskripsi, pengguna, IP..." autocomplete="off">
                    </div>
                    <select class="form-select form-select-sm" id="report-action-filter-${winEl.id}" style="max-width: 170px;">
                        <option value="">Semua Aksi</option>
                        <option value="auth">Autentikasi</option>
                        <option value="user">Pengguna</option>
                        <option value="role">Peran & Akses</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="report-count-badge-${winEl.id}">Memuat...</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle d-none d-sm-inline" id="report-today-badge-${winEl.id}">Hari Ini: 0</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-reports-${winEl.id}" title="Muat Ulang Log">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Alert Container -->
            <div id="report-alert-${winEl.id}" class="d-none px-3 pt-2"></div>

            <!-- 3. Area Tabel Log Aktivitas -->
            <div class="flex-grow-1 overflow-auto bg-white position-relative">
                <table class="table table-hover table-striped mb-0 align-middle" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom" style="z-index: 2;">
                        <tr>
                            <th class="py-2 px-3" style="width: 50px;">#</th>
                            <th class="py-2 px-3" style="width: 150px;">Waktu Kejadian</th>
                            <th class="py-2 px-3" style="width: 150px;">Pengguna</th>
                            <th class="py-2 px-3" style="width: 130px;">Aksi</th>
                            <th class="py-2 px-3">Rincian Aktivitas</th>
                            <th class="py-2 px-3 text-end" style="width: 130px;">Alamat IP</th>
                        </tr>
                    </thead>
                    <tbody id="report-tbody-${winEl.id}">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Memuat data laporan aktivitas dari server...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. Footer Bar Status Modul -->
            <div class="py-1 px-3 bg-light border-top d-flex justify-content-between align-items-center text-muted flex-shrink-0" style="font-size: 11px;">
                <span>Modul Laporan Aktivitas &bull; Terhubung ke <code>/admin/reports</code></span>
                <span id="report-status-bar-${winEl.id}">Otorisasi: Admin</span>
            </div>
        `;

        winEl._reportState = {
            logs: [],
            total: 0,
            todayCount: 0,
            searchQuery: '',
            actionFilter: ''
        };

        const countBadge = body.querySelector(`#report-count-badge-${winEl.id}`);
        const todayBadge = body.querySelector(`#report-today-badge-${winEl.id}`);
        const tbody = body.querySelector(`#report-tbody-${winEl.id}`);
        const searchInput = body.querySelector(`#report-search-input-${winEl.id}`);
        const actionSelect = body.querySelector(`#report-action-filter-${winEl.id}`);
        const btnRefresh = body.querySelector(`#btn-refresh-reports-${winEl.id}`);
        const statusBar = body.querySelector(`#report-status-bar-${winEl.id}`);

        const getActionBadge = (action) => {
            if (action.startsWith('auth.login')) {
                return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-right-to-bracket me-1"></i>auth.login</span>';
            } else if (action.startsWith('auth.logout')) {
                return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fa-solid fa-right-from-bracket me-1"></i>auth.logout</span>';
            } else if (action.startsWith('auth.failed')) {
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-triangle-exclamation me-1"></i>auth.failed</span>';
            } else if (action.startsWith('user.create')) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-user-plus me-1"></i>user.create</span>';
            } else if (action.startsWith('user.update')) {
                return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fa-solid fa-user-pen me-1"></i>user.update</span>';
            } else if (action.startsWith('user.delete')) {
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-user-xmark me-1"></i>user.delete</span>';
            } else if (action.startsWith('role.create')) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fa-solid fa-shield me-1"></i>role.create</span>';
            } else if (action.startsWith('role.update')) {
                return '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fa-solid fa-shield-halved me-1"></i>role.update</span>';
            } else if (action.startsWith('role.delete')) {
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-trash me-1"></i>role.delete</span>';
            }
            return `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">${action}</span>`;
        };

        const renderTable = () => {
            const logs = winEl._reportState.logs || [];

            if (countBadge) {
                countBadge.textContent = `${logs.length} Log Ditampilkan`;
            }
            if (todayBadge) {
                todayBadge.textContent = `Hari Ini: ${winEl._reportState.todayCount}`;
            }

            if (logs.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fa-regular fa-clipboard d-block mb-1 fs-4 text-secondary"></i>
                            Tidak ada data log aktivitas yang sesuai.
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td class="px-3 text-muted small">${log.id}</td>
                    <td class="px-3">
                        <div class="small font-monospace text-secondary">${log.created_at}</div>
                    </td>
                    <td class="px-3">
                        <div class="fw-semibold text-dark text-truncate" style="max-width: 140px;">${log.user_name}</div>
                        ${log.user_email && log.user_email !== '-' ? `<div class="text-muted" style="font-size: 11px;"><code>${log.user_email}</code></div>` : ''}
                    </td>
                    <td class="px-3">
                        ${getActionBadge(log.action)}
                    </td>
                    <td class="px-3">
                        <div class="text-dark small">${log.description}</div>
                    </td>
                    <td class="px-3 text-end">
                        <code class="small">${log.ip_address}</code>
                    </td>
                </tr>
            `).join('');
        };

        const fetchData = async () => {
            if (countBadge) countBadge.textContent = 'Memuat...';
            try {
                const params = new URLSearchParams();
                if (winEl._reportState.searchQuery) params.append('q', winEl._reportState.searchQuery);
                if (winEl._reportState.actionFilter) params.append('action', winEl._reportState.actionFilter);

                const queryStr = params.toString() ? `?${params.toString()}` : '';
                let data = null;

                if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                    data = await window.SyntaxCore.api(`/admin/reports${queryStr}`);
                } else {
                    const res = await fetch(`/admin/reports${queryStr}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    data = await res.json();
                }

                if (data && data.status === 'success') {
                    winEl._reportState.logs = Array.isArray(data.logs) ? data.logs : [];
                    winEl._reportState.total = data.total || 0;
                    winEl._reportState.todayCount = data.today_count || 0;

                    if (statusBar && data.authorized_role) {
                        statusBar.innerHTML = `Otorisasi: <strong class="text-uppercase text-primary">${data.authorized_role}</strong> &bull; Total Tercatat: <strong>${data.total || 0} Log</strong>`;
                    }

                    renderTable();
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat log: ${data?.message || 'Unknown error'}</td></tr>`;
                }
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal menghubungi server: ${err.message}</td></tr>`;
            }
        };

        let searchTimeout = null;
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    winEl._reportState.searchQuery = e.target.value.trim();
                    fetchData();
                }, 300);
            });
        }

        if (actionSelect) {
            actionSelect.addEventListener('change', (e) => {
                winEl._reportState.actionFilter = e.target.value;
                fetchData();
            });
        }

        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                fetchData();
            });
        }

        // Muat data saat window dibuka
        fetchData();
    }

    /**
     * Render antarmuka modul Profil Saya di dalam window.
     * Mengambil data profil dan log aktivitas dari GET /admin/profile,
     * serta mendukung pembaruan nama, email, dan kata sandi via PUT /admin/profile.
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    renderProfileManagement(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        body.innerHTML = `
            <!-- 1. Header Profil Summary Banner -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-primary-subtle text-primary border border-primary-subtle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px; font-size: 18px;">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark" id="profile-display-name-${winEl.id}">Memuat data...</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="profile-badge-role-${winEl.id}">-</span>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" id="profile-badge-level-${winEl.id}">Level -</span>
                        </div>
                        <div class="text-muted small" id="profile-display-email-${winEl.id}">-</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-profile-${winEl.id}" title="Muat Ulang Data Profil">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- 2. Alert Container -->
            <div id="profile-alert-${winEl.id}" class="d-none px-3 pt-2"></div>

            <!-- 3. Konten Utama: Form Profil & Aktivitas -->
            <div class="flex-grow-1 overflow-auto p-3 bg-white">
                <div class="row g-3">
                    <!-- Kolom Kiri: Form Ubah Profil & Kata Sandi -->
                    <div class="col-12 col-lg-7">
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <span class="fw-semibold small text-dark"><i class="fa-regular fa-id-card text-primary me-1"></i> Data Akun Pengguna</span>
                            </div>
                            <div class="card-body p-3">
                                <form id="profile-form-${winEl.id}" autocomplete="off">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold mb-1 text-secondary">Nama Lengkap</label>
                                        <input type="text" class="form-control form-control-sm" id="profile-input-name-${winEl.id}" placeholder="Nama lengkap Anda" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold mb-1 text-secondary">Alamat Email</label>
                                        <input type="email" class="form-control form-control-sm" id="profile-input-email-${winEl.id}" placeholder="email@domain.com" required>
                                        <div class="form-text" style="font-size: 11px;">Digunakan untuk masuk ke sistem dan menerima notifikasi.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold mb-1 text-secondary">Peran & Hak Akses</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-shield-halved"></i></span>
                                            <input type="text" class="form-control bg-light text-muted" id="profile-input-role-${winEl.id}" readonly disabled>
                                        </div>
                                        <div class="form-text text-muted" style="font-size: 11px;">Peran dan level hak akses diatur oleh Administrator sistem.</div>
                                    </div>

                                    <hr class="my-3 text-muted opacity-25">

                                    <div class="mb-2">
                                        <span class="fw-semibold small text-dark d-block mb-1"><i class="fa-solid fa-key text-warning me-1"></i> Keamanan & Kata Sandi</span>
                                        <span class="text-muted" style="font-size: 11px;">Kosongkan jika tidak ingin mengganti password.</span>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-1 text-secondary">Password Saat Ini</label>
                                        <input type="password" class="form-control form-control-sm" id="profile-input-current-pass-${winEl.id}" placeholder="Masukkan password saat ini" autocomplete="current-password">
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold mb-1 text-secondary">Password Baru</label>
                                            <input type="password" class="form-control form-control-sm" id="profile-input-new-pass-${winEl.id}" placeholder="Minimal 6 karakter" autocomplete="new-password">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label small fw-semibold mb-1 text-secondary">Konfirmasi Password Baru</label>
                                            <input type="password" class="form-control form-control-sm" id="profile-input-confirm-pass-${winEl.id}" placeholder="Ulangi password baru" autocomplete="new-password">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-profile-form-${winEl.id}">
                                            <i class="fa-solid fa-rotate-left me-1"></i> Reset
                                        </button>
                                        <button type="submit" class="btn btn-sm btn-primary px-3 shadow-sm" id="btn-submit-profile-${winEl.id}">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Ringkasan Akun & Log Aktivitas -->
                    <div class="col-12 col-lg-5">
                        <!-- Ringkasan Akun -->
                        <div class="card border shadow-sm mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-circle-info text-info me-1"></i> Ringkasan Akun</span>
                            </div>
                            <div class="card-body p-3">
                                <ul class="list-unstyled mb-0" style="font-size: 12px;">
                                    <li class="d-flex justify-content-between py-1 border-bottom">
                                        <span class="text-secondary">ID Pengguna</span>
                                        <span class="fw-bold font-monospace text-dark" id="profile-summary-id-${winEl.id}">-</span>
                                    </li>
                                    <li class="d-flex justify-content-between py-1 border-bottom">
                                        <span class="text-secondary">Status Akun</span>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-circle-check me-1"></i>Aktif</span>
                                    </li>
                                    <li class="d-flex justify-content-between py-1">
                                        <span class="text-secondary">Terdaftar Sejak</span>
                                        <span class="font-monospace text-dark" id="profile-summary-created-${winEl.id}">-</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Aktivitas Terakhir Pengguna -->
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-clock-rotate-left text-secondary me-1"></i> Aktivitas Terakhir Anda</span>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 10px;">5 Terakhir</span>
                            </div>
                            <div class="card-body p-0">
                                <div id="profile-activities-list-${winEl.id}" class="list-group list-group-flush" style="font-size: 12px; max-height: 250px; overflow-y: auto;">
                                    <div class="p-3 text-center text-muted small">
                                        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                                        Memuat riwayat aktivitas...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Footer Bar Status Modul -->
            <div class="py-1 px-3 bg-light border-top d-flex justify-content-between align-items-center text-muted flex-shrink-0" style="font-size: 11px;">
                <span>Modul Profil Pengguna &bull; Terhubung ke <code>/admin/profile</code></span>
                <span id="profile-status-bar-${winEl.id}">Status: Siap</span>
            </div>
        `;

        winEl._profileData = null;

        const displayName = body.querySelector(`#profile-display-name-${winEl.id}`);
        const displayEmail = body.querySelector(`#profile-display-email-${winEl.id}`);
        const badgeRole = body.querySelector(`#profile-badge-role-${winEl.id}`);
        const badgeLevel = body.querySelector(`#profile-badge-level-${winEl.id}`);
        const inputName = body.querySelector(`#profile-input-name-${winEl.id}`);
        const inputEmail = body.querySelector(`#profile-input-email-${winEl.id}`);
        const inputRole = body.querySelector(`#profile-input-role-${winEl.id}`);
        const inputCurrentPass = body.querySelector(`#profile-input-current-pass-${winEl.id}`);
        const inputNewPass = body.querySelector(`#profile-input-new-pass-${winEl.id}`);
        const inputConfirmPass = body.querySelector(`#profile-input-confirm-pass-${winEl.id}`);
        const form = body.querySelector(`#profile-form-${winEl.id}`);
        const btnReset = body.querySelector(`#btn-reset-profile-form-${winEl.id}`);
        const btnSubmit = body.querySelector(`#btn-submit-profile-${winEl.id}`);
        const btnRefresh = body.querySelector(`#btn-refresh-profile-${winEl.id}`);
        const alertContainer = body.querySelector(`#profile-alert-${winEl.id}`);
        const summaryId = body.querySelector(`#profile-summary-id-${winEl.id}`);
        const summaryCreated = body.querySelector(`#profile-summary-created-${winEl.id}`);
        const activitiesList = body.querySelector(`#profile-activities-list-${winEl.id}`);
        const statusBar = body.querySelector(`#profile-status-bar-${winEl.id}`);

        const showAlert = (message, type = 'success') => {
            if (!alertContainer) return;
            const icon = type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation';
            alertContainer.className = 'px-3 pt-2';
            alertContainer.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show py-2 px-3 small mb-0 d-flex align-items-center gap-2" role="alert">
                    <i class="${icon} flex-shrink-0"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close" style="font-size: 9px;"></button>
                </div>
            `;
            if (type === 'success') {
                setTimeout(() => {
                    if (alertContainer) alertContainer.className = 'd-none px-3 pt-2';
                }, 5000);
            }
        };

        const getActivityBadge = (action) => {
            if (action.startsWith('auth.login')) {
                return '<span class="badge bg-success-subtle text-success border border-success-subtle py-0"><i class="fa-solid fa-right-to-bracket me-1"></i>auth.login</span>';
            } else if (action.startsWith('auth.logout')) {
                return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-0"><i class="fa-solid fa-right-from-bracket me-1"></i>auth.logout</span>';
            } else if (action.startsWith('user.profile_update')) {
                return '<span class="badge bg-info-subtle text-info border border-info-subtle py-0"><i class="fa-solid fa-user-pen me-1"></i>update profil</span>';
            } else if (action.startsWith('user.')) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle py-0"><i class="fa-solid fa-user me-1"></i>user</span>';
            } else if (action.startsWith('role.')) {
                return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle py-0"><i class="fa-solid fa-shield me-1"></i>role</span>';
            }
            return `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle py-0">${action}</span>`;
        };

        const renderActivities = (activities = []) => {
            if (!activitiesList) return;
            if (activities.length === 0) {
                activitiesList.innerHTML = `
                    <div class="p-3 text-center text-muted small">
                        <i class="fa-regular fa-clipboard d-block mb-1 fs-5 text-secondary"></i>
                        Belum ada riwayat aktivitas.
                    </div>
                `;
                return;
            }

            activitiesList.innerHTML = activities.map(act => `
                <div class="list-group-item p-2 border-start-0 border-end-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        ${getActivityBadge(act.action)}
                        <span class="text-muted font-monospace" style="font-size: 10px;">${act.created_at}</span>
                    </div>
                    <div class="text-dark small text-truncate" title="${act.description}">${act.description}</div>
                    <div class="text-muted" style="font-size: 10px;">IP: <code>${act.ip_address || '127.0.0.1'}</code></div>
                </div>
            `).join('');
        };

        const fetchProfile = async () => {
            if (statusBar) statusBar.textContent = 'Memuat data profil...';
            try {
                let data = null;
                if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                    data = await window.SyntaxCore.api('/admin/profile');
                } else {
                    const res = await fetch('/admin/profile', {
                        headers: { 'Accept': 'application/json' }
                    });
                    data = await res.json();
                }

                if (data && data.status === 'success' && data.user) {
                    winEl._profileData = data.user;
                    if (displayName) displayName.textContent = data.user.name;
                    if (displayEmail) displayEmail.textContent = data.user.email;
                    if (badgeRole) badgeRole.textContent = data.user.role_name || data.user.role;
                    if (badgeLevel) badgeLevel.textContent = `Level ${data.user.level}`;
                    if (inputName) inputName.value = data.user.name;
                    if (inputEmail) inputEmail.value = data.user.email;
                    if (inputRole) inputRole.value = `${data.user.role_name} (Level ${data.user.level})`;
                    if (summaryId) summaryId.textContent = `#${data.user.id}`;
                    if (summaryCreated) summaryCreated.textContent = data.user.created_at;

                    renderActivities(data.recent_activities || []);
                    if (statusBar) statusBar.textContent = 'Status: Terhubung & Sinkron';
                } else {
                    showAlert(data?.message || 'Gagal memuat profil pengguna.', 'danger');
                    if (statusBar) statusBar.textContent = 'Status: Gagal memuat data';
                }
            } catch (err) {
                showAlert('Gagal menghubungi server: ' + err.message, 'danger');
                if (statusBar) statusBar.textContent = 'Status: Kesalahan koneksi';
            }
        };

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const name = inputName ? inputName.value.trim() : '';
                const email = inputEmail ? inputEmail.value.trim() : '';
                const currentPass = inputCurrentPass ? inputCurrentPass.value : '';
                const newPass = inputNewPass ? inputNewPass.value : '';
                const confirmPass = inputConfirmPass ? inputConfirmPass.value : '';

                if (!name) {
                    showAlert('Nama lengkap tidak boleh kosong.', 'danger');
                    if (inputName) inputName.focus();
                    return;
                }

                if (!email) {
                    showAlert('Alamat email tidak boleh kosong.', 'danger');
                    if (inputEmail) inputEmail.focus();
                    return;
                }

                if (newPass || currentPass || confirmPass) {
                    if (!currentPass) {
                        showAlert('Masukkan password saat ini untuk memverifikasi penggantian kata sandi.', 'warning');
                        if (inputCurrentPass) inputCurrentPass.focus();
                        return;
                    }
                    if (newPass.length < 6) {
                        showAlert('Password baru minimal harus 6 karakter.', 'warning');
                        if (inputNewPass) inputNewPass.focus();
                        return;
                    }
                    if (newPass !== confirmPass) {
                        showAlert('Konfirmasi password baru tidak cocok.', 'warning');
                        if (inputConfirmPass) inputConfirmPass.focus();
                        return;
                    }
                }

                const originalBtnHtml = btnSubmit ? btnSubmit.innerHTML : '';
                if (btnSubmit) {
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Menyimpan...';
                }
                if (statusBar) statusBar.textContent = 'Menyimpan perubahan...';

                const csrfToken = this.getCsrfToken();
                const payload = {
                    name,
                    email,
                    current_password: currentPass,
                    new_password: newPass,
                    confirm_password: confirmPass,
                    _token: csrfToken
                };

                try {
                    let result = null;
                    if (window.SyntaxCore && typeof window.SyntaxCore.api === 'function') {
                        result = await window.SyntaxCore.api('/admin/profile', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(payload)
                        });
                    } else {
                        const res = await fetch('/admin/profile', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: JSON.stringify(payload)
                        });
                        result = await res.json();
                    }

                    if (result && result.status === 'success') {
                        winEl._profileData = result.user;
                        if (displayName) displayName.textContent = result.user.name;
                        if (displayEmail) displayEmail.textContent = result.user.email;
                        this.options.userName = result.user.name;
                        this.options.userEmail = result.user.email;

                        // Perbarui nama di header bar atas (#wd-header-user-info)
                        const headerUserInfo = document.getElementById('wd-header-user-info');
                        if (headerUserInfo) {
                            headerUserInfo.innerHTML = `
                                <span class="text-white fw-medium">${result.user.name}</span>
                                <span class="text-white-50 ms-1" style="font-size: 11px;">(${result.user.role_name || result.user.role})</span>
                            `;
                        }

                        // Kosongkan form input password
                        if (inputCurrentPass) inputCurrentPass.value = '';
                        if (inputNewPass) inputNewPass.value = '';
                        if (inputConfirmPass) inputConfirmPass.value = '';

                        // Tampilkan alert sukses dan toast notifikasi desktop
                        showAlert(result.message || 'Profil Anda berhasil diperbarui.', 'success');
                        this.showToast('Profil Diperbarui', 'Data profil Anda berhasil disimpan.', 'success');

                        // Refresh ulang data profil dan log aktivitas
                        fetchProfile();
                    } else {
                        showAlert(result?.message || 'Gagal memperbarui profil.', 'danger');
                        if (statusBar) statusBar.textContent = 'Status: Gagal menyimpan';
                    }
                } catch (err) {
                    showAlert('Terjadi kesalahan: ' + err.message, 'danger');
                    if (statusBar) statusBar.textContent = 'Status: Terjadi kesalahan';
                } finally {
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalBtnHtml;
                    }
                }
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', () => {
                if (winEl._profileData) {
                    if (inputName) inputName.value = winEl._profileData.name || '';
                    if (inputEmail) inputEmail.value = winEl._profileData.email || '';
                }
                if (inputCurrentPass) inputCurrentPass.value = '';
                if (inputNewPass) inputNewPass.value = '';
                if (inputConfirmPass) inputConfirmPass.value = '';
                if (alertContainer) alertContainer.className = 'd-none px-3 pt-2';
            });
        }

        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                fetchProfile();
            });
        }

        // Muat data profil saat window dibuka
        fetchProfile();
    }

    /**
     * Buka jendela dialog kustomisasi Latar Belakang Desktop (Wallpaper)
     */
    openWallpaperWindow() {
        return this.openWindow({
            id: 'wallpaper-settings',
            title: 'Latar Belakang Desktop',
            icon: 'fa-regular fa-image',
            action: 'open_wallpaper'
        });
    }

    /**
     * Inisialisasi wallpaper desktop dari konfigurasi tersimpan (localStorage)
     */
    initDesktopWallpaper() {
        this._wallpaperConfig = {
            type: 'none',
            url: '',
            gradient: '',
            mode: 'cover',
            dimmer: 0,
            blur: 0
        };

        try {
            const saved = localStorage.getItem('syntaxcore_desktop_wallpaper');
            if (saved) {
                const parsed = JSON.parse(saved);
                this.applyWallpaper(parsed, false);
                return;
            }
        } catch (e) {
            console.warn('[WindowCore] Gagal memuat wallpaper tersimpan:', e);
        }

        this.applyWallpaper(this._wallpaperConfig, false);
    }

    /**
     * Inisialisasi Context Menu klik kanan pada area desktop (#wd-workspace)
     */
    initDesktopContextMenu() {
        const workspace = document.getElementById('wd-workspace');
        const contextMenu = document.getElementById('wd-desktop-context-menu');
        const directInput = document.getElementById('wd-wallpaper-direct-upload');
        if (!workspace || !contextMenu) return;

        workspace.addEventListener('contextmenu', (e) => {
            // Jangan buka context menu desktop jika pengguna mengklik kanan di dalam elemen window, footer, atau header
            if (e.target.closest('.wd-window, #wd-footer, #wd-header, .wd-context-menu')) {
                return;
            }
            e.preventDefault();

            // Hitung posisi aman agar menu tidak meluap ke tepi bawah atau kanan layar
            const menuWidth = 220;
            const menuHeight = 160;
            let left = e.clientX;
            let top = e.clientY;

            if (left + menuWidth > window.innerWidth) {
                left = window.innerWidth - menuWidth - 8;
            }
            if (top + menuHeight > window.innerHeight) {
                top = window.innerHeight - menuHeight - 8;
            }

            contextMenu.style.left = `${left}px`;
            contextMenu.style.top = `${top}px`;
            contextMenu.classList.remove('d-none');
        });

        // Tutup context menu saat pengguna mengklik di luar
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#wd-desktop-context-menu')) {
                contextMenu.classList.add('d-none');
            }
        });

        // Tutup saat menekan tombol Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !contextMenu.classList.contains('d-none')) {
                contextMenu.classList.add('d-none');
            }
        });

        // Event listener item-item context menu
        const btnOpenWp = document.getElementById('wd-ctx-open-wallpaper');
        if (btnOpenWp) {
            btnOpenWp.addEventListener('click', () => {
                contextMenu.classList.add('d-none');
                this.openWallpaperWindow();
            });
        }

        const btnDirectUpload = document.getElementById('wd-ctx-direct-upload');
        if (btnDirectUpload && directInput) {
            btnDirectUpload.addEventListener('click', () => {
                contextMenu.classList.add('d-none');
                directInput.click();
            });
        }

        const btnRefresh = document.getElementById('wd-ctx-refresh');
        if (btnRefresh) {
            btnRefresh.addEventListener('click', () => {
                contextMenu.classList.add('d-none');
                const snapPreview = document.getElementById('wd-snap-preview');
                if (snapPreview) snapPreview.classList.add('d-none');
                this.showToast('Desktop Disegarkan', 'Workspace telah disegarkan.', 'info');
            });
        }

        const btnResetWp = document.getElementById('wd-ctx-reset-wallpaper');
        if (btnResetWp) {
            btnResetWp.addEventListener('click', () => {
                contextMenu.classList.add('d-none');
                this.resetWallpaper();
            });
        }

        // Listener untuk direct upload input
        if (directInput) {
            directInput.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                if (file) {
                    this.uploadAndApplyWallpaper(file);
                    directInput.value = '';
                }
            });
        }
    }

    /**
     * Inisialisasi fitur Drag & Drop berkas gambar langsung ke atas workspace desktop
     */
    initDesktopDragDrop() {
        const workspace = document.getElementById('wd-workspace');
        const overlay = document.getElementById('wd-drag-drop-overlay');
        if (!workspace || !overlay) return;

        let dragCounter = 0;

        workspace.addEventListener('dragenter', (e) => {
            if (e.dataTransfer && e.dataTransfer.types && Array.from(e.dataTransfer.types).includes('Files')) {
                dragCounter++;
                overlay.classList.remove('d-none');
            }
        });

        workspace.addEventListener('dragover', (e) => {
            if (e.dataTransfer && e.dataTransfer.types && Array.from(e.dataTransfer.types).includes('Files')) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
            }
        });

        workspace.addEventListener('dragleave', () => {
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                overlay.classList.add('d-none');
            }
        });

        workspace.addEventListener('drop', (e) => {
            e.preventDefault();
            dragCounter = 0;
            overlay.classList.add('d-none');

            const files = e.dataTransfer?.files;
            if (files && files.length > 0) {
                const file = files[0];
                if (file.type.startsWith('image/')) {
                    this.uploadAndApplyWallpaper(file);
                } else {
                    this.showToast('Format Tidak Didukung', 'Hanya berkas gambar (PNG, JPG, WEBP, GIF, SVG) yang dapat dijadikan wallpaper.', 'warning');
                }
            }
        });
    }

    /**
     * Terapkan konfigurasi wallpaper ke elemen visual #wd-workspace-backdrop dan simpan ke localStorage
     * 
     * @param {Object} config Konfigurasi wallpaper
     * @param {boolean} saveToStorage Apakah disimpan ke localStorage
     */
    applyWallpaper(config, saveToStorage = true) {
        this._wallpaperConfig = Object.assign({
            type: 'none',
            url: '',
            gradient: '',
            mode: 'cover',
            dimmer: 0,
            blur: 0
        }, config);

        const backdrop = document.getElementById('wd-workspace-backdrop');
        const dimmer = document.getElementById('wd-workspace-dimmer');
        const workspace = document.getElementById('wd-workspace');

        if (!backdrop || !workspace) return;

        if (this._wallpaperConfig.type === 'image' && this._wallpaperConfig.url) {
            backdrop.style.backgroundImage = `url("${this._wallpaperConfig.url}")`;
            backdrop.style.backgroundSize = this._wallpaperConfig.mode || 'cover';
            backdrop.style.backgroundRepeat = this._wallpaperConfig.mode === 'repeat' ? 'repeat' : 'no-repeat';
            backdrop.style.backgroundPosition = 'center';
            backdrop.style.filter = this._wallpaperConfig.blur ? `blur(${this._wallpaperConfig.blur}px)` : 'none';
            backdrop.style.transform = this._wallpaperConfig.blur ? 'scale(1.02)' : 'none';
        } else if (this._wallpaperConfig.type === 'gradient' && this._wallpaperConfig.gradient) {
            backdrop.style.backgroundImage = this._wallpaperConfig.gradient;
            backdrop.style.backgroundSize = 'cover';
            backdrop.style.backgroundRepeat = 'no-repeat';
            backdrop.style.backgroundPosition = 'center';
            backdrop.style.filter = 'none';
            backdrop.style.transform = 'none';
        } else {
            backdrop.style.backgroundImage = 'none';
            backdrop.style.filter = 'none';
            backdrop.style.transform = 'none';
            workspace.style.backgroundColor = '#f1f5f9';
        }

        if (dimmer) {
            const dimmerVal = parseInt(this._wallpaperConfig.dimmer, 10) || 0;
            if (dimmerVal > 0) {
                dimmer.classList.remove('d-none');
                dimmer.style.background = `rgba(0, 0, 0, ${dimmerVal / 100})`;
            } else {
                dimmer.classList.add('d-none');
            }
        }

        if (saveToStorage) {
            try {
                localStorage.setItem('syntaxcore_desktop_wallpaper', JSON.stringify(this._wallpaperConfig));
            } catch (e) {
                console.warn('[WindowCore] Gagal menyimpan wallpaper ke localStorage:', e);
            }
        }

        document.dispatchEvent(new CustomEvent('syntaxcore:wallpaper-updated', {
            detail: { config: this._wallpaperConfig }
        }));
    }

    /**
     * Kembalikan background desktop ke tampilan default polos
     */
    async resetWallpaper() {
        this.applyWallpaper({
            type: 'none',
            url: '',
            gradient: '',
            mode: 'cover',
            dimmer: 0,
            blur: 0
        });

        try {
            localStorage.removeItem('syntaxcore_desktop_wallpaper');
        } catch (e) { }

        try {
            await fetch('/admin/wallpaper', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json'
                }
            });
        } catch (e) { }

        this.showToast('Background Direset', 'Latar belakang desktop dikembalikan ke tampilan default.', 'info');
    }

    /**
     * Unggah berkas gambar ke backend dan langsung terapkan ke desktop
     * 
     * @param {File} file Berkas gambar yang diunggah
     */
    async uploadAndApplyWallpaper(file) {
        if (!file || !file.type.startsWith('image/')) {
            this.showToast('Format Tidak Didukung', 'Berkas harus berupa gambar (JPG, PNG, WEBP, GIF, SVG).', 'danger');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            this.showToast('Ukuran Terlalu Besar', 'Ukuran gambar maksimal adalah 10MB.', 'danger');
            return;
        }

        // Preview lokal instan dengan FileReader agar respon langsung terasa instan
        const reader = new FileReader();
        reader.onload = (e) => {
            this.applyWallpaper({
                type: 'image',
                url: e.target.result,
                filename: file.name,
                mode: this._wallpaperConfig.mode || 'cover',
                dimmer: this._wallpaperConfig.dimmer || 0,
                blur: this._wallpaperConfig.blur || 0
            });
        };
        reader.readAsDataURL(file);

        this.showToast('Mengunggah Wallpaper...', 'Sedang mengunggah gambar ke server...', 'info');

        try {
            const formData = new FormData();
            formData.append('wallpaper', file);
            formData.append('_token', this.getCsrfToken());

            const res = await fetch('/admin/wallpaper', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await res.json();
            if (data && data.status === 'success' && data.url) {
                this.applyWallpaper({
                    type: 'image',
                    url: data.url,
                    filename: data.filename || file.name,
                    mode: this._wallpaperConfig.mode || 'cover',
                    dimmer: this._wallpaperConfig.dimmer || 0,
                    blur: this._wallpaperConfig.blur || 0
                });
                this.showToast('Background Diperbarui', 'Gambar berhasil diunggah dan dijadikan wallpaper desktop.', 'success');
            } else {
                this.showToast('Peringatan Upload', data?.message || 'Gambar diterapkan secara lokal namun gagal disimpan permanen di server.', 'warning');
            }
        } catch (err) {
            this.showToast('Koneksi Gagal', 'Gagal mengunggah ke server: ' + err.message, 'warning');
        }
    }

    /**
     * Render antarmuka jendela Pengaturan Latar Belakang Desktop di dalam window
     * 
     * @param {HTMLElement} winEl Elemen window
     */
    renderWallpaperManagement(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        body.innerHTML = `
            <!-- 1. Header Toolbar -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-regular fa-image text-primary"></i>
                    <span class="fw-semibold small text-dark">Kustomisasi Background Desktop Workspace</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="wp-btn-reset-${winEl.id}" style="font-size: 11px;" title="Hapus wallpaper dan kembalikan ke standar">
                    <i class="fa-regular fa-trash-can me-1"></i> Reset Default
                </button>
            </div>

            <!-- 2. Konten Utama -->
            <div class="flex-grow-1 overflow-auto p-3 bg-white">
                <div class="row g-3">
                    <!-- Kolom Kiri: Pratinjau Realtime & Penyesuaian Tampilan -->
                    <div class="col-12 col-md-5">
                        <!-- Pratinjau Layar Monitor Miniatur -->
                        <div class="card border shadow-sm mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-display me-1 text-secondary"></i> Pratinjau Realtime</span>
                            </div>
                            <div class="card-body p-3 text-center">
                                <div class="p-1 bg-dark rounded shadow-sm mx-auto" style="max-width: 250px;">
                                    <div id="wp-preview-screen-${winEl.id}" class="rounded overflow-hidden position-relative" style="height: 135px; background-color: #f1f5f9; background-size: cover; background-position: center; transition: all 0.2s ease;">
                                        <div id="wp-preview-dimmer-${winEl.id}" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.15);"></div>
                                        <!-- Ilustrasi Window Mini di Pratinjau -->
                                        <div class="position-absolute bg-white rounded shadow-sm border p-1" style="width: 70px; height: 42px; font-size: 7px; top: 12px; left: 14px; opacity: 0.9;">
                                            <div class="bg-light px-1 py-0 mb-1 border-bottom text-truncate fw-bold">Window 1</div>
                                            <div class="bg-light-subtle w-75 py-0 mb-1"></div>
                                        </div>
                                        <div class="position-absolute bg-white rounded shadow-sm border p-1" style="width: 75px; height: 48px; font-size: 7px; top: 35px; right: 18px; opacity: 0.95;">
                                            <div class="bg-primary-subtle text-primary px-1 py-0 mb-1 border-bottom text-truncate fw-bold">Window 2</div>
                                            <div class="bg-light-subtle w-100 py-0 mb-1"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 text-muted text-truncate" style="font-size: 11px;" id="wp-preview-caption-${winEl.id}">
                                    Tampilan Default
                                </div>
                            </div>
                        </div>

                        <!-- Pengaturan Penyesuaian -->
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-sliders me-1 text-secondary"></i> Penyesuaian Tampilan</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Mode Ukuran Gambar</label>
                                    <select class="form-select form-select-sm" id="wp-select-mode-${winEl.id}">
                                        <option value="cover">Cover (Penuh Layar - Rekomendasi)</option>
                                        <option value="contain">Contain (Pas di Dalam Layar)</option>
                                        <option value="center">Center (Tengah Asli)</option>
                                        <option value="repeat">Repeat (Ubin Berulang)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-semibold text-secondary mb-0">Redupkan Background</label>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace" id="wp-dimmer-val-${winEl.id}">0%</span>
                                    </div>
                                    <input type="range" class="form-range" id="wp-slider-dimmer-${winEl.id}" min="0" max="60" step="5" value="0">
                                    <div class="form-text text-muted" style="font-size: 10px;">Membantu teks & ikon window tetap terbaca jelas.</div>
                                </div>
                                <div class="mb-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-semibold text-secondary mb-0">Efek Buram (Blur)</label>
                                        <span class="badge bg-secondary-subtle text-secondary font-monospace" id="wp-blur-val-${winEl.id}">0px</span>
                                    </div>
                                    <input type="range" class="form-range" id="wp-slider-blur-${winEl.id}" min="0" max="10" step="1" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Area Upload & Koleksi Preset -->
                    <div class="col-12 col-md-7">
                        <!-- Dropzone Upload Gambar -->
                        <div class="card border shadow-sm mb-3">
                            <div class="card-header bg-light py-2 px-3 border-bottom">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-cloud-arrow-up me-1 text-primary"></i> Unggah Gambar Sendiri</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="wd-dropzone" id="wp-dropzone-${winEl.id}">
                                    <i class="fa-solid fa-arrow-up-from-bracket fs-3 text-primary mb-2 d-block"></i>
                                    <div class="fw-semibold text-dark small mb-1">Pilih berkas gambar atau seret langsung ke sini</div>
                                    <div class="text-muted" style="font-size: 11px;">Mendukung format PNG, JPG, WEBP, GIF, SVG (Maks. 10MB)</div>
                                    <input type="file" id="wp-file-input-${winEl.id}" accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml" class="d-none">
                                </div>
                                <div id="wp-upload-progress-${winEl.id}" class="mt-2 d-none">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%;"></div>
                                    </div>
                                    <div class="text-center text-muted small mt-1" style="font-size: 11px;">Mengunggah gambar ke server...</div>
                                </div>
                            </div>
                        </div>

                        <!-- Pilihan Preset Modern -->
                        <div class="card border shadow-sm">
                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
                                <span class="fw-semibold small text-dark"><i class="fa-solid fa-palette me-1 text-info"></i> Koleksi Preset Modern</span>
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 10px;">Pilihan Cepat</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2" id="wp-preset-container-${winEl.id}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Footer Bar Status Modul -->
            <div class="py-1 px-3 bg-light border-top d-flex justify-content-between align-items-center text-muted flex-shrink-0" style="font-size: 11px;">
                <span>Latar Belakang Desktop &bull; Pengaturan tersimpan secara realtime</span>
                <span id="wp-footer-status-${winEl.id}">Status: Terhubung</span>
            </div>
        `;

        const previewScreen = body.querySelector(`#wp-preview-screen-${winEl.id}`);
        const previewDimmer = body.querySelector(`#wp-preview-dimmer-${winEl.id}`);
        const previewCaption = body.querySelector(`#wp-preview-caption-${winEl.id}`);
        const selectMode = body.querySelector(`#wp-select-mode-${winEl.id}`);
        const sliderDimmer = body.querySelector(`#wp-slider-dimmer-${winEl.id}`);
        const valDimmer = body.querySelector(`#wp-dimmer-val-${winEl.id}`);
        const sliderBlur = body.querySelector(`#wp-slider-blur-${winEl.id}`);
        const valBlur = body.querySelector(`#wp-blur-val-${winEl.id}`);
        const dropzone = body.querySelector(`#wp-dropzone-${winEl.id}`);
        const fileInput = body.querySelector(`#wp-file-input-${winEl.id}`);
        const uploadProgress = body.querySelector(`#wp-upload-progress-${winEl.id}`);
        const presetContainer = body.querySelector(`#wp-preset-container-${winEl.id}`);
        const btnReset = body.querySelector(`#wp-btn-reset-${winEl.id}`);

        const presets = [
            {
                id: 'preset-cyber',
                name: 'Deep Cyber Mesh',
                gradient: 'linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%)',
                desc: 'Dark Minimalist'
            },
            {
                id: 'preset-aurora',
                name: 'Neon Aurora',
                gradient: 'linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4c1d95 70%, #831843 100%)',
                desc: 'Vibrant Violet'
            },
            {
                id: 'preset-nordic',
                name: 'Nordic Calm',
                gradient: 'linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%)',
                desc: 'Cool Slate'
            },
            {
                id: 'preset-sunset',
                name: 'Sunset Glow',
                gradient: 'linear-gradient(135deg, #2b1055 0%, #7597de 100%)',
                desc: 'Warm Twilight'
            },
            {
                id: 'preset-emerald',
                name: 'Emerald Forest',
                gradient: 'linear-gradient(135deg, #064e3b 0%, #047857 50%, #0f172a 100%)',
                desc: 'Calm Green'
            },
            {
                id: 'preset-clean',
                name: 'Slate Polos (Default)',
                gradient: '',
                desc: 'Sistem Default'
            }
        ];

        const updateMiniPreview = (cfg) => {
            if (!previewScreen) return;
            if (cfg.type === 'image' && cfg.url) {
                previewScreen.style.backgroundImage = `url("${cfg.url}")`;
                previewScreen.style.backgroundSize = cfg.mode || 'cover';
                previewScreen.style.backgroundRepeat = cfg.mode === 'repeat' ? 'repeat' : 'no-repeat';
                previewScreen.style.backgroundPosition = 'center';
                previewScreen.style.filter = cfg.blur ? `blur(${cfg.blur}px)` : 'none';
                if (previewCaption) previewCaption.textContent = cfg.filename ? `Kustom: ${cfg.filename}` : 'Gambar Kustom';
            } else if (cfg.type === 'gradient' && cfg.gradient) {
                previewScreen.style.backgroundImage = cfg.gradient;
                previewScreen.style.backgroundSize = 'cover';
                previewScreen.style.filter = 'none';
                if (previewCaption) previewCaption.textContent = `Preset: ${cfg.name || 'Gradient'}`;
            } else {
                previewScreen.style.backgroundImage = 'none';
                previewScreen.style.backgroundColor = '#f1f5f9';
                previewScreen.style.filter = 'none';
                if (previewCaption) previewCaption.textContent = 'Default Workspace';
            }

            if (previewDimmer) {
                const dim = parseInt(cfg.dimmer, 10) || 0;
                if (dim > 0) {
                    previewDimmer.classList.remove('d-none');
                    previewDimmer.style.background = `rgba(0,0,0,${dim / 100})`;
                } else {
                    previewDimmer.classList.add('d-none');
                }
            }
        };

        const syncControlsFromConfig = () => {
            const cfg = this._wallpaperConfig || {};
            if (selectMode) selectMode.value = cfg.mode || 'cover';
            if (sliderDimmer) sliderDimmer.value = cfg.dimmer || 0;
            if (valDimmer) valDimmer.textContent = `${cfg.dimmer || 0}%`;
            if (sliderBlur) sliderBlur.value = cfg.blur || 0;
            if (valBlur) valBlur.textContent = `${cfg.blur || 0}px`;
            updateMiniPreview(cfg);
        };

        // Render kartu-kartu preset
        if (presetContainer) {
            presetContainer.innerHTML = presets.map(p => `
                <div class="col-6 col-sm-4">
                    <div class="wd-wallpaper-card card border shadow-sm p-1" data-preset-id="${p.id}" style="cursor: pointer;">
                        <div class="rounded mb-1" style="height: 52px; background: ${p.gradient || '#f1f5f9'}; border: 1px solid rgba(0,0,0,0.08);"></div>
                        <div class="fw-semibold text-dark text-truncate" style="font-size: 11px;">${p.name}</div>
                        <div class="text-muted text-truncate" style="font-size: 9px;">${p.desc}</div>
                    </div>
                </div>
            `).join('');

            presetContainer.querySelectorAll('.wd-wallpaper-card').forEach(card => {
                card.addEventListener('click', () => {
                    const presetId = card.getAttribute('data-preset-id');
                    const preset = presets.find(p => p.id === presetId);
                    if (preset) {
                        if (preset.gradient) {
                            this.applyWallpaper({
                                type: 'gradient',
                                gradient: preset.gradient,
                                name: preset.name,
                                mode: 'cover',
                                dimmer: parseInt(sliderDimmer?.value, 10) || 0,
                                blur: 0
                            });
                        } else {
                            this.applyWallpaper({
                                type: 'none',
                                url: '',
                                gradient: '',
                                mode: 'cover',
                                dimmer: 0,
                                blur: 0
                            });
                        }
                        syncControlsFromConfig();
                        this.showToast('Wallpaper Diterapkan', `Preset "${preset.name}" berhasil diterapkan.`, 'success');
                    }
                });
            });
        }

        // Dropzone interactions
        if (dropzone && fileInput) {
            dropzone.addEventListener('click', () => fileInput.click());

            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.classList.add('dragover');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('dragover');
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.classList.remove('dragover');
                const files = e.dataTransfer?.files;
                if (files && files.length > 0) {
                    this.uploadAndApplyWallpaper(files[0]);
                }
            });

            fileInput.addEventListener('change', (e) => {
                const file = e.target.files?.[0];
                if (file) {
                    if (uploadProgress) uploadProgress.classList.remove('d-none');
                    this.uploadAndApplyWallpaper(file).finally(() => {
                        if (uploadProgress) uploadProgress.classList.add('d-none');
                        fileInput.value = '';
                    });
                }
            });
        }

        // Controls input listeners
        if (selectMode) {
            selectMode.addEventListener('change', (e) => {
                this.applyWallpaper(Object.assign({}, this._wallpaperConfig, {
                    mode: e.target.value
                }));
                syncControlsFromConfig();
            });
        }

        if (sliderDimmer) {
            sliderDimmer.addEventListener('input', (e) => {
                const val = parseInt(e.target.value, 10);
                if (valDimmer) valDimmer.textContent = `${val}%`;
                this.applyWallpaper(Object.assign({}, this._wallpaperConfig, {
                    dimmer: val
                }));
                syncControlsFromConfig();
            });
        }

        if (sliderBlur) {
            sliderBlur.addEventListener('input', (e) => {
                const val = parseInt(e.target.value, 10);
                if (valBlur) valBlur.textContent = `${val}px`;
                this.applyWallpaper(Object.assign({}, this._wallpaperConfig, {
                    blur: val
                }));
                syncControlsFromConfig();
            });
        }

        if (btnReset) {
            btnReset.addEventListener('click', () => {
                this.resetWallpaper();
                syncControlsFromConfig();
            });
        }

        // Sinkronisasi awal
        syncControlsFromConfig();

        // Listener event update agar preview tetap sinkron jika diubah dari tempat lain
        const updateListener = () => syncControlsFromConfig();
        document.addEventListener('syntaxcore:wallpaper-updated', updateListener);
        winEl.addEventListener('DOMNodeRemoved', () => {
            document.removeEventListener('syntaxcore:wallpaper-updated', updateListener);
        });
    }

    // =========================================================================
    // MODUL CMS (CONTENT MANAGEMENT SYSTEM)
    // =========================================================================

    /**
     * 1. Manajemen Berita & Artikel (open_cms_news)
     */
    renderCmsNews(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        const winId = winEl.id;

        body.innerHTML = `
            <!-- Toolbar -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 480px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="cms-news-search-${winId}" placeholder="Cari judul atau ringkasan...">
                    </div>
                    <select class="form-select form-select-sm" id="cms-news-cat-filter-${winId}" style="max-width: 150px;">
                        <option value="">Semua Kategori</option>
                    </select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="cms-news-count-${winId}">Memuat...</span>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-news-${winId}">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Berita</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-news-${winId}" title="Muat Ulang Data">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="cms-news-alert-${winId}" class="d-none px-3 pt-2"></div>

            <!-- Form Drawer / Panel Editor Berita -->
            <div id="cms-news-form-panel-${winId}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0 overflow-y-auto" style="max-height: 70%;">
                <form id="cms-news-form-${winId}" autocomplete="off">
                    <input type="hidden" id="cms-news-form-id-${winId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark" id="cms-news-form-title-${winId}">Tambah Berita Baru</h6>
                        <button type="button" class="btn-close" id="btn-cancel-news-${winId}"></button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold mb-1">Judul Berita</label>
                            <input type="text" class="form-control form-control-sm" id="cms-news-input-title-${winId}" placeholder="Judul artikel atau berita..." required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Slug URL</label>
                            <input type="text" class="form-control form-control-sm" id="cms-news-input-slug-${winId}" placeholder="slug-otomatis">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Kategori</label>
                            <select class="form-select form-select-sm" id="cms-news-input-category-${winId}">
                                <option value="">Pilih Kategori...</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Tags (pisahkan koma)</label>
                            <input type="text" class="form-control form-control-sm" id="cms-news-input-tags-${winId}" placeholder="Contoh: SyntaxCore, Tutorial">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold mb-1">Status Publikasi</label>
                            <select class="form-select form-select-sm" id="cms-news-input-status-${winId}">
                                <option value="published">Diterbitkan (Published)</option>
                                <option value="draft">Draf (Draft)</option>
                                <option value="archived">Diarsipkan (Archived)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Featured Image (Thumbnail)</label>
                            <div class="input-group input-group-sm">
                                <input type="file" class="form-control" id="cms-news-input-file-${winId}" accept="image/*">
                                <input type="hidden" id="cms-news-input-img-url-${winId}">
                            </div>
                            <div id="cms-news-img-preview-${winId}" class="mt-1 d-none">
                                <img src="" alt="Preview" class="rounded border" style="height: 50px; object-fit: cover;">
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cms-news-input-allow-comments-${winId}" checked>
                                <label class="form-check-label small fw-semibold" for="cms-news-input-allow-comments-${winId}">Izinkan Komentar pada Berita ini</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Ringkasan / Excerpt</label>
                            <textarea class="form-control form-control-sm" id="cms-news-input-summary-${winId}" rows="2" placeholder="Ringkasan singkat yang memikat..."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold mb-1">Konten Lengkap (HTML / Teks)</label>
                            <textarea class="form-control form-control-sm font-monospace" id="cms-news-input-content-${winId}" rows="6" placeholder="Tulis konten artikel di sini..." required></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-news-bottom-${winId}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3" id="btn-save-news-${winId}">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Berita
                        </button>
                    </div>
                </form>
            </div>

            <!-- News Table List -->
            <div class="table-responsive flex-grow-1 overflow-y-auto">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom" style="z-index: 1;">
                        <tr>
                            <th style="width: 40px;" class="text-center">ID</th>
                            <th>Judul Berita</th>
                            <th style="width: 140px;">Kategori</th>
                            <th style="width: 110px;" class="text-center">Status</th>
                            <th style="width: 80px;" class="text-center">Diskusi</th>
                            <th style="width: 80px;" class="text-center">Views</th>
                            <th style="width: 120px;" class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cms-news-tbody-${winId}">
                        <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data berita...</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        const csrfToken = this.getCsrfToken();
        const searchInput = body.querySelector(`#cms-news-search-${winId}`);
        const catFilter = body.querySelector(`#cms-news-cat-filter-${winId}`);
        const countBadge = body.querySelector(`#cms-news-count-${winId}`);
        const tbody = body.querySelector(`#cms-news-tbody-${winId}`);
        const alertBox = body.querySelector(`#cms-news-alert-${winId}`);
        const formPanel = body.querySelector(`#cms-news-form-panel-${winId}`);
        const form = body.querySelector(`#cms-news-form-${winId}`);
        const btnAdd = body.querySelector(`#btn-add-news-${winId}`);
        const btnRefresh = body.querySelector(`#btn-refresh-news-${winId}`);
        const btnCancel = body.querySelector(`#btn-cancel-news-${winId}`);
        const btnCancelBottom = body.querySelector(`#btn-cancel-news-bottom-${winId}`);
        const fileInput = body.querySelector(`#cms-news-input-file-${winId}`);
        const imgUrlInput = body.querySelector(`#cms-news-input-img-url-${winId}`);
        const imgPreview = body.querySelector(`#cms-news-img-preview-${winId}`);
        const inputTitle = body.querySelector(`#cms-news-input-title-${winId}`);
        const inputSlug = body.querySelector(`#cms-news-input-slug-${winId}`);
        const inputCat = body.querySelector(`#cms-news-input-category-${winId}`);

        let categoriesList = [];

        const showAlert = (msg, type = 'success') => {
            alertBox.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-2 mb-0 py-2 small`;
            alertBox.innerHTML = `${msg} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
            setTimeout(() => alertBox.classList.add('d-none'), 5000);
        };

        const loadCategories = () => {
            fetch('/admin/cms/categories', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        categoriesList = res.data || [];
                        let options = '<option value="">Semua Kategori</option>';
                        let formOptions = '<option value="">Pilih Kategori...</option>';
                        categoriesList.forEach(c => {
                            options += `<option value="${c.id}">${c.name}</option>`;
                            formOptions += `<option value="${c.id}">${c.name}</option>`;
                        });
                        catFilter.innerHTML = options;
                        inputCat.innerHTML = formOptions;
                    }
                })
                .catch(() => { });
        };

        const loadNews = () => {
            const query = searchInput.value.trim();
            const catId = catFilter.value;
            let url = '/admin/cms/news?limit=50';
            if (query) url += `&search=${encodeURIComponent(query)}`;
            if (catId) url += `&category_id=${encodeURIComponent(catId)}`;

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const list = res.data || [];
                        countBadge.textContent = `${list.length} Berita`;
                        if (list.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada berita ditemukan.</td></tr>`;
                            return;
                        }

                        tbody.innerHTML = list.map(item => `
                        <tr>
                            <td class="text-center text-muted font-monospace small">${item.id}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    ${item.featured_image ? `<img src="${item.featured_image}" class="rounded border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover;">` : `<div class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center flex-shrink-0 text-muted" style="width: 38px; height: 38px;"><i class="fa-solid fa-newspaper small"></i></div>`}
                                    <div class="overflow-hidden">
                                        <div class="fw-semibold text-truncate text-dark" style="max-width: 300px;">${item.title}</div>
                                        <div class="text-muted small font-monospace text-truncate" style="max-width: 300px;">/${item.slug}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background-color: ${item.category_color || '#6c757d'};">${item.category_name || 'Umum'}</span>
                            </td>
                            <td class="text-center">
                                <span class="badge ${item.status === 'published' ? 'bg-success-subtle text-success border border-success-subtle' : (item.status === 'draft' ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-secondary-subtle text-secondary border')}">
                                    ${item.status}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    <i class="fa-solid fa-comments text-muted me-1"></i>${item.comments_count || 0}
                                </span>
                            </td>
                            <td class="text-center font-monospace text-muted small">${item.views_count || 0}</td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary btn-edit-news" data-id="${item.id}" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="btn btn-outline-danger btn-delete-news" data-id="${item.id}" data-title="${item.title}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `).join('');

                        // Attach edit & delete events
                        tbody.querySelectorAll('.btn-edit-news').forEach(btn => {
                            btn.addEventListener('click', () => editNews(btn.getAttribute('data-id')));
                        });
                        tbody.querySelectorAll('.btn-delete-news').forEach(btn => {
                            btn.addEventListener('click', () => deleteNews(btn.getAttribute('data-id'), btn.getAttribute('data-title')));
                        });
                    }
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data berita: ${err.message}</td></tr>`;
                });
        };

        // File upload handling
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;

            const fd = new FormData();
            fd.append('image', file);
            fd.append('_token', csrfToken);

            fetch('/admin/cms/news/upload-image', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: fd
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        imgUrlInput.value = res.url;
                        imgPreview.classList.remove('d-none');
                        imgPreview.querySelector('img').src = res.url;
                        showAlert('Thumbnail berhasil diunggah.', 'success');
                    } else {
                        showAlert(res.message || 'Gagal mengunggah gambar', 'danger');
                    }
                })
                .catch(err => showAlert(err.message, 'danger'));
        });

        // Auto slug generator
        inputTitle.addEventListener('input', () => {
            const formId = body.querySelector(`#cms-news-form-id-${winId}`).value;
            if (!formId) {
                inputSlug.value = inputTitle.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });

        const resetForm = () => {
            form.reset();
            body.querySelector(`#cms-news-form-id-${winId}`).value = '';
            body.querySelector(`#cms-news-form-title-${winId}`).textContent = 'Tambah Berita Baru';
            imgUrlInput.value = '';
            imgPreview.classList.add('d-none');
            this.setRichEditorContent(`cms-news-input-content-${winId}`, '');
            formPanel.classList.add('d-none');
        };

        const editNews = (id) => {
            fetch(`/admin/cms/news/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        body.querySelector(`#cms-news-form-id-${winId}`).value = d.id;
                        body.querySelector(`#cms-news-form-title-${winId}`).textContent = `Edit Berita: ${d.title}`;
                        inputTitle.value = d.title;
                        inputSlug.value = d.slug;
                        inputCat.value = d.category_id || '';
                        body.querySelector(`#cms-news-input-tags-${winId}`).value = (d.tags || []).map(t => t.name).join(', ');
                        body.querySelector(`#cms-news-input-status-${winId}`).value = d.status;
                        body.querySelector(`#cms-news-input-allow-comments-${winId}`).checked = (parseInt(d.allow_comments, 10) === 1);
                        body.querySelector(`#cms-news-input-summary-${winId}`).value = d.summary || '';
                        this.initRichEditor(`cms-news-input-content-${winId}`);
                        this.setRichEditorContent(`cms-news-input-content-${winId}`, d.content || '');
                        imgUrlInput.value = d.featured_image || '';

                        if (d.featured_image) {
                            imgPreview.classList.remove('d-none');
                            imgPreview.querySelector('img').src = d.featured_image;
                        } else {
                            imgPreview.classList.add('d-none');
                        }

                        formPanel.classList.remove('d-none');
                        formPanel.scrollIntoView({ behavior: 'smooth' });
                    }
                });
        };

        const deleteNews = (id, title) => {
            if (!confirm(`Hapus berita "${title}"? Tindakan ini tidak dapat dibatalkan.`)) return;

            fetch(`/admin/cms/news/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        loadNews();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                })
                .catch(err => showAlert(err.message, 'danger'));
        };

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = body.querySelector(`#cms-news-form-id-${winId}`).value;
            const payload = {
                title: inputTitle.value.trim(),
                slug: inputSlug.value.trim(),
                category_id: inputCat.value || null,
                tags: body.querySelector(`#cms-news-input-tags-${winId}`).value.trim(),
                status: body.querySelector(`#cms-news-input-status-${winId}`).value,
                allow_comments: body.querySelector(`#cms-news-input-allow-comments-${winId}`).checked ? 1 : 0,
                featured_image: imgUrlInput.value || null,
                summary: body.querySelector(`#cms-news-input-summary-${winId}`).value.trim(),
                content: this.getRichEditorContent(`cms-news-input-content-${winId}`).trim(),
                _token: csrfToken
            };

            const method = id ? 'PUT' : 'POST';
            const url = id ? `/admin/cms/news/${id}` : '/admin/cms/news';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        resetForm();
                        loadNews();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                })
                .catch(err => showAlert(err.message, 'danger'));
        });

        btnAdd.addEventListener('click', () => {
            resetForm();
            formPanel.classList.remove('d-none');
            this.initRichEditor(`cms-news-input-content-${winId}`);
        });
        btnCancel.addEventListener('click', resetForm);
        btnCancelBottom.addEventListener('click', resetForm);
        btnRefresh.addEventListener('click', () => { loadNews(); loadCategories(); });
        searchInput.addEventListener('input', () => loadNews());
        catFilter.addEventListener('change', () => loadNews());

        // Inisialisasi awal
        loadCategories();
        loadNews();
    }

    /**
     * 2. Manajemen Halaman & Pengaturan Komentar (open_cms_pages)
     */
    renderCmsPages(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        const winId = winEl.id;

        body.innerHTML = `
            <!-- Toolbar -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 360px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="cms-pages-search-${winId}" placeholder="Cari halaman atau slug...">
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" id="cms-pages-count-${winId}">Memuat...</span>
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-page-${winId}">
                        <i class="fa-solid fa-plus"></i>
                        <span>Buat Halaman</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-page-${winId}" title="Muat Ulang Data">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="cms-pages-alert-${winId}" class="d-none px-3 pt-2"></div>

            <!-- Form Drawer / Panel Editor Halaman -->
            <div id="cms-pages-form-panel-${winId}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0 overflow-y-auto" style="max-height: 70%;">
                <form id="cms-pages-form-${winId}" autocomplete="off">
                    <input type="hidden" id="cms-pages-form-id-${winId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark" id="cms-pages-form-title-${winId}">Buat Halaman Baru</h6>
                        <button type="button" class="btn-close" id="btn-cancel-page-${winId}"></button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-7">
                            <label class="form-label small fw-semibold mb-1">Judul Halaman</label>
                            <input type="text" class="form-control form-control-sm" id="cms-pages-input-title-${winId}" placeholder="Contoh: Tentang Kami" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-semibold mb-1">Slug URL</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white text-muted">/</span>
                                <input type="text" class="form-control" id="cms-pages-input-slug-${winId}" placeholder="tentang-kami" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Tipe Halaman</label>
                            <select class="form-select form-select-sm" id="cms-pages-input-type-${winId}">
                                <option value="standard">Standard (Konten Bebas)</option>
                                <option value="news_index">News Feed (Daftar Berita)</option>
                                <option value="news_single">News Reader (Detail Berita)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Tata Letak Publik (Layout)</label>
                            <select class="form-select form-select-sm" id="cms-pages-input-layout-${winId}">
                                <option value="default">Default Container (Kartu)</option>
                                <option value="fullwidth">Full-Width Canvas (Penuh)</option>
                                <option value="sidebar">Sidebar Navigation (2 Kolom)</option>
                                <option value="blank">Blank Clean (Minimalis)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Status Publikasi</label>
                            <select class="form-select form-select-sm" id="cms-pages-input-status-${winId}">
                                <option value="published">Diterbitkan (Published)</option>
                                <option value="draft">Draf (Draft)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Urutan (Sort Order)</label>
                            <input type="number" class="form-control form-control-sm" id="cms-pages-input-sort-${winId}" value="0">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Meta Title SEO</label>
                            <input type="text" class="form-control form-control-sm" id="cms-pages-input-meta-title-${winId}" placeholder="Judul pada tab browser...">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Meta Description</label>
                            <input type="text" class="form-control form-control-sm" id="cms-pages-input-meta-desc-${winId}" placeholder="Deskripsi untuk mesin pencari...">
                        </div>

                        <!-- PANEL KUSTOMISASI KOMENTAR KHUSUS HALAMAN -->
                        <div class="col-12">
                            <div class="card border border-primary-subtle bg-primary-subtle bg-opacity-10 p-3 mt-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-sliders text-primary"></i>
                                    <h6 class="fw-bold mb-0 text-primary small">Kustomisasi Tampilan Komentar Khusus Halaman</h6>
                                </div>
                                <p class="text-muted mb-3" style="font-size: 11.5px;">
                                    Data komentar tetap melekat pada entitas Berita, namun halaman ini (khususnya tipe <code>news_single</code>) menentukan gaya penyajian dan aturan interaksi diskusinya.
                                </p>

                                <div class="row g-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Gaya Tata Letak (Style)</label>
                                        <select class="form-select form-select-sm" id="cms-pages-comment-style-${winId}">
                                            <option value="cards">Cards (Kartu Berjarak Modern)</option>
                                            <option value="threaded">Threaded (Hierarki Balasan Bersarang)</option>
                                            <option value="minimal">Minimal (Baris Padat Hemat Ruang)</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small fw-semibold mb-1">Jumlah Komentar Per Halaman</label>
                                        <input type="number" class="form-control form-control-sm" id="cms-pages-comment-perpage-${winId}" value="15" min="3" max="100">
                                    </div>
                                    <div class="col-12 col-md-4 d-flex flex-column justify-content-center gap-2 pt-2">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="cms-pages-comment-enabled-${winId}" checked>
                                            <label class="form-check-label small" for="cms-pages-comment-enabled-${winId}">Aktifkan Kolom Komentar</label>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="cms-pages-comment-guests-${winId}" checked>
                                            <label class="form-check-label small" for="cms-pages-comment-guests-${winId}">Izinkan Komentar Pengunjung Tamu</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                                <label class="form-label small fw-semibold mb-0">Konten Halaman (HTML / Teks)</label>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="small text-muted me-1"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i>Template Konten:</span>
                                    <select class="form-select form-select-sm py-0 px-2" id="cms-pages-blueprint-select-${winId}" style="width: auto; font-size: 12px;">
                                        <option value="">-- Pilih Blueprint --</option>
                                        <option value="about">Tentang Kami (About Us)</option>
                                        <option value="services">Layanan &amp; Fitur (Services)</option>
                                        <option value="faq">Tanya Jawab (FAQ)</option>
                                        <option value="contact">Hubungi Kami (Contact Us)</option>
                                        <option value="policy">Kebijakan Privasi (Privacy Policy)</option>
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btn-apply-blueprint-${winId}" style="font-size: 12px;" title="Terapkan blueprint ke editor">
                                        <i class="fa-solid fa-file-import me-1"></i>Terapkan
                                    </button>
                                </div>
                            </div>
                            <textarea class="form-control form-control-sm font-monospace" id="cms-pages-input-content-${winId}" rows="6" placeholder="Tulis konten halaman di sini..."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-page-bottom-${winId}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3" id="btn-save-page-${winId}">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Halaman
                        </button>
                    </div>
                </form>
            </div>

            <!-- Pages Table List -->
            <div class="table-responsive flex-grow-1 overflow-y-auto">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom" style="z-index: 1;">
                        <tr>
                            <th style="width: 40px;" class="text-center">ID</th>
                            <th>Judul & Path URL</th>
                            <th style="width: 120px;">Tipe Halaman</th>
                            <th style="width: 110px;">Layout Publik</th>
                            <th style="width: 120px;">Kustom Komentar</th>
                            <th style="width: 80px;" class="text-center">Status</th>
                            <th style="width: 110px;" class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cms-pages-tbody-${winId}">
                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat daftar halaman...</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        const csrfToken = this.getCsrfToken();
        const searchInput = body.querySelector(`#cms-pages-search-${winId}`);
        const countBadge = body.querySelector(`#cms-pages-count-${winId}`);
        const tbody = body.querySelector(`#cms-pages-tbody-${winId}`);
        const alertBox = body.querySelector(`#cms-pages-alert-${winId}`);
        const formPanel = body.querySelector(`#cms-pages-form-panel-${winId}`);
        const form = body.querySelector(`#cms-pages-form-${winId}`);
        const btnAdd = body.querySelector(`#btn-add-page-${winId}`);
        const btnRefresh = body.querySelector(`#btn-refresh-page-${winId}`);
        const btnCancel = body.querySelector(`#btn-cancel-page-${winId}`);
        const btnCancelBottom = body.querySelector(`#btn-cancel-page-bottom-${winId}`);
        const inputTitle = body.querySelector(`#cms-pages-input-title-${winId}`);
        const inputSlug = body.querySelector(`#cms-pages-input-slug-${winId}`);

        let allPages = [];

        const showAlert = (msg, type = 'success') => {
            alertBox.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-2 mb-0 py-2 small`;
            alertBox.innerHTML = `${msg} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
            setTimeout(() => alertBox.classList.add('d-none'), 5000);
        };

        const loadPages = () => {
            fetch('/admin/cms/pages', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        allPages = res.data || [];
                        renderPageRows(allPages);
                    }
                })
                .catch(err => {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Gagal memuat halaman: ${err.message}</td></tr>`;
                });
        };

        const renderPageRows = (pages) => {
            const query = searchInput.value.toLowerCase().trim();
            const filtered = query ? pages.filter(p => p.title.toLowerCase().includes(query) || p.slug.toLowerCase().includes(query)) : pages;

            countBadge.textContent = `${filtered.length} Halaman`;
            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada halaman ditemukan.</td></tr>`;
                return;
            }

            tbody.innerHTML = filtered.map(p => {
                const cs = p.comment_settings || {};
                const csBadge = cs.enabled
                    ? `<span class="badge bg-info-subtle text-info border border-info-subtle text-uppercase">${cs.style || 'cards'}</span>`
                    : `<span class="badge bg-secondary-subtle text-muted">Nonaktif</span>`;

                const typeBadge = p.page_type === 'news_single'
                    ? '<span class="badge bg-purple-subtle text-purple border" style="background:#f3e8ff; color:#7e22ce;">Reader Single</span>'
                    : (p.page_type === 'news_index'
                        ? '<span class="badge bg-primary-subtle text-primary border">News Index</span>'
                        : '<span class="badge bg-light text-dark border">Standard</span>');

                const layoutBadge = p.layout_template === 'fullwidth'
                    ? '<span class="badge bg-info-subtle text-info border">Full-Width</span>'
                    : (p.layout_template === 'sidebar'
                        ? '<span class="badge bg-warning-subtle text-warning border">Sidebar</span>'
                        : (p.layout_template === 'blank'
                            ? '<span class="badge bg-secondary-subtle text-secondary border">Blank</span>'
                            : '<span class="badge bg-light text-dark border">Default</span>'));

                return `
                    <tr>
                        <td class="text-center text-muted font-monospace small">${p.id}</td>
                        <td>
                            <div class="fw-semibold text-dark">${p.title}</div>
                            <div class="text-muted small font-monospace">/${p.slug === 'beranda' ? '' : p.slug}</div>
                        </td>
                        <td>${typeBadge}</td>
                        <td>${layoutBadge}</td>
                        <td>${csBadge}</td>
                        <td class="text-center">
                            <span class="badge ${p.status === 'published' ? 'bg-success-subtle text-success border' : 'bg-warning-subtle text-warning border'}">${p.status}</span>
                        </td>
                        <td class="text-end pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="/${p.slug === 'beranda' ? '' : p.slug}" target="_blank" class="btn btn-outline-secondary" title="Buka Halaman Publik"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                                <button class="btn btn-outline-secondary btn-edit-page" data-id="${p.id}" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn btn-outline-danger btn-delete-page" data-id="${p.id}" data-title="${p.title}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            tbody.querySelectorAll('.btn-edit-page').forEach(btn => {
                btn.addEventListener('click', () => editPage(btn.getAttribute('data-id')));
            });
            tbody.querySelectorAll('.btn-delete-page').forEach(btn => {
                btn.addEventListener('click', () => deletePage(btn.getAttribute('data-id'), btn.getAttribute('data-title')));
            });
        };

        inputTitle.addEventListener('input', () => {
            const formId = body.querySelector(`#cms-pages-form-id-${winId}`).value;
            if (!formId) {
                inputSlug.value = inputTitle.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });

        const resetForm = () => {
            form.reset();
            body.querySelector(`#cms-pages-form-id-${winId}`).value = '';
            body.querySelector(`#cms-pages-form-title-${winId}`).textContent = 'Buat Halaman Baru';
            body.querySelector(`#cms-pages-input-layout-${winId}`).value = 'default';
            const blueprintSelect = body.querySelector(`#cms-pages-blueprint-select-${winId}`);
            if (blueprintSelect) blueprintSelect.value = '';
            this.setRichEditorContent(`cms-pages-input-content-${winId}`, '');
            formPanel.classList.add('d-none');
        };

        const editPage = (id) => {
            fetch(`/admin/cms/pages/${id}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        body.querySelector(`#cms-pages-form-id-${winId}`).value = d.id;
                        body.querySelector(`#cms-pages-form-title-${winId}`).textContent = `Edit Halaman: ${d.title}`;
                        inputTitle.value = d.title;
                        inputSlug.value = d.slug;
                        body.querySelector(`#cms-pages-input-type-${winId}`).value = d.page_type || 'standard';
                        body.querySelector(`#cms-pages-input-layout-${winId}`).value = d.layout_template || 'default';
                        body.querySelector(`#cms-pages-input-status-${winId}`).value = d.status || 'published';
                        body.querySelector(`#cms-pages-input-sort-${winId}`).value = d.sort_order || 0;
                        body.querySelector(`#cms-pages-input-meta-title-${winId}`).value = d.meta_title || '';
                        body.querySelector(`#cms-pages-input-meta-desc-${winId}`).value = d.meta_description || '';
                        this.initRichEditor(`cms-pages-input-content-${winId}`);
                        this.setRichEditorContent(`cms-pages-input-content-${winId}`, d.content || '');

                        const cs = d.comment_settings || {};
                        body.querySelector(`#cms-pages-comment-style-${winId}`).value = cs.style || 'cards';
                        body.querySelector(`#cms-pages-comment-perpage-${winId}`).value = cs.per_page || 15;
                        body.querySelector(`#cms-pages-comment-enabled-${winId}`).checked = !!cs.enabled;
                        body.querySelector(`#cms-pages-comment-guests-${winId}`).checked = cs.allow_guests !== false;

                        formPanel.classList.remove('d-none');
                        formPanel.scrollIntoView({ behavior: 'smooth' });
                    }
                });
        };

        const deletePage = (id, title) => {
            if (!confirm(`Hapus halaman "${title}"?`)) return;

            fetch(`/admin/cms/pages/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        loadPages();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                })
                .catch(err => showAlert(err.message, 'danger'));
        };

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = body.querySelector(`#cms-pages-form-id-${winId}`).value;
            const payload = {
                title: inputTitle.value.trim(),
                slug: inputSlug.value.trim(),
                page_type: body.querySelector(`#cms-pages-input-type-${winId}`).value,
                layout_template: body.querySelector(`#cms-pages-input-layout-${winId}`).value,
                status: body.querySelector(`#cms-pages-input-status-${winId}`).value,
                sort_order: parseInt(body.querySelector(`#cms-pages-input-sort-${winId}`).value, 10) || 0,
                meta_title: body.querySelector(`#cms-pages-input-meta-title-${winId}`).value.trim(),
                meta_description: body.querySelector(`#cms-pages-input-meta-desc-${winId}`).value.trim(),
                content: this.getRichEditorContent(`cms-pages-input-content-${winId}`),
                comment_settings: {
                    enabled: body.querySelector(`#cms-pages-comment-enabled-${winId}`).checked,
                    style: body.querySelector(`#cms-pages-comment-style-${winId}`).value,
                    allow_guests: body.querySelector(`#cms-pages-comment-guests-${winId}`).checked,
                    per_page: parseInt(body.querySelector(`#cms-pages-comment-perpage-${winId}`).value, 10) || 15,
                },
                _token: csrfToken
            };

            const method = id ? 'PUT' : 'POST';
            const url = id ? `/admin/cms/pages/${id}` : '/admin/cms/pages';

            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        resetForm();
                        loadPages();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                })
                .catch(err => showAlert(err.message, 'danger'));
        });

        // Event listener untuk tombol Terapkan Blueprint
        const blueprintSelect = body.querySelector(`#cms-pages-blueprint-select-${winId}`);
        const btnApplyBlueprint = body.querySelector(`#btn-apply-blueprint-${winId}`);
        if (btnApplyBlueprint && blueprintSelect) {
            btnApplyBlueprint.addEventListener('click', () => {
                const key = blueprintSelect.value;
                if (!key) {
                    alert('Silakan pilih salah satu template konten terlebih dahulu.');
                    return;
                }
                const blueprints = this.getPageBlueprints();
                const html = blueprints[key];
                if (!html) return;

                const current = this.getRichEditorContent(`cms-pages-input-content-${winId}`).trim();
                if (current && !confirm('Terapkan blueprint ini? Konten editor yang ada saat ini akan ditimpa.')) {
                    return;
                }

                this.initRichEditor(`cms-pages-input-content-${winId}`);
                this.setRichEditorContent(`cms-pages-input-content-${winId}`, html);

                // Otomatis sarankan tata letak publik yang serasi
                const layoutInput = body.querySelector(`#cms-pages-input-layout-${winId}`);
                if (key === 'services') {
                    layoutInput.value = 'fullwidth';
                } else if (key === 'policy') {
                    layoutInput.value = 'sidebar';
                }
            });
        }

        btnAdd.addEventListener('click', () => {
            resetForm();
            formPanel.classList.remove('d-none');
            this.initRichEditor(`cms-pages-input-content-${winId}`);
        });
        btnCancel.addEventListener('click', resetForm);
        btnCancelBottom.addEventListener('click', resetForm);
        btnRefresh.addEventListener('click', loadPages);
        searchInput.addEventListener('input', () => renderPageRows(allPages));

        loadPages();
    }

    /**
     * 3. Taksonomi: Kategori & Tag (open_cms_taxonomy)
     */
    renderCmsTaxonomy(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        const winId = winEl.id;

        body.innerHTML = `
            <!-- Nav Tabs -->
            <ul class="nav nav-tabs px-3 pt-2 bg-light border-bottom flex-shrink-0" style="font-size: 13px;">
                <li class="nav-item">
                    <button class="nav-link active fw-semibold" id="tab-btn-cat-${winId}" data-bs-toggle="tab" type="button">
                        <i class="fa-solid fa-folder-tree me-1 text-primary"></i> Kategori Berita
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-semibold text-secondary" id="tab-btn-tag-${winId}" data-bs-toggle="tab" type="button">
                        <i class="fa-solid fa-tags me-1 text-success"></i> Tag Konten
                    </button>
                </li>
            </ul>

            <!-- Alert Container -->
            <div id="cms-tax-alert-${winId}" class="d-none px-3 pt-2"></div>

            <!-- Tab Content Container -->
            <div class="tab-content flex-grow-1 overflow-hidden d-flex flex-column">
                <!-- TAB 1: Kategori -->
                <div class="tab-pane fade show active flex-grow-1 overflow-y-auto p-3 d-flex flex-column" id="tab-cat-pane-${winId}">
                    <div class="row g-3 flex-grow-1">
                        <!-- Form Tambah Kategori -->
                        <div class="col-12 col-md-5">
                            <div class="card border shadow-sm p-3 bg-white">
                                <h6 class="fw-bold mb-3 text-dark" id="cms-cat-form-title-${winId}">Tambah Kategori Baru</h6>
                                <form id="cms-cat-form-${winId}" autocomplete="off">
                                    <input type="hidden" id="cms-cat-form-id-${winId}">
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-1">Nama Kategori</label>
                                        <input type="text" class="form-control form-control-sm" id="cms-cat-name-${winId}" placeholder="Contoh: Teknologi" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-1">Slug URL</label>
                                        <input type="text" class="form-control form-control-sm" id="cms-cat-slug-${winId}" placeholder="teknologi">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-1">Warna Badge</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" class="form-control form-control-color form-control-sm" id="cms-cat-color-${winId}" value="#0d6efd">
                                            <span class="small text-muted font-monospace" id="cms-cat-color-val-${winId}">#0d6efd</span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-1">Deskripsi</label>
                                        <textarea class="form-control form-control-sm" id="cms-cat-desc-${winId}" rows="2" placeholder="Deskripsi singkat..."></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 mt-3">
                                        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="cms-cat-btn-cancel-${winId}">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-primary px-3" id="cms-cat-btn-save-${winId}">
                                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Tabel Kategori -->
                        <div class="col-12 col-md-7">
                            <div class="card border shadow-sm h-100 overflow-hidden d-flex flex-column">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                                    <span class="small fw-semibold text-muted">Daftar Kategori</span>
                                    <span class="badge bg-primary-subtle text-primary border" id="cms-cat-count-${winId}">0 Kategori</span>
                                </div>
                                <div class="table-responsive flex-grow-1 overflow-y-auto">
                                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 13px;">
                                        <thead class="table-light border-bottom">
                                            <tr>
                                                <th style="width: 25px;"></th>
                                                <th>Nama & Slug</th>
                                                <th style="width: 80px;" class="text-center">Berita</th>
                                                <th style="width: 80px;" class="text-end pe-2">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cms-cat-tbody-${winId}">
                                            <tr><td colspan="4" class="text-center py-3 text-muted">Memuat kategori...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Tag Konten -->
                <div class="tab-pane fade flex-grow-1 overflow-y-auto p-3 d-none flex-column" id="tab-tag-pane-${winId}">
                    <div class="card border shadow-sm p-3 mb-3 bg-white">
                        <h6 class="fw-bold mb-2 text-dark">Tambah Tag Baru</h6>
                        <form id="cms-tag-form-${winId}" class="d-flex gap-2 align-items-center" autocomplete="off">
                            <input type="text" class="form-control form-control-sm" id="cms-tag-name-${winId}" placeholder="Ketik nama tag lalu tekan Enter..." style="max-width: 320px;" required>
                            <button type="submit" class="btn btn-sm btn-success px-3">
                                <i class="fa-solid fa-plus me-1"></i> Tambah Tag
                            </button>
                        </form>
                    </div>

                    <div class="card border shadow-sm flex-grow-1 p-3 bg-white d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="small fw-semibold text-muted">Semua Tag Tersedia</span>
                            <span class="badge bg-success-subtle text-success border" id="cms-tag-count-${winId}">0 Tag</span>
                        </div>
                        <div id="cms-tag-chip-container-${winId}" class="d-flex flex-wrap gap-2 overflow-y-auto flex-grow-1 align-content-start">
                            <span class="text-muted small">Memuat tags...</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const csrfToken = this.getCsrfToken();
        const alertBox = body.querySelector(`#cms-tax-alert-${winId}`);
        const tabBtnCat = body.querySelector(`#tab-btn-cat-${winId}`);
        const tabBtnTag = body.querySelector(`#tab-btn-tag-${winId}`);
        const tabCatPane = body.querySelector(`#tab-cat-pane-${winId}`);
        const tabTagPane = body.querySelector(`#tab-tag-pane-${winId}`);

        // Category Elements
        const catForm = body.querySelector(`#cms-cat-form-${winId}`);
        const catFormId = body.querySelector(`#cms-cat-form-id-${winId}`);
        const catFormTitle = body.querySelector(`#cms-cat-form-title-${winId}`);
        const catName = body.querySelector(`#cms-cat-name-${winId}`);
        const catSlug = body.querySelector(`#cms-cat-slug-${winId}`);
        const catColor = body.querySelector(`#cms-cat-color-${winId}`);
        const catColorVal = body.querySelector(`#cms-cat-color-val-${winId}`);
        const catDesc = body.querySelector(`#cms-cat-desc-${winId}`);
        const catBtnCancel = body.querySelector(`#cms-cat-btn-cancel-${winId}`);
        const catCount = body.querySelector(`#cms-cat-count-${winId}`);
        const catTbody = body.querySelector(`#cms-cat-tbody-${winId}`);

        // Tag Elements
        const tagForm = body.querySelector(`#cms-tag-form-${winId}`);
        const tagName = body.querySelector(`#cms-tag-name-${winId}`);
        const tagCount = body.querySelector(`#cms-tag-count-${winId}`);
        const tagChipContainer = body.querySelector(`#cms-tag-chip-container-${winId}`);

        const showAlert = (msg, type = 'success') => {
            alertBox.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-2 mb-0 py-2 small`;
            alertBox.innerHTML = `${msg} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
            setTimeout(() => alertBox.classList.add('d-none'), 4000);
        };

        // Tab Switching
        tabBtnCat.addEventListener('click', () => {
            tabBtnCat.className = 'nav-link active fw-semibold';
            tabBtnTag.className = 'nav-link fw-semibold text-secondary';
            tabCatPane.classList.add('show', 'active');
            tabCatPane.classList.remove('d-none');
            tabTagPane.classList.remove('show', 'active');
            tabTagPane.classList.add('d-none');
        });

        tabBtnTag.addEventListener('click', () => {
            tabBtnTag.className = 'nav-link active fw-semibold';
            tabBtnCat.className = 'nav-link fw-semibold text-secondary';
            tabTagPane.classList.add('show', 'active');
            tabTagPane.classList.remove('d-none');
            tabCatPane.classList.remove('show', 'active');
            tabCatPane.classList.add('d-none');
            loadTags();
        });

        catColor.addEventListener('input', () => {
            catColorVal.textContent = catColor.value;
        });

        catName.addEventListener('input', () => {
            if (!catFormId.value) {
                catSlug.value = catName.value.toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });

        // Load Categories
        const loadCategories = () => {
            fetch('/admin/cms/categories', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const list = res.data || [];
                        catCount.textContent = `${list.length} Kategori`;
                        if (list.length === 0) {
                            catTbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">Belum ada kategori.</td></tr>`;
                            return;
                        }

                        catTbody.innerHTML = list.map(c => `
                        <tr>
                            <td><span class="rounded-circle d-inline-block border" style="width: 12px; height: 12px; background-color: ${c.color || '#0d6efd'};"></span></td>
                            <td>
                                <div class="fw-semibold text-dark">${c.name}</div>
                                <div class="text-muted small font-monospace">${c.slug}</div>
                            </td>
                            <td class="text-center font-monospace small"><span class="badge bg-light text-dark border">${c.news_count || 0}</span></td>
                            <td class="text-end pe-2">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary btn-edit-cat" data-id="${c.id}" data-name="${c.name}" data-slug="${c.slug}" data-color="${c.color}" data-desc="${c.description || ''}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn btn-outline-danger btn-delete-cat" data-id="${c.id}" data-name="${c.name}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    `).join('');

                        catTbody.querySelectorAll('.btn-edit-cat').forEach(btn => {
                            btn.addEventListener('click', () => {
                                catFormId.value = btn.getAttribute('data-id');
                                catFormTitle.textContent = `Edit Kategori: ${btn.getAttribute('data-name')}`;
                                catName.value = btn.getAttribute('data-name');
                                catSlug.value = btn.getAttribute('data-slug');
                                catColor.value = btn.getAttribute('data-color') || '#0d6efd';
                                catColorVal.textContent = catColor.value;
                                catDesc.value = btn.getAttribute('data-desc') || '';
                                catBtnCancel.classList.remove('d-none');
                            });
                        });

                        catTbody.querySelectorAll('.btn-delete-cat').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = btn.getAttribute('data-id');
                                const name = btn.getAttribute('data-name');
                                if (!confirm(`Hapus kategori "${name}"?`)) return;

                                fetch(`/admin/cms/categories/${id}`, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                                })
                                    .then(res => res.json())
                                    .then(r => {
                                        if (r.status === 'success') {
                                            showAlert(r.message, 'success');
                                            loadCategories();
                                        } else {
                                            showAlert(r.message, 'danger');
                                        }
                                    });
                            });
                        });
                    }
                });
        };

        const resetCatForm = () => {
            catForm.reset();
            catFormId.value = '';
            catFormTitle.textContent = 'Tambah Kategori Baru';
            catColor.value = '#0d6efd';
            catColorVal.textContent = '#0d6efd';
            catBtnCancel.classList.add('d-none');
        };

        catBtnCancel.addEventListener('click', resetCatForm);

        catForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = catFormId.value;
            const payload = {
                name: catName.value.trim(),
                slug: catSlug.value.trim(),
                color: catColor.value,
                description: catDesc.value.trim(),
                _token: csrfToken
            };

            const method = id ? 'PUT' : 'POST';
            const url = id ? `/admin/cms/categories/${id}` : '/admin/cms/categories';

            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        resetCatForm();
                        loadCategories();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        });

        // Load Tags
        const loadTags = () => {
            fetch('/admin/cms/tags', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const list = res.data || [];
                        tagCount.textContent = `${list.length} Tag`;
                        if (list.length === 0) {
                            tagChipContainer.innerHTML = '<span class="text-muted small">Belum ada tag dibuat.</span>';
                            return;
                        }

                        tagChipContainer.innerHTML = list.map(t => `
                        <div class="badge bg-light text-dark border p-2 d-inline-flex align-items-center gap-2" style="font-size: 13px;">
                            <i class="fa-solid fa-tag text-success small"></i>
                            <span class="fw-semibold">${t.name}</span>
                            <span class="badge bg-secondary-subtle text-muted ms-1">${t.news_count || 0}</span>
                            <button type="button" class="btn-close btn-close-sm btn-del-tag ms-1" data-id="${t.id}" data-name="${t.name}" style="font-size: 8px;"></button>
                        </div>
                    `).join('');

                        tagChipContainer.querySelectorAll('.btn-del-tag').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = btn.getAttribute('data-id');
                                const name = btn.getAttribute('data-name');
                                if (!confirm(`Hapus tag "${name}"?`)) return;

                                fetch(`/admin/cms/tags/${id}`, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                                })
                                    .then(res => res.json())
                                    .then(r => {
                                        if (r.status === 'success') {
                                            showAlert(r.message, 'success');
                                            loadTags();
                                        } else {
                                            showAlert(r.message, 'danger');
                                        }
                                    });
                            });
                        });
                    }
                });
        };

        tagForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = tagName.value.trim();
            if (!name) return;

            fetch('/admin/cms/tags', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name, _token: csrfToken })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        tagName.value = '';
                        loadTags();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        });

        loadCategories();
    }

    /**
     * 4. Moderasi Komentar (open_cms_comments)
     */
    renderCmsComments(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        const winId = winEl.id;

        body.innerHTML = `
            <!-- Filter Tabs Status -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <div class="d-flex align-items-center gap-1 btn-group btn-group-sm">
                    <button class="btn btn-outline-primary active btn-status-filter" data-status="">Semua</button>
                    <button class="btn btn-outline-warning btn-status-filter" data-status="pending">Menunggu (Pending)</button>
                    <button class="btn btn-outline-success btn-status-filter" data-status="approved">Disetujui</button>
                    <button class="btn btn-outline-danger btn-status-filter" data-status="spam">Spam</button>
                    <button class="btn btn-outline-secondary btn-status-filter" data-status="rejected">Ditolak</button>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border" id="cms-comments-count-${winId}">Memuat...</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-comments-${winId}" title="Muat Ulang">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="cms-comments-alert-${winId}" class="d-none px-3 pt-2"></div>

            <!-- Reply Drawer Modal / Panel -->
            <div id="cms-comments-reply-panel-${winId}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold mb-0 text-dark" id="cms-comments-reply-title-${winId}">Balas Komentar</h6>
                    <button type="button" class="btn-close" id="btn-close-reply-${winId}"></button>
                </div>
                <div class="p-2 bg-white rounded border mb-2 small text-muted fst-italic" id="cms-comments-reply-quote-${winId}">
                    "Isi komentar pengunjung..."
                </div>
                <form id="cms-comments-reply-form-${winId}">
                    <input type="hidden" id="cms-comments-reply-id-${winId}">
                    <div class="mb-2">
                        <textarea class="form-control form-control-sm" id="cms-comments-reply-text-${winId}" rows="3" placeholder="Tulis balasan resmi administrator di sini..." required></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-reply-${winId}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            <i class="fa-solid fa-paper-plane me-1"></i> Kirim Balasan
                        </button>
                    </div>
                </form>
            </div>

            <!-- List Komentar -->
            <div class="flex-grow-1 overflow-y-auto p-3" id="cms-comments-list-${winId}">
                <div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat data komentar...</div>
            </div>
        `;

        const csrfToken = this.getCsrfToken();
        const alertBox = body.querySelector(`#cms-comments-alert-${winId}`);
        const countBadge = body.querySelector(`#cms-comments-count-${winId}`);
        const listContainer = body.querySelector(`#cms-comments-list-${winId}`);
        const btnRefresh = body.querySelector(`#btn-refresh-comments-${winId}`);
        const replyPanel = body.querySelector(`#cms-comments-reply-panel-${winId}`);
        const replyForm = body.querySelector(`#cms-comments-reply-form-${winId}`);
        const replyId = body.querySelector(`#cms-comments-reply-id-${winId}`);
        const replyQuote = body.querySelector(`#cms-comments-reply-quote-${winId}`);
        const replyText = body.querySelector(`#cms-comments-reply-text-${winId}`);
        const btnCloseReply = body.querySelector(`#btn-close-reply-${winId}`);
        const btnCancelReply = body.querySelector(`#btn-cancel-reply-${winId}`);

        let currentStatus = '';

        const showAlert = (msg, type = 'success') => {
            alertBox.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-2 mb-0 py-2 small`;
            alertBox.innerHTML = `${msg} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
            setTimeout(() => alertBox.classList.add('d-none'), 4000);
        };

        const loadComments = () => {
            let url = '/admin/cms/comments';
            if (currentStatus) url += `?status=${encodeURIComponent(currentStatus)}`;

            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const list = res.data || [];
                        countBadge.textContent = `${list.length} Komentar`;
                        if (list.length === 0) {
                            listContainer.innerHTML = '<div class="text-center py-4 text-muted">Tidak ada komentar dalam status ini.</div>';
                            return;
                        }

                        listContainer.innerHTML = list.map(c => {
                            const statusBadge = c.status === 'approved'
                                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Disetujui</span>'
                                : (c.status === 'pending'
                                    ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Menunggu</span>'
                                    : (c.status === 'spam'
                                        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">Spam</span>'
                                        : '<span class="badge bg-secondary-subtle text-secondary border">Ditolak</span>'));

                            return `
                            <div class="card border shadow-sm mb-3">
                                <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 11px;">
                                            ${(c.author_name || 'U').charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-dark">${c.author_name}</span>
                                            <span class="text-muted small ms-1">&lt;${c.author_email}&gt;</span>
                                            ${c.user_id ? '<span class="badge bg-info-subtle text-info border ms-1" style="font-size: 10px;">Member</span>' : '<span class="badge bg-light text-muted border ms-1" style="font-size: 10px;">Tamu</span>'}
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        ${statusBadge}
                                        <span class="text-muted small" style="font-size: 11px;">${c.created_at || ''}</span>
                                    </div>
                                </div>
                                <div class="card-body py-2 px-3">
                                    <div class="mb-1 text-muted small">
                                        Pada artikel: <a href="/baca-berita?slug=${c.news_slug || ''}" target="_blank" class="fw-semibold text-decoration-none">${c.news_title || 'Berita #' + c.news_id}</a>
                                    </div>
                                    <div class="p-2 bg-light-subtle rounded border text-dark" style="font-size: 13.5px;">
                                        ${(c.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}
                                    </div>
                                </div>
                                <div class="card-footer bg-white py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div class="btn-group btn-group-sm">
                                        ${c.status !== 'approved' ? `<button class="btn btn-outline-success btn-status-action" data-id="${c.id}" data-new-status="approved"><i class="fa-solid fa-check me-1"></i> Setujui</button>` : ''}
                                        ${c.status !== 'spam' ? `<button class="btn btn-outline-warning btn-status-action" data-id="${c.id}" data-new-status="spam"><i class="fa-solid fa-shield-virus me-1"></i> Spam</button>` : ''}
                                        ${c.status !== 'rejected' ? `<button class="btn btn-outline-secondary btn-status-action" data-id="${c.id}" data-new-status="rejected"><i class="fa-solid fa-ban me-1"></i> Tolak</button>` : ''}
                                        <button class="btn btn-outline-primary btn-open-reply" data-id="${c.id}" data-author="${c.author_name}" data-content="${(c.content || '').replace(/"/g, '&quot;')}"><i class="fa-solid fa-reply me-1"></i> Balas</button>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger btn-del-comment" data-id="${c.id}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                        `;
                        }).join('');

                        listContainer.querySelectorAll('.btn-status-action').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = btn.getAttribute('data-id');
                                const st = btn.getAttribute('data-new-status');
                                updateCommentStatus(id, st);
                            });
                        });

                        listContainer.querySelectorAll('.btn-open-reply').forEach(btn => {
                            btn.addEventListener('click', () => {
                                replyId.value = btn.getAttribute('data-id');
                                replyQuote.textContent = `"${btn.getAttribute('data-author')}: ${btn.getAttribute('data-content')}"`;
                                replyPanel.classList.remove('d-none');
                                replyText.focus();
                            });
                        });

                        listContainer.querySelectorAll('.btn-del-comment').forEach(btn => {
                            btn.addEventListener('click', () => {
                                const id = btn.getAttribute('data-id');
                                if (!confirm(`Hapus komentar #${id}?`)) return;

                                fetch(`/admin/cms/comments/${id}`, {
                                    method: 'DELETE',
                                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                                })
                                    .then(res => res.json())
                                    .then(r => {
                                        if (r.status === 'success') {
                                            showAlert(r.message, 'success');
                                            loadComments();
                                        } else {
                                            showAlert(r.message, 'danger');
                                        }
                                    });
                            });
                        });
                    }
                });
        };

        const updateCommentStatus = (id, newStatus) => {
            fetch(`/admin/cms/comments/${id}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ status: newStatus, _token: csrfToken })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        loadComments();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        };

        replyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = replyId.value;
            const content = replyText.value.trim();
            if (!content) return;

            fetch(`/admin/cms/comments/${id}/reply`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ content, _token: csrfToken })
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert('Balasan berhasil dikirim dan dipublikasikan.', 'success');
                        replyForm.reset();
                        replyPanel.classList.add('d-none');
                        loadComments();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        });

        const closeReply = () => {
            replyForm.reset();
            replyPanel.classList.add('d-none');
        };

        btnCloseReply.addEventListener('click', closeReply);
        btnCancelReply.addEventListener('click', closeReply);
        btnRefresh.addEventListener('click', loadComments);

        body.querySelectorAll('.btn-status-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                body.querySelectorAll('.btn-status-filter').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentStatus = btn.getAttribute('data-status');
                loadComments();
            });
        });

        loadComments();
    }

    /**
     * 5. Navigasi Menu Publik (open_cms_menus)
     */
    renderCmsPublicMenus(winEl) {
        const body = winEl.querySelector('.wd-window-body');
        if (!body) return;

        body.className = 'wd-window-body card-body p-0 d-flex flex-column h-100 overflow-hidden';
        const winId = winEl.id;

        body.innerHTML = `
            <!-- Toolbar -->
            <div class="py-2 px-3 bg-light border-bottom d-flex justify-content-between align-items-center gap-2 flex-wrap flex-shrink-0">
                <span class="small text-muted">Struktur Navigasi Website Publik (Header & Navbar)</span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm" id="btn-add-pmenu-${winId}">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Item Menu</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-pmenu-${winId}" title="Muat Ulang">
                        <i class="fa-solid fa-arrows-rotate"></i>
                    </button>
                </div>
            </div>

            <!-- Alert Container -->
            <div id="cms-pmenu-alert-${winId}" class="d-none px-3 pt-2"></div>

            <!-- Form Drawer / Panel Editor Menu -->
            <div id="cms-pmenu-form-panel-${winId}" class="d-none bg-light-subtle border-bottom p-3 flex-shrink-0">
                <form id="cms-pmenu-form-${winId}" autocomplete="off">
                    <input type="hidden" id="cms-pmenu-form-id-${winId}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0 text-dark" id="cms-pmenu-form-title-${winId}">Tambah Item Menu</h6>
                        <button type="button" class="btn-close" id="btn-cancel-pmenu-${winId}"></button>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Judul Tautan Menu</label>
                            <input type="text" class="form-control form-control-sm" id="cms-pmenu-input-title-${winId}" placeholder="Contoh: Beranda, Berita, Layanan" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Tipe Tautan (Link Type)</label>
                            <select class="form-select form-select-sm" id="cms-pmenu-input-type-${winId}">
                                <option value="page">Halaman Publik (Pages)</option>
                                <option value="news_category">Kategori Berita</option>
                                <option value="news_index">Kanal Berita (/berita)</option>
                                <option value="custom_url">URL Kustom / Eksternal</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6" id="cms-pmenu-target-container-${winId}">
                            <label class="form-label small fw-semibold mb-1">Target Halaman</label>
                            <select class="form-select form-select-sm" id="cms-pmenu-input-target-${winId}">
                                <option value="beranda">Beranda (Home /)</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6 d-none" id="cms-pmenu-url-container-${winId}">
                            <label class="form-label small fw-semibold mb-1">URL Bebas</label>
                            <input type="text" class="form-control form-control-sm" id="cms-pmenu-input-url-${winId}" placeholder="https://... atau /kontak">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label small fw-semibold mb-1">Menu Induk (Parent)</label>
                            <select class="form-select form-select-sm" id="cms-pmenu-input-parent-${winId}">
                                <option value="">-- Menu Tingkat Utama (Root) --</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label small fw-semibold mb-1">Urutan (Sort Order)</label>
                            <input type="number" class="form-control form-control-sm" id="cms-pmenu-input-sort-${winId}" value="0">
                        </div>

                        <div class="col-12 col-md-3 d-flex align-items-center pt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cms-pmenu-input-active-${winId}" checked>
                                <label class="form-check-label small" for="cms-pmenu-input-active-${winId}">Aktif Tampil</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-cancel-pmenu-bottom-${winId}">Batal</button>
                        <button type="submit" class="btn btn-sm btn-primary px-3">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Menu
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table Public Menu Tree -->
            <div class="table-responsive flex-grow-1 overflow-y-auto">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light sticky-top border-bottom">
                        <tr>
                            <th style="width: 50px;" class="text-center">Order</th>
                            <th>Judul Menu</th>
                            <th style="width: 140px;">Tipe Link</th>
                            <th>URL Terkomputasi</th>
                            <th style="width: 90px;" class="text-center">Status</th>
                            <th style="width: 100px;" class="text-end pe-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cms-pmenu-tbody-${winId}">
                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-1"></i> Memuat navigasi menu...</td></tr>
                    </tbody>
                </table>
            </div>
        `;

        const csrfToken = this.getCsrfToken();
        const alertBox = body.querySelector(`#cms-pmenu-alert-${winId}`);
        const tbody = body.querySelector(`#cms-pmenu-tbody-${winId}`);
        const formPanel = body.querySelector(`#cms-pmenu-form-panel-${winId}`);
        const form = body.querySelector(`#cms-pmenu-form-${winId}`);
        const formId = body.querySelector(`#cms-pmenu-form-id-${winId}`);
        const formTitle = body.querySelector(`#cms-pmenu-form-title-${winId}`);
        const inputTitle = body.querySelector(`#cms-pmenu-input-title-${winId}`);
        const inputType = body.querySelector(`#cms-pmenu-input-type-${winId}`);
        const inputTarget = body.querySelector(`#cms-pmenu-input-target-${winId}`);
        const inputUrl = body.querySelector(`#cms-pmenu-input-url-${winId}`);
        const inputParent = body.querySelector(`#cms-pmenu-input-parent-${winId}`);
        const inputSort = body.querySelector(`#cms-pmenu-input-sort-${winId}`);
        const inputActive = body.querySelector(`#cms-pmenu-input-active-${winId}`);
        const targetContainer = body.querySelector(`#cms-pmenu-target-container-${winId}`);
        const urlContainer = body.querySelector(`#cms-pmenu-url-container-${winId}`);
        const btnAdd = body.querySelector(`#btn-add-pmenu-${winId}`);
        const btnRefresh = body.querySelector(`#btn-refresh-pmenu-${winId}`);
        const btnCancel = body.querySelector(`#btn-cancel-pmenu-${winId}`);
        const btnCancelBottom = body.querySelector(`#btn-cancel-pmenu-bottom-${winId}`);

        let allPages = [];
        let allCategories = [];
        let flatItems = [];

        const showAlert = (msg, type = 'success') => {
            alertBox.className = `alert alert-${type} alert-dismissible fade show mx-3 mt-2 mb-0 py-2 small`;
            alertBox.innerHTML = `${msg} <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>`;
            setTimeout(() => alertBox.classList.add('d-none'), 4000);
        };

        const updateTargetOptions = () => {
            const type = inputType.value;
            if (type === 'custom_url') {
                targetContainer.classList.add('d-none');
                urlContainer.classList.remove('d-none');
            } else if (type === 'news_index') {
                targetContainer.classList.add('d-none');
                urlContainer.classList.add('d-none');
            } else if (type === 'news_category') {
                targetContainer.classList.remove('d-none');
                urlContainer.classList.add('d-none');
                targetContainer.querySelector('label').textContent = 'Pilih Kategori Berita';
                inputTarget.innerHTML = allCategories.map(c => `<option value="${c.slug}">${c.name}</option>`).join('');
            } else {
                targetContainer.classList.remove('d-none');
                urlContainer.classList.add('d-none');
                targetContainer.querySelector('label').textContent = 'Pilih Halaman Publik';
                inputTarget.innerHTML = allPages.map(p => `<option value="${p.slug}">${p.title} (/${p.slug})</option>`).join('');
            }
        };

        inputType.addEventListener('change', updateTargetOptions);

        const loadPrerequisites = async () => {
            try {
                const [pRes, cRes] = await Promise.all([
                    fetch('/admin/cms/pages', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } }).then(r => r.json()),
                    fetch('/admin/cms/categories', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } }).then(r => r.json())
                ]);
                allPages = pRes.data || [];
                allCategories = cRes.data || [];
                updateTargetOptions();
            } catch (e) { }
        };

        const loadMenus = () => {
            fetch('/admin/cms/menus', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        flatItems = res.items || [];
                        const tree = res.tree || [];

                        // Update parent dropdown
                        let parentOptions = '<option value="">-- Menu Tingkat Utama (Root) --</option>';
                        flatItems.filter(m => !m.parent_id).forEach(m => {
                            parentOptions += `<option value="${m.id}">${m.title}</option>`;
                        });
                        inputParent.innerHTML = parentOptions;

                        if (tree.length === 0) {
                            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada item menu.</td></tr>`;
                            return;
                        }

                        // Render tree rows recursively
                        let rowsHtml = '';
                        const renderRow = (item, level = 0) => {
                            const indent = level > 0 ? `<span class="text-muted font-monospace me-1 ms-${level * 2}">└─ </span>` : '';
                            const typeLabel = item.link_type === 'page' ? 'Halaman' : (item.link_type === 'news_category' ? 'Kategori Berita' : (item.link_type === 'news_index' ? 'Kanal Berita' : 'Kustom'));

                            rowsHtml += `
                            <tr>
                                <td class="text-center font-monospace text-muted small">${item.sort_order}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        ${indent}
                                        <span class="fw-semibold text-dark">${item.title}</span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border">${typeLabel}</span></td>
                                <td><span class="font-monospace small text-primary">${item.computed_url}</span></td>
                                <td class="text-center">
                                    <span class="badge ${item.is_active ? 'bg-success-subtle text-success border' : 'bg-secondary-subtle text-muted'}">
                                        ${item.is_active ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary btn-edit-pmenu" data-id="${item.id}" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                        <button class="btn btn-outline-danger btn-delete-pmenu" data-id="${item.id}" data-title="${item.title}" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;

                            if (item.children && item.children.length > 0) {
                                item.children.forEach(ch => renderRow(ch, level + 1));
                            }
                        };

                        tree.forEach(t => renderRow(t, 0));
                        tbody.innerHTML = rowsHtml;

                        tbody.querySelectorAll('.btn-edit-pmenu').forEach(btn => {
                            btn.addEventListener('click', () => editMenu(btn.getAttribute('data-id')));
                        });
                        tbody.querySelectorAll('.btn-delete-pmenu').forEach(btn => {
                            btn.addEventListener('click', () => deleteMenu(btn.getAttribute('data-id'), btn.getAttribute('data-title')));
                        });
                    }
                });
        };

        const resetForm = () => {
            form.reset();
            formId.value = '';
            formTitle.textContent = 'Tambah Item Menu';
            updateTargetOptions();
            formPanel.classList.add('d-none');
        };

        const editMenu = (id) => {
            const item = flatItems.find(m => m.id == id);
            if (!item) return;

            formId.value = item.id;
            formTitle.textContent = `Edit Menu: ${item.title}`;
            inputTitle.value = item.title;
            inputType.value = item.link_type;
            updateTargetOptions();
            inputTarget.value = item.link_target || '';
            inputUrl.value = item.url || '';
            inputParent.value = item.parent_id || '';
            inputSort.value = item.sort_order || 0;
            inputActive.checked = !!item.is_active;

            formPanel.classList.remove('d-none');
            formPanel.scrollIntoView({ behavior: 'smooth' });
        };

        const deleteMenu = (id, title) => {
            if (!confirm(`Hapus menu "${title}"?`)) return;

            fetch(`/admin/cms/menus/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        loadMenus();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        };

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const id = formId.value;
            const payload = {
                title: inputTitle.value.trim(),
                link_type: inputType.value,
                link_target: inputTarget.value || null,
                url: inputUrl.value.trim() || null,
                parent_id: inputParent.value ? parseInt(inputParent.value, 10) : null,
                sort_order: parseInt(inputSort.value, 10) || 0,
                is_active: inputActive.checked ? 1 : 0,
                _token: csrfToken
            };

            const method = id ? 'PUT' : 'POST';
            const url = id ? `/admin/cms/menus/${id}` : '/admin/cms/menus';

            fetch(url, {
                method: method,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        showAlert(res.message, 'success');
                        resetForm();
                        loadMenus();
                    } else {
                        showAlert(res.message, 'danger');
                    }
                });
        });

        btnAdd.addEventListener('click', () => { resetForm(); formPanel.classList.remove('d-none'); });
        btnCancel.addEventListener('click', resetForm);
        btnCancelBottom.addEventListener('click', resetForm);
        btnRefresh.addEventListener('click', loadMenus);

        loadPrerequisites().then(loadMenus);
    }

    /**
     * Inisialisasi editor TinyMCE pada elemen textarea jika library tersedia.
     * 
     * @param {string} textareaId ID elemen textarea (tanpa '#')
     * @param {Object} options Opsi kustom TinyMCE
     */
    initRichEditor(textareaId, options = {}) {
        if (typeof window.tinymce === 'undefined') return;

        const existing = window.tinymce.get(textareaId);
        if (existing) {
            return;
        }

        const csrfToken = this.getCsrfToken();
        const blueprints = this.getPageBlueprints();
        const templatesList = [
            { title: 'Tentang Kami (About Us)', description: 'Struktur visi, misi, nilai dan profil perusahaan', content: blueprints.about },
            { title: 'Layanan & Fitur (Services)', description: 'Grid 3 kolom fitur dan call-to-action banner', content: blueprints.services },
            { title: 'Tanya Jawab (FAQ)', description: 'Daftar pertanyaan dan jawaban akordeon', content: blueprints.faq },
            { title: 'Hubungi Kami (Contact Us)', description: 'Informasi kontak kantor dan form pesan langsung', content: blueprints.contact },
            { title: 'Kebijakan Privasi (Privacy Policy)', description: 'Format formal syarat dan dokumen hukum', content: blueprints.policy },
        ];

        window.tinymce.init({
            selector: '#' + textareaId,
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code fullscreen template',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table template | align lineheight | numlist bullist indent outdent | emoticons charmap code fullscreen | removeformat',
            height: options.height || 360,
            menubar: 'file edit view insert format tools table',
            templates: templatesList,
            branding: false,
            promotion: false,
            license_key: 'gpl',
            images_upload_handler: (blobInfo) => new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('image', blobInfo.blob(), blobInfo.filename());
                formData.append('_token', csrfToken);

                fetch('/admin/cms/news/upload-image', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: formData
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success' && data.url) {
                            resolve(data.url);
                        } else {
                            reject(data.message || 'Gagal mengunggah gambar');
                        }
                    })
                    .catch(err => reject('Error: ' + err.message));
            }),
            setup: (editor) => {
                editor.on('change keyup NodeChange', () => {
                    editor.save();
                });
            },
            init_instance_callback: (editor) => {
                const el = document.getElementById(textareaId);
                if (el && el.value && !editor.getContent()) {
                    editor.setContent(el.value);
                }
            },
            ...options
        });
    }

    /**
     * Dapatkan konten HTML dari TinyMCE atau fallback ke nilai textarea.
     * 
     * @param {string} textareaId
     * @returns {string}
     */
    getRichEditorContent(textareaId) {
        if (typeof window.tinymce !== 'undefined') {
            const editor = window.tinymce.get(textareaId);
            if (editor && editor.initialized) {
                editor.save();
                return editor.getContent();
            }
        }
        const el = document.getElementById(textareaId);
        return el ? el.value : '';
    }

    /**
     * Setel konten ke dalam TinyMCE atau fallback ke textarea value.
     * 
     * @param {string} textareaId
     * @param {string} content
     */
    setRichEditorContent(textareaId, content = '') {
        const el = document.getElementById(textareaId);
        if (el) {
            el.value = content;
        }
        if (typeof window.tinymce !== 'undefined') {
            const editor = window.tinymce.get(textareaId);
            if (editor && editor.initialized) {
                editor.setContent(content);
                editor.save();
            }
        }
    }

    /**
     * Katalog Template Blueprint Konten Halaman Siap Pakai
     */
    getPageBlueprints() {
        return {
            about: `<div class="row align-items-center g-4 my-3">
    <div class="col-12 col-lg-6">
        <h2 class="fw-bold text-dark mb-3">Membangun Masa Depan Solusi Digital</h2>
        <p class="lead text-muted">Kami berdedikasi menciptakan ekosistem teknologi yang tangguh, aman, dan dirancang untuk memberikan kemudahan bagi setiap pengguna.</p>
        <p>Berawal dari semangat inovasi, kami mengembangkan sistem informasi modern dengan standar arsitektur kelas dunia yang mengedepankan performa, kesederhanaan, dan keandalan tinggi.</p>
    </div>
    <div class="col-12 col-lg-6 text-center">
        <div class="p-4 bg-light rounded-4 border">
            <i class="fa-solid fa-rocket fa-4x text-primary mb-3"></i>
            <h4 class="fw-bold text-dark">Inovasi Berkelanjutan</h4>
            <p class="text-muted small mb-0">Teknologi mutakhir yang selalu siap beradaptasi dengan dinamika masa depan.</p>
        </div>
    </div>
</div>

<div class="row g-4 my-4">
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="bg-primary text-white p-2 rounded-3"><i class="fa-solid fa-eye"></i></span>
                <h4 class="fw-bold mb-0">Visi Kami</h4>
            </div>
            <p class="text-muted mb-0">Menjadi pelopor ekosistem digital terpercaya yang memberikan dampak nyata bagi transformasi bisnis dan masyarakat secara global.</p>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-light h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="bg-success text-white p-2 rounded-3"><i class="fa-solid fa-bullseye"></i></span>
                <h4 class="fw-bold mb-0">Misi Kami</h4>
            </div>
            <p class="text-muted mb-0">Mengembangkan solusi perangkat lunak yang elegan, mudah dikelola, dan memberikan pengalaman pengguna terbaik tanpa kompromi performa.</p>
        </div>
    </div>
</div>

<div class="my-5">
    <h3 class="fw-bold text-center mb-4">Nilai Utama Kami</h3>
    <div class="row g-3 text-center">
        <div class="col-12 col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
                <i class="fa-solid fa-shield-halved fa-2x text-primary mb-2"></i>
                <h5 class="fw-bold">Integritas &amp; Keamanan</h5>
                <p class="text-muted small mb-0">Keamanan data dan transparansi adalah pondasi terdepan setiap sistem kami.</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
                <i class="fa-solid fa-bolt fa-2x text-warning mb-2"></i>
                <h5 class="fw-bold">Kecepatan &amp; Performa</h5>
                <p class="text-muted small mb-0">Arsitektur ringan dan efisien untuk response time kilat.</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 border rounded-3 bg-white h-100">
                <i class="fa-solid fa-heart fa-2x text-danger mb-2"></i>
                <h5 class="fw-bold">Fokus Pengguna</h5>
                <p class="text-muted small mb-0">Merancang antarmuka yang ramah dan intuitif bagi semua kalangan.</p>
            </div>
        </div>
    </div>
</div>`,

            services: `<div class="text-center my-4">
    <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-2">Layanan Unggulan</span>
    <h2 class="fw-bold text-dark">Solusi Lengkap untuk Kebutuhan Anda</h2>
    <p class="text-muted mx-auto" style="max-width: 650px;">Kami menghadirkan rangkaian kapabilitas komprehensif yang dirancang untuk mendukung akselerasi dan efisiensi operasional Anda.</p>
</div>

<div class="row g-4 my-4">
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
            <div class="mb-3 text-primary"><i class="fa-solid fa-laptop-code fa-2x"></i></div>
            <h5 class="fw-bold text-dark">Pengembangan Web &amp; Aplikasi</h5>
            <p class="text-muted small">Pembangunan aplikasi web kustom dengan arsitektur MVC modern, performa tinggi, dan standar keamanan terdepan.</p>
            <ul class="list-unstyled small text-muted mt-3">
                <li><i class="fa-solid fa-check text-success me-2"></i>Responsif multi-perangkat</li>
                <li><i class="fa-solid fa-check text-success me-2"></i>SEO &amp; Performa teroptimasi</li>
            </ul>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
            <div class="mb-3 text-success"><i class="fa-solid fa-cloud-arrow-up fa-2x"></i></div>
            <h5 class="fw-bold text-dark">Cloud &amp; Integrasi API</h5>
            <p class="text-muted small">Integrasi layanan komputasi awan, deployment otomatis, dan integrasi antar sistem melalui REST API standar.</p>
            <ul class="list-unstyled small text-muted mt-3">
                <li><i class="fa-solid fa-check text-success me-2"></i>High availability uptime</li>
                <li><i class="fa-solid fa-check text-success me-2"></i>Otentikasi aman berbasis token</li>
            </ul>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 shadow-sm border-0 rounded-4 p-4">
            <div class="mb-3 text-info"><i class="fa-solid fa-chart-pie fa-2x"></i></div>
            <h5 class="fw-bold text-dark">Analitik &amp; Optimasi Data</h5>
            <p class="text-muted small">Pelaporan data berkala, dashboard analitik interaktif, dan optimasi alur kerja berbasis data terpadu.</p>
            <ul class="list-unstyled small text-muted mt-3">
                <li><i class="fa-solid fa-check text-success me-2"></i>Visualisasi grafik dinamis</li>
                <li><i class="fa-solid fa-check text-success me-2"></i>Laporan otomatis real-time</li>
            </ul>
        </div>
    </div>
</div>

<div class="p-4 rounded-4 bg-primary text-white text-center my-4 shadow-sm">
    <h3 class="fw-bold mb-2">Siap Memulai Proyek Anda Bersama Kami?</h3>
    <p class="lead opacity-75 mb-3">Konsultasikan kebutuhan Anda hari ini dan temukan solusi terbaik.</p>
    <a href="/hubungi-kami" class="btn btn-light btn-lg text-primary fw-semibold px-4 shadow-sm">Hubungi Tim Kami</a>
</div>`,

            faq: `<div class="text-center my-4">
    <span class="badge bg-info-subtle text-info px-3 py-2 rounded-pill mb-2">Pusat Bantuan</span>
    <h2 class="fw-bold text-dark">Pertanyaan yang Sering Diajukan (FAQ)</h2>
    <p class="text-muted mx-auto" style="max-width: 600px;">Temukan jawaban cepat atas berbagai pertanyaan umum seputar sistem, layanan, dan bantuan teknis.</p>
</div>

<div class="accordion my-4 shadow-sm rounded-4 overflow-hidden" id="faqAccordion">
    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header" id="faqHeading1">
            <button class="accordion-button fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                <i class="fa-solid fa-circle-question text-primary me-2"></i> Apa itu SyntaxCore Framework?
            </button>
        </h2>
        <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1">
            <div class="accordion-body text-muted" style="line-height: 1.7;">
                SyntaxCore adalah framework PHP MVC mandiri yang ringan, elegan, dan dilengkapi desktop window manager interaktif untuk administrasi data dan manajemen konten publik (CMS).
            </div>
        </div>
    </div>

    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header" id="faqHeading2">
            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                <i class="fa-solid fa-circle-question text-primary me-2"></i> Bagaimana cara membuat halaman publik baru?
            </button>
        </h2>
        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2">
            <div class="accordion-body text-muted" style="line-height: 1.7;">
                Anda dapat masuk ke Panel Admin Desktop, membuka jendela <strong>Manajemen Halaman</strong>, lalu klik <strong>Buat Halaman</strong>. Anda bisa memilih template tata letak serta menyisipkan blueprint konten yang telah disediakan.
            </div>
        </div>
    </div>

    <div class="accordion-item border-0 border-bottom">
        <h2 class="accordion-header" id="faqHeading3">
            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                <i class="fa-solid fa-circle-question text-primary me-2"></i> Apakah komentar berita dapat dimoderasi terlebih dahulu?
            </button>
        </h2>
        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3">
            <div class="accordion-body text-muted" style="line-height: 1.7;">
                Ya. Semua komentar yang masuk dapat ditinjau melalui jendela <strong>Moderasi Komentar</strong>. Administrator dapat menyetujui, menandai sebagai spam, membalas, atau menghapus komentar dengan mudah.
            </div>
        </div>
    </div>

    <div class="accordion-item border-0">
        <h2 class="accordion-header" id="faqHeading4">
            <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                <i class="fa-solid fa-circle-question text-primary me-2"></i> Bagaimana menghubungi dukungan teknis?
            </button>
        </h2>
        <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4">
            <div class="accordion-body text-muted" style="line-height: 1.7;">
                Silakan kunjungi halaman <strong>Hubungi Kami</strong> atau kirim email ke alamat resmi dukungan kami di support@syntaxcore.test. Tim kami siap merespons dalam waktu 1x24 jam kerja.
            </div>
        </div>
    </div>
</div>`,

            contact: `<div class="row g-5 my-4">
    <div class="col-12 col-lg-5">
        <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-2">Terhubung Bersama Kami</span>
        <h2 class="fw-bold text-dark mb-3">Mari Bicarakan Ide atau Pertanyaan Anda</h2>
        <p class="text-muted mb-4">Kami siap mendengarkan kebutuhan Anda dan membantu menemukan solusi terbaik bagi organisasi Anda.</p>

        <div class="d-flex flex-column gap-3">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                <div class="bg-primary text-white p-2 rounded-3"><i class="fa-solid fa-location-dot fa-lg"></i></div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Alamat Kantor</h6>
                    <small class="text-muted">Gedung SyntaxCore Tech Center, Lt. 5, Jakarta Selatan</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                <div class="bg-success text-white p-2 rounded-3"><i class="fa-solid fa-envelope fa-lg"></i></div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Email Resmi</h6>
                    <small class="text-muted">contact@syntaxcore.test / info@syntaxcore.test</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                <div class="bg-info text-white p-2 rounded-3"><i class="fa-solid fa-phone fa-lg"></i></div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Telepon &amp; WhatsApp</h6>
                    <small class="text-muted">+62 (21) 555-0199 / +62 812-3456-7890</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 bg-white">
            <h4 class="fw-bold text-dark mb-3">Kirim Pesan Langsung</h4>
            <form onsubmit="event.preventDefault(); alert('Terima kasih! Pesan Anda telah diterima.');">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Nama Lengkap</label>
                        <input type="text" class="form-control" placeholder="Nama Anda" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold">Alamat Email</label>
                        <input type="email" class="form-control" placeholder="nama@email.com" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Subjek</label>
                        <input type="text" class="form-control" placeholder="Topik pembicaraan" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Pesan Anda</label>
                        <textarea class="form-control" rows="4" placeholder="Tuliskan detail pertanyaan atau kebutuhan Anda..." required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary px-4 py-2 shadow-sm">
                            <i class="fa-solid fa-paper-plane me-2"></i> Kirim Pesan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>`,

            policy: `<div class="my-3">
    <div class="border-bottom pb-3 mb-4">
        <span class="badge bg-secondary-subtle text-secondary px-3 py-1 mb-2">Dokumen Resmi</span>
        <h2 class="fw-bold text-dark mb-1">Kebijakan Privasi &amp; Ketentuan Layanan</h2>
        <small class="text-muted">Terakhir diperbarui: 12 September 2026</small>
    </div>

    <div class="p-3 bg-light rounded-3 mb-4 border small text-muted">
        <strong>Pemberitahuan:</strong> Harap membaca ketentuan dan kebijakan privasi ini dengan seksama sebelum menggunakan layanan kami. Penggunaan situs ini menandakan persetujuan Anda terhadap seluruh syarat yang berlaku.
    </div>

    <h4 class="fw-bold text-dark mt-4 mb-2">1. Pengumpulan Informasi</h4>
    <p class="text-muted">Kami mengumpulkan informasi yang Anda berikan secara langsung saat berinteraksi dengan situs, seperti nama, alamat email, dan konten komentar publik. Kami juga mencatat data teknis standar seperti alamat IP dan preferensi browser untuk keperluan keamanan serta pencegahan spam.</p>

    <h4 class="fw-bold text-dark mt-4 mb-2">2. Penggunaan Informasi</h4>
    <p class="text-muted">Data yang kami kumpulkan dipergunakan semata-mata untuk memvalidasi interaksi pengguna, meningkatkan kinerja sistem, dan melindungi website dari upaya penyalahgunaan atau serangan siber.</p>

    <h4 class="fw-bold text-dark mt-4 mb-2">3. Perlindungan &amp; Keamanan Data</h4>
    <p class="text-muted">Kami menerapkan standar enkripsi dan kontrol akses bertingkat untuk memastikan data pribadi Anda tersimpan secara aman dan tidak diperjualbelikan kepada pihak ketiga manapun.</p>

    <h4 class="fw-bold text-dark mt-4 mb-2">4. Hak Pengguna</h4>
    <p class="text-muted">Setiap pengguna memiliki hak untuk meminta pembaruan atau penghapusan data pribadi yang tersimpan dalam sistem kami melalui kontak resmi yang tercantum di halaman ini.</p>
</div>`
        };
    }

    /**
     * Hapus instance TinyMCE pada textarea
     * 
     * @param {string} textareaId
     */
    destroyRichEditor(textareaId) {
        if (typeof window.tinymce !== 'undefined') {
            const editor = window.tinymce.get(textareaId);
            if (editor) {
                editor.remove();
            }
        }
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