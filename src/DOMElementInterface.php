<?php

namespace andreskrey\Readability;

interface DOMElementInterface
{
    /**
     * @param string $value
     *
     * @return bool
     */
    public function tagNameEqualsTo($value);

    /**
     *
     * @return bool
     */
    public function hasSinglePNode();
}
