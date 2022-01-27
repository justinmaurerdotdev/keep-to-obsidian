<?php
/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 9:08 PM
 */

namespace KeepToMarkdown;

class FileHelpers {
	public function untrailingslashit($string): string {
		return rtrim( $string, '/\\' );
	}

	public function trailingslashit($string): string {
		return $this->untrailingslashit($string) . '/';
	}
}
