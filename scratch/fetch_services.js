import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Helper to manually parse .env files
function loadEnvFile(envPath) {
    if (!fs.existsSync(envPath)) return {};
    const content = fs.readFileSync(envPath, 'utf8');
    const env = {};
    content.split('\n').forEach(line => {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('#')) return;
        const match = trimmed.match(/^([^=]+)=(.*)$/);
        if (match) {
            const key = match[1].trim();
            let val = match[2].trim();
            // Remove surrounding quotes if any
            if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
                val = val.substring(1, val.length - 1);
            }
            env[key] = val;
        }
    });
    return env;
}

// Try loading from api/.env first, then root .env
const projectRoot = path.join(__dirname, '..');
const apiEnv = loadEnvFile(path.join(projectRoot, 'api', '.env'));
const rootEnv = loadEnvFile(path.join(projectRoot, '.env'));

// Merge variables
const apiKey = apiEnv.GODOFPANEL_API_KEY || rootEnv.GODOFPANEL_API_KEY || '7aed775ad8b88b50a1706db2f35c5eaf';

async function fetchAndCacheServices() {
    console.log(`Using GodOfPanel API Key: ${apiKey.substring(0, 5)}...`);
    console.log('Fetching services from GodOfPanel API...');
    try {
        const response = await fetch('https://godofpanel.com/api/v2', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                key: apiKey,
                action: 'services',
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP Error ${response.status}`);
        }

        const data = await response.json();
        if (data.error) {
            throw new Error(`API Error: ${data.error}`);
        }

        if (!Array.isArray(data)) {
            throw new Error('Invalid response format: expected an array of services');
        }

        console.log(`Successfully fetched ${data.length} services from GodOfPanel.`);

        // Build the cache structure matching getCachedData / setCachedData in PHP
        const cachePayload = {
            time: Math.floor(Date.now() / 1000),
            payload: data,
        };

        const cacheDir = path.join(projectRoot, 'api', 'cache');
        if (!fs.existsSync(cacheDir)) {
            fs.mkdirSync(cacheDir, { recursive: true });
        }

        const cacheFilePath = path.join(cacheDir, 'cache_upstream_services.json');
        fs.writeFileSync(cacheFilePath, JSON.stringify(cachePayload, null, 2));

        console.log(`\n✅ Cached services successfully to: ${cacheFilePath}`);
        console.log('You can now upload the entire "api/cache/" folder to your InfinityFree /htdocs/api/ directory.');
    } catch (err) {
        console.error('❌ Failed to fetch and cache services:', err);
    }
}

fetchAndCacheServices();
