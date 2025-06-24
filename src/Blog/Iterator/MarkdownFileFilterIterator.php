<?php

declare (strict_types=1);

namespace MarkdownBlog\Iterator;

use DirectoryIterator;
use SplFileInfo;
/**
 * Class MarkdownFileFilterIterator
 */
class MarkdownFileFilterIterator extends \FilterIterator
{
    /**
     * @param \DirectoryIterator $iterator
     * @return void
     */
    public function __construct(DirectoryIterator $iterator)
    {
        parent::__construct($iterator);
        $this->rewind();
    }
    /**
     * @return bool
     */
    public function accept(): bool
    {
        /** @var SplFileInfo $item */
        $item = $this->getInnerIterator()->current();
        if (!$item instanceof SplFileInfo) {
            return false;
        }
        if ($item->isDir() || !$item->isFile() || !$item->isReadable()) {
            return false;
        }
        if (!in_array($item->getExtension(), ['md', 'markdown'])) {
            return false;
        }
        return true;
    }
}