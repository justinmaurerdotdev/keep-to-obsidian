<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 9:08 PM
 */

namespace KeepToObsidian;

class FileHelpers
{
    public function untrailingSlashIt(string $string): string
    {
        return rtrim($string, '/\\');
    }

    public function trailingSlashIt(string $string): string
    {
        return $this->untrailingslashit($string) . '/';
    }

	public function assertDirectoryExists(string $dir): void
	{
		if (!is_dir($dir) && !mkdir($dir) && !is_dir($dir)) {
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
		}
	}

}
