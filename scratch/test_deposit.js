async function testDeposit() {
    try {
        const res = await fetch('https://back9090-1.onrender.com/deposit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ amount: 15, user_id: '123456789', return_url: 'https://localhost' })
        });
        const text = await res.text();
        console.log("Raw response:", text);
    } catch (e) {
        console.error("Fetch error:", e.message);
    }
}

testDeposit();
