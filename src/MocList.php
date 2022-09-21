<?php

namespace KeepToObsidian;

class MocList {
	private const DEFAULT = 'DEFAULT';

	/** @var Moc[] */
	private array $list = [];

	public function addNote(KeepJSONMarkdownConverter $note): void {
		foreach ($note->labels as $label) {
			if ($label === self::DEFAULT) {
				continue;
			}
			if (!isset($this->list[$label])) {
				$this->list[$label] = new Moc($label);
			}
			$this->list[$label]->addNote($note);
		}
	}

	public function writeAll(string $basePath): void {
		foreach ($this->list as $moc) {
			$moc->write($basePath);
		}
	}

}
