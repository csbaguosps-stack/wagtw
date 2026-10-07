<?php
$devices = $data['devices'] ?? [];
$activeDev = !empty($devices) ? $devices[0] : null;
$defaultToken = $activeDev['token'] ?? 'wagtw_token_sample_12345678';
$defaultPhone = !empty($activeDev['phone']) ? $activeDev['phone'] : '6285100000000';
$apiSendUrl = BASEURL . '/api/send';
$apiStatusUrl = BASEURL . '/api/status';
?>

<div class="space-y-8 max-w-7xl mx-auto pb-12">

    <!-- ─── Hero Header & Device Selector ────────────────────────────────────── -->
    <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-gray-900 to-cyan-950 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-cyan-900/40">
        <div class="absolute -right-16 -top-16 w-80 h-80 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-80 h-80 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-3 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-400/20 text-cyan-300 text-xs font-semibold uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-ping"></span>
                    Dokumentasi API & Webhook v2.0
                </div>
                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight text-white">
                    Panduan Menghubungkan <span class="bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-emerald-400">Response Webhook</span>
                </h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                    Integrasikan WhatsApp Gateway WAGTW ke toko online, website, sistem CRM, notifikasi pesanan, atau aplikasi kustom Anda persis seperti alur <strong>Fonnte</strong>.
                </p>
            </div>

            <!-- Device Switcher Card -->
            <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 sm:p-5 border border-white/10 min-w-[280px] sm:min-w-[340px] shadow-lg">
                <label class="block text-xs font-semibold text-cyan-200 uppercase tracking-wider mb-2">Pilih Device untuk Snippet Kode</label>
                <?php if (!empty($devices)): ?>
                <select id="deviceSelector" class="w-full bg-slate-800/90 border border-cyan-500/30 text-white rounded-xl px-3 py-2.5 text-sm font-medium outline-none focus:ring-2 focus:ring-cyan-400 mb-3 transition">
                    <?php foreach ($devices as $idx => $d): ?>
                    <option value="<?= htmlspecialchars($d['id']) ?>" 
                            data-token="<?= htmlspecialchars($d['token']) ?>" 
                            data-phone="<?= htmlspecialchars($d['phone'] ?? '6285100000000') ?>"
                            data-webhook="<?= htmlspecialchars($d['webhook_url'] ?? '') ?>"
                            <?= $idx === 0 ? 'selected' : '' ?>>
                        <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['phone'] ? '+' . $d['phone'] : 'Belum Terhubung') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <p class="text-xs text-amber-300 mb-3">Belum ada device terdaftar. Silakan buat device di menu Devices terlebih dahulu.</p>
                <?php endif; ?>

                <div class="bg-slate-950/60 rounded-xl p-3 border border-slate-700/50">
                    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-1">
                        <span>Device Token Aktif:</span>
                        <button onclick="copyCurrentToken()" class="text-cyan-400 hover:text-cyan-300 font-medium transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Salin
                        </button>
                    </div>
                    <code id="displayTokenBadge" class="text-emerald-400 font-mono text-xs break-all block"><?= htmlspecialchars($defaultToken) ?></code>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Alur Kerja Webhook Diagram Card ──────────────────────────────────── -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
        <h2 class="text-lg font-bold text-gray-800 mb-2 flex items-center gap-2">
            <span class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-black">1</span>
            Bagaimana Alur Webhook Bekerja?
        </h2>
        <p class="text-sm text-gray-500 mb-6">Alur pertukaran data dua arah antara WhatsApp, Gateway WAGTW, dan server aplikasi Anda.</p>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Step 1 -->
            <div class="relative bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-sm mb-3 shadow-md shadow-emerald-500/20">
                    WA
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">1. Pesan WA Masuk</h3>
                <p class="text-xs text-gray-500">Pelanggan / user mengirimkan pesan WhatsApp ke nomor device Anda.</p>
                <div class="mt-auto pt-3 text-[11px] font-mono text-emerald-600 font-semibold">Incoming Message</div>
            </div>

            <!-- Step 2 -->
            <div class="relative bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-cyan-600 text-white flex items-center justify-center font-bold text-sm mb-3 shadow-md shadow-cyan-600/20">
                    HTTP
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">2. Gateway Kirim Webhook</h3>
                <p class="text-xs text-gray-500">WAGTW meneruskan payload JSON ke <code>Webhook URL</code> server Anda secara real-time.</p>
                <div class="mt-auto pt-3 text-[11px] font-mono text-cyan-600 font-semibold">POST JSON Payload</div>
            </div>

            <!-- Step 3 -->
            <div class="relative bg-slate-50 border border-slate-200 rounded-2xl p-4 flex flex-col">
                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-sm mb-3 shadow-md shadow-purple-600/20">
                    APP
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">3. Server Anda Memproses</h3>
                <p class="text-xs text-gray-500">Server Anda (PHP, Laravel, Node, dll) memeriksa pesan (cek order, database, AI, dll).</p>
                <div class="mt-auto pt-3 text-[11px] font-mono text-purple-600 font-semibold">Business Logic</div>
            </div>

            <!-- Step 4 -->
            <div class="relative bg-gradient-to-br from-cyan-50 to-emerald-50 border border-cyan-200 rounded-2xl p-4 flex flex-col shadow-sm">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-emerald-600 text-white flex items-center justify-center font-bold text-sm mb-3 shadow-md">
                    REPLY
                </div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">4. Balas Pesan (2 Cara)</h3>
                <p class="text-xs text-gray-600">
                    <strong>Cara A:</strong> Direct Return JSON <code>{"message":"..."}</code><br>
                    <strong>Cara B:</strong> Panggil REST API <code>POST /api/send</code>
                </p>
                <div class="mt-auto pt-3 text-[11px] font-mono text-emerald-700 font-bold">Auto-Send WhatsApp</div>
            </div>
        </div>
    </div>

    <!-- ─── Inbound Payload Section ─────────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Payload Card (Left) -->
        <div class="lg:col-span-6 bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-gray-100 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-cyan-100 text-cyan-700 flex items-center justify-center text-xs font-bold">2</span>
                    Payload Webhook Masuk
                </h2>
                <button onclick="copySnippet('payloadJsonCode')" class="text-xs text-gray-500 hover:text-gray-800 flex items-center gap-1 font-medium bg-gray-100 hover:bg-gray-200 px-2.5 py-1 rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Salin JSON
                </button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Format JSON yang dikirimkan WAGTW via HTTP POST ke Webhook URL Anda saat ada pesan WhatsApp masuk:</p>

            <div class="relative flex-1 bg-slate-900 rounded-xl p-4 overflow-x-auto text-xs font-mono text-cyan-300">
                <pre id="payloadJsonCode">{
  "event": "message",
  "device": "<span class="var-phone"><?= htmlspecialchars($defaultPhone) ?></span>",
  "sender": "6281234567890",
  "message": "Halo, cek status pesanan #INV1024",
  "name": "Budi Santoso",
  "is_group": false,
  "timestamp": <?= time() ?>,
  "wa_msg_id": "3EB09F21884C0123"
}</pre>
            </div>

            <!-- Parameters Table -->
            <div class="mt-4 border border-gray-100 rounded-xl overflow-hidden">
                <table class="w-full text-xs text-left">
                    <thead class="bg-gray-50 text-gray-500 font-semibold border-b border-gray-100">
                        <tr>
                            <th class="px-3 py-2">Field</th>
                            <th class="px-3 py-2">Tipe</th>
                            <th class="px-3 py-2">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-600">
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">event</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">Tipe event, bernilai <code>message</code></td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">device</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">Nomor WhatsApp perangkat gateway Anda</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">sender</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">Nomor WhatsApp pengirim (cth: 6281234567890)</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">message</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">Isi teks pesan yang dikirim oleh pengirim</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">name</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">Nama profil WhatsApp pengirim (pushName)</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">is_group</td>
                            <td class="px-3 py-2 text-gray-400">boolean</td>
                            <td class="px-3 py-2"><code>true</code> jika pesan berasal dari WhatsApp Group</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-mono font-bold text-cyan-600">wa_msg_id</td>
                            <td class="px-3 py-2 text-gray-400">string</td>
                            <td class="px-3 py-2">ID unik pesan dari WhatsApp</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2 Ways of Response (Right) -->
        <div class="lg:col-span-6 space-y-4 flex flex-col">
            <!-- Method A: Direct Webhook Response -->
            <div class="bg-gradient-to-br from-emerald-500/5 via-teal-500/5 to-cyan-500/5 rounded-2xl p-6 border border-emerald-200/80 shadow-sm flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                        Metode 1 • Paling Praktis (Auto-Reply)
                    </span>
                    <span class="text-xs font-semibold text-emerald-600">Fonnte Direct Response</span>
                </div>
                <h3 class="text-base font-bold text-gray-800 mb-1.5">Direct Response via Return JSON</h3>
                <p class="text-xs text-gray-600 mb-3 leading-relaxed">
                    Server webhook Anda <strong>cukup me-return response JSON</strong> saat WAGTW mengirimkan webhook. WAGTW akan langsung mendeteksi field <code>message</code> atau <code>reply</code> dan otomatis mengirimkannya kembali ke pengirim WA!
                </p>

                <div class="bg-slate-900 rounded-xl p-3.5 text-xs font-mono text-emerald-300 border border-slate-800">
                    <div class="text-slate-400 text-[10px] mb-1">// Response JSON yang dihasilkan oleh Webhook URL Anda:</div>
