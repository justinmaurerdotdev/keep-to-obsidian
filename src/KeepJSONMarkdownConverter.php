<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 9:19 PM
 */

namespace KeepToObsidian;

use ABGEO\MDGenerator\Document;
use ABGEO\MDGenerator\Element;
use Ausi\SlugGenerator\SlugGenerator;
use Ausi\SlugGenerator\SlugOptions;
use DateTime;
use Exception;
use stdClass;

/**
 * Takes a Google Keep note in .json form and makes a
 * ABGEO\MDGenerator\Document ($this->document) that can be used as a string.
 */
class KeepJSONMarkdownConverter
{
    public const ARCHIVE_DIR = 'Archive/';
    public const ATTACHMENT_DIR = 'Attachments/';

    /**
     * @var string
     */
    public string $color;
    /**
     * @var bool
     */
    public bool $isTrashed;
    /**
     * @var bool
     */
    public bool $isPinned;
    /**
     * @var bool
     */
    public bool $isArchived;
    /**
     * @var string
     */
    public string $textContent;
    /**
     * @var array
     */
    public array $listContent;
    /**
     * @var string
     */
    public string $title;
    /**
     * @var DateTime
     */
    public DateTime $modifiedTime;
    /**
     * @var DateTime
     */
    public DateTime $createdTime;
    /**
     * @var array
     */
    public array $annotations;
    /**
     * @var string[]
     */
    public array $labels;
    /**
     * @var array
     */
    public array $attachments;
    /**
     * @var string[]
     */
    public array $attachmentsToCopy = [];
    /**
     * @var Document
     */
    public Document $document;
    /**
     * @var string
     */
    public string $filename;

    /**
     * @param stdClass $json_note Should be the JSON representation of a Google Keep note
     *
     * @throws Exception
     */
    public function __construct(stdClass $json_note)
    {
        $this->document = new Document();
        if (property_exists($json_note, 'color')) {
            $this->initColor($json_note->color);
        }
        if (property_exists($json_note, 'isTrashed')) {
            $this->initIsTrashed($json_note->isTrashed);
        }
        if (property_exists($json_note, 'isPinned')) {
            $this->initIsPinned($json_note->isPinned);
        }
        if (property_exists($json_note, 'isArchived')) {
            $this->initIsArchived($json_note->isArchived);
        }
        if (property_exists($json_note, 'textContent')) {
            $this->initTextContent($json_note->textContent);
        }
        if (property_exists($json_note, 'listContent')) {
            $this->initListContent($json_note->listContent);
        }
        if (property_exists($json_note, 'userEditedTimestampUsec')) {
            $this->initUserEditedTimestampUsec($json_note->userEditedTimestampUsec);
        }
        if (property_exists($json_note, 'createdTimestampUsec')) {
            $this->initCreatedTimestampUsec($json_note->createdTimestampUsec);
        }
        if (property_exists($json_note, 'title')) {
            $this->initTitle($json_note);
        }
        if (property_exists($json_note, 'annotations')) {
            $this->initAnnotations($json_note->annotations);
        }
        if (property_exists($json_note, 'labels')) {
            $this->initLabels($json_note->labels);
        }
        if (property_exists($json_note, 'attachments')) {
            $this->initAttachments($json_note->attachments);
        }

        $this->processDocumentPieces();
    }

    /**
     * @param $color
     *
     * @return void
     */
    private function initColor($color): void
    {
        if (is_string($color)) {
            $this->color = $color;
        }
    }

    /**
     * @param $isTrashed
     *
     * @return void
     */
    private function initIsTrashed($isTrashed): void
    {
        if (is_bool($isTrashed)) {
            $this->isTrashed = $isTrashed;
        }
    }

    /**
     * @param $isPinned
     *
     * @return void
     */
    private function initIsPinned($isPinned): void
    {
        if (is_bool($isPinned)) {
            $this->isPinned = $isPinned;
        }
    }

    /**
     * @param $isArchived
     *
     * @return void
     */
    private function initIsArchived($isArchived): void
    {
        if (is_bool($isArchived)) {
            $this->isArchived = $isArchived;
        }
    }

    /**
     * @param $textContent
     *
     * @return void
     */
    private function initTextContent($textContent): void
    {
        if (is_string($textContent)) {
            $this->textContent = $textContent;
        }
    }

    /**
     * @param $listContent
     *
     * @return void
     */
    private function initListContent($listContent): void
    {
        if (is_array($listContent)) {
            $this->listContent = $listContent;
        }
    }

    /**
     * @throws Exception
     */
    private function initTitle(\stdClass $json_note): void
    {
        $slugGenerator = new SlugGenerator((new SlugOptions())
            ->setDelimiter(' ')
            ->setValidChars('a-zA-Z0-9'));
        if (is_string($json_note->title) && $json_note->title) {
            echo $json_note->title . "\r\n";
            $this->title = $json_note->title;
        } elseif (
            isset($json_note->annotations)
            && count($json_note->annotations) === 1
            && $json_note->textContent === $json_note->annotations[0]->url
        ) {
            $this->title = $json_note->annotations[0]->title;
        } elseif (isset($this->createdTime)) {
            $this->title = $this->createdTime->format('Y-m-d-h-i-s');
            $slugGenerator = new SlugGenerator((new SlugOptions())->setDelimiter('-'));
        } else {
            throw new \RuntimeException("No usable title can be derived from this note.");
        }
        $this->filename = $slugGenerator->generate($this->title, ['validChars' => 'A-Za-z0-9']) . '.md';
        if ($this->isArchived) {
            $this->filename = self::ARCHIVE_DIR . $this->filename;
        }
    }

