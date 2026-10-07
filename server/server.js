/**
 * ╔════════════════════════════════════════════════════════════╗
 * ║   WAGTW WhatsApp Engine v3.0 - Powered by Baileys v7       ║
 * ║   No Chrome / No Puppeteer - Pure WebSocket + LID          ║
 * ║                                                            ║
 * ║   Coding by: cs.baguosps@gmail.com                         ║
 * ║   Copyright (c) 2024-2026. All rights reserved.           ║
 * ╚════════════════════════════════════════════════════════════╝
 */

require('dotenv').config();

const express    = require('express');
const cors       = require('cors');
const mysql      = require('mysql2/promise');
const qrcode     = require('qrcode');
const path       = require('path');
const fs         = require('fs');
const pino       = require('pino');

// ─── Baileys v7 Imports ───────────────────────────────────────────────────────
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
    isJidBroadcast,
    isJidGroup,
    jidNormalizedUser,
    areJidsSameUser,
    jidDecode,
    Browsers,
    proto,
    generateWAMessageFromContent,
} = require('@whiskeysockets/baileys');
const { Boom } = require('@hapi/boom');
const { callGroqAutoSwitch, callGroq } = require('./groq');
const { searchAutoSwitch, needsWebSearch } = require('./search');

// ─── Helper: Normalisasi Phone Number dari JID (handle LID v7) ───────────────
// Baileys v7 memperkenalkan LID (Linked Identity Device).
// Format JID baru: "628xxx:12@lid" atau "628xxx:12@s.whatsapp.net"
// Fungsi ini mengekstrak nomor telepon bersih dari semua format.
function normalizePhoneFromJid(jid) {
    if (!jid) return '';
    // Semua format: strip domain, strip :device
    return jid.split('@')[0].split(':')[0];
}

// ─── In-Memory Store (kompatibel dengan Baileys v7 — makeInMemoryStore dihapus dari core) ──
function makeInMemoryStore() {
    const chats = [];
    const messages = {};
    const contacts = {};

    return {
        chats,
        messages,
        contacts,
        loadMessage: async (jid, id) => {
            const list = messages[jid]?.array || [];
            return list.find(m => m.key?.id === id);
        },
        bind: (ev) => {
            ev.on('chats.set', ({ chats: newChats }) => {
                if (Array.isArray(newChats)) chats.splice(0, chats.length, ...newChats);
            });
            ev.on('chats.update', (updates) => {
                if (Array.isArray(updates)) {
                    for (const update of updates) {
                        const c = chats.find(item => item.id === update.id);
                        if (c) Object.assign(c, update);
                        else chats.push(update);
                    }
                }
            });
            ev.on('contacts.set', ({ contacts: newContacts }) => {
                if (Array.isArray(newContacts)) {
                    for (const c of newContacts) if (c && c.id) contacts[c.id] = c;
                }
            });
            ev.on('contacts.update', (updates) => {
                if (Array.isArray(updates)) {
                    for (const update of updates) {
                        if (update && update.id) {
                            if (contacts[update.id]) Object.assign(contacts[update.id], update);
                            else contacts[update.id] = update;
                        }
                    }
                }
            });
            ev.on('messages.upsert', ({ messages: newMessages }) => {
                if (Array.isArray(newMessages)) {
                    for (const msg of newMessages) {
                        const jid = msg.key?.remoteJid;
                        if (!jid) continue;
                        if (!messages[jid]) messages[jid] = { array: [] };
                        messages[jid].array.push(msg);
                        if (messages[jid].array.length > 100) messages[jid].array.shift();
                    }
                }
            });
        },
        readFromFile: (filePath) => {
            try {
                if (fs.existsSync(filePath)) {
                    const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));
                    if (data && Array.isArray(data.chats)) chats.splice(0, chats.length, ...data.chats);
                    if (data && data.contacts) Object.assign(contacts, data.contacts);
                    if (data && data.messages) Object.assign(messages, data.messages);
                }
            } catch (_) {}
        },
        writeToFile: (filePath) => {
            try {
                fs.writeFileSync(filePath, JSON.stringify({ chats, contacts, messages }, null, 2));
            } catch (_) {}
        }
    };
}

// Global error handlers to prevent socket drops from crashing the engine
process.on('unhandledRejection', (reason) => {
    console.error('[UnhandledRejection]', reason?.message || reason);
});
process.on('uncaughtException', (err) => {
    console.error('[UncaughtException]', err?.message || err);
});

// ─── App Config ───────────────────────────────────────────────────────────────
const app        = express();
const PORT       = process.env.PORT || process.env.WA_ENGINE_PORT || 3001;
const API_TOKEN  = process.env.API_SECRET_TOKEN || 'wagtw_secret_token_2024';
const AUTH_DIR   = path.join(__dirname, '.sessions');

// Pastikan folder sesi tersedia
if (!fs.existsSync(AUTH_DIR)) fs.mkdirSync(AUTH_DIR, { recursive: true });

// Silent logger untuk Baileys (supaya terminal tidak banjir log)
const baileysLogger = pino({ level: 'silent' });

// ─── Middleware ───────────────────────────────────────────────────────────────
app.use(cors());

// Strip /server prefix if request routed through cPanel CloudLinux Passenger
app.use((req, res, next) => {
    if (req.url.startsWith('/server/')) {
        req.url = req.url.substring(7);
    } else if (req.url === '/server' || req.url.startsWith('/server?')) {
        req.url = req.url.replace(/^\/server/i, '') || '/';
    }
    next();
});


app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// ─── Auth Middleware ──────────────────────────────────────────────────────────
async function authMiddleware(req, res, next) {
    const authHeader = req.headers['authorization'] || '';
    let bearerToken = '';
    if (authHeader.startsWith('Bearer ')) bearerToken = authHeader.substring(7).trim();
    else if (authHeader) bearerToken = authHeader.trim();

    const token = req.headers['x-api-token'] || req.query.token || bearerToken || req.body?.token;
    if (!token) {
        return res.status(403).json({ status: 'error', message: 'Unauthorized - Token tidak valid.' });
    }

    if (token === API_TOKEN) {
        return next();
    }

    // Cek apakah token merupakan token device di database
    try {
        const devices = await dbQuery('SELECT * FROM devices WHERE token = ? LIMIT 1', [token]);
        if (devices.length > 0) {
            req.device = devices[0];
            if (!req.body) req.body = {};
            if (!req.body.sessionId) {
                req.body.sessionId = devices[0].session_id;
            }
            return next();
        }
    } catch (e) {}

    return res.status(403).json({ status: 'error', message: 'Unauthorized - Token tidak valid.' });
}

// ─── Database Pool ────────────────────────────────────────────────────────────
let db = mysql.createPool({
    host:               process.env.DB_HOST || 'localhost',
    user:               process.env.DB_USER || 'root',
    password:           process.env.DB_PASS || '',
    database:           process.env.DB_NAME || 'wagtw',
    charset:            'utf8mb4',
    waitForConnections: true,
    connectionLimit:    10,
});

// Auto-test and fallback to local root if hosting credentials are configured in .env but server runs locally
(async () => {
    try {
        await db.query('SELECT 1');
        await db.query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        console.log(`[DB] Connected successfully to database: ${process.env.DB_NAME || 'wagtw'} (charset: utf8mb4)`);
    } catch (err) {
        console.warn(`[DB] Primary connection to "${process.env.DB_NAME || 'wagtw'}" failed (${err.code}). Trying local fallback (root@localhost/wagtw)...`);
        try {
            const fallbackDb = mysql.createPool({
                host: 'localhost',
                user: 'root',
                password: '',
                database: 'wagtw',
                charset: 'utf8mb4',
                waitForConnections: true,
                connectionLimit: 10,
            });
            await fallbackDb.query('SELECT 1');
            await fallbackDb.query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            db = fallbackDb;
            console.log('[DB] Fallback connected successfully to local database: wagtw (charset: utf8mb4)');
        } catch (fbErr) {
            console.error('[DB Fatal] Could not connect to primary or fallback database:', fbErr.message);
        }
    }
})();

async function dbQuery(sql, params = []) {
    try {
        const [rows] = await db.execute(sql, params);
        return rows;
    } catch (err) {
        console.error('[DB Error]', err.message);
        return [];
    }
}

// ─── Session Store ────────────────────────────────────────────────────────────
// sessions[sessionId] = { socket, qrBase64, status, phone, retries, deviceId }
const sessions = {};

// ─── Bot Sent Message Tracker ─────────────────────────────────────────────────
// Melacak ID pesan yang dikirim oleh sistem/bot (agar tidak dianggap sebagai pesan intervensi Admin manusia)
const botSentMessageIds = new Set();
function markBotSentMessage(msgId) {
    if (!msgId) return;
    botSentMessageIds.add(msgId);
    if (botSentMessageIds.size > 2000) {
        const first = botSentMessageIds.values().next().value;
        botSentMessageIds.delete(first);
    }
}

// ─── DB Helper ────────────────────────────────────────────────────────────────
async function updateDeviceStatus(sessionId, status, phone = null) {
    if (phone) {
        await dbQuery('UPDATE devices SET status = ?, phone = ? WHERE session_id = ?', [status, phone, sessionId]);
    } else {
        await dbQuery('UPDATE devices SET status = ? WHERE session_id = ?', [status, sessionId]);
    }
    console.log(`[DB] ${sessionId} → ${status}${phone ? ' | +' + phone : ''}`);
}

async function saveMessageToDB(sessionId, waMsgId, fromPhone, toPhone, msgType, message, direction, status) {
    try {
        const devices = await dbQuery('SELECT id, user_id FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
        if (!devices.length) return;
        const device = devices[0];
        
        await dbQuery(
            `INSERT INTO messages (user_id, device_id, wa_msg_id, from_phone, to_phone, msg_type, message, direction, status, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status)`,
            [device.user_id, device.id, waMsgId || null, fromPhone, toPhone, msgType, message, direction, status]
        );
    } catch (e) {
        console.error('[SaveMsg Error]', e.message);
    }
}

// ─── Webhook Dispatcher & Direct Response Engine (Fonnte Compatible) ────────
async function dispatchWebhook(sessionId, senderJid, incomingText, fullMsg) {
    if (!sessionId || !senderJid) return false;

    try {
        const devices = await dbQuery('SELECT * FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
        if (!devices.length || !devices[0].webhook_url) return false;

        const webhookUrl = devices[0].webhook_url.trim();
        if (!webhookUrl.startsWith('http://') && !webhookUrl.startsWith('https://')) return false;

        const session = sessions[sessionId];
        if (!session?.socket || session.status !== 'connected') return false;

        const isGroup = senderJid.endsWith('@g.us');
        // v7 LID: gunakan helper normalizePhoneFromJid untuk handle format JID baru
        const remotePhone = normalizePhoneFromJid(senderJid);
        const myPhone = session.phone || devices[0].phone || '';
        const senderName = fullMsg.pushName || '';
        const timestamp = Math.floor(Date.now() / 1000);

        const payload = {
            event:     'message',
            device:    myPhone,
            sender:    remotePhone,
            message:   incomingText || '',
            name:      senderName,
            is_group:  isGroup,
            timestamp: timestamp,
            wa_msg_id: fullMsg.key?.id || null
        };

        console.log(`[WEBHOOK] Mengirimkan pesan masuk ke ${webhookUrl} dari +${remotePhone}...`);

        const res = await fetch(webhookUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'User-Agent': 'WAGTW-Webhook-Engine/2.0',
                'X-Wagtw-Event': 'message'
            },
            body: JSON.stringify(payload),
            signal: AbortSignal.timeout(8000)
        });

        if (!res.ok) {
            console.warn(`[WEBHOOK] ${webhookUrl} menghasilkan HTTP status ${res.status}`);
            return false;
        }

        const text = await res.text();
        if (!text) return false;

        let data = null;
        try {
            data = JSON.parse(text);
        } catch (_) {
            return false;
        }

        // Direct Response: jika webhook membalas dengan JSON yang memiliki field 'message' atau 'reply'
        const replyText = data.message || data.reply || (typeof data.response === 'string' ? data.response : null);
        if (replyText && session.socket) {
            console.log(`[WEBHOOK DIRECT REPLY] Terdeteksi balasan langsung dari webhook: "${replyText.substring(0, 50)}..."`);
            const sock = session.socket;
            const sentMsg = await sock.sendMessage(senderJid, { text: replyText }, { quoted: fullMsg });
            const waMsgId = sentMsg?.key?.id || null;
            markBotSentMessage(waMsgId);
            await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', replyText, 'out', 'sent');
            return true; // Webhook berhasil membalas pesan secara langsung
        }

        return false;
    } catch (err) {
        console.warn('[WEBHOOK ERROR]', err.message);
        return false;
    }
}

