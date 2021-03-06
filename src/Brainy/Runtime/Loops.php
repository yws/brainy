<?php
namespace Box\Brainy\Runtime;

class Loops
{

    /**
     * Returns the size of an item
     * @param  mixed $value A value to count the members of
     * @return int
     */
    public static function getCount($value)
    {
        if (is_array($value) || $value instanceof \Countable) {
            return count($value);
        }
        if ($value instanceof \IteratorAggregate) {
            // Note: getIterator() returns a Traversable, not an Iterator
            // thus rewind() and valid() methods may not be present
            return iterator_count($value->getIterator());
        } elseif ($value instanceof \Iterator) {
            return iterator_count($value);
        } elseif ($value instanceof \PDOStatement) {
            return $value->rowCount();
        } elseif ($value instanceof \Traversable) {
            return iterator_count($value);
        } elseif ($value instanceof \ArrayAccess) {
            if ($value->offsetExists(0)) {
                return 1;
            }
        } elseif (is_object($value)) {
            return count($value);
        }
        return 0;
    }
}
