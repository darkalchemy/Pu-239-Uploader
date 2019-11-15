<?php

require_once 'vendor/autoload.php';
require_once 'config.php';

use Curl\Curl;

if (empty($config) || empty($config['path']) || !file_exists($config['path']) || !is_dir($config['path'])) {
    die("The path to the data to upload must be a folder, not a file.\n");
}

$nfo = '';
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['path'], RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
foreach ($objects as $name => $object) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $size = @filesize($name);
    if ($ext === 'nfo' && $size > 0) {
        $nfo = $name;
        if (empty($config['descr'])) {
            $config['descr'] = $name;
        }
    }
}
$name = basename($config['path']);
$dirsize = GetDirectorySize($config['path']);
echo "Preparing to upload => $name\n";
$mb = bytes_to_megabytes($dirsize);
$pieces = get_piece_size($mb);
echo "$mb => $pieces = " . ceil($dirsize / 1024 / 1024 / $pieces) . " pieces\n";
$torrent = create_torrent($name, $pieces, $config);

try {
    $search = curl_search($name, $config);
} catch (ErrorException $e) {
    // TODO
}
if (!empty($search['msg'])) {
    echo $search['msg'] . "\n";
} else {
    echo "Torrent Exists\nid: {$search[0]['id']}\nname: {$search[0]['name']}\n";
    die();
}

try {
    upload_torrent($torrent, $name, $nfo, $config);
} catch (ErrorException $e) {
    // TODO
}
try {
    $search = curl_search($name, $config);
} catch (ErrorException $e) {
    // TODO
}
if (!empty($search[0]['id'])) {
    try {
        download_torrent($torrent, $config, $search[0]['id']);
    } catch (ErrorException $e) {
        // TODO
    }
    if (file_exists($torrent)) {
        echo "$torrent downloaded successfully from {$config['url']}\n";
    } else {
        echo "$torrent failed to downloaded from {$config['url']}\n";
    }
}

/**
 * @param string $torrent
 * @param array $config
 * @param int $tid
 *
 * @throws ErrorException
 */
function download_torrent(string $torrent, array $config, int $tid)
{
    $curl = new Curl();
    if (file_exists($torrent)) {
        unlink($torrent);
    }
    $curl->download($config['url'] . "/download.php?torrent={$tid}&torrent_pass={$config['torrent_pass']}", $torrent);
    $curl->close();
}

/**
 *
 * @param string $name
 * @param array $config
 *
 * @return mixed
 * @throws ErrorException
 *
 */
function curl_search(string $name, array $config)
{
    $name = getname($name);
    $curl = new Curl();
    $curl->post($config['url'] . '/search.php', [
        'bot' => $config['username'],
        'torrent_pass' => $config['torrent_pass'],
        'auth' => $config['auth'],
        'search' => $name,
    ]);

    if ($curl->error) {
        echo 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        die();
    } else {
        return json_decode(json_encode($curl->response), true);
    }
}

/**
 *
 * @param string $name
 * @param int $cat
 * @param string $torrent
 * @param string $body
 * @param string $nfo
 * @param array $config
 *
 * @return mixed|string
 * @throws ErrorException
 *
 */
function curl_post(string $name, int $cat, string $torrent, string $body, string $nfo, array $config)
{
    $curl = new Curl();
    $curl->post($config['url'] . '/takeupload.php', [
        'bot' => $config['username'],
        'torrent_pass' => $config['torrent_pass'],
        'auth' => $config['auth'],
        'name' => "$name",
        'type' => $cat,
        'file' => "@$torrent",
        'nfo' => "@$nfo",
        'body' => "$body",
    ]);

    if ($curl->error) {
        $response = 'Error: ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
    } else {
        $response = json_decode(json_encode($curl->response), true);
    }
    $curl->close();

    return $response;
}

/**
 * @param string $torrent
 * @param string $name
 * @param string $nfo
 * @param array $config
 *
 * @throws ErrorException
 */
function upload_torrent(string $torrent, string $name, string $nfo, array $config)
{
    $desc = file_get_contents($config['descr']);
    $response = curl_post($name, $config['category'], $torrent, $desc, $nfo, $config);
    echo $response . "\n";
}

/**
 * @param int $mb
 *
 * @return int
 */
function get_piece_size(int $mb)
{
    switch (true) {
        case $mb >= 484352:
            return 28;

        case $mb >= 194560:
            return 27;

        case $mb >= 73728:
            return 26;

        case $mb >= 16384:
            return 25;

        case $mb >= 8192:
            return 23;

        case $mb >= 4096:
            return 22;

        case $mb >= 2048:
            return 21;

        case $mb >= 1024:
            return 20;

        case $mb >= 512:
            return 19;

        case $mb >= 256:
            return 18;

        default:
            return 16;
    }
}

/**
 * @param int $bytes
 *
 * @return string
 */
function bytes_to_megabytes(int $bytes)
{
    return ceil($bytes / 1024 / 1024);
}

/**
 * @param string $name
 * @param int $pieces
 * @param array $config
 *
 * @return string
 */
function create_torrent(string $name, int $pieces, array $config)
{
    $announce = $config['url'] . '/announce.php';
    $comment = 'Thanks for downloading!';
    $command = "mktorrent -l{$pieces} -a{$announce} -c'{$comment}' -o\"{$name}.torrent\" \"{$config['path']}\"";
    if (file_exists("{$name}.torrent")) {
        unlink("{$name}.torrent");
    }
    passthru($command);

    return "{$name}.torrent";
}

/**
 * @param string $path
 *
 * @return int
 */
function GetDirectorySize(string $path)
{
    $bytestotal = $files = 0;
    $path = realpath($path);
    if ($path !== false && !empty($path) && is_dir($path)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
            $bytestotal += $object->getSize();
            ++$files;
        }
    }

    return $bytestotal;
}

/**
 * @param string $name
 *
 * @return mixed|string
 */
function getname(string $name)
{
    $name = str_ireplace('.torrent', '', $name);
    $name = str_ireplace('H.264', 'H_264', $name);
    $name = str_ireplace('7.1', '7_1', $name);
    $name = str_ireplace('5.1', '5_1', $name);
    $name = str_ireplace('2.1', '2_1', $name);
    $name = str_ireplace('.', ' ', $name);
    $name = str_ireplace('H_264', 'H.264', $name);
    $name = str_ireplace('7_1', '7.1', $name);
    $name = str_ireplace('5_1', '5.1', $name);
    $name = str_ireplace('2_1', '2.1', $name);

    return $name;
}
