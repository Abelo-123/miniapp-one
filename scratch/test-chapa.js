/**
 * Chapa Diagnostic Script
 * Use this to verify your API keys and connection to Chapa.
 */
import 'dotenv/config';
import fetch from 'node-fetch'; // or use global fetch if node 18+

const CHAPA_SECRET_KEY = process.env.CHAPA_SECRET_KEY;
const CHAPA_BASE_URL = 'https://api.chapa.co/v1';

async function testChapa() {
    console.log('--- Chapa Diagnostic ---');
    console.log('Secret Key:', CHAPA_SECRET_KEY ? `${CHAPA_SECRET_KEY.substring(0, 12)}...` : 'MISSING');
    
    if (!CHAPA_SECRET_KEY) {
        console.error('Error: CHAPA_SECRET_KEY is not defined in .env');
        return;
    }

    try {
        console.log('Testing connection to Chapa...');
        const res = await fetch(`${CHAPA_BASE_URL}/banks`, {
            headers: { Authorization: `Bearer ${CHAPA_SECRET_KEY}` }
        });
        
        const data = await res.json();
        
        if (res.status === 200) {
            console.log('✅ Success: Connected to Chapa API.');
            console.log(`Found ${data.data?.length || 0} supported banks.`);
            
            if (CHAPA_SECRET_KEY.includes('tEs') || CHAPA_SECRET_KEY.includes('TEST')) {
                console.log('⚠️ Warning: You are using a TEST API key. Real payments will NOT be verified.');
            } else {
                console.log('🚀 Confirmed: You are using a LIVE API key.');
            }
        } else {
            console.error(`❌ Error: Chapa API returned HTTP ${res.status}`);
            console.error('Message:', data.message || 'No message');
            if (res.status === 401) {
                console.error('Reason: Your CHAPA_SECRET_KEY is invalid or unauthorized.');
            }
        }
    } catch (err) {
        console.error('❌ Network Error:', err.message);
    }
}

testChapa();
