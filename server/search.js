/**
 * search.js — Web Search Helper for WAGTW AI Engine
 * Mendukung DuckDuckGo (gratis/scraper), Bing Web Search API, Google Custom Search API
 * Auto-switch berdasarkan priority & status aktif dari DB
 * 
 * Coding by: cs.baguosps@gmail.com
 * Copyright (c) 2024-2026. All rights reserved.
 */

const https = require('https');
const http  = require('http');
const { URL } = require('url');

/**
 * Lakukan pencarian DuckDuckGo (tidak butuh API key, pakai HTML scraping)
 * @param {string} query
 * @returns {Promise<string|null>} snippet hasil pencarian
 */
async function searchDuckDuckGo(query) {
    return new Promise((resolve) => {
        const encoded = encodeURIComponent(query);
        const options = {
            hostname: 'html.duckduckgo.com',
            path: `/html/?q=${encoded}`,
            method: 'GET',
            headers: {
                'User-Agent': 'Mozilla/5.0 (compatible; WAGTW-AI-Search/1.0)',
                'Accept': 'text/html',
            },
        };

        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                try {
                    // Ekstrak snippet dari result-snippet class
                    const snippets = [];
                    const snippetRegex = /class="result__snippet"[^>]*>([\s\S]*?)<\/a>/g;
                    let match;
                    while ((match = snippetRegex.exec(data)) !== null && snippets.length < 3) {
                        const clean = match[1]
                            .replace(/<[^>]+>/g, '')
                            .replace(/&amp;/g, '&')
                            .replace(/&lt;/g, '<')
                            .replace(/&gt;/g, '>')
                            .replace(/&quot;/g, '"')
                            .replace(/&#x27;/g, "'")
                            .replace(/\s+/g, ' ')
                            .trim();
                        if (clean.length > 20) snippets.push(clean);
                    }

                    if (snippets.length > 0) {
                        resolve(snippets.join('\n'));
                    } else {
                        resolve(null);
                    }
                } catch (e) {
                    console.warn('[Search:DuckDuckGo] Parse error:', e.message);
                    resolve(null);
                }
            });
        });

        req.on('error', (e) => {
            console.warn('[Search:DuckDuckGo] Request error:', e.message);
            resolve(null);
        });

        req.setTimeout(10000, () => {
            req.destroy();
            console.warn('[Search:DuckDuckGo] Timeout');
            resolve(null);
        });

        req.end();
    });
}

/**
 * Cari menggunakan Bing Web Search API atau Scraping
 * @param {string} query
 * @param {string} apiKey — Bing API Key from Azure (optional)
 * @returns {Promise<string|null>}
 */
async function searchBing(query, apiKey) {
    if (apiKey && apiKey.trim() !== '') {
        return new Promise((resolve) => {
            const encoded = encodeURIComponent(query);
            const options = {
                hostname: 'api.bing.microsoft.com',
                path: `/v7.0/search?q=${encoded}&count=3&mkt=id-ID`,
                method: 'GET',
                headers: {
                    'Ocp-Apim-Subscription-Key': apiKey,
                },
            };

            const req = https.request(options, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        const json = JSON.parse(data);
                        const snippets = (json?.webPages?.value || [])
                            .slice(0, 3)
                            .map(r => r.snippet || '')
                            .filter(s => s.length > 10);
                        resolve(snippets.length > 0 ? snippets.join('\n') : null);
                    } catch (e) {
                        console.warn('[Search:Bing:API] Parse error:', e.message);
                        resolve(null);
                    }
                });
            });

            req.on('error', (e) => { console.warn('[Search:Bing:API] Error:', e.message); resolve(null); });
            req.setTimeout(10000, () => { req.destroy(); resolve(null); });
            req.end();
        });
    } else {
        // HTML Scraping Fallback
        return new Promise((resolve) => {
            const encoded = encodeURIComponent(query);
            const options = {
                hostname: 'www.bing.com',
                path: `/search?q=${encoded}`,
                method: 'GET',
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36',
                    'Accept': 'text/html',
                },
            };

            const req = https.request(options, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        const snippets = [];
                        const snippetRegex = /<p[^>]*>([\s\S]*?)<\/p>/g;
                        let match;
                        while ((match = snippetRegex.exec(data)) !== null && snippets.length < 5) {
                            const clean = match[1].replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&#39;/g, "'").trim();
                            if (clean.length > 40 && !clean.includes('Bing') && !clean.includes('Microsoft')) {
                                snippets.push(clean);
                            }
                        }
                        if (snippets.length > 0) resolve(snippets.slice(0, 3).join('\n'));
                        else resolve(null);
                    } catch (e) {
                        console.warn('[Search:Bing:Scrape] Error:', e.message);
                        resolve(null);
                    }
                });
            });

            req.on('error', (e) => { console.warn('[Search:Bing:Scrape] Req error:', e.message); resolve(null); });
            req.setTimeout(10000, () => { req.destroy(); resolve(null); });
            req.end();
        });
    }
}

/**
 * Cari menggunakan Google Custom Search JSON API atau HTML Scraping
 * @param {string} query
 * @param {string} apiKey — Google API Key (optional)
 * @returns {Promise<string|null>}
 */
