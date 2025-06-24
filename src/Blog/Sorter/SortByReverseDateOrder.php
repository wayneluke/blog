<?php

declare (strict_types=1);

namespace MarkdownBlog\Sorter;

use MarkdownBlog\Entity\BlogItem;
/**
 * Class SortByReverseDateOrder
 */
class SortByReverseDateOrder
{
    /**
     * @param \BlogItem $a
     * @param \BlogItem $b
     * @return int
     */
    public function __invoke(BlogItem $a, BlogItem $b): int
    {
        $firstDate = $a->getPublishDate();
        $secondDate = $b->getPublishDate();
        if ($firstDate == $secondDate) {
            return 0;
        }
        return $firstDate > $secondDate ? -1 : 1;
    }
}