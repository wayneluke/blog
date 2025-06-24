<?php

declare (strict_types=1);

namespace MarkdownBlog\Iterator;

use DateTime, Iterator;
use MarkdownBlog\Entity\BlogItem;
/**
 * Class PublishedItemFilterIterator
 */
class PublishedItemFilterIterator extends \FilterIterator
{
    /**
     * @param \Iterator $iterator
     * @return void
     */
    public function __construct(Iterator $iterator)
    {
        parent::__construct($iterator);
        $this->rewind();
    }
    /**
     * @return bool
     */
    public function accept(): bool
    {
        /** @var BlogItem $episode */
        $episode = $this->getInnerIterator()->current();
        return $episode->getPublishDate() <= new DateTime();
    }
}