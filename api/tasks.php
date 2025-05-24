<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Nicht autorisiert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

switch ($action) {
    case 'toggle':
        $taskId = $data['taskId'] ?? 0;
        $completed = $data['completed'] ?? false;
        
        try {
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
            $status = $completed ? 'completed' : 'pending';
            $stmt->execute([$status, $taskId, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
        }
        break;
        
    case 'create':
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $dueDate = $data['dueDate'] ?? null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, due_date, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $dueDate, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'taskId' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
        }
        break;
        
    case 'delete':
        $taskId = $data['taskId'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$taskId, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ungültige Aktion']);
} 