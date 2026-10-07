<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title']; ?></title>
    <meta name="description" content="Login ke WAGTW - WhatsApp Gateway Platform">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script>const BASEURL = '<?= BASEURL; ?>';</script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0a0e1a;
            overflow: hidden;
            position: relative;
        }

        /* ── Animated Background ── */
        .bg-orbs {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: floatOrb 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, #25d366, #128c7e);
            top: -100px; left: -100px;
            animation-delay: 0s;
        }
        .orb-2 {
            width: 320px; height: 320px;
            background: radial-gradient(circle, #00b4d8, #0077b6);
            bottom: -80px; right: -80px;
            animation-delay: -3s;
        }
        .orb-3 {
            width: 250px; height: 250px;
            background: radial-gradient(circle, #7b2ff7, #4a0080);
            top: 50%; right: 15%;
            animation-delay: -5s;
        }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(20px, -20px) scale(1.05); }
            66% { transform: translate(-15px, 15px) scale(0.97); }
        }

        /* Grid overlay */
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 0;
        }

        /* ── Card ── */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 16px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.10);
            border-radius: 24px;
            padding: 44px 40px 40px;
            box-shadow:
                0 0 0 1px rgba(37, 211, 102, 0.08),
                0 32px 64px rgba(0,0,0,0.5),
                inset 0 1px 0 rgba(255,255,255,0.08);
        }

        /* ── Logo ── */
        .logo-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 36px;
        }
        .logo-icon {
            width: 68px; height: 68px;
            border-radius: 20px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 18px;
            box-shadow: 0 8px 32px rgba(37, 211, 102, 0.35);
            position: relative;
            overflow: hidden;
        }
        .logo-icon::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 60%);
        }
        .logo-icon svg {
            width: 36px; height: 36px;
            color: #fff;
            position: relative; z-index: 1;
        }
        .logo-title {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            line-height: 1;
        }
        .logo-title span { color: #25d366; }
        .logo-sub {
            font-size: 13px;
            color: rgba(255,255,255,0.45);
            margin-top: 6px;
            font-weight: 400;
            letter-spacing: 0.2px;
        }

        /* ── Form ── */
        .form-group {
            margin-bottom: 18px;
        }
        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .input-wrap {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.3);
            transition: color 0.2s;
            pointer-events: none;
        }
        .input-wrap:focus-within .input-icon { color: #25d366; }

        .form-input {
            width: 100%;
            padding: 13px 14px 13px 44px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 12px;
            color: #fff;
            font-size: 14.5px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            outline: none;
            transition: all 0.25s ease;
            letter-spacing: 0.2px;
        }
        .form-input::placeholder { color: rgba(255,255,255,0.25); font-weight: 400; }
        .form-input:focus {
            background: rgba(37, 211, 102, 0.07);
            border-color: rgba(37, 211, 102, 0.5);
            box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.12);
        }
        .form-input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 100px #111827 inset;
            -webkit-text-fill-color: #fff;
        }

        /* Toggle password */
        .toggle-pass {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: rgba(255,255,255,0.3);
            padding: 4px;
            transition: color 0.2s;
            line-height: 0;
        }
        .toggle-pass:hover { color: rgba(255,255,255,0.7); }

        /* ── Submit Button ── */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
            box-shadow: 0 4px 24px rgba(37, 211, 102, 0.3);
            letter-spacing: 0.2px;
            position: relative;
            overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.25s;
        }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 32px rgba(37, 211, 102, 0.4);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Spinner */
        .spinner {
            width: 18px; height: 18px;
            border: 2.5px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Divider footer ── */
        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 12px;
            color: rgba(255,255,255,0.25);
            letter-spacing: 0.3px;
        }
        .login-footer span { color: #25d366; font-weight: 600; }

        /* Badge */
        .secure-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(37,211,102,0.08);
            border: 1px solid rgba(37,211,102,0.2);
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            color: rgba(37,211,102,0.8);
            font-weight: 500;
            margin-bottom: 6px;
        }
        .secure-badge svg { width: 12px; height: 12px; }

        /* fade-in animation */
        .login-card {
            animation: fadeUp 0.5s ease both;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* SweetAlert2 dark override */
        .swal2-popup { background: #161c2d !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.08) !important; border-radius: 16px !important; }
        .swal2-title { color: #fff !important; }
        .swal2-html-container { color: rgba(255,255,255,0.65) !important; }
        .swal2-confirm { background: linear-gradient(135deg, #25d366, #128c7e) !important; border-radius: 8px !important; }
    </style>
</head>
<body>

    <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>
    <div class="bg-grid"></div>

    <div class="login-wrapper">
        <div class="login-card">

            <!-- Logo & Title -->
            <div class="logo-wrap">
                <?php if (!empty($data['app_logo'])): ?>
                <div class="logo-icon" style="background: transparent; box-shadow: none; padding: 0;">
                    <img src="<?= BASEURL ?>/uploads/branding/<?= $data['app_logo'] ?>" style="width: 100%; height: 100%; object-fit: contain;" alt="Logo">
                </div>
                <?php else: ?>
                <div class="logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>
                    </svg>
                </div>
                <?php endif; ?>
                <div class="secure-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd"/>
                    </svg>
                    Secured Login
                </div>
                <div class="logo-title"><?= htmlspecialchars($data['app_name'] ?? 'WAGTW Gateway') ?></div>
                <div class="logo-sub">WhatsApp Gateway Platform</div>
            </div>


            <!-- Form -->
            <form id="formLogin" method="POST" autocomplete="off">

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="8" r="5"/><path d="M3 21a9 9 0 0 1 18 0"/>
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Masukkan username"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-pass" id="togglePass" title="Tampilkan password">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" id="btnLogin" class="btn-login">
                    <span id="btnText">Masuk</span>
                    <div class="spinner" id="btnLoader"></div>
                </button>

            </form>

            <div class="login-footer">
    &copy; <?= date('Y'); ?> <span>by BaguosPS</span> &mdash; All rights reserved
    <br>
    <a href="https://wa.me/6285157265534?text=min%20lupa%20password" target="_blank" rel="noopener noreferrer" class="btn-wa">
        Lupa password?
    </a>
</div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
    $(document).ready(function() {

        // Toggle show/hide password
        $('#togglePass').on('click', function() {
            const input = $('#password');
            const icon  = $('#eyeIcon');
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.html('<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>');
            } else {
                input.attr('type', 'password');
                icon.html('<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>');
            }
        });

        // Handle Login Submit
        $('#formLogin').on('submit', function(e) {
            e.preventDefault();

            const btn    = $('#btnLogin');
            const loader = $('#btnLoader');
            const text   = $('#btnText');

            btn.prop('disabled', true);
            text.text('Memproses...');
            loader.css('display', 'block');

            $.ajax({
                url: BASEURL + '/auth/doLogin',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message,
                            showConfirmButton: false,
                            timer: 1200,
                            background: '#161c2d',
                            color: '#fff'
                        }).then(() => { window.location.href = res.redirect; });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: res.message,
                            background: '#161c2d',
                            color: '#fff',
                            confirmButtonText: 'Coba Lagi'
                        });
                        btn.prop('disabled', false);
                        text.text('Masuk');
                        loader.css('display', 'none');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Terjadi kesalahan sistem. Coba lagi.',
                        background: '#161c2d',
                        color: '#fff'
                    });
                    btn.prop('disabled', false);
                    text.text('Masuk');
                    loader.css('display', 'none');
                }
            });
        });

        // Auto-focus
        $('#username').focus();
    });
    
    </script>
</body>
</html>
