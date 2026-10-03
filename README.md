# Secure File Compression & Vault System

> **Academic Project Submission**  
> Built with **Clean Architecture**, **HTML5**, **CSS3 (Custom Obsidian Glassmorphism)**, **JavaScript**, **PHP 8.3**, and **MySQL** (with dual zero-configuration SQLite adapter).

---

## 🌟 Overview & Key Highlights

This application is an enterprise-grade **Secure File Compression System** engineered specifically for academic evaluation and real-world utility:
1. **Core Lossless Compression**: Pure **Huffman Variable-Length Coding** utilizing a Min-Heap Priority Queue (`SplPriorityQueue`) for character-frequency analysis, building dynamic binary prefix trees, generating variable-length codewords, and bit-level stream serialization.
2. **Standard Comparison Engine**: Integrated **DEFLATE (ZIP RFC 1951)** engine to demonstrate dictionary vs. entropy compression comparisons.
3. **Military-Grade Security**: **AES-256-CBC** with **PBKDF2** (15,000 iterations) key derivation and **HMAC-SHA256 Encrypt-then-MAC** authentication to completely prevent tampering or bit-flipping attacks.
4. **Interactive Academic Visualizer**:
   - Live **Binary Prefix Tree Graph (SVG)** showing character leaves (green) and internal frequency nodes (blue) with `0` and `1` branch labels.
   - Live **Huffman Codebook Dictionary** detailing ASCII codes, occurrence frequencies, probabilities, and bit lengths.
   - **Shannon Entropy Gauge** ($H(X) = -\sum P(x) \log_2 P(x)$), Average Codeword Length ($L_{\text{avg}}$), and theoretical efficiency ($\eta$).
5. **Secure Vault & Activity Audit Trail**: Tracks all compression, decompression, integrity checks, and unauthorized attempts.

---

## 🏛️ Clean Architecture Breakdown

The project follows strict separation of concerns into 4 decoupled layers:

```
file_compression_system/
├── config/
│   ├── app.php                       # Constants, autoloader, environment setup
│   └── database.php                  # PDO connection (MySQL + auto SQLite fallback)
├── database/
│   └── schema.sql                    # Relational schema (users, files, logs)
├── src/
│   ├── Domain/                       # Core Entities (Enterprise Business Rules)
│   │   ├── HuffmanNode.php           # Binary tree node & leaf structure
│   │   ├── FileRecord.php            # Stored archive domain model
│   │   └── ActivityLog.php           # Audit entry domain model
│   ├── Interfaces/                   # Abstractions / Contracts (Dependency Inversion)
│   │   ├── CompressionInterface.php
│   │   ├── EncryptionInterface.php
│   │   └── FileRepositoryInterface.php
│   ├── Services/                     # Application Business Logic
│   │   ├── HuffmanEngine.php         # Huffman tree builder & bitstream packer
│   │   ├── ZipEngine.php             # Deflate level-9 compressor
│   │   ├── AesEncryptionService.php  # AES-256 PBKDF2 & HMAC authenticated encryption
│   │   └── FileStorageService.php    # Isolated directory disk manager
│   ├── Repositories/                 # Data Access Layer
│   │   └── DatabaseFileRepository.php # PDO implementation for MySQL / SQLite
│   └── Controllers/                  # Interface Adapters
│       ├── CompressionController.php # Coordinates compress & decompress workflows
│       └── FileController.php        # Vault management & download streams
├── public/                           # Web Document Root
│   ├── index.php                     # Single Page Interface view
│   ├── api.php                       # Front controller & REST API router
│   └── assets/
│       ├── css/ (style.css, visualizer.css)
│       └── js/  (app.js, visualizer.js, crypto-utils.js)
├── storage/                          # Secure Isolated Storage (Non-public)
│   ├── uploads/                      # Temporary staging
│   ├── compressed/                   # Obfuscated archives (.shuf, .szip)
│   ├── decompressed/                 # Restored files
│   └── database.sqlite               # Local database
├── tools/
│   └── php/                          # Embedded portable PHP 8.3 runtime
├── run_server.bat                    # 1-Click launcher
└── test_system.php                   # Automated CLI test suite
```

---

## 🚀 How to Run the System

### Method 1: One-Click Launcher (Recommended for Windows / VS Code)
Double-click [`run_server.bat`](file:///e:/file_compression%20system/run_server.bat).  
It automatically boots the embedded PHP 8.3 development server and opens `http://127.0.0.1:8000` in your default browser.

### Method 2: Manual Terminal in VS Code
Run:
```powershell
.\tools\php\php.exe -S 127.0.0.1:8000 -t public
```
Then navigate to: **`http://127.0.0.1:8000`**

### Method 3: Running with XAMPP MySQL
If you have XAMPP running:
1. Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. Import [`database/schema.sql`](file:///e:/file_compression%20system/database/schema.sql) into phpMyAdmin (creates `secure_compress`).
3. The application will automatically detect MySQL on `127.0.0.1:3306` and switch the database status badge from `SQLITE` to `MYSQL`.

---

## 🧪 Automated Test Suite

To verify all algorithms, lossless decompression, and AES-256 tamper rejection:
```powershell
.\tools\php\php.exe test_system.php
```

All 5 core tests pass with zero errors:
- [x] Database Connection (dual MySQL/SQLite)
- [x] Huffman Coding lossless byte-for-byte fidelity
- [x] AES-256 authenticated encryption & PBKDF2 key derivation
- [x] Tamper & wrong password rejection
- [x] ZIP Deflate compression & decompression

---

## 🎓 Academic Viva & Presentation Notes

1. **Why Huffman Coding?**
   - It is an optimal prefix code for lossless data compression. By assigning shorter bit codes to symbols that appear more frequently, it compresses data toward the theoretical limit established by **Claude Shannon's Source Coding Theorem**.
2. **What is Shannon Entropy?**
   - $H(X) = -\sum P(x) \log_2 P(x)$. It represents the average information content per symbol. The closer the average Huffman code length ($L_{\text{avg}}$) is to $H(X)$, the closer the compression is to theoretical optimality.
3. **How does the system decompress without losing data?**
   - The serialized frequency map is stored in the header of the `.shuf` file (`HUF1` magic signature). During decompression, the receiver reconstructs the exact same priority queue and Huffman tree, traversing the packed bitstream from root to leaf to recover the original bytes losslessly.
4. **Why Encrypt-then-MAC (AES-256 + HMAC)?**
   - Standard encryption is vulnerable to bit-flipping attacks. By generating an HMAC-SHA256 signature over the salt, IV, and ciphertext, any tampering or invalid password attempt is detected before decryption occurs.
