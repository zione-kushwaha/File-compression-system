/**
 * Secure File Compression System - Primary Frontend Application Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // State
    let isAuthenticated = false;
    let selectedCompressFile = null;
    let selectedDecompressFile = null;
    let selectedAlgorithm = 'huffman';
    let isEncryptionEnabled = false;

    // Initialize visualizer
    const visualizer = new HuffmanVisualizer('svgCanvasContainer');

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
    // Compression Tab Handling
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

    // Algorithm cards
    const algoCards = document.querySelectorAll('.algo-card');
    algoCards.forEach(card => {
        card.addEventListener('click', () => {
            algoCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedAlgorithm = card.getAttribute('data-algo');
        });
    });

    // Encryption toggle & Password Strength
    const encryptToggle = document.getElementById('encryptToggle');
    const passwordBox = document.getElementById('passwordBox');
    const compressPasswordInput = document.getElementById('compressPasswordInput');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');
    const togglePwdBtn = document.getElementById('togglePwdBtn');

    if (encryptToggle) {
        encryptToggle.addEventListener('change', () => {
            isEncryptionEnabled = encryptToggle.checked;
            if (isEncryptionEnabled) {
                passwordBox.classList.add('active');
                compressPasswordInput.focus();
            } else {
                passwordBox.classList.remove('active');
                compressPasswordInput.value = '';
                updateStrengthMeter('');
            }
        });
    }

    if (compressPasswordInput) {
        compressPasswordInput.addEventListener('input', (e) => {
            updateStrengthMeter(e.target.value);
        });
    }

    function updateStrengthMeter(pwd) {
        const result = CryptoUtils.evaluatePasswordStrength(pwd);
        strengthFill.style.width = result.percent + '%';
        strengthFill.style.backgroundColor = result.color;
        strengthLabel.textContent = `Strength: ${result.label}`;
        strengthLabel.style.color = result.color;
    }

    if (togglePwdBtn) {
        togglePwdBtn.addEventListener('click', () => {
            const isPassword = compressPasswordInput.type === 'password';
            compressPasswordInput.type = isPassword ? 'text' : 'password';
        });
    }

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
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });

        fileInputCompress.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });
    }

    async function handleFileSelect(file) {
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

    // Submit Compression Form
    if (btnCompressSubmit) {
        btnCompressSubmit.addEventListener('click', async () => {
            if (!selectedCompressFile) return;

            const password = isEncryptionEnabled ? compressPasswordInput.value.trim() : '';
            if (isEncryptionEnabled && !password) {
                showToast('Please enter an encryption password.', 'error');
                compressPasswordInput.focus();
                return;
            }

            // Start loading state
            btnCompressSubmit.disabled = true;
            compressSpinner.style.display = 'inline-block';
            compressBtnText.textContent = 'Compressing & Securing...';

            const formData = new FormData();
            formData.append('file', selectedCompressFile);
            formData.append('algorithm', selectedAlgorithm);
            formData.append('password', password);

            try {
                const response = await fetch('api.php?action=compress', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Compression failed on server.');
                }

                showToast('File compressed & secured successfully!', 'success');
                displayCompressionResult(data);

                // If huffman, render tree & codebook
                if (data.tree) {
                    visualizer.render(data.tree, data.codebook, data.stats);
                }

            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btnCompressSubmit.disabled = false;
                compressSpinner.style.display = 'none';
                compressBtnText.textContent = 'Compress & Secure File';
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
        const viewTreeBtn = document.getElementById('resViewTreeBtn');

        origSizeEl.textContent = CryptoUtils.formatBytes(data.file.originalSize);
        compSizeEl.textContent = CryptoUtils.formatBytes(data.file.compressedSize);
        ratioEl.textContent = (data.file.compressionRatio >= 0 ? '+' : '') + data.file.compressionRatio + '%';
        
        ratioEl.className = 'stat-val ' + (data.file.compressionRatio > 0 ? 'ratio-positive' : 'ratio-neutral');
        hashEl.textContent = data.file.sha256Checksum;
        downloadBtn.href = data.downloadUrl;

        if (data.file.algorithm === 'huffman') {
            viewTreeBtn.style.display = 'inline-flex';
            viewTreeBtn.onclick = () => {
                document.querySelector('.tab-btn[data-tab="visualizerTab"]').click();
            };
        } else {
            viewTreeBtn.style.display = 'none';
        }

        // Intelligent Educational Tip for Pre-compressed files
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
            tipEl.innerHTML = `<span>💡</span><div><strong>Pre-compressed file (${ext}):</strong> PDFs, JPGs, and ZIPs are already compressed. Applying Huffman adds header metadata (Shannon Entropy Limit). Try a <code>.txt</code>, <code>.csv</code>, or code file to see 30%–60% size reduction!</div>`;
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
    const decompressPasswordInput = document.getElementById('decompressPasswordInput');

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

            const password = decompressPasswordInput ? decompressPasswordInput.value.trim() : '';

            btnDecompressSubmit.disabled = true;
            decompressSpinner.style.display = 'inline-block';
            decompressBtnText.textContent = 'Decompressing & Verifying...';

            const formData = new FormData();
            formData.append('file', selectedDecompressFile);
            if (password) {
                formData.append('password', password);
            }

            try {
                const response = await fetch('api.php?action=decompress', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (data.isPasswordRequired) {
                        showToast(data.message, 'info');
                        const pwdGroup = document.getElementById('decompressPasswordGroup');
                        if (pwdGroup) {
                            pwdGroup.style.display = 'block';
                            decompressPasswordInput.focus();
                        }
                        return;
                    }
                    throw new Error(data.error || 'Decompression failed.');
                }

                showToast('File restored and SHA-256 integrity verified!', 'success');
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
    // Vault Management Table
    // -------------------------------------------------------------
    async function loadVaultFiles() {
        const tbody = document.getElementById('vaultTableBody');
        if (!tbody) return;

        if (!isAuthenticated) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2.5rem; color:var(--text-dim); font-size:0.9rem;">🔒 Vault is locked. Please sign in with the Demo account or register to access saved archives.</td></tr>`;
            return;
        }

        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--text-dim);">Loading vault records...</td></tr>`;

        try {
            const resp = await fetch('api.php?action=list');
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                if (data.requireAuth) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2.5rem; color:var(--text-dim);">🔒 Sign in required to view vault archives.</td></tr>`;
                    return;
                }
                throw new Error(data.error || 'Failed to fetch files');
            }

            // Update database status pill
            const dbBadge = document.getElementById('dbStatusBadge');
            if (dbBadge) {
                dbBadge.textContent = data.database.toUpperCase();
            }

            if (data.files.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2.5rem; color:var(--text-dim);">Vault is empty. Compress a file to get started.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.files.map(f => `
                <tr>
                    <td>
                        <div style="font-weight:600; color:var(--text-main);">${escapeHtml(f.originalName)}</div>
                        <div style="font-size:0.7rem; color:var(--text-dim); font-family:var(--font-mono);">${f.storedName}</div>
                    </td>
                    <td>
                        <span class="tag-algo">${f.algorithm.toUpperCase()}</span>
                    </td>
                    <td>
                        ${f.isEncrypted ? `<span class="tag-encrypted"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> AES-256</span>` : `<span class="tag-plain">None</span>`}
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
                            <button onclick="window.decompressVaultItem(${f.id}, ${f.isEncrypted})" class="ctrl-btn" title="Decompress">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                            </button>
                            <button onclick="window.deleteVaultItem(${f.id})" class="ctrl-btn" style="color:var(--accent-rose);" title="Delete">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:2rem; color:var(--accent-rose);">Error: ${err.message}</td></tr>`;
        }
    }

    window.deleteVaultItem = async function(id) {
        if (!confirm('Are you sure you want to permanently delete this file record and archive?')) return;
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

    window.decompressVaultItem = async function(id, isEncrypted) {
        let password = '';
        if (isEncrypted) {
            password = prompt('Enter AES-256 password for this archive:');
            if (password === null) return;
        }

        showToast('Decompressing file from vault...', 'info');

        const formData = new FormData();
        formData.append('file_id', id);
        if (password) formData.append('password', password);

        try {
            const resp = await fetch('api.php?action=decompress', { method: 'POST', body: formData });
            const data = await resp.json();

            if (!resp.ok || !data.success) {
                throw new Error(data.error || 'Decompression failed');
            }

            // Direct trigger download
            window.location.href = data.downloadUrl;
            showToast('Decompressed file downloaded!', 'success');
        } catch (err) {
            showToast(err.message, 'error');
        }
    };

    // -------------------------------------------------------------
    // Audit Logs
    // -------------------------------------------------------------
    async function loadAuditLogs() {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;

        try {
            const resp = await fetch('api.php?action=logs');
            const data = await resp.json();

            if (data.logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">No audit logs recorded yet.</td></tr>`;
                return;
            }

            tbody.innerHTML = data.logs.map(l => `
                <tr>
                    <td style="font-size:0.75rem; color:var(--text-dim); font-family:var(--font-mono);">${l.createdAt}</td>
                    <td><strong>${l.action}</strong></td>
                    <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(l.filename)}</td>
                    <td>
                        <span style="font-size:0.7rem; font-weight:700; color:${l.status === 'SUCCESS' ? 'var(--accent-emerald)' : 'var(--accent-rose)'};">
                            ${l.status}
                        </span>
                    </td>
                    <td style="font-size:0.75rem; color:var(--text-dim); font-family:var(--font-mono);">${l.ipAddress || '127.0.0.1'}</td>
                    <td style="font-size:0.75rem; color:var(--text-muted);">${escapeHtml(l.details || '')}</td>
                </tr>
            `).join('');

        } catch (err) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--accent-rose);">Error loading logs</td></tr>`;
        }
    }

    // Visualizer Controls
    const btnZoomIn = document.getElementById('btnZoomIn');
    const btnZoomOut = document.getElementById('btnZoomOut');
    const btnResetZoom = document.getElementById('btnResetZoom');

    if (btnZoomIn) btnZoomIn.addEventListener('click', () => visualizer.zoom(0.2));
    if (btnZoomOut) btnZoomOut.addEventListener('click', () => visualizer.zoom(-0.2));
    if (btnResetZoom) btnResetZoom.addEventListener('click', () => visualizer.resetZoom());

    // Copy Checksum button
    const copyChecksumBtn = document.getElementById('copyChecksumBtn');
    if (copyChecksumBtn) {
        copyChecksumBtn.addEventListener('click', () => {
            const text = document.getElementById('resChecksum').textContent;
            navigator.clipboard.writeText(text).then(() => {
                showToast('SHA-256 Checksum copied to clipboard!', 'info');
            });
        });
    }

    // Toast Utility
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span>${escapeHtml(message)}</span>
        `;

        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
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

    // -------------------------------------------------------------
    // Authentication Modal & State Handling
    // -------------------------------------------------------------
    const authModalOverlay = document.getElementById('authModalOverlay');
    const btnOpenAuthModal = document.getElementById('btnOpenAuthModal');
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

    // Password visibility toggles
    const btnToggleLoginPwd = document.getElementById('btnToggleLoginPwd');
    const loginPassword = document.getElementById('loginPassword');
    const btnToggleRegPwd = document.getElementById('btnToggleRegPwd');
    const regPassword = document.getElementById('regPassword');

    if (btnToggleLoginPwd && loginPassword) {
        btnToggleLoginPwd.addEventListener('click', () => {
            loginPassword.type = loginPassword.type === 'password' ? 'text' : 'password';
        });
    }

    if (btnToggleRegPwd && regPassword) {
        btnToggleRegPwd.addEventListener('click', () => {
            regPassword.type = regPassword.type === 'password' ? 'text' : 'password';
        });
    }

    if (btnOpenAuthModal) {
        btnOpenAuthModal.addEventListener('click', () => {
            authModalOverlay.classList.add('active');
        });
    }

    if (btnCloseAuthModal) {
        btnCloseAuthModal.addEventListener('click', () => {
            authModalOverlay.classList.remove('active');
        });
    }

    if (authModalOverlay) {
        authModalOverlay.addEventListener('click', (e) => {
            if (e.target === authModalOverlay) {
                authModalOverlay.classList.remove('active');
            }
        });
    }

    if (btnSwitchSignIn && btnSwitchSignUp) {
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
    }

    // Auto-fill demo credentials
    if (btnFillDemoUser) {
        btnFillDemoUser.addEventListener('click', () => {
            document.getElementById('loginIdentifier').value = 'demo_user';
            document.getElementById('loginPassword').value = 'Admin@123';
            showToast('Demo credentials filled!', 'info');
        });
    }

    // Sign In Submission
    if (formSignIn) {
        formSignIn.addEventListener('submit', async (e) => {
            e.preventDefault();
            const identifier = document.getElementById('loginIdentifier').value.trim();
            const password = document.getElementById('loginPassword').value;
            const spinner = document.getElementById('loginSpinner');
            const btnText = document.getElementById('loginBtnText');
            const submitBtn = document.getElementById('btnLoginSubmit');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Signing in...';

            try {
                const resp = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier, password })
                });

                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    throw new Error(data.error || 'Login failed.');
                }

                showToast(`Welcome back, ${data.user.username}!`, 'success');
                authModalOverlay.classList.remove('active');
                setAuthenticatedUI(data.user);
                loadVaultFiles();
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Sign In';
            }
        });
    }

    // Sign Up Submission
    if (formSignUp) {
        formSignUp.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('regUsername').value.trim();
            const email = document.getElementById('regEmail').value.trim();
            const password = document.getElementById('regPassword').value;
            const spinner = document.getElementById('regSpinner');
            const btnText = document.getElementById('regBtnText');
            const submitBtn = document.getElementById('btnRegisterSubmit');

            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            btnText.textContent = 'Creating account...';

            try {
                const resp = await fetch('api.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, email, password })
                });

                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    throw new Error(data.error || 'Registration failed.');
                }

                showToast(`Account created! Welcome, ${data.user.username}!`, 'success');
                authModalOverlay.classList.remove('active');
                setAuthenticatedUI(data.user);
                loadVaultFiles();
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
                btnText.textContent = 'Create Account';
            }
        });
    }

    // System Lock Banner & 1-Click Demo Login
    const systemLockBanner = document.getElementById('systemLockBanner');
    const btnQuickDemoLogin = document.getElementById('btnQuickDemoLogin');
    const btnBannerOpenAuth = document.getElementById('btnBannerOpenAuth');

    if (btnBannerOpenAuth) {
        btnBannerOpenAuth.addEventListener('click', () => {
            authModalOverlay?.classList.add('active');
        });
    }

    if (btnQuickDemoLogin) {
        btnQuickDemoLogin.addEventListener('click', async () => {
            btnQuickDemoLogin.disabled = true;
            btnQuickDemoLogin.innerHTML = '<span>Signing In...</span>';
            try {
                const resp = await fetch('api.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier: 'demo_user', password: 'Admin@123' })
                });
                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    throw new Error(data.error || 'Demo login failed');
                }
                showToast(`Welcome back, ${data.user.username}! System Unlocked.`, 'success');
                setAuthenticatedUI(data.user);
                loadVaultFiles();
            } catch (err) {
                showToast(err.message, 'error');
            } finally {
                btnQuickDemoLogin.disabled = false;
                btnQuickDemoLogin.innerHTML = '<span>⚡ 1-Click Demo Login</span>';
            }
        });
    }

    // Logout
    if (btnLogout) {
        btnLogout.addEventListener('click', async () => {
            try {
                await fetch('api.php?action=logout', { method: 'POST' });
                showToast('Signed out successfully. System locked.', 'info');
                setUnauthenticatedUI();
                loadVaultFiles();
            } catch (err) {
                showToast(err.message, 'error');
            }
        });
    }

    async function checkAuthStatus() {
        try {
            const resp = await fetch('api.php?action=me');
            const data = await resp.json();
            if (data.authenticated && data.user) {
                setAuthenticatedUI(data.user);
            } else {
                setUnauthenticatedUI();
            }
        } catch (e) {
            setUnauthenticatedUI();
        }
    }

    function setAuthenticatedUI(user) {
        isAuthenticated = true;
        if (systemLockBanner) systemLockBanner.style.display = 'none';
        if (btnOpenAuthModal) btnOpenAuthModal.style.display = 'none';
        if (userProfileBadge) {
            userProfileBadge.classList.add('active');
            if (navUsername) navUsername.textContent = user.username;
            if (userAvatarText) userAvatarText.textContent = user.username.charAt(0).toUpperCase();
        }
    }

    function setUnauthenticatedUI() {
        isAuthenticated = false;
        if (systemLockBanner) systemLockBanner.style.display = 'flex';
        if (btnOpenAuthModal) btnOpenAuthModal.style.display = 'inline-flex';
        if (userProfileBadge) userProfileBadge.classList.remove('active');
    }

    // Initial load
    checkAuthStatus();
    loadVaultFiles();
});
