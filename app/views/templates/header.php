<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="author" content="cs.baguosps@gmail.com">
    <title><?= $data['title']; ?> - <?= htmlspecialchars($data['app_name'] ?? 'WAGTW') ?></title>
    <?php if (!empty($data['app_logo'])): ?>
    <link rel="icon" href="<?= BASEURL ?>/uploads/branding/<?= $data['app_logo'] ?>">
    <link rel="apple-touch-icon" href="<?= BASEURL ?>/uploads/branding/<?= $data['app_logo'] ?>">
    <?php endif; ?>
    <!-- Tailwind CSS (Local Offline) -->
    <script src="<?= BASEURL ?>/js/tailwindcss.js"></script>

    <!-- Core Libraries: jQuery, SheetJS, DataTables & SweetAlert2 (Loaded early for view scripts) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #10B981;
            --brand-hover: #059669;
        }
        body { font-family: 'Inter', sans-serif; background-color: #F8FAFC; color: #1e293b; }
        
        /* Modern Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        /* Hide scrollbars for pill tabs & clean touch horizontal scroll */
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        .touch-scroll { -webkit-overflow-scrolling: touch; }
        
        /* DataTables Customization */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: var(--brand-primary) !important; color: white !important; border: none; border-radius: 0.5rem; font-weight: 600; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4); }
        .dataTables_wrapper .dataTables_length select { padding-right: 2.5rem; border-radius: 0.5rem; border-color: #e2e8f0; }
        .dataTables_wrapper .dataTables_filter input { border-radius: 0.5rem; border: 1px solid #e2e8f0; padding: 0.375rem 0.75rem; outline: none; transition: border-color 0.2s; }
        .dataTables_wrapper .dataTables_filter input:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2); }
        
        /* Top Progress Bar SPA Loader */
        #topProgressBar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(to right, #34d399, #10b981, #059669);
            z-index: 10000;
            width: 0%;
            opacity: 0;
            transition: width 0.3s ease, opacity 0.3s ease;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.7);
        }

        /* Live Sync & Notification Styles */
        @keyframes livePulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.15); }
        }
        .animate-live-pulse {
            animation: livePulse 2s infinite ease-in-out;
        }

        @keyframes highlightFade {
            0% { background-color: rgba(16, 185, 129, 0.25); }
            100% { background-color: transparent; }
        }
        .highlight-new-row {
            animation: highlightFade 3s ease-out;
        }

        /* Toast Notification Container */
        #wagtwToastContainer {
            position: fixed;
            top: 1.25rem;
            right: 1.25rem;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            pointer-events: none;
            max-width: 24rem;
            width: 100%;
        }
        .wagtw-toast {
            pointer-events: auto;
            transform: translateX(110%);
            opacity: 0;
            transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
        }
        .wagtw-toast.show {
            transform: translateX(0);
            opacity: 1;
        }
    </style>
    <script>const BASEURL = '<?= BASEURL; ?>';</script>
</head>
<body class="bg-gray-50 flex overflow-hidden h-screen text-gray-800">

    <div id="topProgressBar"></div>
    <div id="wagtwToastContainer"></div>
