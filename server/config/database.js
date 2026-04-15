/**
 * Database Configuration — MySQL2 Connection Pool
 * Using Render MySQL - Update credentials below
 */
import mysql from 'mysql2/promise';

// IMPORTANT: Update these with your Render MySQL credentials from Dashboard
const pool = mysql.createPool({
    host: 'UPDATE_WITH_RENDER_INTERNAL_HOST',  // e.g., 10.22.153.202
    user: 'UPDATE_WITH_RENDER_USERNAME',         // e.g., paxyo
    password: 'UPDATE_WITH_RENDER_PASSWORD',     // Get from Render Dashboard
    database: 'UPDATE_WITH_RENDER_DB_NAME',       // e.g., paxyodb
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
        console.log('✅ DB Connected to Render MySQL');
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
        } catch (e) {
            console.error('❌ Failed to create chat_messages table', e.message);
        }
        conn.release();
    })
    .catch(err => {
        console.error('❌ DB CONNECTION ERROR:', err.message);
    });

export default pool;