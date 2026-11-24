/**
 * groq.js — Groq API Helper with Auto-Switch
 * For WAGTW Engine
 * 
 * Coding by: cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

const https = require('https');

/**
 * Call Groq API
 * @param {string} apiKey   — Groq API Key
 * @param {string} model    — Model ID (e.g. llama-3.3-70b-versatile)
 * @param {string} sysPrompt — System prompt / rules
 * @param {Array} chatHistory  — Array of objects: [{ role: 'user'|'assistant', content: '...' }]
 * @returns {Promise<string|null>}  — AI reply text or null on failure
 */
async function callGroq(apiKey, model, sysPrompt, chatHistory) {
    return new Promise((resolve) => {
        const payload = JSON.stringify({
            model: (model && model !== 'llama3-8b-8192') ? model : 'llama-3.3-70b-versatile',
            messages: [
                ...(sysPrompt ? [{ role: 'system', content: sysPrompt }] : []),
                ...(Array.isArray(chatHistory) ? chatHistory : [{ role: 'user', content: String(chatHistory) }]),
            ],
            max_tokens: 1500,
            temperature: 0.7,
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
                        resolve({ ok: true, text: json.choices[0].message.content.trim(), statusCode: 200 });
                    } else {
                        const errMsg = json.error?.message || `HTTP ${res.statusCode}`;
                        console.warn(`[Groq] Error (${res.statusCode}): ${errMsg}`);
                        resolve({ ok: false, error: errMsg, statusCode: res.statusCode || 500 });
                    }
                } catch (e) {
                    console.warn('[Groq] Parse error:', e.message);
                    resolve({ ok: false, error: 'Parse response error: ' + e.message, statusCode: res.statusCode || 500 });
                }
            });
        });

        req.on('error', (e) => {
            console.warn('[Groq] Request error:', e.message);
            resolve({ ok: false, error: e.message, statusCode: 'NET_ERR' });
        });

        req.setTimeout(15000, () => {
            req.destroy();
            console.warn('[Groq] Request timeout');
            resolve({ ok: false, error: 'Request timeout (15s)', statusCode: 'TIMEOUT' });
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
