/**
 * ╔════════════════════════════════════════════════════════════╗
 * ║   WAGTW WhatsApp Engine v3.0 - Powered by Baileys v7       ║
 * ║   No Chrome / No Puppeteer - Pure WebSocket + LID          ║
 * ║                                                            ║
 * ║   Coding by: cs.baguosps@gmail.com                         ║
 * ║   Copyright (c) 2024-2026. All rights reserved.            ║
 * ╚════════════════════════════════════════════════════════════╝
 */

require('dotenv').config();

const express = require('express');
const cors = require('cors');
const mysql = require('mysql2/promise');
const qrcode = require('qrcode');
const path = require('path');
const fs = require('fs');
const pino = require('pino');

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

// ─── Cache Pemetaan LID <-> Phone Number (Baileys v7) ─────────────────────────
const lidToPhoneMap = new Map(); // lidDigits -> realPhoneDigits
const phoneToLidMap = new Map(); // realPhoneDigits -> lidDigits

function getRawPhoneFromJid(jid) {
    if (!jid) return '';
    return jid.split('@')[0].split(':')[0];
}

function recordLidPhoneMapping(lidJidOrPhone, pnJidOrPhone, sock = null) {
    if (!lidJidOrPhone || !pnJidOrPhone) return;
    const raw1 = getRawPhoneFromJid(lidJidOrPhone);
    const raw2 = getRawPhoneFromJid(pnJidOrPhone);
    if (!raw1 || !raw2 || raw1 === raw2) return;

    let lidDigits = null;
    let pnDigits = null;

    if (lidJidOrPhone.endsWith('@lid') || (raw1.length >= 14 && !raw1.startsWith('62'))) {
        lidDigits = raw1;
        pnDigits = raw2;
    } else if (pnJidOrPhone.endsWith('@lid') || (raw2.length >= 14 && !raw2.startsWith('62'))) {
        lidDigits = raw2;
        pnDigits = raw1;
    } else if (raw1.startsWith('62') && !raw2.startsWith('62')) {
        pnDigits = raw1;
        lidDigits = raw2;
    } else if (raw2.startsWith('62') && !raw1.startsWith('62')) {
        pnDigits = raw2;
        lidDigits = raw1;
    }

    if (lidDigits && pnDigits) {
        lidToPhoneMap.set(lidDigits, pnDigits);
        phoneToLidMap.set(pnDigits, lidDigits);

        if (sock?.signalRepository?.lidMapping?.storeLIDPNMappings) {
            try {
                sock.signalRepository.lidMapping.storeLIDPNMappings([
                    { lid: `${lidDigits}@lid`, pn: `${pnDigits}@s.whatsapp.net` }
                ]);
            } catch (_) { }
        }
    }
}

// ─── Helper: Normalisasi Phone Number dari JID (handle LID v7) ───────────────
// Baileys v7 memperkenalkan LID (Linked Identity Device).
// Format JID baru: "628xxx:12@lid" atau "628xxx:12@s.whatsapp.net"
// Fungsi ini mengekstrak nomor telepon bersih dari semua format, dan memetakan LID ke nomor asli jika ada.
function normalizePhoneFromJid(jid) {
    if (!jid) return '';
    const raw = getRawPhoneFromJid(jid);
    if (lidToPhoneMap.has(raw)) {
        return lidToPhoneMap.get(raw);
    }
    return raw;
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
            } catch (_) { }
        },
        writeToFile: (filePath) => {
            try {
                fs.writeFileSync(filePath, JSON.stringify({ chats, contacts, messages }, null, 2));
            } catch (_) { }
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
const app = express();
const PORT = process.env.PORT || process.env.WA_ENGINE_PORT || 3001;
const API_TOKEN = process.env.API_SECRET_TOKEN || 'wagtw_secret_token_2024';
const AUTH_DIR = path.join(__dirname, '.sessions');

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
    } catch (e) { }

    return res.status(403).json({ status: 'error', message: 'Unauthorized - Token tidak valid.' });
}

// ─── Database Pool ────────────────────────────────────────────────────────────
const isHostingEnv = (typeof (PhusionPassenger) !== 'undefined') ||
                     __dirname.includes('winseeop') ||
                     __dirname.includes('wagtw.winseeoptik.com') ||
                     process.env.USER === 'winseeop' ||
                     (process.env.HOME && process.env.HOME.includes('winseeop')) ||
                     process.platform === 'linux';

const hostingDbUser = 'winseeop_wagtw';
const hostingDbPass = 'winseeop_wagtw';
const hostingDbName = 'winseeop_wagtw';

let initialDbHost = process.env.DB_HOST || 'localhost';
let initialDbUser = process.env.DB_USER || (isHostingEnv ? hostingDbUser : 'root');
let initialDbPass = (process.env.DB_PASS !== undefined) ? process.env.DB_PASS : (isHostingEnv ? hostingDbPass : '');
let initialDbName = process.env.DB_NAME || (isHostingEnv ? hostingDbName : 'wagtw');

// Jika terdeteksi di lingkungan hosting cPanel namun env masih bernilai 'root' atau 'wagtw'
if (isHostingEnv && (initialDbUser === 'root' || initialDbName === 'wagtw')) {
    initialDbUser = hostingDbUser;
    initialDbPass = hostingDbPass;
    initialDbName = hostingDbName;
}

let db = mysql.createPool({
    host: initialDbHost,
    user: initialDbUser,
    password: initialDbPass,
    database: initialDbName,
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
});

