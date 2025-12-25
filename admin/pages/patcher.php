<?php
// Admin Smart Patcher page - Consolidated Patcher Tool
$pageTitle = 'Smart Patcher';
$pageSubtitle = 'Safely edit code with backups and diff preview.';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requirePermission('patcher');

// Verify admin session for patcher functionality
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Patcher - Code Repair Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <!-- CodeMirror CSS & JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <!-- Load modes in correct order: XML must be before PHP/HTML -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/json/json.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1e1e1e;
            color: #e0e0e0;
        }
        
        .editor-wrapper {
            display: flex;
            height: calc(100vh - 120px);
            gap: 1px;
            background: #2d2d2d;
        }
        
        .sidebar {
            width: 280px;
            background: #252526;
            border-right: 1px solid #3e3e42;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            padding: 16px;
            background: #2d2d30;
            border-bottom: 1px solid #3e3e42;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .sidebar-actions {
            display: flex;
            gap: 8px;
            padding: 8px;
            background: #2d2d30;
            border-bottom: 1px solid #3e3e42;
        }
        
        .sidebar-actions button {
            flex: 1;
            padding: 6px 8px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-new-file {
            background: #fbbf24;
            color: #1e1e1e;
        }
        
        .btn-new-file:hover {
            background: #f59e0b;
        }
        
        .btn-new-folder {
            background: #475569;
            color: white;
        }
        
        .btn-new-folder:hover {
            background: #7d52d6;
        }
        
        .file-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 0;
        }
        
        .file-search {
            margin: 8px;
            padding: 8px 12px;
            background: #3e3e42;
            border: 1px solid #555;
            border-radius: 4px;
            color: #e0e0e0;
            font-size: 12px;
        }
        
        .file-search::placeholder {
            color: #858585;
        }
        
        .file-item {
            padding: 6px 16px;
            font-size: 13px;
            cursor: pointer;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #cccccc;
            transition: background 0.15s;
        }
        
        .file-item:hover {
            background: #37373d;
            color: #ffffff;
        }
        
        .file-item.active {
            background: #094771;
            color: #ffffff;
            border-left: 3px solid #007acc;
            padding-left: 13px;
        }
        
        .file-item i {
            flex-shrink: 0;
            font-size: 16px;
        }
        
        .file-dir {
            padding: 4px 16px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #858585;
            margin-top: 8px;
            text-overflow: ellipsis;
            overflow: hidden;
        }
        
        .main-editor {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #1e1e1e;
            overflow: hidden;
        }
        
        .editor-header {
            background: #2d2d30;
            border-bottom: 1px solid #3e3e42;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .editor-title {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        
        .editor-title h2 {
            font-size: 14px;
            font-weight: 500;
            color: #e0e0e0;
            margin: 0;
        }
        
        .editor-title .breadcrumb {
            font-size: 12px;
            color: #858585;
        }
        
        .editor-actions {
            display: flex;
            gap: 8px;
        }
        
        .editor-btn {
            padding: 6px 14px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-backups {
            background: #4d4d4d;
            color: #e0e0e0;
        }
        
        .btn-backups:hover {
            background: #5d5d5d;
        }
        
        .btn-edit {
            background: #fbbf24;
            color: #1e1e1e;
        }
        
        .btn-edit:hover {
            background: #f59e0b;
        }
        
        .btn-edit.editing {
            background: #10b981;
            color: white;
        }
        
        .btn-edit.editing:hover {
            background: #059669;
        }
        
        .editor-container {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .CodeMirror {
            flex: 1 !important;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace !important;
            font-size: 13px !important;
            line-height: 1.6 !important;
            background: #1e1e1e !important;
            color: #d4d4d4 !important;
        }
        
        .CodeMirror-linenumber {
            background: #1e1e1e !important;
            color: #858585 !important;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace !important;
        }
        
        .CodeMirror-gutters {
            background: #1e1e1e !important;
            border-right: 1px solid #3e3e42 !important;
        }
        
        .CodeMirror-cursor {
            border-left: 1px solid #aeafad !important;
        }
        
        .CodeMirror-selected {
            background: #264f78 !important;
        }
        
        .editor-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #858585;
            font-size: 14px;
        }
        
        .editor-placeholder i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.3;
        }
        
        .controls-bar {
            background: #2d2d30;
            border-top: 1px solid #3e3e42;
            padding: 12px 20px;
            display: flex;
            gap: 8px;
        }
        
        .btn-primary {
            padding: 8px 16px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-preview {
            background: #0e639c;
            color: white;
        }
        
        .btn-preview:hover:not(:disabled) {
            background: #1177bb;
        }
        
        .btn-apply {
            background: #13a10e;
            color: white;
        }
        
        .btn-apply:hover:not(:disabled) {
            background: #16c60c;
        }
        
        .btn-cancel {
            background: #4d4d4d;
            color: #e0e0e0;
        }
        
        .btn-cancel:hover {
            background: #5d5d5d;
        }
        
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .diff-line {
            padding: 2px 8px;
            border-left: 3px solid transparent;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
        }
        
        .diff-added {
            background-color: #1d3a1d;
            border-left-color: #4ec9b0;
        }
        
        .diff-removed {
            background-color: #3a1d1d;
            border-left-color: #ce7b7b;
        }
        
        .diff-unchanged {
            background-color: #2d2d30;
            border-left-color: #3e3e42;
        }
        
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1e1e1e;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #464647;
            border-radius: 6px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #545455;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-500 text-slate-900 shadow-lg">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold flex items-center">
                        <i class='bx bx-code-alt text-3xl mr-2'></i>
                        Smart Patcher
                    </h1>
                    <p class="text-amber-900/70 text-sm mt-1">Safe Code Repair Tool with Backups & Diff Preview</p>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium">👤 <?php echo htmlspecialchars($adminUsername); ?></span>
                    <a href="index.php?pages=dashboard" class="px-4 py-2 bg-slate-900/10 hover:bg-slate-900/20 rounded-lg transition font-semibold">
                        <i class='bx bx-arrow-back'></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="editor-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <i class='bx bx-folder'></i>
                Files
            </div>
            
            <div class="sidebar-actions">
                <button class="btn-new-file" onclick="promptNewFile()" title="New File">
                    <i class='bx bx-file-plus'></i> New
                </button>
                <button class="btn-new-folder" onclick="promptNewFolder()" title="New Folder">
                    <i class='bx bx-folder-plus'></i> Folder
                </button>
            </div>
            
            <input 
                type="text" 
                id="fileSearch" 
                class="file-search"
                placeholder="Search files..."
            />
            
            <div class="file-list" id="fileList">
                <div style="padding: 20px; text-align: center; color: #858585; font-size: 12px;">
                    <i class='bx bx-loader-alt bx-spin' style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                    Loading files...
                </div>
            </div>
        </div>

        <!-- Main Editor -->
        <div class="main-editor">
            <div class="editor-header" id="editorHeader" style="display: none;">
                <div class="editor-title">
                    <i class='bx bx-file' id="fileIcon"></i>
                    <div>
                        <h2 id="currentFileName">Untitled</h2>
                        <div class="breadcrumb" id="currentFilePath"></div>
                    </div>
                </div>
                <div class="editor-actions">
                    <button onclick="viewBackups()" class="editor-btn btn-backups">
                        <i class='bx bx-history'></i> Backups
                    </button>
                    <button onclick="toggleEditMode()" id="editBtn" class="editor-btn btn-edit">
                        <i class='bx bx-edit'></i> Edit
                    </button>
                </div>
            </div>

            <div class="editor-container" id="editorContainer">
                <div class="editor-placeholder">
                    <i class='bx bx-file-blank'></i>
                    <p>Select a file from the left to begin editing</p>
                </div>
            </div>

            <div class="controls-bar" id="controlsBar" style="display: none;">
                <button onclick="previewDiff()" id="previewBtn" class="btn-primary btn-preview" disabled>
                    <i class='bx bx-show'></i> Preview Changes
                </button>
                <button onclick="applyFix()" id="applyBtn" class="btn-primary btn-apply" disabled>
                    <i class='bx bx-check-circle'></i> Apply Fix
                </button>
                <button onclick="cancelEdit()" id="cancelBtn" class="btn-primary btn-cancel" style="display: none;">
                    <i class='bx bx-x'></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        const API = '../api/patcher.php';
        let currentFile = null;
        let originalContent = '';
        let isEditMode = false;
        let allFiles = [];
        let editor = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadFiles();
            document.getElementById('fileSearch').addEventListener('input', (e) => {
                filterFiles(e.target.value);
            });
        });

        async function loadFiles() {
            try {
                const res = await fetch(`${API}?action=listFiles`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                allFiles = data.files;
                renderFiles(allFiles);
            } catch (err) {
                document.getElementById('fileList').innerHTML = 
                    `<div style="padding: 20px; color: #ff6b6b; font-size: 12px;">${err.message}</div>`;
            }
        }

        function renderFiles(files) {
            const container = document.getElementById('fileList');
            
            if (files.length === 0) {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: #858585; font-size: 12px;">No files found</div>';
                return;
            }

            let lastDir = '';
            let html = '';
            
            files.forEach(file => {
                if (file.dir !== lastDir) {
                    html += `<div class="file-dir">${file.dir}</div>`;
                    lastDir = file.dir;
                }
                
                const icon = getFileIcon(file.extension);
                html += `
                    <div class="file-item" data-path="${file.path}" onclick="loadFile('${file.path}')">
                        <i class='bx ${icon}'></i>
                        <span>${file.name}</span>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function filterFiles(search) {
            const filtered = allFiles.filter(f => 
                f.name.toLowerCase().includes(search.toLowerCase())
            );
            renderFiles(filtered);
        }

        async function loadFile(path) {
            try {
                Swal.fire({
                    title: 'Loading...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(`${API}?action=readFile&path=${encodeURIComponent(path)}`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                currentFile = data;
                originalContent = data.content;
                
                // Initialize editor if not exists
                if (!editor) {
                    const editorDiv = document.querySelector('.editor-container');
                    editorDiv.innerHTML = '';
                    editor = CodeMirror(editorDiv, {
                        value: data.content,
                        mode: getModeForExtension(data.extension),
                        theme: 'monokai',
                        lineNumbers: true,
                        lineWrapping: true,
                        readOnly: true,
                        indentUnit: 4,
                        tabSize: 4,
                        indentWithTabs: false,
                        extraKeys: {
                            'Ctrl-/': 'toggleComment',
                            'Cmd-/': 'toggleComment'
                        }
                    });
                } else {
                    editor.setValue(data.content);
                    editor.setOption('mode', getModeForExtension(data.extension));
                    editor.setOption('readOnly', true);
                }
                
                // Update header
                document.getElementById('editorHeader').style.display = 'flex';
                document.getElementById('controlsBar').style.display = 'flex';
                document.getElementById('currentFileName').textContent = data.filename;
                document.getElementById('currentFilePath').textContent = data.path;
                document.getElementById('fileIcon').className = 'bx ' + getFileIcon(data.extension);
                
                isEditMode = false;
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('cancelBtn').style.display = 'none';
                document.getElementById('editBtn').innerHTML = '<i class="bx bx-edit"></i> Edit';
                document.getElementById('editBtn').classList.remove('editing');
                
                // Mark file as active
                document.querySelectorAll('.file-item').forEach(el => {
                    el.classList.remove('active');
                    const itemPath = el.getAttribute('data-path');
                    if (itemPath === path) {
                        el.classList.add('active');
                    }
                });
                
                Swal.close();
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function toggleEditMode() {
            isEditMode = !isEditMode;
            
            if (isEditMode) {
                editor.setOption('readOnly', false);
                editor.focus();
                document.getElementById('editBtn').innerHTML = '<i class="bx bx-lock-open"></i> Editing...';
                document.getElementById('editBtn').classList.add('editing');
                document.getElementById('previewBtn').disabled = false;
                document.getElementById('cancelBtn').style.display = 'flex';
            } else {
                editor.setOption('readOnly', true);
                document.getElementById('editBtn').innerHTML = '<i class="bx bx-edit"></i> Edit';
                document.getElementById('editBtn').classList.remove('editing');
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('cancelBtn').style.display = 'none';
            }
        }

        function cancelEdit() {
            editor.setValue(originalContent);
            toggleEditMode();
        }

        async function previewDiff() {
            const newContent = editor.getValue();
            
            if (newContent === originalContent) {
                Swal.fire('No Changes', 'The content is identical to the original', 'info');
                return;
            }

            try {
                Swal.fire({
                    title: 'Generating diff...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch(`${API}?action=previewDiff`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        path: currentFile.path,
                        content: newContent
                    })
                });

                const data = await res.json();
                if (data.error) throw new Error(data.error);

                showDiffModal(data.diff, data.stats);
                document.getElementById('applyBtn').disabled = false;
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function showDiffModal(diff, stats) {
            let html = `
                <div style="text-align: left; max-height: 500px; overflow-y: auto;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 20px;">
                        <div style="background: #1d3a1d; padding: 12px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #4ec9b0;">${stats.added}</div>
                            <div style="font-size: 11px; color: #858585; margin-top: 4px;">Added</div>
                        </div>
                        <div style="background: #3a1d1d; padding: 12px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #ce7b7b;">${stats.removed}</div>
                            <div style="font-size: 11px; color: #858585; margin-top: 4px;">Removed</div>
                        </div>
                        <div style="background: #2d2d30; padding: 12px; border-radius: 4px; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold; color: #858585;">${stats.unchanged}</div>
                            <div style="font-size: 11px; color: #858585; margin-top: 4px;">Unchanged</div>
                        </div>
                    </div>
                    <div style="border: 1px solid #3e3e42; border-radius: 4px; overflow: hidden;">
            `;

            diff.lines.forEach(line => {
                const classes = {
                    'added': 'diff-added',
                    'removed': 'diff-removed',
                    'unchanged': 'diff-unchanged'
                };
                
                const escapedContent = (line.content || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html += `
                    <div class="diff-line ${classes[line.type]}">
                        <span style="color: #858585; margin-right: 16px; display: inline-block; width: 40px; text-align: right;">${line.lineNum}</span>
                        <span style="font-family: monospace;">${escapedContent || '&nbsp;'}</span>
                    </div>
                `;
            });

            html += `</div></div>`;

            Swal.fire({
                title: '📊 Diff Preview',
                html: html,
                width: '900px',
                confirmButtonText: 'Close',
                confirmButtonColor: '#0e639c',
                background: '#1e1e1e',
                color: '#e0e0e0'
            });
        }

        async function applyFix() {
            const result = await Swal.fire({
                title: 'Apply Fix?',
                html: `
                    <p style="color: #e0e0e0; margin-bottom: 12px;">This will:</p>
                    <ul style="text-align: left; color: #cccccc; font-size: 14px; margin-left: 20px;">
                        <li>✓ Create a backup with timestamp</li>
                        <li>✓ Overwrite the live file</li>
                        <li>✓ Log the action</li>
                    </ul>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Apply Fix',
                confirmButtonColor: '#13a10e',
                cancelButtonText: 'Cancel',
                background: '#1e1e1e',
                color: '#e0e0e0'
            });

            if (!result.isConfirmed) return;

            try {
                Swal.fire({
                    title: 'Applying fix...',
                    html: 'Creating backup and writing file...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                    background: '#1e1e1e',
                    color: '#e0e0e0'
                });

                const newContent = editor.getValue();

                const res = await fetch(`${API}?action=applyFix`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        path: currentFile.path,
                        content: newContent
                    })
                });

                const data = await res.json();
                if (data.error) throw new Error(data.error);

                originalContent = newContent;
                isEditMode = false;
                editor.setOption('readOnly', true);
                document.getElementById('applyBtn').disabled = true;
                document.getElementById('previewBtn').disabled = true;
                document.getElementById('cancelBtn').style.display = 'none';
                document.getElementById('editBtn').innerHTML = '<i class="bx bx-edit"></i> Edit';
                document.getElementById('editBtn').classList.remove('editing');

                Swal.fire({
                    icon: 'success',
                    title: 'Fix Applied!',
                    html: `
                        <p style="color: #e0e0e0;">File updated successfully</p>
                        <p style="font-size: 12px; color: #858585; margin-top: 8px;">Backup: ${data.backup}</p>
                    `,
                    confirmButtonColor: '#13a10e',
                    background: '#1e1e1e',
                    color: '#e0e0e0'
                });
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        async function viewBackups() {
            if (!currentFile) return;

            try {
                const res = await fetch(`${API}?action=listBackups&path=${encodeURIComponent(currentFile.path)}`);
                const data = await res.json();
                
                if (data.error) throw new Error(data.error);

                let html = '<div style="max-height: 400px; overflow-y: auto;">';
                
                if (data.count === 0) {
                    html += '<p style="color: #858585; text-align: center; padding: 40px 20px;">No backups found for this file</p>';
                } else {
                    html += '<div style="display: grid; gap: 8px;">';
                    data.backups.forEach(backup => {
                        html += `
                            <div style="padding: 12px; background: #2d2d30; border-radius: 4px; border: 1px solid #3e3e42;">
                                <div style="font-weight: 500; color: #e0e0e0;">${backup.name}</div>
                                <div style="font-size: 11px; color: #858585; margin-top: 4px;">
                                    Created: ${backup.created} | Size: ${formatBytes(backup.size)}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                
                html += '</div>';

                Swal.fire({
                    title: '🕒 Backups',
                    html: html,
                    confirmButtonColor: '#0e639c',
                    background: '#1e1e1e',
                    color: '#e0e0e0'
                });
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        function getModeForExtension(ext) {
            const modes = {
                'php': { name: 'application/x-httpd-php', startOpen: true },
                'js': 'text/javascript',
                'css': 'text/css',
                'html': 'text/html',
                'json': 'application/json'
            };
            return modes[ext] || 'null';
        }

        function getFileIcon(ext) {
            const icons = {
                'php': 'bxl-php',
                'js': 'bxl-javascript',
                'css': 'bxl-css3',
                'html': 'bxl-html5',
                'json': 'bx-code-curly'
            };
            return icons[ext] || 'bx-file';
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        async function promptNewFile() {
            const { value: path } = await Swal.fire({
                title: 'Create New File',
                input: 'text',
                inputLabel: 'Relative path (inside public, admin, config, migrations)',
                inputPlaceholder: 'e.g. admin/api/new_tool.php or config/new.php',
                showCancelButton: true,
                confirmButtonText: 'Create File',
                confirmButtonColor: '#0e639c',
                background: '#1e1e1e',
                color: '#e0e0e0'
            });

            if (!path) return;

            try {
                Swal.showLoading();
                const res = await fetch(`${API}?action=createFile`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ path })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                await loadFiles();
                await loadFile(data.path);
                Swal.fire('Created', 'File created successfully', 'success');
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }

        async function promptNewFolder() {
            const { value: path } = await Swal.fire({
                title: 'Create New Folder',
                input: 'text',
                inputLabel: 'Relative path (inside public, admin, config, migrations)',
                inputPlaceholder: 'e.g. admin/api/tools or config/cache',
                showCancelButton: true,
                confirmButtonText: 'Create Folder',
                confirmButtonColor: '#6f42c1',
                background: '#1e1e1e',
                color: '#e0e0e0'
            });

            if (!path) return;

            try {
                Swal.showLoading();
                const res = await fetch(`${API}?action=createFolder`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ path })
                });
                const data = await res.json();
                if (data.error) throw new Error(data.error);
                await loadFiles();
                Swal.fire('Created', 'Folder created successfully', 'success');
            } catch (err) {
                Swal.fire('Error', err.message, 'error');
            }
        }
    </script>
</body>
</html>