// ─── WhatsApp Text Formatter & Helper Functions ─────────────────────────────

/**
 * Merapikan format balasan AI agar ramah WhatsApp:
 * - Mengonversi tabel Markdown menjadi daftar poin terstruktur
 * - Mengubah tag HTML (<br>, <b>, dll) menjadi format WhatsApp
 * - Menghilangkan double asterisks (**text** -> *text*)
 * - Merapikan spasi kosong ganda
 */
function cleanWhatsAppFormatting(input) {
    if (!input || typeof input !== 'string') return '';

    let res = input;

    // 1. Ganti **teks** menjadi *teks* (format tebal resmi WA)
    res = res.replace(/\*\*([^*]+)\*\*/g, '*$1*');

    // 2. Tangani tabel markdown jika ada
    const lines = res.split('\n');
    const output = [];
    let inTable = false;
    let tableRows = [];

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trim = line.trim();
        if (trim.startsWith('|') && trim.endsWith('|')) {
            inTable = true;
            tableRows.push(trim);
        } else if (trim.startsWith('|') && !trim.endsWith('|') && inTable) {
            tableRows.push(trim + ' |');
        } else {
            if (inTable) {
                output.push(processTableRows(tableRows));
                tableRows = [];
                inTable = false;
            }
            if (trim.startsWith('|')) {
                const cleanedDangling = trim.replace(/^\|+/, '').trim();
                if (cleanedDangling) output.push(cleanedDangling);
            } else {
                output.push(line);
            }
        }
    }
    if (inTable) {
        output.push(processTableRows(tableRows));
    }

    let result = output.join('\n');

    // 3. Bersihkan tag HTML
    result = result.replace(/<br\s*\/?>/gi, '\n');
    result = result.replace(/<\/?(b|strong)>/gi, '*');
    result = result.replace(/<\/?(i|em)>/gi, '_');
    result = result.replace(/<[^>]+>/g, '');

    // 4. Bersihkan double bullet (• • -> •)
    result = result.replace(/•\s*•/g, '•');

    // 5. Bersihkan baris baru berlebih
    result = result.replace(/[ \t]+\n/g, '\n');
    result = result.replace(/\n{3,}/g, '\n\n');

    return result.trim();
}

function processTableRows(rows) {
    if (!rows || rows.length < 2) return rows ? rows.join('\n') : '';

    const headerRaw = rows[0].split('|').map(s => s.trim()).filter(Boolean);
    let dataStartIndex = 1;
    if (rows[1] && /^[|\-:\s]+$/.test(rows[1])) {
        dataStartIndex = 2;
    }

    const formattedBlocks = [];
    let rowNum = 1;

    for (let i = dataStartIndex; i < rows.length; i++) {
        const rawLine = rows[i];
        const cells = rawLine.split('|').map(s => s.trim()).filter(Boolean);
        if (cells.length === 0) continue;

        const title = cells[0];
        const cleanTitle = title.replace(/<br\s*\/?>/gi, ' ').replace(/^[*_\s]+|[*_\s]+$/g, '');
        let block = `*${rowNum}. ${cleanTitle}*`;

        for (let c = 1; c < cells.length; c++) {
            const hName = headerRaw[c] ? headerRaw[c].replace(/^[*_\s]+|[*_\s]+$/g, '') : '';
            let val = cells[c];
            val = val.replace(/<br\s*\/?>/gi, '\n  • ');
            if (hName) {
                block += `\n• *${hName}:*\n  ${val}`;
            } else {
                block += `\n• ${val}`;
            }
        }
        formattedBlocks.push(block);
        rowNum++;
    }

    return formattedBlocks.join('\n\n');
}

/**
 * Unmute nomor kontak / grup agar Bot AI kembali aktif
 * Membersihkan nomor kontak langsung serta nomor/LID terkait di riwayat pesan
 */
async function unmuteContact(deviceId, phone, rawJid = null) {
    if (!deviceId || !phone) return;
    try {
        // 1. Hapus langsung berdasarkan phone / LID
        await dbQuery(
            'DELETE FROM ai_muted_contacts WHERE device_id = ? AND phone = ?',
            [deviceId, phone]
        );

        // Jika rawJid diberikan, pastikan bentuk normalisasinya juga dibersihkan
        if (rawJid) {
            const altPhone = normalizePhoneFromJid(rawJid);
            if (altPhone && altPhone !== phone) {
                await dbQuery('DELETE FROM ai_muted_contacts WHERE device_id = ? AND phone = ?', [deviceId, altPhone]).catch(() => {});
            }
        }

        // 2. Bersihkan juga nomor/LID terkait dari histori percakapan (antisipasi mapping LID vs Phone)
        const related = await dbQuery(
            `SELECT DISTINCT from_phone as num FROM messages WHERE to_phone = ? AND from_phone != ?
             UNION
             SELECT DISTINCT to_phone as num FROM messages WHERE from_phone = ? AND to_phone != ? LIMIT 10`,
            [phone, phone, phone, phone]
        ).catch(() => []);

        if (Array.isArray(related)) {
            for (const r of related) {
                if (r.num && r.num.length >= 5 && r.num !== phone) {
                    await dbQuery('DELETE FROM ai_muted_contacts WHERE device_id = ? AND phone = ?', [deviceId, r.num]).catch(() => {});
                }
            }
        }

        console.log(`[AI UNMUTE] Kontak/Grup ${phone} (dan alias terkait) berhasil di-unmute (Device ${deviceId})`);
    } catch (e) {
        console.error('[AI UNMUTE] Error unmuting contact:', e.message);
    }
}

/**
 * Deteksi pemicu Chat Admin dari teks pesan masuk
 */
