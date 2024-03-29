<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 8:26 PM
 */

use KeepToObsidian\FileHelpers;
use KeepToObsidian\KeepJSONMarkdownConverter;
use KeepToObsidian\MocList;
use KeepToObsidian\Starred;

require 'vendor/autoload.php';

if (!isset($argv) || !is_array($argv) || count($argv) < 2) {
    exit("No file path given\n");
}

// $argv[0] is the name of the script
$sourcePath = $argv[1];
if (!is_dir($sourcePath)) {
    exit('Not a directory: ' . $sourcePath . PHP_EOL);
}
$sourcePath = FileHelpers::trailingSlashIt($sourcePath);
$mocList = new MocList();
$starred = new Starred();

FileHelpers::assertDirectoryExists($sourcePath . 'md/');
FileHelpers::assertDirectoryExists($sourcePath . 'md/.obsidian');
FileHelpers::assertDirectoryExists($sourcePath . 'md/' . KeepJSONMarkdownConverter::ARCHIVE_DIR);
FileHelpers::assertDirectoryExists($sourcePath . 'md/' . KeepJSONMarkdownConverter::ATTACHMENT_DIR);

$files = glob($sourcePath . "*.json");
foreach ($files as $file) {
    $note = file_get_contents($file);

    try {
        $note = json_decode($note, false, 512, JSON_THROW_ON_ERROR);
        if (!($note instanceof \stdClass)) {
            throw new \RuntimeException('not a json document');
        }
        $converter = new KeepJSONMarkdownConverter($note);
        if ($converter->isTrashed) {
            continue;
        }
        file_put_contents($sourcePath . 'md/' . $converter->filename, $converter->document);
        foreach ($converter->getFilesToCopy($sourcePath) as [$srcFilename, $dstFilename]) {
            copy(
                $sourcePath . $srcFilename,
                $sourcePath . 'md/' . $dstFilename
            );
        }
        if ($converter->isPinned) {
            $starred->addNote($converter);
        }
        $mocList->addNote($converter);
    } catch (\Throwable $e) {
        echo $e->getMessage();
        echo 'File: ' . $file;
    }
}

$starred->write($sourcePath . 'md/');
$mocList->writeAll($sourcePath . 'md/');

exit("All done\n");
