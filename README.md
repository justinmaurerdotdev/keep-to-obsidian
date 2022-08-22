# Keep to Obsidian

This is a PHP-based tool to convert Google Keep notes to Obsidian notes. It assumes that you have PHP 7.4+ and [Composer](https://getcomposer.org/) installed and working. 

It assumes that you have downloaded the backups of your Keep data from [Google Takeout](https://takeout.google.com/).

Included in those files are .json files, which are used for ease instead of parsing the HTML files. You will need to unzip the Takeout backup into a convenient location.

From the `keep-to-markdown` directory, run the following to install the necessary dependencies and convert your notes.
Give it the path to the downloaded Keep backup files. Maybe `~/Downloads/Takeout/Keep/`.

```
composer install

php process-keep-json.php path/to/files/
```

This will create a directory (if it does not already exist) called `md/` in the directory that you give it. 
This new directory will be full of Markdown files and can be used as your new Obsidian vault. You will likely want to rename it and move it to your Obsidian directory.

Archived notes will be placed in the `md/archive/` subdirectory.
Pinned notes will be converted into starred notes.
Labels in Keep will be added as tags in MarkDown.

**Warning**: this process overwrites `md/.obsidian/starred.json` when pinned notes
in Keep are being converted.

## Not supported

Not all properties of a note in Keep will be converted. These are:

- color
- createdTimestampUsec
- userEditedTimestampUsec


License: *GPL-3.0-only*
The license can be seen in the LICENSE file.
