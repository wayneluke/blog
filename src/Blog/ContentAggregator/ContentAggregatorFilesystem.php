<?php

namespace MarkdownBlog\ContentAggregator;

use MarkdownBlog\Iterator\MarkdownFileFilterIterator;
use MarkdownBlog\Entity\BlogItem;
use Mni\FrontYAML\Document;
use Mni\FrontYAML\Parser;
/**
 * Class ContentAggregatorFilesystem
 */
class ContentAggregatorFilesystem implements ContentAggregatorInterface
{
    /**
     * @var \Parser $fileParser
     */
    protected Parser $fileParser;
    /**
     * @var \MarkdownFileFilterIterator $fileIterator
     */
    protected MarkdownFileFilterIterator $fileIterator;
    /**
     * @var array $items
     */
    private array $items = [];
    /**
     * @param \MarkdownFileFilterIterator $fileIterator
     * @param \Parser $fileParser
     * @return void
     */
    public function __construct(MarkdownFileFilterIterator $fileIterator, Parser $fileParser)
    {
        $this->fileParser = $fileParser;
        $this->fileIterator = $fileIterator;
        $this->buildItemsList();
    }
    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
    /**
     * @return void
     */
    protected function buildItemsList(): void
    {
        foreach ($this->fileIterator as $file) {
            $article = $this->buildItemFromFile($file);
            if (!is_null($article)) {
                $this->items[] = $article;
            }
        }
    }
    /**
     * @param string $slug
     * @return \BlogItem|null
     */
    public function findItemBySlug(string $slug): ?BlogItem
    {
        foreach ($this->items as $article) {
            if ($article->getSlug() === $slug) {
                return $article;
            }
        }
        return null;
    }
    /**
     * @param \SplFileInfo $file
     * @return \BlogItem|null
     */
    public function buildItemFromFile(\SplFileInfo $file): ?BlogItem
    {
        $fileContent = file_get_contents($file->getPathname());
        $document = $this->fileParser->parse($fileContent, false);
        $item = new BlogItem();
        $item->populate($this->getItemData($document));
        return $item;
    }
    /**
     * @param \Document $document
     * @return array
     */
    public function getItemData(Document $document): array
    {
        return ['publishDate' => $document->getYAML()['publish_date'] ?? '', 'slug' => $document->getYAML()['slug'] ?? '', 'synopsis' => $document->getYAML()['synopsis'] ?? '', 'title' => $document->getYAML()['title'] ?? '', 'image' => $document->getYAML()['image'] ?? '', 'categories' => $document->getYAML()['categories'] ?? [], 'tags' => $document->getYAML()['tags'] ?? [], 'content' => $document->getContent()];
    }
}