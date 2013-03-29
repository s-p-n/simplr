<?php
namespace Simplr\Signal;

class SignalManager
{
    protected $signals = [];

    /**
     * @var bool
     */
    protected $strict;

    /**
     * @param bool $strict Defaults to false. If false, will be less strict when dealing with unregistered signals.
     * If true, will usually throw exceptions when dealing with unregistered signals.
     * Specifically, will throw exceptions for the following methods: addEvent, triggerSignal, clearSignal.
     * If false, then signal may be automatically registered, null may be returned, or/and warning may be triggered. But no exceptions; the code will continue running.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($strict = false)
    {
        if (!is_bool($strict)) {
            throw new \InvalidArgumentException('SignalManager constructor only accepts a boolean. Input type was: ' . gettype($strict));
        }
        $this->strict = $strict;
    }

    /**
     * Register $signalName, allowing objects to be attached to it.
     * This is only necessary if SignalManager is constructed with $strict being set to true.
     *
     * @param string      $signalName
     * @return SignalManager
     * @throws \InvalidArgumentException
     * @throws Exception\SignalManagerException
     */
    public function registerSignal($signalName)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "registerSignal" argument 1 only accepts a string. Input type was: ' . gettype($signalName));
        }
        if (array_key_exists($signalName, $this->signals)) {
            throw new Exception\SignalManagerException('Specified signal: ' . $signalName . ' is already registered.');
        }

        $this->signals[$signalName] = [
            'events' => [],
            'frozen' => false
        ];

        return $this;
    }

    /**
     * Unregister a registered signal.
     *
     * @param string $signalName
     * @return bool Will return false if the signal doesn't exist. Otherwise, will return true.
     */
    public function unregisterSignal($signalName)
    {
        if (array_key_exists($signalName, $this->signals)) {
            foreach ($this->signals[$signalName] as $key => $value) {
                $this->signals[$signalName][$key] = null;
                unset($this->signals[$signalName][$key]);
            }
            $this->signals[$signalName] = null;
            unset($this->signals[$signalName]);

            return true;
        }

        return false;
    }

    /**
     * Clears all events from the specified signal.
     *
     * @param string $signalName
     * @return SignalManager
     * @throws Exception\SignalManagerException
     */
    public function clearSignal($signalName)
    {
        if (!array_key_exists($signalName, $this->signals)) {
            if ($this->strict === true) {
                throw new Exception\SignalManagerException('Specified signal: ' . $signalName . '  is not registered.');
            } else {
                trigger_error('Attempting to clear non-existant signal: ' . $signalName, E_USER_WARNING);
                return $this;
            }
        }

        $this->signals[$signalName]['events'] = null;
        unset($this->signals[$signalName]['events']);
        $this->signals[$signalName]['events'] = [];

        return $this;
    }

    /**
     * Check to see if the specified signal is registered or not.
     *
     * @param string $signalName
     * @return bool
     */
    public function isSignalRegistered($signalName)
    {
        return array_key_exists($signalName, $this->signals);
    }

    /**
     * Attach event to the specified signal.
     * If SignalManager is contructed with $strict being set to false, and the signal doesn't exist, it will be created.
     *
     * @param string $signalName
     * @param Event  $event
     * @return SignalManager
     * @throws Exception\SignalManagerException
     * @throws \InvalidArgumentException
     */
    public function attachEvent($signalName, Event $event)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "addEvent" argument 1 only accepts a string. Input type was: ' . gettype($signalName));
        }

        if (!array_key_exists($signalName, $this->signals)) {
            if ($this->strict === true) {
                throw new Exception\SignalManagerException('Specified signal: ' . $signalName . '  is not registered.');
            } else {
                $this->registerSignal($signalName);
            }
        }

        $this->signals[$signalName]['events'][] = $event;

        return $this;
    }

    /**
     * Attach an array of Event objects to the specified signal.
     *
     * @param string $signalName
     * @param array  $events Array of Event objects.
     * @return SignalManager
     */
    public function attachEvents($signalName, array $events)
    {
        foreach ($events as $event) {
            $this->addEvent($signalName, $event);
        }

        return $this;
    }

    /**
     * Get all Event objects that are attached to the specified signal.
     *
     * @param string $signalName
     * @return array|bool Returns false if $signalName is unregistered. Otherwise returns array of Event objects.
     * @throws \InvalidArgumentException
     */
    public function getEvents($signalName)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "getEvents" only accepts a string. Input type was: ' . gettype($signalName));
        }
        if (!array_key_exists($signalName, $this->signals)) {
            return false;
        }

        $events = [];
        foreach ($this->signals[$signalName]['events'] as $event) {
            $events[] = $event;
        }

        return $events;
    }

    /**
     * Freeze the specified signal.
     * If a frozen signal is triggered, none of the events attached to it will be triggered.
     *
     * @param string $signalName
     * @return bool Will return false if signal is not registered. Otherwise, will return true.
     * @throws \InvalidArgumentException
     */
    public function freezeSignal($signalName)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "freezeSignal" only accepts a string. Input type was: ' . gettype($signalName));
        }

        if (array_key_exists($signalName, $this->signals)) {
            $this->signals[$signalName]['frozen'] = true;
            return true;
        }

        return false;
    }

    /**
     * Unfreeze the specified signal.
     *
     * @param string $signalName
     * @return bool Will return false if signal is not registered. Otherwise, will return true.
     * @throws \InvalidArgumentException
     */
    public function unfreezeSignal($signalName)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "unfreezeSignal" only accepts a string. Input type was: ' . gettype($signalName));
        }

        if (array_key_exists($signalName, $this->signals)) {
            $this->signals[$signalName]['frozen'] = false;
            return true;
        }

        return false;
    }

    /**
     * Check to see if the specified signal is frozen or not.
     *
     * @param string $signalName
     * @return bool|null Returns null if $signalName is unregistered.
     * @throws \InvalidArgumentException
     */
    public function isSignalFrozen($signalName)
    {
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "isSignalFrozen" only accepts a string. Input type was: ' . gettype($signalName));
        }

        if (array_key_exists($signalName, $this->signals)) {
            return $this->signals[$signalName]['frozen'];
        }

        return null;
    }

    /**
     * Trigger the specified signal.
     *
     * @param null|object $object The object will be passed to the event.
     * @param string      $signalName
     * @return array|bool|null Returns null if $signalName is unregistered. Returns false if $signalName is frozen. Otherwise returns array of (already triggered) Event objects.
     * @throws Exception\SignalManagerException
     * @throws \InvalidArgumentException
     */
    public function triggerSignal($object, $signalName)
    {
        if (!is_null($object) && !is_object($object)) {
            throw new \InvalidArgumentException('Method "triggerSignal" argument 1 only accepts null or an object. Input type was: ' . gettype($object));
        }
        if (!is_string($signalName)) {
            throw new \InvalidArgumentException('Method "triggerSignal" argument 2 only accepts a string. Input type was: ' . gettype($signalName));
        }

        if (!array_key_exists($signalName, $this->signals)) {
            if ($this->strict === true) {
                throw new Exception\SignalManagerException('Specified signal: ' . $signalName . '  is not registered.');
            }
            return null;
        }

        if ($this->signals[$signalName]['frozen'] === true) {
            return false;
        }

        $events = $this->getEvents($signalName);
        foreach ($events as $event) {
            $event->trigger($object);
        }

        return $events;
    }
}
