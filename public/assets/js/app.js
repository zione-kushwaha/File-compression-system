/**
 * File Compression System - Primary Frontend Application Controller
 * Lossless Huffman Coding Algorithm Implementation
 */

document.addEventListener('DOMContentLoaded', () => {
    // State
    let isAuthenticated = false;
    let selectedCompressFile = null;
    let selectedDecompressFile = null;

    // DOM Elements - Navigation Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetTab = btn.getAttribute('data-tab');
            tabButtons.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            const targetContent = document.getElementById(targetTab);
            if (targetContent) targetContent.classList.add('active');

            if (targetTab === 'vaultTab') {
                loadVaultFiles();
            } else if (targetTab === 'logsTab') {
                loadAuditLogs();
            }
        });
    });

    // -------------------------------------------------------------
    // Compression Tab Handling (Huffman Coding)
    // -------------------------------------------------------------
    const dropzoneCompress = document.getElementById('dropzoneCompress');
    const fileInputCompress = document.getElementById('fileInputCompress');
    const selectedFileCard = document.getElementById('selectedFileCard');
    const fileNamePreview = document.getElementById('fileNamePreview');
    const fileSizePreview = document.getElementById('fileSizePreview');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const btnCompressSubmit = document.getElementById('btnCompressSubmit');
    const compressSpinner = document.getElementById('compressSpinner');
    const compressBtnText = document.getElementById('compressBtnText');

    // Drag and drop for compress (Gated by Authentication)
    if (dropzoneCompress && fileInputCompress) {
        dropzoneCompress.addEventListener('click', () => {
            if (!isAuthenticated) {
                showToast('🔒 Please sign in to compress files.', 'info');
                document.getElementById('authModalOverlay')?.classList.add('active');
                return;
            }
            fileInputCompress.click();
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzoneCompress.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneCompress.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzoneCompress.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneCompress.classList.remove('dragover');
            });
        });

        dropzoneCompress.addEventListener('drop', (e) => {
            if (!isAuthenticated) {
                showToast('🔒 Please sign in to compress files.', 'info');
                document.getElementById('authModalOverlay')?.classList.add('active');
                return;
            }
            if (e.dataTransfer.files.length > 0) {
                handleCompressFileSelect(e.dataTransfer.files[0]);
            }
        });

        fileInputCompress.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleCompressFileSelect(e.target.files[0]);
            }
        });
    }

    function handleCompressFileSelect(file) {
        selectedCompressFile = file;
        fileNamePreview.textContent = file.name;
        fileSizePreview.textContent = CryptoUtils.formatBytes(file.size);
        selectedFileCard.classList.add('active');
        btnCompressSubmit.disabled = false;
        dropzoneCompress.style.display = 'none';
    }

    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', () => {
            selectedCompressFile = null;
            selectedFileCard.classList.remove('active');
            dropzoneCompress.style.display = 'block';
            fileInputCompress.value = '';
            btnCompressSubmit.disabled = true;
            document.getElementById('resultCardCompress').classList.remove('active');
        });
    }

    // Submit Compression Request
    if (btnCompressSubmit) {
        btnCompressSubmit.addEventListener('click', async () => {
            if (!selectedCompressFile) return;

            btnCompressSubmit.disabled = true;
            compressSpinner.style.display = 'inline-block';
            compressBtnText.textContent = 'Compressing (Huffman)...';

            const formData = new FormData();
            formData.append('file', selectedCompressFile);
            formData.append('algorithm', 'huffman');

            try {
                const response = await fetch('api.php?action=compress', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (data.requireAuth) {
                        showToast('🔒 Please sign in to compress files.', 'info');
                        document.getElementById('authModalOverlay')?.classList.add('active');
                        return;
                    }
                    throw new Error(data.error || 'Compression failed.');
                }

                showToast('File compressed successfully with Huffman Coding!', 'success');
                displayCompressionResult(data);

            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btnCompressSubmit.disabled = false;
                compressSpinner.style.display = 'none';
                compressBtnText.textContent = 'Compress File (Huffman)';
            }
        });
    }

    function displayCompressionResult(data) {
        const card = document.getElementById('resultCardCompress');
        if (!card) return;

        const origSizeEl = document.getElementById('resOriginalSize');
        const compSizeEl = document.getElementById('resCompressedSize');
        const ratioEl = document.getElementById('resRatio');
        const hashEl = document.getElementById('resChecksum');
        const downloadBtn = document.getElementById('resDownloadBtn');

        origSizeEl.textContent = CryptoUtils.formatBytes(data.file.originalSize);
        compSizeEl.textContent = CryptoUtils.formatBytes(data.file.compressedSize);
        ratioEl.textContent = (data.file.compressionRatio >= 0 ? '+' : '') + data.file.compressionRatio + '%';
        
        ratioEl.className = 'stat-val ' + (data.file.compressionRatio > 0 ? 'ratio-positive' : 'ratio-neutral');
        hashEl.textContent = data.file.sha256Checksum;
        downloadBtn.href = data.downloadUrl;

        // Educational note for pre-compressed files
        let tipEl = document.getElementById('resEntropyTip');
        if (!tipEl) {
            tipEl = document.createElement('div');
            tipEl.id = 'resEntropyTip';
            tipEl.style.cssText = 'margin-top: 1rem; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.775rem; line-height: 1.5; display: flex; align-items: flex-start; gap: 0.5rem;';
            card.appendChild(tipEl);
        }

        if (data.file.compressionRatio < 0) {
            tipEl.style.display = 'flex';
            tipEl.style.background = 'rgba(245, 158, 11, 0.1)';
            tipEl.style.border = '1px solid rgba(245, 158, 11, 0.3)';
            tipEl.style.color = '#fbbf24';
            const ext = data.file.originalName.split('.').pop().toUpperCase();
            tipEl.innerHTML = `<span>💡</span><div><strong>Pre-compressed file (${ext}):</strong> High-entropy formats (PDF/JPG/ZIP) are already compressed. Adding Huffman's frequency header results in slight overhead. Uncompressed text/code files achieve significant size reduction.</div>`;
        } else {
            tipEl.style.display = 'none';
        }

        card.classList.add('active');
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // -------------------------------------------------------------
    // Decompression Tab Handling
    // -------------------------------------------------------------
    const dropzoneDecompress = document.getElementById('dropzoneDecompress');
    const fileInputDecompress = document.getElementById('fileInputDecompress');
    const decompressCard = document.getElementById('decompressCard');
    const decompressFileName = document.getElementById('decompressFileName');
    const decompressFileSize = document.getElementById('decompressFileSize');
    const removeDecompressFileBtn = document.getElementById('removeDecompressFileBtn');
    const btnDecompressSubmit = document.getElementById('btnDecompressSubmit');
    const decompressSpinner = document.getElementById('decompressSpinner');
    const decompressBtnText = document.getElementById('decompressBtnText');

    if (dropzoneDecompress && fileInputDecompress) {
        dropzoneDecompress.addEventListener('click', () => {
            if (!isAuthenticated) {
                showToast('🔒 Please sign in to decompress files.', 'info');
                document.getElementById('authModalOverlay')?.classList.add('active');
                return;
            }
            fileInputDecompress.click();
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzoneDecompress.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneDecompress.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzoneDecompress.addEventListener(eventName, (e) => {
                e.preventDefault();
                dropzoneDecompress.classList.remove('dragover');
            });
        });

        dropzoneDecompress.addEventListener('drop', (e) => {
            if (!isAuthenticated) {
                showToast('🔒 Please sign in to decompress files.', 'info');
                document.getElementById('authModalOverlay')?.classList.add('active');
                return;
            }
            if (e.dataTransfer.files.length > 0) {
                handleDecompressFileSelect(e.dataTransfer.files[0]);
            }
        });

        fileInputDecompress.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleDecompressFileSelect(e.target.files[0]);
            }
        });
    }

    function handleDecompressFileSelect(file) {
        selectedDecompressFile = file;
        decompressFileName.textContent = file.name;
        decompressFileSize.textContent = CryptoUtils.formatBytes(file.size);
        decompressCard.classList.add('active');
        btnDecompressSubmit.disabled = false;
        dropzoneDecompress.style.display = 'none';
    }

    if (removeDecompressFileBtn) {
        removeDecompressFileBtn.addEventListener('click', () => {
            selectedDecompressFile = null;
            decompressCard.classList.remove('active');
            dropzoneDecompress.style.display = 'block';
            fileInputDecompress.value = '';
            btnDecompressSubmit.disabled = true;
            document.getElementById('resultCardDecompress').classList.remove('active');
        });
    }

    if (btnDecompressSubmit) {
        btnDecompressSubmit.addEventListener('click', async () => {
            if (!selectedDecompressFile) return;

            btnDecompressSubmit.disabled = true;
            decompressSpinner.style.display = 'inline-block';
            decompressBtnText.textContent = 'Restoring File...';

            const formData = new FormData();
            formData.append('file', selectedDecompressFile);

            try {
                const response = await fetch('api.php?action=decompress', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Decompression failed.');
                }

                showToast('File restored and SHA-256 verified successfully!', 'success');
                displayDecompressResult(data);

            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btnDecompressSubmit.disabled = false;
                decompressSpinner.style.display = 'none';
                decompressBtnText.textContent = 'Decompress & Restore File';
            }
        });
    }

    function displayDecompressResult(data) {
        const card = document.getElementById('resultCardDecompress');
        if (!card) return;

        const nameEl = document.getElementById('resDecName');
        const sizeEl = document.getElementById('resDecSize');
        const algoEl = document.getElementById('resDecAlgo');
        const hashEl = document.getElementById('resDecHash');
        const downloadBtn = document.getElementById('resDecDownloadBtn');

        nameEl.textContent = data.originalName;
        sizeEl.textContent = CryptoUtils.formatBytes(data.restoredSize);
        algoEl.textContent = data.algorithm;
        hashEl.textContent = data.sha256Checksum;
        downloadBtn.href = data.downloadUrl;

        card.classList.add('active');
        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // -------------------------------------------------------------
    // My Files Table Handling
    // -------------------------------------------------------------
    async function loadVaultFiles() {
        const tbody = document.getElementById('vaultTableBody');
        if (!tbody) return;

        if (!isAuthenticated) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim); font-size:0.9rem;">🔒 Please sign in with the Demo account to access your saved files.</td></tr>`;
            return;
        }

        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading files...</td></tr>`;

        try {
            const resp = await fetch('api.php?action=list');
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                if (data.requireAuth) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim);">🔒 Sign in required to view files.</td></tr>`;
                    return;
                }
                throw new Error(data.error || 'Failed to fetch files');
            }

            const dbBadge = document.getElementById('dbStatusBadge');
            if (dbBadge && data.database) {
                dbBadge.textContent = data.database.toUpperCase();
            }

            if (data.files.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim);">No compressed files found. Compress a file to get started!</td></tr>`;
                return;
            }

            tbody.innerHTML = data.files.map(f => `
                <tr>
                    <td>
                        <div style="font-weight:600; color:var(--text-main);">${escapeHtml(f.originalName)}</div>
                        <div style="font-size:0.7rem; color:var(--text-dim); font-family:var(--font-mono);">${f.storedName}</div>
                    </td>
                    <td>
                        <span class="tag-algo">HUFFMAN</span>
                    </td>
                    <td>${CryptoUtils.formatBytes(f.originalSize)}</td>
                    <td>${CryptoUtils.formatBytes(f.compressedSize)}</td>
                    <td>
                        <strong style="color:${f.compressionRatio > 0 ? 'var(--accent-emerald)' : 'var(--accent-cyan)'}">
                            ${f.compressionRatio > 0 ? '+' : ''}${f.compressionRatio}%
                        </strong>
                    </td>
                    <td>
                        <div style="display:flex; gap:0.5rem;">
                            <a href="api.php?action=download&id=${f.id}" class="ctrl-btn" title="Download Archive">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            </a>
                            <button onclick="window.deleteVaultItem(${f.id})" class="ctrl-btn" style="color:var(--accent-rose);" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--accent-rose);">Error: ${err.message}</td></tr>`;
        }
    }

    window.deleteVaultItem = async function(id) {
        if (!confirm('Are you sure you want to permanently delete this file record?')) return;
        try {
            const formData = new FormData();
            formData.append('id', id);
            const resp = await fetch('api.php?action=delete', { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                showToast('File deleted successfully.', 'success');
                loadVaultFiles();
            } else {
                throw new Error(data.error || 'Failed to delete');
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    };

    // -------------------------------------------------------------
    // Activity Logs Handling
    // -------------------------------------------------------------
    async function loadAuditLogs() {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;

        if (!isAuthenticated) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim); font-size:0.9rem;">🔒 Please sign in to view activity logs.</td></tr>`;
            return;
        }

        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading activity trail...</td></tr>`;

        try {
            const resp = await fetch('api.php?action=logs');
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                if (data.requireAuth) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim);">🔒 Sign in required to view activity logs.</td></tr>`;
                    return;
                }
                throw new Error(data.error || 'Failed to load logs');
            }

            if (data.logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2.5rem; color:var(--text-dim);">No activity logs yet.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.logs.map(log => `
                <tr>
                    <td style="font-family:var(--font-mono); font-size:0.8rem; color:var(--text-dim);">${log.timestamp}</td>
                    <td>
                        <span class="badge-action ${log.action.includes('error') ? 'badge-err' : ''}">${escapeHtml(log.action)}</span>
                    </td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(log.targetFile || '-')}</td>
                    <td>
                        <span style="color:${log.status === 'success' ? 'var(--accent-emerald)' : 'var(--accent-rose)'}; font-weight:600; text-transform:uppercase; font-size:0.75rem;">
                            ${escapeHtml(log.status)}
                        </span>
                    </td>
                    <td style="font-family:var(--font-mono); font-size:0.8rem;">${escapeHtml(log.ipAddress || '127.0.0.1')}</td>
                    <td style="font-size:0.825rem; color:var(--text-dim); max-width:240px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(log.details || '-')}</td>
                </tr>
            `).join('');

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--accent-rose);">Error: ${err.message}</td></tr>`;
        }
    }

    // -------------------------------------------------------------
    // Authentication Handling (Sign In / Register / Session)
    // -------------------------------------------------------------
    const authModalOverlay = document.getElementById('authModalOverlay');
    const btnOpenAuthModal = document.getElementById('btnOpenAuthModal');
    const btnBannerOpenAuth = document.getElementById('btnBannerOpenAuth');
    const btnQuickDemoLogin = document.getElementById('btnQuickDemoLogin');
    const btnCloseAuthModal = document.getElementById('btnCloseAuthModal');
    const btnSwitchSignIn = document.getElementById('btnSwitchSignIn');
    const btnSwitchSignUp = document.getElementById('btnSwitchSignUp');
    const formSignIn = document.getElementById('formSignIn');
    const formSignUp = document.getElementById('formSignUp');
    const userProfileBadge = document.getElementById('userProfileBadge');
    const navUsername = document.getElementById('navUsername');
    const userAvatarText = document.getElementById('userAvatarText');
    const btnLogout = document.getElementById('btnLogout');
    const btnFillDemoUser = document.getElementById('btnFillDemoUser');
    const systemLockBanner = document.getElementById('systemLockBanner');

    function updateAuthStateUI(user) {
        if (user) {
            isAuthenticated = true;
            btnOpenAuthModal.style.display = 'none';
            userProfileBadge.style.display = 'flex';
            navUsername.textContent = user.username;
            userAvatarText.textContent = user.username.charAt(0).toUpperCase();
            if (systemLockBanner) systemLockBanner.style.display = 'none';
        } else {
            isAuthenticated = false;
            btnOpenAuthModal.style.display = 'inline-flex';
            userProfileBadge.style.display = 'none';
            if (systemLockBanner) systemLockBanner.style.display = 'flex';
        }
    }

    async function checkAuthStatus() {
        try {
            const resp = await fetch('api.php?action=auth_status');
            const data = await resp.json();
            if (data.authenticated && data.user) {
                updateAuthStateUI(data.user);
            } else {
                updateAuthStateUI(null);
            }
        } catch {
            updateAuthStateUI(null);
        }
    }

    if (btnOpenAuthModal) {
        btnOpenAuthModal.addEventListener('click', () => {
            authModalOverlay.classList.add('active');
        });
    }

    if (btnBannerOpenAuth) {
        btnBannerOpenAuth.addEventListener('click', () => {
            authModalOverlay.classList.add('active');
        });
    }

    if (btnCloseAuthModal) {
        btnCloseAuthModal.addEventListener('click', () => {
            authModalOverlay.classList.remove('active');
        });
    }

    authModalOverlay.addEventListener('click', (e) => {
        if (e.target === authModalOverlay) {
            authModalOverlay.classList.remove('active');
        }
    });

    btnSwitchSignIn.addEventListener('click', () => {
        btnSwitchSignIn.classList.add('active');
        btnSwitchSignUp.classList.remove('active');
        formSignIn.classList.add('active');
        formSignUp.classList.remove('active');
    });

    btnSwitchSignUp.addEventListener('click', () => {
        btnSwitchSignUp.classList.add('active');
        btnSwitchSignIn.classList.remove('active');
        formSignUp.classList.add('active');
        formSignIn.classList.remove('active');
    });

    if (btnFillDemoUser) {
        btnFillDemoUser.addEventListener('click', () => {
            document.getElementById('loginIdentifier').value = 'demo_user';
            document.getElementById('loginPassword').value = 'Admin@123';
            showToast('Demo credentials filled!', 'info');
        });
    }

    async function performDemoLogin() {
        const formData = new FormData();
        formData.append('identifier', 'demo_user');
        formData.append('password', 'Admin@123');

        try {
            const resp = await fetch('api.php?action=login', { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success) {
                showToast(`Welcome, ${data.user.username}!`, 'success');
                updateAuthStateUI(data.user);
                authModalOverlay.classList.remove('active');
            } else {
                showToast(data.error || 'Demo login failed.', 'error');
            }
        } catch {
            showToast('Connection error during demo login.', 'error');
        }
    }

    if (btnQuickDemoLogin) {
        btnQuickDemoLogin.addEventListener('click', performDemoLogin);
    }

    // Sign In Submit
    formSignIn.addEventListener('submit', async (e) => {
        e.preventDefault();
        const ident = document.getElementById('loginIdentifier').value.trim();
        const pwd = document.getElementById('loginPassword').value;

        const btn = document.getElementById('btnLoginSubmit');
        const spinner = document.getElementById('loginSpinner');
        const txt = document.getElementById('loginBtnText');

        btn.disabled = true;
        spinner.style.display = 'inline-block';
        txt.textContent = 'Authenticating...';

        const formData = new FormData();
        formData.append('identifier', ident);
        formData.append('password', pwd);

        try {
            const resp = await fetch('api.php?action=login', { method: 'POST', body: formData });
            const data = await resp.json();

            if (data.success) {
                showToast(`Welcome back, ${data.user.username}!`, 'success');
                updateAuthStateUI(data.user);
                authModalOverlay.classList.remove('active');
            } else {
                showToast(data.error || 'Login failed.', 'error');
            }
        } catch {
            showToast('Connection error during login.', 'error');
        } finally {
            btn.disabled = false;
            spinner.style.display = 'none';
            txt.textContent = 'Sign In';
        }
    });

    // Sign Up Submit
    formSignUp.addEventListener('submit', async (e) => {
        e.preventDefault();
        const user = document.getElementById('regUsername').value.trim();
        const email = document.getElementById('regEmail').value.trim();
        const pwd = document.getElementById('regPassword').value;

        const btn = document.getElementById('btnRegisterSubmit');
        const spinner = document.getElementById('regSpinner');
        const txt = document.getElementById('regBtnText');

        btn.disabled = true;
        spinner.style.display = 'inline-block';
        txt.textContent = 'Creating Account...';

        const formData = new FormData();
        formData.append('username', user);
        formData.append('email', email);
        formData.append('password', pwd);

        try {
            const resp = await fetch('api.php?action=register', { method: 'POST', body: formData });
            const data = await resp.json();

            if (data.success) {
                showToast(`Account created! Welcome, ${data.user.username}!`, 'success');
                updateAuthStateUI(data.user);
                authModalOverlay.classList.remove('active');
            } else {
                showToast(data.error || 'Registration failed.', 'error');
            }
        } catch {
            showToast('Connection error during registration.', 'error');
        } finally {
            btn.disabled = false;
            spinner.style.display = 'none';
            txt.textContent = 'Create Account';
        }
    });

    // Logout
    if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
            try {
                await fetch('api.php?action=logout', { method: 'POST' });
                showToast('Logged out successfully.', 'info');
                updateAuthStateUI(null);
            } catch {
                updateAuthStateUI(null);
            }
        });
    }

    // Password visibility toggle helpers
    setupTogglePassword('btnToggleLoginPwd', 'loginPassword');
    setupTogglePassword('btnToggleRegPwd', 'regPassword');

    function setupTogglePassword(btnId, inputId) {
        const btn = document.getElementById(btnId);
        const input = document.getElementById(inputId);
        if (btn && input) {
            btn.addEventListener('click', () => {
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        }
    }

    // Initialize
    checkAuthStatus();
});

// Toast notification helper
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = 'ℹ️';
    if (type === 'success') icon = '✅';
    if (type === 'error') icon = '⚠️';

    toast.innerHTML = `
        <span style="font-size:1.1rem;">${icon}</span>
        <div style="flex:1;">${escapeHtml(message)}</div>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
