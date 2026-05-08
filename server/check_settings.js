import pool from './config/database.js';

async function check() {
    try {
        const [rows] = await pool.execute('SELECT * FROM service_custom WHERE service_id = 2807');
        console.log('Service 2807 custom settings:', rows);
        
        const [settings] = await pool.execute('SELECT * FROM settings WHERE setting_key = "rate_multiplier"');
        console.log('Rate multiplier:', settings);
    } catch (e) {
        console.error(e);
    } finally {
        process.exit(0);
    }
}

check();
