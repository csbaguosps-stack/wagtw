<?php
$role = $data['role'];
$profile = $data['profile'];
?>

<div class="max-w-5xl space-y-6 pb-12">
    <!-- Header -->
    <div>
        <div class="flex items-center gap-3 mb-1">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-md">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Pengaturan Sistem</h2>
        </div>
        <p class="text-sm text-gray-500">Kelola profil Anda <?= ($role !== 'user') ? 'dan manajemen pengguna' : '' ?>.</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white p-2 rounded-2xl shadow-sm border border-gray-100 flex flex-wrap gap-2 w-fit">
        <button onclick="switchTab('profile')" id="tab-btn-profile" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all bg-blue-600 text-white shadow-md">
            Profil Saya
        </button>
        <?php if ($role !== 'user'): ?>
        <button onclick="switchTab('users')" id="tab-btn-users" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50">
            Manajemen Pengguna
        </button>
        <?php endif; ?>
        <?php if ($role === 'super_admin'): ?>
        <button onclick="switchTab('branding')" id="tab-btn-branding" class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Logo & Branding Web
        </button>
        <?php endif; ?>
    </div>


    <!-- TAB 1: PROFIL -->
    <div id="tab-content-profile" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50">
            <h3 class="text-lg font-bold text-gray-800">Profil Akun</h3>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-1 flex flex-col items-center gap-4">
                <?php 
                    $photoUrl = !empty($profile['profile_picture']) ? BASEURL . '/uploads/profiles/' . $profile['profile_picture'] : ''; 
                ?>
                <div class="relative w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden bg-gradient-to-tr from-blue-400 to-indigo-500 flex items-center justify-center group cursor-pointer" onclick="document.getElementById('photoInput').click()">
                    <?php if ($photoUrl): ?>
                    <img src="<?= $photoUrl ?>" id="profilePreview" class="w-full h-full object-cover">
                    <?php else: ?>
                    <span id="profileInitials" class="text-4xl font-bold text-white"><?= substr($profile['name'], 0, 1) ?></span>
                    <img src="" id="profilePreview" class="hidden w-full h-full object-cover absolute inset-0">
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                </div>
                <input type="file" id="photoInput" class="hidden" accept="image/jpeg, image/png, image/webp" onchange="uploadPhoto(this)">
                <p class="text-xs text-gray-500 text-center">Klik foto untuk mengubah.<br>Otomatis dikompres & dipotong.</p>
            </div>
            
            <div class="md:col-span-2">
                <form id="form-profile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($profile['name']) ?>" required class="w-full px-4 py-2.5 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required class="w-full px-4 py-2.5 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Username (Login)</label>
                        <input type="text" value="<?= htmlspecialchars($profile['username']) ?>" readonly class="w-full px-4 py-2.5 bg-gray-100 border border-gray-200 rounded-xl outline-none text-gray-500 cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru (Kosongkan jika tidak ingin diubah)</label>
                        <input type="password" name="password" placeholder="••••••••" class="w-full px-4 py-2.5 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none transition">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition mt-4">Simpan Profil</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: MANAJEMEN PENGGUNA -->
    <?php if ($role !== 'user'): ?>
    <div id="tab-content-users" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hidden">
        <div class="p-6 flex items-start justify-between border-b border-gray-50 flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800">Daftar Pengguna</h3>
                <p class="text-sm text-gray-400 mt-1">Kelola bawahan Anda <?= $role === 'super_admin' ? 'serta tetapkan tugas ke Admin' : '' ?>.</p>
            </div>
            <button onclick="openUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Tambah Pengguna
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 text-gray-500 text-sm">
                        <th class="py-4 px-6 font-semibold">Nama / Username</th>
                        <th class="py-4 px-6 font-semibold">Role</th>
                        <th class="py-4 px-6 font-semibold">Status</th>
                        <?php if ($role === 'super_admin'): ?>
                        <th class="py-4 px-6 font-semibold">Dikelola Oleh (Parent)</th>
                        <?php endif; ?>
                        <th class="py-4 px-6 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php if (empty($data['users'])): ?>
                    <tr><td colspan="5" class="py-8 text-center text-gray-400">Tidak ada pengguna bawahan.</td></tr>
                    <?php else: foreach ($data['users'] as $u): ?>
                    <tr id="u-row-<?= $u['id'] ?>" class="hover:bg-gray-50/30">
                        <td class="py-4 px-6">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($u['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars($u['username']) ?></div>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($u['role'] === 'super_admin') echo '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-md">SUPER ADMIN</span>';
                                  else if ($u['role'] === 'admin') echo '<span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs font-bold rounded-md">ADMIN</span>';
                                  else echo '<span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded-md">USER</span>'; ?>
                        </td>
                        <td class="py-4 px-6">
                            <?php if ($u['status'] === 'active') echo '<span class="text-green-600 font-semibold text-sm">Aktif</span>';
                                  else echo '<span class="text-red-500 font-semibold text-sm">Nonaktif</span>'; ?>
                        </td>
                        <?php if ($role === 'super_admin'): ?>
                        <td class="py-4 px-6 text-sm text-gray-600">
                            <?= $u['parent_name'] ? '<span class="text-indigo-600 font-semibold">'.$u['parent_name'].'</span>' : '<span class="text-gray-400 italic">Mandiri</span>' ?>
                        </td>
                        <?php endif; ?>
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-2">
                                <button onclick='editUser(<?= json_encode($u) ?>)' class="p-2 text-blue-500 hover:bg-blue-50 rounded-xl transition" title="Edit Pengguna"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                <?php if ($u['role'] === 'super_admin'): ?>
                                <span class="p-2 text-gray-300 cursor-not-allowed rounded-xl flex items-center" title="Super Admin dilindungi sistem dan tidak dapat dihapus">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <?php elseif ($u['id'] == ($_SESSION['user_id'] ?? 0)): ?>
                                <span class="p-2 text-gray-300 cursor-not-allowed rounded-xl flex items-center" title="Akun Anda sendiri tidak dapat dihapus">
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                </span>
                                <?php else: ?>
                                <button onclick='deleteUser(<?= $u['id'] ?>)' class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition" title="Hapus Pengguna"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role === 'super_admin'): 
        $appSettings = $data['app_settings'] ?? [];

        $appLogoFilename = $appSettings['app_logo'] ?? ($data['app_logo'] ?? '');
        $currentLogoUrl = !empty($appLogoFilename) ? BASEURL . '/uploads/branding/' . $appLogoFilename : '';
        $currentAppName = $appSettings['app_name'] ?? ($data['app_name'] ?? 'WAGTW Gateway');
    ?>
    <!-- TAB 3: LOGO & BRANDING APLIKASI (SUPER ADMIN) -->
    <div id="tab-content-branding" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden hidden">
        <div class="p-6 border-b border-gray-50 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    Pengaturan Logo & Branding Web
                </h3>
                <p class="text-sm text-gray-500 mt-1">Ubah thumbnail logo dan identitas platform yang tampil pada Sidebar, Header, dan Halaman Login.</p>
            </div>
            <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-lg border border-emerald-200">SUPER ADMIN ONLY</span>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Kolom Kiri: Upload Logo -->
            <div class="md:col-span-5 flex flex-col items-center text-center p-6 bg-slate-50/60 rounded-2xl border border-slate-200/80">
                <label class="block text-sm font-bold text-gray-700 mb-3">Thumbnail Logo Saat Ini</label>
                
                <!-- Logo Box with transparent pattern background -->
                <div class="relative w-40 h-40 rounded-2xl border-2 border-dashed border-gray-300 hover:border-emerald-500 transition-all p-3 flex items-center justify-center bg-white shadow-inner group cursor-pointer overflow-hidden" 
                     onclick="document.getElementById('logoInput').click()" 
                     title="Klik untuk memilih logo baru">
                    
                    <img src="<?= $currentLogoUrl ?>" id="logoPreviewImg" class="<?= empty($currentLogoUrl) ? 'hidden ' : '' ?>w-full h-full object-contain transition group-hover:scale-105" alt="App Logo">
                    
                    <div id="logoDefaultPlaceholder" class="<?= !empty($currentLogoUrl) ? 'hidden ' : '' ?>flex flex-col items-center justify-center text-gray-400">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white text-2xl font-black mb-2 shadow-md">
                            W
                        </div>
                        <span class="text-xs font-semibold text-gray-400">Belum ada logo</span>
                    </div>

                    <div class="absolute inset-0 bg-black/50 backdrop-blur-xs flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity text-white">
                        <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span class="text-xs font-bold">Ganti Logo</span>
                    </div>
                </div>

                <input type="file" id="logoInput" class="hidden" accept="image/png, image/jpeg, image/webp, image/svg+xml, image/gif" onchange="uploadLogoFile(this)">

                <div class="mt-4 space-y-2">
                    <button type="button" onclick="document.getElementById('logoInput').click()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-2 mx-auto">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Pilih File Logo
                    </button>
                    <p class="text-[11px] text-gray-400 leading-relaxed max-w-xs">
                        Mendukung format <b>PNG, JPG, WEBP, SVG</b>.<br>Disarankan background transparan dengan resolusi min. 256x256 px.
                    </p>
                </div>
            </div>

            <!-- Kolom Kanan: Nama Aplikasi & Live Preview -->
            <div class="md:col-span-7 space-y-6">
                <form id="form-branding" class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Platform / Brand Aplikasi</label>
                        <input type="text" name="app_name" id="input-app-name" value="<?= htmlspecialchars($currentAppName) ?>" required 
                               oninput="document.getElementById('previewBrandText').textContent = this.value || 'WAGTW Gateway'"
                               placeholder="Misal: WAGTW Gateway" 
                               class="w-full px-4 py-3 bg-gray-50 focus:bg-white border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-300 outline-none transition text-sm font-medium">
                        <p class="text-xs text-gray-400 mt-1">Nama ini akan menjadi judul utama di pojok kiri atas aplikasi dan halaman login.</p>
                    </div>

                    <!-- Live Sidebar Preview Box -->
                    <div class="p-4 rounded-2xl bg-slate-100/70 border border-slate-200">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">Simulasi Tampilan di Sidebar:</span>
                        <div class="h-16 bg-white border border-gray-200 rounded-xl px-4 flex items-center gap-3 shadow-xs">
                            <div class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-50 border border-gray-100 overflow-hidden shrink-0">
                                <img src="<?= $currentLogoUrl ?>" id="previewBrandLogoImg" class="<?= empty($currentLogoUrl) ? 'hidden ' : '' ?>w-full h-full object-contain" alt="Logo">
                                <div id="previewBrandLogoFallback" class="<?= !empty($currentLogoUrl) ? 'hidden ' : '' ?>w-full h-full bg-gradient-to-tr from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold text-xs">
                                    W
                                </div>
                            </div>
                            <span id="previewBrandText" class="text-base font-bold bg-clip-text text-transparent bg-gradient-to-r from-emerald-600 to-teal-600 truncate">
                                <?= htmlspecialchars($currentAppName) ?>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl transition shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan Perubahan Branding
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>



<!-- Modal User -->
<?php if ($role !== 'user'): ?>
<div id="modal-user" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md transform transition-all">
        <div class="flex items-center justify-between p-6 border-b border-gray-100">
            <h3 id="modal-user-title" class="text-lg font-bold text-gray-800">Tambah Pengguna</h3>
            <button onclick="document.getElementById('modal-user').classList.add('hidden')" class="text-gray-400 hover:bg-gray-50 rounded-full p-2 transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-user" class="p-6 space-y-4">
            <input type="hidden" name="id" id="u-id">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
                    <input type="text" name="name" id="u-name" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none text-sm transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="u-username" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none text-sm transition">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" name="email" id="u-email" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none text-sm transition">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password</label>
                <input type="password" name="password" id="u-password" placeholder="Biarkan kosong jika tidak diubah" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-300 outline-none text-sm transition">
                <span class="text-xs text-gray-400" id="u-pass-hint"></span>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <?php if ($role === 'super_admin'): ?>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role</label>
                    <select name="role" id="u-role" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm transition">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin" id="opt-super-admin" class="hidden">Super Admin (Terkunci)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Assign ke (Parent)</label>
                    <select name="parent_id" id="u-parent" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm transition">
                        <option value="">— Tidak Ada (Mandiri) —</option>
                        <?php foreach ($data['admins'] as $adm): ?>
                        <option value="<?= $adm['id'] ?>"><?= htmlspecialchars($adm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="role" value="user">
                <input type="hidden" name="parent_id" value="<?= $_SESSION['user_id'] ?>">
                <?php endif; ?>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                <select name="status" id="u-status" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none text-sm transition">
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                </select>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition mt-2">Simpan Pengguna</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function switchTab(tab) {
    const tabs = ['profile', 'users', 'branding'];
    tabs.forEach(t => {
        const btn = document.getElementById('tab-btn-' + t);
        const content = document.getElementById('tab-content-' + t);
        if (!btn || !content) return;
        
        if (t === tab) {
            btn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all bg-blue-600 text-white shadow-md';
            content.classList.remove('hidden');
        } else {
            btn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all text-gray-500 hover:bg-gray-50';
            content.classList.add('hidden');
        }
    });
}


document.getElementById('form-profile').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fetch(BASEURL + '/setting/updateProfile', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => window.reloadCurrentPage(), 1500);
            } else Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        });
});

