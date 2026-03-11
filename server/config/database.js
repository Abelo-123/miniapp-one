/**
 * Database Configuration — MySQL2 Connection Pool
 */
import mysql from 'mysql2/promise';
import 'dotenv/config';

// DEBUG: Log the connection config (masking sensitive bits)
console.log('[db-init] Attempting connection with:');
console.log(`  - Host: ${process.env.DB_HOST || 'localhost'}`);
console.log(`  - User: ${process.env.DB_USER || 'root'}`);
console.log(`  - DB:   ${process.env.DB_NAME || 'paxyo'}`);
console.log(`  - Port: ${process.env.DB_PORT || 3306}`);

const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'paxyocom_paxyov3',
    port: parseInt(process.env.DB_PORT || '3306'),
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    connectTimeout: 5000, // Speed up debugging — fail in 5s instead of 30s
});

// Test the connection immediately on start
pool.getConnection()
    .then(conn => {
        console.log('✅ [db-init] Database connection SUCCESS!');
        conn.release();
    })
    .catch(err => {
        console.error('❌ [db-init] Database connection FAILED:');
        console.error(`   Error Code: ${err.code}`);
        console.error(`   Message:    ${err.message}`);
        
        if (err.code === 'ETIMEDOUT') {
            console.error('   👉 Diagnosis: Firewall blocking connection on port 3306.');
        } else if (err.code === 'ER_ACCESS_DENIED_ERROR') {
            console.error('   👉 Diagnosis: Wrong DB_USER or DB_PASS, or IP not whitelisted.');
        }
    });

export default pool;
