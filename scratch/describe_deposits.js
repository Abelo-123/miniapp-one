import path from 'path';
import dotenv from 'dotenv';
import mysql from 'mysql2/promise';

dotenv.config({ path: path.join(process.cwd(), 'api/.env') });

const DB_CONFIG = {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT || '3306', 10),
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    ssl: { rejectUnauthorized: false }
};

async function checkDatabase() {
    console.log('🔌 Connecting to Aiven MySQL...');
    let connection;
    try {
        connection = await mysql.createConnection(DB_CONFIG);
        console.log('✅ Connected successfully!');
        
        const [columns] = await connection.query('DESCRIBE deposits');
        console.log(columns);
    } catch (e) {
        console.error('❌ Error:', e.message);
    } finally {
        if (connection) await connection.end();
    }
}

checkDatabase();
