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
$html_file_path = $argv[1];

$file_helper = new FileHelpers();
$html_file_path = $file_helper->trailingSlashIt($html_file_path);
if (is_dir($html_file_path)) {
	$file_helper->assertDirectoryExists($html_file_path . 'md/');
	$file_helper->assertDirectoryExists($html_file_path . 'md/.obsidian');
	$file_helper->assertDirectoryExists($html_file_path . 'md/' . KeepJSONMarkdownConverter::ARCHIVE_DIR);
	$files = glob($html_file_path . "*.json");
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
				file_put_contents($html_file_path . 'md/' . $converter->filename, $converter->document);
				if (isset($converter->attachmentsToCopy)) {
					foreach ($converter->attachmentsToCopy as $srcFilename) {
						$dstFilename = $srcFilename;
						if (!file_exists($html_file_path . $dstFilename)) {
							// bug in Google Takeout? Some attachments seem to have the wrong extension
							$srcFilename = str_replace('.jpeg', '.jpg', $srcFilename);
							if (!file_exists($html_file_path . $srcFilename)) {
								echo $converter->title . ': attachment not found: ' . $html_file_path .
									$dstFilename . PHP_EOL;
								continue;
							}
						}
						if ($converter->isArchived) {
							$dstFilename = KeepJSONMarkdownConverter::ARCHIVE_DIR . $dstFilename;
						}
						copy($html_file_path . $srcFilename, $html_file_path . 'md/' . $dstFilename);
					}
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
		$html_file_path . 'md/.obsidian/starred.json',
		json_encode($starred, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
	);
}
exit('All done');
