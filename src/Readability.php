<?php

namespace andreskrey\Readability;

class Readability implements ReadabilityInterface
{
    private $score = 0;

    public function getScore()
    {
        return $this->score;
    }
}
