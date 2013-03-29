<?php
namespace Simplr\Signal;

class Event
{
    /**
     * @var null|object
     */
    protected $owner;

    /**
     * @var object
     */
    protected $boundTo;

    /**
     * @var \Closure
     */
    protected $closure;

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var string
     */
    protected $restrictedTo;

    /**
     * @param null|object $owner
     * @param \Closure    $closure
     * @throws \InvalidArgumentException
     */
    public function __construct($owner, \Closure $closure)
    {
        if (!is_null($owner) && !is_object($owner)) {
            throw new \InvalidArgumentException('Event constructor argument 1 only accepts an object or null. Input type was: ' . gettype($owner));
        }

        $this->owner = $owner;
        $this->closure = $closure;
    }

    /**
     * Set the event closure.
     * The closure is invoked when the event is triggered.
     *
     * @param \Closure $closure
     * @return Event
     */
    public function setClosure(\Closure $closure)
    {
        $this->closure = $closure;

        return $this;
    }

    /**
     * Get the event closure.
     * The closure is invoked when the event is triggered.
     *
     * @return \Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Set the event owner.
     * The event owner is usually the object that created the event.
     *
     * @param null|object $owner
     * @return Event
     * @throws \InvalidArgumentException
     */
    public function setOwner($owner)
    {
        if (!is_object($owner)) {
            throw new \InvalidArgumentException('Method "setOwner" argument 1 only accepts an object. Input type was: ' . gettype($owner));
        }

        $this->owner = $owner;

        return $this;
    }

    /**
     * Get the event owner.
     * The event owner is usually the object that created the event.
     *
     * @return null|object
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set the result of this event closure.
     * Will be automatically set after being triggered.
     *
     * @param mixed $result
     * @return Event
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Return the result of this event closure (after it was triggered).
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Bind this closure to $object.
     * Binding a closure allows the $this keyword to be the same instance as $object.
     * Warning: This method will throw an exception if the closure is not set. See the "setClosure" method.
     *
     * If $privateScope is true, then the closure will have access to the public, protected, and private class scope.
     * If $privateScope is false, the closure will have access only to the public class scope.
     *
     * @param object $object
     * @param bool   $privateScope
     * @return Event
     * @throws Exception\EventException
     * @throws \InvalidArgumentException
     */
    public function bindTo($object, $privateScope = false)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('Method "bindTo" argument 1 only accepts an object. Input type was: ' . gettype($object));
        }
        if (!is_bool($privateScope)) {
            throw new \InvalidArgumentException('Method "bindTo" argument 2 only accepts a boolean. Input type was: ' . gettype($privateScope));
        }

        if (is_null($this->closure)) {
            throw new Exception\EventException('Must set closure before using "bindTo" method.');
        }

        $this->boundTo = $object;
        if ($privateScope === true) {
            $this->closure->bindTo($object, $object);
        } else {
            $this->closure->bindTo($object);
        }

        return $this;
    }

    /**
     * Get the object that the event's closure is bound to.
     *
     * @return null|object
     */
    public function getBound()
    {
        return $this->boundTo;
    }

    /**
     * Restrict this event to being triggered only by the specified class/interface.
     * If set to a class, it will still allow any class that subclasses the specified class.
     * If set to an interface, it will allow any class that implements that interface.
     *
     * Tip 1: Set $classname to an empty string to restrict this event to not being run by any object.
     * Tip 2: Set $classname to null to not restrict this event to anything. (Default)
     *
     * @param string $className
     * @return Event
     * @throws \InvalidArgumentException
     */
    public function restrictTo($className)
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Method "restrictTo" only accepts a string. Input type was: ' . gettype($className));
        }

        $this->restrictedTo = $className;

        return $this;
    }

    /**
     * Returns null if this object isn't restricted. Otherwise, returns class name.
     *
     * @return string|null
     */
    public function isRestricted()
    {
        if (is_null($this->restrictedTo)) {
            return null;
        }

        return $this->restrictedTo;
    }

    /**
     * Trigger the event.
     * Will invoke the event's closure and set the result (via setResult) to the return value of that closure.
     *
     * @param object|null $object Object will be passed to first argument of closure.
     * @return Event
     * @throws \InvalidArgumentException
     * @throws Exception\EventException
     */
    public function trigger($object = null)
    {
        if (!is_object($object) && !is_null($object)) {
            throw new \InvalidArgumentException('Method "trigger" only accepts null or an object. Input type was: ' . gettype($object));
        }

        if (!is_null($restrictedTo = $this->isRestricted())
            && (empty($restrictedTo) && !is_null($object))
        ) {
            throw new Exception\EventException('Attempted to trigger event with object type: ' . gettype($object) . ' but the event has been restricted so that it can\'t be triggered by an object.');
        } elseif (!is_null($restrictedTo)
            && (is_null($object) || (!is_subclass_of($object, $restrictedTo) && !in_array($restrictedTo, class_implements($object), true)))
        ) {
            throw new Exception\EventException('Attempted to trigger event with object type: ' . gettype($object) . ' but the event has been restricted so that it can only be triggered by: ' . $restrictedTo);
        }

        $closure = $this->closure;
        $this->setResult($closure($object));

        return $this;
    }
}
