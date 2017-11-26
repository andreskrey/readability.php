<?php

namespace andreskrey\Readability\NodeClass;

class DOMText extends \DOMText
{
    use NodeClassTrait;

    /**
     * Placeholder for getAttribute function. DOMText does not have any attributes but we might call it at some
     * point of the execution
     *
     * @param $attribute string Not used
     *
     * @return null
     */
    public function getAttribute($attribute)
    {
        return null;
    }

}