<pre>{
  "message": "Halo Budi! Pesanan #INV1024 sedang DIKIRIM kurir JNE."
}</pre>
                </div>
                <p class="text-[11px] text-emerald-700 mt-2 font-medium">✓ Tidak perlu cURL tambahan. Cepat, hemat resource, dan otomatis mengutip pesan user.</p>
            </div>

            <!-- Method B: REST API Endpoint -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200 shadow-sm flex-1">
                <div class="flex items-center justify-between mb-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-cyan-100 text-cyan-800 border border-cyan-300">
                        Metode 2 • Fleksibel Kapan Saja
                    </span>
                    <span class="text-xs font-semibold text-cyan-600">REST API Send</span>
                </div>
                <h3 class="text-base font-bold text-gray-800 mb-1.5">Kirim Pesan via REST API</h3>
                <p class="text-xs text-gray-600 mb-3 leading-relaxed">
                    Gunakan endpoint ini kapan saja dari website, toko online, atau cron job tanpa harus menunggu pesan masuk terlebih dahulu.
                </p>

                <div class="space-y-2">
                    <div class="flex items-center gap-2 bg-slate-100 rounded-lg p-2 text-xs font-mono">
                        <span class="bg-blue-600 text-white px-2 py-0.5 rounded font-bold">POST</span>
                        <span class="text-gray-800 flex-1 truncate"><?= htmlspecialchars($apiSendUrl) ?></span>
                        <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($apiSendUrl) ?>'); Swal.fire({icon:'success', title:'URL Disalin!', timer:1000, showConfirmButton:false});" class="text-gray-500 hover:text-gray-800 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    <div class="text-[11px] text-gray-500 bg-gray-50 p-2 rounded-lg border border-gray-100">
                        <strong>Header:</strong> <code class="text-indigo-600">Authorization: <span class="var-token"><?= htmlspecialchars($defaultToken) ?></span></code><br>
                        <strong>Body:</strong> <code class="text-emerald-600">target=08123456789&message=Halo</code>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Code Snippets Section ───────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-black">3</span>
                    Contoh Kode Integrasi Siap Pakai
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Pilih bahasa pemrograman favorit Anda untuk menyalin kode siap pakai.</p>
            </div>

            <!-- Tab Buttons -->
            <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl overflow-x-auto" id="codeTabs">
                <button onclick="switchTab('php')" class="tab-btn active px-3.5 py-1.5 rounded-lg text-xs font-bold transition bg-white text-gray-800 shadow-sm" data-tab="php">PHP Native</button>
                <button onclick="switchTab('laravel')" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 transition" data-tab="laravel">Laravel</button>
                <button onclick="switchTab('nodejs')" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 transition" data-tab="nodejs">Node.js</button>
                <button onclick="switchTab('python')" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 transition" data-tab="python">Python</button>
                <button onclick="switchTab('curl')" class="tab-btn px-3.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 transition" data-tab="curl">cURL</button>
            </div>
        </div>

        <div class="p-6 bg-slate-950">
            <!-- Header bar of code container -->
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800 text-xs text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                    <span id="tabFilename" class="ml-2 font-mono text-slate-300">webhook.php</span>
                </div>
                <button onclick="copyActiveTabCode()" class="flex items-center gap-1.5 px-3 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-300 rounded-lg text-xs font-semibold transition border border-slate-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Salin Kode
                </button>
            </div>

            <!-- TAB: PHP Native -->
            <div id="tab-content-php" class="tab-pane font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed">
