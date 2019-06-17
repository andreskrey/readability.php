<?php

namespace andreskrey\Readability\Nodes\DOM;

/**
 * Class DOMNodeList
 *
 * This is a fake DOMNodeList class that allows adding items to the list. The original class is static and the nodes
 * are defined automagically when instantiating it. This fake version behaves exactly the same way but adds the function
 * add() that allows to insert new DOMNodes into the DOMNodeList.
 *
 * It cannot extend the original DOMNodeList class because the functionality behind the property ->length is hidden
 * from the user and cannot be extended, changed, or tweaked.
 *
 * @package andreskrey\Readability\Nodes\DOM
 */
class DOMNodeList implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $length = 0;

    /**
     * To allow access to length in the same way that DOMNodeList allows
     *
     * {@inheritDoc}
     */
    public function __get($name)
    {
        switch ($name) {
            case 'length':
                return $this->length;
            default:
                trigger_error(sprintf('Undefined property: %s::%s', static::class, $name));
        }
    }

    /**
     * @param \DOMNode $node
     *
     * @return DOMNodeList
     */
    public function add(\DOMNode $node)
    {
        $this->items[] = $node;
        $this->length++;

        return $this;
    }

    /**
     * @return int|void
     */
    public function count()
    {
        return $this->length;
    }

    /**
     * To make it compatible with iterator_to_array() function
     *
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
        $this->length = count($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
        $this->length--;
    }
}
