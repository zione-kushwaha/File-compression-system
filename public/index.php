<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Compression System | Huffman Coding</title>
    <meta name="description" content="Lossless file compression and decompression using Huffman Coding algorithm.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
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
                <h1>HuffmanCompress <span class="brand-badge">PRO</span></h1>
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
            <button class="tab-btn" data-tab="vaultTab" id="tabBtnVault">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                My Files
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
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">Sign in with the demo account or register your own account to compress and manage files.</p>
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
             TAB 1: COMPRESSION (HUFFMAN)
             ========================================== -->
        <section class="tab-content active" id="compressTab">
            <div class="section-header">
                <h2>Lossless File Compression</h2>
                <p>Drag and compress files using the Huffman Coding algorithm with optimal variable-length prefix codes.</p>
            </div>

            <div class="grid-compress">
                <!-- Left: Dropzone & Action -->
                <div class="glass-card" style="padding: 2rem;">
                    
                    <!-- File Dropzone -->
                    <div class="dropzone" id="dropzoneCompress">
                        <div class="dropzone-icon">
                            <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/>
                            </svg>
                        </div>
                        <h3 class="dropzone-title">Drop your file here</h3>
                        <p class="dropzone-sub">Documents, text, code, or data files (Max 50MB)</p>
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

                    <!-- Hidden inputs for compatibility -->
                    <input type="hidden" id="selectedAlgoInput" value="huffman">

                    <!-- Submit -->
                    <button class="btn-primary" id="btnCompressSubmit" style="margin-top: 1.5rem;" disabled>
                        <div class="spinner" id="compressSpinner"></div>
                        <span id="compressBtnText">Compress File (Huffman)</span>
                    </button>
                </div>

                <!-- Right: Results & Features -->
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
                                Download Compressed (.shuf)
                            </a>
                        </div>
                    </div>

                    <!-- Clean Algorithm Summary Card -->
                    <div class="glass-card" style="padding: 1.8rem; text-align: left;">
                        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.8rem; color: #ffffff;">Huffman Coding Principles</h3>
                        <p style="font-size: 0.875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 1.2rem;">
                            Huffman coding is a greedy, lossless entropy encoding algorithm. Characters with higher frequencies receive shorter binary codewords, while infrequent characters receive longer codewords, achieving optimal prefix compression.
                        </p>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; font-size: 0.8rem; text-align: center;">
                            <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid var(--border-subtle);">
                                <div style="font-size: 1.1rem; margin-bottom: 0.25rem;">⚡</div>
                                <strong style="color: #fff;">Lossless</strong>
                            </div>
                            <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid var(--border-subtle);">
                                <div style="font-size: 1.1rem; margin-bottom: 0.25rem;">🌳</div>
                                <strong style="color: #fff;">Min-Heap Tree</strong>
                            </div>
                            <div style="padding: 0.75rem; background: rgba(255,255,255,0.03); border-radius: 8px; border: 1px solid var(--border-subtle);">
                                <div style="font-size: 1.1rem; margin-bottom: 0.25rem;">🛡️</div>
                                <strong style="color: #fff;">SHA-256 Check</strong>
                            </div>
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
                <p>Upload your <code>.shuf</code> file to rebuild the Huffman tree and restore the original file.</p>
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
                        <p class="dropzone-sub">Drop <code>.shuf</code> file here</p>
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

                    <button class="btn-primary" id="btnDecompressSubmit" style="margin-top: 1.5rem;" disabled>
                        <div class="spinner" id="decompressSpinner"></div>
                        <span id="decompressBtnText">Decompress & Restore File</span>
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
             TAB 3: MY FILES
             ========================================== -->
        <section class="tab-content" id="vaultTab">
            <div class="section-header">
                <h2>My Compressed Files</h2>
                <p>Browse, download, or decompress your saved Huffman archives.</p>
            </div>

            <div class="glass-card table-card">
                <div class="table-responsive">
                    <table class="custom-table" id="vaultTable">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Algorithm</th>
                                <th>Original Size</th>
                                <th>Compressed Size</th>
                                <th>Ratio</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="vaultTableBody">
                            <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading files...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ==========================================
             TAB 4: ACTIVITY LOGS
             ========================================== -->
        <section class="tab-content" id="logsTab">
            <div class="section-header">
                <h2>Activity Log</h2>
                <p>Audit trail of compression and decompression sessions.</p>
            </div>

            <div class="glass-card table-card">
                <div class="table-responsive">
                    <table class="custom-table" id="logsTable">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Action</th>
                                <th>Target File</th>
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
        <div>&copy; 2026 File Compression System (Huffman Coding)</div>
        <div class="footer-links">
            <span id="footerDbName" style="color: var(--accent-cyan); font-family: var(--font-mono);">Database Connected</span>
        </div>
    </footer>

    <!-- ==========================================
         AUTHENTICATION MODAL DIALOG
         ========================================== -->
    <div class="modal-overlay" id="authModalOverlay">
        <div class="auth-modal-dialog">
            
            <!-- Left Side: Visual Asset & Headline -->
            <div class="auth-banner-side">
                <img src="assets/img/shield_vault.jpg" alt="File Vault" class="auth-banner-img">
                <div class="auth-banner-overlay"></div>
                <div class="auth-banner-content">
                    <div class="auth-banner-title">File Compression</div>
                    <div class="auth-banner-sub">Lossless file compression and restoration using Huffman prefix coding.</div>
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
    <script src="assets/js/app.js"></script>
</body>
</html>