<pre><code class="language-php">&lt;?php
/**
 * webhook.php - Endpoint Webhook WhatsApp (PHP Native)
 * Mendukung Direct Response JSON &amp; Kirim Pesan via API WAGTW
 */

header('Content-Type: application/json; charset=utf-8');

// 1. Tangkap Payload JSON yang dikirimkan oleh WAGTW
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['status' => false, 'message' => 'No data received']);
    exit;
}

// 2. Ekstrak data pesan masuk
$device    = $data['device']    ?? ''; // Nomor device Anda
$sender    = $data['sender']    ?? ''; // Nomor pengirim WhatsApp
$message   = trim($data['message'] ?? ''); // Teks pesan masuk
$name      = $data['name']      ?? 'Kak';
$isGroup   = $data['is_group']  ?? false;

// ── METODE 1: Direct Response (Auto-Reply Instan via Return JSON) ──────
// Cukup echo JSON dengan field 'message' atau 'reply'.
// WAGTW akan otomatis membalas ke pengirim tanpa perlu cURL tambahan!

if (strtolower($message) === 'ping') {
    echo json_encode([
        'message' => "Pong! Webhook server Anda aktif dan terhubung lancar. 🚀"
    ]);
    exit;
}

if (stripos($message, 'order') !== false) {
    echo json_encode([
        'message' => "Halo {$name}, pesanan Anda sedang kami proses di sistem. Mohon ditunggu ya! 🙏"
    ]);
    exit;
}

