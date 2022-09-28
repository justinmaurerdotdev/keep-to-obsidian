<?php

namespace KeepToObsidian;

class Starred {

	private array $starred;

	public function __construct() {
		$this->starred = [
			'items' => [],
		];
	}


	public function addNote(KeepJSONMarkdownConverter $note): void {
		$this->starred['items'][] = [
			'type'  => 'file',
			'title' => $note->title,
			'path'  => $note->filename,
		];
	}

	/**
	 * @param string $basePath
	 * @return void
	 * @throws \JsonException
	 */
	public function write(string $basePath): void {
		file_put_contents(
			$basePath . '.obsidian/starred.json',
			json_encode($this->starred, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
		);
	}
}
