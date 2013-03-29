<?php
namespace Simplr\DI;

/**
 * @package Simplr\DI
 */
class Dependency
{
    /**
     * @var object|string
     */
    protected $class;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var array
     */
    protected $order;

    /**
     * @var object
     */
    protected $instance;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var Container
     */
    protected $dic;

    /**
     * @var bool
     */
    protected $shared = false;

    /**
     * @param string|object $class If string, must be class name. If object, the dependency will be set to shared and the instance will be set to the passed object.
     * @param Container $dic
     * @throws Exception\DependencyException
     */
    public function __construct($class, Container $dic = null)
    {
        $this->dic = $dic;
        if (is_object($class)) { // If object is passed to constructor, share passed object as dependency
            $this->shared = true;
            $this->instance = $class;
            $this->class = get_class($class);
        } elseif (class_exists($class)) {
            $this->class = $class;
        } else {
            throw new Exception\DependencyException('Class: ' . $class . ' does not exist.');
        }
    }

    /**
     * Set the class name associated with the dependency (to be instantiated)
     *
     * @param string $class
     * @return Dependency
     * @throws Exception\DependencyException
     */
    public function setClass($class)
    {
        if (!class_exists($class)) {
            throw new Exception\DependencyException('Class: ' . $class . ' does not exist.');
        }

        $this->class = $class;
        if ($this->shared) {
            $this->setShared(true);
        }

        return $this;
    }

    /**
     * Get the class name associated with the dependency
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param Container $dic
     */
    public function setDic(Container $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Set the arguments.
     * Will be passed to the class's constructor.
     *
     * Normally this must be an array of values in the same order that they will be when the class is constructed.
     * But you can use an associative array if you set the order correctly.
     * See setOrder method for more.
     *
     * @param array $args
     * @return Dependency
     */
    public function setArgs(array $args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Sets a single argument.
     *
     * @param string|int $key
     * @param mixed      $value
     * @return Dependency
     */
    public function setArg($key, $value)
    {
        $this->args[$key] = $value;

        return $this;
    }

    /**
     * Get the array of args.
     *
     * @return array
     * @throws Exception\DependencyException
     */
    public function getArgs()
    {
        // The code block below resorts the array if an order is set for this arg key.
        if (($hasOrder = !empty($this->order)) && $this->arrayIsStrings(array_keys($this->args))) {
            $sortedArgs = [];
            foreach ($this->order as $v) {
                if (!array_key_exists($v, $this->args)) {
                    throw new Exception\DependencyException('Order asked for non-existant arg key: ' . $v);
                }
                $arg = $this->args[$v];
                if (is_string($arg) && class_exists($arg)) { // If arg is class name, create object; does not support args.
                   $arg = new $arg();
                } elseif (is_string($arg) && !is_null($this->dic) && $this->dic->has($arg)) { // If arg is Dic key
                    $arg = $this->dic->get($arg);
                }
                $sortedArgs[] = $arg;

            }
            if (count($sortedArgs) != count($this->args)) {
                trigger_error('Difference in number of keys between args and order args. Dependency key: ' . $this->key, E_USER_WARNING);
            }

            return $sortedArgs;
        } elseif ($hasOrder) {
            trigger_error('Order is set for dependency key: ' . $this->key . ' but not all arg array keys are strings. Ignoring order...', E_USER_WARNING);
        }

        $args = [];
        foreach($this->args as $key => $arg) {
            if (is_string($arg) && class_exists($arg)) { // If arg is class name, create object; does not support args.
                $arg = new $arg();
            } elseif (is_string($arg) && !is_null($this->dic) && $this->dic->has($arg)) { // If arg is Dic key
                $arg = $this->dic->get($arg);
            }
            $args[$key] = $arg;
        }
        return $this->args;
    }

    /**
     * Check if args are set or not.
     *
     * @return bool
     */
    public function hasArgs()
    {
        return !empty($this->args);
    }

    /**
     * Checks if the specified arg key exists or not.
     *
     * @param string|int $key
     * @return bool
     */
    public function hasArg($key)
    {
        return array_key_exists($key, $this->args);
    }

    /**
     * Set the order of arguments.
     * Allows use of an associative array when setting arguments.
     *
     * Set this to an array of values, wherein each value will correspond to a key in the args array.
     * Make sure this array is in the correct order for the class's constructor's arguments.
     *
     * @param array $order
     * @return Dependency
     * @throws \InvalidArgumentException
     */
    public function setOrder(array $order)
    {
        if (!$this->arrayIsStrings($order)) {
            throw new \InvalidArgumentException('Method "setOrder" only accepts an array. Input type was: ' . gettype($order));
        }

        $this->order = $order;

        return $this;
    }

    /**
     * Get the order of arguments.
     *
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Check if this dependency has an order of arguments.
     *
     * @return bool
     */
    public function hasOrder()
    {
        return !empty($this->order);
    }

    /**
     * Set the dependency container key that the dependency is associated with.
     *
     * @param string $key
     * @return Dependency
     * @throws \InvalidArgumentException
     */
    public function setKey($key)
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Method "setKey" only accepts a string. Input type was: ' . gettype($key));
        }

        $this->key = $key;

        return $this;
    }

    /**
     * Get the dependency container key that the dependency is associated with.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * If true, then the same instance of the class will be given when invoking the get method.
     * If false, a new instance of the class will be given every time the get method is invoked.
     * Defaults to false.
     *
     * @param bool $shared
     * @return Dependency
     * @throws \InvalidArgumentException
     */
    public function setShared($shared)
    {
        if (!is_bool($shared)) {
            throw new \InvalidArgumentException('Method "setShared" only accepts a boolean. Input type was: ' . gettype($shared));
        }

        if ($shared === false) { // If $shared is true, instance will be created the first time "get" is called.
            $this->instance = null;
        }

        $this->shared = $shared;

        return $this;
    }

    /**
     * Check if the dependency is shared or not.
     *
     * @return bool
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * Get the dependency's class's instance.
     *
     * @return object
     * @throws Exception\DependencyException
     */
    public function get()
    {
        $obj = null;
        if ($this->shared === true) {
            if (is_null($this->instance)) {
                $this->instance = $this->create();
            }
            $obj = $this->instance;
        } else {
            $obj = $this->create();
        }

        return $obj;
    }

    /**
     * Sets args, order, and instance to null. Sets shared to false.
     *
     * @return Dependency
     */
    public function defaults()
    {
        $this->args = null;
        $this->order = null;
        $this->instance = null;
        $this->shared = false;

        return $this;
    }

    /**
     * Will get a new instance of the dependency's class.
     *
     * @return object
     */
    protected function create()
    {
        if (!empty($this->args)) {
            return (new \ReflectionClass($this->class))->newInstanceArgs($this->getArgs());
        } else {
            return new $this->class;
        }
    }

    /**
     * @param array $arr
     * @return bool Will return true if all values in the array are strings. Otherwise, will return false.
     */
    protected function arrayIsStrings(array $arr)
    {
        foreach ($arr as $v) {
            if (!is_string($v)) {
                return false;
            }
        }

        return true;
    }
}
