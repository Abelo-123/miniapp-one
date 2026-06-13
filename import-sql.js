/**
 * Database Import Tool
 * Usage: node import-sql.js < exported-sql-file.sql
 */
import fs from 'fs';
import path from 'path';
import dotenv from 'dotenv';
import mysql from 'mysql2/promise';

// Load .env from api directory
dotenv.config({ path: path.join(process.cwd(), 'api/.env') });

const SQL_FILE = process.argv[2];

if (!SQL_FILE) {
    console.log('Usage: node import-sql.js <sql-file.sql>');
    console.log('Example: node import-sql.js "paxyocom_paxyov3 (4).sql"');
    process.exit(1);
}

// MySQL credentials from api/.env
const DB_CONFIG = {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT || '3306', 10),
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    multipleStatements: true,
    ssl: {
        rejectUnauthorized: false // Required for secure connections to cloud providers like Aiven
    }
};


async function importSQL() {
    console.log('📂 Reading SQL file:', SQL_FILE);
    
    if (!fs.existsSync(SQL_FILE)) {
        console.error('❌ File not found:', SQL_FILE);
        process.exit(1);
    }
    
    const sql = fs.readFileSync(SQL_FILE, 'utf8');
    
    console.log('🔌 Connecting to Render MySQL...');
    console.log('   Host:', DB_CONFIG.host);
    console.log('   User:', DB_CONFIG.user);
    console.log('   DB:', DB_CONFIG.database);
    
    let connection;
    try {
        connection = await mysql.createConnection(DB_CONFIG);
        console.log('✅ Connected!');
        
        // Enable multiple statements on this specific connection if not already enabled
        console.log('📝 Executing SQL dump...');
        
        try {
            // Aiven enforces primary keys. We temporarily disable this requirement for the import session.
            await connection.query('SET SESSION sql_require_primary_key = 0;\n' + sql);
            console.log('✅ All statements executed successfully!');
        } catch (e) {
            console.error('❌ Error executing SQL:', e.message);
        }
        
    } catch (e) {
        console.error('❌ Connection failed:', e.message);
    } finally {
        if (connection) await connection.end();
    }
}

importSQL();