function uploadPhoto(input) {
    if (!input.files || !input.files[0]) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    
    Swal.fire({ title: 'Mengunggah...', text: 'Sedang memproses dan mengompres foto', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    fetch(BASEURL + '/setting/uploadPhoto', { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => window.reloadCurrentPage(), 1500);
            } else Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        });
}

<?php if ($role !== 'user'): ?>
function openUserModal() {
    document.getElementById('form-user').reset();
    document.getElementById('u-id').value = '';
    document.getElementById('u-password').required = true;
    document.getElementById('u-pass-hint').textContent = 'Wajib diisi untuk user baru.';
    document.getElementById('modal-user-title').textContent = 'Tambah Pengguna';

    const optSuper = document.getElementById('opt-super-admin');
    if (optSuper) optSuper.classList.add('hidden');
    const roleSelect = document.getElementById('u-role');
    if (roleSelect) {
        roleSelect.disabled = false;
        roleSelect.value = 'user';
    }
    const statusSelect = document.getElementById('u-status');
    if (statusSelect) {
        statusSelect.disabled = false;
        statusSelect.value = 'active';
    }
    const parentSelect = document.getElementById('u-parent');
    if (parentSelect) {
        parentSelect.disabled = false;
        parentSelect.value = '';
    }

    document.getElementById('modal-user').classList.remove('hidden');
}

