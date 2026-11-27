/**
 * groq.js — Groq API Helper with Auto-Switch
 * For WAGTW Engine
 * 
 * Coding by: cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

const https = require('https');

// Pemetaan model lama/decommissioned ke model aktif resmi Groq
const DECOMMISSIONED_MAP = {
    'llama3-8b-8192': 'openai/gpt-oss-20b',
    'llama3-70b-8192': 'openai/gpt-oss-120b',
    'llama-3.1-70b-versatile': 'openai/gpt-oss-120b',
    'llama-3.1-8b-instant': 'openai/gpt-oss-20b',
    'llama-3.3-70b-versatile': 'openai/gpt-oss-120b',
    'llama-3.2-1b-preview': 'openai/gpt-oss-20b',
    'llama-3.2-3b-preview': 'openai/gpt-oss-20b',
};

/**
 * Memastikan pesan balasan AI tidak terpotong di tengah jalan atau menggantung
 * Jika terpotong akibat limit token / output terputus, buang baris yang tidak tuntas dan tutup dengan rapi
 */
function fixHangingText(text, finishReason = 'stop') {
    if (!text || typeof text !== 'string') return text;
    let trimmed = text.trim();
    if (!trimmed) return trimmed;

    // Tanda baca penutup kalimat / pesan WhatsApp yang sah
    const validEndings = ['.', '!', '?', '😊', '🙏', '✨', '👍', '👋', '”', '"', ')', '>', '…', '🎉', '🌟'];
    const isTruncated = finishReason === 'length' || !validEndings.some(e => trimmed.endsWith(e));
    
    if (!isTruncated) {
        return trimmed;
    }

    const lines = trimmed.split('\n');
    let modified = false;

    while (lines.length > 0) {
        let lastLine = lines[lines.length - 1].trim();
        if (!lastLine) {
            lines.pop();
            continue;
        }

        // Jika baris terakhir sudah diakhiri tanda baca penutup yang sah, selesai
        if (validEndings.some(e => lastLine.endsWith(e))) {
            break;
        }

        // Jika baris terakhir adalah poin/bullet yang terpotong di tengah kalimat
        // contoh: "- Beli lensa ≥ Rp 500.000 → Free frame senilai Rp" atau "Mohon"
        if (lastLine.startsWith('-') || lastLine.startsWith('•') || lastLine.startsWith('*') || /^\d+[\.\)]/.test(lastLine) || lastLine.length < 90) {
            lines.pop();
            modified = true;
            // Jika baris sebelumnya adalah judul/header item yang isinya baru saja terhapus
            // contoh: "• Lensa FUJI - Free Frame"
            if (lines.length > 0) {
                let prev = lines[lines.length - 1].trim();
                if ((prev.startsWith('•') || prev.startsWith('*') || prev.startsWith('-')) && !prev.endsWith('.') && !prev.endsWith(':')) {
                    lines.pop();
                }
            }
            continue;
        }

        // Jika baris biasa terpotong, potong sampai tanda baca kalimat terakhir yang utuh
        const lastPunctIdx = Math.max(
            lastLine.lastIndexOf('. '),
            lastLine.lastIndexOf('.'),
            lastLine.lastIndexOf('!'),
            lastLine.lastIndexOf('?')
        );
        if (lastPunctIdx > 0) {
            lines[lines.length - 1] = lastLine.substring(0, lastPunctIdx + 1).trim();
            modified = true;
            break;
        } else {
            lines.pop();
            modified = true;
        }
    }

    trimmed = lines.join('\n').trim();
    if (!trimmed) return text.trim();

    if (!validEndings.some(e => trimmed.endsWith(e))) {
        trimmed += '.';
    }

    // Jika pesan sebelumnya terpotong di tengah jalan, tambahkan pertanyaan penutup agar tetap natural dan tuntas
    if (modified || finishReason === 'length') {
        trimmed += '\n\nBoleh kami bantu info lebih lengkap atau mau kami bantu cekkan stoknya kak? 😊';
    }

    return trimmed;
}

/**
 * Call Groq API
 * @param {string} apiKey       — Groq API Key
 * @param {string} model        — Model ID (e.g. openai/gpt-oss-20b or openai/gpt-oss-120b)
 * @param {string} sysPrompt    — System prompt / rules
 * @param {Array} chatHistory   — Array of objects: [{ role: 'user'|'assistant', content: '...' }]
 * @param {number} maxTokens    — Max completion tokens (default 600 untuk balasan lengkap & tidak terpotong)
 * @returns {Promise<object>}   — { ok: boolean, text?: string, error?: string, statusCode: number|string, isRateLimit: boolean }
 */
