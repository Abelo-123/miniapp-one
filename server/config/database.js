/**
 * Database Configuration — MySQL2 Connection Pool
 *
 * Provides a shared connection pool using mysql2/promise.
 * All route handlers use `pool.execute()` for parameterised queries.
 */
import mysql from 'mysql2/promise';
import 'dotenv/config';

const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    database: process.env.DB_NAME || 'paxyocom_paxyov3',
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
});

export default pool;
