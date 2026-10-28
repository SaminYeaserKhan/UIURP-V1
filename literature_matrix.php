<?php
session_start();

require __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Get project ID from URL
$projectId = isset($_GET['id']) ? $_GET['id'] : null;
if (!$projectId) {
    header('Location: index.php');
    exit();
}

// Connect to MongoDB
$client = new MongoDB\Client(require __DIR__ . '/src/config/mongo_uri.php');
$db = $client->uiurp;
$project_collection = $db->projectsV2;
$literature_collection = $db->literature_matrix;

// Get project details
$project = $project_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($projectId)]);
if (!$project) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Get literature matrix data for this project
$literatureMatrix = $literature_collection->findOne(['projectId' => new MongoDB\BSON\ObjectId($projectId)]);

// Initialize default tags
$defaultTags = [
    ['name' => 'Publication Year', 'color' => 'var(--tag-blue)'],
    ['name' => 'Research Aim / Objective', 'color' => 'var(--tag-green)'],
    ['name' => 'Dataset', 'color' => 'var(--tag-purple)'],
    ['name' => 'Methodology', 'color' => 'var(--tag-orange)'],
    ['name' => 'Findings', 'color' => 'var(--tag-blue)'],
    ['name' => 'Research Gap', 'color' => 'var(--tag-green)'],
    ['name' => 'Future Work', 'color' => 'var(--tag-purple)'],
    ['name' => 'Relevance', 'color' => 'var(--tag-orange)'],
    ['name' => 'Keywords/Theme', 'color' => 'var(--tag-blue)'],
    ['name' => 'Citation/BibTeX', 'color' => 'var(--tag-green)']
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Literature Matrix - <?php echo htmlspecialchars($project->title); ?></title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Include custom CSS for Notion-like design -->
    <style>
        :root {
            /* Dark Theme (Default) */
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --bg-quaternary: #475569;
            --text-primary: #f8fafc;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #334155;
            --shadow-color: rgba(0, 0, 0, 0.3);
            
            /* Notion-like colors for dark theme */
            --notion-gray: #1e293b;
            --notion-border: #334155;
            --notion-white: #0f172a;
            --notion-text: #f8fafc;
            --notion-text-muted: #cbd5e1;
            
            /* Tag colors for dark theme */
            --tag-blue: #1e40af;
            --tag-green: #166534;
            --tag-purple: #7c3aed;
            --tag-orange: #ea580c;
        }

        /* Light Theme Variables */
        [data-theme="light"] {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #e2e8f0;
            --bg-quaternary: #cbd5e1;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            
            /* Notion-like colors for light theme */
            --notion-gray: #f7f6f3;
            --notion-border: #e3e3e1;
            --notion-white: #ffffff;
            --notion-text: #1e293b;
            --notion-text-muted: #64748b;
            
            /* Tag colors for light theme */
            --tag-blue: #deeafd;
            --tag-green: #dbeddb;
            --tag-purple: #e9e3fd;
            --tag-orange: #ffe2dd;
        }

        body {
            background-color: var(--notion-white);
            color: var(--notion-text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, "Apple Color Emoji", Arial, sans-serif;
            padding-top: 60px; /* Add padding for fixed navbar */
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 15px; /* Add horizontal padding */
        }

        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
            padding: 1rem;
            background: var(--notion-gray);
            border-radius: 8px;
            border: 1px solid var(--notion-border);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .tag {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
            position: relative;
            color: var(--text-primary);
            background: var(--bg-secondary);
        }

        /* Dark theme specific styles */
        :root:not([data-theme="light"]) .tag {
            color: var(--bg-primary);
            background: var(--text-secondary);
        }

        .tag.selected {
            color: var(--bg-primary);
            background: var(--tag-blue);
            font-weight: 500;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        /* Light theme specific styles */
        [data-theme="light"] .tag.selected {
            color: var(--text-primary);
            background: var(--tag-blue);
        }

        .tag:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .literature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--notion-white);
            border-radius: 8px;
            overflow: hidden;
            table-layout: fixed; /* Add fixed table layout */
            transition: background-color 0.3s ease;
        }

        .literature-table thead {
            position: sticky;
            top: 0;
            z-index: 2;
            background: var(--notion-gray);
        }

        .literature-table th {
            position: sticky;
            top: 0;
            background: var(--notion-gray);
            color: var(--notion-text);
            z-index: 2;
            padding: 12px 15px;
            border-bottom: 2px solid var(--notion-border);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Set specific widths for different columns */
        .literature-table th:first-child,
        .literature-table td:first-child {
            width: 250px; /* Fixed width for filename column */
            max-width: 250px;
        }

        .literature-table td {
            background: var(--notion-white);
            color: var(--notion-text);
            padding: 12px 15px;
            border: 1px solid var(--notion-border);
            vertical-align: top;
            word-wrap: break-word; /* Allow word wrapping */
            overflow-wrap: break-word;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        /* Add ellipsis for long filenames */
        .literature-table td:first-child {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Show full filename on hover */
        .literature-table td:first-child:hover {
            white-space: normal;
            overflow: visible;
            position: relative;
            z-index: 1;
            background: var(--notion-white);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        /* Ensure other columns take up remaining space evenly */
        .literature-table th:not(:first-child),
        .literature-table td:not(:first-child) {
            width: auto;
            min-width: 200px;
        }

        .upload-section {
            margin: 2rem 0;
            padding: 2rem;
            background: var(--notion-gray);
            border-radius: 8px;
            border: 1px solid var(--notion-border);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        /* Dark theme button styling (default) */
        .btn-notion {
            background: linear-gradient(135deg, var(--tag-blue), var(--tag-purple));
            color: var(--text-primary);
            border: 1px solid rgba(76, 201, 240, 0.1);
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 14px;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-notion:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(76, 201, 240, 0.2), rgba(114, 9, 183, 0.2));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-notion:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
            border-color: rgba(76, 201, 240, 0.3);
        }

        .btn-notion:hover:before {
            opacity: 1;
        }

        .btn-notion:active {
            transform: translateY(0);
        }

        .btn-notion:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            background: var(--bg-quaternary);
            border-color: transparent;
        }

        .btn-notion:disabled:before {
            display: none;
        }

        /* Light theme button styling */
        [data-theme="light"] .btn-notion {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px var(--shadow-color);
        }

        [data-theme="light"] .btn-notion:before {
            background: linear-gradient(135deg, 
                rgba(59, 130, 246, 0.1), 
                rgba(147, 51, 234, 0.1)
            );
        }

        [data-theme="light"] .btn-notion:hover {
            background: var(--bg-secondary);
            border-color: var(--tag-blue);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        [data-theme="light"] .btn-notion:disabled {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            color: var(--text-muted);
            box-shadow: none;
        }

        /* Button with icon */
        .btn-notion i {
            font-size: 16px;
            transition: transform 0.3s ease;
        }

        .btn-notion:hover i {
            transform: translateX(2px);
        }

        #fileList {
            margin-top: 1rem;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem;
            background: var(--notion-white);
            color: var(--notion-text);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            border: 1px solid var(--notion-border);
            transition: all 0.3s ease;
        }

        .file-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px var(--shadow-color);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .alert-info {
            background-color: var(--tag-blue);
            color: var(--notion-text);
            border: 1px solid var(--notion-border);
        }

        .alert-danger {
            background-color: var(--tag-orange);
            color: var(--notion-text);
            border: 1px solid var(--notion-border);
        }

        .retry-button {
            background: var(--tag-blue);
            color: var(--notion-text);
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 8px;
            transition: all 0.3s ease;
        }

        .retry-button:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        .retry-button.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .file-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cell-loading {
            background-color: var(--tag-blue);
            opacity: 0.7;
        }

        .delete-file {
            color: var(--tag-orange);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .delete-file:hover {
            background-color: var(--tag-orange);
            color: var(--notion-white);
        }

        .tag-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .loading-spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--border-color);
            border-left-color: var(--text-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .last-edit-info {
            font-size: 12px;
            color: var(--notion-text-muted);
            margin-top: 4px;
            transition: color 0.3s ease;
        }

        /* Add loading animation */
        .analysis-progress {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--notion-white);
            color: var(--notion-text);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px var(--shadow-color);
            border: 1px solid var(--notion-border);
            z-index: 1000;
            text-align: center;
            transition: all 0.3s ease;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            margin-top: 10px;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--tag-blue);
            border-radius: 2px;
            transition: width 0.3s ease;
        }

        /* Fix table header alignment */
        .table-responsive {
            overflow-x: auto;
            margin-top: 2rem;
            box-shadow: 0 2px 4px var(--shadow-color);
            border-radius: 8px;
            position: relative;
            transition: box-shadow 0.3s ease;
        }

        /* Form controls styling */
        .form-control {
            background-color: var(--notion-white);
            color: var(--notion-text);
            border: 1px solid var(--notion-border);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: var(--notion-white);
            color: var(--notion-text);
            border-color: var(--tag-blue);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        /* Input group styling */
        .input-group {
            transition: all 0.3s ease;
        }

        /* Trendy generation overlay */
        .generation-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(59, 130, 246, 0.15); /* blue haze */
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }
        
        .spinner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: var(--notion-white);
            color: var(--notion-text);
            padding: 2rem 3rem;
            border-radius: 18px;
            box-shadow: 0 8px 32px var(--shadow-color);
            border: 1px solid var(--notion-border);
            transition: all 0.3s ease;
        }
        
        .trendy-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--tag-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 1rem;
        }
        
        .spinner-text {
            font-size: 1.2rem;
            color: var(--tag-blue);
            font-weight: 500;
            letter-spacing: 0.03em;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .generation-status-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .spinner-inline {
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid var(--border-color);
            border-top: 3px solid var(--tag-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        .spinner-inline-text {
            font-size: 1.1rem;
            color: var(--tag-blue);
            font-weight: 500;
            letter-spacing: 0.02em;
        }
        
        .file-item.generating {
            background: var(--tag-blue) !important;
            color: var(--notion-text);
            box-shadow: 0 2px 8px var(--shadow-color);
            opacity: 0.9;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    
    <!-- Theme initialization script - Prevent flash of unstyled content -->
    <script>
        (function() {
            // Get saved theme immediately to prevent flash
            const savedTheme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
</head>
<body>
    <?php include 'src/includes/navbar.php'; ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Literature Matrix - <?php echo htmlspecialchars($project->title); ?></h1>
            <a href="Project_details.php?id=<?php echo $projectId; ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Project
            </a>
        </div>
        
        <!-- Tag Management Section -->
        <div class="mb-4">
            <h4>Choose Your Columns</h4>
            <div class="tag-container" id="tagContainer">
                <!-- Tags will be dynamically added here -->
            </div>
            <div class="input-group mb-3">
                <input type="text" class="form-control" id="newTagInput" placeholder="Add new tag">
                <button class="btn btn-notion" onclick="addNewTag()">Add Tag</button>
            </div>
        </div>

        <!-- File Upload Section -->
        <div class="upload-section">
            <h4>Upload PDF Files</h4>
            <input type="file" id="pdfUpload" accept=".pdf" class="form-control mb-3" multiple>
            <button class="btn btn-notion" onclick="handleFileUpload()">Upload Files</button>
            <div id="fileList" class="mt-3"></div>
            <div id="generateButtonContainer" class="mt-3" style="display: none;">
                <hr class="my-3">
                <button class="btn btn-notion" onclick="generateMatrix()" id="generateBtn">
                    Generate Matrix
                </button>
            </div>
            <div id="generationStatusRow" class="generation-status-row mt-3"></div>
        </div>

        <!-- Literature Matrix Table -->
        <div class="table-responsive">
            <table class="literature-table" id="literatureTable">
                <thead>
                    <tr id="tableHeader">
                        <th>File Name</th>
                        <!-- Dynamic columns will be added here based on selected tags -->
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Table content will be dynamically added here -->
                </tbody>
            </table>
        </div>

        <!-- Export Button -->
        <div class="mt-4">
            <button class="btn btn-notion" onclick="exportTable()">Export to Excel</button>
        </div>
    </div>

    <!-- Include necessary JavaScript libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

    <script>
        // Set worker path for pdf.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

        const GEMINI_PROXY_URL = 'src/controller/gemini_proxy.php'; // keeps the API key on the server
        const PROJECT_ID = '<?php echo $projectId; ?>';
        const USER_ID = '<?php echo $userId; ?>';
        const USER_NAME = '<?php echo $_SESSION['username']; ?>';

        // Available tags with different colors
        let availableTags = <?php echo json_encode($defaultTags); ?>;
        let selectedTags = new Set(<?php 
            if ($literatureMatrix && isset($literatureMatrix['selectedTags'])) {
                // Convert BSON array to PHP array first
                $selectedTags = iterator_to_array($literatureMatrix['selectedTags']);
                echo json_encode(array_map(function($tag) { return $tag['name']; }, $selectedTags));
            } else {
                echo '[]';
            }
        ?>);

        // Store PDF text content for retry functionality
        let pdfContents = new Map(); // Map to store PDF text content by filename
        let uploadedFiles = <?php 
            if ($literatureMatrix && isset($literatureMatrix['files'])) {
                // Convert BSON array to PHP array
                $files = iterator_to_array($literatureMatrix['files']);
                echo json_encode($files);
            } else {
                echo '[]';
            }
        ?>;

        // Initialize the page
        function init() {
            renderTags();
            updateTable();
            updateFileList();
        }

        // Render tags in the tag container
        function renderTags() {
            const tagContainer = document.getElementById('tagContainer');
            tagContainer.innerHTML = '';

            availableTags.forEach(tag => {
                const tagElement = document.createElement('span');
                tagElement.className = `tag ${selectedTags.has(tag.name) ? 'selected' : ''}`;
                const indicator = document.createElement('span');
                indicator.className = 'tag-indicator';
                indicator.style.backgroundColor = tag.color;
                tagElement.appendChild(indicator);
                tagElement.appendChild(document.createTextNode(tag.name));
                
                // Always set a background color
                tagElement.style.backgroundColor = selectedTags.has(tag.name) ? tag.color : '#ffffff';
                tagElement.style.border = '1px solid ' + tag.color;
                
                tagElement.onclick = () => toggleTag(tag.name);
                tagContainer.appendChild(tagElement);
            });
        }

        // Toggle tag selection
        function toggleTag(tagName) {
            if (selectedTags.has(tagName)) {
                selectedTags.delete(tagName);
            } else {
                selectedTags.add(tagName);
            }
            renderTags();
            updateTable();
            saveToDatabase();
        }

        // Add new tag
        function addNewTag() {
            const input = document.getElementById('newTagInput');
            const tagName = input.value.trim();
            
            if (tagName && !availableTags.some(tag => tag.name === tagName)) {
                const colors = ['var(--tag-blue)', 'var(--tag-green)', 'var(--tag-purple)', 'var(--tag-orange)'];
                const randomColor = colors[Math.floor(Math.random() * colors.length)];
                
                availableTags.push({ name: tagName, color: randomColor });
                selectedTags.add(tagName);
                input.value = '';
                renderTags();
                updateTable();
                saveToDatabase();
            }
        }

        // Function to extract text from PDF with chunking
        async function extractTextFromPDF(file) {
            return new Promise((resolve, reject) => {
                // Check file size
                const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB
                if (file.size > MAX_FILE_SIZE) {
                    reject(`File ${file.name} is too large. Maximum size is 10MB.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = async function(event) {
                    try {
                        const typedarray = new Uint8Array(event.target.result);
                        const pdf = await pdfjsLib.getDocument(typedarray).promise;
                        
                        let fullText = '';
                        for(let i = 1; i <= pdf.numPages; i++) {
                            const page = await pdf.getPage(i);
                            const textContent = await page.getTextContent();
                            const pageText = textContent.items.map(item => item.str).join(' ');
                            fullText += pageText + '\n';
                        }
                        resolve(fullText);
                    } catch (error) {
                        reject(error);
                    }
                };
                reader.readAsArrayBuffer(file);
            });
        }

        // Function to analyze text with Gemini (with retry)
        async function analyzeWithGemini(text, tag) {
            const MAX_RETRIES = 3;
            const RETRY_DELAY = 2000; // 2 seconds
            // const CHUNK_SIZE = 5000; // characters per chunk

            // No chunking: send the entire text in one call
            let retries = 0;
            while (retries < MAX_RETRIES) {
                try {
                    const prompt = `Analyze this research paper text and extract information relevant to the category "${tag}". 
Provide a very concise summary of the key points related to this category.
Text: ${text}`;

                    const response = await fetch(GEMINI_PROXY_URL, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            contents: [{
                                parts: [{
                                    text: prompt
                                }]
                            }],
                            generationConfig: {
                                temperature: 0.4,
                                topK: 32,
                                topP: 0.8,
                                maxOutputTokens: 512,
                            },
                            safetySettings: [
                                {
                                    category: "HARM_CATEGORY_HARASSMENT",
                                    threshold: "BLOCK_MEDIUM_AND_ABOVE"
                                },
                                {
                                    category: "HARM_CATEGORY_HATE_SPEECH",
                                    threshold: "BLOCK_MEDIUM_AND_ABOVE"
                                },
                                {
                                    category: "HARM_CATEGORY_SEXUALLY_EXPLICIT",
                                    threshold: "BLOCK_MEDIUM_AND_ABOVE"
                                },
                                {
                                    category: "HARM_CATEGORY_DANGEROUS_CONTENT",
                                    threshold: "BLOCK_MEDIUM_AND_ABOVE"
                                }
                            ]
                        })
                    });

                    if (response.status === 429) {
                        throw new Error('Rate limit exceeded');
                    }

                    const data = await response.json();
                    if (data.candidates && data.candidates[0].content.parts[0].text) {
                        return data.candidates[0].content.parts[0].text;
                    }
                    throw new Error('Analysis failed');
                } catch (error) {
                    retries++;
                    if (retries === MAX_RETRIES) {
                        console.error('Gemini API Error:', error);
                        return "Analysis failed after multiple retries";
                    }
                    // Wait before retrying
                    await new Promise(resolve => setTimeout(resolve, RETRY_DELAY));
                }
            }
        }

        // Handle file upload
        async function handleFileUpload() {
            const fileInput = document.getElementById('pdfUpload');
            const files = fileInput.files;
            
            if (files.length === 0) {
                alert('Please select at least one PDF file.');
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type !== 'application/pdf') {
                    alert(`${file.name} is not a PDF file. Skipping...`);
                    continue;
                }

                // Create FormData
                const formData = new FormData();
                formData.append('file', file);
                formData.append('projectId', PROJECT_ID);
                formData.append('userId', USER_ID);
                formData.append('userName', USER_NAME);

                try {
                    // Show loading state
                    const loadingDiv = document.createElement('div');
                    loadingDiv.className = 'alert alert-info';
                    loadingDiv.innerHTML = `Uploading ${file.name}... <div class="loading-spinner"></div>`;
                    document.getElementById('fileList').appendChild(loadingDiv);

                    // Upload file
                    const response = await fetch('src/model/upload_literature_file.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Extract text from PDF
                        const text = await extractTextFromPDF(file);
                        pdfContents.set(file.name, text);

                        // Add file to uploadedFiles array
                        uploadedFiles.push({
                            name: file.name,
                            path: result.filePath,
                            uploadedAt: new Date().toISOString(),
                            uploadedBy: {
                                userId: USER_ID,
                                name: USER_NAME
                            },
                            data: {}
                        });

                        loadingDiv.remove();
                        updateFileList();
                        saveToDatabase();
                    } else {
                        loadingDiv.className = 'alert alert-danger';
                        loadingDiv.textContent = `Failed to upload ${file.name}: ${result.message}`;
                    }
                } catch (error) {
                    console.error('Error uploading file:', error);
                    loadingDiv.className = 'alert alert-danger';
                    loadingDiv.textContent = `Error uploading ${file.name}`;
                }
            }

            fileInput.value = '';
        }

        // Update generateMatrix function
        async function generateMatrix() {
            const generateBtn = document.getElementById('generateBtn');
            generateBtn.disabled = true;
            generateBtn.textContent = 'Generating...';

            // Find files that need generation (missing at least one selected tag)
            const filesToProcess = uploadedFiles.filter(file => {
                return Array.from(selectedTags).some(tag => !file.data || !file.data[tag]);
            });

            // Mark files as generating
            filesToProcess.forEach(file => file.isGenerating = true);
            updateFileList();

            try {
                let totalTags = selectedTags.size;
                for (const file of filesToProcess) {
                    const text = pdfContents.get(file.name);
                    if (!text) continue;

                    for (const tag of selectedTags) {
                        if (file.data && file.data[tag]) {
                            continue; // Skip already generated tags
                        }
                        const analysis = await analyzeWithGemini(text, tag);
                        if (!file.data) file.data = {};
                        file.data[tag] = analysis;
                        file.lastEditedBy = {
                            userId: USER_ID,
                            name: USER_NAME,
                            timestamp: new Date().toISOString()
                        };
                    }
                    // Mark file as done
                    file.isGenerating = false;
                    updateFileList();
                }
                updateTable();
                saveToDatabase();
            } catch (error) {
                console.error('Error generating matrix:', error);
                alert('Error generating matrix. Please try again.');
            } finally {
                generateBtn.disabled = false;
                generateBtn.textContent = 'Generate Matrix';
                // Remove all generating flags
                uploadedFiles.forEach(file => file.isGenerating = false);
                updateFileList();
            }
        }

        // Function to delete a file
        async function deleteFile(fileName) {
            if (!confirm(`Are you sure you want to delete ${fileName}?`)) {
                return;
            }

            try {
                const response = await fetch('src/model/delete_literature_file.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        projectId: PROJECT_ID,
                        fileName: fileName
                    })
                });

                const result = await response.json();
                if (result.success) {
                    uploadedFiles = uploadedFiles.filter(file => file.name !== fileName);
                    pdfContents.delete(fileName);
                    updateFileList();
                    updateTable();
                    saveToDatabase();
                } else {
                    alert(`Failed to delete ${fileName}: ${result.message}`);
                }
            } catch (error) {
                console.error('Error deleting file:', error);
                alert(`Error deleting ${fileName}`);
            }
        }

        // Update file list
        function updateFileList() {
            const fileList = document.getElementById('fileList');
            const generateButtonContainer = document.getElementById('generateButtonContainer');
            fileList.innerHTML = '';

            if (uploadedFiles.length > 0) {
                uploadedFiles.forEach(file => {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    if (file.isGenerating) fileItem.classList.add('generating');
                    
                    // Spinner for generating files
                    if (file.isGenerating) {
                        const spinner = document.createElement('div');
                        spinner.className = 'spinner-inline';
                        fileItem.appendChild(spinner);
                    }

                    const fileName = document.createElement('span');
                    fileName.textContent = file.name;
                    
                    const fileActions = document.createElement('div');
                    fileActions.className = 'file-actions';
                    
                    const retryButton = document.createElement('button');
                    retryButton.className = 'retry-button';
                    retryButton.textContent = 'Retry Analysis';
                    retryButton.setAttribute('data-filename', file.name);
                    retryButton.onclick = () => retryAnalysis(file.name);
                    
                    const deleteButton = document.createElement('i');
                    deleteButton.className = 'bi bi-x-circle delete-file';
                    deleteButton.onclick = () => deleteFile(file.name);
                    
                    fileActions.appendChild(retryButton);
                    fileActions.appendChild(deleteButton);
                    fileItem.appendChild(fileName);
                    fileItem.appendChild(fileActions);
                    fileList.appendChild(fileItem);
                });

                // Show generate button container if there are files
                generateButtonContainer.style.display = 'block';
            } else {
                // Hide generate button container if no files
                generateButtonContainer.style.display = 'none';
            }
        }

        // Update table
        function updateTable() {
            const header = document.getElementById('tableHeader');
            const body = document.getElementById('tableBody');

            // Update header
            header.innerHTML = '<th>File Name</th>';
            selectedTags.forEach(tag => {
                const th = document.createElement('th');
                th.textContent = tag;
                header.appendChild(th);
            });

            // Update body
            body.innerHTML = '';
            uploadedFiles.forEach(file => {
                const row = document.createElement('tr');
                row.innerHTML = `<td>${file.name}</td>`;
                
                selectedTags.forEach(tag => {
                    const td = document.createElement('td');
                    td.contentEditable = true;
                    td.textContent = file.data[tag] || '';
                    td.setAttribute('data-filename', file.name);
                    td.setAttribute('data-tag', tag);
                    
                    // Add last edit info if available
                    if (file.lastEditedBy) {
                        const lastEditInfo = document.createElement('div');
                        lastEditInfo.className = 'last-edit-info';
                        lastEditInfo.textContent = `Last edited by ${file.lastEditedBy.name} on ${new Date(file.lastEditedBy.timestamp).toLocaleString()}`;
                        td.appendChild(lastEditInfo);
                    }
                    
                    td.onblur = (e) => {
                        const fileObj = uploadedFiles.find(f => f.name === file.name);
                        if (fileObj) {
                            fileObj.data[tag] = e.target.textContent;
                            fileObj.lastEditedBy = {
                                userId: USER_ID,
                                name: USER_NAME,
                                timestamp: new Date().toISOString()
                            };
                            saveToDatabase();
                        }
                    };
                    row.appendChild(td);
                });

                body.appendChild(row);
            });
        }

        // Save to database
        async function saveToDatabase() {
            try {
                // Convert selectedTags Set to array of objects
                const selectedTagsArray = Array.from(selectedTags).map(tagName => {
                    const existingTag = availableTags.find(t => t.name === tagName);
                    return {
                        name: tagName,
                        color: existingTag ? existingTag.color : 'var(--tag-blue)'
                    };
                });

                // Clean up files data before sending
                const cleanFiles = uploadedFiles.map(file => {
                    // Create a clean copy without any functions or complex objects
                    return {
                        name: String(file.name),
                        path: String(file.path || ''),
                        uploadedAt: String(file.uploadedAt || new Date().toISOString()),
                        uploadedBy: {
                            userId: String(file.uploadedBy?.userId || USER_ID),
                            name: String(file.uploadedBy?.name || USER_NAME)
                        },
                        data: Object.fromEntries(
                            Object.entries(file.data || {}).map(([key, value]) => [key, String(value || '')])
                        ),
                        lastEditedBy: file.lastEditedBy ? {
                            userId: String(file.lastEditedBy.userId),
                            name: String(file.lastEditedBy.name),
                            timestamp: String(file.lastEditedBy.timestamp)
                        } : null
                    };
                });

                const response = await fetch('src/model/save_literature_matrix.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        projectId: String(PROJECT_ID),
                        selectedTags: selectedTagsArray,
                        files: cleanFiles
                    })
                });

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Failed to save changes');
                }
            } catch (error) {
                console.error('Failed to save to database:', error);
                alert('Failed to save changes. Please try again.');
            }
        }

        // Export table to Excel
        function exportTable() {
            const table = document.getElementById('literatureTable');
            const wb = XLSX.utils.table_to_book(table, { sheet: "Literature Matrix" });
            XLSX.writeFile(wb, `literature_matrix_${PROJECT_ID}.xlsx`);
        }

        // Initialize with some default selected tags
        window.onload = function() {
            // Select some default tags
            ['Publication Year', 'Research Aim / Objective', 'Methodology', 'Findings'].forEach(tagName => {
                selectedTags.add(tagName);
            });
            init();
            
            // Listen for theme changes from navbar
            document.addEventListener('themeChanged', function(event) {
                // Theme change is already handled by CSS variables
                // We can add any additional logic here if needed
                console.log('Theme changed to:', event.detail.theme);
            });
        };
    </script>
</body>
</html>
