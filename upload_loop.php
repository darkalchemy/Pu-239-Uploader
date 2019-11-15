<?php

// This can be used to upload many folders at once.
if (!empty($argv[1]) && is_numeric($argv[1]) && !empty($argv[2])) {
    $category = (int) $argv[1];
    $path = $argv[2];
} else {
    die("You must pass the category and parent path of the data to upload\n{$argv[0]} category \"/path/to/data\"\n\n");
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

    $command = "php upload.php $category \"$dir\" \"$nfo\"";
    passthru($command);
}