    /**
     * @param $userEditedTimestampUsec
     *
     * @return void
     */
    private function initUserEditedTimestampUsec($userEditedTimestampUsec): void
    {
        if (is_int($userEditedTimestampUsec)) {
            // divide by one million because these are microseconds and unix time uses seconds
            $unixTime = (int)($userEditedTimestampUsec / 1000000);
            $this->modifiedTime = new DateTime("@$unixTime");
        }
    }

    /**
     * @param $createdTimestampUsec
     *
     * @return void
     */
    private function initCreatedTimestampUsec($createdTimestampUsec): void
    {
        if (is_int($createdTimestampUsec)) {
            // divide by one million because these are microseconds and unix time uses seconds
            $unixTime = (int)($createdTimestampUsec / 1000000);
            $this->createdTime = new DateTime("@$unixTime");
        }
    }

    /**
     * @param $annotations
     *
     * @return void
     */
    private function initAnnotations($annotations): void
    {
        if (is_array($annotations)) {
            $this->annotations = $annotations;
        }
    }

    /**
     * @param $labels
     *
     * @return void
     */
    private function initLabels($labels): void
    {
        if (is_array($labels)) {
            $this->labels = array_map(static function ($label) {
                return $label->name;
            }, $labels);
        }
    }

    /**
     * @param $attachments
     *
     * @return void
     */
    private function initAttachments($attachments): void
    {
        if (is_array($attachments)) {
            $this->attachments = $attachments;
        }
    }

    /**
     * @return void
     */
    private function processDocumentPieces(): void
    {
        if (isset($this->title)) {
            $this->document->addElement(Element::createHeading($this->title, '1'));
            $this->document->addElement(Element::createBreak());
        }

        if (isset($this->textContent)) {
            $paragraphs = explode('\n\n', $this->textContent);
            foreach ($paragraphs as $paragraph) {
                $lines = explode('\n', $paragraph);
                $new_lines = [];
                foreach ($lines as $line) {
                    $new_lines[] = $line;
                    $new_lines[] = Element::createBreak();
                }
                $new_lines[] = Element::createBreak();
                $this->document->addElement(Element::concatenateElements(...$new_lines));
            }
        }

        if (isset($this->listContent)) {
            $list = [];
            foreach ($this->listContent as $listItem) {
                if ($listItem instanceof stdClass && isset($listItem->text)) {
                    $entry = isset($listItem->isChecked) && $listItem->isChecked ? '[x]' : '[ ]';
                    $entry .= ' ' . $listItem->text;
                    $list[] = $entry;
                }
            }
            $this->document->addElement(Element::createList($list));
        }

        if (isset($this->attachments)) {
            $new_lines = [];
            foreach ($this->attachments as $attachment) {
                if (strpos($attachment->mimetype, 'image/') === 0) {
                    $basename = basename($attachment->filePath);
                    $dstFilename = ($this->isArchived ? '../' : '') . self::ATTACHMENT_DIR . $basename;
                    $new_lines[] = Element::createImage($dstFilename, $basename, $basename);
                    $this->attachmentsToCopy[] = $attachment->filePath;
                } else {
                    // No support for recorded audio yet
                    throw new \RuntimeException('Unknown mimetype:' . $attachment->mimetype);
                }
            }
            $this->document->addElement(Element::concatenateElements(...$new_lines));
        }

        if (isset($this->annotations)) {
            foreach ($this->annotations as $annotation) {
                if ($annotation instanceof stdClass && isset($annotation->url)) {
                    $new_lines = [];
                    $new_lines[] = Element::createBold("[$annotation->title]($annotation->url)");
                    $new_lines[] = Element::createBreak();
                    $new_lines[] = Element::createBold((string)$annotation->description);
                    $new_lines[] = Element::createBreak();
                    $new_lines[] = Element::createBreak();
                    $this->document->addElement(Element::concatenateElements(...$new_lines));
                }
            }
        }

        if (!isset($this->labels)) {
            $this->labels = ['nolabel'];
        }
        if (isset($this->color) && $this->color !== 'DEFAULT') {
            $this->labels[] = $this->color;
        }
        $tags = array_map(static function (string $label) {
            return '#' . str_replace(' ', '', $label);
        }, $this->labels);
        if ($tags) {
            $this->document->addElement(Element::createBreak());
            $this->document->addElement(Element::createParagraph(implode(' ', $tags)));
        }
    }

    /**
     * @param string $basePath
     * @return string[][]
     */
    public function getFilesToCopy(string $basePath): array
    {
        $result = [];
        foreach ($this->attachmentsToCopy as $srcFilename) {
            $dstFilename = $srcFilename;
            if (!file_exists($basePath . $srcFilename)) {
                // bug in Google Takeout? Some attachments seem to have the wrong extension
                $srcFilename = str_replace('.jpeg', '.jpg', $srcFilename);
                if (!file_exists($basePath . $srcFilename)) {
                    echo $this->title . ': attachment not found: ' . $basePath .
                        $dstFilename . PHP_EOL;
                    continue;
                }
            }
            $result[] = [
                $srcFilename,
                self::ATTACHMENT_DIR . basename($dstFilename),
            ];
        }

        return $result;
    }
}
