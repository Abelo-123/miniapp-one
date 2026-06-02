import crypto from 'crypto';
import pool from './config/database.js';

async function migrate() {
    console.log('🚀 Starting Database Migration for Referral System...');
    
    try {
        const connection = await pool.getConnection();

        console.log('\n[1/3] Adding referral_code column...');
        try {
            await connection.execute(`ALTER TABLE auth ADD COLUMN referral_code VARCHAR(50) UNIQUE DEFAULT NULL`);
            console.log('✅ Added referral_code column');
        } catch (e) {
            console.log('ℹ️ referral_code column already exists or error:', e.message);
        }

        console.log('\n[2/3] Adding referred_by column...');
        try {
            await connection.execute(`ALTER TABLE auth ADD COLUMN referred_by BIGINT(20) DEFAULT NULL`);
            console.log('✅ Added referred_by column');
        } catch (e) {
            console.log('ℹ️ referred_by column already exists or error:', e.message);
        }

        console.log('\n[3/3] Adding refers column...');
        try {
            await connection.execute(`ALTER TABLE auth ADD COLUMN refers JSON DEFAULT NULL`);
            console.log('✅ Added refers column');
        } catch (e) {
            console.log('ℹ️ refers column already exists or error:', e.message);
        }

        console.log('\n[Migrating Data] Generating referral codes for existing users...');
        const [users] = await connection.execute('SELECT tg_id FROM auth WHERE referral_code IS NULL');
        
        if (users.length === 0) {
            console.log('✅ All users already have a referral code.');
        } else {
            console.log(`Found ${users.length} users needing a referral code.`);
            for (let i = 0; i < users.length; i++) {
                const user = users[i];
                // Generate code: REF + 3 random hex bytes (6 chars) + last 3 of ID
                const randomHex = crypto.randomBytes(3).toString('hex').toUpperCase();
                const idSuffix = user.tg_id.toString().slice(-3);
                const refCode = `REF${randomHex}${idSuffix}`;
                
                await connection.execute('UPDATE auth SET referral_code = ? WHERE tg_id = ?', [refCode, user.tg_id]);
                
                if ((i + 1) % 10 === 0) {
                    console.log(`Processed ${i + 1}/${users.length}...`);
                }
            }
            console.log(`✅ Successfully generated referral codes for ${users.length} users.`);
        }

        connection.release();
        console.log('\n🎉 Migration complete! The database is now ready for the referral system.');
    } catch (err) {
        console.error('\n❌ Migration Failed:', err);
    } finally {
        // Close the pool so the script exits
        await pool.end();
        process.exit(0);
    }
}

migrate();