// Auto-test and fallback between hosting credentials and local root
(async () => {
    try {
        await db.query('SELECT 1');
        await db.query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        console.log(`[DB] Connected successfully to database: ${initialDbName} (user: ${initialDbUser}, charset: utf8mb4)`);
    } catch (err) {
        console.warn(`[DB] Primary connection (${initialDbUser}@${initialDbHost}/${initialDbName}) failed (${err.code}). Trying fallback...`);
        const fallbackUser = (initialDbUser === hostingDbUser) ? 'root' : hostingDbUser;
        const fallbackPass = (initialDbUser === hostingDbUser) ? '' : hostingDbPass;
        const fallbackName = (initialDbUser === hostingDbUser) ? 'wagtw' : hostingDbName;
        try {
            const fallbackDb = mysql.createPool({
                host: 'localhost',
                user: fallbackUser,
                password: fallbackPass,
                database: fallbackName,
                charset: 'utf8mb4',
                waitForConnections: true,
                connectionLimit: 10,
            });
            await fallbackDb.query('SELECT 1');
            await fallbackDb.query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            db = fallbackDb;
            console.log(`[DB] Fallback connected successfully to: ${fallbackName} (user: ${fallbackUser}, charset: utf8mb4)`);
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

// ─── Incoming Message Deduplication Tracker ──────────────────────────────────
// Melacak ID pesan masuk untuk mencegah eksekusi ganda (Baileys v7 sering memancarkan
// event ganda 'notify' & 'append', atau retransmisi stanza dari WhatsApp)
const processedIncomingMsgIds = new Map(); // waMsgId -> timestamp
function isDuplicateIncomingMessage(waMsgId) {
    if (!waMsgId) return false;
    const now = Date.now();
    if (processedIncomingMsgIds.has(waMsgId)) {
        return true;
    }
    processedIncomingMsgIds.set(waMsgId, now);
    if (processedIncomingMsgIds.size > 3000) {
        const threshold = now - 10 * 60 * 1000; // Simpan cache selama 10 menit
        for (const [id, ts] of processedIncomingMsgIds.entries()) {
            if (ts < threshold) {
                processedIncomingMsgIds.delete(id);
            }
        }
    }
    return false;
}

// ─── In-Flight Autoreply/AI Lock per Sender ───────────────────────────────────
// Mencegah dua pemanggilan AI/Autoreply berjalan serentak untuk kontak yang sama
const activeReplyLocks = new Set();

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
            event: 'message',
            device: myPhone,
            sender: remotePhone,
            message: incomingText || '',
            name: senderName,
            is_group: isGroup,
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
 * Mengonversi nomor telepon di dalam teks menjadi link WhatsApp (https://wa.me/...) yang bisa langsung diklik
 * - Membersihkan karakter unicode rusak (diamond question mark \uFFFD)
 * - Menangani nomor telepon Indonesia (+62 / 62 / 08...)
 * - Mengabaikan nomor yang sudah berupa link / URL (wa.me, http://, https://)
 */
function makePhoneNumbersClickable(text) {
    if (!text || typeof text !== 'string') return '';

    // 1. Bersihkan karakter rusak seperti replacement character \uFFFD, zero-width spaces, dll
    let clean = text.replace(/[\uFFFD\u200B-\u200D\uFEFF]/g, '');

    // 2. Normalisasi berbagai jenis tanda hubung unicode (en-dash, em-dash, non-breaking hyphen)
    clean = clean.replace(/[\u2010\u2011\u2012\u2013\u2014\u2015]/g, '-');

    // 3. Regex mendeteksi nomor telepon Indonesia (+62 / 62 / 08...) tanpa mengubah URL yang sudah ada
    return clean.replace(/(?:(https?:\/\/[^\s]+|wa\.me\/[^\s]+)|(\+?62[\s\-]?8[0-9\s\-]{7,18}[0-9]|08[0-9\s\-]{8,16}[0-9]))/gi, (match, url, phone) => {
        if (url) {
            return url;
        }
        if (phone) {
            let digits = phone.replace(/[^0-9]/g, '');
            if (digits.startsWith('08')) {
                digits = '62' + digits.substring(1);
            } else if (digits.startsWith('8')) {
                digits = '62' + digits;
            }
            if (digits.length >= 10 && digits.length <= 15 && digits.startsWith('628')) {
                return `https://wa.me/${digits}`;
            }
        }
        return match;
    });
}

/**
 * Merapikan format balasan AI agar ramah WhatsApp:
 * - Mengonversi tabel Markdown menjadi daftar poin terstruktur
 * - Mengubah tag HTML (<br>, <b>, dll) menjadi format WhatsApp
 * - Menghilangkan double asterisks (**text** -> *text*)
 * - Merapikan spasi kosong ganda
 * - Mengonversi nomor telepon menjadi tautan wa.me yang bisa langsung diklik
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

    // 6. Format nomor telepon menjadi tautan wa.me yang bisa langsung diklik oleh pengguna
    result = makePhoneNumbersClickable(result);

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
        const checkPhones = [phone];
        const norm = normalizePhoneFromJid(phone);
        if (norm && !checkPhones.includes(norm)) checkPhones.push(norm);
        const raw = getRawPhoneFromJid(phone);
        if (raw && !checkPhones.includes(raw)) checkPhones.push(raw);

        if (lidToPhoneMap.has(phone)) {
            const mappedPn = lidToPhoneMap.get(phone);
            if (mappedPn && !checkPhones.includes(mappedPn)) checkPhones.push(mappedPn);
        }
        if (phoneToLidMap.has(phone)) {
            const mappedLid = phoneToLidMap.get(phone);
            if (mappedLid && !checkPhones.includes(mappedLid)) checkPhones.push(mappedLid);
        }

        if (rawJid) {
            const altRaw = getRawPhoneFromJid(rawJid);
            if (altRaw && !checkPhones.includes(altRaw)) checkPhones.push(altRaw);
            const altNorm = normalizePhoneFromJid(rawJid);
            if (altNorm && !checkPhones.includes(altNorm)) checkPhones.push(altNorm);
        }

        // 1. Hapus langsung berdasarkan semua alias phone / LID
        const placeholders = checkPhones.map(() => '?').join(',');
        await dbQuery(
            `DELETE FROM ai_muted_contacts WHERE device_id = ? AND phone IN (${placeholders})`,
            [deviceId, ...checkPhones]
        );

        // 2. Bersihkan juga nomor/LID terkait dari histori percakapan (antisipasi mapping LID vs Phone)
        const related = await dbQuery(
            `SELECT DISTINCT from_phone as num FROM messages WHERE to_phone = ? AND from_phone != ?
             UNION
             SELECT DISTINCT to_phone as num FROM messages WHERE from_phone = ? AND to_phone != ? LIMIT 10`,
            [phone, phone, phone, phone]
        ).catch(() => []);

        if (Array.isArray(related)) {
            for (const r of related) {
                if (r.num && r.num.length >= 5 && !checkPhones.includes(r.num)) {
                    await dbQuery('DELETE FROM ai_muted_contacts WHERE device_id = ? AND phone = ?', [deviceId, r.num]).catch(() => { });
                }
            }
        }

        console.log(`[AI UNMUTE] Kontak/Grup ${phone} (alias: ${checkPhones.join(', ')}) berhasil di-unmute (Device ${deviceId})`);
    } catch (e) {
        console.error('[AI UNMUTE] Error unmuting contact:', e.message);
    }
}

/**
 * Helper: Ambil target JID WhatsApp dari nomor telepon / LID
 * Mendukung format standard (@s.whatsapp.net), Group (@g.us), dan LID baru WhatsApp (@lid)
 */
function getJidFromPhone(phone, session = null) {
    if (!phone) return '';
    const cleanPhone = phone.toString().trim();
    if (cleanPhone.includes('@')) {
        return cleanPhone;
    }

    // 1. Cek store Baileys untuk mencari JID asli yang tersimpan (misal JID LID dengan suffix :device)
    if (session?.store) {
        const rawChats = session.store.chats?.array || (Array.isArray(session.store.chats) ? session.store.chats : []);
        const found = rawChats.find(c => {
            const id = c?.id || c?.jid || '';
            return normalizePhoneFromJid(id) === cleanPhone;
        });
        if (found?.id) return found.id;

        if (session.store.messages) {
            for (const jid of Object.keys(session.store.messages)) {
                if (normalizePhoneFromJid(jid) === cleanPhone) {
                    return jid;
                }
            }
        }
    }

    // 2. Format Grup WhatsApp
    if (cleanPhone.includes('-') || cleanPhone.endsWith('@g.us')) {
        return cleanPhone.endsWith('@g.us') ? cleanPhone : `${cleanPhone}@g.us`;
    }

    // 3. Format LID (Linked Identity Device): nomor WA berdigit >= 14 yang BUKAN nomor telepon Indonesia/normal
    if (cleanPhone.length >= 14 && !cleanPhone.startsWith('62') && !cleanPhone.startsWith('0')) {
        return `${cleanPhone}@lid`;
    }

    // 4. Format nomor telepon biasa
    return `${cleanPhone}@s.whatsapp.net`;
}

/**
 * Auto-Followup: Saat kontak di-unmute (baik oleh admin di web, kata kunci #selesai, atau expired timer),
 * periksa apakah ada pesan masuk terakhir dari kontak yang BELUM dibalas oleh admin / AI.
 * Jika ada pesan tertunda, bot AI akan otomatis memproses dan membalasnya!
 */
async function triggerAutoReplyForUnmutedContact(deviceId, customerPhone, sessionId = null, reason = 'auto') {
    if (!deviceId || !customerPhone) return { replied: false, reason: 'missing_params' };
    const cleanPhone = normalizePhoneFromJid(customerPhone) || customerPhone.toString().trim();

    try {
        let targetSessionId = sessionId;
        if (!targetSessionId) {
            const devs = await dbQuery('SELECT session_id FROM devices WHERE id = ? LIMIT 1', [deviceId]);
            if (devs.length) targetSessionId = devs[0].session_id;
        }

        if (!targetSessionId) {
            console.warn(`[AI AUTO-FOLLOWUP] Device ${deviceId} tidak memiliki session_id.`);
            return { replied: false, reason: 'no_session' };
        }

        const session = sessions[targetSessionId];
        if (!session?.socket || session.status !== 'connected') {
            console.warn(`[AI AUTO-FOLLOWUP] Sesi ${targetSessionId} belum connected. Lewati auto-reply.`);
            return { replied: false, reason: 'session_not_connected' };
        }

        // Cari riwayat pesan terbaru antara kontak ini dan device
        const recentMsgs = await dbQuery(`
            SELECT id, from_phone, to_phone, direction, message, created_at 
            FROM messages 
            WHERE device_id = ? AND (from_phone = ? OR to_phone = ? OR from_phone LIKE ? OR to_phone LIKE ?)
            ORDER BY id DESC LIMIT 10
        `, [deviceId, cleanPhone, cleanPhone, `%${cleanPhone}%`, `%${cleanPhone}%`]);

        if (!recentMsgs || !recentMsgs.length) {
            console.log(`[AI AUTO-FOLLOWUP] Tidak ada riwayat pesan untuk kontak ${cleanPhone}.`);
            return { replied: false, reason: 'no_messages' };
        }

        // Evaluasi pesan nyata terbaru (abaikan notifikasi sistem atau perintah kata kunci)
        let pendingCustomerMsg = null;
        for (const m of recentMsgs) {
            const text = (m.message || '').trim();
            if (!text) continue;
            const lower = text.toLowerCase();

            // Abaikan pesan konfirmasi sistem pengaktifan kembali
            if (lower.includes('sesi percakapan dengan admin telah selesai') || 
                lower.includes('bot ai telah diaktifkan kembali') ||
                lower.includes('bot ai kami kini aktif kembali')) {
                continue;
            }

            // Abaikan kata kunci perintah admin / bot
            if (lower === '#selesai' || lower === 'selesai' || lower === 'akhiri percakapan' || lower === '#ai on' || lower === 'aktifkan ai') {
                continue;
            }

            // Pesan nyata terbaru:
            if (m.direction === 'in') {
                pendingCustomerMsg = text;
            } else {
                // Pesan nyata terbaru berarah keluar (sudah dibalas manusia/admin)
                pendingCustomerMsg = null;
            }
            break;
        }

        if (pendingCustomerMsg) {
            console.log(`[AI AUTO-FOLLOWUP] Kontak ${cleanPhone} memiliki pesan yang belum dibalas (${reason}): "${pendingCustomerMsg.substring(0, 50)}...". Memicu balasan AI...`);

            const senderJid = getJidFromPhone(cleanPhone, session);

            // Beri jeda 800ms agar status & database stabil
            await new Promise(r => setTimeout(r, 800));

            // Panggil processAutoreply dengan skipMuteCheck = true
            await processAutoreply(targetSessionId, senderJid, pendingCustomerMsg, null, true).catch(err => {
                console.error(`[AI AUTO-FOLLOWUP ERR] Gagal membalas kontak ${cleanPhone}:`, err.message);
            });

            return { replied: true, text: pendingCustomerMsg };
        } else {
            console.log(`[AI AUTO-FOLLOWUP] Kontak ${cleanPhone} tidak memiliki pesan tertunda atau sudah dijawab. Tidak perlu auto-reply.`);
            return { replied: false, reason: 'already_answered' };
        }
    } catch (err) {
        console.error(`[AI AUTO-FOLLOWUP FATAL] Kontak ${cleanPhone}:`, err.message);
        return { replied: false, error: err.message };
    }
}

/**
 * Bersihkan data mute kontak yang masa berlakunya telah kadaluarsa dari database
 * FITUR AUTO-FOLLOWUP: Jika selama masa mute admin belum membalas pesan terakhir pelanggan,
 * maka saat AI aktif kembali, AI akan otomatis membalas pesan yang belum terjawab tersebut!
 */
async function cleanupExpiredMutedContacts() {
    try {
        const nowSql = formatDateToSql(new Date());
        const expiredRows = await dbQuery(`
            SELECT mc.id, mc.device_id, mc.phone, mc.reason, d.session_id, d.phone as device_phone
            FROM ai_muted_contacts mc
            JOIN devices d ON d.id = mc.device_id
            WHERE (mc.muted_until IS NOT NULL AND mc.muted_until <= ?) 
               OR (mc.muted_until IS NULL AND mc.muted_date < CURRENT_DATE() AND mc.reason != 'manual')
        `, [nowSql]);

        if (!expiredRows || !expiredRows.length) return;

        console.log(`[AI REACTIVATE] Ditemukan ${expiredRows.length} kontak yang masa mute-nya telah berakhir. Mengaktifkan AI kembali...`);

        for (const row of expiredRows) {
            // Cek apakah device dan sesi WhatsApp sedang aktif/terkoneksi
            const sessionId = row.session_id;
            const session = sessions[sessionId];
            if (!session?.socket || session.status !== 'connected') {
                // Sesi WhatsApp sedang offline/reconnecting, tunda 30 detik agar dicoba kembali saat koneksi pulih
                const retrySoon = formatDateToSql(new Date(Date.now() + 30 * 1000));
                await dbQuery('UPDATE ai_muted_contacts SET muted_until = ? WHERE id = ?', [retrySoon, row.id]).catch(() => { });
                continue;
            }

            // Hapus dari ai_muted_contacts setelah dipastikan sesi WhatsApp siap
            await dbQuery('DELETE FROM ai_muted_contacts WHERE id = ?', [row.id]).catch(() => { });

            const customerPhone = row.phone;
            // Otomatis balas pesan tertunda pelanggan jika ada
            await triggerAutoReplyForUnmutedContact(row.device_id, customerPhone, sessionId, `expired_${row.reason}`);
        }
    } catch (e) {
        console.warn('[AI MUTE CLEANUP] Error:', e.message);
    }
}

/**
 * Deteksi apakah teks pesan adalah template bawaan iklan Meta / pertanyaan promo & lokasi
 * (Contoh: "Saya Mau Cek Mata Gratis !!", "Lok dimana ya", "Paket kacamata 75K")
 */
function isAdLeadContent(text) {
    if (!text || typeof text !== 'string') return false;
    const clean = text.trim().toLowerCase();

    // 1. Template bawaan tombol Iklan Meta (Winsee Optik)
    const adTemplates = [
        'cek mata gratis', 'cek mata', 'periksa mata gratis', 'tes mata gratis',
        'paket kacamata', 'paket kacamata 75k', '75k', 'promo kacamata',
        'frame dan lensa gratis', 'lensa gratis', 'frame gratis',
        'promo optik', 'koleksi optik', 'diskon optik',
        'mau kacamata', 'beli kacamata', 'pesan kacamata', 'gratis lensa'
    ];
    if (adTemplates.some(tpl => clean.includes(tpl))) return true;

    // 2. Pertanyaan lokasi / cabang toko optik yang sering ditanyakan pelanggan iklan
    const locationKeywords = [
        'lok dimana', 'lok dimana ya', 'lokasi dimana', 'lokasi di mana', 'alamat dimana',
        'alamat di mana', 'posisi dimana', 'ada di mana', 'ada dimana', 'daerah mana',
        'cabang dimana', 'cabang di mana', 'optik nya dimana', 'optiknya dimana', 'tokonya dimana',
        'di mn', 'dmn', 'dimn', 'optiknya', 'optik nya'
    ];
    if (locationKeywords.some(kw => clean.includes(kw))) return true;

    return false;
}

/**
 * Deteksi apakah pesan berasal dari Iklan META (Click-to-WhatsApp Ads / CTWA)
 * Meneliti metadata contextInfo, externalAdReply, entryPointConversion, dan ctwaPayload
 */
function isMetaAdMessage(msg) {
    if (!msg || !msg.message) return false;

    let m = msg.message;
    // Unwrap wrappers jika ada
    while (m) {
        if (m.ephemeralMessage?.message) m = m.ephemeralMessage.message;
        else if (m.viewOnceMessage?.message) m = m.viewOnceMessage.message;
        else if (m.viewOnceMessageV2?.message) m = m.viewOnceMessageV2.message;
        else if (m.documentWithCaptionMessage?.message) m = m.documentWithCaptionMessage.message;
        else if (m.editedMessage?.message?.protocolMessage?.editedMessage) m = m.editedMessage.message.protocolMessage.editedMessage;
        else break;
    }

    const contextInfo = m.extendedTextMessage?.contextInfo
        || m.imageMessage?.contextInfo
        || m.videoMessage?.contextInfo
        || m.interactiveMessage?.contextInfo
        || m.interactiveResponseMessage?.contextInfo
        || m.templateMessage?.contextInfo
        || m.buttonsResponseMessage?.contextInfo
        || m.contextInfo
        || null;

    if (contextInfo) {
        // 1. externalAdReply (preview/kartu iklan Facebook / Instagram)
        if (contextInfo.externalAdReply) {
            return true;
        }
        // 2. entryPointConversionApp / Source (ctwa facebook/instagram)
        if (contextInfo.entryPointConversionApp || contextInfo.entryPointConversionSource) {
            return true;
        }
        // 3. Payload konversi iklan Meta
        if (contextInfo.ctwaPayload || contextInfo.conversionData) {
            return true;
        }
    }

    // 4. Cek interactiveMessage nativeFlow dari CTA iklan
    if (m.interactiveMessage?.header?.hasMediaAttachment && m.interactiveMessage?.nativeFlowMessage) {
        return true;
    }

    return false;
}

/**
 * Ekstraksi teks pesan masuk secara menyeluruh dari segala jenis pesan WhatsApp & Baileys
 * Mendukung teks biasa, media caption, interactive buttons, quick reply, template, dan iklan CTWA
 */
function extractIncomingText(msg) {
    if (!msg || !msg.message) return '';

    let m = msg.message;

    // Unwrap wrappers recursively
    while (m) {
        if (m.ephemeralMessage?.message) {
            m = m.ephemeralMessage.message;
        } else if (m.viewOnceMessage?.message) {
            m = m.viewOnceMessage.message;
        } else if (m.viewOnceMessageV2?.message) {
            m = m.viewOnceMessageV2.message;
        } else if (m.documentWithCaptionMessage?.message) {
            m = m.documentWithCaptionMessage.message;
        } else if (m.editedMessage?.message?.protocolMessage?.editedMessage) {
            m = m.editedMessage.message.protocolMessage.editedMessage;
        } else {
            break;
        }
    }

    // 1. Teks langsung
    if (m.conversation) return m.conversation.trim();
    if (m.extendedTextMessage?.text) return m.extendedTextMessage.text.trim();

    // 2. Tombol dan template balasan
    if (m.buttonsResponseMessage?.selectedDisplayText) return m.buttonsResponseMessage.selectedDisplayText.trim();
    if (m.buttonsResponseMessage?.selectedButtonId) return m.buttonsResponseMessage.selectedButtonId.trim();
    if (m.templateButtonReplyMessage?.selectedDisplayText) return m.templateButtonReplyMessage.selectedDisplayText.trim();
    if (m.templateButtonReplyMessage?.selectedId) return m.templateButtonReplyMessage.selectedId.trim();
    if (m.listResponseMessage?.title) return m.listResponseMessage.title.trim();
    if (m.listResponseMessage?.singleSelectReply?.selectedRowId) return m.listResponseMessage.singleSelectReply.selectedRowId.trim();

    // 3. Interactive Response (Native Flow / Quick Reply buttons dari Iklan Meta)
    if (m.interactiveResponseMessage?.nativeFlowResponseMessage?.paramsJson) {
        try {
            const btnJson = JSON.parse(m.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson);
            const res = btnJson.display_text || btnJson.id || btnJson.title || '';
            if (res) return String(res).trim();
        } catch (_) {
            return String(m.interactiveResponseMessage.nativeFlowResponseMessage.paramsJson).trim();
        }
    }

    // 4. Interactive Message (WhatsApp Business / CTWA ad body text)
    if (m.interactiveMessage?.body?.text) return m.interactiveMessage.body.text.trim();
    if (m.interactiveMessage?.header?.title) return m.interactiveMessage.header.title.trim();

    // 5. Caption media
    if (m.imageMessage?.caption) return m.imageMessage.caption.trim();
    if (m.videoMessage?.caption) return m.videoMessage.caption.trim();
    if (m.documentMessage?.caption) return m.documentMessage.caption.trim();

    // 6. Fallback: Context externalAdReply title/body jika tidak ada teks lain dari pelanggan
    const contextInfo = m.extendedTextMessage?.contextInfo
        || m.imageMessage?.contextInfo
        || m.interactiveMessage?.contextInfo
        || m.contextInfo;

    if (contextInfo?.externalAdReply) {
        const ad = contextInfo.externalAdReply;
        if (ad.title && ad.body) return `${ad.title} - ${ad.body}`.trim();
        if (ad.title) return ad.title.trim();
        if (ad.body) return ad.body.trim();
    }

    return '';
}

/**
 * Deteksi pemicu Chat Admin dari teks pesan masuk
 * HANYA aktif jika pelanggan benar-benar meminta bantuan admin/manusia secara eksplisit.
 * Pertanyaan biasa seperti "Halo admin, alamat di mana?" atau "mau tanya admin..." TIDAK akan memicu mute.
 */
function isChatAdminTrigger(text) {
    if (!text || typeof text !== 'string') return false;
    const clean = text.trim().toLowerCase().replace(/[^\w\s#]/g, ' ');
    const normalized = clean.replace(/\s+/g, ' ').trim();

    // ── CEK KATA PERTANYAAN / INQUIRY ───────────────────────────────────────
    // Jika pesan mengandung pertanyaan seputar info toko, produk, harga, promo, atau lokasi,
    // JANGAN PERNAH MUTE! Biarkan Bot AI menjawab pertanyaan pelanggan tersebut.
    const inquiryKeywords = [
        'dimana', 'di mana', 'lokasi', 'alamat', 'cabang', 'toko',
        'harga', 'berapa', 'biaya', 'ongkir', 'tarif', 'price',
        'promo', 'diskon', 'discount', 'voucher', 'potongan', 'gratis', 'free',
        'jam buka', 'buka jam', 'tutup jam', 'operasional', 'jadwal',
        'kacamata', 'frame', 'lensa', 'minus', 'plus', 'silinder', 'cylinder',
        'periksa', 'cek mata', 'resep', 'optik', 'garansi', 'model', 'katalog',
        'bisa', 'apakah', 'ada', 'ready', 'stok', 'stock'
    ];
    const hasInquiry = inquiryKeywords.some(kw => normalized.includes(kw));
    if (hasInquiry) {
        return false; // Pelanggan sedang bertanya info, BUKAN meminta staf admin manual!
    }

    // 1. Kata kunci langsung / frasa pendek admin
    if (normalized === 'admin' || normalized === 'cs' || normalized === 'operator') return true;
    if (normalized === '#admin' || normalized === '!admin' || normalized === 'chat_admin') return true;

    // 2. Frasa spesifik chat admin
    if (normalized === 'chat admin' || normalized === 'chatt admin' || normalized === 'hubungi admin') return true;
    if (normalized.includes('bantuan admin') || normalized.includes('kontak admin')) return true;
    if (normalized.includes('bicara dengan admin') || normalized.includes('bicara admin') || normalized.includes('ngomong sama admin') || normalized.includes('bicara sama admin')) return true;
    if (normalized.includes('sambungkan ke admin') || normalized.includes('sambungkan admin') || normalized.includes('alih kan ke admin') || normalized.includes('panggil admin')) return true;

    // 3. Sapaan singkat HANYA kepada admin tanpa pertanyaan lanjutan (contoh: persis "halo admin", "halo cs", "pagi admin")
    if (/^(halo|hai|hi|hei|pagi|siang|sore|malam)?\s*(admin|cs|operator)\s*(dong|ya|tolong|pls|please)?$/i.test(normalized)) {
        return true;
    }

    // 4. Pola kombinasi tindakan meminta terhubung dengan staf/manusia (BUKAN sekadar bertanya "mau tanya admin...", "tanya staff...")
    if (/\b(minta|tolong|mau|bisa|mohon)\s+(chat|chatt|hubungi|kontak|bicara|ngobrol|ngomong|sambungkan|dialihkan|alih)\b.*\b(admin|staf|staff|cs|operator|customer service|manusia|human|orang asli)\b/i.test(normalized)) {
        return true;
    }

    // 5. Pola eksplisit menolak bot
    if (/\b(manusia|orang asli|bukan bot|bukan ai|jangan bot|stop bot)\b/i.test(normalized)) {
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
function calculateMutedUntil(durationSetting, reason = null) {
    const now = new Date();

    // 1. Jika AI limit (Rate Limit / Quota / 429): waktu mute tepat 1 menit agar membalas kembali setelah 1 menit
    if (reason === 'ai_rate_limit') {
        return new Date(now.getTime() + 1 * 60 * 1000); // 1 Menit
    }

    // 2. Jika AI mati / API error, berikan waktu retry default (1 menit) jika durasi diatur ke manual/next_day
    if ((reason === 'ai_server_down' || reason === 'ai_error') && (durationSetting === 'manual' || durationSetting === 'next_day')) {
        return new Date(now.getTime() + 1 * 60 * 1000); // 1 Menit retry default
    }

    // 3. Jika gagal kirim pesan WhatsApp (ai_send_failed):
    // Masuk ke AI mute dan coba kembali membalas setelah DURASI YANG DITENTUKAN oleh setting device.
    if (reason === 'ai_send_failed') {
        if (durationSetting === 'manual') {
            return new Date(now.getTime() + 5 * 60 * 1000); // Fallback 5 menit jika mode manual
        }
    }

    switch (durationSetting) {
        case '1_minute':
            return new Date(now.getTime() + 1 * 60 * 1000);
        case '5_minutes':
            return new Date(now.getTime() + 5 * 60 * 1000);
        case '15_minutes':
            return new Date(now.getTime() + 15 * 60 * 1000);
        case '30_minutes':
            return new Date(now.getTime() + 30 * 60 * 1000);
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
            return new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0);
        default:
            // Format custom manual: misal "custom_1m" (1 menit), "custom_10m" (10 menit) atau "custom_2h" (2 jam)
            if (typeof durationSetting === 'string' && durationSetting.startsWith('custom_')) {
                const raw = durationSetting.replace('custom_', '').trim();
                if (raw.endsWith('m')) {
                    const mins = parseInt(raw);
                    if (!isNaN(mins) && mins > 0) return new Date(now.getTime() + mins * 60 * 1000);
                } else if (raw.endsWith('h')) {
                    const hrs = parseInt(raw);
                    if (!isNaN(hrs) && hrs > 0) return new Date(now.getTime() + hrs * 60 * 60 * 1000);
                } else {
                    const val = parseInt(raw);
                    if (!isNaN(val) && val > 0) return new Date(now.getTime() + val * 60 * 1000);
                }
            }
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
async function isContactMuted(deviceId, phone, rawJid = null) {
    if (!deviceId || !phone) return false;
    try {
        const checkPhones = [phone];
        const norm = normalizePhoneFromJid(phone);
        if (norm && !checkPhones.includes(norm)) checkPhones.push(norm);
        const raw = getRawPhoneFromJid(phone);
        if (raw && !checkPhones.includes(raw)) checkPhones.push(raw);

        if (lidToPhoneMap.has(phone)) {
            const mappedPn = lidToPhoneMap.get(phone);
            if (mappedPn && !checkPhones.includes(mappedPn)) checkPhones.push(mappedPn);
        }
        if (phoneToLidMap.has(phone)) {
            const mappedLid = phoneToLidMap.get(phone);
            if (mappedLid && !checkPhones.includes(mappedLid)) checkPhones.push(mappedLid);
        }

        if (rawJid) {
            const altRaw = getRawPhoneFromJid(rawJid);
            if (altRaw && !checkPhones.includes(altRaw)) checkPhones.push(altRaw);
            const altNorm = normalizePhoneFromJid(rawJid);
            if (altNorm && !checkPhones.includes(altNorm)) checkPhones.push(altNorm);
        }

        const placeholders = checkPhones.map(() => '?').join(',');
        const rows = await dbQuery(
            `SELECT id, muted_date, muted_until, reason FROM ai_muted_contacts 
             WHERE device_id = ? AND phone IN (${placeholders}) ORDER BY id DESC LIMIT 1`,
            [deviceId, ...checkPhones]
        );
        if (!rows.length) return false;

        const row = rows[0];
        const now = new Date();

        // 1. Jika terdapat batas waktu spesifik (muted_until)
        if (row.muted_until) {
            const until = new Date(row.muted_until);
            if (now >= until) {
                console.log(`[AI MUTE EXPIRED] Kontak ${phone} masa mute telah berakhir (${row.muted_until}). Mengaktifkan Bot AI kembali.`);
                await dbQuery('DELETE FROM ai_muted_contacts WHERE id = ?', [row.id]).catch(() => { });
                return false;
            }
            return true;
        }

        // 2. Jika mode manual (hanya aktif bila admin ketik kata kunci)
        if (row.reason === 'manual') {
            return true;
        }

        // 3. Fallback jika muted_until null (next_day cek tanggal hari ini berdasarkan waktu lokal server)
        const pad = (n) => String(n).padStart(2, '0');
        const todayStr = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
        const rowDateStr = row.muted_date ? (row.muted_date instanceof Date ? `${row.muted_date.getFullYear()}-${pad(row.muted_date.getMonth() + 1)}-${pad(row.muted_date.getDate())}` : String(row.muted_date).slice(0, 10)) : '';
        if (rowDateStr === todayStr) {
            return true;
        } else {
            await dbQuery('DELETE FROM ai_muted_contacts WHERE id = ?', [row.id]).catch(() => { });
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
        const mutedUntilDate = calculateMutedUntil(durationSetting, reason);
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

        if (durationSetting.includes('_hour') || durationSetting.includes('_minute') || durationSetting.startsWith('custom_') || rows[0].reason === 'ai_server_down' || rows[0].reason === 'ai_error' || rows[0].reason === 'ai_rate_limit' || rows[0].reason === 'ai_send_failed') {
            const newUntil = calculateMutedUntil(durationSetting, rows[0].reason);
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
async function processAutoreply(sessionId, senderJid, incomingText, fullMsg = null, skipMuteCheck = false) {
    if (!incomingText || !sessionId || !senderJid) return;

    // Normalisasi dan pastikan format JID akurat (termasuk deteksi LID v7)
    const session = sessions[sessionId];
    if (session) {
        senderJid = getJidFromPhone(senderJid, session);
    }

    // Cegah pemrosesan paralel/ganda untuk sesi dan nomor kontak yang sama
    const lockKey = `${sessionId}:${senderJid}`;
    if (activeReplyLocks.has(lockKey)) {
        console.log(`[AUTOREPLY BUSY] Sedang memproses respon untuk ${senderJid}, mengabaikan pemrosesan paralel.`);
        return;
    }
    activeReplyLocks.add(lockKey);

    try {
        // Cari device beserta pengaturan bisnisnya berdasarkan session_id
        const devices = await dbQuery('SELECT * FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
        if (!devices.length) return;

        const deviceId = devices[0].id;
        const userId = devices[0].user_id;

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
        const isAiEnabled = !!(aiConfig && (aiConfig.ai_enabled == 1 || aiConfig.ai_enabled === true || aiConfig.ai_enabled === '1'));

        // Cek Target Balasan (Pribadi/Grup)
        const targetReply = aiConfig ? (aiConfig.target_reply || 'both') : 'both';
        const isGroup = senderJid.endsWith('@g.us');

        // ── DETEKSI PESAN DARI IKLAN META (Facebook / Instagram Ads - CTWA) ATAU TEMPLATE PROMO ───
        const isFromMetaAd = isMetaAdMessage(fullMsg) || isAdLeadContent(incomingText);
        if (isFromMetaAd) {
            console.log(`[META ADS / PROMO LEAD] Kontak ${remotePhone} (${senderJid}) terdeteksi lead iklan/promo: "${incomingText.substring(0, 50)}". Memastikan Bot AI langsung merespons.`);
            // Jika kontak sebelumnya pernah di-mute (termasuk false mute admin_talking / sesi lama), otomatis unmute sekarang!
            await unmuteContact(deviceId, remotePhone, senderJid);
        }

        // ── FITUR AI (UNMUTE, CHAT ADMIN, DAN MUTE) HANYA BERJALAN JIKA AI AKTIF ──
        if (isAiEnabled) {
            // 1. FITUR UNMUTE / REAKTIVASI AI (Pribadi maupun Grup)
            const cleanCmd = incomingText.trim().toLowerCase();
            const isCustomerReactivate = cleanCmd === '#ai on' || cleanCmd === '#ai aktif' || cleanCmd === '!ai on' || cleanCmd === '/ai on' || cleanCmd === 'aktifkan ai'
                || isEndConversationKeyword(incomingText, aiConfig?.ai_reactivate_keyword);

            if (isCustomerReactivate) {
                console.log(`[AI UNMUTE] Kontak/Grup ${remotePhone} meminta reaktivasi Bot AI (Teks: '${incomingText}').`);
                await unmuteContact(deviceId, remotePhone, senderJid);
                const reactivatedText = `🤖 *Bot AI telah diaktifkan kembali* untuk percakapan ini. Silakan tanyakan apa saja! 😊`;

                // Tampilkan simulasi mengetik singkat yang responsif
                await sock.sendPresenceUpdate('composing', senderJid).catch(() => { });
                await new Promise(r => setTimeout(r, 600));

                try {
                    const sentMsg = await sock.sendMessage(senderJid, { text: reactivatedText }, { quoted: fullMsg });
                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                    markBotSentMessage(sentMsg?.key?.id);
                    await saveMessageToDB(sessionId, sentMsg?.key?.id || null, myPhone, remotePhone, 'text', reactivatedText, 'out', 'sent');
                } catch (_) {
                    const sentMsg = await sock.sendMessage(senderJid, { text: reactivatedText }).catch(() => { });
                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                    markBotSentMessage(sentMsg?.key?.id);
                }

                // Cek apakah ada teks pertanyaan tambahan dari pelanggan dalam pesan aktivasi yang sama
                const extraQuestion = incomingText.replace(/#ai on|#ai aktif|!ai on|\/ai on|aktifkan ai/gi, '').trim();
                if (extraQuestion.length >= 4) {
                    console.log(`[AI UNMUTE] Pelanggan menyertakan pertanyaan saat reaktivasi: "${extraQuestion}". Memproses respon AI...`);
                    await new Promise(r => setTimeout(r, 800));
                    await processAutoreply(sessionId, senderJid, extraQuestion, null, true).catch(() => {});
                }
                return;
            }

            // 2. FITUR CHAT ADMIN & HANDOFF (Pribadi maupun Grup)
            // PENTING: Jika pesan datang dari Iklan META (CTWA), JANGAN mute karena tombol ad CTA Meta sering berjudul "Chat Admin"!
            // Kecuali jika pelanggan secara eksplisit mengetik meminta manusia/orang asli ("bukan bot", "orang asli", "manusia").
            const explicitHumanDemand = /\b(manusia|orang asli|bukan bot|bukan ai|jangan bot|stop bot)\b/i.test(incomingText);

            if (!isFromMetaAd || explicitHumanDemand) {
                if (isChatAdminTrigger(incomingText)) {
                    console.log(`[CHAT ADMIN] Kontak/Grup ${remotePhone} memicu Chat Admin. Me-mute Bot AI.`);
                    await muteContact(deviceId, remotePhone, 'chat_admin');

                    // Gunakan template kustom handoff dari device_ai_settings jika ada
                    const handoffText = (aiConfig && aiConfig.ai_handoff_text && aiConfig.ai_handoff_text.trim())
                        ? aiConfig.ai_handoff_text.trim()
                        : `Baik, permintaan Anda telah kami teruskan ke *Admin / Staf kami* 👤\n\nAdmin kami akan segera membaca dan merespons chat Anda secara langsung. Sementara ini, Bot AI dinonaktifkan untuk percakapan ini dan akan aktif kembali besok hari.\n\nTerima kasih atas kesabaran Anda! 🙏`;

                    // Tampilkan simulasi mengetik singkat yang responsif
                    await sock.sendPresenceUpdate('composing', senderJid).catch(() => { });
                    await new Promise(r => setTimeout(r, 600));

                    try {
                        let sentMsg = null;
                        try {
                            sentMsg = await sock.sendMessage(senderJid, { text: handoffText }, { quoted: fullMsg });
                        } catch (quotedErr) {
                            sentMsg = await sock.sendMessage(senderJid, { text: handoffText });
                        }
                        await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                        const waMsgId = sentMsg?.key?.id || null;
                        markBotSentMessage(waMsgId);
                        await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', handoffText, 'out', 'sent');
                    } catch (err) {
                        await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                        console.error('[Chat Admin Handoff Send Error]', err.message);
                    }
                    return; // Hentikan di sini, jangan jalankan AI atau autoreply
                }
            }

            // 3. CEK STATUS MUTE (Pribadi maupun Grup)
            // Lead baru dari Iklan Meta / Promo tidak boleh diblokir oleh mute lama agar AI langsung merespons
            if (!skipMuteCheck && !isFromMetaAd) {
                const isMuted = await isContactMuted(deviceId, remotePhone, senderJid);
                if (isMuted) {
                    // PENTING: Cek apakah mute ini adalah 'admin_talking' yang disebabkan oleh salam otomatis / false mute
                    // Jika pelanggan sedang bertanya info toko/produk dan tidak ada balasan admin baru,
                    // maka hapus mute dan izinkan AI membalas langsung!
                    const mutedRows = await dbQuery(
                        'SELECT reason, updated_at FROM ai_muted_contacts WHERE device_id = ? AND phone = ? ORDER BY id DESC LIMIT 1',
                        [deviceId, remotePhone]
                    );

                    const isFalseAdminMute = mutedRows.length && mutedRows[0].reason === 'admin_talking';
                    if (isFalseAdminMute) {
                        const inquiryKeywords = [
                            'dimana', 'di mana', 'lokasi', 'alamat', 'cabang', 'toko',
                            'harga', 'berapa', 'biaya', 'ongkir', 'tarif', 'price',
                            'promo', 'diskon', 'voucher', 'potongan', 'gratis', 'free',
                            'jam buka', 'buka jam', 'tutup jam', 'operasional', 'jadwal',
                            'kacamata', 'frame', 'lensa', 'minus', 'plus', 'silinder',
                            'periksa', 'cek mata', 'resep', 'optik', 'bisa', 'apakah', 'ada',
                            'lok dimana', 'lokasi optik', 'alamat optik'
                        ];
                        const cleanLower = incomingText.trim().toLowerCase();
                        const isAskingQuestion = inquiryKeywords.some(kw => cleanLower.includes(kw));

                        if (isAskingQuestion) {
                            console.log(`[AI UNMUTE - ACTIVE INQUIRY] Kontak ${remotePhone} sedang bertanya ("${incomingText.substring(0, 50)}") dan tidak ada staf manusia aktif. Mengaktifkan AI untuk membalas...`);
                            await unmuteContact(deviceId, remotePhone, senderJid);
                            // Lanjut proses balasan AI!
                        } else {
                            console.log(`[AI MUTED] Kontak/Grup ${remotePhone} sedang dalam sesi Admin. Bot AI & autoreply dinonaktifkan.`);
                            await touchMutedContact(deviceId, remotePhone);
                            return;
                        }
                    } else {
                        console.log(`[AI MUTED] Kontak/Grup ${remotePhone} sedang dalam sesi Admin (${mutedRows[0]?.reason || 'muted'}). Bot AI dinonaktifkan.`);
                        await touchMutedContact(deviceId, remotePhone);
                        return;
                    }
                }
            }
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
                        const sentMsg = await sock.sendMessage(senderJid, { text: devData.welcome_message }).catch(() => { });
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
                                const sentMsg = await sock.sendMessage(senderJid, { text: devData.away_message }).catch(() => { });
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
                case 'exact': matched = text === trigger; break;
                case 'startswith': matched = text.startsWith(trigger); break;
                case 'contains':
                default: matched = text.includes(trigger); break;
            }

            if (matched) {
                keywordMatched = true;
                const configuredDelay = (rule.delay !== undefined && rule.delay !== null) ? (rule.delay * 1000) : 1000;
                // Simulasi mengetik singkat yang responsif (500ms - 1500ms)
                const typingDuration = Math.min(1500, Math.max(500, configuredDelay));

                await sock.sendPresenceUpdate('composing', senderJid).catch(() => { });
                await new Promise(r => setTimeout(r, typingDuration));

                try {
                    // Gunakan quoted msg agar thread konteks valid (terutama pada pesan pribadi / LID)
                    let sentMsg = null;
                    try {
                        sentMsg = await sock.sendMessage(senderJid, { text: rule.reply_text }, { quoted: fullMsg });
                    } catch (quotedErr) {
                        console.warn(`[Autoreply Quoted Error] ${quotedErr.message}, mencoba kirim pesan langsung...`);
                        sentMsg = await sock.sendMessage(senderJid, { text: rule.reply_text });
                    }

                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                    console.log(`[Autoreply] "${rule.trigger_word}" matched (${ruleTarget}) → kirim ke ${senderJid}`);

                    // Simpan balasan manual autoreply ke histori DB
                    const waMsgId = sentMsg?.key?.id || null;
                    markBotSentMessage(waMsgId);
                    await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', rule.reply_text, 'out', 'sent');
                } catch (err) {
                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
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

        const aiEnabled = isAiEnabled && isTargetMatch && isAutoAi;
        const searchEnabled = aiConfig && aiConfig.search_enabled == 1;

        // ── MODE AI: Gunakan Groq ─────────────────────────────────────────────────
        if (aiEnabled) {
            console.log(`[AI] Device ${deviceId} — Mode AI aktif, menghubungi Groq...`);
            const aiStartTime = Date.now();

            // Tampilkan indikator mengetik selama AI berpikir
            await sock.sendPresenceUpdate('composing', senderJid).catch(() => { });

            try {
                // Ambil 8 pesan terakhir untuk context history dari Baileys Store atau Database
                let chatContext = [];
                const historyMsgs = session.store?.messages[senderJid]?.array?.slice(-8) || [];
                if (historyMsgs.length > 0) {
                    chatContext = historyMsgs.map(msg => {
                        const isFromMe = msg.key.fromMe;
                        const text = msg.message?.conversation || msg.message?.extendedTextMessage?.text || '';
                        return {
                            role: isFromMe ? 'assistant' : 'user',
                            content: text
                        };
                    }).filter(c => c.content);
                } else {
                    try {
                        const dbMsgs = await dbQuery(`
                            SELECT direction, message 
                            FROM messages 
                            WHERE device_id = ? AND (from_phone = ? OR to_phone = ?)
                            ORDER BY id DESC LIMIT 5
                        `, [deviceId, remotePhone, remotePhone]);
                        if (dbMsgs && dbMsgs.length > 0) {
                            chatContext = dbMsgs.reverse().map(m => ({
                                role: m.direction === 'out' ? 'assistant' : 'user',
                                content: (typeof m.message === 'string' && m.message.length > 400) ? (m.message.substring(0, 400) + '...') : m.message
                            })).filter(c => c.content);
                        }
                    } catch (dbHistErr) {
                        console.warn('[AI Context DB Error]', dbHistErr.message);
                    }
                }

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
7. Jawab secara lengkap, jelas, dan tuntas sampai selesai.
8. FORMAT NOMOR TELEPON/WHATSAPP: Jika memberikan nomor telepon atau kontak WhatsApp, WAJIB tuliskan dalam format tautan/link yang BISA LANGSUNG DIKLIK oleh pelanggan di WhatsApp, yaitu https://wa.me/628xxx (contoh: 'Telp/WA: https://wa.me/6285129868954'). Dilarang memecah angka telepon dengan spasi atau karakter aneh agar link tetap utuh dan bisa langsung diklik.`;

            sysPrompt = sysPrompt + "\n" + waFormatRules;

            if (isFromMetaAd) {
                sysPrompt += `\n[CATATAN PENTING - PELANGGAN DARI IKLAN META (INSTAGRAM/FACEBOOK)]: Pelanggan ini baru saja mengklik iklan promosi kami. Sambut dengan sangat ramah, hangat, dan antusias. Langsung jawab pertanyaannya seputar promo, frame, lensa, atau lokasi cabang toko Winsee Optik terdekat secara jelas dan lengkap agar pelanggan langsung terbantu!`;
            }

            // ── Inject Tanggal & Jam WIB ───────────────────────────────────────
            const wibOffset = 7 * 60; // WIB = UTC+7
            const nowUtc = new Date();
            const nowWib = new Date(nowUtc.getTime() + wibOffset * 60 * 1000);

            const hariList = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const bulanList = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

            const hari = hariList[nowWib.getUTCDay()];
            const tgl = nowWib.getUTCDate();
            const bulan = bulanList[nowWib.getUTCMonth()];
            const tahun = nowWib.getUTCFullYear();
            const jam = String(nowWib.getUTCHours()).padStart(2, '0');
            const menit = String(nowWib.getUTCMinutes()).padStart(2, '0');
            const detik = String(nowWib.getUTCSeconds()).padStart(2, '0');

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

            // ── Kumpulkan daftar server yang akan dicoba (dengan Multi-Server Failover)
            let serversToTry = [];
            if (aiConfig.ai_server_id && aiConfig.api_key) {
                // Utamakan server pilihan device terlebih dahulu
                serversToTry.push({ id: aiConfig.ai_server_id, api_key: aiConfig.api_key, label: aiConfig.ai_label || 'Primary Server' });
                // Tambahkan server-server aktif lainnya sebagai cadangan otomatis (failover) jika server utama limit/gagal
                try {
                    const otherServers = await dbQuery(
                        'SELECT id, api_key, label FROM ai_servers WHERE is_active = 1 AND id != ? ORDER BY priority DESC, id ASC',
                        [aiConfig.ai_server_id]
                    );
                    for (const os of otherServers) {
                        serversToTry.push(os);
                    }
                } catch (_) { }
            } else {
                serversToTry = await dbQuery('SELECT id, api_key, label FROM ai_servers WHERE is_active = 1 ORDER BY priority DESC, id ASC');
            }

            // ── Kumpulkan daftar model yang akan dicoba (dengan Auto-Fallback ke Model Berkecepatan & Kuota Tinggi)
            let modelsToTry = [];
            const decommModels = ['llama3-8b-8192', 'llama3-70b-8192', 'llama-3.1-70b-versatile', 'llama-3.2-1b-preview', 'llama-3.2-3b-preview'];

            const activeDbModels = await dbQuery('SELECT model_name FROM ai_models WHERE is_active = 1 ORDER BY priority DESC, id ASC');

            // 1. Jika device memilih model tertentu (misal llama-3.3-70b-versatile)
            if (aiConfig.model && aiConfig.model !== 'auto' && !decommModels.includes(aiConfig.model)) {
                modelsToTry.push({ model_name: aiConfig.model });
            }

            // 2. Tambahkan model-model aktif dari database sesuai prioritas
            for (const m of activeDbModels) {
                if (!decommModels.includes(m.model_name) && !modelsToTry.some(x => x.model_name === m.model_name)) {
                    modelsToTry.push(m);
                }
            }

            // 3. PENTING: Selalu sediakan 'llama-3.1-8b-instant' sebagai model penyelamat (safety fallback)
            // Model ini memiliki kuota 20.000 TPM (3.3x lipat dari 70b) dan 14.400 RPD, serta respon sangat kilat (<0.5 detik).
            // Jika model 70B terkena rate limit 6.000 TPM, 8B Instant akan langsung mengambil alih tanpa error!
            if (!modelsToTry.some(x => x.model_name === 'llama-3.1-8b-instant')) {
                modelsToTry.push({ model_name: 'llama-3.1-8b-instant' });
            }
            if (!modelsToTry.some(x => x.model_name === 'llama-3.3-70b-versatile')) {
                modelsToTry.unshift({ model_name: 'llama-3.3-70b-versatile' });
            }

            // ── Lakukan percobaan (Auto-Switch 2D: Multi-Server x Multi-Model)
            if (serversToTry.length === 0) {
                console.warn(`[AI] Device ${deviceId} — Tidak ada API Key aktif (Server AI kosong)! Harap tambahkan API Key Groq di menu Server AI.`);
            }

            let isRateLimited = false;

            if (serversToTry.length > 0 && modelsToTry.length > 0) {
                outerLoop: for (let sIdx = 0; sIdx < serversToTry.length; sIdx++) {
                    const srv = serversToTry[sIdx];
                    for (let mIdx = 0; mIdx < modelsToTry.length; mIdx++) {
                        const mod = modelsToTry[mIdx];
                        console.log(`[Groq] Trying server ${sIdx + 1}/${serversToTry.length}: ${srv.label || 'Auto'} | Model: ${mod.model_name}`);
                        const groqRes = await callGroq(srv.api_key, mod.model_name, sysPrompt, chatContext, 450);
                        if (groqRes && groqRes.ok && groqRes.text) {
                            aiReply = groqRes.text;
                            console.log(`[Groq] ✓ Got reply from server: ${srv.label || 'Auto'} | Model: ${mod.model_name}`);
                            break outerLoop;
                        }

                        // Catat kegagalan / API mati ke database (ai_server_logs)
                        const errStatus = String(groqRes?.statusCode || 'ERR');
                        const errMsg = groqRes?.error || 'Gagal menghubungi server Groq';
                        const maskedKey = srv.api_key ? (srv.api_key.substring(0, 10) + '...' + srv.api_key.slice(-6)) : 'unknown';
                        console.warn(`[Groq] ✗ Failed on server: ${srv.label || 'Auto'} | Model: ${mod.model_name} [${errStatus}: ${errMsg}], switching...`);

                        // Deteksi Rate Limit / Quota Exceeded (429 atau pesan limit)
                        if (groqRes?.isRateLimit || errStatus === '429' || (typeof errMsg === 'string' && /rate[_\s-]?limit|too many requests|tpm|rpm|quota|tokens per minute|requests per minute/i.test(errMsg))) {
                            isRateLimited = true;
                            // Jeda singkat 600ms sebelum beralih ke model / key cadangan
                            await new Promise(r => setTimeout(r, 600));
                        }

                        try {
                            await dbQuery(
                                `INSERT INTO ai_server_logs (server_id, label, api_key_masked, model, status_code, error_message, created_at)
                                 VALUES (?, ?, ?, ?, ?, ?, NOW())`,
                                [srv.id || null, srv.label || 'Groq Key', maskedKey, mod.model_name, errStatus, errMsg]
                            );
                        } catch (logErr) {
                            console.warn('[AI Log Error]', logErr.message);
                        }
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

                    // Cek apakah footer sudah tertera di output aiReply agar tidak terduplikasi ganda
                    const cleanFooter = customFooter.replace(/[-_=*|#\s]/g, '').trim().toLowerCase();
                    const cleanAiReply = aiReply.replace(/[-_=*|#\s]/g, '').toLowerCase();
                    const isAlreadyInReply = (cleanFooter.length > 5 && cleanAiReply.includes(cleanFooter.substring(0, 25)))
                        || aiReply.toLowerCase().includes('chat admin')
                        || aiReply.toLowerCase().includes('butuh bantuan staf');

                    if (!isAlreadyInReply) {
                        aiReply = aiReply + '\n\n' + customFooter;
                    }
                }

                // ── SIMULASI MENGETIK RESPONSIF & ALAMI ───────────────────────────
                // Karena status 'composing' sudah aktif selama Groq berpikir (~1-1.5 detik),
                // kita hanya butuh jeda mengetik tambahan yang singkat (300ms - 800ms)
                // agar total waktu respon bot tetap lincah (~1.8 - 2.5 detik) dan tidak membuat pelanggan menunggu lama.
                const elapsedAiTime = Date.now() - aiStartTime;
                const targetTotalDuration = Math.min(2200, Math.max(1500, Math.floor(aiReply.length * 3)));
                const remainingDelay = Math.max(300, targetTotalDuration - elapsedAiTime);
                const typingDuration = Math.min(800, remainingDelay);

                console.log(`[AI TYPING] Mengetik ke ${senderJid} (${aiReply.length} karakter, jeda ${typingDuration}ms, total ~${elapsedAiTime + typingDuration}ms)...`);
                await sock.sendPresenceUpdate('composing', senderJid).catch(() => { });
                await new Promise(r => setTimeout(r, typingDuration));

                try {
                    let sentMsg = null;

                    // Deteksi jika obrolan menggunakan pesan sementara / disappearing (ephemeral)
                    let ephemeralExpiration = undefined;
                    if (fullMsg?.message?.ephemeralMessage) {
                        ephemeralExpiration = fullMsg.message.ephemeralMessage.message?.extendedTextMessage?.contextInfo?.expiration
                            || fullMsg.message.ephemeralMessage.contextInfo?.expiration
                            || 604800; // 7 hari default
                    } else if (fullMsg?.contextInfo?.expiration) {
                        ephemeralExpiration = fullMsg.contextInfo.expiration;
                    }

                    const sendOptions = {};
                    if (ephemeralExpiration) {
                        sendOptions.ephemeralExpiration = ephemeralExpiration;
                    }

                    // Kirim teks standar WhatsApp yang 100% didukung dan stabil di semua perangkat (Android, iPhone, WA Web)
                    try {
                        if (fullMsg && fullMsg.key?.id) {
                            sentMsg = await sock.sendMessage(senderJid, { text: aiReply }, { ...sendOptions, quoted: fullMsg });
                        } else {
                            sentMsg = await sock.sendMessage(senderJid, { text: aiReply }, sendOptions);
                        }
                    } catch (quotedErr) {
                        console.warn(`[AI Quoted Error] ${quotedErr.message}, mencoba kirim pesan langsung tanpa quote...`);
                        sentMsg = await sock.sendMessage(senderJid, { text: aiReply }, sendOptions);
                    }

                    // Hentikan status mengetik SETELAH pesan sukses terkirim
                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });

                    const waMsgId = sentMsg?.key?.id || null;
                    markBotSentMessage(waMsgId);
                    console.log(`[AI] Balasan Groq teks berhasil terkirim ke ${senderJid}`);

                    // Simpan balasan ke histori DB agar muncul di halaman "Lihat Chat"
                    await saveMessageToDB(sessionId, waMsgId, myPhone, remotePhone, 'text', aiReply, 'out', 'sent');
                } catch (err) {
                    await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                    console.error('[AI Send Error] Gagal mengirim pesan ke WhatsApp:', err.message);
                    console.warn(`[AI SEND FAILED] Kontak ${remotePhone} dimasukkan ke AI mute karena gagal kirim, akan dicoba kembali setelah durasi yang ditentukan.`);
                    await muteContact(deviceId, remotePhone, 'ai_send_failed');
                }
                return; // Selesai, jangan lanjut ke autoreply biasa
            } else {
                await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
                if (isRateLimited) {
                    console.warn(`[AI LIMIT] AI mencapai limit (Rate Limit / Quota) untuk device ${deviceId}. Me-mute kontak ${remotePhone} selama 1 menit dan akan otomatis membalas kembali setelah 1 menit...`);
                    await muteContact(deviceId, remotePhone, 'ai_rate_limit');
                } else {
                    console.warn(`[AI FAILED] Semua server AI gagal/mati untuk device ${deviceId}. Me-mute kontak ${remotePhone} secara otomatis ke status Bot Mute agar ditangani staf / di-retry otomatis...`);
                    await muteContact(deviceId, remotePhone, 'ai_server_down');
                }
                return;
            }
        } catch (aiErr) {
            await sock.sendPresenceUpdate('paused', senderJid).catch(() => { });
            const isLimit = typeof aiErr.message === 'string' && /rate[_\s-]?limit|too many requests|tpm|rpm|quota|429/i.test(aiErr.message);
            if (isLimit) {
                console.warn(`[AI LIMIT ERROR] AI limit terdeteksi untuk device ${deviceId}:`, aiErr.message);
                await muteContact(deviceId, remotePhone, 'ai_rate_limit');
            } else {
                console.error(`[AI ERROR] Terjadi kesalahan saat memproses AI untuk device ${deviceId}:`, aiErr.message);
                await muteContact(deviceId, remotePhone, 'ai_server_down');
            }
            return;
        }
    }
    } finally {
        // Lepas lock aktifitas autoreply untuk sender ini
        activeReplyLocks.delete(lockKey);
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
        if (sessions[sessionId].heartbeatInterval) {
            clearInterval(sessions[sessionId].heartbeatInterval);
            sessions[sessionId].heartbeatInterval = null;
        }
        const oldSock = sessions[sessionId].socket;
        sessions[sessionId].socket = null; // Lepas referensi segera
        try {
            oldSock.ev.removeAllListeners();
            oldSock.end(undefined);
        } catch (_) { }
    }

    const existingRetries = sessions[sessionId]?.retries || 0;
    sessions[sessionId] = {
        socket: null,
        qrBase64: null,
        pairingCode: null,
        status: 'initializing',
        phone: sessions[sessionId]?.phone || null,
        retries: isRetry ? existingRetries : 0,
        store: null,
        storeInterval: null,
        heartbeatInterval: null,
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
    } catch (e) { }

    const sock = makeWASocket({
        version,
        logger: baileysLogger,
        auth: state,
        printQRInTerminal: false,
        // Use Windows Desktop signature to match the official WhatsApp desktop client on Windows.
        // Avoids headless Linux scraper/bot fingerprints that are heavily monitored by Meta AI
        browser: Browsers.windows('Desktop'),
        syncFullHistory: false,
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
        } catch (e) { }
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
            sessions[sessionId].status = 'qr';
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
            if (sessions[sessionId]?.heartbeatInterval) {
                clearInterval(sessions[sessionId].heartbeatInterval);
                sessions[sessionId].heartbeatInterval = null;
            }

            // Periksa apakah sesi ini sudah memiliki kredensial sah yang terdaftar di disk
            const credsPath = path.join(sessionAuthDir, 'creds.json');
            let hasValidCreds = false;
            if (fs.existsSync(credsPath)) {
                try {
                    const c = JSON.parse(fs.readFileSync(credsPath, 'utf8'));
                    if (c?.me?.id || c?.registered) {
                        hasValidCreds = true;
                    }
                } catch (_) { }
            }

            // 1. RESTART REQUIRED (Code 515) atau HANDSHAKE PAIRING SUKSES (Code 428)
            // WhatsApp sengaja menutup socket pairing dan meminta reconnect instan dengan kredensial baru.
            // JANGAN HAPUS AUTH! Langsung reconnect agar handshaking tuntas.
            if (statusCode === DisconnectReason.restartRequired || statusCode === 515 || statusCode === 428) {
                console.log(`[RESTART] Session ${sessionId}: Handshake / Restart diminta (${statusCode}). Reconnecting...`);
                sessions[sessionId].status = 'initializing';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].reconnectTimeout = setTimeout(() => {
                    createSession(sessionId, true, false).catch(console.error);
                }, 1000);
                return;
            }

            // 2. EXPLICIT LOGGED OUT (401)
            // Hanya hapus folder sesi jika user benar-benar logout dari aplikasi WhatsApp di HP!
            if (statusCode === DisconnectReason.loggedOut) {
                console.log(`[AUTH LOGGED OUT] Session ${sessionId}: Sesi di-logout dari WhatsApp (401). Menghapus auth.`);
                try { fs.rmSync(sessionAuthDir, { recursive: true, force: true }); } catch (e) { }

                sessions[sessionId].status = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].retries = 0;
                sessions[sessionId].socket = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 3. CONNECTION REPLACED (440)
            if (statusCode === DisconnectReason.connectionReplaced) {
                console.log(`[REPLACED] Session ${sessionId}: Sesi dibuka di perangkat/browser lain.`);
                sessions[sessionId].status = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].socket = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 4. TIMEOUT (408) HANYA JIKA BELUM TERDAFTAR (QR kadaluwarsa karena tidak di-scan)
            if (!hasValidCreds && (statusCode === DisconnectReason.timedOut || statusCode === 408)) {
                console.log(`[TIMEOUT QR] Session ${sessionId}: QR Code kadaluwarsa karena tidak di-scan (408).`);
                sessions[sessionId].status = 'disconnected';
                sessions[sessionId].qrBase64 = null;
                sessions[sessionId].pairingCode = null;
                sessions[sessionId].socket = null;
                await updateDeviceStatus(sessionId, 'disconnected');
                return;
            }

            // 5. TEMPORARY ERROR LAINNYA: Connection Lost (408 pada device terdaftar), 500, socket reset, network glitch, dll.
            // JANGAN HAPUS AUTH! Coba reconnect otomatis secara berulang dan cerdas (tanpa menyerah).
            sessions[sessionId].status = 'disconnected';
            sessions[sessionId].qrBase64 = null;
            sessions[sessionId].pairingCode = null;
            sessions[sessionId].socket = null;
            await updateDeviceStatus(sessionId, 'disconnected');

            if (hasValidCreds) {
                const retries = (sessions[sessionId]?.retries || 0) + 1;
                sessions[sessionId].retries = retries;

                // Smart backoff agar tidak membombardir server:
                // Retries 1-3  : jeda 3 detik
                // Retries 4-6  : jeda 10 detik
                // Retries 7-10 : jeda 20 detik
                // Retries > 10 : jeda 60 detik (tetap terus mencoba setiap 1 menit, tidak menyerah)
                let delayMs = 3000;
                if (retries > 10) {
                    delayMs = 60000;
                } else if (retries > 6) {
                    delayMs = 20000;
                } else if (retries > 3) {
                    delayMs = 10000;
                }

                console.log(`[AUTO-RECONNECT] Session ${sessionId}: Mencoba hubungkan ulang (${retries}x) dalam ${delayMs / 1000} detik...`);
                sessions[sessionId].reconnectTimeout = setTimeout(() => {
                    createSession(sessionId, true, false).catch(console.error);
                }, delayMs);
            } else {
                console.log(`[SESSION CLOSED] Session ${sessionId}: Ditutup tanpa kredensial terdaftar.`);
            }
        }

        if (connection === 'open') {
            const user = sock.user;
            // v7 LID: user.id bisa berformat "phone:device@lid" atau "phone:device@s.whatsapp.net"
            // normalizePhoneFromJid() handle semua format dengan benar
            const phone = user?.id ? normalizePhoneFromJid(user.id) : null;
            console.log(`[CONNECTED] Session ${sessionId}: +${phone} [Baileys v7]`);

            sessions[sessionId].status = 'connected';
            sessions[sessionId].phone = phone;
            sessions[sessionId].qrBase64 = null;
            sessions[sessionId].pairingCode = null;
            sessions[sessionId].retries = 0;
            await updateDeviceStatus(sessionId, 'connected', phone);

            // Bersihkan interval heartbeat lama jika ada
            if (sessions[sessionId].heartbeatInterval) {
                clearInterval(sessions[sessionId].heartbeatInterval);
            }
            // Heartbeat setiap 25 detik: kirim presence update agar socket WhatsApp tidak idle-kill oleh ISP/NAT/cPanel
            sessions[sessionId].heartbeatInterval = setInterval(async () => {
                try {
                    if (sessions[sessionId]?.status === 'connected' && sessions[sessionId]?.socket) {
                        await sessions[sessionId].socket.sendPresenceUpdate('available');
                    }
                } catch (e) {
                    console.warn(`[HEARTBEAT WARN] Session ${sessionId} ping failed: ${e.message}`);
                }
            }, 25000);
        }
    });

    // ── Handle pesan masuk & sinkronisasi chat ──────────────────────────────
    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        // Multi-device WhatsApp sync: pesan masuk = 'notify', pesan yang diketik admin di HP = 'append' atau 'notify'
        if (type !== 'notify' && type !== 'append') return;

        for (const msg of messages) {
            if (isJidBroadcast(msg.key.remoteJid || '')) continue; // Abaikan broadcast

            // ── SINKRONISASI PEMETAAN LID DAN PHONE NUMBER (Baileys v7) ──────
            if (msg.key.remoteJidAlt) {
                recordLidPhoneMapping(msg.key.remoteJid, msg.key.remoteJidAlt, sock);
            }

            const senderJid = msg.key.remoteJid;
            let remotePhone = normalizePhoneFromJid(senderJid);

            // Jika senderJid adalah LID tapi nomor telepon aslinya ada di remoteJidAlt atau cache
            if (senderJid.endsWith('@lid') || (remotePhone.length >= 14 && !remotePhone.startsWith('62'))) {
                const mappedPhone = lidToPhoneMap.get(remotePhone) || (msg.key.remoteJidAlt ? normalizePhoneFromJid(msg.key.remoteJidAlt) : null);
                if (mappedPhone && mappedPhone.length >= 9 && mappedPhone.length <= 14) {
                    remotePhone = mappedPhone;
                }
            }

            let myPhone = sessions[sessionId]?.phone;

            if (!myPhone) {
                const devRows = await dbQuery('SELECT phone FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
                if (devRows.length && devRows[0].phone) {
                    myPhone = devRows[0].phone;
                    if (sessions[sessionId]) sessions[sessionId].phone = myPhone;
                }
            }
            if (!myPhone) continue; // Jangan proses jika nomor device belum terbaca

            // Ekstraksi teks pesan masuk secara komprehensif (mendukung interactive message, CTA buttons, dan CTWA ad fallbacks)
            let incomingText = extractIncomingText(msg);

            const waMsgId = msg.key.id;
            const isFromMe = msg.key.fromMe;

            // ── CEK STUB / PLACEHOLDER PESAN BELUM TERDEKRIPSI ───────────────
            // Baileys terkadang menerima stub sementara (CIPHERTEXT/PDO resend).
            // PENTING: JANGAN catat stub kosong ke anti-duplikasi cache!
            // Jika dicatat sekarang, saat pesan aslinya didekripsi beberapa detik kemudian,
            // pesan asli tersebut akan terblokir sebagai duplikat!
            const isStubPlaceholder = !!msg.messageStubType || !msg.message || Object.keys(msg.message).length === 0;
            if (isStubPlaceholder && !incomingText) {
                continue; // Lewati stub, tunggu stanza dekripsi pesan asli tiba
            }

            // ── ANTI-DUPLIKASI PESAN MASUK ───────────────────────────────────
            // Hanya periksa deduplikasi jika pesan sudah memiliki teks nyata dan bukan pesan keluar
            if (!isFromMe && waMsgId && incomingText) {
                if (isDuplicateIncomingMessage(waMsgId)) {
                    console.log(`[DEDUPLICATE] Mengabaikan pesan masuk duplikat (ID: ${waMsgId}) dari ${senderJid}`);
                    continue;
                }
            }

            // Tentukan arah & nomor
            const direction = isFromMe ? 'out' : 'in';
            const fromPhone = isFromMe ? myPhone : remotePhone;
            const toPhone = isFromMe ? remotePhone : myPhone;
            const status = isFromMe ? 'sent' : 'delivered';

            // ── FILTER PESAN SINKRONISASI / HISTORI LAMA ─────────────────────
            // Jika pesan berumur > 600 detik (10 menit) (misal sinkronisasi riwayat chat saat reconnect),
            // arsipkan ke database tapi JANGAN memicu aksi live (mute admin atau balasan otomatis).
            // Ambang batas 600 detik mencegah pesan iklan tertunda/lag terbuang sia-sia.
            const msgTimestampSec = Number(msg.messageTimestamp || 0);
            const isOldMessage = msgTimestampSec > 0 && ((Date.now() / 1000) - msgTimestampSec > 600);

            if (isOldMessage) {
                if (incomingText) {
                    await saveMessageToDB(sessionId, waMsgId, fromPhone, toPhone, 'text', incomingText, direction, status);
                }
                continue;
            }

            // Simpan Histori ke DB untuk pesan live
            if (incomingText) {
                console.log(`[MSG ${direction.toUpperCase()}] ${sessionId} : ${incomingText.substring(0, 50)}`);
                await saveMessageToDB(sessionId, waMsgId, fromPhone, toPhone, 'text', incomingText, direction, status);
            }

            // Jika pesan dikirim dari akun kita sendiri (isFromMe == true):
            if (isFromMe) {
                // Deteksi jika pesan ini diketik langsung oleh HUMAN ADMIN di HP atau WhatsApp Web
                if (!botSentMessageIds.has(waMsgId)) {
                    // 1. Abaikan jika teks kosong (misal protocol message update timer pesan sementara 7 hari, read receipt, stubs)
                    if (!incomingText || typeof incomingText !== 'string' || !incomingText.trim()) {
                        continue;
                    }

                    // 2. Abaikan jika pesan ini berasal dari Iklan Meta / CTWA handshake
                    if (isMetaAdMessage(msg)) {
                        console.log(`[ADMIN MESSAGE IGNORED] Pesan otomatis CTA Iklan Meta terdeteksi. Jangan mute Bot AI.`);
                        continue;
                    }

                    // 3. Abaikan jika pesan ini adalah "Salam otomatis" atau Pesan Sambutan WhatsApp Business
                    const lowerText = incomingText.toLowerCase().trim();
                    if (lowerText.includes('salam otomatis') || lowerText.includes('pesan otomatis') || lowerText.includes('pesan di luar jam kerja')) {
                        console.log(`[ADMIN MESSAGE IGNORED] Pesan salam otomatis WhatsApp Business terdeteksi. Jangan mute Bot AI.`);
                        continue;
                    }

                    // 4. Periksa apakah ini pesan greeting promosi iklan (seperti "Halo Optik winsee punya paket kacamata 75K...")
                    if (lowerText.includes('halo optik winsee') || lowerText.includes('paket kacamata 75k') || lowerText.includes('cek koleksinya') || lowerText.includes('cek mata gratis')) {
                        console.log(`[ADMIN MESSAGE IGNORED] Pesan promosi pembuka iklan terdeteksi. Jangan mute Bot AI.`);
                        continue;
                    }

                    let devId = sessions[sessionId]?.deviceId;
                    if (!devId) {
                        const devRows = await dbQuery('SELECT id, welcome_message, away_message FROM devices WHERE session_id = ? LIMIT 1', [sessionId]);
                        if (devRows.length && devRows[0].id) {
                            devId = devRows[0].id;
                            if (sessions[sessionId]) sessions[sessionId].deviceId = devId;
                            if (devRows[0].welcome_message && lowerText === devRows[0].welcome_message.toLowerCase().trim()) {
                                continue;
                            }
                            if (devRows[0].away_message && lowerText === devRows[0].away_message.toLowerCase().trim()) {
                                continue;
                            }
                        }
                    }
                    if (devId && remotePhone) {
                        // Ambil konfigurasi kata kunci penutupan sesi dari device_ai_settings
                        const devAiSets = await dbQuery(
                            'SELECT ai_enabled, ai_reactivate_keyword, ai_reactivate_notify, ai_reactivate_message FROM device_ai_settings WHERE device_id = ? LIMIT 1',
                            [devId]
                        );

                        const isAiActiveOnDev = devAiSets.length && (devAiSets[0].ai_enabled == 1 || devAiSets[0].ai_enabled === true || devAiSets[0].ai_enabled === '1');

                        // Logika reaktivasi AI / mute admin HANYA berlaku jika AI pada device ini AKTIF
                        if (isAiActiveOnDev) {
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

                                // Auto-followup: jika ada pesan pelanggan yang belum sempat dibalas sebelum sesi diakhiri,
                                // AI akan otomatis memproses dan membalasnya sekarang
                                triggerAutoReplyForUnmutedContact(devId, remotePhone, sessionId, 'admin_ended_chat').catch(err => {
                                    console.error('[ADMIN END CONVERSATION AUTO-FOLLOWUP ERR]', err.message);
                                });
                            } else {
                                // Admin sedang chat manual biasa, maka mute kontak / perpanjang masa mute ("setelah tidak ada respon")
                                console.log(`[ADMIN CHAT DETECTED] Human admin sedang berbicara langsung dengan ${remotePhone} (${senderJid}). Me-mute/memperpanjang mute Bot AI.`);
                                await muteContact(devId, remotePhone, 'admin_talking');
                            }
                        }
                    }
                }
            }

            // Proses webhook & autoreply untuk semua pesan masuk baru (baik 'notify' maupun pembukaan chat baru 'append')
            if (!isFromMe) {
                // ── OPT-OUT / UNSUBSCRIBE HANDLER (WHATSAPP ANTI-BAN PROTECTION) ──
                // If recipient replies STOP / BERHENTI / UNSUBSCRIBE:
                // Automatically add to blacklist & send a polite confirmation.
                // Converts potential spam reports into 2-way chats, preventing 90% of user report/block clicks!
                if (incomingText) {
                    const stopKeywords = ['stop', 'berhenti', 'unsubscribe', 'unsub', 'batal', 'jangan chat', 'jangan kirim', 'stop broadcast', 'stop wa'];
                    const normalizedIncoming = incomingText.trim().toLowerCase();
                    const isOptOutRequest = stopKeywords.some(kw => normalizedIncoming === kw || normalizedIncoming.startsWith(kw + ' ') || normalizedIncoming.startsWith(kw + '.'));

                    if (isOptOutRequest) {
                        console.log(`[OPT-OUT DETECTED] Recipient +${remotePhone} requested STOP/Unsubscribe: "${incomingText}"`);
                        try {
                            // 1. Save to broadcast_blacklists table
                            await dbQuery("INSERT INTO broadcast_blacklists (phone, reason, created_at) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE reason = ?", [
                                remotePhone,
                                `Opt-out via chat: "${incomingText.substring(0, 50)}"`,
                                `Opt-out via chat: "${incomingText.substring(0, 50)}"`
                            ]);

                            // 2. Cancel pending recipients across all active campaigns for this number
                            await dbQuery("UPDATE campaign_recipients SET status = 'failed', error_msg = 'Cancelled: Recipient replied STOP (Opt-out)' WHERE phone LIKE ? AND status = 'pending'", [
                                `%${remotePhone.slice(-8)}%`
                            ]);

                            // 3. Send polite 2-way confirmation message
                            const optOutReply = "Baik kak, terima kasih atas konfirmasinya. Nomor Anda telah kami hapus dari daftar broadcast. Kami memohon maaf jika sempat mengganggu waktu Anda. Semoga sehat & sukses selalu! 🙏";
                            const sentOptMsg = await sock.sendMessage(senderJid, { text: optOutReply });
                            const optWaId = sentOptMsg?.key?.id || null;
                            markBotSentMessage(optWaId);
                            await saveMessageToDB(sessionId, optWaId, myPhone, remotePhone, 'text', optOutReply, 'out', 'sent');
                        } catch (optErr) {
                            console.warn('[OPT-OUT REPLY ERR]', optErr.message);
                        }
                        continue; // Completed, do not dispatch to webhook or AI autoreply
                    }
                }

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
                if (newStatus === 2) dbStatus = 'pending';   // belum terkirim ke server
                if (newStatus === 3) dbStatus = 'sent';      // terkirim ke server WA (ceklis 1 server)
                if (newStatus === 4) dbStatus = 'delivered'; // terkirim ke HP penerima (ceklis 2)
                if (newStatus === 5) dbStatus = 'read';      // sudah dibaca (ceklis 2 biru)
                if (newStatus === 6) dbStatus = 'played';    // voice note diputar

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

            const senderJid = msg.key.remoteJid;
            // v7 LID: gunakan helper untuk normalisasi
            const remotePhone = senderJid ? normalizePhoneFromJid(senderJid) : '';
            if (!remotePhone) continue;

            const incomingText = msg.message?.conversation
                || msg.message?.extendedTextMessage?.text
                || '';

            const waMsgId = msg.key.id;
            const isFromMe = msg.key.fromMe;

            const direction = isFromMe ? 'out' : 'in';
            const fromPhone = isFromMe ? myPhone : remotePhone;
            const toPhone = isFromMe ? remotePhone : myPhone;
            const status = isFromMe ? 'read' : 'read'; // Anggap riwayat masa lalu semuanya 'read'

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
        status: 'ok',
        engine: 'WAGTW v3.0 - Baileys v7 Engine (LID)',
        message: 'WAGTW Engine is running',
        uptime: Math.floor(process.uptime()) + 's'
    });
});

app.get('/api/status', (req, res) => {
    const active = Object.keys(sessions).map(id => ({
        sessionId: id,
        status: sessions[id].status,
        phone: sessions[id].phone,
        hasQR: !!sessions[id].qrBase64,
    }));
    res.json({
        status: 'ok',
        engine: 'WAGTW v3.0 - Baileys v7 Engine (LID)',
        uptime: Math.floor(process.uptime()) + 's',
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

// ── POST /api/session/reconnect ──────────────────────────────────────────
app.post('/api/session/reconnect', authMiddleware, async (req, res) => {
    const { sessionId } = req.body;
    if (!sessionId) return res.status(400).json({ status: 'error', message: 'sessionId diperlukan.' });

    const authPath = path.join(AUTH_DIR, sessionId);
    const credsPath = path.join(authPath, 'creds.json');
    let hasCreds = false;
    if (fs.existsSync(credsPath)) {
        try {
            const creds = JSON.parse(fs.readFileSync(credsPath, 'utf8'));
            if (creds?.me?.id || creds?.registered) hasCreds = true;
        } catch (_) { }
    }

    if (!hasCreds) {
        return res.json({
            status: 'need_qr',
            message: 'Perangkat belum memiliki kredensial tersimpan. Silakan Scan QR terlebih dahulu.'
        });
    }

    console.log(`[MANUAL RECONNECT] Mereconnect sesi: ${sessionId}`);
    createSession(sessionId, false, false).catch(err => console.error('[RECONNECT ERROR]', err.message));

    return res.json({
        status: 'success',
        message: 'Perintah reconnect berhasil dikirim. WhatsApp sedang dihubungkan ulang...'
    });
});

// ── POST /api/reconnect-all ──────────────────────────────────────────────
app.post('/api/reconnect-all', authMiddleware, async (req, res) => {
    runHourlyAutoReconnect().catch(err => console.error('[RECONNECT ALL ERR]', err.message));
    res.json({ status: 'success', message: 'Auto-reconnect untuk seluruh device terdaftar sedang dijalankan.' });
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
            try { fs.rmSync(authDir, { recursive: true, force: true }); } catch (e) { }
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

// ── POST /api/ai/unmute ────────────────────────────────────────────────────────
// Endpoint dipanggil dari Web UI (Aitemplate) saat user klik "Aktifkan AI Sekarang"
// atau saat aksi bulk unmute kontak dijalankan.
app.post('/api/ai/unmute', authMiddleware, async (req, res) => {
    try {
        const { deviceId, phone } = req.body;
        if (!deviceId || !phone) {
            return res.status(400).json({ status: 'error', message: 'deviceId dan phone diperlukan.' });
        }

        const cleanPhone = normalizePhoneFromJid(phone) || String(phone).trim();
        console.log(`[API AI UNMUTE] Permintaan aktivasi AI untuk kontak ${cleanPhone} pada Device ID ${deviceId}`);

        // 1. Bersihkan record mute di database
        await unmuteContact(deviceId, cleanPhone);

        // 2. Trigger auto-followup jika ada pesan masuk terakhir yang belum dibalas
        const result = await triggerAutoReplyForUnmutedContact(deviceId, cleanPhone, null, 'manual_web_unmute');

        res.json({
            status: 'success',
            message: `Bot AI berhasil diaktifkan kembali untuk kontak ${cleanPhone}`,
            autoReplied: result.replied || false
        });
    } catch (err) {
        console.error('[API AI UNMUTE ERR]', err.message);
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// ─── Broadcast & Anti-Banned Engine ──────────────────────────────────────────

/**
 * Menghasilkan teks spintax rekursif: {Halo|Hai|Salam {pagi|siang}}
 */
function parseSpintax(text) {
    if (!text) return '';
    const regEx = /\{([^{}]+)\}/;
    let matches;
    let iterations = 0;
    while ((matches = regEx.exec(text)) !== null && iterations < 15) {
        const options = matches[1].split('|');
        const random = Math.floor(Math.random() * options.length);
        text = text.replace(matches[0], options[random]);
        iterations++;
    }
    return text;
}

/**
 * Salam waktu lokal otomatis (WIB UTC+7)
 */
function getDynamicGreeting() {
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const localTime = new Date(utc + (3600000 * 7));
    const hour = localTime.getHours();

    if (hour >= 4 && hour < 11) return 'Selamat pagi';
    if (hour >= 11 && hour < 15) return 'Selamat siang';
    if (hour >= 15 && hour < 18) return 'Selamat sore';
    return 'Selamat malam';
}

/**
 * Informasi tanggal dan waktu lokal terformat
 */
function getFormattedDateInfo() {
    const now = new Date();
    const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
    const local = new Date(utc + (3600000 * 7));

    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    const dayName = days[local.getDay()];
    const dateNum = String(local.getDate()).padStart(2, '0');
    const monthName = months[local.getMonth()];
    const yearNum = local.getFullYear();
    const hours = String(local.getHours()).padStart(2, '0');
    const mins = String(local.getMinutes()).padStart(2, '0');

    return {
        day: dayName,
        date: `${dateNum} ${monthName} ${yearNum}`,
        dateShort: `${dateNum}/${String(local.getMonth() + 1).padStart(2, '0')}/${yearNum}`,
        time: `${hours}:${mins}`
    };
}

/**
 * Hasilkan kode unik acak untuk tag [UniqueId]
 */
function generateRandomTag(length = 5) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let res = '';
    for (let i = 0; i < length; i++) {
        res += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return res;
}

/**
 * Injeksi karakter Zero-Width tak terlihat pada akhir pesan.
 * Trik Anti-Banned: Pesan tampak sama persis di layar HP penerima,
 * tetapi memiliki Hash kriptografis unik (MD5/SHA256) di server Meta,
 * sehingga sistem AI spam Meta TIDAK mendeteksi pesan massal duplikat.
 */
function injectInvisibleAntiHash(text) {
    const zwChars = ['\u200B', '\u200C', '\u200D', '\uFEFF'];
    const length = Math.floor(Math.random() * 5) + 4; // 4 s/d 8 karakter tak terlihat
    let watermark = '';
    for (let i = 0; i < length; i++) {
        watermark += zwChars[Math.floor(Math.random() * zwChars.length)];
    }
    return text + watermark;
}

/**
 * Format pesan broadcast lengkap dengan Spintax, Variabel Dinamis & Watermark Anti-Hash
 */
function formatBroadcastMessage(rawMessage, targetName, targetPhone) {
    let msg = parseSpintax(rawMessage || '');
    const greeting = getDynamicGreeting();
    const dateInfo = getFormattedDateInfo();
    const uniqueId = generateRandomTag(5);

    msg = msg.replace(/\[Name\]/gi, targetName || 'Kak');
    msg = msg.replace(/\[Phone\]/gi, targetPhone || '');
    msg = msg.replace(/\[(Greeting|Salam)\]/gi, greeting);
    msg = msg.replace(/\[(Date|Tanggal)\]/gi, dateInfo.date);
    msg = msg.replace(/\[(Time|Waktu|Jam)\]/gi, dateInfo.time);
    msg = msg.replace(/\[(Day|Hari)\]/gi, dateInfo.day);
    msg = msg.replace(/\[(UniqueId|Unique|Random|Ref|Kode)\]/gi, uniqueId);

    // Selipkan watermark tak terlihat (Anti-Duplicate Content Ban)
    return injectInvisibleAntiHash(msg);
}

const activeCampaignsLocks = {}; // Mencegah double send pada async loop campaign
const activeDeviceLocks = {}; // Mencegah bentrok 2 campaign berjalan di 1 device
const campaignBatchState = {}; // Manajemen jeda istirahat berkala (batch pacing)
const consecutiveFailures = {}; // Circuit breaker jika banyak nomor invalid berurutan

async function processBroadcasts() {
    try {
        const campaigns = await dbQuery("SELECT c.*, d.session_id FROM campaigns c JOIN devices d ON d.id = c.device_id WHERE c.status = 'running'");
        if (!campaigns.length) return;

        for (const c of campaigns) {
            // 1. Kunci campaign jika sedang ada delay / pengiriman aktif
            if (activeCampaignsLocks[c.id]) continue;

            // 2. Kunci device jika nomor WA ini sedang digunakan broadcast lain (hindari interleave simultan)
            if (activeDeviceLocks[c.session_id]) continue;

            const sockData = sessions[c.session_id];
            if (!sockData || sockData.status !== 'connected') continue; // Skip jika device offline

            // 3. Cek Masa Istirahat Batch Pacing (Anti-Banned Cooling Period)
            const batchState = campaignBatchState[c.id] || { sentSinceLastBatch: 0, pausedUntil: 0 };
            campaignBatchState[c.id] = batchState;

            const now = Date.now();
            if (batchState.pausedUntil && now < batchState.pausedUntil) {
                const remainingSecs = Math.ceil((batchState.pausedUntil - now) / 1000);
                const remainingMins = Math.ceil(remainingSecs / 60);
                if (!batchState.lastLog || now - batchState.lastLog > 60000) {
                    console.log(`[BATCH PACING] Campaign #${c.id} (${c.name}): Sedang masa istirahat anti-banned. Melanjutkan dalam ~${remainingMins} menit (${remainingSecs}s)...`);
                    batchState.lastLog = now;
                }
                continue;
            }

            // Kunci proses untuk campaign & device ini
            activeCampaignsLocks[c.id] = true;
            activeDeviceLocks[c.session_id] = true;

            try {
                // Ambil 1 baris target pending
                const pending = await dbQuery("SELECT * FROM campaign_recipients WHERE campaign_id = ? AND status = 'pending' LIMIT 1", [c.id]);

                if (pending.length === 0) {
                    const stats = await dbQuery("SELECT sent, failed, total FROM campaigns WHERE id = ?", [c.id]);
                    const s = stats[0] || { sent: 0, failed: 0, total: 0 };
                    const finalStatus = (s.sent === 0 && s.failed > 0) ? 'failed' : 'done';
                    await dbQuery("UPDATE campaigns SET status = ?, finished_at = NOW() WHERE id = ?", [finalStatus, c.id]);

                    delete activeCampaignsLocks[c.id];
                    delete activeDeviceLocks[c.session_id];
                    delete campaignBatchState[c.id];
                    delete consecutiveFailures[c.id];
                    continue;
                }

                const target = pending[0];
                let toNumber = target.phone.replace(/[^0-9]/g, '');
                if (toNumber.startsWith('0')) {
                    toNumber = '62' + toNumber.substring(1);
                } else if (toNumber.startsWith('8')) {
                    toNumber = '62' + toNumber;
                }
                const jid = toNumber + '@s.whatsapp.net';

                // ── ANTI-BAN PROTECTION: Check Blacklist / Opt-Out List ──
                // If this number has opted-out or is blacklisted, skip immediately without waiting for delay
                const checkBlacklist = await dbQuery(
                    "SELECT id FROM broadcast_blacklists WHERE phone = ? OR phone LIKE ? LIMIT 1",
                    [toNumber, `%${toNumber.slice(-9)}%`]
                );
                if (checkBlacklist.length > 0) {
                    console.log(`[BLACKLIST SKIP] Camp #${c.id} -> +${toNumber} skipped (number is blacklisted / opted-out).`);
                    await dbQuery("UPDATE campaign_recipients SET status = 'failed', error_msg = 'Skipped: Number is on Blacklist / Opt-Out list' WHERE id = ?", [target.id]);
                    await dbQuery("UPDATE campaigns SET failed = failed + 1 WHERE id = ?", [c.id]);
                    delete activeCampaignsLocks[c.id];
                    delete activeDeviceLocks[c.session_id];
                    continue;
                }

                // Apply Anti-Ban: Spintax, Dynamic Variables & Invisible Anti-Hash Watermark
                const finalMsg = formatBroadcastMessage(c.message, target.name, toNumber);

                // Apply Anti-Ban: Dynamic & Safe Random Delay Calculation
                // Enforce a minimum safe delay of 15s to maintain account health in Meta's algorithms (avoids rate limits)
                const rawDelayMin = c.delay_min > 0 ? c.delay_min : 15;
                const rawDelayMax = c.delay_max >= rawDelayMin ? c.delay_max : rawDelayMin + 15;
                const safeDelayMin = Math.max(15, rawDelayMin);
                const safeDelayMax = Math.max(safeDelayMin + 5, rawDelayMax);

                // Add random jitter so interval is never robotic or constant
                const baseDelay = Math.floor(Math.random() * (safeDelayMax - safeDelayMin + 1) + safeDelayMin);
                const jitter = Math.floor(Math.random() * 5) - 2; // -2s to +2s
                const delayMs = Math.max(12, baseDelay + jitter) * 1000;

                // Non-blocking timeout for realistic delay
                setTimeout(async () => {
                    try {
                        // 1. Validate phone number digit format
                        if (!toNumber || toNumber.length < 8 || toNumber.length > 16) {
                            throw new Error('Invalid phone number format (must be 8-16 digits)');
                        }

                        // 2. Humanized Presence & Typing Flow Simulation
                        // NOTE: DO NOT call sock.onWhatsApp(jid) here!
                        // Bulk onWhatsApp calls trigger Meta's directory harvesting / scraping bot detection.
                        // WhatsApp server automatically validates recipients when sock.sendMessage is invoked.

                        // a. Simulate user coming online (available)
                        await sockData.socket.sendPresenceUpdate('available').catch(() => { });

                        // b. Simulate reading & opening chat delay (1.0 - 2.5s)
                        await new Promise(r => setTimeout(r, Math.floor(Math.random() * 1500 + 1000)));

                        // c. Simulate typing status (composing) proportional to text length
                        await sockData.socket.sendPresenceUpdate('composing', jid).catch(() => { });
                        const typingDurationMs = Math.min(6000, Math.max(2000, Math.floor(finalMsg.length * 20 + (Math.random() * 1200 + 800))));
                        await new Promise(r => setTimeout(r, typingDurationMs));

                        let sentMsg = null;
                        let msgType = 'text';

                        // Send media attachment if campaign includes media
                        if (c.media_path) {
                            const fullMediaPath = path.resolve(__dirname, '..', 'public', c.media_path.replace(/^\/+/, ''));
                            if (fs.existsSync(fullMediaPath)) {
                                const ext = path.extname(fullMediaPath).toLowerCase();
                                const mediaBuffer = fs.readFileSync(fullMediaPath);

                                if (['.jpg', '.jpeg', '.png', '.webp'].includes(ext)) {
                                    msgType = 'image';
                                    sentMsg = await sockData.socket.sendMessage(jid, {
                                        image: mediaBuffer,
                                        caption: finalMsg
                                    });
                                } else if (['.mp4', '.mov', '.3gp'].includes(ext)) {
                                    msgType = 'video';
                                    sentMsg = await sockData.socket.sendMessage(jid, {
                                        video: mediaBuffer,
                                        caption: finalMsg
                                    });
                                } else {
                                    msgType = 'document';
                                    sentMsg = await sockData.socket.sendMessage(jid, {
                                        document: mediaBuffer,
                                        mimetype: 'application/octet-stream',
                                        fileName: path.basename(fullMediaPath),
                                        caption: finalMsg
                                    });
                                }
                            }
                        }

                        if (!sentMsg) {
                            sentMsg = await sockData.socket.sendMessage(jid, { text: finalMsg });
                        }

                        // d. Finished typing: turn off composing presence
                        await sockData.socket.sendPresenceUpdate('paused', jid).catch(() => { });

                        await dbQuery("UPDATE campaign_recipients SET status = 'sent', sent_at = NOW(), error_msg = NULL WHERE id = ?", [target.id]);
                        await dbQuery("UPDATE campaigns SET sent = sent + 1 WHERE id = ?", [c.id]);

                        const waMsgId = sentMsg?.key?.id || null;
                        markBotSentMessage(waMsgId);
                        await saveMessageToDB(c.session_id, waMsgId, sockData.phone, toNumber, msgType, finalMsg, 'out', 'sent');

                        console.log(`[BROADCAST SUCCESS] Camp #${c.id} -> +${toNumber} (Delay: ${delayMs / 1000}s, Typing: ${typingDurationMs / 1000}s)`);

                        // Reset circuit breaker on success
                        consecutiveFailures[c.id] = 0;

                        // 4. Batch Pacing Management (Anti-Ban Cooling Period)
                        batchState.sentSinceLastBatch = (batchState.sentSinceLastBatch || 0) + 1;
                        const batchSendLimit = c.batch_send > 0 ? c.batch_send : 20; // safe default: 20 messages
                        const batchSleepMins = c.batch_sleep > 0 ? c.batch_sleep : (c.batch_send > 0 ? 5 : 5); // default cooldown: 5 minutes

                        if (batchState.sentSinceLastBatch >= batchSendLimit) {
                            const pauseMs = batchSleepMins * 60 * 1000;
                            batchState.pausedUntil = Date.now() + pauseMs;
                            batchState.sentSinceLastBatch = 0;
                            const resumeTime = new Date(batchState.pausedUntil).toLocaleTimeString('id-ID');
                            console.log(`\n💤 [ANTI-BAN BATCH PACING] Camp #${c.id} (${c.name}): Sent ${batchSendLimit} messages in a row. Pausing for ${batchSleepMins} minutes (resuming at ${resumeTime}) to protect account health.`);
                        }

                    } catch (e) {
                        const errMsg = (e.message || 'Failed to send message').substring(0, 200);
                        await dbQuery("UPDATE campaign_recipients SET status = 'failed', error_msg = ? WHERE id = ?", [errMsg, target.id]);
                        await dbQuery("UPDATE campaigns SET failed = failed + 1 WHERE id = ?", [c.id]);
                        console.error(`[BROADCAST FAILED] Camp #${c.id} -> +${toNumber}: ${errMsg}`);

                        // If WhatsApp session is disconnected or logged out, auto-pause campaign
                        if (!sockData.socket || sockData.status !== 'connected' || /logged out|banned|connection closed/i.test(errMsg)) {
                            console.warn(`⚠️ [BROADCAST ABORT] WhatsApp session ${c.session_id} disconnected. Pausing campaign #${c.id}.`);
                            await dbQuery("UPDATE campaigns SET status = 'paused' WHERE id = ?", [c.id]);
                            delete activeCampaignsLocks[c.id];
                            delete activeDeviceLocks[c.session_id];
                            return;
                        }

                        // Circuit Breaker: If 5 numbers fail consecutively, auto-pause campaign to protect WhatsApp number
                        consecutiveFailures[c.id] = (consecutiveFailures[c.id] || 0) + 1;
                        if (consecutiveFailures[c.id] >= 5) {
                            console.warn(`⚠️ [CIRCUIT BREAKER] Camp #${c.id} (${c.name}): 5 consecutive failures detected! Auto-pausing campaign to protect WhatsApp account from ban.`);
                            await dbQuery("UPDATE campaigns SET status = 'paused' WHERE id = ?", [c.id]);
                            delete activeCampaignsLocks[c.id];
                            delete activeDeviceLocks[c.session_id];
                            delete campaignBatchState[c.id];
                            consecutiveFailures[c.id] = 0;
                            return;
                        }
                    } finally {
                        delete activeCampaignsLocks[c.id]; // Unlock campaign
                        delete activeDeviceLocks[c.session_id]; // Unlock device
                    }
                }, delayMs);

            } catch (err) {
                console.log('[BROADCAST PROC ERR]', err.message);
                delete activeCampaignsLocks[c.id];
                delete activeDeviceLocks[c.session_id];
            }
        }
    } catch (err) {
        console.error('[BROADCAST ENGINE ERR]', err.message);
    }
}

// Jalankan Broadcast Worker setiap 5 detik untuk memeriksa Queue
setInterval(processBroadcasts, 5000);


// ─── Auto-Reconnect Setiap 1 Jam (Hourly Watchdog & Auto-Reconnect) ───────────
const AUTO_RECONNECT_INTERVAL_MS = 60 * 60 * 1000; // 1 Jam (3600000 ms)

async function runHourlyAutoReconnect() {
    console.log(`\n[AUTO-RECONNECT 1 JAM] 🕒 Memulai siklus auto-reconnect berkala 1 jam...`);
    try {
        const devices = await dbQuery("SELECT id, session_id, name, phone, status FROM devices");
        if (!devices || !devices.length) {
            console.log(`[AUTO-RECONNECT 1 JAM] Tidak ada device terdaftar di database.`);
            return { count: 0, reconnected: 0 };
        }

        let reconnectedCount = 0;
        for (const dev of devices) {
            const sid = dev.session_id;
            const authPath = path.join(AUTH_DIR, sid);
            const credsPath = path.join(authPath, 'creds.json');

            let hasValidCreds = false;
            if (fs.existsSync(credsPath)) {
                try {
                    const c = JSON.parse(fs.readFileSync(credsPath, 'utf8'));
                    if (c?.me?.id || c?.registered) hasValidCreds = true;
                } catch (_) { }
            }

            if (!hasValidCreds) {
                // Perangkat belum scan QR / belum pernah login, lewati
                continue;
            }

            // Jika sedang ada antrean broadcast aktif di server, tunda reconnect agar pengiriman tidak terputus
            if (Object.keys(activeCampaignsLocks).length > 0) {
                console.log(`[AUTO-RECONNECT 1 JAM] Sesi ${sid} (${dev.phone || dev.name}): Ditunda karena broadcast sedang aktif.`);
                continue;
            }

            const currentSession = sessions[sid];
            const isAlive = currentSession?.status === 'connected' && currentSession?.socket;

            if (!isAlive) {
                console.log(`[AUTO-RECONNECT 1 JAM] ⚠️ Sesi ${sid} (${dev.phone || dev.name}) saat ini '${dev.status}'. Menghubungkan ulang ke WhatsApp sekarang...`);
                createSession(sid, false, false).catch(err => {
                    console.error(`[AUTO-RECONNECT 1 JAM ERR] Sesi ${sid}:`, err.message);
                });
                reconnectedCount++;
            } else {
                console.log(`[AUTO-RECONNECT 1 JAM] 🔄 Sesi ${sid} (${dev.phone || dev.name}) aktif. Melakukan penyegaran socket & koneksi berkala...`);
                createSession(sid, false, false).catch(err => {
                    console.error(`[AUTO-RECONNECT 1 JAM ERR] Sesi ${sid}:`, err.message);
                });
                reconnectedCount++;
            }

            // Jeda 3 detik antar device agar tidak terjadi beban spike bersamaan
            await new Promise(r => setTimeout(r, 3000));
        }
        console.log(`[AUTO-RECONNECT 1 JAM] ✅ Siklus auto-reconnect selesai (${reconnectedCount} sesi diproses).\n`);
        return { count: devices.length, reconnected: reconnectedCount };
    } catch (err) {
        console.error('[AUTO-RECONNECT 1 JAM FATAL]', err.message);
        return { error: err.message };
    }
}

// Jalankan watchdog & auto-reconnect setiap 1 jam
setInterval(runHourlyAutoReconnect, AUTO_RECONNECT_INTERVAL_MS);

// Bersihkan data mute expired & otomatis balas pesan yang tertunda setiap 15 detik
setInterval(cleanupExpiredMutedContacts, 15 * 1000);


// ─── Auto-Restore Sessions on Startup ────────────────────────────────────────
async function restoreSessionsFromDB() {
    try {
        // Ensure broadcast_blacklists table exists for anti-ban protection
        await dbQuery(`
            CREATE TABLE IF NOT EXISTS broadcast_blacklists (
                id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                phone VARCHAR(50) NOT NULL,
                reason VARCHAR(255) DEFAULT 'Opt-out (Balas STOP)',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY unique_blacklist_phone (phone)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        `);

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
                } catch (e) { }
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
    console.log(`║  Node.js: ${String(process.version + ' (Disarankan v22.x)').padEnd(40)}║`);
    console.log('║  Groq AI: ENABLED (auto-switch)                   ║');
    console.log('╚═══════════════════════════════════════════════════╝');
    console.log('');

    // Restore semua sesi yang sebelumnya aktif setelah server siap
    setTimeout(restoreSessionsFromDB, 3000);
}

// CloudLinux cPanel Node.js Selector Passenger Support
if (typeof (PhusionPassenger) !== 'undefined') {
    app.listen('passenger', () => onServerReady('cPanel Passenger'));
} else {
    app.listen(PORT, () => onServerReady(PORT));
}

