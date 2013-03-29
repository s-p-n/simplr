<?php
namespace Simplr\DI;

class Container
{
    /**
     * @var array
     */
    protected $dependencies = [];

    /**
     * @var array
     */
    protected $args = [];

    /**
     * @var array
     */
    protected $sharedArgs = [];

    /**
     * @var array
     */
    protected $singleInstances = [];

    /**
     * @var array
     */
    protected $order = [];

    public function __construct()
    {

    }

    /**
     * Will invoke the get method on the dependency, which will return an instance of the object associated with the dependency.
     *
     * @param string $key
     * @return object
     * @throws Exception\ContainerException
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new Exception\ContainerException('Dependency key: ' . $key . ' does not exist.');
        }

        return $this->dependencies[$key]->get();
    }

    /**
     * Sets $key to $value.
     *
     * @param string            $key Dependency key. Used when invoking the get method.
     * @param string|Dependency $value Can be either a string of a valid class name, or an object that implemenets Dependency.
     * @return Dependency
     * @throws \InvalidArgumentException
     */
    public function set($key, $value)
    {
        if ($this->has($key)) {
            $this->dependencies[$key] = null;
            unset($this->dependencies[$key]);
            trigger_error('Dependency key: ' . $key . ' already exists. Going to overwrite...', E_USER_WARNING);
        }

        if (is_string($value) && class_exists($value)) {
            $this->dependencies[$key] = new Dependency($value);
        } elseif (is_object($value) && $value instanceof Dependency) {
            $this->dependencies[$key] = $value;
        } else {
            throw new \InvalidArgumentException('Method "set" argument 2 only accepts valid IDependency-implementing object or string of valid class name. Input type was: ' . gettype($value));
        }

        $this->dependencies[$key]->setDic($this);
        $this->dependencies[$key]->setKey($key);
        return $this->dependencies[$key];
    }

    /**
     * Check if $key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->dependencies);
    }

    /**
     * Return the raw dependency object.
     *
     * @param string $key
     * @return Dependency
     * @throws Exception\ContainerException
     */
    public function raw($key)
    {
        if (!array_key_exists($key, $this->dependencies)) {
            throw new Exception\ContainerException('Dependency key: ' . $key . ' does not exist,');
        }

        return $this->dependencies[$key];
    }

    /**
     * Duplicate a pre-existing dependency object to another key.
     *
     * @param string $key
     * @param string $newKey
     * @return Dependency
     * @throws Exception\ContainerException
     */
    public function duplicate($key, $newKey)
    {
        if (!array_key_exists($key, $this->dependencies)) {
            throw new Exception\ContainerException('Dependency key: ' . $key . ' does not exist,');
        }

        return $this->set($newKey, clone $this->dependencies[$key]);
    }

    /**
     * Return all keys in this container.
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->dependencies);
    }

    /**
     * For testing/debugging purposes only. Will invoke Dependency::get on all dependencies.
     *
     * If there are any issues, exceptions may be thrown or warnings/notices may be triggered.
     * Should be called only in a testing/development environment. Do NOT use for production.
     * Should be called after DIC is fully set up.
     *
     * @return bool Will return true once finished.
     */
    public function testAll()
    {
        foreach ($this->dependencies as $v) {
            $v->get();
        }

        return true;
    }
}
