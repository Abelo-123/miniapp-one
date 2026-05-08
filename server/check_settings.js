import pool from './config/database.js';

async function checkSettings() {
    try {
        const [rows] = await pool.execute('SELECT * FROM settings');
        console.log('Settings:', rows);
        process.exit(0);
    } catch (err) {
        console.error(err);
        process.exit(1);
    }
}

checkSettings();
