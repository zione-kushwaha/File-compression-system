# File Compression System Using Huffman Coding

> **Academic Project Submission**  
> Candidate: **Aarti Kushwaha** (2026)  
> Built with **Clean Architecture**, **HTML5**, **CSS3**, **Vanilla JavaScript**, **PHP 8.3**, and **SQLite / MySQL**.

---

## 🌟 Overview & Key Highlights

This application is an optimal **Lossless File Compression System** based purely on the classical **Huffman Coding Algorithm**:
1. **Core Lossless Compression**: Pure **Huffman Variable-Length Coding** utilizing a Min-Heap Priority Queue (`SplPriorityQueue`) for character-frequency analysis, building optimal binary prefix trees, generating variable-length codewords, and bit-level stream serialization.
2. **Lossless Restoration**: Bitstream parsing and sequential prefix tree traversal reconstructing original files byte-for-byte with 100% fidelity.
3. **Data Integrity Verification**: Integrated cryptographic **SHA-256 Checksums** computed before compression and verified after decompression to ensure zero data corruption.
4. **Clean Architecture**: Decoupled domain entities, repository interfaces, Huffman engine services, and controller adapters.
5. **Interactive Web Dashboard**: Drag-and-drop file ingestion, real-time compression ratio calculation, download manager, and session activity tracking.

---

## 🏛️ Clean Architecture Breakdown

The project follows strict separation of concerns into decoupled layers:

```
file_compression_system/
├── config/
│   ├── app.php                       # Constants, autoloader, environment setup
│   └── database.php                  # PDO connection (MySQL + auto SQLite fallback)
├── database/
│   └── schema.sql                    # Relational schema (users, files, logs)
├── report/
│   ├── main.tex                      # Complete Academic Project Proposal (LaTeX)
│   ├── main.pdf                      # Compiled Project Proposal PDF
│   ├── presentation.tex              # 7-Slide Defense Presentation (Beamer LaTeX)
│   └── presentation.pdf              # Compiled 7-Slide Presentation PDF
├── src/
│   ├── Domain/                       # Core Entities (Enterprise Business Rules)
│   │   ├── HuffmanNode.php           # Binary tree node & leaf structure
│   │   ├── FileRecord.php            # Stored archive domain model
│   │   ├── ActivityLog.php           # Audit entry domain model
│   │   └── User.php                  # User entity model
│   ├── Interfaces/                   # Abstractions / Contracts (Dependency Inversion)
│   │   ├── CompressionInterface.php
│   │   ├── FileRepositoryInterface.php
│   │   └── UserRepositoryInterface.php
│   ├── Services/                     # Application Business Logic
│   │   ├── HuffmanEngine.php         # Huffman tree builder & bitstream packer
│   │   ├── FileStorageService.php    # Isolated directory disk manager
│   │   └── AuthService.php           # User session & bcrypt authentication
│   ├── Repositories/                 # Data Access Layer
│   │   ├── DatabaseFileRepository.php # PDO implementation for files & logs
│   │   └── DatabaseUserRepository.php # PDO implementation for users
│   └── Controllers/                  # Interface Adapters
│       ├── CompressionController.php # Coordinates compress & decompress workflows
│       ├── FileController.php        # File listing, download, and delete actions
│       └── AuthController.php        # User login, registration, and logout
├── public/                           # Web Document Root
│   ├── index.php                     # Clean single-page dashboard view
│   ├── api.php                       # REST API router
│   └── assets/
│       ├── css/style.css             # Responsive styling
│       └── js/ (app.js, crypto-utils.js)
├── storage/                          # Isolated Non-public Storage
│   ├── compressed/                   # Stored archives (.shuf)
│   ├── decompressed/                 # Restored files
│   └── database.sqlite               # Local zero-config database
├── tools/
│   └── php/                          # Embedded portable PHP 8.3 runtime
├── run_server.bat                    # 1-Click launcher
└── test_system.php                   # Automated CLI test suite
```

---

## 🚀 How to Run the System

### Method 1: One-Click Launcher (Recommended for Windows / VS Code)
Double-click [`run_server.bat`](file:///e:/file_compression%20system/run_server.bat).  
It automatically starts the local PHP server and opens `http://127.0.0.1:8000` in your default browser.

### Method 2: Manual Terminal in VS Code
Run:
```powershell
.\tools\php\php.exe -S 127.0.0.1:8000 -t public
```
Then visit `http://127.0.0.1:8000`.

### Pre-loaded Demo Account
- **Username**: `demo_user`
- **Password**: `Admin@123`  
*(Or click the 1-Click Demo Login button on the interface)*

---

## 🧪 Automated Test Suite

Run the full system test suite via terminal:
```powershell
.\tools\php\php.exe test_system.php
```

All tests verify:
- SQLite & MySQL database connectivity
- Huffman compression ratio, entropy, and lossless byte-for-byte match
- Large file compression benchmarks (20% to 60% savings)
- SHA-256 checksum verification
- Bcrypt password hashing and user authentication

---

## 📄 Academic Proposal & Defense Presentation

The academic deliverables are located in the `report/` folder:
- **Proposal Document (PDF)**: [`report/main.pdf`](file:///e:/file_compression%20system/report/main.pdf)
- **7-Slide Defense Presentation (PDF)**: [`report/presentation.pdf`](file:///e:/file_compression%20system/report/presentation.pdf)
