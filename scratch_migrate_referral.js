import pool from './config/database.js';

async function migrate() {
    try {
        console.log('Adding referral columns...');
        await pool.execute('ALTER TABLE auth ADD COLUMN IF NOT EXISTS referral_code VARCHAR(50) UNIQUE DEFAULT NULL');
        await pool.execute('ALTER TABLE auth ADD COLUMN IF NOT EXISTS referred_by BIGINT(20) DEFAULT NULL');
        console.log('Done!');
        process.exit(0);
    } catch (e) {
        console.error(e);
        process.exit(1);
    }
}

migrate();
