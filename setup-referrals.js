import pool from './config/database.js';
import crypto from 'crypto';

async function migrate() {
    console.log('Starting referral system database migration...');
    let conn;
    try {
        conn = await pool.getConnection();

        // 1. Add Columns (Ignores errors if they already exist)
        try {
            await conn.execute(`ALTER TABLE auth ADD COLUMN referral_code VARCHAR(50) UNIQUE DEFAULT NULL`);
            console.log('✅ Added referral_code column');
        } catch (e) {
            console.log('ℹ️ referral_code column already exists');
        }

        try {
            await conn.execute(`ALTER TABLE auth ADD COLUMN referred_by BIGINT(20) DEFAULT NULL`);
            console.log('✅ Added referred_by column');
        } catch (e) {
            console.log('ℹ️ referred_by column already exists');
        }

        try {
            await conn.execute(`ALTER TABLE auth ADD COLUMN refers JSON DEFAULT NULL`);
            console.log('✅ Added refers column');
        } catch (e) {
            console.log('ℹ️ refers column already exists');
        }

        // 2. Backfill referral codes for existing users
        console.log('\nChecking for existing users without referral codes...');
        const [users] = await conn.execute('SELECT tg_id FROM auth WHERE referral_code IS NULL');
        
        if (users.length > 0) {
            console.log(`Found ${users.length} users needing referral codes. Generating...`);
            
            for (const user of users) {
                const newRefCode = 'REF' + crypto.randomBytes(3).toString('hex').toUpperCase() + user.tg_id.toString().slice(-3);
                await conn.execute('UPDATE auth SET referral_code = ? WHERE tg_id = ?', [newRefCode, user.tg_id]);
                console.log(`  -> Assigned ${newRefCode} to user ${user.tg_id}`);
            }
            console.log('✅ All existing users updated successfully!');
        } else {
            console.log('✅ All existing users already have referral codes.');
        }

    } catch (err) {
        console.error('❌ Migration failed:', err);
    } finally {
        if (conn) conn.release();
        console.log('\nMigration complete. Exiting...');
        process.exit(0);
    }
}

migrate();