function isChatAdminTrigger(text) {
    if (!text || typeof text !== 'string') return false;
    const clean = text.trim().toLowerCase().replace(/[^\w\s#]/g, ' ');
    const normalized = clean.replace(/\s+/g, ' ').trim();

    // 1. Kata kunci langsung / frasa pendek admin
    if (normalized === 'admin' || normalized === 'cs' || normalized === 'operator') return true;
    if (normalized === '#admin' || normalized === '!admin' || normalized === 'chat_admin') return true;

    // 2. Frasa spesifik chat admin
    if (normalized.includes('chat admin') || normalized.includes('chatt admin') || normalized.includes('hubungi admin')) return true;
    if (normalized.includes('bantuan admin') || normalized.includes('kontak admin') || normalized.includes('tanya admin')) return true;
    if (normalized.includes('bicara dengan admin') || normalized.includes('bicara admin') || normalized.includes('ngomong sama admin')) return true;

    // 3. Sapaan singkat ke admin (contoh: "halo admin", "halo admin dong", "pagi admin", "admin dong")
    if (/^(halo|hai|hi|hei|pagi|siang|sore|malam)?\s*(admin|cs|operator)\s*(dong|ya|tolong|pls|please)?$/i.test(normalized)) {
        return true;
    }

    // 4. Pola kombinasi tindakan (contoh: "bisa tolong hubungi admin?", "mau chat staf admin")
    if (/\b(chat|chatt|hubungi|kontak|bicara|berbicara|ngobrol|ngomong|tanya|panggil|sambungkan)\b.*\b(admin|staf|staff|cs|operator|customer service|manusia|human)\b/i.test(normalized)) {
        return true;
    }

    return false;
}

/**
 * Cek apakah teks pesan cocok dengan salah satu kata kunci penutup percakapan (mendukung multiple keywords)
 */
function isEndConversationKeyword(text, keywordSetting) {
    if (!text || typeof text !== 'string') return false;
    const cleanText = text.trim().toLowerCase();
    if (!cleanText) return false;

    // Normalisasi teks input: buang karakter tanda baca di awal/akhir
    const normalizedInput = cleanText.replace(/^[^\w#!\/]+|[^\w#!\/]+$/g, '').trim();

    // Ambil kata kunci dari setting (pisahkan koma, titik koma, baris baru)
    const rawSetting = (keywordSetting && keywordSetting.trim())
        ? keywordSetting
        : 'akhiri percakapan, akhiri, selesai, #selesai, tutup sesi, end chat';

    const keywords = rawSetting
        .split(/[\r\n,;]+/)
        .map(k => k.trim().toLowerCase())
        .filter(k => k.length > 0);

    // Default fallback esensial yang selalu aktif
    const defaultFallbacks = [
        'akhiri percakapan', 'akhiri chat', 'akhiri sesi', 'akhiri', 
        'selesai percakapan', 'selesai chat', 'selesai', 
        '#akhiri', '#selesai', '!akhiri', '!selesai', '/selesai', '/akhiri',
        'tutup sesi', 'tutup percakapan', 'tutup chat', 
        'end chat', 'end conversation', 'close chat', 'close session'
    ];
    for (const def of defaultFallbacks) {
        if (!keywords.includes(def)) keywords.push(def);
    }

    for (const kw of keywords) {
        // 1. Cocok persis
        if (cleanText === kw || normalizedInput === kw) return true;

        // 2. Dimulai dengan kata kunci (misal: "akhiri percakapan ya", "selesai kak", "akhiri.")
        if (cleanText.startsWith(kw + ' ') || cleanText.startsWith(kw + '.') || cleanText.startsWith(kw + '!') || cleanText.startsWith(kw + '?') || cleanText.startsWith(kw + ',')) {
            return true;
        }

        // 3. Diakhiri dengan kata kunci (misal: "percakapan selesai", "chat ini diakhiri")
        if (cleanText.endsWith(' ' + kw)) return true;

        // 4. Frasa multi-kata ada di dalam kalimat (misal: "baik admin akhiri percakapan sekarang")
        if (cleanText.includes(kw)) {
            // Jika kata kunci panjang / multi-kata atau berawalan simbol, langsung cocok
            if (kw.includes(' ') || kw.startsWith('#') || kw.startsWith('!') || kw.startsWith('/') || kw.length >= 6) {
                return true;
            }
            // Jika kata tunggal pendek (misal: "akhiri" atau "selesai"), gunakan regex word boundary
            const wordRegex = new RegExp(`(^|\\s|[.,!?])${kw}($|\\s|[.,!?])`, 'i');
            if (wordRegex.test(cleanText)) return true;
        }
    }

    return false;
}

/**
 * Hitung nilai Date untuk muted_until berdasarkan konfigurasi durasi device
 */
function calculateMutedUntil(durationSetting) {
    const now = new Date();
    switch (durationSetting) {
        case '1_hour':
            return new Date(now.getTime() + 1 * 60 * 60 * 1000);
        case '2_hours':
            return new Date(now.getTime() + 2 * 60 * 60 * 1000);
        case '3_hours':
            return new Date(now.getTime() + 3 * 60 * 60 * 1000);
        case '6_hours':
            return new Date(now.getTime() + 6 * 60 * 60 * 1000);
        case '12_hours':
            return new Date(now.getTime() + 12 * 60 * 60 * 1000);
        case '24_hours':
            return new Date(now.getTime() + 24 * 60 * 60 * 1000);
        case 'manual':
            return null; // Tetap mute sampai admin ketik kata kunci / user ketik #ai on
        case 'next_day':
        default:
            return new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0);
    }
}

/**
 * Format Date ke SQL DATETIME 'YYYY-MM-DD HH:mm:ss'
 */
function formatDateToSql(date) {
    if (!date) return null;
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

/**
 * Cek apakah nomor kontak sedang di-mute pada device tertentu
 */
async function isContactMuted(deviceId, phone) {
    if (!deviceId || !phone) return false;
    try {
        const rows = await dbQuery(
            'SELECT id, muted_date, muted_until, reason FROM ai_muted_contacts WHERE device_id = ? AND phone = ? ORDER BY id DESC LIMIT 1',
            [deviceId, phone]
        );
        if (!rows.length) return false;

        const row = rows[0];
        const now = new Date();

        // 1. Jika terdapat batas waktu spesifik (muted_until)
        if (row.muted_until) {
            const until = new Date(row.muted_until);
            if (now >= until) {
                console.log(`[AI MUTE EXPIRED] Kontak ${phone} masa mute telah berakhir (${row.muted_until}). Mengaktifkan Bot AI kembali.`);
                await dbQuery('DELETE FROM ai_muted_contacts WHERE id = ?', [row.id]).catch(() => {});
                return false;
            }
            return true;
        }

        // 2. Jika mode manual (hanya aktif bila admin ketik kata kunci)
        if (row.reason === 'manual') {
            return true;
        }

        // 3. Fallback jika muted_until null (next_day cek tanggal hari ini)
        const todayStr = now.toISOString().slice(0, 10);
        const rowDateStr = row.muted_date ? (row.muted_date instanceof Date ? row.muted_date.toISOString().slice(0, 10) : String(row.muted_date).slice(0, 10)) : '';
        if (rowDateStr === todayStr) {
            return true;
        } else {
            await dbQuery('DELETE FROM ai_muted_contacts WHERE id = ?', [row.id]).catch(() => {});
            return false;
        }
    } catch (e) {
        console.warn('[AI MUTE] Error checking mute status:', e.message);
        return false;
    }
}

/**
 * Mute nomor kontak berdasarkan konfigurasi durasi device
 */
async function muteContact(deviceId, phone, reason = 'chat_admin') {
    if (!deviceId || !phone) return;
    try {
        const aiSets = await dbQuery(
            'SELECT ai_reactivate_duration FROM device_ai_settings WHERE device_id = ? LIMIT 1',
            [deviceId]
        );
        const durationSetting = aiSets.length && aiSets[0].ai_reactivate_duration ? aiSets[0].ai_reactivate_duration : 'next_day';
        const mutedUntilDate = calculateMutedUntil(durationSetting);
        const mutedUntilSql = formatDateToSql(mutedUntilDate);

        await dbQuery(
            `INSERT INTO ai_muted_contacts (device_id, phone, muted_date, muted_until, reason, created_at, updated_at)
             VALUES (?, ?, CURRENT_DATE(), ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE muted_until = VALUES(muted_until), reason = VALUES(reason), updated_at = NOW()`,
            [deviceId, phone, mutedUntilSql, reason]
        );
        console.log(`[AI MUTE] Kontak/Grup ${phone} di-mute (Device ${deviceId}, Reason: ${reason}, Until: ${mutedUntilSql || 'Manual/Next Day'})`);
    } catch (e) {
        console.error('[AI MUTE] Error muting contact:', e.message);
    }
}

/**
 * Perpanjang masa mute kontak jika ada percakapan baru saat sesi admin berlangsung ("setelah tidak ada respon")
 */
async function touchMutedContact(deviceId, phone) {
    if (!deviceId || !phone) return;
    try {
        const rows = await dbQuery(
            'SELECT id, reason FROM ai_muted_contacts WHERE device_id = ? AND phone = ? ORDER BY id DESC LIMIT 1',
            [deviceId, phone]
        );
        if (!rows.length) return;

        const aiSets = await dbQuery(
            'SELECT ai_reactivate_duration FROM device_ai_settings WHERE device_id = ? LIMIT 1',
            [deviceId]
        );
        const durationSetting = aiSets.length && aiSets[0].ai_reactivate_duration ? aiSets[0].ai_reactivate_duration : 'next_day';

        if (durationSetting.includes('_hour')) {
            const newUntil = calculateMutedUntil(durationSetting);
            const newUntilSql = formatDateToSql(newUntil);
            await dbQuery(
                'UPDATE ai_muted_contacts SET muted_until = ?, updated_at = NOW() WHERE id = ?',
                [newUntilSql, rows[0].id]
            );
            console.log(`[AI MUTE TOUCH] Kontak ${phone} aktifitas baru terdeteksi. Timer mute diperpanjang hingga: ${newUntilSql}`);
        } else {
            await dbQuery('UPDATE ai_muted_contacts SET updated_at = NOW() WHERE id = ?', [rows[0].id]);
        }
    } catch (e) {
        console.warn('[AI MUTE TOUCH] Error:', e.message);
    }
}

// ─── Autoreply Engine (dengan AI Groq) ──────────────────────────────────────
async function processAutoreply(sessionId, senderJid, incomingText, fullMsg) {
    if (!incomingText || !sessionId) return;

    // Cari device beserta pengaturan bisnisnya berdasarkan session_id
    const devices = await dbQuery('SELECT * FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
    if (!devices.length) return;

    const deviceId = devices[0].id;
    const userId   = devices[0].user_id;

    const session = sessions[sessionId];
    if (!session?.socket || session.status !== 'connected') return;
    const sock = session.socket;

    // v7 LID: gunakan helper normalizePhoneFromJid
    const remotePhone = normalizePhoneFromJid(senderJid);
    const myPhone = session.phone;

    // ── Cek pengaturan AI per device ─────────────────────────────────────────
    const aiSettings = await dbQuery(
        'SELECT ads.*, ais.api_key, ais.model as ai_model, ais.label as ai_label FROM device_ai_settings ads LEFT JOIN ai_servers ais ON ais.id = ads.ai_server_id WHERE ads.device_id = ? LIMIT 1',
        [deviceId]
    );

    const aiConfig = aiSettings.length ? aiSettings[0] : null;
    
    // Cek Target Balasan (Pribadi/Grup)
    const targetReply = aiConfig ? (aiConfig.target_reply || 'both') : 'both';
    const isGroup = senderJid.endsWith('@g.us');

    // ── FITUR UNMUTE / REAKTIVASI AI (Pribadi maupun Grup) ────────────────────
    const cleanCmd = incomingText.trim().toLowerCase();
    const isCustomerReactivate = cleanCmd === '#ai on' || cleanCmd === '#ai aktif' || cleanCmd === '!ai on' || cleanCmd === '/ai on' || cleanCmd === 'aktifkan ai'
        || isEndConversationKeyword(incomingText, aiConfig?.ai_reactivate_keyword);

    if (isCustomerReactivate) {
        console.log(`[AI UNMUTE] Kontak/Grup ${remotePhone} meminta reaktivasi Bot AI (Teks: '${incomingText}').`);
        await unmuteContact(deviceId, remotePhone, senderJid);
        const reactivatedText = `🤖 *Bot AI telah diaktifkan kembali* untuk percakapan ini. Silakan tanyakan apa saja! 😊`;
        try {
            const sentMsg = await sock.sendMessage(senderJid, { text: reactivatedText }, { quoted: fullMsg });
            markBotSentMessage(sentMsg?.key?.id);
            await saveMessageToDB(sessionId, sentMsg?.key?.id || null, myPhone, remotePhone, 'text', reactivatedText, 'out', 'sent');
        } catch (_) {
            const sentMsg = await sock.sendMessage(senderJid, { text: reactivatedText });
            markBotSentMessage(sentMsg?.key?.id);
        }
        return;
    }

    // ── FITUR CHAT ADMIN & HANDOFF (Pribadi maupun Grup) ──────────────────────
    if (isChatAdminTrigger(incomingText)) {
        console.log(`[CHAT ADMIN] Kontak/Grup ${remotePhone} memicu Chat Admin. Me-mute Bot AI.`);
        await muteContact(deviceId, remotePhone, 'chat_admin');

        // Gunakan template kustom handoff dari device_ai_settings jika ada
        const handoffText = (aiConfig && aiConfig.ai_handoff_text && aiConfig.ai_handoff_text.trim())
            ? aiConfig.ai_handoff_text.trim()
            : `Baik, permintaan Anda telah kami teruskan ke *Admin / Staf kami* 👤\n\nAdmin kami akan segera membaca dan merespons chat Anda secara langsung. Sementara ini, Bot AI dinonaktifkan untuk percakapan ini dan akan aktif kembali besok hari.\n\nTerima kasih atas kesabaran Anda! 🙏`;

        try {
            let sentMsg = null;
            try {
                sentMsg = await sock.sendMessage(senderJid, { text: handoffText }, { quoted: fullMsg });
            } catch (quotedErr) {
                sentMsg = await sock.sendMessage(senderJid, { text: handoffText });
            }
            const waMsgId = sentMsg?.key?.id || null;
            markBotSentMessage(waMsgId);
            await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', handoffText, 'out', 'sent');
        } catch (err) {
            console.error('[Chat Admin Handoff Send Error]', err.message);
        }
        return; // Hentikan di sini, jangan jalankan AI atau autoreply
    }

    // ── CEK STATUS MUTE (Pribadi maupun Grup) ────────────────────────
    const isMuted = await isContactMuted(deviceId, remotePhone);
    if (isMuted) {
        console.log(`[AI MUTED] Kontak/Grup ${remotePhone} sedang dalam sesi Admin. Bot AI & autoreply dinonaktifkan.`);
        // Perpanjang timer mute karena ada pesan baru ("setelah tidak ada respon")
        await touchMutedContact(deviceId, remotePhone);
        return; // Hentikan di sini, jangan membalas
    }

    // ── WHATSAPP BUSINESS FEATURES (Welcome & Away) ──────────────────────────
    if (!isGroup) {
        const devData = devices[0];
        
        // 1. Welcome Message
        if (devData.welcome_enabled == 1 && devData.welcome_message) {
            const oneDayAgo = new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ');
            const historyCount = await dbQuery('SELECT COUNT(id) as cnt FROM messages WHERE from_phone = ? AND to_phone = ? AND created_at > ?', [remotePhone, myPhone, oneDayAgo]);
            // Jika pesannya <= 1 (yaitu pesan yg baru masuk), berarti ini chat pertama dalam 1 hari
            if (historyCount[0].cnt <= 1) {
                console.log(`[BUSINESS] Mengirim Welcome Message ke ${remotePhone}`);
                try {
                    const sentMsg = await sock.sendMessage(senderJid, { text: devData.welcome_message }, { quoted: fullMsg });
                    markBotSentMessage(sentMsg?.key?.id);
                } catch (_) {
                    const sentMsg = await sock.sendMessage(senderJid, { text: devData.welcome_message }).catch(() => {});
                    markBotSentMessage(sentMsg?.key?.id);
                }
            }
        }

        // 2. Away Message
        if (devData.away_enabled == 1 && devData.away_message && devData.work_hours) {
            try {
                const wh = JSON.parse(devData.work_hours);
                const wibOffset = 7 * 60; // WIB
                const nowUtc = new Date();
                const nowWib = new Date(nowUtc.getTime() + wibOffset * 60 * 1000);
                
                const days = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
                const currentDay = days[nowWib.getUTCDay()];
                const todaySchedule = wh[currentDay];
                
                let isAway = false;
                if (todaySchedule && todaySchedule.is_off) {
                    isAway = true;
                } else if (todaySchedule && todaySchedule.start && todaySchedule.end) {
                    const jamWib = nowWib.getUTCHours();
                    const menitWib = nowWib.getUTCMinutes();
                    const currentMinutes = jamWib * 60 + menitWib;
                    
                    const [startH, startM] = todaySchedule.start.split(':').map(Number);
                    const [endH, endM] = todaySchedule.end.split(':').map(Number);
                    const startMinutes = startH * 60 + startM;
                    const endMinutes = endH * 60 + endM;
                    
                    if (currentMinutes < startMinutes || currentMinutes > endMinutes) {
                        isAway = true;
                    }
                }

                if (isAway) {
                    // Anti-spam: Cek apakah sudah pernah kirim away message dalam 12 jam terakhir
                    const twelveHoursAgo = new Date(Date.now() - 12 * 60 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ');
                    const recentAway = await dbQuery(`SELECT id FROM messages WHERE from_phone = ? AND to_phone = ? AND direction = 'out' AND message = ? AND created_at > ? LIMIT 1`, [myPhone, remotePhone, devData.away_message, twelveHoursAgo]);
                    
                    if (recentAway.length === 0) {
                        console.log(`[BUSINESS] Mengirim Away Message ke ${remotePhone}`);
                        try {
                            const sentMsg = await sock.sendMessage(senderJid, { text: devData.away_message }, { quoted: fullMsg });
                            markBotSentMessage(sentMsg?.key?.id);
                        } catch (_) {
                            const sentMsg = await sock.sendMessage(senderJid, { text: devData.away_message }).catch(() => {});
                            markBotSentMessage(sentMsg?.key?.id);
                        }
                    }
                }
            } catch (e) {
                console.error('[BUSINESS] Gagal memproses Away Message:', e);
            }
        }
    }
    // ──────────────────────────────────────────────────────────────────────────
    // ── MODE MANUAL / FALLBACK: Keyword Autoreply ─────────────────────────────
    // Jika AI berhasil membalas, proses sudah berhenti (return) di atas.
    // Jika sampai di sini, artinya AI mati ATAU AI gagal (fallback), maka jalankan keyword autoreply biasa.

    const rules = await dbQuery(
        'SELECT * FROM autoreplies WHERE (device_id = ? OR device_id IS NULL) AND active = 1 ORDER BY priority DESC, id ASC',
        [deviceId]
    );


    const text = incomingText.trim().toLowerCase();

    let keywordMatched = false;
    for (const rule of rules) {
        // Cek filter target reply (both, private, group)
        const ruleTarget = rule.target_reply || 'both';
        const matchChatType = (ruleTarget === 'both') || 
                              (ruleTarget === 'private' && !isGroup) || 
                              (ruleTarget === 'group' && isGroup);
        if (!matchChatType) continue;

        const trigger = rule.trigger_word.toLowerCase();
        let matched = false;

        switch (rule.match_type) {
            case 'exact':      matched = text === trigger; break;
            case 'startswith': matched = text.startsWith(trigger); break;
            case 'contains':
            default:           matched = text.includes(trigger); break;
        }

        if (matched) {
            keywordMatched = true;
            const delayMs = (rule.delay || 1) * 1000;

            if (rule.set_typing) {
                await sock.sendPresenceUpdate('composing', senderJid).catch(() => {});
            }

            await new Promise(r => setTimeout(r, delayMs));

            try {
                // Gunakan quoted msg agar thread konteks valid (terutama pada pesan pribadi / LID)
                let sentMsg = null;
                try {
                    sentMsg = await sock.sendMessage(senderJid, { text: rule.reply_text }, { quoted: fullMsg });
                } catch (quotedErr) {
                    console.warn(`[Autoreply Quoted Error] ${quotedErr.message}, mencoba kirim pesan langsung...`);
                    sentMsg = await sock.sendMessage(senderJid, { text: rule.reply_text });
                }

                console.log(`[Autoreply] "${rule.trigger_word}" matched (${ruleTarget}) → kirim ke ${senderJid}`);
                
                // Simpan balasan manual autoreply ke histori DB
                const waMsgId = sentMsg?.key?.id || null;
                markBotSentMessage(waMsgId);
                await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', rule.reply_text, 'out', 'sent');
            } catch (err) {
                console.error('[Autoreply Send Error]', err.message);
            }
            break;
        }
    }
    if (keywordMatched) return;

    const isTargetMatch = (targetReply === 'both') || 
                          (targetReply === 'private' && !isGroup) || 
                          (targetReply === 'group' && isGroup);

    const replyMode = aiConfig ? (aiConfig.reply_mode || 'manual') : 'manual';
    const isAutoAi = (replyMode === 'auto' || replyMode === 'ai');

    const aiEnabled = aiConfig && aiConfig.ai_enabled == 1 && isTargetMatch && isAutoAi;
    const searchEnabled = aiConfig && aiConfig.search_enabled == 1;

    // ── MODE AI: Gunakan Groq ─────────────────────────────────────────────────
    if (aiEnabled) {
        console.log(`[AI] Device ${deviceId} — Mode AI aktif, menghubungi Groq...`);

        // Tampilkan indikator mengetik selama AI berpikir
        await sock.sendPresenceUpdate('composing', senderJid).catch(() => {});

        // Ambil 8 pesan terakhir untuk context history dari Baileys Store
        const historyMsgs = session.store?.messages[senderJid]?.array?.slice(-8) || [];
        
        let chatContext = historyMsgs.map(msg => {
            const isFromMe = msg.key.fromMe;
            const text = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';
            return {
                role: isFromMe ? 'assistant' : 'user',
                content: text
            };
        }).filter(c => c.content);

        // Pastikan pesan terakhir dalam konteks selalu pesan yang baru masuk (role: user)
        // Groq membutuhkan prompt terakhir berasal dari user.
        const lastContextMsg = chatContext.length > 0 ? chatContext[chatContext.length - 1] : null;
        if (!lastContextMsg || lastContextMsg.content !== incomingText) {
            chatContext.push({ role: 'user', content: incomingText });
        }

        let aiReply = null;
        let sysPrompt = aiConfig.rules || 'Kamu adalah asisten WhatsApp yang ramah dan helpful. Balas dengan singkat dan jelas dalam Bahasa Indonesia.';

        // ── Aturan Format Balasan WhatsApp (Anti-Tabel & Ramah Layar HP) ───
        const waFormatRules = `
[ATURAN FORMAT BALASAN WHATSAPP (WAJIB DIIKUTI)]:
1. Jawaban WAJIB rapi, bersih, dan nyaman dibaca di layar HP WhatsApp.
2. DILARANG KERAS menggunakan format tabel Markdown (karakter '|'). WhatsApp TIDAK mendukung tabel Markdown!
3. DILARANG menggunakan tag HTML seperti <br>, <b>, <i>, dsb.
4. Gunakan format daftar poin (bullet '•' atau nomor '1.', '2.') untuk menguraikan penjelasan atau langkah.
5. Gunakan cetak tebal WhatsApp (*kata*) untuk menonjolkan judul poin atau istilah penting.
6. Berikan jeda 1 baris kosong antar poin atau paragraf agar teks tidak menumpuk rapat.
7. Jawab secara lengkap, jelas, dan tuntas sampai selesai.`;

        sysPrompt = sysPrompt + "\n" + waFormatRules;

        // ── Inject Tanggal & Jam WIB ───────────────────────────────────────
        const wibOffset = 7 * 60; // WIB = UTC+7
        const nowUtc = new Date();
        const nowWib = new Date(nowUtc.getTime() + wibOffset * 60 * 1000);

        const hariList = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        const bulanList = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        const hari   = hariList[nowWib.getUTCDay()];
        const tgl    = nowWib.getUTCDate();
        const bulan  = bulanList[nowWib.getUTCMonth()];
        const tahun  = nowWib.getUTCFullYear();
        const jam    = String(nowWib.getUTCHours()).padStart(2, '0');
        const menit  = String(nowWib.getUTCMinutes()).padStart(2, '0');
        const detik  = String(nowWib.getUTCSeconds()).padStart(2, '0');

        const waktuWib = `${hari}, ${tgl} ${bulan} ${tahun} pukul ${jam}:${menit}:${detik} WIB`;
        sysPrompt = `[Waktu saat ini: ${waktuWib}]

` + sysPrompt;

        // ── Inject Knowledge Base / Data AI milik device ini jika ada ───────────
        try {
            const kbRows = await dbQuery('SELECT title, content FROM device_ai_data WHERE device_id = ? ORDER BY id ASC', [deviceId]);
            if (kbRows && kbRows.length > 0) {
                const kbText = kbRows.map(k => `### ${k.title}:\n${k.content}`).join('\n\n');
                sysPrompt += `\n\n[DATA PENGETAHUAN & FAQ BISNIS TOKO]:\n` + kbText;
            }
        } catch (kbErr) {
            console.warn('[AI] Gagal membaca data knowledge base device:', kbErr.message);
        }

        // ── Web Search Integration ─────────────────────────────────────────

        if (searchEnabled && needsWebSearch(incomingText)) {
            console.log(`[Search] Pertanyaan memerlukan info terkini, menjalankan web search...`);
            try {
                const searchEngines = await dbQuery(
                    'SELECT * FROM ai_search_engines WHERE is_active = 1 ORDER BY priority DESC, id ASC'
                );
                if (searchEngines.length > 0) {
                    const { result: searchResult, provider } = await searchAutoSwitch(searchEngines, incomingText);
                    if (searchResult) {
                        console.log(`[Search] ✓ Hasil didapat dari ${provider}, inject ke prompt AI`);
                        // Inject ke system prompt agar AI tahu info terkini
                        sysPrompt = sysPrompt + `

[INFORMASI TERKINI dari Web (${provider}):]
${searchResult}

Gunakan informasi di atas sebagai referensi tambahan jika relevan. Selalu prioritaskan informasi terkini ini daripada pengetahuan lamamu.`;
                        // Increment pull_count di DB
                        await dbQuery(
                            'UPDATE ai_search_engines SET pull_count = pull_count + 1 WHERE provider = ? AND is_active = 1 LIMIT 1',
                            [provider]
                        );
                    } else {
                        console.warn('[Search] Semua engine gagal mendapat hasil, lanjut tanpa search.');
                    }
                } else {
                    console.log('[Search] Tidak ada search engine aktif, lanjut tanpa search.');
                }
            } catch (searchErr) {
                console.warn('[Search] Error saat web search:', searchErr.message);
            }
        }

        // ── Kumpulkan daftar server yang akan dicoba
        let serversToTry = [];
        if (aiConfig.ai_server_id && aiConfig.api_key) {
            serversToTry.push({ api_key: aiConfig.api_key, label: aiConfig.ai_label });
        } else {
            serversToTry = await dbQuery('SELECT * FROM ai_servers WHERE is_active = 1 ORDER BY priority DESC, id ASC');
        }

        // ── Kumpulkan daftar model yang akan dicoba
        let modelsToTry = [];
        if (aiConfig.model && aiConfig.model !== 'auto') {
            modelsToTry.push({ model_name: aiConfig.model });
        } else {
            modelsToTry = await dbQuery('SELECT * FROM ai_models WHERE is_active = 1 ORDER BY priority DESC, id ASC');
            // Fallback jika tidak ada model di DB
            if (modelsToTry.length === 0) modelsToTry.push({ model_name: 'llama3-8b-8192' });
        }

        // ── Lakukan percobaan (Auto-Switch 2D)
        console.log(`[DEBUG] aiConfig:`, aiConfig);
        console.log(`[DEBUG] serversToTry length:`, serversToTry.length, serversToTry);
        console.log(`[DEBUG] modelsToTry length:`, modelsToTry.length, modelsToTry);

        if (serversToTry.length === 0) {
            console.warn(`[AI] Device ${deviceId} — Tidak ada API Key aktif (Server AI kosong)! Harap tambahkan API Key Groq di menu Server AI.`);
        }

        if (serversToTry.length > 0 && modelsToTry.length > 0) {
            outerLoop: for (const srv of serversToTry) {
                for (const mod of modelsToTry) {
                    console.log(`[Groq] Trying server: ${srv.label || 'Auto'} | Model: ${mod.model_name}`);
                    aiReply = await callGroq(srv.api_key, mod.model_name, sysPrompt, chatContext);
                    if (aiReply) {
                        console.log(`[Groq] ✓ Got reply from server: ${srv.label || 'Auto'} | Model: ${mod.model_name}`);
                        break outerLoop;
                    }
                    console.warn(`[Groq] ✗ Failed on server: ${srv.label || 'Auto'} | Model: ${mod.model_name}, switching...`);
                }
            }
        }

        if (aiReply) {
            // Bersihkan dan rapikan format teks untuk WhatsApp
            aiReply = cleanWhatsAppFormatting(aiReply);

            // Sisipkan panduan bantuan staf / Admin jika diaktifkan di template
            const footerEnabled = aiConfig && (aiConfig.ai_footer_enabled !== undefined && aiConfig.ai_footer_enabled !== null)
                ? (parseInt(aiConfig.ai_footer_enabled) === 1)
                : true;

            if (footerEnabled) {
                const customFooter = aiConfig && aiConfig.ai_footer_text && aiConfig.ai_footer_text.trim()
                    ? aiConfig.ai_footer_text.trim()
                    : '────────────────────────\n💬 *Butuh bantuan staf / Admin?*\nKetik *Chat Admin* untuk berbicara langsung dengan tim kami.';

                if (!aiReply.toLowerCase().includes('chat admin') && !aiReply.toLowerCase().includes('butuh bantuan staf')) {
                    aiReply = aiReply + '\n\n' + customFooter;
                }
            }

            // Hentikan status mengetik
            await sock.sendPresenceUpdate('paused', senderJid).catch(() => {});
            
            try {
                let sentMsg = null;

                // Kirim teks standar WhatsApp yang 100% didukung dan stabil di semua perangkat (Android, iPhone, WA Web)
                try {
                    sentMsg = await sock.sendMessage(senderJid, { text: aiReply }, { quoted: fullMsg });
                } catch (quotedErr) {
                    console.warn(`[AI Quoted Error] ${quotedErr.message}, mencoba kirim pesan langsung tanpa quote...`);
                    sentMsg = await sock.sendMessage(senderJid, { text: aiReply });
                }
                
                const waMsgId = sentMsg?.key?.id || null;
                markBotSentMessage(waMsgId);
                console.log(`[AI] Balasan Groq teks berhasil terkirim ke ${senderJid}`);

                // Simpan balasan ke histori DB agar muncul di halaman "Lihat Chat"
                await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', aiReply, 'out', 'sent');
            } catch (err) {
                console.error('[AI Send Error]', err.message);
            }
            return; // Selesai, jangan lanjut ke autoreply biasa
        } else {
            await sock.sendPresenceUpdate('paused', senderJid).catch(() => {});
            console.warn('[AI] Semua server Groq gagal, fallback ke autoreply keyword...');
        }
    }

}

// ─── Create / Start WhatsApp Session ─────────────────────────────────────────
async function createSession(sessionId, isRetry = false, forceNewAuth = false) {
    const sessionAuthDir = path.join(AUTH_DIR, sessionId);

    // If forceNewAuth requested, clean auth directory
    if (forceNewAuth && fs.existsSync(sessionAuthDir)) {
        try {
            fs.rmSync(sessionAuthDir, { recursive: true, force: true });
        } catch (e) {
            console.log(`[Auth] Gagal hapus auth lama ${sessionId}:`, e.message);
        }
    }

    if (!fs.existsSync(sessionAuthDir)) fs.mkdirSync(sessionAuthDir, { recursive: true });

    // Batalkan timeout reconnect sebelumnya jika ada
    if (sessions[sessionId]?.reconnectTimeout) {
        clearTimeout(sessions[sessionId].reconnectTimeout);
        sessions[sessionId].reconnectTimeout = null;
    }

    // Bersihkan sesi dan socket lama secara higienis (hapus listener agar tidak bentrok)
    if (sessions[sessionId]?.socket) {
        if (sessions[sessionId].storeInterval) {
            clearInterval(sessions[sessionId].storeInterval);
            sessions[sessionId].storeInterval = null;
        }
        const oldSock = sessions[sessionId].socket;
        sessions[sessionId].socket = null; // Lepas referensi segera
        try {
            oldSock.ev.removeAllListeners();
            oldSock.end(undefined);
        } catch (_) {}
    }

    const existingRetries = sessions[sessionId]?.retries || 0;
    sessions[sessionId] = {
        socket:           null,
        qrBase64:         null,
        pairingCode:      null,
        status:           'initializing',
        phone:            sessions[sessionId]?.phone || null,
        retries:          isRetry ? existingRetries : 0,
        store:            null,
        storeInterval:    null,
        reconnectTimeout: null
    };

    await updateDeviceStatus(sessionId, 'initializing');

    // Ambil versi Baileys (dengan fallback versi modern WA Web yang kompatibel dengan WhatsApp Business)
    let version = [2, 3000, 1043857760];
    let isLatest = true;
    try {
        const v = await fetchLatestBaileysVersion();
        if (v && v.version && Array.isArray(v.version) && v.version.length === 3 && v.version[1] >= 3000) {
            version = v.version;
            isLatest = v.isLatest;
        }
    } catch (e) {
        console.warn(`[Baileys] fetchLatestBaileysVersion fallback:`, e.message);
    }
    console.log(`[Baileys] Version: ${version.join('.')} | Latest: ${isLatest}`);

    // Load auth state dari disk
    const { state, saveCreds } = await useMultiFileAuthState(sessionAuthDir);

    // Inisialisasi In-Memory Store
    const storePath = path.join(sessionAuthDir, 'baileys_store.json');
    const store = makeInMemoryStore({ logger: baileysLogger });
    try {
        if (fs.existsSync(storePath)) {
            store.readFromFile(storePath);
        }
    } catch (e) {}

    const sock = makeWASocket({
        version,
        logger:           baileysLogger,
        auth:             state,
        printQRInTerminal: false,
        // Gunakan Ubuntu Chrome signature — lebih kompatibel dengan WA Web protokol terbaru
        // & delivery receipt (ceklis 2) bekerja lebih konsisten
        browser:          Browsers.ubuntu('Chrome'),
        syncFullHistory:  false,
        generateHighQualityLinkPreview: false,
        markOnlineOnConnect: true,
        connectTimeoutMs: 60000,
        defaultQueryTimeoutMs: 60000,
        keepAliveIntervalMs: 25000,
        // FIX CEKLIS 1: return undefined (bukan empty proto) agar WA server bisa
        // melakukan retry fetch & mengirim delivery receipt dengan benar
        getMessage: async (key) => {
            if (store) {
                const msg = await store.loadMessage(key.remoteJid, key.id);
                if (msg?.message) return msg.message;
            }
            // return undefined → WA server akan handle sendiri (tidak ghost)
            return undefined;
        }
    });

    store.bind(sock.ev);

    // Save store to file periodically
    const storeInterval = setInterval(() => {
        try { 
            if (fs.existsSync(sessionAuthDir)) store.writeToFile(storePath); 
        } catch (e) {}
    }, 15000);

    sessions[sessionId].socket = sock;
    sessions[sessionId].store = store;
    sessions[sessionId].storeInterval = storeInterval;

    // ── Simpan credentials saat berubah ──────────────────────────────────
    sock.ev.on('creds.update', saveCreds);

    // ── Handle koneksi ────────────────────────────────────────────────────
    sock.ev.on('connection.update', async (update) => {
        const { connection, lastDisconnect, qr } = update;

        // QR Code tersedia
        if (qr) {
            console.log(`[QR] Session ${sessionId}: QR generated`);
            sessions[sessionId].qrBase64 = await qrcode.toDataURL(qr, {
                errorCorrectionLevel: 'M',
                margin: 3,
                scale: 8,
                width: 512,
                color: {
                    dark: '#000000',
                    light: '#ffffff'
                }
            });
            sessions[sessionId].status   = 'qr';
            await updateDeviceStatus(sessionId, 'qr');
        }

        if (connection === 'close') {
            const error = lastDisconnect?.error;
            const statusCode = error?.output?.statusCode;
            console.log(`[CLOSE] Session ${sessionId}: Status Code ${statusCode} | Error: ${error?.message || error}`);

            if (sessions[sessionId]?.storeInterval) {
                clearInterval(sessions[sessionId].storeInterval);
                sessions[sessionId].storeInterval = null;
            }

            // 1. RESTART REQUIRED (Code 515) atau HANDSHAKE PAIRING SUKSES (Code 428)
            // WhatsApp sengaja menutup socket pairing dan meminta reconnect instan dengan kredensial baru.
            // JANGAN HAPUS AUTH! Langsung reconnect agar handshaking tuntas.
            if (statusCode === DisconnectReason.restartRequired || statusCode === 515 || statusCode === 428) {
                console.log(`[RESTART] Session ${sessionId}: Handshake pairing sukses (${statusCode}). Reconnecting...`);
                sessions[sessionId].status = 'initializing';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].reconnectTimeout = setTimeout(() => {
                    createSession(sessionId, true, false).catch(console.error);
                }, 500);
                return;
            }

            // 2. EXPLICIT LOGGED OUT (401)
            // Hanya hapus folder sesi jika user benar-benar logout dari aplikasi WhatsApp di HP!
            if (statusCode === DisconnectReason.loggedOut) {
                console.log(`[AUTH LOGGED OUT] Session ${sessionId}: Sesi di-logout dari WhatsApp (401). Menghapus auth.`);
                try { fs.rmSync(sessionAuthDir, { recursive: true, force: true }); } catch(e) {}

                sessions[sessionId].status   = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].retries  = 0;
                sessions[sessionId].socket   = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 3. CONNECTION REPLACED (440)
            if (statusCode === DisconnectReason.connectionReplaced) {
                console.log(`[REPLACED] Session ${sessionId}: Sesi dibuka di perangkat/browser lain.`);
                sessions[sessionId].status   = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].socket   = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 4. TIMEOUT (408) - QR kadaluwarsa karena tidak di-scan
            if (statusCode === DisconnectReason.timedOut || statusCode === 408) {
                console.log(`[TIMEOUT] Session ${sessionId}: QR atau koneksi timeout (408).`);
                sessions[sessionId].status   = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].socket   = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 5. TEMPORARY ERROR LAINNYA (Code 500, socket reset, network glitch, dll)
            // JANGAN HAPUS AUTH! Coba reconnect otomatis hingga 5 kali.
            sessions[sessionId].status   = 'disconnected';
            sessions[sessionId].qrBase64 = null;
            sessions[sessionId].pairingCode = null;
            sessions[sessionId].socket   = null;
            await updateDeviceStatus(sessionId, 'disconnected');

            const retries = sessions[sessionId]?.retries || 0;
            if (retries < 5) {
                sessions[sessionId].retries = retries + 1;
                console.log(`[RECONNECT] Session ${sessionId}: Retry ${retries + 1}/5 dalam 3 detik...`);
                sessions[sessionId].reconnectTimeout = setTimeout(() => {
                    createSession(sessionId, true, false).catch(console.error);
                }, 3000);
            } else {
                console.log(`[GIVE UP] Session ${sessionId}: Max retries tercapai.`);
            }
        }

        if (connection === 'open') {
            const user  = sock.user;
            // v7 LID: user.id bisa berformat "phone:device@lid" atau "phone:device@s.whatsapp.net"
            // normalizePhoneFromJid() handle semua format dengan benar
            const phone = user?.id ? normalizePhoneFromJid(user.id) : null;
            console.log(`[CONNECTED] Session ${sessionId}: +${phone} [Baileys v7]`);

            sessions[sessionId].status   = 'connected';
            sessions[sessionId].phone    = phone;
            sessions[sessionId].qrBase64 = null;
            sessions[sessionId].pairingCode = null;
            sessions[sessionId].retries  = 0;
            await updateDeviceStatus(sessionId, 'connected', phone);
        }
    });

    // ── Handle pesan masuk & sinkronisasi chat ──────────────────────────────
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        // Multi-device WhatsApp sync: pesan masuk = 'notify', pesan yang diketik admin di HP = 'append' atau 'notify'
        if (type !== 'notify' && type !== 'append') return;

        for (const msg of messages) {
            if (isJidBroadcast(msg.key.remoteJid || '')) continue; // Abaikan broadcast

            const senderJid   = msg.key.remoteJid;
            // v7 LID: JID bisa berformat "628xxx:12@lid" — gunakan helper normalizePhoneFromJid()
            const remotePhone = normalizePhoneFromJid(senderJid);
            let myPhone       = sessions[sessionId]?.phone;
            
            if (!myPhone) {
                const devRows = await dbQuery('SELECT phone FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
                if (devRows.length && devRows[0].phone) {
                    myPhone = devRows[0].phone;
                    if (sessions[sessionId]) sessions[sessionId].phone = myPhone;
                }
            }
            if (!myPhone) continue; // Jangan proses jika nomor device belum terbaca

            let incomingText = msg.message?.conversation
                || msg.message?.extendedTextMessage?.text
                || msg.message?.buttonsResponseMessage?.selectedDisplayText
                || msg.message?.buttonsResponseMessage?.selectedButtonId
                || msg.message?.templateButtonReplyMessage?.selectedDisplayText
                || msg.message?.templateButtonReplyMessage?.selectedId
                || '';

            // Handle klik tombol interaktif (nativeFlowMessage quick_reply)
            if (!incomingText && msg.message?.interactiveResponseMessage?.nativeFlowResponseMessage?.paramsJson) {
                try {
                    const btnJson = JSON.parse(msg.message.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson);
                    incomingText = btnJson.id || btnJson.display_text || '';
                } catch (_) {
                    incomingText = msg.message.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson;
                }
            }

            // Ekstraksi pesan pesan ephemeral, view once, atau edited message
            if (!incomingText && msg.message?.ephemeralMessage?.message) {
                const em = msg.message.ephemeralMessage.message;
                incomingText = em.conversation || em.extendedTextMessage?.text || '';
            }
            if (!incomingText && msg.message?.viewOnceMessage?.message) {
                const vom = msg.message.viewOnceMessage.message;
                incomingText = vom.conversation || vom.extendedTextMessage?.text || '';
            }
            if (!incomingText && msg.message?.viewOnceMessageV2?.message) {
                const vom2 = msg.message.viewOnceMessageV2.message;
                incomingText = vom2.conversation || vom2.extendedTextMessage?.text || '';
            }
            if (!incomingText && msg.message?.editedMessage?.message?.protocolMessage?.editedMessage) {
                const edm = msg.message.editedMessage.message.protocolMessage.editedMessage;
                incomingText = edm.conversation || edm.extendedTextMessage?.text || '';
            }
                
            const waMsgId  = msg.key.id;
            const isFromMe = msg.key.fromMe;
            
            // Tentukan arah & nomor
            const direction = isFromMe ? 'out' : 'in';
            const fromPhone = isFromMe ? myPhone : remotePhone;
            const toPhone   = isFromMe ? remotePhone : myPhone;
            const status    = isFromMe ? 'sent' : 'delivered';

            // Simpan Histori ke DB asal dia berupa teks
            if (incomingText) {
                console.log(`[MSG ${direction.toUpperCase()}] ${sessionId} : ${incomingText.substring(0, 50)}`);
                await saveMessageToDB(sessionId, waMsgId, fromPhone, toPhone, 'text', incomingText, direction, status);
            }

            // Jika pesan dikirim dari akun kita sendiri (isFromMe == true):
            if (isFromMe) {
                // Deteksi jika pesan ini diketik langsung oleh HUMAN ADMIN di HP atau WhatsApp Web
                if (!botSentMessageIds.has(waMsgId)) {
                    let devId = sessions[sessionId]?.deviceId;
                    if (!devId) {
                        const devRows = await dbQuery('SELECT id FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
                        if (devRows.length && devRows[0].id) {
                            devId = devRows[0].id;
                            if (sessions[sessionId]) sessions[sessionId].deviceId = devId;
                        }
                    }
                    if (devId && remotePhone) {
                        // Ambil konfigurasi kata kunci penutupan sesi dari device_ai_settings
                        const devAiSets = await dbQuery(
                            'SELECT ai_reactivate_keyword, ai_reactivate_notify, ai_reactivate_message FROM device_ai_settings WHERE device_id = ? LIMIT 1',
                            [devId]
                        );
                        const reactivateKeywordSetting = (devAiSets.length && devAiSets[0].ai_reactivate_keyword)
                            ? devAiSets[0].ai_reactivate_keyword
                            : 'akhiri percakapan, akhiri, selesai, #selesai, tutup sesi, end chat';

                        // ── CEK APAKAH ADMIN MENGETIK SALAH SATU KATA KUNCI "AKHIRI PERCAKAPAN" ──
                        const isEndConversation = isEndConversationKeyword(incomingText, reactivateKeywordSetting);

                        if (isEndConversation) {
                            console.log(`[ADMIN END CONVERSATION] Admin mengetik '${incomingText}'. Mengaktifkan kembali Bot AI untuk ${remotePhone}.`);
                            await unmuteContact(devId, remotePhone, senderJid);

                            // Kirim pesan konfirmasi ke kontak jika notifikasi diaktifkan
                            const shouldNotify = devAiSets.length ? (parseInt(devAiSets[0].ai_reactivate_notify) === 1) : true;
                            const notifyText = (devAiSets.length && devAiSets[0].ai_reactivate_message && devAiSets[0].ai_reactivate_message.trim())
                                ? devAiSets[0].ai_reactivate_message.trim()
                                : 'Sesi percakapan dengan Admin telah selesai. Bot AI kami kini aktif kembali untuk melayani Anda. Silakan tanyakan apa saja yang Anda butuhkan! 🤖';

                            if (shouldNotify && notifyText) {
                                try {
                                    const sentMsg = await sock.sendMessage(senderJid, { text: notifyText });
                                    const notifyWaId = sentMsg?.key?.id || null;
                                    markBotSentMessage(notifyWaId);
                                    await saveMessageToDB(sessionId, notifyWaId, myPhone, remotePhone, 'text', notifyText, 'out', 'sent');
                                } catch (notifyErr) {
                                    console.warn('[ADMIN END CONVERSATION] Gagal mengirim pesan notifikasi:', notifyErr.message);
                                }
                            }
                        } else {
                            // Admin sedang chat manual biasa, maka mute kontak / perpanjang masa mute ("setelah tidak ada respon")
                            console.log(`[ADMIN CHAT DETECTED] Human admin sedang berbicara langsung dengan ${remotePhone} (${senderJid}). Me-mute/memperpanjang mute Bot AI.`);
                            await muteContact(devId, remotePhone, 'admin_talking');
                        }
                    }
                }
            }

            // Proses webhook & autoreply (HANYA untuk pesan masuk (direction IN) & BUKAN pesan diri sendiri)
            if (!isFromMe) {
                const webhookHandled = await dispatchWebhook(sessionId, senderJid, incomingText, msg);
                if (!webhookHandled) {
                    await processAutoreply(sessionId, senderJid, incomingText, msg);
                }
            }
        }
    });

    // ── FIX CEKLIS 1: Handle perubahan status pesan (sent → delivered → read) ──
    // Event ini dikirim WA server setiap kali status pesan berubah.
    // Tanpa listener ini, DB tidak pernah update dari 'sent' ke 'delivered'/'read'.
    sock.ev.on('messages.update', async (updates) => {
        for (const update of updates) {
            try {
                if (!update.key?.id || !update.update?.status) continue;

                const waMsgId = update.key.id;
                const newStatus = update.update.status;

                // Baileys status codes: 1=ERROR, 2=PENDING, 3=SERVER_ACK, 4=DELIVERY_ACK, 5=READ, 6=PLAYED
                let dbStatus = null;
                if (newStatus === 2)  dbStatus = 'pending';   // belum terkirim ke server
                if (newStatus === 3)  dbStatus = 'sent';      // terkirim ke server WA (ceklis 1 server)
                if (newStatus === 4)  dbStatus = 'delivered'; // terkirim ke HP penerima (ceklis 2)
                if (newStatus === 5)  dbStatus = 'read';      // sudah dibaca (ceklis 2 biru)
                if (newStatus === 6)  dbStatus = 'played';    // voice note diputar

                if (dbStatus) {
                    await dbQuery(
                        'UPDATE messages SET status = ? WHERE wa_msg_id = ?',
                        [dbStatus, waMsgId]
                    );
                    if (newStatus >= 4) {
                        // Log hanya untuk delivered & read agar tidak spam terminal
                        console.log(`[MSG UPDATE] ${waMsgId} → ${dbStatus}`);
                    }
                }
            } catch (e) {
                // Silent — jangan sampai error update status crash engine
            }
        }
    });

    // ── Handle Sinkronisasi Riwayat Chat MASA LALU (Historical Sync) ───
    sock.ev.on('messaging-history.set', async ({ messages, isLatest }) => {
        console.log(`[HISTORY SYNC] ${sessionId} : Menemukan ${messages.length} pesan lama, mengunduh ke database...`);
        const myPhone = sessions[sessionId].phone;
        if (!myPhone) return;

        let savedCount = 0;
        for (const msg of messages) {
            if (isJidBroadcast(msg.key.remoteJid || '')) continue;

            const senderJid   = msg.key.remoteJid;
            // v7 LID: gunakan helper untuk normalisasi
            const remotePhone = senderJid ? normalizePhoneFromJid(senderJid) : '';
            if (!remotePhone) continue;

            const incomingText = msg.message?.conversation
                || msg.message?.extendedTextMessage?.text
                || '';

            const waMsgId  = msg.key.id;
            const isFromMe = msg.key.fromMe;
            
            const direction = isFromMe ? 'out' : 'in';
            const fromPhone = isFromMe ? myPhone : remotePhone;
            const toPhone   = isFromMe ? remotePhone : myPhone;
            const status    = isFromMe ? 'read' : 'read'; // Anggap riwayat masa lalu semuanya 'read'

            if (incomingText) {
                await saveMessageToDB(sessionId, waMsgId, fromPhone, toPhone, 'text', incomingText, direction, status);
                savedCount++;
            }
        }
        console.log(`[HISTORY SYNC] Selesai! ${savedCount} pesan text masa lalu berhasil di-backup untuk ${sessionId}.`);
    });

    return sessions[sessionId];
}

// ════════════════════════════════════════════════════════════════════════════
// ─── REST API ROUTES ─────────────────────────────────────────────────────
// ════════════════════════════════════════════════════════════════════════════

// ── Root / Health Check (publik) ─────────────────────────────────────────
app.get('/', (req, res) => {
    // Dukung respon text/html agar tidak memicu error CloudLinux NodeJS Selector content-type mismatch
    if (req.headers.accept && req.headers.accept.includes('text/html') && !req.query.json) {
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        return res.send(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>WAGTW Engine</title>
</head>
<body style="font-family:sans-serif;display:flex;flex-direction:column;align-items:center;justify-content:center;height:80vh;background:#f8fafc;color:#1e293b;">
    <div style="background:#fff;padding:30px 40px;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.05);text-align:center;border:1px solid #e2e8f0;">
        <h2 style="color:#10b981;margin:0 0 10px 0;">✅ WAGTW Engine is Running</h2>
        <p style="margin:0 0 15px 0;color:#64748b;font-size:14px;">WAGTW WhatsApp Engine v3.0 - Baileys v7 Engine (LID)</p>
        <span style="background:#ecfdf5;color:#059669;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;">Uptime: ${Math.floor(process.uptime())}s</span>
    </div>
</body>
</html>`);
    }

    res.json({
        status:  'ok',
        engine:  'WAGTW v3.0 - Baileys v7 Engine (LID)',
        message: 'WAGTW Engine is running',
        uptime:  Math.floor(process.uptime()) + 's'
    });
});

app.get('/api/status', (req, res) => {
    const active = Object.keys(sessions).map(id => ({
        sessionId: id,
        status:    sessions[id].status,
        phone:     sessions[id].phone,
        hasQR:     !!sessions[id].qrBase64,
    }));
    res.json({
        status:         'ok',
        engine:         'WAGTW v3.0 - Baileys v7 Engine (LID)',
        uptime:         Math.floor(process.uptime()) + 's',
        activeSessions: active,
    });
});

// ── GET /api/chats ───────────────────────────────────────────────────────
app.get('/api/chats', authMiddleware, (req, res) => {
    const { sessionId } = req.query;
    if (!sessionId || !sessions[sessionId]) return res.json({ chats: [] });
    
    const store = sessions[sessionId].store;
    if (!store) return res.json({ chats: [] });

    // store.chats is an array in latest baileys or has an array property
    const rawChats = store.chats?.array || store.chats || [];
    
    // Format into simpler structure for frontend
    const chats = rawChats.map(c => {
        const jid = c.id;
        const messages = store.messages[jid]?.array || [];
        const lastMsgObj = messages.length > 0 ? messages[messages.length - 1] : null;
        let lastMsgText = '';
        let isOut = false;
        if (lastMsgObj) {
             lastMsgText = lastMsgObj.message?.conversation || lastMsgObj.message?.extendedTextMessage?.text || '';
             isOut = lastMsgObj.key.fromMe;
        }
        
        return {
             phone_number: jid.split('@')[0] + (jid.includes('@g.us') ? '-' : ''),
             is_group: jid.includes('@g.us'),
             last_message: lastMsgText,
             direction: isOut ? 'out' : 'in',
             last_message_time: c.conversationTimestamp ? new Date(c.conversationTimestamp * 1000).toISOString() : new Date().toISOString()
        };
    }).filter(c => c.last_message); // Only return chats that have a last message
    
    // Sort by timestamp desc
    chats.sort((a, b) => new Date(b.last_message_time) - new Date(a.last_message_time));

    res.json({ chats });
});

// ── GET /api/messages ────────────────────────────────────────────────────
app.get('/api/messages', authMiddleware, (req, res) => {
    const { sessionId, jid } = req.query;
    if (!sessionId || !jid || !sessions[sessionId]) return res.json({ messages: [] });
    
    const store = sessions[sessionId].store;
    if (!store) return res.json({ messages: [] });

    const rawMessages = store.messages[jid]?.array || [];
    const messages = rawMessages.map(m => {
        const isFromMe = m.key.fromMe;
        const text = m.message?.conversation || m.message?.extendedTextMessage?.text || '';
        let status = isFromMe ? 'sent' : 'delivered'; // simplify status
        if (m.status === 3 || m.status === 4) status = 'read'; 
        
        return {
            id: m.key.id,
            direction: isFromMe ? 'out' : 'in',
            message: text,
            created_at: m.messageTimestamp ? new Date(m.messageTimestamp * 1000).toISOString() : new Date().toISOString(),
            status: status
        };
    }).filter(m => m.message); // Only return messages with text

    res.json({ messages });
});

// ── GET /api/contacts ────────────────────────────────────────────────────
app.get('/api/contacts', authMiddleware, (req, res) => {
    const { sessionId } = req.query;
    if (!sessionId || !sessions[sessionId]) return res.json({ contacts: {} });
    
    const store = sessions[sessionId].store;
    if (!store) return res.json({ contacts: {} });

    res.json({ contacts: store.contacts });
});

// ── POST /api/session/start ──────────────────────────────────────────────
app.post('/api/session/start', authMiddleware, async (req, res) => {
    const { sessionId, forceNew } = req.body;
    if (!sessionId) return res.status(400).json({ status: 'error', message: 'sessionId diperlukan.' });

    if (sessions[sessionId]?.status === 'connected') {
        return res.json({ status: 'already_connected', message: 'Device sudah terhubung.', phone: sessions[sessionId].phone });
    }

    // Jika sesi sedang aktif berjalan (qr atau initializing) dan user TIDAK meminta forceNew, gunakan yang ada
    if (!forceNew && (sessions[sessionId]?.status === 'qr' || sessions[sessionId]?.status === 'initializing')) {
        return res.json({ 
            status: sessions[sessionId].status, 
            message: 'Session sedang aktif.',
            hasQR: !!sessions[sessionId].qrBase64,
            qr: sessions[sessionId].qrBase64 || null
        });
    }

    console.log(`[START] Memulai session: ${sessionId} (forceNew: ${!!forceNew})`);

    createSession(sessionId, false, !!forceNew).catch(err => console.error('[CREATE ERROR]', err.message));

    res.json({ status: 'starting', message: 'Session dimulai. Ambil QR dalam beberapa detik.' });
});

// ── GET /api/session/qr/:sessionId ──────────────────────────────────────
app.get('/api/session/qr/:sessionId', authMiddleware, (req, res) => {
    const s = sessions[req.params.sessionId];
    if (!s) return res.status(404).json({ status: 'not_found', message: 'Session tidak ditemukan.' });
    if (s.status === 'connected') return res.json({ status: 'connected', phone: s.phone });
    if (s.qrBase64) return res.json({ status: 'qr_ready', qr: s.qrBase64 });
    return res.json({ status: s.status || 'initializing', message: 'QR belum siap, harap tunggu...' });
});

// ── GET /api/session/status/:sessionId ──────────────────────────────────
app.get('/api/session/status/:sessionId', authMiddleware, (req, res) => {
    const s = sessions[req.params.sessionId];
    if (!s) return res.json({ status: 'not_started' });
    res.json({ 
        status: s.status, 
        phone: s.phone, 
        hasQR: !!s.qrBase64,
        pairingCode: s.pairingCode || null 
    });
});

// ── POST /api/session/pairing-code ──────────────────────────────────────
app.post('/api/session/pairing-code', authMiddleware, async (req, res) => {
    let { sessionId, phoneNumber } = req.body;
    if (!sessionId || !phoneNumber) {
        return res.status(400).json({ status: 'error', message: 'sessionId dan phoneNumber diperlukan.' });
    }

    // Bersihkan format nomor WhatsApp
    let cleanPhone = String(phoneNumber).replace(/[^0-9]/g, '');
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.substring(1);
    }

    if (cleanPhone.length < 9) {
        return res.status(400).json({ status: 'error', message: 'Nomor WhatsApp tidak valid (minimal 9 digit).' });
    }

    // Cek apakah sudah terhubung
    if (sessions[sessionId]?.status === 'connected') {
        return res.json({ 
            status: 'already_connected', 
            message: 'Device sudah terhubung.', 
            phone: sessions[sessionId].phone 
        });
    }

    try {
        // Pastikan session aktif berjalan
        if (!sessions[sessionId]?.socket) {
            await createSession(sessionId, false, false);
        }

        const session = sessions[sessionId];
        if (!session?.socket) {
            return res.status(500).json({ status: 'error', message: 'Gagal menginisialisasi engine untuk pairing code.' });
        }

        // Tunggu hingga socket siap menerima request pairing code (ketika koneksi WA sudah siap menerima pairing/QR)
        let attempts = 0;
        while (!session.qrBase64 && session.status !== 'qr' && session.status !== 'connected' && attempts < 40) {
            await new Promise(r => setTimeout(r, 250));
            attempts++;
        }

        if (session.status === 'connected') {
            return res.json({ status: 'already_connected', message: 'Device sudah terhubung.', phone: session.phone });
        }

        if (session.socket.authState?.creds?.registered) {
            return res.json({ status: 'already_connected', message: 'Sesi ini sudah terdaftar di WhatsApp.', phone: session.phone });
        }

        const rawCode = await session.socket.requestPairingCode(cleanPhone);
        // Format kode ke bentuk ABCD-1234 agar mudah dibaca
        const formattedCode = rawCode ? (rawCode.match(/.{1,4}/g)?.join('-') || rawCode) : rawCode;
        session.pairingCode = formattedCode;

        console.log(`[PAIRING CODE] Session ${sessionId} untuk +${cleanPhone}: ${formattedCode}`);
        return res.json({
            status: 'success',
            pairingCode: formattedCode,
            phone: cleanPhone,
            message: 'Kode pairing berhasil dibuat.'
        });
    } catch (err) {
        console.error(`[PAIRING CODE ERROR] Session ${sessionId}:`, err.message);
        return res.status(500).json({
            status: 'error',
            message: 'Gagal membuat kode pairing: ' + (err.message || 'Terjadi kesalahan pada socket WhatsApp')
        });
    }
});

// ── POST /api/session/logout/:sessionId ─────────────────────────────────
app.post('/api/session/logout/:sessionId', authMiddleware, async (req, res) => {
    const { sessionId } = req.params;
    const s = sessions[sessionId];
    if (!s?.socket) return res.status(404).json({ status: 'error', message: 'Session tidak aktif.' });

    try {
        if (s.storeInterval) clearInterval(s.storeInterval);
        await s.socket.logout();
        delete sessions[sessionId];

        // Hapus folder auth agar bisa scan QR ulang
        const authDir = path.join(AUTH_DIR, sessionId);
        if (fs.existsSync(authDir)) {
             try { fs.rmSync(authDir, { recursive: true, force: true }); } catch(e) {}
        }

        await updateDeviceStatus(sessionId, 'pending');
        res.json({ status: 'success', message: 'Device berhasil di-logout.' });
    } catch (err) {
        console.error('[LOGOUT ERROR]', err.message);
        // Paksa hapus meskipun error
        if (s.storeInterval) clearInterval(s.storeInterval);
        delete sessions[sessionId];
        updateDeviceStatus(sessionId, 'pending');
        res.json({ status: 'success', message: 'Session dihapus.' });
    }
});

// ── POST /api/send ───────────────────────────────────────────────────────
// Body: { sessionId, to, target, message }
app.post('/api/send', authMiddleware, async (req, res) => {
    let { sessionId, to, target, message } = req.body;
    to = to || target;
    if (!sessionId || !to || !message) {
        return res.status(400).json({ status: 'error', message: 'sessionId, to (atau target), dan message wajib diisi.' });
    }

    const s = sessions[sessionId];
    if (!s || s.status !== 'connected') {
        return res.status(400).json({ status: 'error', message: 'Device tidak terhubung.' });
    }

    try {
        const isGroup = to.includes('-');
        const basePhone = to.replace('-', '').replace(/[^0-9]/g, '');
        const remotePhone = basePhone;

        let targetJid;
        if (isGroup) {
            // Grup: gunakan format basePhone@g.us langsung
            targetJid = basePhone + '@g.us';
        } else {
            // FIX CEKLIS 1: Resolve JID lewat onWhatsApp() untuk handle LID system
            // LID = Linked Identity Device — WA baru pakai ini, JID @s.whatsapp.net bisa
            // berbeda dengan JID internal WA server. Kirim ke JID salah = ceklis 1 selamanya.
            const rawJid = basePhone + '@s.whatsapp.net';
            try {
                const waCheck = await s.socket.onWhatsApp(rawJid);
                if (waCheck && waCheck.length > 0 && waCheck[0].exists) {
                    // Gunakan JID yang di-return WA server (bisa LID atau standard)
                    targetJid = waCheck[0].jid || rawJid;
                } else {
                    return res.status(400).json({ status: 'error', message: `Nomor +${remotePhone} tidak terdaftar di WhatsApp.` });
                }
            } catch (checkErr) {
                // Jika onWhatsApp() gagal (network issue), fallback ke JID standard
                console.warn(`[SEND] onWhatsApp check failed untuk +${remotePhone}, fallback ke JID standard:`, checkErr.message);
                targetJid = rawJid;
            }
        }

        const sentMsg = await s.socket.sendMessage(targetJid, { text: message });

        // Log ke sinkronisasi Database `messages`
        const myPhone = s.phone;
        const waMsgId = sentMsg?.key?.id || null;
        markBotSentMessage(waMsgId);
        await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', message, 'out', 'sent');

        res.json({ status: 'success', message: 'Pesan berhasil dikirim ke ' + (isGroup ? 'Group Chat' : '+' + remotePhone) });
    } catch (err) {
        console.error('[SEND ERROR]', err.message);
        res.status(500).json({ status: 'error', message: 'Gagal kirim: ' + err.message });
    }
});

// ─── Broadcast & Anti-Banned Engine ──────────────────────────────────────────
function parseSpintax(text) {
    let matches, options, random;
    const regEx = new RegExp(/{([^}]+)}/);
    while((matches = regEx.exec(text)) !== null) {
        options = matches[1].split('|');
        random = Math.floor(Math.random() * options.length);
        text = text.replace(matches[0], options[random]);
    }
    return text;
}

const activeCampaignsLocks = {}; // Mencegah double send pada async loop

async function processBroadcasts() {
    try {
        const campaigns = await dbQuery("SELECT c.*, d.session_id FROM campaigns c JOIN devices d ON d.id = c.device_id WHERE c.status = 'running'");
        if (!campaigns.length) return;

        for (const c of campaigns) {
            if (activeCampaignsLocks[c.id]) continue; // Sedang ada proses delay berjalan untuk campaign ini

            const sockData = sessions[c.session_id];
            if (!sockData || sockData.status !== 'connected') continue; // Skip jika device offline

            activeCampaignsLocks[c.id] = true;

            try {
                // Ambil 1 baris target pending
                const pending = await dbQuery("SELECT * FROM campaign_recipients WHERE campaign_id = ? AND status = 'pending' LIMIT 1", [c.id]);
                
                if (pending.length === 0) {
                    const stats = await dbQuery("SELECT sent, failed, total FROM campaigns WHERE id = ?", [c.id]);
                    const s = stats[0] || { sent: 0, failed: 0, total: 0 };
                    const finalStatus = (s.sent === 0 && s.failed > 0) ? 'failed' : 'done';
                    await dbQuery("UPDATE campaigns SET status = ?, finished_at = NOW() WHERE id = ?", [finalStatus, c.id]);
                    delete activeCampaignsLocks[c.id];
                    continue;
                }

                const target = pending[0];
                let toNumber = target.phone.replace(/[^0-9]/g, '');
                if (toNumber.startsWith('0')) {
                    toNumber = '62' + toNumber.substring(1);
                }
                const jid = toNumber + '@s.whatsapp.net';

                // Terapkan Anti-Banned: Spintax & Nama Variabel
                let finalMsg = parseSpintax(c.message);
                finalMsg = finalMsg.replace(/\[Name\]/gi, target.name || 'Kak');

                // Terapkan Anti-Banned: Kalkulasi Delay Acak
                const delayMin = c.delay_min > 0 ? c.delay_min : 5;
                const delayMax = c.delay_max >= delayMin ? c.delay_max : delayMin + 5;
                const delayMs = Math.floor(Math.random() * (delayMax - delayMin + 1) + delayMin) * 1000;

                // Gunakan setTimeout non-blocking
                setTimeout(async () => {
                    try {
                        // 1. Validasi format angka nomor telepon
                        if (!toNumber || toNumber.length < 8 || toNumber.length > 16) {
                            throw new Error('Format nomor tidak valid (kurang dari 8 atau lebih dari 16 digit)');
                        }

                        // 2. Verifikasi apakah nomor terdaftar aktif di WhatsApp
                        let targetJid = jid;
                        let isRegistered = false;
                        try {
                            const waCheck = await sockData.socket.onWhatsApp(jid);
                            if (waCheck && waCheck.length > 0 && waCheck[0].exists) {
                                isRegistered = true;
                                if (waCheck[0].jid) targetJid = waCheck[0].jid;
                            }
                        } catch (chkErr) {
                            console.warn(`[WA CHECK] Gagal cek onWhatsApp +${toNumber}:`, chkErr.message);
                        }

                        if (!isRegistered) {
                            throw new Error('Nomor tidak terdaftar di WhatsApp');
                        }

                        // Simulasikan mengetik agar terlihat natural
                        await sockData.socket.sendPresenceUpdate('composing', targetJid).catch(() => {});

                        let sentMsg = null;
                        let msgType = 'text';

                        // Kirim media jika campaign memiliki file media
                        if (c.media_path) {
                            const fullMediaPath = path.resolve(__dirname, '..', 'public', c.media_path.replace(/^\/+/, ''));
                            if (fs.existsSync(fullMediaPath)) {
                                const ext = path.extname(fullMediaPath).toLowerCase();
                                const mediaBuffer = fs.readFileSync(fullMediaPath);

                                if (['.jpg', '.jpeg', '.png', '.webp'].includes(ext)) {
                                    msgType = 'image';
                                    sentMsg = await sockData.socket.sendMessage(targetJid, {
                                        image: mediaBuffer,
                                        caption: finalMsg
                                    });
                                } else if (['.mp4', '.mov', '.3gp'].includes(ext)) {
                                    msgType = 'video';
                                    sentMsg = await sockData.socket.sendMessage(targetJid, {
                                        video: mediaBuffer,
                                        caption: finalMsg
                                    });
                                } else {
                                    msgType = 'document';
                                    sentMsg = await sockData.socket.sendMessage(targetJid, {
                                        document: mediaBuffer,
                                        mimetype: 'application/octet-stream',
                                        fileName: path.basename(fullMediaPath),
                                        caption: finalMsg
                                    });
                                }
                            }
                        }

                        if (!sentMsg) {
                            sentMsg = await sockData.socket.sendMessage(targetJid, { text: finalMsg });
                        }
                        
                        await dbQuery("UPDATE campaign_recipients SET status = 'sent', sent_at = NOW(), error_msg = NULL WHERE id = ?", [target.id]);
                        await dbQuery("UPDATE campaigns SET sent = sent + 1 WHERE id = ?", [c.id]);
                        
                        const waMsgId = sentMsg?.key?.id || null;
                        markBotSentMessage(waMsgId);
                        await saveMessageToDB(c.session_id, waMsgId, sockData.phone, toNumber, msgType, finalMsg, 'out', 'sent');
                        
                        console.log(`[BROADCAST SUKSES] Camp #${c.id} -> +${toNumber} (Delay: ${delayMs/1000}s)`);
                    } catch (e) {
                        const errMsg = (e.message || 'Gagal mengirim pesan').substring(0, 200);
                        await dbQuery("UPDATE campaign_recipients SET status = 'failed', error_msg = ? WHERE id = ?", [errMsg, target.id]);
                        await dbQuery("UPDATE campaigns SET failed = failed + 1 WHERE id = ?", [c.id]);
                        console.error(`[BROADCAST GAGAL] Camp #${c.id} -> +${toNumber}: ${errMsg}`);
                    } finally {
                        delete activeCampaignsLocks[c.id]; // Buka kunci agar bisa melanjut target berikutnya di tick loop dtk depan
                    }
                }, delayMs);

            } catch (err) {
                console.log('[BROADCAST PROC ERR]', err.message);
                delete activeCampaignsLocks[c.id];
            }
        }
    } catch (err) {
        console.error('[BROADCAST ENGINE ERR]', err.message);
    }
}

// Jalankan Broadcast Worker setiap 5 detik untuk memeriksa Queue
setInterval(processBroadcasts, 5000);


// ─── Auto-Restore Sessions on Startup ────────────────────────────────────────
async function restoreSessionsFromDB() {
    try {
        const devices = await dbQuery(
            "SELECT session_id, status FROM devices WHERE status IN ('connected','qr','initializing','disconnected')"
        );
        if (!devices.length) {
            console.log('[RESTORE] Tidak ada sesi yang perlu di-restore.');
            return;
        }
        console.log(`[RESTORE] Memeriksa ${devices.length} sesi untuk di-restore...`);
        for (const device of devices) {
            const authPath = path.join(AUTH_DIR, device.session_id);
            const credsPath = path.join(authPath, 'creds.json');
            
            let hasValidCreds = false;
            if (fs.existsSync(credsPath)) {
                try {
                    const creds = JSON.parse(fs.readFileSync(credsPath, 'utf8'));
                    if (creds?.me?.id || creds?.registered) {
                        hasValidCreds = true;
                    }
                } catch (e) {}
            }

            if (hasValidCreds) {
                console.log(`[RESTORE] Memulai sesi terdaftar: ${device.session_id}`);
                createSession(device.session_id).catch(e =>
                    console.error(`[RESTORE ERROR] ${device.session_id}:`, e.message)
                );
                // Jeda antar sesi agar tidak overload
                await new Promise(r => setTimeout(r, 2000));
            } else {
                console.log(`[RESTORE] Lewati ${device.session_id} (menunggu scan dari web)`);
                if (device.status !== 'pending') {
                    await updateDeviceStatus(device.session_id, 'disconnected');
                }
            }
        }
    } catch (err) {
        console.error('[RESTORE FATAL]', err.message);
    }
}

// ─── Start Server ─────────────────────────────────────────────────────────────
function onServerReady(listeningOn) {
    console.log('');
    console.log('╔═══════════════════════════════════════════════════╗');
    console.log('║     WAGTW WhatsApp Engine v3.0 - BAILEYS v7      ║');
    console.log('║     No Chrome • No Puppeteer • LID Support        ║');
    console.log('╠═══════════════════════════════════════════════════╣');
    console.log(`║  Port   : ${String(listeningOn).padEnd(40)}║`);
    console.log(`║  Token  : ${API_TOKEN.substring(0, 20).padEnd(40)}║`);
    console.log('║  Groq AI: ENABLED (auto-switch)                   ║');
    console.log('╚═══════════════════════════════════════════════════╝');
    console.log('');

    // Restore semua sesi yang sebelumnya aktif setelah server siap
    setTimeout(restoreSessionsFromDB, 3000);
}

// CloudLinux cPanel Node.js Selector Passenger Support
if (typeof(PhusionPassenger) !== 'undefined') {
    app.listen('passenger', () => onServerReady('cPanel Passenger'));
} else {
    app.listen(PORT, () => onServerReady(PORT));
}