async function searchGoogle(query, apiKey) {
    if (apiKey && apiKey.trim() !== '') {
        // Support format "API_KEY|CX_ID" pada field api_key
        let key = apiKey;
        let cx  = '';
        if (apiKey.includes('|')) {
            [key, cx] = apiKey.split('|');
        }

        return new Promise((resolve) => {
            const encoded = encodeURIComponent(query);
            const path = `/customsearch/v1?key=${key}${cx ? `&cx=${cx}` : ''}&q=${encoded}&num=3&hl=id`;

            const options = {
                hostname: 'www.googleapis.com',
                path: path,
                method: 'GET',
            };

            const req = https.request(options, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        const json = JSON.parse(data);
                        const snippets = (json?.items || [])
                            .slice(0, 3)
                            .map(r => r.snippet || '')
                            .filter(s => s.length > 10);
                        resolve(snippets.length > 0 ? snippets.join('\n') : null);
                    } catch (e) {
                        console.warn('[Search:Google:API] Parse error:', e.message);
                        resolve(null);
                    }
                });
            });

            req.on('error', (e) => { console.warn('[Search:Google:API] Error:', e.message); resolve(null); });
            req.setTimeout(10000, () => { req.destroy(); resolve(null); });
            req.end();
        });
    } else {
        // HTML Scraping Fallback
        return new Promise((resolve) => {
            const encoded = encodeURIComponent(query);
            const options = {
                hostname: 'www.google.com',
                path: `/search?q=${encoded}&hl=id`,
                method: 'GET',
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                    'Accept': 'text/html',
                    'Accept-Language': 'id,en;q=0.9'
                },
            };

            const req = https.request(options, (res) => {
                let data = '';
                res.on('data', chunk => data += chunk);
                res.on('end', () => {
                    try {
                        const snippets = [];
                        // Common Google snippet containers: VwiC3b or BNeawe
                        const snippetRegex = /<div class="[^"]*BNeawe[^"]*"[^>]*>([\s\S]*?)<\/div>/g;
                        let match;
                        while ((match = snippetRegex.exec(data)) !== null && snippets.length < 4) {
                            const clean = match[1].replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&#39;/g, "'").trim();
                            if (clean.length > 40 && !clean.includes('Coba Penelusuran')) {
                                snippets.push(clean);
                            }
                        }

                        if (snippets.length === 0) {
                            const fallbackRegex = /<div class="[^"]*VwiC3b[^"]*"[^>]*>([\s\S]*?)<\/div>/g;
                            while ((match = fallbackRegex.exec(data)) !== null && snippets.length < 3) {
                                const clean = match[1].replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').replace(/&#39;/g, "'").trim();
                                if (clean.length > 40) snippets.push(clean);
                            }
                        }

                        if (snippets.length > 0) resolve(snippets.slice(0, 3).join('\n'));
                        else resolve(null);
                    } catch (e) {
                        console.warn('[Search:Google:Scrape] Error:', e.message);
                        resolve(null);
                    }
                });
            });

            req.on('error', (e) => { console.warn('[Search:Google:Scrape] Req error:', e.message); resolve(null); });
            req.setTimeout(10000, () => { req.destroy(); resolve(null); });
            req.end();
        });
    }
}

/**
 * Auto-switch search: coba setiap engine aktif berurutan (priority DESC)
 * @param {Array}  engines  — Array dari {provider, api_key, label, is_active} dari DB
 * @param {string} query    — Query yang akan dicari
 * @returns {Promise<{result: string|null, provider: string|null}>}
 */
async function searchAutoSwitch(engines, query) {
    if (!engines || engines.length === 0) return { result: null, provider: null };

    const activeEngines = engines.filter(e => e.is_active == 1);
    if (activeEngines.length === 0) return { result: null, provider: null };

    for (const engine of activeEngines) {
        console.log(`[Search] Mencoba provider: ${engine.provider} (${engine.label})`);
        let result = null;

        try {
            switch (engine.provider) {
                case 'google':
                    result = await searchGoogle(query, engine.api_key);
                    break;
                case 'bing':
                    result = await searchBing(query, engine.api_key);
                    break;
                case 'duckduckgo':
                    result = await searchDuckDuckGo(query);
                    break;
                default:
                    result = await searchDuckDuckGo(query);
            }
        } catch (e) {
            console.warn(`[Search] Error pada ${engine.provider}:`, e.message);
        }

        if (result) {
            console.log(`[Search] ✓ Hasil dari: ${engine.provider}`);
            return { result, provider: engine.provider };
        }

        console.warn(`[Search] ✗ Gagal pada: ${engine.provider}, beralih...`);
    }

    return { result: null, provider: null };
}

/**
 * Cek apakah pertanyaan user kemungkinan butuh info terkini (perlu search)
 * @param {string} text
 * @returns {boolean}
 */
function needsWebSearch(text) {
    const t = text.toLowerCase();
    const triggers = [
        'berita', 'news', 'hari ini', 'terkini', 'terbaru', 'sekarang', 'update',
        'harga', 'cuaca', 'prakiraan', 'jadwal', 'ramalan', 'kurs', 'dollar',
        'berapa harga', 'kapan', 'siapa', 'dimana', 'bagaimana cara', 'apa itu',
        'tahun ini', 'bulan ini', 'minggu ini', 'kemarin', 'lagi trending',
        'viral', 'sedang', 'terjadi', 'kabar', 'informasi terbaru',
    ];
    return triggers.some(kw => t.includes(kw));
}

module.exports = { searchAutoSwitch, searchDuckDuckGo, searchBing, searchGoogle, needsWebSearch };
