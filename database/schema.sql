-- Symbol Table Management System Database Schema
-- Import this in phpMyAdmin after creating database 'symbol_table_db'

CREATE DATABASE IF NOT EXISTS symbol_table_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE symbol_table_db;

-- ============================================================
-- TABLE: programs
-- Stores compiler programs/source files being analyzed
-- ============================================================
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(255) NOT NULL,
    language VARCHAR(100) NOT NULL DEFAULT 'C',
    source_code TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE: symbol_tables
-- Each program can have multiple scopes (global, local, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS symbol_tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    scope_name VARCHAR(255) NOT NULL DEFAULT 'global',
    scope_level INT NOT NULL DEFAULT 0,
    parent_scope_id INT DEFAULT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_scope_id) REFERENCES symbol_tables(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE: symbols
-- The core symbol entries in each scope
-- ============================================================
CREATE TABLE IF NOT EXISTS symbols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('variable','function','constant','class','parameter','array','pointer','struct','enum','typedef') NOT NULL DEFAULT 'variable',
    data_type VARCHAR(100) NOT NULL DEFAULT 'int',
    value VARCHAR(500) DEFAULT NULL,
    scope ENUM('global','local','parameter','class') NOT NULL DEFAULT 'local',
    is_initialized TINYINT(1) NOT NULL DEFAULT 0,
    is_declared TINYINT(1) NOT NULL DEFAULT 1,
    line_number INT DEFAULT NULL,
    memory_address VARCHAR(50) DEFAULT NULL,
    size_bytes INT DEFAULT NULL,
    attributes JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES symbol_tables(id) ON DELETE CASCADE,
    UNIQUE KEY unique_symbol_in_scope (table_id, name)
);

-- ============================================================
-- TABLE: symbol_references
-- Tracks where each symbol is used
-- ============================================================
CREATE TABLE IF NOT EXISTS symbol_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol_id INT NOT NULL,
    line_number INT NOT NULL,
    reference_type ENUM('declaration','definition','usage','assignment','call') NOT NULL DEFAULT 'usage',
    context VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (symbol_id) REFERENCES symbols(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE: errors
-- Compiler errors associated with symbols
-- ============================================================
CREATE TABLE IF NOT EXISTS errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    error_type ENUM('undeclared','redeclaration','type_mismatch','scope_error','syntax_error','semantic_error') NOT NULL,
    symbol_name VARCHAR(255) DEFAULT NULL,
    line_number INT DEFAULT NULL,
    message TEXT NOT NULL,
    is_resolved TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- ============================================================
-- SAMPLE DATA
-- ============================================================
INSERT INTO programs (program_name, language, description, source_code) VALUES
('Hello World Program', 'C', 'Basic hello world program', '#include <stdio.h>\nint main() {\n    int x = 10;\n    char name[] = "World";\n    printf("Hello %s, x=%d\\n", name, x);\n    return 0;\n}'),
('Calculator Program', 'C++', 'Simple arithmetic calculator', '#include <iostream>\nusing namespace std;\nfloat add(float a, float b) {\n    float result = a + b;\n    return result;\n}\nint main() {\n    float x = 5.0, y = 3.0;\n    cout << add(x, y);\n    return 0;\n}'),
('Student Management', 'Java', 'Student records management system', 'public class Student {\n    private int id;\n    private String name;\n    private float gpa;\n    public Student(int id, String name) {\n        this.id = id;\n        this.name = name;\n        this.gpa = 0.0f;\n    }\n}');

INSERT INTO symbol_tables (program_id, scope_name, scope_level, description) VALUES
(1, 'global', 0, 'Global scope for Hello World'),
(1, 'main', 1, 'Local scope of main function'),
(2, 'global', 0, 'Global scope for Calculator'),
(2, 'add', 1, 'Function scope for add()'),
(2, 'main', 1, 'Local scope of main function'),
(3, 'class:Student', 0, 'Class scope for Student');

INSERT INTO symbols (table_id, name, type, data_type, value, scope, is_initialized, is_declared, line_number, memory_address, size_bytes) VALUES
(1, 'stdio.h', 'constant', 'header', NULL, 'global', 1, 1, 1, NULL, NULL),
(2, 'x', 'variable', 'int', '10', 'local', 1, 1, 3, '0x7fff5fbff5ac', 4),
(2, 'name', 'array', 'char', '"World"', 'local', 1, 1, 4, '0x7fff5fbff5a0', 6),
(2, 'main', 'function', 'int', NULL, 'global', 0, 1, 2, '0x100000f30', 0),
(3, 'add', 'function', 'float', NULL, 'global', 0, 1, 3, '0x100000f80', 0),
(4, 'a', 'parameter', 'float', NULL, 'parameter', 0, 1, 3, '0x7fff5fbff59c', 4),
(4, 'b', 'parameter', 'float', NULL, 'parameter', 0, 1, 3, '0x7fff5fbff598', 4),
(4, 'result', 'variable', 'float', NULL, 'local', 1, 1, 4, '0x7fff5fbff594', 4),
(5, 'x', 'variable', 'float', '5.0', 'local', 1, 1, 8, '0x7fff5fbff590', 4),
(5, 'y', 'variable', 'float', '3.0', 'local', 1, 1, 8, '0x7fff5fbff58c', 4),
(6, 'id', 'variable', 'int', NULL, 'class', 0, 1, 2, NULL, 4),
(6, 'name', 'variable', 'String', NULL, 'class', 0, 1, 3, NULL, 8),
(6, 'gpa', 'variable', 'float', '0.0f', 'class', 1, 1, 4, NULL, 4),
(6, 'Student', 'function', 'void', NULL, 'class', 0, 1, 5, NULL, 0);

INSERT INTO symbol_references (symbol_id, line_number, reference_type, context) VALUES
(2, 3, 'declaration', 'int x = 10;'),
(2, 5, 'usage', 'printf("Hello %s, x=%d\n", name, x);'),
(3, 4, 'declaration', 'char name[] = "World";'),
(3, 5, 'usage', 'printf("Hello %s, x=%d\n", name, x);'),
(6, 3, 'declaration', 'float a'),
(7, 3, 'declaration', 'float b'),
(8, 4, 'declaration', 'float result = a + b;');

INSERT INTO errors (program_id, error_type, symbol_name, line_number, message, is_resolved) VALUES
(1, 'undeclared', 'printf', 5, 'Function printf used without proper include (resolved by stdio.h)', 1),
(2, 'type_mismatch', 'result', 4, 'Potential precision loss in float arithmetic', 0),
(3, 'semantic_error', 'gpa', 7, 'Field gpa should be validated before assignment', 0);
