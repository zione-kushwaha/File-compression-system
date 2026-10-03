/**
 * Secure File Compression System - Interactive Huffman Tree Visualizer & Metrics
 */

class HuffmanVisualizer {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.zoomLevel = 1.0;
        this.currentTree = null;
    }

    render(treeData, codebook, stats) {
        this.currentTree = treeData;
        if (!this.container) return;

        if (!treeData) {
            this.container.innerHTML = `
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--text-dim); gap:0.5rem;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="5" r="3"/><circle cx="5" cy="19" r="3"/><circle cx="19" cy="19" r="3"/>
                        <line x1="12" y1="8" x2="5" y2="16"/><line x1="12" y1="8" x2="19" y2="16"/>
                    </svg>
                    <span>Compress a file using Huffman Coding to explore the live binary tree.</span>
                </div>
            `;
            return;
        }

        // Layout the tree
        const nodeRadius = 18;
        const levelHeight = 65;
        
        // Calculate tree dimensions
        let maxDepth = 0;
        let leafCount = 0;

        function traverse(node, depth) {
            if (!node) return;
            if (depth > maxDepth) maxDepth = depth;
            if (node.isLeaf) {
                leafCount++;
                return;
            }
            traverse(node.left, depth + 1);
            traverse(node.right, depth + 1);
        }
        traverse(treeData, 0);

        const svgWidth = Math.max(800, leafCount * 55);
        const svgHeight = Math.max(400, (maxDepth + 2) * levelHeight);

        // Position nodes
        let nextLeafX = 40;
        function positionNodes(node, depth) {
            if (!node) return;
            node.y = (depth + 1) * levelHeight;

            if (node.isLeaf) {
                node.x = nextLeafX;
                nextLeafX += 50;
            } else {
                positionNodes(node.left, depth + 1);
                positionNodes(node.right, depth + 1);
                const leftX = node.left ? node.left.x : node.right.x;
                const rightX = node.right ? node.right.x : node.left.x;
                node.x = (leftX + rightX) / 2;
            }
        }
        positionNodes(treeData, 0);

        // Build SVG Elements
        let linesHtml = '';
        let nodesHtml = '';

        function drawEdgesAndNodes(node) {
            if (!node) return;

            if (node.left) {
                linesHtml += `
                    <line class="tree-link" x1="${node.x}" y1="${node.y}" x2="${node.left.x}" y2="${node.left.y}"/>
                    <text class="tree-edge-label" x="${(node.x + node.left.x) / 2 - 8}" y="${(node.y + node.left.y) / 2}">0</text>
                `;
                drawEdgesAndNodes(node.left);
            }

            if (node.right) {
                linesHtml += `
                    <line class="tree-link" x1="${node.x}" y1="${node.y}" x2="${node.right.x}" y2="${node.right.y}"/>
                    <text class="tree-edge-label" x="${(node.x + node.right.x) / 2 + 8}" y="${(node.y + node.right.y) / 2}">1</text>
                `;
                drawEdgesAndNodes(node.right);
            }

            const isLeaf = node.isLeaf;
            const circleClass = isLeaf ? 'tree-node-circle tree-node-leaf' : 'tree-node-circle';
            const label = isLeaf ? (node.char || '0x' + node.byte) : node.frequency;

            nodesHtml += `
                <g class="tree-node-group" data-freq="${node.frequency}" data-char="${node.char || ''}">
                    <circle class="${circleClass}" cx="${node.x}" cy="${node.y}" r="${nodeRadius}">
                        <title>${isLeaf ? `Symbol: ${node.char}\nFrequency: ${node.frequency}` : `Subtree Frequency: ${node.frequency}`}</title>
                    </circle>
                    <text class="tree-node-text" x="${node.x}" y="${node.y}">${label}</text>
                </g>
            `;
        }
        drawEdgesAndNodes(treeData);

        this.container.innerHTML = `
            <svg id="huffmanSvg" width="${svgWidth}" height="${svgHeight}" style="transform-origin: 0 0; transform: scale(${this.zoomLevel});">
                <g id="svgViewport">
                    ${linesHtml}
                    ${nodesHtml}
                </g>
            </svg>
        `;

        if (codebook) {
            this.renderCodebookTable(codebook);
        }

        if (stats) {
            this.renderEntropyMetrics(stats);
        }
    }

    renderCodebookTable(codebook) {
        const tbody = document.getElementById('codebookTableBody');
        if (!tbody) return;

        if (!codebook || codebook.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-dim); padding:2rem;">No codebook generated yet.</td></tr>`;
            return;
        }

        tbody.innerHTML = codebook.map(row => `
            <tr>
                <td><span class="symbol-badge">${this.escapeHtml(row.char)}</span></td>
                <td style="font-family:var(--font-mono); color:var(--text-dim);">${row.byte}</td>
                <td><strong>${row.frequency}</strong></td>
                <td>${row.probability}%</td>
                <td><span class="code-badge">${row.code}</span></td>
                <td><span style="color:${row.length < 8 ? 'var(--accent-emerald)' : 'var(--text-muted)'}">${row.length} bits</span></td>
            </tr>
        `).join('');
    }

    renderEntropyMetrics(stats) {
        const entropyVal = document.getElementById('entropyVal');
        const avgCodeLenVal = document.getElementById('avgCodeLenVal');
        const efficiencyVal = document.getElementById('efficiencyVal');
        const efficiencyBar = document.getElementById('efficiencyProgressBar');

        if (entropyVal) entropyVal.textContent = stats.entropy + ' bits/symbol';
        if (avgCodeLenVal) avgCodeLenVal.textContent = stats.avgCodeLength + ' bits/symbol';
        if (efficiencyVal) efficiencyVal.textContent = stats.efficiency + '%';
        if (efficiencyBar) efficiencyBar.style.width = Math.min(stats.efficiency, 100) + '%';
    }

    zoom(delta) {
        this.zoomLevel = Math.max(0.4, Math.min(2.5, this.zoomLevel + delta));
        const svg = document.getElementById('huffmanSvg');
        if (svg) {
            svg.style.transform = `scale(${this.zoomLevel})`;
        }
    }

    resetZoom() {
        this.zoomLevel = 1.0;
        const svg = document.getElementById('huffmanSvg');
        if (svg) {
            svg.style.transform = `scale(1)`;
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

window.HuffmanVisualizer = HuffmanVisualizer;