// ── METODE 2: Kirim Pesan Kapan Saja via REST API WAGTW ────────────────
function kirimPesanWA($target, $pesan) {
    $token = '<span class="var-token"><?= htmlspecialchars($defaultToken) ?></span>'; // Token Device Anda
    $url   = '<?= htmlspecialchars($apiSendUrl) ?>';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'target'  => $target,
            'message' => $pesan
        ]),
        CURLOPT_HTTPHEADER     => [
            "Authorization: {$token}"
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Jika tidak ada direct reply, return status OK
echo json_encode(['status' => true, 'message' => 'Event processed successfully']);
</code></pre>
            </div>

            <!-- TAB: Laravel -->
            <div id="tab-content-laravel" class="tab-pane hidden font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed">
<pre><code class="language-php">&lt;?php
// 1. Pada file routes/api.php
use App\Http\Controllers\WhatsAppWebhookController;

Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

// 2. Pada app/Http/Controllers/WhatsAppWebhookController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $sender  = $request->input('sender');
        $message = trim($request->input('message', ''));
        $name    = $request->input('name', 'Pelanggan');

        // METODE 1: Direct Response (WAGTW auto-reply dari return response JSON)
        if (strtolower($message) === 'cek') {
            return response()->json([
                'message' => "Halo {$name}, akun Anda aktif dan terdaftar di database Laravel kami."
            ]);
        }

        // METODE 2: Kirim Pesan via REST API Menggunakan Laravel Http Client
        if (stripos($message, 'tagihan') !== false) {
            Http::withHeaders([
                'Authorization' => '<span class="var-token"><?= htmlspecialchars($defaultToken) ?></span>',
            ])->asForm()->post('<?= htmlspecialchars($apiSendUrl) ?>', [
                'target'  => $sender,
                'message' => "Tagihan bulan ini untuk {$name}: Rp 150.000. Bayar sebelum tgl 20 ya!",
            ]);

            return response()->json(['status' => 'invoice_sent']);
        }

        return response()->json(['status' => 'received']);
    }
}
</code></pre>
            </div>

            <!-- TAB: Node.js -->
            <div id="tab-content-nodejs" class="tab-pane hidden font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed">
