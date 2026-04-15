/**
 * Database Import Tool
 * Usage: node import-sql.js < exported-sql-file.sql
 */
import fs from 'fs';
import mysql from 'mysql2/promise';

const SQL_FILE = process.argv[2];

if (!SQL_FILE) {
    console.log('Usage: node import-sql.js <sql-file.sql>');
    console.log('Example: node import-sql.js backup.sql');
    process.exit(1);
}

// Render MySQL credentials - UPDATE THESE
const DB_CONFIG = {
    host: '10.2.65.146', // Get from Render Dashboard → Your MySQL → Internal Host
    user: 'your-render-username',  // Get from Render
    password: 'your-render-password', // Get from Render  
    database: 'your-render-dbname',   // Get from Render
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
        
        // Split into individual statements
        const statements = sql.split(/;\s*$/m).filter(s => s.trim());
        
        console.log(`📝 Executing ${statements.length} statements...`);
        
        let successCount = 0;
        let errorCount = 0;
        
        for (const statement of statements) {
            const trimmed = statement.trim();
            if (!trimmed || trimmed.startsWith('--')) continue;
            
            try {
                await connection.query(trimmed);
                successCount++;
            } catch (e) {
                errorCount++;
                if (errorCount <= 5) {
                    console.error('❌ Error:', e.message.substring(0, 100));
                }
            }
        }
        
        console.log('✅ Completed!');
        console.log(`   Success: ${successCount}`);
        console.log(`   Errors: ${errorCount}`);
        
    } catch (e) {
        console.error('❌ Connection failed:', e.message);
    } finally {
        if (connection) await connection.end();
    }
}

importSQL();