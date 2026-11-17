<?php
require_once 'config.php';

$conn = getDBConnection();

$stats = [];

// Total counts
$stats['total_programs'] = $conn->query("SELECT COUNT(*) as c FROM programs")->fetch_assoc()['c'];
$stats['total_scopes'] = $conn->query("SELECT COUNT(*) as c FROM symbol_tables")->fetch_assoc()['c'];
$stats['total_symbols'] = $conn->query("SELECT COUNT(*) as c FROM symbols")->fetch_assoc()['c'];
$stats['total_errors'] = $conn->query("SELECT COUNT(*) as c FROM errors WHERE is_resolved=0")->fetch_assoc()['c'];
$stats['resolved_errors'] = $conn->query("SELECT COUNT(*) as c FROM errors WHERE is_resolved=1")->fetch_assoc()['c'];

// Symbol type breakdown
$result = $conn->query("SELECT type, COUNT(*) as count FROM symbols GROUP BY type ORDER BY count DESC");
$typeBreakdown = [];
while ($row = $result->fetch_assoc()) $typeBreakdown[] = $row;
$stats['symbol_type_breakdown'] = $typeBreakdown;

// Language breakdown
$result = $conn->query("SELECT language, COUNT(*) as count FROM programs GROUP BY language ORDER BY count DESC");
$langBreakdown = [];
while ($row = $result->fetch_assoc()) $langBreakdown[] = $row;
$stats['language_breakdown'] = $langBreakdown;

// Data type distribution
$result = $conn->query("SELECT data_type, COUNT(*) as count FROM symbols GROUP BY data_type ORDER BY count DESC LIMIT 10");
$dtBreakdown = [];
while ($row = $result->fetch_assoc()) $dtBreakdown[] = $row;
$stats['data_type_breakdown'] = $dtBreakdown;

// Scope usage
$result = $conn->query("SELECT scope, COUNT(*) as count FROM symbols GROUP BY scope");
$scopeBreakdown = [];
while ($row = $result->fetch_assoc()) $scopeBreakdown[] = $row;
$stats['scope_usage'] = $scopeBreakdown;

// Error type breakdown
$result = $conn->query("SELECT error_type, COUNT(*) as count FROM errors GROUP BY error_type ORDER BY count DESC");
$errBreakdown = [];
while ($row = $result->fetch_assoc()) $errBreakdown[] = $row;
$stats['error_type_breakdown'] = $errBreakdown;

// Recent programs
$result = $conn->query("SELECT id, program_name, language, created_at FROM programs ORDER BY created_at DESC LIMIT 5");
$recent = [];
while ($row = $result->fetch_assoc()) $recent[] = $row;
$stats['recent_programs'] = $recent;

sendResponse($stats);
$conn->close();
?>
