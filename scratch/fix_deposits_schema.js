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

async function fixSchema() {
    console.log('🔌 Connecting to Aiven MySQL...');
    let connection;
    try {
        connection = await mysql.createConnection(DB_CONFIG);
        console.log('✅ Connected successfully!');
        
        console.log('🛠️ Altering deposits table to make reference_id nullable and set default...');
        await connection.query("ALTER TABLE deposits MODIFY reference_id VARCHAR(255) NULL DEFAULT ''");
        console.log('✅ Success! The table schema has been updated.');
    } catch (e) {
        console.error('❌ Error fixing schema:', e.message);
    } finally {
        if (connection) await connection.end();
    }
}

fixSchema();
