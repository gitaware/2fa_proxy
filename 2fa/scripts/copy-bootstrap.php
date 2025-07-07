<?php
$srcDir = __DIR__ . '/../vendor/npm-asset/bootstrap/dist';
$destDir = __DIR__ . '/../public/assets/bootstrap';

// Recursively copy a folder
function copyFolder($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0775, true);
    }

    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $srcPath = "$src/$file";
        $dstPath = "$dst/$file";

        if (is_dir($srcPath)) {
            copyFolder($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    closedir($dir);
}

// Do the copy
if (is_dir($srcDir)) {
    copyFolder($srcDir, $destDir);
    echo "✅ Bootstrap copied to /2fa/public/assets/bootstrap\n";
} else {
    echo "❌ Bootstrap source directory not found: $srcDir\n";
    exit(1);
}
