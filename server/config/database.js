/**
 * Database Configuration — MySQL2 Connection Pool
 * Using cPanel MySQL
 */
import mysql from 'mysql2/promise';

// cPanel MySQL credentials
const pool = mysql.createPool({
    host: 'localhost',
    user: 'paxyocom_newRender',
    password: '_[xgm!h,PT0MUx,y',
    database: 'paxyocom_paxyov3',
    // host: 'localhost',
    // user: 'root',
    // password: '',
    // database: 'paxyobot',
    port: 3306,
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    connectTimeout: 10000,
});

// TEST CONNECTION AND LOG ERRORS
pool.getConnection()
    .then(async conn => {
        console.log('✅ DB Connected to cPanel MySQL');
        try {
            await conn.execute(`DROP TABLE IF EXISTS chat_messages`);
            await conn.execute(`
                CREATE TABLE chat_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    is_admin TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            `);
            console.log('✅ chat_messages table ready');

            // Add referral columns to auth table if they don't exist
            try {
                await conn.execute(`ALTER TABLE auth ADD COLUMN referral_code VARCHAR(50) UNIQUE DEFAULT NULL`);
                console.log('✅ referral_code column added to auth');
            } catch (e) {
                // Column might already exist
            }
            try {
                await conn.execute(`ALTER TABLE auth ADD COLUMN referred_by BIGINT(20) DEFAULT NULL`);
                console.log('✅ referred_by column added to auth');
            } catch (e) {
                // Column might already exist
            }
            try {
                await conn.execute(`ALTER TABLE auth ADD COLUMN refers JSON DEFAULT NULL`);
                console.log('✅ refers column added to auth');
            } catch (e) {
                // Column might already exist
            }
            try {
                await conn.execute(`ALTER TABLE auth ADD COLUMN referral_balance DECIMAL(15, 2) DEFAULT 0.00`);
                console.log('✅ referral_balance column added to auth');
            } catch (e) {
                // Column might already exist
            }
            try {
                await conn.execute(`
                    CREATE TABLE IF NOT EXISTS withdrawals (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id VARCHAR(50) NOT NULL,
                        amount DECIMAL(15, 2) NOT NULL,
                        full_name VARCHAR(100) NOT NULL,
                        bank_name VARCHAR(100) NOT NULL,
                        account_number VARCHAR(100) NOT NULL,
                        status ENUM('pending', 'done') DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                `);
                console.log('✅ withdrawals table ready');
            } catch (e) {
                console.error('❌ Failed to create withdrawals table', e.message);
            }

        } catch (e) {
            console.error('❌ Failed to create chat_messages table', e.message);
        }
        conn.release();
    })
    .catch(err => {
        console.error('❌ DB CONNECTION ERROR:', err.message);
    });

export default pool;
