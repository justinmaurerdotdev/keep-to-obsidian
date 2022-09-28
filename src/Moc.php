<?php

namespace KeepToObsidian;

use ABGEO\MDGenerator\Document;
use ABGEO\MDGenerator\Element;

/**
 * Creates an initial Map Of Content
 */
class Moc
{
    private Document $document;
    private string $name;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->document = new Document();
        $this->document->addElement(Element::createHeading('MOC ' . $name, '1'));
        $this->document->addElement(Element::createBreak());
    }

    public function addNote(KeepJSONMarkdownConverter $note): void
    {
        // TODO: is create/change date relevant for MOC?

        $title = $note->title ?: $note->filename;
        $filename = $note->filename;

        $list = [
            sprintf("[%s](%s)", $title, $filename),
        ];

        $this->document->addElement(Element::createList($list));
    }

    public function write(string $basePath): void
    {
        file_put_contents($basePath . '/MOC ' . $this->name . '.md', $this->document);
    }
}
