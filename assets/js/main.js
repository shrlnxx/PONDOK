/**
 * CORE JAVASCRIPT LOGIC (OFFICIAL VERSION)
 */
const API_BASE = 'api/';

// 1. Fetch Wrapper
async function apiCall(endpoint, method = 'GET', body = null) {
    const options = { method, headers: {} };
    if (body) {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(body);
    }
    try {
        const response = await fetch(API_BASE + endpoint, options);
        if (response.status === 401 && !window.location.href.includes('index.html')) {
            window.location.href = 'index.html';
            return null;
        }
        return await response.json();
    } catch (err) {
        console.error("API Error:", err);
        return null;
    }
}

// 2. Check Auth
async function checkAuth() {
    if (window.location.pathname.endsWith('index.html')) return;
    const res = await apiCall('auth.php?action=check');
    if (!res || res.status !== 'authenticated') {
        window.location.href = 'index.html';
    }
}

// 3. Render Sidebar
function renderLayout(activePage) {
    // Inject Boxicons
    if (!document.getElementById('boxicons-css')) {
        const link = document.createElement('link');
        link.id = 'boxicons-css';
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css';
        document.head.appendChild(link);
    }

    const app = document.getElementById('app');
    const content = app.innerHTML;

    app.innerHTML = `
        <div class="app-container">
            <nav class="sidebar">
                <!-- Close Button For Mobile -->
                <button class="sidebar-close no-print" onclick="document.querySelector('.sidebar').classList.remove('active')">
                    <i class='bx bx-x'></i>
                </button>

                <!-- LOGO & JUDUL RESMI -->
                <div class="brand" style="display:flex; flex-direction:column; align-items:flex-start; padding: 0 10px; height:auto; margin-bottom: 30px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:5px;">
                        <i class='bx bxs-shield-plus' style="font-size: 2.5rem; color: var(--primary);"></i>
                        <div style="line-height:1.2;">
                            <div style="font-size:0.8rem; letter-spacing:1px; color:rgba(255,255,255,0.7);">SISTEM KEAMANAN</div>
                            <div style="font-size:1.1rem; font-weight:800; color:white;">PPS SHIROTHUL<br>FUQOHA</div>
                        </div>
                    </div>
                </div>
                
                <div class="nav-links">
                    <a href="dashboard.html" class="nav-link ${activePage === 'dashboard' ? 'active' : ''}">
                        <i class='bx bxs-dashboard'></i> Dashboard
                    </a>
                    <a href="input.html" class="nav-link ${activePage === 'input' ? 'active' : ''}">
                        <i class='bx bxs-edit'></i> Input Pelanggaran
                    </a>
                    <a href="riwayat.html" class="nav-link ${activePage === 'riwayat' ? 'active' : ''}">
                        <i class='bx bxs-time-five'></i> Riwayat
                    </a>
                    <a href="santri.html" class="nav-link ${activePage === 'santri' ? 'active' : ''}">
                        <i class='bx bxs-user-detail'></i> Data Santri
                    </a>
                    <a href="laporan.html" class="nav-link ${activePage === 'laporan' ? 'active' : ''}">
                        <i class='bx bxs-report'></i> Laporan
                    </a>
                    <a href="users.html" class="nav-link ${activePage === 'users' ? 'active' : ''}">
                        <i class='bx bxs-group'></i> Petugas
                    </a>
                    
                    <div style="margin: 10px 0; border-top: 1px solid rgba(255,255,255,0.1);"></div>
                    <a href="perizinan.html" class="nav-link ${activePage === 'perizinan' ? 'active' : ''}">
                        <i class='bx bxs-door-open'></i> Perizinan
                    </a>
                </div>

                <a href="#" onclick="doLogout()" class="nav-link" style="color: #ef4444; margin-top: auto;">
                    <i class='bx bx-log-out'></i> Logout
                </a>
            </nav>

            <!-- Mobile Overlay -->
            <div class="sidebar-overlay" onclick="document.querySelector('.sidebar').classList.remove('active')"></div>

            <main class="main-content">
                <header class="flex justify-between mb-4" style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <!-- Mobile Burger -->
                        <button class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.add('active')">
                            <i class='bx bx-menu'></i>
                        </button>
                        
                        <div>
                            <h2 id="pageTitle" style="margin-bottom:5px">Halaman</h2>
                            <p class="text-muted" id="pageSubtitle">Selamat datang kembali</p>
                        </div>
                    </div>
                </header>
                ${content}
            </main>

        </div>
        `;
}

// 4. Logout
async function doLogout() {
    if (confirm('Yakin ingin keluar?')) {
        await apiCall('auth.php?action=logout');
        window.location.href = 'index.html';
    }
}