async function callGroq(apiKey, model, sysPrompt, chatHistory, maxTokens = 600) {
    return new Promise((resolve) => {
        let safeModel = (model && typeof model === 'string') ? model.trim() : '';
        if (DECOMMISSIONED_MAP[safeModel]) {
            safeModel = DECOMMISSIONED_MAP[safeModel];
        }

        if (!safeModel || safeModel === 'auto') {
            resolve({ ok: false, error: 'Model AI belum dipilih atau tidak valid.', statusCode: 400 });
            return;
        }

        let messages = [
            ...(sysPrompt ? [{ role: 'system', content: sysPrompt }] : []),
            ...(Array.isArray(chatHistory) ? chatHistory : [{ role: 'user', content: String(chatHistory) }]),
        ];

        // ── BERSIHKAN FORMAT PAYLOAD TANPA MEMOTONG DATA PENGETAHUAN TOKO ──
        // Hapus baris pembatas dekoratif (===, ---), spasi berlebih tanpa menghilangkan kata/fakta
        if (messages[0]?.role === 'system' && messages[0].content) {
            messages[0].content = messages[0].content
                .replace(/[=\-_*~#]{3,}/g, '')
                .replace(/[ \t]+/g, ' ')
                .replace(/\r\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim();
        }

        let totalChars = messages.reduce((acc, m) => acc + (m.content ? m.content.length : 0), 0);

        // Jika riwayat chat panjang, prioritaskan 2 pesan paling baru agar konteks input tidak membengkak
        if (messages.length > 3) {
            const sys = messages[0].role === 'system' ? messages[0] : null;
            const recent = messages.slice(-2); // Ambil 2 pesan terakhir
            messages = sys ? [sys, ...recent] : recent;
            totalChars = messages.reduce((acc, m) => acc + (m.content ? m.content.length : 0), 0);
        }

        // Estimasi token input secara akurat (~3.0 karakter per token pada teks Indonesia)
        const estimatedInputTokens = Math.ceil(totalChars / 3.0);

        // Alokasikan max_tokens yang optimal:
        // Cukup besar (600 - 1.100 tokens) agar teks TIDAK TERPOTONG dan sangat detail,
        // namun tetap di bawah batas kuota TPM (7.850 - estimatedInputTokens) agar tidak pernah 413.
        const maxAllowedTokens = Math.max(500, Math.min(1100, 7850 - estimatedInputTokens));
        const effectiveMaxTokens = Math.min(maxAllowedTokens, maxTokens || 1000);

        const payload = JSON.stringify({
            model: safeModel,
            messages: messages,
            max_tokens: effectiveMaxTokens,
            temperature: 0.6,
        });

        const options = {
            hostname: 'api.groq.com',
            path: '/openai/v1/chat/completions',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${apiKey}`,
                'Content-Length': Buffer.byteLength(payload),
            },
        };

        const req = https.request(options, (res) => {
            res.setEncoding('utf8');
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    const json = JSON.parse(data);
                    if (res.statusCode === 200 && json.choices?.[0]?.message?.content) {
                        const rawText = json.choices[0].message.content.trim();
                        const finishReason = json.choices[0].finish_reason || 'stop';
                        // Perbaiki teks jika terpotong / menggantung
                        const fixedText = fixHangingText(rawText, finishReason);
                        resolve({ ok: true, text: fixedText, statusCode: 200, isRateLimit: false, finishReason });
                    } else {
                        const errMsg = json.error?.message || `HTTP ${res.statusCode}`;
                        const isRateLimit = res.statusCode === 429 || /rate[_\s-]?limit|too many requests|tpm|rpm|quota/i.test(errMsg);
                        console.warn(`[Groq] Error (${res.statusCode}): ${errMsg}`);
                        resolve({
                            ok: false,
                            error: errMsg,
                            statusCode: res.statusCode || 500,
                            isRateLimit,
                            retryAfter: res.headers['retry-after'] || null
                        });
                    }
                } catch (e) {
                    console.warn('[Groq] Parse error:', e.message);
                    resolve({ ok: false, error: 'Parse response error: ' + e.message, statusCode: res.statusCode || 500, isRateLimit: false });
                }
            });
        });

        req.on('error', (e) => {
            console.warn('[Groq] Request error:', e.message);
            resolve({ ok: false, error: e.message, statusCode: 'NET_ERR', isRateLimit: false });
        });

        req.setTimeout(15000, () => {
            req.destroy();
            console.warn('[Groq] Request timeout');
            resolve({ ok: false, error: 'Request timeout (15s)', statusCode: 'TIMEOUT', isRateLimit: false });
        });

        req.write(payload);
        req.end();
    });
}

/**
 * Call Groq with auto-switch across multiple API keys
 * @param {Array}  servers   — Array of {api_key, model} objects, ordered by priority DESC
 * @param {string} sysPrompt — System prompt
 * @param {Array} chatHistory   — Array of chat history objects
 * @returns {Promise<string|null>}
 */
async function callGroqAutoSwitch(servers, sysPrompt, chatHistory) {
    if (!servers || servers.length === 0) return null;

    for (const server of servers) {
        console.log(`[Groq] Trying server: ${server.label || server.id} | Model: ${server.model}`);
        const res = await callGroq(server.api_key, server.model, sysPrompt, chatHistory);
        if (res && res.ok && res.text) {
            console.log(`[Groq] ✓ Got reply from: ${server.label || server.id}`);
            return res.text;
        }
        console.warn(`[Groq] ✗ Failed on server: ${server.label || server.id}, switching...`);
    }

    console.warn('[Groq] All servers failed, no auto-switch fallback available.');
    return null;
}

module.exports = { callGroq, callGroqAutoSwitch, fixHangingText };
