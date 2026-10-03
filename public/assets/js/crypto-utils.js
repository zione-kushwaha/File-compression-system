/**
 * Secure File Compression System - Cryptographic & Formatting Utilities
 */

const CryptoUtils = {
    /**
     * Evaluates password strength and entropy.
     */
    evaluatePasswordStrength(password) {
        if (!password) {
            return { score: 0, label: 'Empty', color: '#64748b', percent: 0 };
        }

        let charsetSize = 0;
        if (/[a-z]/.test(password)) charsetSize += 26;
        if (/[A-Z]/.test(password)) charsetSize += 26;
        if (/[0-9]/.test(password)) charsetSize += 10;
        if (/[^a-zA-Z0-9]/.test(password)) charsetSize += 32;

        const length = password.length;
        // Entropy = L * log2(R)
        const entropy = length * (Math.log(charsetSize || 1) / Math.log(2));

        if (entropy < 30) {
            return { score: 1, label: 'Weak (Vulnerable)', color: '#f43f5e', percent: 25 };
        } else if (entropy < 50) {
            return { score: 2, label: 'Moderate', color: '#f59e0b', percent: 50 };
        } else if (entropy < 75) {
            return { score: 3, label: 'Strong', color: '#06b6d4', percent: 75 };
        } else {
            return { score: 4, label: 'Military-Grade (AES-256 Optimal)', color: '#10b981', percent: 100 };
        }
    },

    /**
     * Formats bytes into human-readable strings (KB, MB, GB).
     */
    formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    },

    /**
     * Computes client-side SHA-256 checksum of a File object using WebCrypto API.
     */
    async computeFileHash(file) {
        const buffer = await file.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }
};

window.CryptoUtils = CryptoUtils;
