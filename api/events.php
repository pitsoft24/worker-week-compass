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
    case 'create':
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $eventDate = $data['eventDate'] ?? null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, user_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $eventDate, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'eventId' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
        }
        break;
        
    case 'delete':
        $eventId = $data['eventId'] ?? 0;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ? AND user_id = ?");
            $stmt->execute([$eventId, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Datenbankfehler']);
        }
        break;
        
    case 'update':
        $eventId = $data['eventId'] ?? 0;
        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $eventDate = $data['eventDate'] ?? null;
        
        try {
            $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$title, $description, $eventDate, $eventId, $_SESSION['user_id']]);
            
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