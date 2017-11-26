<?php

use andreskrey\Readability\Configuration;

/**
 * Class Readability
 */
class Readability
{
    /**
     * @var string|null
     */
    protected $title = null;
    /**
     * @var string|null
     */
    protected $content = null;
    /**
     * @var string|null
     */
    protected $image = null;
    /**
     * @var string|null
     */
    protected $author = null;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Readability constructor.
     *
     * @param Configuration $configuration
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return null|string
     */
    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null $title
     */
    protected function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param null $content
     */
    protected function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param null $image
     */
    protected function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return string|null
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param null $author
     */
    protected function setAuthor($author)
    {
        $this->author = $author;
    }

}