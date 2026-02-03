<?php
/**
 * Todo API - Simple CRUD backend
 * Uses SQLite for storage (no MySQL setup needed)
 */

// CORS headers for frontend access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database setup
$dbPath = __DIR__ . '/todos.db';
$db = new SQLite3($dbPath);

// Create table if not exists
$db->exec('
    CREATE TABLE IF NOT EXISTS todos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        user_name TEXT DEFAULT NULL,
        text TEXT NOT NULL,
        completed INTEGER DEFAULT 0,
        completed_by INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Migration: Ensure columns exist
@$db->exec('ALTER TABLE todos ADD COLUMN completed_by INTEGER DEFAULT NULL');
@$db->exec('ALTER TABLE todos ADD COLUMN user_name TEXT DEFAULT NULL');

// Get request parameters
$method = $_SERVER['REQUEST_METHOD'];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$todoId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($userId === null && $method !== 'OPTIONS') {
    http_response_code(400);
    echo json_encode(['error' => 'user_id is required']);
    exit();
}

switch ($method) {
    case 'GET':
        $stmt = $db->prepare('SELECT * FROM todos ORDER BY created_at DESC');
        $result = $stmt->execute();
        
        $todos = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $todos[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'],
                'user_name' => $row['user_name'],
                'text' => $row['text'],
                'completed' => (bool)$row['completed'],
                'completed_by' => $row['completed_by'],
                'created_at' => $row['created_at']
            ];
        }
        echo json_encode($todos);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['text']) || trim($input['text']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'text is required']);
            exit();
        }
        
        $stmt = $db->prepare('INSERT INTO todos (user_id, user_name, text) VALUES (:user_id, :user_name, :text)');
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':user_name', isset($input['user_name']) ? $input['user_name'] : null, SQLITE3_TEXT);
        $stmt->bindValue(':text', trim($input['text']), SQLITE3_TEXT);
        $stmt->execute();
        
        $newId = $db->lastInsertRowID();
        $stmt = $db->prepare('SELECT * FROM todos WHERE id = :id');
        $stmt->bindValue(':id', $newId, SQLITE3_INTEGER);
        $todo = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        
        echo json_encode($todo);
        break;

    case 'PUT':
        if ($todoId === null) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit();
        }
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updates = [];
        $params = [];
        
        if (isset($input['text'])) {
            $updates[] = 'text = :text';
            $params[':text'] = trim($input['text']);
        }
        
        if (isset($input['completed'])) {
            $updates[] = 'completed = :completed';
            $params[':completed'] = $input['completed'] ? 1 : 0;
            if ($input['completed']) {
                $updates[] = 'completed_by = :completed_by';
                $params[':completed_by'] = $userId;
            } else {
                $updates[] = 'completed_by = NULL';
            }
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No fields to update']);
            exit();
        }
        
        $sql = 'UPDATE todos SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $todoId, SQLITE3_INTEGER);
        foreach ($params as $key => $value) $stmt->bindValue($key, $value);
        $stmt->execute();
        
        $todo = $db->prepare('SELECT * FROM todos WHERE id = :id');
        $todo->bindValue(':id', $todoId, SQLITE3_INTEGER);
        echo json_encode($todo->execute()->fetchArray(SQLITE3_ASSOC));
        break;

    case 'DELETE':
        $stmt = $db->prepare('DELETE FROM todos WHERE id = :id');
        $stmt->bindValue(':id', $todoId, SQLITE3_INTEGER);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;
}
$db->close();
