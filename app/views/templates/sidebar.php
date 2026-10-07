    <!-- Sidebar Overlay for Mobile -->
    <div id="mobileSidebarOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>
    
    <!-- Sidebar -->
    <?php
        $sidebarLogo = !empty($data['app_logo']) ? BASEURL . '/uploads/branding/' . $data['app_logo'] : '';
        $sidebarAppName = !empty($data['app_name']) ? htmlspecialchars($data['app_name']) : 'WAGTW Gateway';

        // Detect current active menu from URL or Request URI
        $currentUrl = strtolower($_GET['url'] ?? '');
        $currentSegment = explode('/', trim($currentUrl, '/'))[0];
        if (empty($currentSegment)) {
            $reqUri = strtolower($_SERVER['REQUEST_URI'] ?? '');
            if (strpos($reqUri, '/device') !== false) $currentSegment = 'device';
            elseif (strpos($reqUri, '/autoreply') !== false) $currentSegment = 'autoreply';
            elseif (strpos($reqUri, '/broadcast') !== false) $currentSegment = 'broadcast';
            elseif (strpos($reqUri, '/messagehistory') !== false) $currentSegment = 'messagehistory';
            elseif (strpos($reqUri, '/webhook') !== false) $currentSegment = 'webhook';
            elseif (strpos($reqUri, '/aiserver') !== false) $currentSegment = 'aiserver';
            elseif (strpos($reqUri, '/aitemplate') !== false) $currentSegment = 'aitemplate';
            elseif (strpos($reqUri, '/setting') !== false) $currentSegment = 'setting';
            else $currentSegment = 'dashboard';
        }
    ?>
    <aside id="mainSidebar" class="w-64 bg-white border-r border-gray-100 flex flex-col shadow-lg md:shadow-sm fixed md:relative inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-30">
        <div class="h-16 flex items-center justify-between px-5 border-b border-gray-100">
            <div class="flex items-center gap-2.5 min-w-0">
                <?php if ($sidebarLogo): ?>
                    <img src="<?= $sidebarLogo ?>" alt="Logo" class="w-8 h-8 rounded-lg object-contain shrink-0">
                <?php else: ?>
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white font-black text-sm shrink-0 shadow-sm">
                        W
                    </div>
                <?php endif; ?>
                <span class="text-base font-bold bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-600 truncate"><?= $sidebarAppName ?></span>
            </div>
            <button id="closeSidebarBtn" class="md:hidden text-gray-400 hover:text-gray-600 focus:outline-none shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-4 space-y-1.5">
            <?php $isDash = ($currentSegment === 'dashboard'); ?>
            <a href="<?= BASEURL; ?>/dashboard" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isDash ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="dashboard">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <?php $isDev = ($currentSegment === 'device'); ?>
            <a href="<?= BASEURL; ?>/device" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isDev ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="device">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                Devices
            </a>

            <?php $isAuto = ($currentSegment === 'autoreply'); ?>
            <a href="<?= BASEURL; ?>/autoreply" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isAuto ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="autoreply">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Autoreply
            </a>
            <?php $isBroad = ($currentSegment === 'broadcast'); ?>
            <a href="<?= BASEURL; ?>/broadcast" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isBroad ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="broadcast">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                Broadcast
            </a>
            <?php $isMsg = ($currentSegment === 'messagehistory'); ?>
            <a href="<?= BASEURL; ?>/messagehistory" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isMsg ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="messagehistory">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Message History
            </a>
            <?php $isHook = ($currentSegment === 'webhook'); ?>
            <a href="<?= BASEURL; ?>/webhook" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isHook ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="webhook">
                <svg class="w-5 h-5 mr-3 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Panduan Webhook
                <span class="ml-auto text-[10px] bg-cyan-100 text-cyan-700 font-bold px-1.5 py-0.5 rounded uppercase">API</span>
            </a>

            <?php $isAiTpl = ($currentSegment === 'aitemplate'); ?>
            <a href="<?= BASEURL; ?>/aitemplate" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isAiTpl ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="aitemplate">
                <svg class="w-5 h-5 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Template AI WA
                <span class="ml-auto text-[10px] bg-emerald-100 text-emerald-800 font-bold px-1.5 py-0.5 rounded uppercase">Baru</span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
            <?php $isAi = ($currentSegment === 'aiserver'); ?>
            <a href="<?= BASEURL; ?>/aiserver" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isAi ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="aiserver">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h16a2 2 0 012 2v10a2 2 0 01-2 2h-2m-4-8h.01M9 9h.01M15 9h.01"/>
                </svg>
                Server AI
            </a>
            <?php endif; ?>
            
            <?php $isSet = ($currentSegment === 'setting'); ?>
            <a href="<?= BASEURL; ?>/setting" class="nav-link flex items-center px-4 py-3 rounded-xl transition <?= $isSet ? 'bg-emerald-50 text-emerald-700 font-semibold active-nav' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 font-medium' ?>" data-target="setting">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Pengaturan
            </a>
        </nav>
        <div class="p-4 border-t border-gray-100 bg-gray-50/50">
            <a href="<?= BASEURL; ?>/auth/logout" class="flex items-center px-4 py-2.5 text-red-600 hover:bg-red-100 rounded-xl transition font-medium">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </a>
            <div class="mt-2.5 text-center">
                <span class="text-[10px] text-gray-400 font-medium tracking-tight">Coding by <a href="mailto:cs.baguosps@gmail.com" class="text-gray-500 hover:text-emerald-600 transition">cs.baguosps@gmail.com</a></span>
            </div>
        </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden w-full relative bg-[#F8FAFC]">
        <!-- Topbar -->
        <header class="h-16 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-6 z-10 sticky top-0">
            <div class="flex items-center">
                <button id="openSidebarBtn" class="md:hidden text-gray-500 hover:text-emerald-500 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-xl font-bold text-gray-800 ml-4 md:ml-0 tracking-tight" id="pageTitle"><?= $data['title']; ?></h1>
            </div>
            <div class="flex items-center gap-3">
                <!-- Live Auto-Reload Badge & Sound Toggle -->
                <div class="hidden sm:flex items-center gap-2">
                    <button id="toggleNotificationSound" class="p-2 rounded-xl text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors" title="Toggle Suara Notifikasi Pesan">
                        <svg id="soundIconOn" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
                        <svg id="soundIconOff" class="w-5 h-5 text-gray-400 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                    </button>
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200/60" title="Sistem otomatis memuat pesan & pembaruan baru via AJAX tanpa reload browser">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-live-pulse"></span>
                        <span class="text-xs font-bold text-emerald-700">Live Auto-Update</span>
                    </div>
                </div>

                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-700"><?= htmlspecialchars($_SESSION['name'] ?? 'Pengguna') ?></span>
                    <?php 
                        $roleLbl = 'User'; $roleColor = 'bg-gray-100 text-gray-700';
                        if(isset($_SESSION['role'])) {
                            if($_SESSION['role'] === 'super_admin') { $roleLbl = 'Super Admin'; $roleColor = 'bg-red-50 text-red-600'; }
                            else if($_SESSION['role'] === 'admin') { $roleLbl = 'Admin'; $roleColor = 'bg-emerald-50 text-emerald-600'; }
                        }
                    ?>
                    <span class="text-[10px] uppercase font-bold tracking-wider shadow-sm px-2 py-0.5 rounded-md <?= $roleColor ?>"><?= $roleLbl ?></span>
                </div>
                
                <?php if(!empty($_SESSION['profile_picture'])): ?>
                <div class="w-10 h-10 rounded-full shadow-md border-2 border-white overflow-hidden bg-white">
                    <img src="<?= BASEURL ?>/uploads/profiles/<?= $_SESSION['profile_picture'] ?>" class="w-full h-full object-cover">
                </div>
                <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-blue-400 to-indigo-500 flex justify-center items-center text-white font-bold shadow-md border-2 border-white">
                    <?= substr($_SESSION['name'] ?? 'A', 0, 1); ?>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Main View / AJAX Container -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 md:p-6 lg:p-8 w-full" id="ajaxContentProvider">