<pre><code class="language-javascript">/**
 * server.js (Express.js)
 * npm install express axios
 */
const express = require('express');
const axios   = require('axios');
const app     = express();

app.use(express.json());

// Endpoint Webhook Menerima Pesan dari WAGTW
app.post('/webhook', async (req, res) => {
    const { sender, message, name } = req.body;
    console.log(`Pesan masuk dari ${name} (+${sender}): ${message}`);

    // METODE 1: Direct Response JSON (WAGTW otomatis kirim balasan ke pengirim)
    if (message.toLowerCase() === 'halo') {
        return res.json({
            message: `Halo ${name || 'Kak'}! Ada yang bisa kami bantu dari Node.js backend? 😊`
        });
    }

    // METODE 2: Kirim pesan kapan saja via API WAGTW
    if (message.toLowerCase().includes('otp')) {
        await axios.post('<?= htmlspecialchars($apiSendUrl) ?>', {
            target: sender,
            message: `Kode verifikasi OTP Anda adalah: 9482. Rahasiakan dari siapapun.`
        }, {
            headers: {
                'Authorization': '<span class="var-token"><?= htmlspecialchars($defaultToken) ?></span>'
            }
        });
    }

    res.json({ status: true });
});

app.listen(3000, () => console.log('Webhook server running on port 3000'));
</code></pre>
            </div>

            <!-- TAB: Python -->
            <div id="tab-content-python" class="tab-pane hidden font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed">
<pre><code class="language-python"># app.py (Flask)
# pip install flask requests
from flask import Flask, request, jsonify
import requests

app = Flask(__name__)

DEVICE_TOKEN = '<span class="var-token"><?= htmlspecialchars($defaultToken) ?></span>'
API_SEND_URL = '<?= htmlspecialchars($apiSendUrl) ?>'

@app.route('/webhook', methods=['POST'])
def whatsapp_webhook():
    data = request.get_json() or {}
    sender  = data.get('sender', '')
    message = (data.get('message') or '').strip().lower()
    name    = data.get('name', 'Kak')

    # METODE 1: Direct Response JSON (WAGTW auto reply)
    if message == 'help':
        return jsonify({
            'message': f"Halo {name}! Ketik 'status' untuk cek akun, atau 'cs' untuk CS kami."
        })

    # METODE 2: Kirim Pesan via WAGTW REST API
    if 'info' in message:
        requests.post(API_SEND_URL, data={
            'target': sender,
            'message': 'Info promo hari ini: Diskon 25% produk elektronik!'
        }, headers={
            'Authorization': DEVICE_TOKEN
        })

    return jsonify({'status': 'ok'})

if __name__ == '__main__':
    app.run(port=5000, debug=True)
</code></pre>
            </div>

            <!-- TAB: cURL -->
            <div id="tab-content-curl" class="tab-pane hidden font-mono text-xs text-slate-200 overflow-x-auto leading-relaxed">
<pre><code class="language-bash"># Kirim Pesan WhatsApp via cURL (Terminal / Bash)
curl -X POST "<?= htmlspecialchars($apiSendUrl) ?>" \
  -H "Authorization: <span class="var-token"><?= htmlspecialchars($defaultToken) ?></span>" \
  -d "target=6281234567890" \
  -d "message=Halo! Ini pesan tes dari API Gateway WAGTW."

