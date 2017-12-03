<?php

namespace andreskrey\Readability;

/**
 * Class Configuration.
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
    public function getMaxTopCandidates()
    {
        return $this->maxTopCandidates;
    }

    /**
     * @param int $maxTopCandidates
     *
     * @return $this
     */
    public function setMaxTopCandidates($maxTopCandidates)
    {
        $this->maxTopCandidates = $maxTopCandidates;

        return $this;
    }

    /**
     * @return int
     */
    public function getWordThreshold()
    {
        return $this->wordThreshold;
    }

    /**
     * @param int $wordThreshold
     *
     * @return $this
     */
    public function setWordThreshold($wordThreshold)
    {
        $this->wordThreshold = $wordThreshold;

        return $this;
    }

    /**
     * @return bool
     */
    public function getArticleByLine()
    {
        return $this->articleByLine;
    }

    /**
     * @param bool $articleByLine
     *
     * @return $this
     */
    public function setArticleByLine($articleByLine)
    {
        $this->articleByLine = $articleByLine;

        return $this;
    }

    /**
     * @return bool
     */
    public function getStripUnlikelyCandidates()
    {
        return $this->stripUnlikelyCandidates;
    }

    /**
     * @param bool $stripUnlikelyCandidates
     *
     * @return $this
     */
    public function setStripUnlikelyCandidates($stripUnlikelyCandidates)
    {
        $this->stripUnlikelyCandidates = $stripUnlikelyCandidates;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCleanConditionally()
    {
        return $this->cleanConditionally;
    }

    /**
     * @param bool $cleanConditionally
     *
     * @return $this
     */
    public function setCleanConditionally($cleanConditionally)
    {
        $this->cleanConditionally = $cleanConditionally;

        return $this;
    }

    /**
     * @return bool
     */
    public function getWeightClasses()
    {
        return $this->weightClasses;
    }

    /**
     * @param bool $weightClasses
     *
     * @return $this
     */
    public function setWeightClasses($weightClasses)
    {
        $this->weightClasses = $weightClasses;

        return $this;
    }

    /**
     * @return bool
     */
    public function getRemoveReadabilityTags()
    {
        return $this->removeReadabilityTags;
    }

    /**
     * @param bool $removeReadabilityTags
     *
     * @return $this
     */
    public function setRemoveReadabilityTags($removeReadabilityTags)
    {
        $this->removeReadabilityTags = $removeReadabilityTags;

        return $this;
    }

    /**
     * @return bool
     */
    public function getFixRelativeURLs()
    {
        return $this->fixRelativeURLs;
    }

    /**
     * @param bool $fixRelativeURLs
     *
     * @return $this
     */
    public function setFixRelativeURLs($fixRelativeURLs)
    {
        $this->fixRelativeURLs = $fixRelativeURLs;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSubstituteEntities()
    {
        return $this->substituteEntities;
    }

    /**
     * @param bool $substituteEntities
     *
     * @return $this
     */
    public function setSubstituteEntities($substituteEntities)
    {
        $this->substituteEntities = $substituteEntities;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNormalizeEntities()
    {
        return $this->normalizeEntities;
    }

    /**
     * @param bool $normalizeEntities
     *
     * @return $this
     */
    public function setNormalizeEntities($normalizeEntities)
    {
        $this->normalizeEntities = $normalizeEntities;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalURL()
    {
        return $this->originalURL;
    }

    /**
     * @param string $originalURL
     *
     * @return $this
     */
    public function setOriginalURL($originalURL)
    {
        $this->originalURL = $originalURL;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSummonCthulhu()
    {
        return $this->summonCthulhu;
    }

    /**
     * @param bool $summonCthulhu
     *
     * @return $this
     */
    public function setSummonCthulhu($summonCthulhu)
    {
        $this->summonCthulhu = $summonCthulhu;

        return $this;
    }

    /**
     * @var bool
     */
    protected $summonCthulhu = false;
}
