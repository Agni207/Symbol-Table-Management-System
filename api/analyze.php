<?php
require_once 'config.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('POST only');
}

$data = getInput();
if (empty($data['source_code']) || empty($data['program_id'])) {
    sendError('source_code and program_id required');
}

$source = $data['source_code'];
$programId = (int)$data['program_id'];
$language = $data['language'] ?? 'C';

// Get or create global scope
$scopeResult = $conn->query("SELECT id FROM symbol_tables WHERE program_id=$programId AND scope_name='global'");
if ($scopeResult->num_rows === 0) {
    $conn->query("INSERT INTO symbol_tables (program_id, scope_name, scope_level) VALUES ($programId, 'global', 0)");
    $globalScopeId = $conn->insert_id;
} else {
    $globalScopeId = $scopeResult->fetch_assoc()['id'];
}

$lines = explode("\n", $source);
$symbols = [];
$errors = [];
$scopeStack = [$globalScopeId];
$currentScopeId = $globalScopeId;
$braceDepth = 0;
$scopes = ['global' => $globalScopeId];

// C/C++ data types
$dataTypes = ['int','float','double','char','long','short','unsigned','void','bool','string','auto','size_t','uint8_t','uint16_t','uint32_t','int8_t','int16_t','int32_t','String'];

// Patterns
$funcPattern = '/\b(' . implode('|', $dataTypes) . ')\s+\*?(\w+)\s*\(([^)]*)\)\s*[\{;]/';
$varPattern = '/^\s*(' . implode('|', $dataTypes) . ')\s+\*?(\w+)(?:\s*=\s*([^;,\n]+?))?;/';
$arrayPattern = '/^\s*(' . implode('|', $dataTypes) . ')\s+(\w+)\s*\[(\d*)\]\s*(?:=\s*([^;]+?))?;/';
$constPattern = '/^\s*(?:const|#define)\s+(' . implode('|', $dataTypes) . ')?\s*(\w+)\s*(?:=\s*([^;]+?))?;?/';

$addedSymbols = [];

function safeInsertSymbol($conn, $tableId, $name, $type, $dataType, $value, $scope, $isInit, $lineNo, &$addedSymbols) {
    $key = $tableId . ':' . $name;
    if (isset($addedSymbols[$key])) return null;
    $addedSymbols[$key] = true;
    $stmt = $conn->prepare("INSERT IGNORE INTO symbols (table_id, name, type, data_type, value, scope, is_initialized, is_declared, line_number) VALUES (?,?,?,?,?,?,?,1,?)");
    $stmt->bind_param('isssssii', $tableId, $name, $type, $dataType, $value, $scope, $isInit, $lineNo);
    $stmt->execute();
    return $conn->insert_id ?: null;
}

foreach ($lines as $lineIdx => $line) {
    $lineNo = $lineIdx + 1;
    $trimmed = trim($line);
    if (empty($trimmed) || strpos($trimmed, '//') === 0 || strpos($trimmed, '#include') === 0) continue;

    // Count braces for scope tracking
    $openBraces = substr_count($line, '{');
    $closeBraces = substr_count($line, '}');

    // Function detection
    if (preg_match($funcPattern, $line, $m)) {
        $retType = $m[1]; $funcName = $m[2]; $params = $m[3];
        $scopeName = $funcName;
        $exists = $conn->query("SELECT id FROM symbol_tables WHERE program_id=$programId AND scope_name='$scopeName'");
        if ($exists->num_rows === 0) {
            $conn->query("INSERT INTO symbol_tables (program_id, scope_name, scope_level, parent_scope_id) VALUES ($programId, '$scopeName', 1, $globalScopeId)");
            $funcScopeId = $conn->insert_id;
            $scopes[$scopeName] = $funcScopeId;
        } else {
            $funcScopeId = $exists->fetch_assoc()['id'];
        }
        safeInsertSymbol($conn, $globalScopeId, $funcName, 'function', $retType, null, 'global', 0, $lineNo, $addedSymbols);
        // Parse parameters
        if (!empty(trim($params))) {
            foreach (explode(',', $params) as $param) {
                $param = trim($param);
                if (preg_match('/(' . implode('|', $dataTypes) . ')\s+\*?(\w+)/', $param, $pm)) {
                    safeInsertSymbol($conn, $funcScopeId, $pm[2], 'parameter', $pm[1], null, 'parameter', 0, $lineNo, $addedSymbols);
                }
            }
        }
        $currentScopeId = $funcScopeId;
    }

    // Variable detection
    if (preg_match($varPattern, $line, $m)) {
        $dtype = $m[1]; $varName = $m[2]; $val = isset($m[3]) ? trim($m[3]) : null;
        $isInit = $val !== null ? 1 : 0;
        $scopeType = ($currentScopeId === $globalScopeId) ? 'global' : 'local';
        safeInsertSymbol($conn, $currentScopeId, $varName, 'variable', $dtype, $val, $scopeType, $isInit, $lineNo, $addedSymbols);
    }

    // Array detection
    if (preg_match($arrayPattern, $line, $m)) {
        $dtype = $m[1]; $arrName = $m[2];
        $scopeType = ($currentScopeId === $globalScopeId) ? 'global' : 'local';
        safeInsertSymbol($conn, $currentScopeId, $arrName, 'array', $dtype, null, $scopeType, 1, $lineNo, $addedSymbols);
    }

    // Brace depth
    $braceDepth += $openBraces - $closeBraces;
    if ($closeBraces > 0 && $braceDepth <= 0) {
        $currentScopeId = $globalScopeId;
        $braceDepth = 0;
    }
}

// Count inserted symbols
$countResult = $conn->query("SELECT COUNT(*) as c FROM symbols s JOIN symbol_tables st ON s.table_id = st.id WHERE st.program_id = $programId");
$totalSymbols = $countResult->fetch_assoc()['c'];

sendResponse([
    'message' => 'Analysis complete',
    'program_id' => $programId,
    'symbols_found' => count($addedSymbols),
    'total_symbols' => $totalSymbols,
    'scopes_created' => count($scopes)
]);
$conn->close();
?>
