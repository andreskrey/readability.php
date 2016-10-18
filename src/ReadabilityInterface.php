<?php

namespace andreskrey\Readability;

interface ReadabilityInterface
{
    public function __construct($node);

    public function getScore();

    public function initializeNode();

    public function getClassWeight();

}
