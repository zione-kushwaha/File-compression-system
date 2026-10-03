<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Compression | Huffman & AES-256</title>
    <meta name="description" content="Secure file compression and decompression using Huffman coding and AES-256 encryption.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/visualizer.css">
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar" id="mainNavbar">
        <a href="#" class="nav-brand" id="brandLink">
            <div class="brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="brand-text">
                <h1>SecureCompress <span class="brand-badge">PRO</span></h1>
            </div>
        </a>

        <div class="nav-tabs" id="navTabs">
            <button class="tab-btn active" data-tab="compressTab" id="tabBtnCompress">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242M12 12v9m-4-4 4 4 4-4"/></svg>
                Compress
            </button>
            <button class="tab-btn" data-tab="decompressTab" id="tabBtnDecompress">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242M12 21v-9m-4 4 4-4 4 4"/></svg>
                Decompress
            </button>
            <button class="tab-btn" data-tab="visualizerTab" id="tabBtnVisualizer">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><line x1="12" y1="8" x2="5" y2="16"/><line x1="12" y1="8" x2="19" y2="16"/></svg>
                Tree Graph
            </button>
            <button class="tab-btn" data-tab="vaultTab" id="tabBtnVault">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                My Vault
            </button>
            <button class="tab-btn" data-tab="logsTab" id="tabBtnLogs">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Activity
            </button>
        </div>

        <div class="nav-actions">
            <div class="status-indicator" title="Connected Database">
                <div class="pulse-dot"></div>
                <span id="dbStatusBadge">SQLITE / MYSQL</span>
            </div>

            <!-- Logged out button -->
            <button class="btn-auth-nav" id="btnOpenAuthModal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Sign In</span>
            </button>

            <!-- Logged in Profile Badge -->
            <div class="user-profile-badge" id="userProfileBadge">
                <div class="user-avatar" id="userAvatarText">U</div>
                <span id="navUsername">User</span>
                <button class="btn-logout-nav" id="btnLogout" title="Log Out">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-wrapper">

        <!-- System Authentication Lock Banner (Visible when logged out) -->
        <div class="system-lock-banner" id="systemLockBanner" style="display:none; background: linear-gradient(135deg, rgba(99,102,241,0.18), rgba(6,182,212,0.12)); border: 1px solid var(--border-active); border-radius: var(--radius-lg); padding: 1.5rem 2rem; margin-bottom: 2rem; align-items: center; justify-content: space-between; gap: 1.5rem; flex-wrap: wrap; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <div style="display:flex; align-items:center; gap: 1rem;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99,102,241,0.2); border: 1px solid rgba(99,102,241,0.4); display:flex; align-items:center; justify-content:center; color: var(--accent-cyan); flex-shrink: 0;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.25rem; color: #ffffff;">System Authentication Required</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Sign in with the preloaded Demo credentials or register your own account to unlock file compression and secure vault access.</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap: 0.75rem;">
                <button type="button" class="btn-primary" id="btnQuickDemoLogin" style="padding: 0.7rem 1.4rem; font-size: 0.875rem; width: auto; background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                    <span>⚡ 1-Click Demo Login</span>
                </button>
                <button type="button" class="btn-secondary" id="btnBannerOpenAuth" style="padding: 0.7rem 1.4rem; font-size: 0.875rem; width: auto;">
                    <span>Sign In / Register</span>
                </button>
            </div>
        </div>

        <!-- ==========================================
             TAB 1: COMPRESSION & ENCRYPTION
             ========================================== -->
        <section class="tab-content active" id="compressTab">
            <div class="section-header">
                <h2>Secure File Compression</h2>
                <p>Drag, compress, and password-protect your files with Huffman Coding & AES-256 military-grade encryption.</p>
            </div>

            <div class="grid-compress">
                <!-- Left: Dropzone & Settings -->
                <div class="glass-card" style="padding: 2rem;">
                    
                    <!-- File Dropzone -->
                    <div class="dropzone" id="dropzoneCompress">
                        <div class="dropzone-icon">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                            </svg>
                        </div>
                        <h3 class="dropzone-title">Drop your file here</h3>
                        <p class="dropzone-sub">Documents, text, pictures, or code files (Max 50MB)</p>
                        <span class="browse-btn">Choose File</span>
                        <input type="file" id="fileInputCompress" style="display: none;">
                    </div>

                    <!-- Selected File Preview -->
                    <div class="selected-file-card" id="selectedFileCard">
                        <div class="file-badge-icon">FILE</div>
                        <div class="file-meta-info">
                            <div class="file-name-text" id="fileNamePreview">filename.ext</div>
                            <div class="file-size-text" id="fileSizePreview">0 KB</div>
                        </div>
                        <button class="remove-file-btn" id="removeFileBtn" title="Remove">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <!-- Algorithm Selection -->
                    <div class="config-group" style="margin-top: 1.5rem;">
                        <div class="config-label">
                            <span>Compression Algorithm</span>
                        </div>
                        <div class="algo-selector-grid">
                            <div class="algo-card selected" data-algo="huffman" id="algoCardHuffman">
                                <div class="algo-card-header">
                                    <div class="algo-title">Huffman Coding</div>
                                    <span class="algo-badge">Visual Tree</span>
                                </div>
                                <div class="algo-desc">Frequency-based binary prefix tree.</div>
                            </div>
                            <div class="algo-card" data-algo="zip" id="algoCardZip">
                                <div class="algo-card-header">
                                    <div class="algo-title">Deflate (ZIP)</div>
                                    <span class="algo-badge" style="background:rgba(168,85,247,0.15); color:var(--accent-purple); border-color:rgba(168,85,247,0.3);">Standard</span>
                                </div>
                                <div class="algo-desc">Dictionary-based fast compression.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Protection Card -->
                    <div class="config-group">
                        <div class="security-card">
                            <div class="security-header">
                                <div class="security-title-box">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                    <div>
                                        <div style="font-size:0.95rem; font-weight:600;">Encrypt with AES-256</div>
                                        <div style="font-size:0.75rem; color:var(--text-dim);">Password protect this file</div>
                                    </div>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="encryptToggle">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="password-box" id="passwordBox">
                                <div class="input-field-wrapper">
                                    <input type="password" id="compressPasswordInput" class="text-input" placeholder="Set a secure password...">
                                    <button type="button" class="toggle-pwd-btn" id="togglePwdBtn">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                                <div class="strength-meter-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-label">
                                    <span id="strengthLabel">Strength: Empty</span>
                                    <span>AES-256-CBC</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <button class="btn-primary" id="btnCompressSubmit" disabled>
                        <div class="spinner" id="compressSpinner"></div>
                        <span id="compressBtnText">Compress & Save</span>
                    </button>
                </div>

                <!-- Right: Visual Feature Card & Results -->
                <div>
                    <!-- Result Card -->
                    <div class="result-card" id="resultCardCompress">
                        <div class="result-header">
                            <div class="result-title">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                File Compressed Successfully!
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">Original</div>
                                <div class="stat-val" id="resOriginalSize">0 B</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Compressed</div>
                                <div class="stat-val" id="resCompressedSize">0 B</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Saved</div>
                                <div class="stat-val" id="resRatio">0%</div>
                            </div>
                        </div>

                        <div class="hash-preview-box">
                            <div>
                                <span style="color:var(--text-dim);">SHA-256 Checksum: </span>
                                <span class="hash-text" id="resChecksum">Calculating...</span>
                            </div>
                            <button class="copy-btn" id="copyChecksumBtn">Copy</button>
                        </div>

                        <div class="action-row">
                            <a href="#" class="btn-secondary" id="resDownloadBtn" download>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                Download File
                            </a>
                            <button class="btn-secondary" id="resViewTreeBtn" style="display:none; background:rgba(99,102,241,0.15); border-color:var(--primary);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/><line x1="12" y1="8" x2="5" y2="16"/><line x1="12" y1="8" x2="19" y2="16"/></svg>
                                View Tree
                            </button>
                        </div>
                    </div>

                    <!-- Visual Tech Card -->
                    <div class="glass-card" style="padding: 1.5rem; text-align: center; overflow: hidden;">
                        <img src="assets/img/shield_vault.jpg" alt="Security Shield" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: 1rem; border: 1px solid var(--border-subtle);">
                        <div style="display: flex; justify-content: space-around; font-size: 0.825rem; color: var(--text-muted);">
                            <div>🔒 <strong>AES-256</strong> Encrypted</div>
                            <div>⚡ <strong>Huffman</strong> Lossless</div>
                            <div>🛡️ <strong>SHA-256</strong> Verified</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 2: DECOMPRESSION & RESTORATION
             ========================================== -->
        <section class="tab-content" id="decompressTab">
            <div class="section-header">
                <h2>Decompress & Restore</h2>
                <p>Upload your <code>.shuf</code> or <code>.szip</code> file to unlock and restore the original file.</p>
            </div>

            <div style="max-width: 620px; margin: 0 auto;">
                <div class="glass-card" style="padding: 2.2rem;">
                    <div class="dropzone" id="dropzoneDecompress">
                        <div class="dropzone-icon" style="color:var(--accent-cyan); border-color:rgba(6,182,212,0.3); background:rgba(6,182,212,0.1);">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                        </div>
                        <h3 class="dropzone-title">Upload Compressed Archive</h3>
                        <p class="dropzone-sub">Drop <code>.shuf</code> or <code>.szip</code> here</p>
                        <span class="browse-btn">Select File</span>
                        <input type="file" id="fileInputDecompress" style="display: none;">
                    </div>

                    <div class="selected-file-card" id="decompressCard">
                        <div class="file-badge-icon" style="background:rgba(6,182,212,0.15); color:var(--accent-cyan); border-color:rgba(6,182,212,0.3);">ARCH</div>
                        <div class="file-meta-info">
                            <div class="file-name-text" id="decompressFileName">archive.shuf</div>
                            <div class="file-size-text" id="decompressFileSize">0 KB</div>
                        </div>
                        <button class="remove-file-btn" id="removeDecompressFileBtn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="config-group" id="decompressPasswordGroup" style="margin-top: 1.5rem; display:none;">
                        <label class="config-label">
                            <span>Password (Required for AES-256)</span>
                        </label>
                        <input type="password" id="decompressPasswordInput" class="text-input" placeholder="Enter password to unlock...">
                    </div>

                    <button class="btn-primary" id="btnDecompressSubmit" style="margin-top: 1.5rem;" disabled>
                        <div class="spinner" id="decompressSpinner"></div>
                        <span id="decompressBtnText">Decompress & Restore</span>
                    </button>

                    <!-- Result -->
                    <div class="result-card" id="resultCardDecompress" style="margin-top: 1.8rem;">
                        <div class="result-header">
                            <div class="result-title">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                File Restored Successfully
                            </div>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">File</div>
                                <div class="stat-val" id="resDecName" style="font-size:0.9rem; overflow:hidden; text-overflow:ellipsis;">file.txt</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Size</div>
                                <div class="stat-val" id="resDecSize">0 B</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Algorithm</div>
                                <div class="stat-val" id="resDecAlgo" style="font-size:0.85rem; color:var(--accent-cyan);">Huffman</div>
                            </div>
                        </div>
                        <div class="hash-preview-box">
                            <div>
                                <span style="color:var(--text-dim);">SHA-256: </span>
                                <span class="hash-text" id="resDecHash">Matching...</span>
                            </div>
                        </div>
                        <div class="action-row">
                            <a href="#" class="btn-secondary" id="resDecDownloadBtn" download>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                                Download Restored File
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 3: HUFFMAN TREE VISUALIZER
             ========================================== -->
        <section class="tab-content" id="visualizerTab">
            <div class="section-header">
                <h2>Huffman Binary Tree Visualizer</h2>
                <p>Visual representation of character frequencies and prefix tree paths.</p>
            </div>

            <div class="visualizer-wrapper">
                <div class="glass-card tree-card-container">
                    <div class="tree-controls">
                        <div class="tree-title-group">
                            <h3>Binary Prefix Graph</h3>
                            <span style="font-size:0.75rem; color:var(--text-dim);">Green = Characters | Blue = Frequency sum nodes</span>
                        </div>
                        <div class="tree-btn-group">
                            <button class="ctrl-btn" id="btnZoomIn">+ Zoom In</button>
                            <button class="ctrl-btn" id="btnZoomOut">- Zoom Out</button>
                            <button class="ctrl-btn" id="btnResetZoom">Reset</button>
                        </div>
                    </div>

                    <div class="svg-canvas-container" id="svgCanvasContainer">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <div class="codebook-grid">
                    <div class="glass-card table-card">
                        <div class="table-header-box">
                            <h3 style="font-size: 1.05rem; font-weight:600;">Codebook Dictionary</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table" id="codebookTable">
                                <thead>
                                    <tr>
                                        <th>Char</th>
                                        <th>ASCII</th>
                                        <th>Count</th>
                                        <th>Prob.</th>
                                        <th>Binary Code</th>
                                        <th>Bits</th>
                                    </tr>
                                </thead>
                                <tbody id="codebookTableBody">
                                    <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Compress a file using Huffman Coding to see its codebook.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="glass-card theory-card">
                        <h3 style="font-size: 1.05rem; font-weight:600;">Compression Metrics</h3>
                        
                        <div class="metric-bar-group">
                            <div>
                                <div class="metric-row-header">
                                    <span style="color:var(--text-muted);">Shannon Entropy:</span>
                                    <strong id="entropyVal" style="color:var(--accent-cyan); font-family:var(--font-mono);">0.00 bits/symbol</strong>
                                </div>
                            </div>
                            <div>
                                <div class="metric-row-header">
                                    <span style="color:var(--text-muted);">Average Code Length:</span>
                                    <strong id="avgCodeLenVal" style="color:var(--primary-light); font-family:var(--font-mono);">0.00 bits/symbol</strong>
                                </div>
                            </div>
                            <div>
                                <div class="metric-row-header">
                                    <span style="color:var(--text-muted);">Efficiency (&eta;):</span>
                                    <strong id="efficiencyVal" style="color:var(--accent-emerald); font-family:var(--font-mono);">0%</strong>
                                </div>
                                <div class="metric-track">
                                    <div class="metric-progress" id="efficiencyProgressBar" style="width: 0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 4: FILE VAULT
             ========================================== -->
        <section class="tab-content" id="vaultTab">
            <div class="section-header">
                <h2>Saved Archives Vault</h2>
                <p>Browse, download, or decompress your saved files.</p>
            </div>

            <div class="glass-card table-card">
                <div class="vault-header">
                    <h3 style="font-size: 1.1rem; font-weight:600;">My Archives</h3>
                </div>
                <div class="table-responsive">
                    <table class="custom-table" id="vaultTable">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Algorithm</th>
                                <th>Security</th>
                                <th>Original</th>
                                <th>Compressed</th>
                                <th>Saved</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vaultTableBody">
                            <tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading vault...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 5: ACTIVITY LOGS
             ========================================== -->
        <section class="tab-content" id="logsTab">
            <div class="section-header">
                <h2>Activity Log</h2>
                <p>Audit trail of compression, decompression, and account logins.</p>
            </div>

            <div class="glass-card table-card">
                <div class="table-responsive">
                    <table class="custom-table" id="logsTable">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>IP</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading logs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </main>

    <!-- Footer -->
    <footer class="footer">
        <div>&copy; 2026 Secure File Compression System</div>
        <div class="footer-links">
            <span id="footerDbName" style="color: var(--accent-cyan); font-family: var(--font-mono);">Database Connected</span>
        </div>
    </footer>

    <!-- ==========================================
         AUTHENTICATION MODAL DIALOG
         ========================================== -->
    <div class="modal-overlay" id="authModalOverlay">
        <div class="auth-modal-dialog">
            
            <!-- Left Side: 3D Visual Asset & Headline -->
            <div class="auth-banner-side">
                <img src="assets/img/shield_vault.jpg" alt="Vault Graphic" class="auth-banner-img">
                <div class="auth-banner-overlay"></div>
                <div class="auth-banner-content">
                    <div class="auth-banner-title">Secure File Vault</div>
                    <div class="auth-banner-sub">Protect and compress your confidential files with Huffman coding and AES-256 encryption.</div>
                </div>
            </div>

            <!-- Right Side: Clean Form with Tab Switcher -->
            <div class="auth-form-side">
                <button type="button" class="auth-close-x" id="btnCloseAuthModal" title="Close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>

                <div class="auth-tab-switch">
                    <button type="button" class="auth-switch-btn active" id="btnSwitchSignIn">Sign In</button>
                    <button type="button" class="auth-switch-btn" id="btnSwitchSignUp">Create Account</button>
                </div>

                <!-- SIGN IN FORM -->
                <form class="auth-form active" id="formSignIn">
                    <div class="form-group-modern">
                        <label class="form-label-modern">Email or Username</label>
                        <input type="text" id="loginIdentifier" class="text-input" placeholder="e.g. demo_user" required>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Password</label>
                        <div class="input-field-wrapper">
                            <input type="password" id="loginPassword" class="text-input" placeholder="••••••••" required>
                            <button type="button" class="toggle-pwd-btn" id="btnToggleLoginPwd">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Demo Credentials Pill -->
                    <div class="demo-quick-login" id="btnFillDemoUser" title="Click to fill demo account">
                        <span>💡 Quick Demo: <strong>demo_user</strong> / <strong>Admin@123</strong></span>
                        <span style="color:var(--accent-cyan);">Auto-Fill &rarr;</span>
                    </div>

                    <button type="submit" class="btn-primary" id="btnLoginSubmit" style="margin-top:0.4rem;">
                        <div class="spinner" id="loginSpinner"></div>
                        <span id="loginBtnText">Sign In</span>
                    </button>
                </form>

                <!-- SIGN UP FORM -->
                <form class="auth-form" id="formSignUp">
                    <div class="form-group-modern">
                        <label class="form-label-modern">Username</label>
                        <input type="text" id="regUsername" class="text-input" placeholder="Choose a username" required minlength="3">
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Email Address</label>
                        <input type="email" id="regEmail" class="text-input" placeholder="name@domain.com" required>
                    </div>

                    <div class="form-group-modern">
                        <label class="form-label-modern">Password</label>
                        <div class="input-field-wrapper">
                            <input type="password" id="regPassword" class="text-input" placeholder="Minimum 6 characters" required minlength="6">
                            <button type="button" class="toggle-pwd-btn" id="btnToggleRegPwd">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="btnRegisterSubmit" style="margin-top:0.4rem;">
                        <div class="spinner" id="regSpinner"></div>
                        <span id="regBtnText">Create Account</span>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Scripts -->
    <script src="assets/js/crypto-utils.js"></script>
    <script src="assets/js/visualizer.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
