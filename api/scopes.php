<?php
require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT st.*, 
                (SELECT COUNT(*) FROM symbols WHERE table_id = st.id) as symbol_count
                FROM symbol_tables st WHERE st.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) sendResponse($row);
            else sendError('Scope not found', 404);
        } elseif ($programId) {
            $stmt = $conn->prepare("SELECT st.*, 
                (SELECT COUNT(*) FROM symbols WHERE table_id = st.id) as symbol_count
                FROM symbol_tables st WHERE st.program_id = ? ORDER BY st.scope_level, st.id");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
            $result = $stmt->get_result();
            $scopes = [];
            while ($row = $result->fetch_assoc()) $scopes[] = $row;
            sendResponse($scopes);
        } else {
            $result = $conn->query("SELECT st.*, p.program_name,
                (SELECT COUNT(*) FROM symbols WHERE table_id = st.id) as symbol_count
                FROM symbol_tables st JOIN programs p ON st.program_id = p.id ORDER BY st.program_id, st.scope_level");
            $scopes = [];
            while ($row = $result->fetch_assoc()) $scopes[] = $row;
            sendResponse($scopes);
        }
        break;

    case 'POST':
        $data = getInput();
        if (empty($data['program_id']) || empty($data['scope_name'])) sendError('program_id and scope_name required');
        $stmt = $conn->prepare("INSERT INTO symbol_tables (program_id, scope_name, scope_level, parent_scope_id, description) VALUES (?,?,?,?,?)");
        $parentId = $data['parent_scope_id'] ?? null;
        $level = $data['scope_level'] ?? 1;
        $stmt->bind_param('issis', $data['program_id'], $data['scope_name'], $level, $parentId, $data['description'] ?? '');
        if ($stmt->execute()) sendResponse(['message' => 'Scope created', 'id' => $conn->insert_id], 201);
        else sendError('Duplicate scope name in this program or DB error: ' . $conn->error);
        break;

    case 'PUT':
        if (!$id) sendError('ID required');
        $data = getInput();
        $stmt = $conn->prepare("UPDATE symbol_tables SET scope_name=?, scope_level=?, description=? WHERE id=?");
        $stmt->bind_param('sisi', $data['scope_name'], $data['scope_level'] ?? 0, $data['description'] ?? '', $id);
        if ($stmt->execute()) sendResponse(['message' => 'Scope updated']);
        else sendError('Update failed: ' . $conn->error);
        break;

    case 'DELETE':
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare("DELETE FROM symbol_tables WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) sendResponse(['message' => 'Scope deleted']);
        else sendError('Not found', 404);
        break;
}
$conn->close();
?>
