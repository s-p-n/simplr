<?php
namespace Simplr\Request;

class ArrayContainer implements \Countable, \IteratorAggregate
{
    /**
     * @var array
     */
    protected $arr;

    /**
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->arr = $array;
    }

    /**
     * @param $key
     * @return mixed|null Returns null if not found.
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->arr)) {
            return $this->arr[$key];
        }

        return null;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->arr;
    }

    /**
     * @return array
     */
    public function keys()
    {
        return array_keys($this->arr);
    }

    /**
     * @param $key
     * @param mixed $value
     * @return ArrayContainer
     */
    public function set($key, $value)
    {
        $this->arr[$key] = $value;
        return $this;
    }

    /**
     * @param mixed $value
     * @return ArrayContainer
     */
    public function add($value)
    {
        $this->arr[] = $value;
        return $this;
    }

    /**
     * @param array $array
     * @param bool $overwrite
     * @return ArrayContainer
     */
    public function addArray(array $array, $overwrite = true)
    {
        if ($overwrite) {
            $this->arr = $array + $this->arr;
        } else {
            $this->arr = $this->arr + $array;
        }

        return $this;
    }

    /**
     * @param $key
     * @return bool
     */
    public function exists($key)
    {
        if (array_key_exists($key, $this->arr)) {
            return true;
        }

        return false;
    }

    /**
     * @param mixed $value
     * @param bool $strict
     * @return bool
     */
    public function contains($value, $strict = false)
    {
        if (in_array($value, $this->arr, $strict)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (empty($this->arr)) {
            return true;
        }
        return false;
    }

    /**
     * @param $key
     * @return bool True if removed successfully, false if didn't exist and thus wasn't removed.
     */
    public function remove($key)
    {
        if (array_key_exists($key, $this->arr)) {
            unset($this->arr[$key]);
            return true;
        }
        return false;
    }

    /**
     * @return ArrayContainer
     */
    public function removeAll()
    {
        foreach ($this->arr as $key => $value) {
            unset($this->arr[$key]);
        }

        return $this;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->arr);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->arr);
    }
}
