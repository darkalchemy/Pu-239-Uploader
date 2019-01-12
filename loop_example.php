<?php

// This can be used to upload many folders at once, but only if an nfo is present.

$category = 41; // set this to the category that you uploads will go to

if (!empty($argv[1])) {
    $path = $argv[1];
} else {
    die("You must pass the parent path of the data to upload\n{$argv[0]} \"/path/to/data\"\n\n");
}

foreach (new DirectoryIterator($path) as $fileinfo) {
    $dir = $fileinfo->getPathname();
    if ($fileinfo->isDot() || !is_dir($dir)) {
        continue;
    }

    $nfo = '';
    foreach (new DirectoryIterator($dir) as $info) {
        $file = $info->getPathname();
        if ($info->isDot() || is_dir($file)) {
            continue;
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $size = @filesize($file);
        if ($ext === 'nfo' && $size > 0) {
            $nfo = $file;
        }
    }

    if (!empty($nfo)) {
        $command = "php upload.php $category \"$dir\" \"$nfo\"";
        passthru($command);
    }
}

