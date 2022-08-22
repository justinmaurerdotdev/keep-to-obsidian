<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 8:26 PM
 */

use KeepToObsidian\FileHelpers;
use KeepToObsidian\KeepJSONMarkdownConverter;

require 'vendor/autoload.php';

if (isset($argv) && is_array($argv)) {
    // $argv[0] is the name of the script
    $html_file_path = $argv[1];

    if ($html_file_path) {
        $file_helper = new FileHelpers();
        $html_file_path = $file_helper->trailingslashit($html_file_path);
        if (is_dir($html_file_path)) {
	        if (!is_dir($html_file_path . 'md/')) {
		        mkdir($html_file_path . 'md/');
	        }
	        if (!is_dir($html_file_path . 'md/.obsidian')) {
		        mkdir($html_file_path . 'md/.obsidian');
	        }
	        if (!is_dir($html_file_path . 'md/' . KeepJSONMarkdownConverter::ARCHIVE_DIR)) {
		        mkdir($html_file_path . 'md/' . KeepJSONMarkdownConverter::ARCHIVE_DIR);
	        }
            $files = glob($html_file_path . "*.json");
			$starred = [
				'items' => [],
			];
            foreach ($files as $file) {
                $note = file_get_contents($file);
                $note = json_decode($note);

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
	        file_put_contents($html_file_path . 'md/.obsidian/starred.json', json_encode($starred, JSON_PRETTY_PRINT));
        }
    }
    exit('All done');
} else {
    exit('No file path given');
}
