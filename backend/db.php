<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
// Simple database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'paxyocom_paxyoV3';

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Escape strings
function db_escape($str) {
    global $conn;
    return mysqli_real_escape_string($conn, $str);
}

// ============================================
// SELECT - Get data from table
// ============================================
// db_select('auth', 'tg_id', 111, 'balance')           → 100.50
// db_select('auth', 'tg_id', 111, ['balance', 'name']) → ['balance' => 100.50, 'name' => 'John']
// db_select('auth', 'tg_id', 111, '*')                 → full row
// db_select('auth', 'tg_id', 111)                      → full row (default)

function db_select($table, $where_col, $where_val, $fields = '*') {
    global $conn;
    
    $where_val = db_escape($where_val);
    
    if (is_array($fields)) {
        $select = implode(', ', $fields);
    } else {
        $select = $fields;
    }
    
    $sql = "SELECT $select FROM $table WHERE $where_col = '$where_val' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if (!$result || mysqli_num_rows($result) === 0) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!is_array($fields) && $fields !== '*') {
        return $row[$fields] ?? null;
    }
    
    return $row;
}

// ============================================
// INSERT - Add new data
// ============================================
// db_insert('auth', ['tg_id' => 111, 'balance' => 50, 'name' => 'John'])
// Returns: insert ID or true on success, false on failure

function db_insert($table, $data) {
    global $conn;
    
    $columns = implode(', ', array_keys($data));
    $values = implode("', '", array_map('db_escape', array_values($data)));
    
    $sql = "INSERT INTO $table ($columns) VALUES ('$values')";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $id = mysqli_insert_id($conn);
        return $id > 0 ? $id : true;
    }
    return false;
}

// ============================================
// UPDATE - Modify existing data
// ============================================
// db_update('auth', 'tg_id', 111, ['balance' => 200])
// db_update('auth', 'tg_id', 111, ['balance' => 200, 'name' => 'Updated'])
// Returns: true on success, false on failure

function db_update($table, $where_col, $where_val, $data) {
    global $conn;
    
    $where_val = db_escape($where_val);
    
    $set_parts = [];
    foreach ($data as $key => $value) {
        $escaped_val = db_escape($value);
        $set_parts[] = "$key = '$escaped_val'";
    }
    $set_str = implode(', ', $set_parts);
    
    $sql = "UPDATE $table SET $set_str WHERE $where_col = '$where_val'";
    return mysqli_query($conn, $sql);
}

// ============================================
// DELETE - Remove data
// ============================================
// db_delete('auth', 'tg_id', 111)
// Returns: true on success, false on failure

function db_delete($table, $where_col, $where_val) {
    global $conn;
    
    $where_val = db_escape($where_val);
    
    $sql = "DELETE FROM $table WHERE $where_col = '$where_val'";
    return mysqli_query($conn, $sql);
}

// ============================================
// SHORTCUT: db() - All-in-one function
// ============================================
// db('select', 'auth', 'tg_id', 111, 'balance')
// db('insert', 'auth', ['tg_id' => 111, 'balance' => 50])
// db('update', 'auth', 'tg_id', 111, ['balance' => 200])
// db('delete', 'auth', 'tg_id', 111)

function db($action, $table, ...$args) {
    switch (strtolower($action)) {
        case 'select':
        case 'get':
            return db_select($table, $args[0], $args[1], $args[2] ?? '*');
        case 'insert':
        case 'add':
            return db_insert($table, $args[0]);
        case 'update':
        case 'set':
            return db_update($table, $args[0], $args[1], $args[2]);
        case 'delete':
        case 'remove':
            return db_delete($table, $args[0], $args[1]);
        default:
            return null;
    }
}

// ============================================
// RAW QUERY - For complex queries
// ============================================
// db_query("SELECT * FROM auth WHERE balance > 100")
// Returns: array of rows

function db_query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    
    if (!$result) return null;
    
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}
?>