# Response Sukses:
# {
#   "status": true,
#   "message": "Pesan berhasil diproses",
#   "device": "<span class="var-phone"><?= htmlspecialchars($defaultPhone) ?></span>",
#   "results": [{"target":"6281234567890","status":true,"message":"Pesan berhasil dikirim"}]
# }
</code></pre>
            </div>
        </div>
    </div>

    <!-- ─── Live Webhook Tester Simulator ──────────────────────────────────── -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-gray-100">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black">4</span>
                    Live Webhook Tester (Simulator)
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Uji endpoint Webhook URL server Anda sebelum menghubungkannya ke sistem secara live.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-cyan-50 text-cyan-700 border border-cyan-200">
                <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
                Interactive Simulator
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Tester (Left) -->
            <form id="formTestWebhook" class="lg:col-span-6 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">URL Webhook Anda (Target Uji Coba)</label>
                    <input type="url" id="testWebhookUrl" required
                           placeholder="https://domain-anda.com/webhook.php" 
                           class="w-full text-xs px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-cyan-500 font-mono">
                    <p class="text-[11px] text-gray-400 mt-1">Gunakan URL yang dapat diakses publik (contoh: domain server Anda atau webhook.site).</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Pengirim Uji Coba</label>
                        <input type="text" id="testSender" value="6281234567890" 
                               class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-cyan-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Pengirim</label>
                        <input type="text" id="testName" value="Budi Santoso (Tester)" 
                               class="w-full text-xs px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Pesan Uji Coba</label>
                    <textarea id="testMessage" rows="2" 
                              class="w-full text-xs p-3 bg-gray-50 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-cyan-500 font-mono">Halo, cek status order #1029</textarea>
                </div>

                <button type="submit" id="btnRunTest" class="w-full py-2.5 px-4 bg-gradient-to-r from-cyan-600 to-emerald-600 hover:from-cyan-700 hover:to-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-md shadow-cyan-600/20 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Kirim Uji Coba Webhook Sekarang</span>
                </button>
            </form>

            <!-- Result Box (Right) -->
            <div class="lg:col-span-6 bg-slate-900 rounded-2xl p-5 border border-slate-800 flex flex-col text-white">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-800">
                    <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Hasil Evaluasi Respon Webhook
                    </span>
                    <span id="testHttpBadge" class="px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-800 text-slate-400">
                        Menunggu Tes
                    </span>
                </div>

                <div id="testResultEmpty" class="flex-1 flex flex-col items-center justify-center py-10 text-center text-slate-500">
                    <svg class="w-12 h-12 mb-2 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p class="text-xs">Klik tombol "Kirim Uji Coba" untuk melihat hasil HTTP &amp; parsing balasan otomatis dari server Anda.</p>
                </div>

                <div id="testResultContent" class="hidden space-y-3 flex-1 overflow-y-auto max-h-[360px] text-xs">
                    <!-- Direct Reply Detected Box -->
                    <div id="directReplyCard" class="p-3 rounded-xl border bg-emerald-950/60 border-emerald-500/40 text-emerald-300">
                        <div class="flex items-center gap-1.5 font-bold mb-1 text-emerald-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Direct Response Auto-Reply Terdeteksi!</span>
                        </div>
                        <p class="text-[11px] text-slate-300">Pesan ini akan langsung terkirim otomatis ke WhatsApp pengirim:</p>
                        <div class="mt-2 p-2 rounded bg-slate-950 font-mono text-xs text-white border border-emerald-500/20" id="detectedReplyText"></div>
                    </div>

                    <!-- Latency & Code -->
                    <div class="flex items-center justify-between text-[11px] text-slate-400 bg-slate-800/60 p-2 rounded-lg">
                        <span>Waktu Respon: <strong id="testLatency" class="text-cyan-300">0 ms</strong></span>
                        <span>HTTP Code: <strong id="testCode" class="text-cyan-300">200</strong></span>
                    </div>

                    <!-- Raw Response Body -->
                    <div>
                        <span class="text-[11px] text-slate-400 font-semibold mb-1 block">Raw Response Body dari Server Anda:</span>
                        <pre id="testRawBody" class="p-3 bg-slate-950 rounded-xl border border-slate-800 font-mono text-[11px] text-cyan-200 overflow-x-auto"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── Device Webhook Management Table ─────────────────────────────────── -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm font-black">5</span>
                    Daftar Device &amp; URL Webhook Terdaftar
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Kelola dan simpan Webhook URL perangkat Anda langsung dari tabel ini.</p>
            </div>
            <a href="<?= BASEURL ?>/device" class="text-xs text-cyan-600 hover:text-cyan-700 font-semibold flex items-center gap-1 hover:underline">
                Kelola Device di Menu Device &rarr;
            </a>
        </div>

        <?php if (!empty($devices)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 text-left">Device</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-left">Device Token</th>
                        <th class="px-5 py-3 text-left">Webhook URL</th>
                        <th class="px-5 py-3 text-right pr-6">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    <?php foreach ($devices as $d): ?>
                    <tr class="hover:bg-gray-50/70 transition" id="row-device-<?= $d['id'] ?>">
                        <td class="px-5 py-3.5">
                            <div class="font-bold text-gray-800"><?= htmlspecialchars($d['name']) ?></div>
                            <div class="text-[11px] text-gray-500 font-mono"><?= htmlspecialchars($d['phone'] ? '+' . $d['phone'] : 'Belum Ditautkan') ?></div>
                        </td>
                        <td class="px-5 py-3.5">
                            <?php if ($d['status'] === 'connected'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Terhubung
                            </span>
                            <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                <?= htmlspecialchars(ucfirst($d['status'])) ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <code class="bg-gray-100 px-2 py-1 rounded text-gray-700 font-mono text-[11px] max-w-[150px] truncate"><?= htmlspecialchars($d['token']) ?></code>
                                <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($d['token']) ?>'); Swal.fire({icon:'success', title:'Token Disalin!', timer:1000, showConfirmButton:false});" class="text-gray-400 hover:text-cyan-600 p-1" title="Salin Token">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 min-w-[280px]">
                            <input type="url" id="tableWebhookUrl-<?= $d['id'] ?>" 
                                   value="<?= htmlspecialchars($d['webhook_url'] ?? '') ?>"
                                   placeholder="https://domain-anda.com/webhook.php"
                                   class="w-full text-xs px-3 py-1.5 bg-gray-50 border border-gray-200 rounded-lg outline-none focus:ring-2 focus:ring-cyan-500 font-mono text-gray-700">
                        </td>
                        <td class="px-5 py-3.5 text-right pr-6">
                            <button onclick="saveTableWebhook(<?= $d['id'] ?>)" 
                                    id="btnSaveTableWebhook-<?= $d['id'] ?>"
                                    class="px-3 py-1.5 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-xs font-semibold transition shadow-sm active:scale-95">
                                Simpan URL
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="py-12 text-center text-gray-400 text-xs">
            Belum ada device yang ditambahkan. Silakan buka menu Devices untuk menambah device.
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ─── Interactive JavaScript ─────────────────────────────────────────────── -->
<script>
(function() {
    let currentToken = '<?= htmlspecialchars($defaultToken) ?>';
    let currentPhone = '<?= htmlspecialchars($defaultPhone) ?>';

    // Device Switcher Event
    $('#deviceSelector').on('change', function() {
        const selected = $(this).find('option:selected');
        currentToken = selected.data('token') || '';
        currentPhone = selected.data('phone') || '6285100000000';
        const webhook = selected.data('webhook') || '';

        $('#displayTokenBadge').text(currentToken);
        $('.var-token').text(currentToken);
        $('.var-phone').text(currentPhone);
        
        if (webhook && !$('#testWebhookUrl').val()) {
            $('#testWebhookUrl').val(webhook);
        }
    });

    // Inisialisasi awal webhook URL di form tester jika ada
    const initWebhook = $('#deviceSelector option:selected').data('webhook');
    if (initWebhook) {
        $('#testWebhookUrl').val(initWebhook);
    }

    // Tab switcher
    window.switchTab = function(tabName) {
        $('.tab-btn').removeClass('active bg-white text-gray-800 shadow-sm').addClass('text-gray-600');
        $(`.tab-btn[data-tab="${tabName}"]`).addClass('active bg-white text-gray-800 shadow-sm').removeClass('text-gray-600');

        $('.tab-pane').addClass('hidden');
        $(`#tab-content-${tabName}`).removeClass('hidden');

        const filenames = {
            php: 'webhook.php',
            laravel: 'WhatsAppWebhookController.php',
            nodejs: 'server.js',
            python: 'app.py',
            curl: 'terminal.sh'
        };
        $('#tabFilename').text(filenames[tabName] || 'code.txt');
    };

    // Copy Token
    window.copyCurrentToken = function() {
        navigator.clipboard.writeText(currentToken).then(() => {
            Swal.fire({ icon: 'success', title: 'Token Disalin!', text: currentToken, timer: 1200, showConfirmButton: false });
        });
    };

    // Copy snippet helper
    window.copySnippet = function(elementId) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(() => {
            Swal.fire({ icon: 'success', title: 'Kode Disalin!', timer: 1000, showConfirmButton: false });
        });
    };

    window.copyActiveTabCode = function() {
        const activePane = document.querySelector('.tab-pane:not(.hidden)');
        if (activePane) {
            const text = activePane.innerText;
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({ icon: 'success', title: 'Kode Program Disalin!', timer: 1000, showConfirmButton: false });
            });
        }
    };

    // Form Live Webhook Tester Submit
    $('#formTestWebhook').on('submit', function(e) {
        e.preventDefault();
        const btn = $('#btnRunTest');
        const url = $('#testWebhookUrl').val().trim();
        const sender = $('#testSender').val().trim();
        const name = $('#testName').val().trim();
        const message = $('#testMessage').val().trim();

        btn.prop('disabled', true).addClass('opacity-70').find('span').text('Mengirim Payload...');

        $.ajax({
            url: BASEURL + '/webhook/testWebhook',
            type: 'POST',
            data: {
                webhook_url: url,
                sender: sender,
                name: name,
                message: message,
                device_phone: currentPhone
            },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).removeClass('opacity-70').find('span').text('Kirim Uji Coba Webhook Sekarang');

                $('#testResultEmpty').addClass('hidden');
                $('#testResultContent').removeClass('hidden');

                const code = res.http_code || 0;
                const is2xx = code >= 200 && code < 300;

                $('#testCode').text(code);
                $('#testLatency').text(res.latency_ms + ' ms');
                $('#testHttpBadge')
                    .text(`HTTP ${code} ${is2xx ? 'OK' : 'ERR'}`)
                    .attr('class', `px-2 py-0.5 rounded text-[10px] font-bold font-mono ${is2xx ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30'}`);

                $('#testRawBody').text(res.raw_response || '(Empty response body)');

                if (res.is_valid_direct_reply && res.detected_reply) {
                    $('#directReplyCard').removeClass('hidden');
                    $('#detectedReplyText').text(res.detected_reply);
                } else {
                    $('#directReplyCard').addClass('hidden');
                }

                if (res.status === 'success' && is2xx) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Webhook Terhubung!',
                        text: `Server Anda merespons dengan HTTP ${code} (${res.latency_ms}ms)` + (res.is_valid_direct_reply ? ' & Direct Reply terdeteksi!' : ''),
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Koneksi Selesai dengan Catatan',
                        text: res.message || `Server merespons kode HTTP ${code}`
                    });
                }
            },
            error: function() {
                btn.prop('disabled', false).removeClass('opacity-70').find('span').text('Kirim Uji Coba Webhook Sekarang');
                Swal.fire('Error', 'Gagal memanggil endpoint simulator.', 'error');
            }
        });
    });

    // Save Webhook from Table
    window.saveTableWebhook = function(deviceId) {
        const input = $(`#tableWebhookUrl-${deviceId}`);
        const btn = $(`#btnSaveTableWebhook-${deviceId}`);
        const url = input.val().trim();

        btn.prop('disabled', true).addClass('opacity-70').text('Menyimpan...');

        $.ajax({
            url: BASEURL + '/webhook/saveWebhookUrl',
            type: 'POST',
            data: { id: deviceId, webhook_url: url },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).removeClass('opacity-70').text('Simpan URL');
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, timer: 1500, showConfirmButton: false });
                    // Update data-webhook di dropdown
                    $(`#deviceSelector option[value="${deviceId}"]`).data('webhook', url);
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function() {
                btn.prop('disabled', false).removeClass('opacity-70').text('Simpan URL');
                Swal.fire('Error', 'Terjadi kesalahan sistem saat menyimpan URL.', 'error');
            }
        });
    };

})();
</script>
