/**
 * Database Configuration — MySQL2 Connection Pool
 */
import mysql from 'mysql2/promise';
import 'dotenv/config';

// When running ON the cPanel server, host is always localhost
const pool = mysql.createPool({
    host: 'localhost', 
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    port: 3306,
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
});

// TEST CONNECTION AND LOG ERRORS
pool.getConnection()
    .then(conn => {
        console.log('✅ DB Connected');
        conn.release();
    })
    .catch(err => {
        console.error('❌ DB CONNECTION ERROR:', err.message);
        console.error('   Check if user is assigned to DB in cPanel with All Privileges.');
    });

export default pool;