function editUser(u) {
    document.getElementById('form-user').reset();
    document.getElementById('u-id').value = u.id;
    document.getElementById('u-name').value = u.name;
    document.getElementById('u-username').value = u.username;
    document.getElementById('u-email').value = u.email;

    const optSuper = document.getElementById('opt-super-admin');
    const roleSelect = document.getElementById('u-role');
    const statusSelect = document.getElementById('u-status');
    const parentSelect = document.getElementById('u-parent');

    if (u.role === 'super_admin') {
        if (optSuper) optSuper.classList.remove('hidden');
        if (roleSelect) {
            roleSelect.value = 'super_admin';
            roleSelect.disabled = true; // Role Super Admin tidak dapat diubah
        }
        if (statusSelect) {
            statusSelect.value = 'active';
            statusSelect.disabled = true; // Super Admin tidak dapat dinonaktifkan
        }
        if (parentSelect) {
            parentSelect.value = '';
            parentSelect.disabled = true;
        }
    } else {
        if (optSuper) optSuper.classList.add('hidden');
        if (roleSelect) {
            roleSelect.disabled = false;
            roleSelect.value = u.role;
        }
        if (statusSelect) {
            statusSelect.disabled = false;
            statusSelect.value = u.status;
        }
        if (parentSelect) {
            parentSelect.disabled = false;
            parentSelect.value = u.parent_id || '';
        }
    }
    
    document.getElementById('u-password').required = false;
    document.getElementById('u-pass-hint').textContent = 'Biarkan kosong jika tidak ingin mengubah password.';
    document.getElementById('modal-user-title').textContent = (u.role === 'super_admin') ? 'Edit Super Admin' : 'Edit Pengguna';
    document.getElementById('modal-user').classList.remove('hidden');
}

