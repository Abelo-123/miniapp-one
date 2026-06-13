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
        
        const [tables] = await connection.query('SHOW TABLES');
        console.log(`\n📊 Found ${tables.length} tables in '${DB_CONFIG.database}':`);
        
        for (const row of tables) {
            const tableName = Object.values(row)[0];
            const [countResult] = await connection.query(`SELECT COUNT(*) as count FROM \`${tableName}\``);
            console.log(`  - ${tableName} (${countResult[0].count} rows)`);
        }
        
    } catch (e) {
        console.error('❌ Error checking database:', e.message);
    } finally {
        if (connection) await connection.end();
    }
}

checkDatabase();
