<?php
require_once 'config.php';

$conn = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : null;
$programId = isset($_GET['program_id']) ? (int)$_GET['program_id'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT s.*, st.scope_name, p.program_name 
                FROM symbols s 
                JOIN symbol_tables st ON s.table_id = st.id 
                JOIN programs p ON st.program_id = p.id 
                WHERE s.id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                // Get references
                $refStmt = $conn->prepare("SELECT * FROM symbol_references WHERE symbol_id = ? ORDER BY line_number");
                $refStmt->bind_param('i', $id);
                $refStmt->execute();
                $refs = [];
                $refResult = $refStmt->get_result();
                while ($r = $refResult->fetch_assoc()) $refs[] = $r;
                $row['references'] = $refs;
                sendResponse($row);
            } else sendError('Symbol not found', 404);
        } elseif ($tableId) {
            $stmt = $conn->prepare("SELECT s.*, 
                (SELECT COUNT(*) FROM symbol_references WHERE symbol_id = s.id) as ref_count
                FROM symbols s WHERE s.table_id = ? ORDER BY s.type, s.name");
            $stmt->bind_param('i', $tableId);
            $stmt->execute();
            $symbols = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $symbols[] = $row;
            sendResponse($symbols);
        } elseif ($programId) {
            $stmt = $conn->prepare("SELECT s.*, st.scope_name, 
                (SELECT COUNT(*) FROM symbol_references WHERE symbol_id = s.id) as ref_count
                FROM symbols s 
                JOIN symbol_tables st ON s.table_id = st.id 
                WHERE st.program_id = ? ORDER BY st.scope_level, s.type, s.name");
            $stmt->bind_param('i', $programId);
            $stmt->execute();
            $symbols = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $symbols[] = $row;
            sendResponse($symbols);
        } elseif ($search) {
            $like = '%' . $conn->real_escape_string($search) . '%';
            $stmt = $conn->prepare("SELECT s.*, st.scope_name, p.program_name,
                (SELECT COUNT(*) FROM symbol_references WHERE symbol_id = s.id) as ref_count
                FROM symbols s 
                JOIN symbol_tables st ON s.table_id = st.id
                JOIN programs p ON st.program_id = p.id
                WHERE s.name LIKE ? OR s.data_type LIKE ? OR s.type LIKE ?
                ORDER BY s.name LIMIT 100");
            $stmt->bind_param('sss', $like, $like, $like);
            $stmt->execute();
            $symbols = [];
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) $symbols[] = $row;
            sendResponse($symbols);
        } else {
            $result = $conn->query("SELECT s.*, st.scope_name, p.program_name,
                (SELECT COUNT(*) FROM symbol_references WHERE symbol_id = s.id) as ref_count
                FROM symbols s 
                JOIN symbol_tables st ON s.table_id = st.id
                JOIN programs p ON st.program_id = p.id
                ORDER BY p.id, st.scope_level, s.type, s.name LIMIT 200");
            $symbols = [];
            while ($row = $result->fetch_assoc()) $symbols[] = $row;
            sendResponse($symbols);
        }
        break;

    case 'POST':
        $data = getInput();
        if (empty($data['table_id']) || empty($data['name']) || empty($data['data_type'])) {
            sendError('table_id, name, and data_type are required');
        }
        $stmt = $conn->prepare("INSERT INTO symbols (table_id, name, type, data_type, value, scope, is_initialized, is_declared, line_number, memory_address, size_bytes) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $isInit = isset($data['is_initialized']) ? (int)$data['is_initialized'] : 0;
        $isDecl = isset($data['is_declared']) ? (int)$data['is_declared'] : 1;
        $lineNo = !empty($data['line_number']) ? (int)$data['line_number'] : null;
        $sizeBytes = !empty($data['size_bytes']) ? (int)$data['size_bytes'] : null;
        $stmt->bind_param('isssssiiisi',
            $data['table_id'], $data['name'], $data['type'] ?? 'variable',
            $data['data_type'], $data['value'] ?? null, $data['scope'] ?? 'local',
            $isInit, $isDecl, $lineNo, $data['memory_address'] ?? null, $sizeBytes
        );
        if ($stmt->execute()) sendResponse(['message' => 'Symbol added', 'id' => $conn->insert_id], 201);
        else sendError('Symbol already exists in this scope or DB error: ' . $conn->error);
        break;

    case 'PUT':
        if (!$id) sendError('ID required');
        $data = getInput();
        $stmt = $conn->prepare("UPDATE symbols SET name=?, type=?, data_type=?, value=?, scope=?, is_initialized=?, is_declared=?, line_number=?, memory_address=?, size_bytes=? WHERE id=?");
        $isInit = isset($data['is_initialized']) ? (int)$data['is_initialized'] : 0;
        $isDecl = isset($data['is_declared']) ? (int)$data['is_declared'] : 1;
        $lineNo = !empty($data['line_number']) ? (int)$data['line_number'] : null;
        $sizeBytes = !empty($data['size_bytes']) ? (int)$data['size_bytes'] : null;
        $stmt->bind_param('sssssiisii',
            $data['name'], $data['type'], $data['data_type'],
            $data['value'] ?? null, $data['scope'],
            $isInit, $isDecl, $lineNo, $data['memory_address'] ?? null, $sizeBytes, $id
        );
        if ($stmt->execute()) sendResponse(['message' => 'Symbol updated']);
        else sendError('Update failed: ' . $conn->error);
        break;

    case 'DELETE':
        if (!$id) sendError('ID required');
        $stmt = $conn->prepare("DELETE FROM symbols WHERE id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) sendResponse(['message' => 'Symbol deleted']);
        else sendError('Not found', 404);
        break;
}
$conn->close();
?>
