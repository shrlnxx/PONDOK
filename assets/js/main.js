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
    // Inject Custom CSS
    if (!document.getElementById('custom-css')) {
        const link = document.createElement('link');
        link.id = 'custom-css';
        link.rel = 'stylesheet';
        link.href = 'assets/css/tailwind-custom.css';
        document.head.appendChild(link);
    }

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
        <div class="flex min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900">
            <!-- Sidebar -->
            <nav id="sidebar" class="fixed lg:sticky top-0 left-0 h-screen w-64 bg-slate-800/70 backdrop-blur-xl border-r border-white/10 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50 flex flex-col">
                <!-- Close Button For Mobile -->
                <button class="lg:hidden absolute top-4 right-4 text-white text-2xl hover:text-indigo-400 transition-colors no-print" onclick="document.getElementById('sidebar').classList.remove('active')">
                    <i class='bx bx-x'></i>
                </button>

                <!-- Logo & Brand -->
                <div class="p-6 border-b border-white/10">
                    <div class="flex items-center gap-3 mb-2">
                        <i class='bx bxs-shield-plus text-4xl text-indigo-400'></i>
                        <div class="leading-tight">
                            <div class="text-xs text-slate-400 uppercase tracking-wider">Sistem Keamanan</div>
                            <div class="text-lg font-extrabold text-white">PPS SHIROTHUL<br>FUQOHA</div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Links -->
                <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <a href="dashboard.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'dashboard' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-dashboard text-xl'></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="input.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'input' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-edit text-xl'></i>
                        <span>Input Pelanggaran</span>
                    </a>
                    <a href="riwayat.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'riwayat' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-time-five text-xl'></i>
                        <span>Riwayat</span>
                    </a>
                    <a href="santri.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'santri' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-user-detail text-xl'></i>
                        <span>Data Santri</span>
                    </a>
                    <a href="laporan.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'laporan' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-report text-xl'></i>
                        <span>Laporan</span>
                    </a>
                    <a href="users.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'users' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-group text-xl'></i>
                        <span>Petugas</span>
                    </a>
                    
                    <div class="my-2 border-t border-white/10"></div>
                    
                    <a href="perizinan.html" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-indigo-500/20 hover:text-white transition-all ${activePage === 'perizinan' ? 'bg-indigo-500/30 text-white font-semibold' : ''}">
                        <i class='bx bxs-door-open text-xl'></i>
                        <span>Perizinan</span>
                    </a>
                </div>

                <!-- Logout Button -->
                <div class="p-3 border-t border-white/10">
                    <a href="#" onclick="doLogout()" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/20 hover:text-red-300 transition-all">
                        <i class='bx bx-log-out text-xl'></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>

            <!-- Mobile Overlay -->
            <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden hidden" onclick="document.getElementById('sidebar').classList.remove('active'); this.classList.add('hidden')"></div>

            <!-- Main Content -->
            <main class="flex-1 p-4 md:p-6 lg:p-8 overflow-x-hidden">
                <!-- Header -->
                <header class="flex items-center justify-between mb-6 md:mb-8">
                    <div class="flex items-center gap-4">
                        <!-- Mobile Menu Button -->
                        <button class="lg:hidden text-white text-2xl hover:text-indigo-400 transition-colors" onclick="document.getElementById('sidebar').classList.add('active'); document.getElementById('sidebar-overlay').classList.remove('hidden')">
                            <i class='bx bx-menu'></i>
                        </button>
                        
                        <div>
                            <h2 id="pageTitle" class="text-2xl md:text-3xl font-bold text-white mb-1">Halaman</h2>
                            <p id="pageSubtitle" class="text-sm md:text-base text-slate-400">Selamat datang kembali</p>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                ${content}
            </main>
        </div>

        <style>
            #sidebar.active {
                transform: translateX(0);
            }
        </style>
        `;
}

// 4. Logout
async function doLogout() {
    if (confirm('Yakin ingin keluar?')) {
        await apiCall('auth.php?action=logout');
        window.location.href = 'index.html';
    }
}
