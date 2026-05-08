import 'dotenv/config';

async function test() {
    const apiKey = process.env.GODOFPANEL_API_KEY;
    if (!apiKey) {
        console.error('No API key found in .env');
        return;
    }

    const params = new URLSearchParams({ key: apiKey, action: 'services' });
    const res = await fetch('https://godofpanel.com/api/v2', {
        method: 'POST',
        body: params
    });
    const services = await res.json();
    
    const svc = services.find(s => s.service === '2807');
    console.log('Service 2807 raw data:', svc);
}

test();
