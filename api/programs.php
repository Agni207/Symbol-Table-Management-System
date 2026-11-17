<?php
require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT p.*, 
                (SELECT COUNT(*) FROM symbol_tables WHERE program_id = p.id) as scope_count,
                (SELECT COUNT(*) FROM symbols s JOIN symbol_tables st ON s.table_id = st.id WHERE st.program_id = p.id) as symbol_count,
                (SELECT COUNT(*) FROM errors WHERE program_id = p.id) as error_count
                FROM programs p WHERE p.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                sendResponse($row);
            } else {
                sendError('Program not found', 404);
            }
        } else {
            $search = isset($_GET['search']) ? '%' . $conn->real_escape_string($_GET['search']) . '%' : '%';
            $stmt = $conn->prepare("SELECT p.*, 
                (SELECT COUNT(*) FROM symbol_tables WHERE program_id = p.id) as scope_count,
                (SELECT COUNT(*) FROM symbols s JOIN symbol_tables st ON s.table_id = st.id WHERE st.program_id = p.id) as symbol_count,
                (SELECT COUNT(*) FROM errors WHERE program_id = p.id AND is_resolved = 0) as active_errors
                FROM programs p
                WHERE p.program_name LIKE ? OR p.language LIKE ? OR p.description LIKE ?
                ORDER BY p.created_at DESC");
            $stmt->bind_param('sss', $search, $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();
            $programs = [];
            while ($row = $result->fetch_assoc()) {
                $programs[] = $row;
            }
            sendResponse($programs);
        }
        break;

    case 'POST':
        $data = getInput();
        if (empty($data['program_name']) || empty($data['language'])) {
            sendError('program_name and language are required');
        }
        $stmt = $conn->prepare("INSERT INTO programs (program_name, language, source_code, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $data['program_name'], $data['language'], $data['source_code'] ?? '', $data['description'] ?? '');
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            // Auto-create global scope
            $scopeStmt = $conn->prepare("INSERT INTO symbol_tables (program_id, scope_name, scope_level, description) VALUES (?, 'global', 0, 'Global scope')");
            $scopeStmt->bind_param('i', $newId);
            $scopeStmt->execute();
            sendResponse(['message' => 'Program created', 'id' => $newId, 'scope_id' => $conn->insert_id], 201);
        } else {
            sendError('Failed to create program: ' . $conn->error);
        }
        break;

    case 'PUT':
        if (!$id) sendError('ID required for update');
        $data = getInput();
        $fields = [];
        $types = '';
        $values = [];
        if (isset($data['program_name'])) { $fields[] = 'program_name=?'; $types .= 's'; $values[] = $data['program_name']; }
        if (isset($data['language']))     { $fields[] = 'language=?';     $types .= 's'; $values[] = $data['language']; }
        if (isset($data['source_code']))  { $fields[] = 'source_code=?';  $types .= 's'; $values[] = $data['source_code']; }
        if (isset($data['description']))  { $fields[] = 'description=?';  $types .= 's'; $values[] = $data['description']; }
        if (empty($fields)) sendError('No fields to update');
        $types .= 'i';
        $values[] = $id;
        $stmt = $conn->prepare("UPDATE programs SET " . implode(',', $fields) . " WHERE id=?");
        $stmt->bind_param($types, ...$values);
        if ($stmt->execute()) {
            sendResponse(['message' => 'Program updated']);
        } else {
            sendError('Failed to update: ' . $conn->error);
        }
        break;

    case 'DELETE':
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare("DELETE FROM programs WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            sendResponse(['message' => 'Program deleted']);
        } else {
            sendError('Program not found or delete failed', 404);
        }
        break;
}
$conn->close();
?>
