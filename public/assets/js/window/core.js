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
                <header class="py-2 px-4 bg-dark text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>activities</span>
                        <span style="font-size: 12px;">${this.state.initializedAt.toLocaleTimeString()}</span>
                        <span>icon</span>
                    </div>
                </header>
                ` : ''}
                
                <main id="wd-workspace" class="wd-workspace flex-grow-1 position-relative overflow-hidden">
                    <div id="wd-snap-preview" class="wd-snap-preview d-none"></div>
                </main>

                ${(this.options.footer.active) ? `
                <footer class="bg-primary-subtle border-top">
                    <div class="d-flex align-items-stretch gap-1 h-100">
                        <div id="wp-menu" class="wp-menu" style="padding: ${this.options.footer.icons.ypadding ?? '0px'} ${this.options.footer.icons.xpadding ?? '0px'};">
                            <i class="fa-brands fa-microsoft d-block" style="font-size: ${this.options.footer.icons.dimension ?? '0px'};"></i>
                            ${this.options.footer.icons.text ?? ''}
                        </div>
                        <div class="d-flex align-items-stretch gap-1 h-100" id="wd-active-content">
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

        // Deteksi apakah ini modul Master Pengguna atau Data Peran (Roles)
        const isUsersModule = (item.action === 'open_users') || (item.route === '/admin/users');
        const isRolesModule = (item.action === 'open_roles') || (item.route === '/admin/roles');
        const defaultWidth = isUsersModule ? 780 : (isRolesModule ? 840 : 440);
        const defaultHeight = isUsersModule ? 520 : (isRolesModule ? 560 : 250);

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

        // Jika modul Manajemen Pengguna atau Peran, render antarmuka masing-masing
        if (isUsersModule) {
            this.renderUserManagement(winEl);
        } else if (isRolesModule) {
            this.renderRoleManagement(winEl);
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