<?php
require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$symbolId = isset($_GET['symbol_id']) ? (int)$_GET['symbol_id'] : null;

switch ($method) {
    case 'GET':
        if ($symbolId) {
            $stmt = $conn->prepare("SELECT * FROM symbol_references WHERE symbol_id = ? ORDER BY line_number");
            $stmt->bind_param('i', $symbolId);
            $stmt->execute();
            $refs = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $refs[] = $row;
            sendResponse($refs);
        } else {
            $result = $conn->query("SELECT sr.*, s.name as symbol_name FROM symbol_references sr JOIN symbols s ON sr.symbol_id = s.id ORDER BY sr.symbol_id, sr.line_number LIMIT 200");
            $refs = [];
            while ($row = $result->fetch_assoc()) $refs[] = $row;
            sendResponse($refs);
        }
        break;

    case 'POST':
        $data = getInput();
        if (empty($data['symbol_id']) || empty($data['line_number'])) sendError('symbol_id and line_number required');
        $stmt = $conn->prepare("INSERT INTO symbol_references (symbol_id, line_number, reference_type, context) VALUES (?,?,?,?)");
        $stmt->bind_param('iiss', $data['symbol_id'], $data['line_number'], $data['reference_type'] ?? 'usage', $data['context'] ?? null);
        if ($stmt->execute()) sendResponse(['message' => 'Reference added', 'id' => $conn->insert_id], 201);
        else sendError('Failed: ' . $conn->error);
        break;

    case 'DELETE':
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare("DELETE FROM symbol_references WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) sendResponse(['message' => 'Reference deleted']);
        else sendError('Not found', 404);
        break;
}
$conn->close();
?>
