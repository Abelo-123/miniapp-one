<?php
/**
 * Simple SQL Import Tool for Render
 * Upload your .sql file and visit this page
 */

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

// Check if file uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['sql_file']['tmp_name'])) {
    $sql = file_get_contents($_FILES['sql_file']['tmp_name']);
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Split SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $count = 0;
        foreach ($statements as $statement) {
            if (stripos($statement, 'CREATE') !== false || 
                stripos($statement, 'INSERT') !== false ||
                stripos($statement, 'ALTER') !== false) {
                $pdo->exec($statement);
                $count++;
            }
        }
        
        echo "✅ Success! Executed $count statements.";
    } catch (PDOException $e) {
        echo "❌ Error: " . $e->getMessage();
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>SQL Import Tool</title>
</head>
<body>
    <h2>MySQL Import Tool</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="file" name="sql_file" accept=".sql" required>
        <button type="submit">Import SQL</button>
    </form>
</body>
</html>