document.getElementById('form-user').addEventListener('submit', function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    
    // Pastikan field yang didisable (super admin) tetap terkirim nilainya
    const roleSelect = document.getElementById('u-role');
    const statusSelect = document.getElementById('u-status');
    if (roleSelect && roleSelect.disabled) fd.append('role', roleSelect.value);
    if (statusSelect && statusSelect.disabled) fd.append('status', statusSelect.value);

    const url = fd.get('id') ? '/setting/editUser' : '/setting/addUser';
    
    fetch(BASEURL + url, { method: 'POST', body: fd })
        .then(r => r.json()).then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => window.reloadCurrentPage(), 1500);
            } else Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
        });
});

function deleteUser(id) {
    Swal.fire({
        title: 'Hapus Pengguna?', text: 'Pengguna beserta datanya akan ikut terhapus.', icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData(); fd.append('id', id);
        fetch(BASEURL + '/setting/deleteUser', { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                if (res.status === 'success') {
                    document.getElementById('u-row-' + id).remove();
                    Swal.fire('Terhapus!', res.message, 'success');
                } else Swal.fire('Gagal!', res.message, 'error');
            });
    });
}
<?php endif; ?>

<?php if ($role === 'super_admin'): ?>
function uploadLogoFile(input) {

    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    const fd = new FormData();
    fd.append('logo', file);

    Swal.fire({
        title: 'Mengunggah Logo...',
        text: 'Sedang memproses dan mengompres file logo',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    fetch(BASEURL + '/setting/uploadLogo', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            // Update preview images
            const previewImg = document.getElementById('logoPreviewImg');
            const fallback = document.getElementById('logoDefaultPlaceholder');
            const sidebarPreview = document.getElementById('previewBrandLogoImg');
            const sidebarFallback = document.getElementById('previewBrandLogoFallback');

            if (previewImg) {
                previewImg.src = res.logo_url + '?v=' + new Date().getTime();
                previewImg.classList.remove('hidden');
            }
            if (fallback) fallback.classList.add('hidden');

            if (sidebarPreview) {
                sidebarPreview.src = res.logo_url + '?v=' + new Date().getTime();
                sidebarPreview.classList.remove('hidden');
            }
            if (sidebarFallback) sidebarFallback.classList.add('hidden');

            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            });

            // Reload after short delay so header and sidebar update globally
            setTimeout(() => location.reload(), 1500);
        } else {
            Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
        }
    })
    .catch(() => {
        Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan saat mengunggah logo.' });
    });
}

var formBranding = document.getElementById('form-branding');
if (formBranding) {
    formBranding.addEventListener('submit', function(e) {
        e.preventDefault();
        const fd = new FormData(this);

        fetch(BASEURL + '/setting/updateBranding', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                setTimeout(() => location.reload(), 1500);
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message });
            }
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan jaringan.' });
        });
    });
}
<?php endif; ?>
</script>

