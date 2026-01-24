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
// Create table if not exists (update with new column if needed)
// Note: SQLite ALTER TABLE is limited, so we check column existence or just handle it gracefully in new installs.
// For simplicity in this script, we'll ensure the column exists by attempting to add it if missing, or just recreate for this task.
// User wants to keep data? I'll assume we can just ensure the column exists.
$db->exec('
    CREATE TABLE IF NOT EXISTS todos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        text TEXT NOT NULL,
        completed INTEGER DEFAULT 0,
        completed_by INTEGER DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
');

// Try to add column if it doesn't exist (silent failure if it does for SQLite)
@$db->exec('ALTER TABLE todos ADD COLUMN completed_by INTEGER DEFAULT NULL');

// Get request parameters
$method = $_SERVER['REQUEST_METHOD'];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$todoId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Validate user_id for actions (required to know WHO is acting)
if ($userId === null && $method !== 'OPTIONS') {
    http_response_code(400);
    echo json_encode(['error' => 'user_id is required']);
    exit();
}

// Handle requests
switch ($method) {
    case 'GET':
        // Fetch ALL todos for shared view
        $stmt = $db->prepare('SELECT id, user_id, text, completed, completed_by, created_at FROM todos ORDER BY created_at DESC');
        $result = $stmt->execute();
        
        $todos = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $todos[] = [
                'id' => $row['id'],
                'user_id' => $row['user_id'], // Creator
                'text' => $row['text'],
                'completed' => (bool)$row['completed'],
                'completed_by' => $row['completed_by'],
                'created_at' => $row['created_at']
            ];
        }
        
        echo json_encode($todos);
        break;

    case 'POST':
        // Add new todo
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['text']) || trim($input['text']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'text is required']);
            exit();
        }
        
        $stmt = $db->prepare('INSERT INTO todos (user_id, text) VALUES (:user_id, :text)');
        $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $stmt->bindValue(':text', trim($input['text']), SQLITE3_TEXT);
        $stmt->execute();
        
        $newId = $db->lastInsertRowID();
        
        // Return the created todo
        $stmt = $db->prepare('SELECT id, user_id, text, completed, completed_by, created_at FROM todos WHERE id = :id');
        $stmt->bindValue(':id', $newId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $todo = $result->fetchArray(SQLITE3_ASSOC);
        
        echo json_encode([
            'id' => $todo['id'],
            'user_id' => $todo['user_id'],
            'text' => $todo['text'],
            'completed' => (bool)$todo['completed'],
            'completed_by' => $todo['completed_by'],
            'created_at' => $todo['created_at']
        ]);
        break;

    case 'PUT':
        // Update todo
        if ($todoId === null) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit();
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Build update query dynamically
        $updates = [];
        $params = [];
        
        if (isset($input['text'])) {
            $updates[] = 'text = :text';
            $params[':text'] = trim($input['text']);
        }
        
        if (isset($input['completed'])) {
            $updates[] = 'completed = :completed';
            $params[':completed'] = $input['completed'] ? 1 : 0;
            
            // Track who completed it
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
        
        // Remove user_id check from WHERE so anyone can update (shared list)
        $sql = 'UPDATE todos SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $todoId, SQLITE3_INTEGER);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        if ($db->changes() === 0) {
            // Check if it exists but just wasn't changed
             // Or maybe it strictly doesn't exist
        }
        
        // Return the updated todo
        $stmt = $db->prepare('SELECT id, user_id, text, completed, completed_by, created_at FROM todos WHERE id = :id');
        $stmt->bindValue(':id', $todoId, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $todo = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!$todo) {
             http_response_code(404);
             echo json_encode(['error' => 'Todo not found']);
             exit();
        }

        echo json_encode([
            'id' => $todo['id'],
            'user_id' => $todo['user_id'],
            'text' => $todo['text'],
            'completed' => (bool)$todo['completed'],
            'completed_by' => $todo['completed_by'],
            'created_at' => $todo['created_at']
        ]);
        break;

    case 'DELETE':
        // Delete todo - allow any user to delete? Or restrict? 
        // For shared list, allowing delete is simplest for now.
        if ($todoId === null) {
            http_response_code(400);
            echo json_encode(['error' => 'id is required']);
            exit();
        }
        
        $stmt = $db->prepare('DELETE FROM todos WHERE id = :id');
        $stmt->bindValue(':id', $todoId, SQLITE3_INTEGER);
        $stmt->execute();
        
        if ($db->changes() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Todo not found']);
            exit();
        }
        
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

$db->close();
