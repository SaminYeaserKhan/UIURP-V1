<?php
/**
 * Migration Helper Script
 * 
 * This script helps identify files that need to be updated to use the new
 * secure download proxy system.
 * 
 * Run from command line: php migration_helper.php
 */

echo "=== File Management System Migration Helper ===\n\n";

// Files to scan
$filesToScan = [
    'Project_details.php',
    'post_details.php',
    'view_posts.php',
    'Research_page.php',
    'Faculty_Profile.php',
    'Student_Profile.php'
];

echo "Scanning for direct file access patterns...\n\n";

$totalIssues = 0;

foreach ($filesToScan as $file) {
    if (!file_exists($file)) {
        continue;
    }
    
    echo "Checking: $file\n";
    echo str_repeat("-", 50) . "\n";
    
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $fileIssues = 0;
    
    // Pattern 1: href="storage/
    // Pattern 2: href="uploads/
    // Pattern 3: href='storage/
    // Pattern 4: href='uploads/
    // Pattern 5: src="storage/
    // Pattern 6: src="uploads/
    
    foreach ($lines as $lineNum => $line) {
        $actualLineNum = $lineNum + 1;
        
        // Check for direct storage/uploads access
        if (preg_match('/href=["\'](?:\/)?(?:storage|uploads)\//', $line) || 
            preg_match('/src=["\'](?:\/)?(?:storage|uploads)\//', $line)) {
            
            // Skip if already using download.php
            if (strpos($line, 'download.php') !== false) {
                continue;
            }
            
            echo "  Line $actualLineNum: " . trim($line) . "\n";
            $fileIssues++;
        }
    }
    
    if ($fileIssues > 0) {
        echo "  Found $fileIssues issue(s)\n";
        $totalIssues += $fileIssues;
    } else {
        echo "  ✓ No issues found\n";
    }
    
    echo "\n";
}

echo "=== Summary ===\n";
echo "Total issues found: $totalIssues\n\n";

if ($totalIssues > 0) {
    echo "Action Required:\n";
    echo "1. Update direct file links to use download.php proxy\n";
    echo "2. Example fix:\n";
    echo "   OLD: <a href=\"storage/files/file.pdf\">Download</a>\n";
    echo "   NEW: <a href=\"download.php?file=storage/files/file.pdf\">Download</a>\n\n";
    echo "3. For dynamic paths in PHP:\n";
    echo "   OLD: <a href=\"<?= \$file['path'] ?>\">Download</a>\n";
    echo "   NEW: <a href=\"download.php?file=<?= urlencode(ltrim(\$file['path'], '/')) ?>\">Download</a>\n\n";
    echo "4. For JavaScript:\n";
    echo "   OLD: window.location.href = filePath;\n";
    echo "   NEW: window.location.href = '/download.php?file=' + encodeURIComponent(filePath);\n\n";
}

echo "See FILE_MANAGEMENT_README.md for complete migration guide.\n";
?>
