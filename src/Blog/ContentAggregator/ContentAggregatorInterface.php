<?php

declare (strict_types=1);

namespace MarkdownBlog\ContentAggregator;

use MarkdownBlog\Entity\BlogItem;
interface ContentAggregatorInterface
{
    /**
     * @param string $slug
     * @return \BlogItem|null
     */
    public function findItemBySlug(string $slug): ?BlogItem;
    /**
     * @return array
     */
    public function getItems(): array;
}