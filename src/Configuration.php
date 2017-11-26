<?php

namespace andreskrey\Readability\v1;

/**
 * Class Configuration
 */
class Configuration
{
    /**
     * @var int
     */
    protected $maxTopCandidates = 5;
    /**
     * @var int
     */
    protected $wordThreshold = 500;
    /**
     * @var bool
     */
    protected $articleByLine = false;
    /**
     * @var bool
     */
    protected $stripUnlikelyCandidates = true;
    /**
     * @var bool
     */
    protected $cleanConditionally = true;
    /**
     * @var bool
     */
    protected $weightClasses = true;
    /**
     * @var bool
     */
    protected $removeReadabilityTags = true;
    /**
     * @var bool
     */
    protected $fixRelativeURLs = false;
    /**
     * @var bool
     */
    protected $substituteEntities = false;
    /**
     * @var bool
     */
    protected $normalizeEntities = false;
    /**
     * @var string
     */
    protected $originalURL = 'http://fakehost';

    /**
     * @return int
     */
    public function getMaxTopCandidates(): int
    {
        return $this->maxTopCandidates;
    }

    /**
     * @param int $maxTopCandidates
     * @return $this
     */
    public function setMaxTopCandidates(int $maxTopCandidates)
    {
        $this->maxTopCandidates = $maxTopCandidates;
        return $this;
    }

    /**
     * @return int
     */
    public function getWordThreshold(): int
    {
        return $this->wordThreshold;
    }

    /**
     * @param int $wordThreshold
     * @return $this
     */
    public function setWordThreshold(int $wordThreshold)
    {
        $this->wordThreshold = $wordThreshold;
        return $this;
    }

    /**
     * @return bool
     */
    public function getArticleByLine(): bool
    {
        return $this->articleByLine;
    }

    /**
     * @param bool $articleByLine
     * @return $this
     */
    public function setArticleByLine(bool $articleByLine)
    {
        $this->articleByLine = $articleByLine;
        return $this;
    }

    /**
     * @return bool
     */
    public function getStripUnlikelyCandidates(): bool
    {
        return $this->stripUnlikelyCandidates;
    }

    /**
     * @param bool $stripUnlikelyCandidates
     * @return $this
     */
    public function setStripUnlikelyCandidates(bool $stripUnlikelyCandidates)
    {
        $this->stripUnlikelyCandidates = $stripUnlikelyCandidates;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCleanConditionally(): bool
    {
        return $this->cleanConditionally;
    }

    /**
     * @param bool $cleanConditionally
     * @return $this
     */
    public function setCleanConditionally(bool $cleanConditionally)
    {
        $this->cleanConditionally = $cleanConditionally;
        return $this;
    }

    /**
     * @return bool
     */
    public function getWeightClasses(): bool
    {
        return $this->weightClasses;
    }

    /**
     * @param bool $weightClasses
     * @return $this
     */
    public function setWeightClasses(bool $weightClasses)
    {
        $this->weightClasses = $weightClasses;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRemoveReadabilityTags(): bool
    {
        return $this->removeReadabilityTags;
    }

    /**
     * @param bool $removeReadabilityTags
     * @return $this
     */
    public function setRemoveReadabilityTags(bool $removeReadabilityTags)
    {
        $this->removeReadabilityTags = $removeReadabilityTags;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFixRelativeURLs(): bool
    {
        return $this->fixRelativeURLs;
    }

    /**
     * @param bool $fixRelativeURLs
     * @return $this
     */
    public function setFixRelativeURLs(bool $fixRelativeURLs)
    {
        $this->fixRelativeURLs = $fixRelativeURLs;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSubstituteEntities(): bool
    {
        return $this->substituteEntities;
    }

    /**
     * @param bool $substituteEntities
     * @return $this
     */
    public function setSubstituteEntities(bool $substituteEntities)
    {
        $this->substituteEntities = $substituteEntities;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNormalizeEntities(): bool
    {
        return $this->normalizeEntities;
    }

    /**
     * @param bool $normalizeEntities
     * @return $this
     */
    public function setNormalizeEntities(bool $normalizeEntities)
    {
        $this->normalizeEntities = $normalizeEntities;
        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalURL(): string
    {
        return $this->originalURL;
    }

    /**
     * @param string $originalURL
     * @return $this
     */
    public function setOriginalURL(string $originalURL)
    {
        $this->originalURL = $originalURL;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSummonCthulhu(): bool
    {
        return $this->summonCthulhu;
    }

    /**
     * @param bool $summonCthulhu
     * @return $this
     */
    public function setSummonCthulhu(bool $summonCthulhu)
    {
        $this->summonCthulhu = $summonCthulhu;
        return $this;
    }

    /**
     * @var bool
     */
    protected $summonCthulhu = false;
}
