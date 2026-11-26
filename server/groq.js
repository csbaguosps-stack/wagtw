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
    'llama3-8b-8192': 'llama-3.1-8b-instant',
    'llama3-70b-8192': 'llama-3.3-70b-versatile',
    'llama-3.1-70b-versatile': 'llama-3.3-70b-versatile',
    'llama-3.2-1b-preview': 'llama-3.1-8b-instant',
    'llama-3.2-3b-preview': 'llama-3.1-8b-instant',
};

/**
 * Call Groq API
 * @param {string} apiKey       — Groq API Key
 * @param {string} model        — Model ID (e.g. llama-3.3-70b-versatile or llama-3.1-8b-instant)
 * @param {string} sysPrompt    — System prompt / rules
 * @param {Array} chatHistory   — Array of objects: [{ role: 'user'|'assistant', content: '...' }]
 * @param {number} maxTokens    — Max completion tokens (default 450 untuk efisiensi TPM WhatsApp)
 * @returns {Promise<object>}   — { ok: boolean, text?: string, error?: string, statusCode: number|string, isRateLimit: boolean }
 */
async function callGroq(apiKey, model, sysPrompt, chatHistory, maxTokens = 450) {
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

        // Safety check TPM: Untuk model dengan kuota ketat 8.000 TPM (seperti gpt-oss-20b / gpt-oss-120b)
        let effectiveMaxTokens = maxTokens;
        const isLowTpmModel = /gpt-oss|20b|120b/i.test(safeModel);
        if (isLowTpmModel) {
            effectiveMaxTokens = Math.min(280, maxTokens);
        }

        // Pangkas payload jika total karakter sangat panjang (> 14.000 karakter ~ 4.500-5.500 tokens)
        // agar request + max_tokens tidak pernah melampaui limit TPM 8.000 (HTTP 413)
        let totalChars = messages.reduce((acc, m) => acc + (m.content ? m.content.length : 0), 0);
        if (totalChars > 13000 && messages.length > 1) {
            const sys = messages[0].role === 'system' ? messages[0] : null;
            if (sys && sys.content && sys.content.length > 10000) {
                sys.content = sys.content.substring(0, 9500) + '\n\n[Data dipangkas demi efisiensi kuota TPM...]';
            }
            const recent = messages.slice(-2);
            messages = sys ? [sys, ...recent] : recent;
            console.log(`[Groq] Prompt dipangkas agar total request aman di bawah batas TPM 8.000 untuk model ${safeModel}`);
        }

        const payload = JSON.stringify({
            model: safeModel,
            messages: messages,
            max_tokens: Math.min(1000, Math.max(150, effectiveMaxTokens)),
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
                        resolve({ ok: true, text: json.choices[0].message.content.trim(), statusCode: 200, isRateLimit: false });
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

module.exports = { callGroq, callGroqAutoSwitch };
