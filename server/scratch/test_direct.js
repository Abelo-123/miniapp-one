import fetch from 'node-fetch';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

dotenv.config({ path: path.resolve(__dirname, '../.env') });

const apiKey = process.env.CHAPA_SECRET_KEY;
console.log('Using API Key:', apiKey);

async function runTest() {
    const provider = 'telebirr';
    const phone_number = '0912345678'; // dummy or test number format
    const amount = 10;
    const generatedTxRef = `TEST-DEP-${Date.now()}`;

    try {
        const url = `https://api.chapa.co/v1/charges?type=${provider}`;
        console.log('Calling URL:', url);
        const chapaRes = await fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${apiKey}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                amount: amount,
                currency: 'ETB',
                email: 'customer@paxyo.com',
                first_name: 'User',
                last_name: 'Test',
                phone_number: phone_number,
                tx_ref: generatedTxRef
            })
        });

        console.log('Status Code:', chapaRes.status);
        const text = await chapaRes.text();
        console.log('Raw Response:', text);
    } catch (err) {
        console.error('Error:', err);
    }
}

runTest();
