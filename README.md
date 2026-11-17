# 🗂️ Symbol Table Management System (STMS)
### Project-Based Learning (PBL) — Compiler Design & Engineering

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Chart.js](https://img.shields.io/badge/Chart.js-4.x-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)
[![License](https://img.shields.io/badge/License-Academic%20Use-blue?style=for-the-badge)](#)

---

## 👥 Project Team Members

| Sl. No. | Team Member | University Role / Contribution |
| :---: | :--- | :--- |
| **1.** | **Agnibha Dey** | System Architecture, Full-Stack Lead & GitHub Maintainer |
| **2.** | **Ashika Ray** | Compiler Lexer/Analyzer Logic & Scope Hierarchy Design |
| **3.** | **Ritayan Banerjee** | Database Schema, Relational Integrity & API Development |
| **4.** | **Sayak Das** | Frontend UI/UX, Chart.js Visualizations & QA Testing |

---

## 📖 Executive Summary & Academic Background

In computer science and compiler construction, a **Symbol Table** is a fundamental data structure maintained by the translator to store information about source-program identifiers (such as variable names, function names, objects, classes, interfaces, and data types). 

During lexical and syntax analysis, symbols are collected. During semantic analysis, the compiler checks for:
- Type compatibility and assignment validation.
- Scope rules (global vs. block/local vs. parameter scoping).
- Declarations vs. redeclarations (detecting duplicate symbol conflicts).
- Tracking memory addresses, byte offsets, and initialization flags.

The **Symbol Table Management System (STMS)** is an interactive, full-stack platform designed to simulate, visualize, and manage the life cycle of symbol tables across different programming scopes. It bridges theoretical compiler design concepts with practical web engineering.

---

## ✨ Key System Features

- **🔐 User Authentication & Session Security**:
  - Secure registration, login, and token-based authentication.
  - Password recovery system with OTP verification and mock mail outbox.
  - Preconfigured demo administrative credentials.

- **📊 Interactive Compiler Analytics Dashboard**:
  - Real-time aggregation of total programs, scopes, active symbols, and unresolved errors.
  - Dynamic visualizations powered by Chart.js (Symbol Type breakdown, Language usage, Error classifications).

- **📂 Multi-Program Management**:
  - CRUD operations for managing different codebases (C, C++, Java, Python, JavaScript).
  - Storage of raw source code, metadata, and compilation tracking.

- **🌳 Hierarchical Scopes & Symbol Tables**:
  - Multi-level nested scope tracking (`global`, `local`, `function`, `parameter`, `class`).
  - Parent-child tree linkages to model lexical block structures and activation records.

- **🔍 Comprehensive Symbol Attributes Tracker**:
  - Stores: Identifier Name, Symbol Type, Data Type, Assigned Value, Memory Address (Hex), Size in Bytes, Line Number, Initialized status, Declared status.
  - Cross-reference tracking (`declaration`, `definition`, `usage`, `assignment`, `call`).

- **⚡ Built-in C/C++ Code Lexer & Semantic Analyzer**:
  - CodeMirror IDE integration with syntax highlighting.
  - Automated regex-based parser that scans source code, discovers functions/parameters/variables, creates corresponding scopes, and populates the database automatically.

- **🐛 Semantic Error Logging & Resolution**:
  - Records and diagnoses compiler diagnostics: `undeclared`, `redeclaration`, `type_mismatch`, `scope_error`, `syntax_error`, `semantic_error`.
  - Mark errors as resolved with audit logs.

---

## 🏗️ System Architecture & Workflow

```
+-------------------------------------------------------------------------+
|                         FRONTEND (Single Page App)                      |
|      HTML5  *  Bootstrap 5.3  *  CodeMirror 5  *  Chart.js  *  JS       |
+------------------------------------+------------------------------------+
                                     |
                                REST API (JSON)
                                     |
+------------------------------------v------------------------------------+
|                         BACKEND (PHP 8.x / XAMPP)                       |
|   +------------------+  +------------------+  +---------------------+   |
|   | auth.php         |  | programs.php     |  | analyze.php         |   |
|   | config.php       |  | scopes.php       |  | symbols.php         |   |
|   | mailer.php       |  | errors.php       |  | stats.php           |   |
|   +------------------+  +------------------+  +---------------------+   |
+------------------------------------+------------------------------------+
                                     |
                               MySQLi Driver
                                     |
+------------------------------------v------------------------------------+
|                         DATABASE (MySQL / MariaDB)                      |
|   +--------------+  +------------------+  +-------------------------+   |
|   | users        |  | programs         |  | symbols                 |   |
|   | password_otps|  | symbol_tables    |  | symbol_references       |   |
|   | mail_outbox  |  | errors           |  | (Foreign Key Cascades)  |   |
|   +--------------+  +------------------+  +-------------------------+   |
+-------------------------------------------------------------------------+
```

---

## 🛠️ Technology Stack

| Layer | Technologies Used |
| :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons, CodeMirror (C-like mode, Dracula theme), Chart.js |
| **Backend** | PHP 8.x (RESTful JSON APIs, MySQLi Prepared Statements, Session/Token handling) |
| **Database** | MySQL / MariaDB (InnoDB, Foreign Key Constraints, Cascading Deletes, UTF-8mb4) |
| **Local Server** | XAMPP (Apache HTTP Server + MySQL Service) |
| **Version Control** | Git & GitHub |

---

## 📁 Repository Directory Structure

```plaintext
Symbol-Table-Management-System/
├── api/
│   ├── .htaccess             # Apache rewrite and CORS directives
│   ├── analyze.php           # Automated C/C++ lexer and code parser
│   ├── auth.php              # Authentication, login, signup, OTP verify
│   ├── config.php            # Database connection & global response helpers
│   ├── errors.php            # Compiler error log CRUD API
│   ├── mailer.php            # Mock mailer & OTP generator
│   ├── programs.php          # Programs repository CRUD API
│   ├── references.php        # Symbol occurrence references API
│   ├── scopes.php            # Scope tree and hierarchy API
│   ├── stats.php             # Analytics & aggregated metrics API
│   └── symbols.php           # Core symbol table records API
├── database/
│   ├── auth_schema.sql       # Users, OTPs, and mail queue tables
│   └── schema.sql            # Main compiler & symbol tables schema + seed data
├── .gitignore                # Git exclusions
├── deploy-to-xampp.bat       # One-click Windows deployment script to XAMPP
├── index.html                # Main SPA Dashboard application
└── README.md                 # Complete project documentation
```

---

## ⚙️ Installation & Setup Guide

### 📋 Prerequisites
1. **XAMPP Control Panel** (with Apache and MySQL services installed).
2. Modern Web Browser (Google Chrome, Microsoft Edge, Firefox).
3. **Git** installed on your system.

---

### Step 1: Clone the Repository
Clone the repository to your local machine:
```bash
git clone https://github.com/Agni207/Symbol-Table-Management-System.git
```

---

### Step 2: Deploy to XAMPP `htdocs`
You can either:
- **Option A (Automated)**: Double-click `deploy-to-xampp.bat` to automatically sync files to `C:\xampp\htdocs\symbol-table-system\`.
- **Option B (Manual)**: Copy the entire `Symbol-Table-Management-System` folder contents into:
  ```
  C:\xampp\htdocs\symbol-table-system\
  ```

---

### Step 3: Configure the Database
1. Launch **XAMPP Control Panel** and start both **Apache** and **MySQL**.
2. Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
3. Click on the **SQL** tab or create a database named `symbol_table_db`:
   ```sql
   CREATE DATABASE IF NOT EXISTS symbol_table_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Click **Import**:
   - First import `database/schema.sql`.
   - Next import `database/auth_schema.sql`.
5. Verify that the tables (`programs`, `symbol_tables`, `symbols`, `symbol_references`, `errors`, `users`, `password_otps`, `mail_outbox`) are populated with sample data.

---

### Step 4: Run the Application in Google Chrome
Open Google Chrome and visit:
```
http://localhost/symbol-table-system/
```
*(Or simply open `index.html` via your local web server environment).*

#### 🔑 Default Demo Credentials
- **Email**: `admin@symboltable.local`
- **Password**: `Admin@123`
*(You can also use the registration form to create a new personal account).*

---

## 🧪 Demonstration & Testing Walkthrough

1. **Dashboard Overview**:
   - Review live metric cards displaying total programs, scopes, symbols, and open compiler errors.
   - Inspect the interactive Doughnut and Bar charts visualizing symbol type distributions.

2. **Creating & Managing Programs**:
   - Navigate to **Programs** $\rightarrow$ Click **New Program**.
   - Input a program name (e.g., `Matrix Multiplication in C`), select language `C`, and paste your code.
   - The system automatically provisions a root `global` scope table for the program.

3. **Running the Automated Code Analyzer**:
   - Navigate to **Code Analyzer**.
   - Select your target program.
   - Paste or load sample C/C++ code into the CodeMirror editor.
   - Click **Analyze Code**: The backend lexical scanner parses function declarations, parameter lists, and variable definitions, calculates memory byte sizes, and commits them to the database.

4. **Examining Scopes & Symbols**:
   - Navigate to **Scopes / Tables** to view the hierarchical nesting level of each function block.
   - Navigate to **Symbols** to search, filter by type (Variable, Function, Array, Struct), and inspect memory addresses.

5. **Logging & Resolving Compiler Diagnostics**:
   - Navigate to **Error Log** to track semantic or syntax issues (e.g., undeclared identifier on line 14).
   - Click the green checkmark icon to mark diagnostics as resolved.

---

## 📊 Database Schema Details

### `programs`
- `id` (PK, Auto Increment)
- `program_name` (VARCHAR)
- `language` (VARCHAR)
- `source_code` (TEXT)
- `description` (TEXT)
- `created_at`, `updated_at` (TIMESTAMP)

### `symbol_tables` (Scopes)
- `id` (PK, Auto Increment)
- `program_id` (FK $\rightarrow$ `programs.id` ON DELETE CASCADE)
- `scope_name` (VARCHAR - e.g. `global`, `main`, `calculateArea`)
- `scope_level` (INT - `0` for global, `1+` for nested)
- `parent_scope_id` (FK $\rightarrow$ `symbol_tables.id` ON DELETE SET NULL)
- `description` (TEXT)

### `symbols`
- `id` (PK, Auto Increment)
- `table_id` (FK $\rightarrow$ `symbol_tables.id` ON DELETE CASCADE)
- `name` (VARCHAR)
- `type` (`variable`, `function`, `constant`, `class`, `parameter`, `array`, `pointer`, `struct`, `enum`, `typedef`)
- `data_type` (VARCHAR - `int`, `float`, `char*`, `double`, etc.)
- `value` (VARCHAR)
- `scope` (`global`, `local`, `parameter`, `class`)
- `is_initialized` (TINYINT)
- `is_declared` (TINYINT)
- `line_number` (INT)
- `memory_address` (VARCHAR - Hexadecimal address simulation)
- `size_bytes` (INT)

### `errors`
- `id` (PK, Auto Increment)
- `program_id` (FK $\rightarrow$ `programs.id` ON DELETE CASCADE)
- `error_type` (`undeclared`, `redeclaration`, `type_mismatch`, `scope_error`, `syntax_error`, `semantic_error`)
- `symbol_name` (VARCHAR)
- `line_number` (INT)
- `message` (TEXT)
- `is_resolved` (TINYINT)

---

## 📜 Academic Evaluation & Submission Notice

This repository represents the official submission for the **Project-Based Learning (PBL)** coursework in **Compiler Design / Systems Programming**.

All code, schemas, and analytical models were developed collaboratively by the project group members listed above.

---
*Developed with ❤️ for Academic Excellence in Compiler Engineering.*
