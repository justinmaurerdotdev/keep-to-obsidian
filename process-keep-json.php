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
            $files = glob($html_file_path . "*.json");
            foreach ($files as $file) {
                $note = file_get_contents($file);
                $note = json_decode($note);

                if ($note instanceof stdClass) {
                    try {
                        $converter = new KeepJSONMarkdownConverter($note);
						if ($converter->isTrashed) {
							continue;
						}
                        if (!is_dir($html_file_path . 'md/')) {
                            mkdir($html_file_path . 'md/');
                        }
                        file_put_contents($html_file_path . 'md/' . $converter->filename, $converter->document);
                    } catch (Exception $e) {
                        echo $e->getMessage();
                        echo 'File: ' . $file;
                    }
                }
            }
        }
    }
    exit('All done');
} else {
    exit('No file path given');
}
