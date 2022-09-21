<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 8:26 PM
 */

use KeepToObsidian\FileHelpers;
use KeepToObsidian\KeepJSONMarkdownConverter;

require 'vendor/autoload.php';

if (!isset($argv) || !is_array($argv) || count($argv) < 2) {
	exit('No file path given');
}

// $argv[0] is the name of the script
$sourcePath = $argv[1];

$sourcePath = FileHelpers::trailingSlashIt($sourcePath);

if (is_dir($sourcePath)) {
	FileHelpers::assertDirectoryExists($sourcePath . 'md/');
	FileHelpers::assertDirectoryExists($sourcePath . 'md/.obsidian');
	FileHelpers::assertDirectoryExists($sourcePath . 'md/' . KeepJSONMarkdownConverter::ARCHIVE_DIR);
	FileHelpers::assertDirectoryExists($sourcePath . 'md/' . KeepJSONMarkdownConverter::ATTACHMENT_DIR);
	$files = glob($sourcePath . "*.json");
	$starred = [
		'items' => [],
	];
	foreach ($files as $file) {
		$note = file_get_contents($file);
		$note = json_decode($note, false, 512, JSON_THROW_ON_ERROR);

		if ($note instanceof stdClass) {
			try {
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
					$starred['items'][] = [
						'type'  => 'file',
						'title' => $converter->title,
						'path'  => $converter->filename,
					];
				}
			} catch (Exception $e) {
				echo $e->getMessage();
				echo 'File: ' . $file;
			}
		}
	}
	file_put_contents(
		$sourcePath . 'md/.obsidian/starred.json',
		json_encode($starred, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
	);
}
exit('All done');
