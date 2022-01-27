<?php

/**
 * Created by: justinmaurer
 * Date: 1/25/22
 * Time: 9:19 PM
 */

namespace KeepToMarkdown;

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
            $this->initTitle($json_note->title);
        }
        if (property_exists($json_note, 'annotations')) {
            $this->initAnnotations($json_note->annotations);
        }

        $this->processDocumentPieces();
    }

    /**
     * @param $color
     *
     * @return void
     */
    private function initColor($color)
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
    private function initIsTrashed($isTrashed)
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
    private function initIsPinned($isPinned)
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
    private function initIsArchived($isArchived)
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
    private function initTextContent($textContent)
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
    private function initListContent($listContent)
    {
        if (is_array($listContent)) {
            $this->listContent = $listContent;
        }
    }

    /**
     * @throws Exception
     */
    private function initTitle($title)
    {
        if (is_string($title) && $title) {
            echo $title . "\r\n";
            $this->title = $title;
            $slugGenerator = new SlugGenerator((new SlugOptions())
                ->setDelimiter(' ')
                ->setValidChars('a-zA-Z0-9'));
        } else {
            if (isset($this->createdTime)) {
                $this->title = $this->createdTime->format('Y-m-d-h-i-s');
                $slugGenerator = new SlugGenerator((new SlugOptions())->setDelimiter('-'));
            } else {
                throw new Exception("No usable title can be derived from this note.");
            }
        }
        $this->filename = $slugGenerator->generate($this->title, ['validChars' => 'A-Za-z0-9']) . '.md';
    }

    /**
     * @param $userEditedTimestampUsec
     *
     * @return void
     */
    private function initUserEditedTimestampUsec($userEditedTimestampUsec)
    {
        if (is_int($userEditedTimestampUsec)) {
            // divide by one million because these are microseconds and unix time uses seconds
            $unixTime = (int) ($userEditedTimestampUsec / 1000000);
            $this->modifiedTime = new DateTime("@$unixTime");
        }
    }

    /**
     * @param $createdTimestampUsec
     *
     * @return void
     */
    private function initCreatedTimestampUsec($createdTimestampUsec)
    {
        if (is_int($createdTimestampUsec)) {
            // divide by one million because these are microseconds and unix time uses seconds
            $unixTime = (int) ($createdTimestampUsec / 1000000);
            $this->createdTime = new DateTime("@$unixTime");
        }
    }

    /**
     * @param $annotations
     *
     * @return void
     */
    private function initAnnotations($annotations)
    {
        if (is_array($annotations)) {
            $this->annotations = $annotations;
        }
    }

    /**
     * @return void
     */
    private function processDocumentPieces()
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

        if (isset($this->annotations)) {
            foreach ($this->annotations as $annotation) {
                if ($annotation instanceof stdClass && isset($annotation->url)) {
                    $new_lines = [];
                    $new_lines[] = Element::createBold("[$annotation->title]($annotation->url)");
                    $new_lines[] = Element::createBreak();
                    $new_lines[] = Element::createBold("$annotation->description");
                    $new_lines[] = Element::createBreak();
                    $new_lines[] = Element::createBreak();
                    $this->document->addElement(Element::concatenateElements(...$new_lines));
                }
            }
        }
    }
}
