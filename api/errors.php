<?php
require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT e.*, p.program_name FROM errors e JOIN programs p ON e.program_id = p.id WHERE e.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($row = $stmt->get_result()->fetch_assoc()) sendResponse($row);
            else sendError('Error not found', 404);
        } elseif ($programId) {
            $stmt = $conn->prepare("SELECT * FROM errors WHERE program_id = ? ORDER BY is_resolved, created_at DESC");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
            $errors = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $errors[] = $row;
            sendResponse($errors);
        } else {
            $result = $conn->query("SELECT e.*, p.program_name FROM errors e JOIN programs p ON e.program_id = p.id ORDER BY e.is_resolved, e.created_at DESC");
            $errors = [];
            while ($row = $result->fetch_assoc()) $errors[] = $row;
            sendResponse($errors);
        }
        break;

    case 'POST':
        $data = getInput();
        if (empty($data['program_id']) || empty($data['error_type']) || empty($data['message'])) sendError('program_id, error_type, message required');
        $stmt = $conn->prepare("INSERT INTO errors (program_id, error_type, symbol_name, line_number, message) VALUES (?,?,?,?,?)");
        $lineNo = !empty($data['line_number']) ? (int)$data['line_number'] : null;
        $stmt->bind_param('issss', $data['program_id'], $data['error_type'], $data['symbol_name'] ?? null, $lineNo, $data['message']);
        if ($stmt->execute()) sendResponse(['message' => 'Error logged', 'id' => $conn->insert_id], 201);
        else sendError('Failed: ' . $conn->error);
        break;

    case 'PUT':
        if (!$id) sendError('ID required');
        $data = getInput();
        if (isset($data['is_resolved'])) {
            $stmt = $conn->prepare("UPDATE errors SET is_resolved=? WHERE id=?");
            $v = (int)$data['is_resolved'];
            $stmt->bind_param('ii', $v, $id);
        } else {
            $stmt = $conn->prepare("UPDATE errors SET error_type=?, symbol_name=?, line_number=?, message=?, is_resolved=? WHERE id=?");
            $lineNo = !empty($data['line_number']) ? (int)$data['line_number'] : null;
            $isResolved = (int)($data['is_resolved'] ?? 0);
            $stmt->bind_param('ssisii', $data['error_type'], $data['symbol_name'] ?? null, $lineNo, $data['message'], $isResolved, $id);
        }
        if ($stmt->execute()) sendResponse(['message' => 'Error updated']);
        else sendError('Update failed');
        break;

    case 'DELETE':
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare("DELETE FROM errors WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) sendResponse(['message' => 'Error deleted']);
        else sendError('Not found', 404);
        break;
}
$conn->close();
?>
