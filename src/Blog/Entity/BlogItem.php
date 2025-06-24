<?php

declare (strict_types=1);

namespace MarkdownBlog\Entity;

use DateTime;
use Michelf\MarkdownExtra;
/**
 * Class BlogItem
 */
class BlogItem
{
    /**
     * @var \DateTime $publishDate
     */
    private DateTime $publishDate;
    /**
     * @var string $slug
     */
    private string $slug = '';
    /**
     * @var string $title
     */
    private string $title = '';
    /**
     * @var string $image
     */
    private string $image = '';
    /**
     * @var string $synopsis
     */
    private string $synopsis = '';
    /**
     * @var string $content
     */
    private string $content = '';
    /**
     * @var array $categories
     */
    private array $categories = [];
    /**
     * @var array $tags
     */
    private array $tags = [];
    /**
     * @param array $options
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->populate($options);
    }
    /**
     * @param array $options
     * @return void
     */
    public function populate(array $options = [])
    {
        $properties = get_class_vars(__CLASS__);
        foreach ($options as $key => $value) {
            if (array_key_exists($key, $properties) && !empty($value)) {
                $this->{$key} = $key === 'publishDate' ? new \DateTime($value) : $value;
            }
        }
    }
    /**
     * @return \DateTime
     */
    public function getPublishDate(): DateTime
    {
        return $this->publishDate;
    }
    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }
    /**
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }
    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
    /**
     * @return string
     */
    public function getContent(): string
    {
        $markdownParser = new MarkdownExtra();
        return $markdownParser->defaultTransform($this->content);
    }
    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }
    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->categories;
    }
    /**
     * @return string
     */
    public function getSynopsis(): string
    {
        return $this->synopsis ?? '';
    }
}