<?php
namespace Simplr\Routing;

class Route
{
    /**
     * @var string
     */
    protected $rule;

    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var array
     */
    protected $requisites = [];

    /**
     * @param string   $rule
     * @param callable $callback
     * @throws \InvalidArgumentException
     */
    public function __construct($rule, callable $callback)
    {
        if (!is_string($rule)) {
            throw new \InvalidArgumentException('Route constructor argument 1 only accepts a string. Input type was: ' . gettype($rule));
        }

        $this->rule = $rule;
        $this->callback = $callback;
    }

    /**
     * Set the rule, i.e. intended URI, of this route.
     * Use {var} syntax for variables.
     *
     * @param string $rule
     * @return Route
     * @throws \InvalidArgumentException
     */
    public function setRule($rule)
    {
        if (!is_string($rule)) {
            throw new \InvalidArgumentException('Method "setRule" only accepts a string. Input type was: ' . gettype($rule));
        }

        $this->rule = $rule;

        return $this;
    }

    /**
     * Get the rule.
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set the callback of this route.
     * This will be invoked if the route is matched.
     *
     * @param callable $callback
     * @return Route
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * Get the callback of the route.
     *
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Add a key/value parameter to the route.
     * This will be merged with (and override, if necessary) the route variables.
     *
     * @param                  $key
     * @param mixed            $value
     * @return Route
     */
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Add an array of key/value parameters to the route.
     *
     * @param array        $params
     * @return Route
     */
    public function addParams(array $params)
    {
        foreach ($params as $k => $v) {
            $this->addParam($k, $v);
        }

        return $this;
    }

    /**
     * Get a specific parameter.
     *
     * @param $key
     * @return null|mixed
     */
    public function getParam($key)
    {
        if (array_key_exists($key, $this->params)) {
            return $this->params[$key];
        }

        return null;
    }

    /**
     * Get an array of all the parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Set a regular expression filter that will be applied to route variables.
     * A URL attempting to match the specified rout evariable must match this regular expression.
     *
     * @param string $routeVar
     * @param string $filter
     * @return Route
     * @throws \InvalidArgumentException
     */
    public function setFilter($routeVar, $filter)
    {
        if (!is_string($routeVar)) {
            throw new \InvalidArgumentException('Method "setFilter" argument 1 only accepts a string. Input type was: ' . gettype($routeVar));
        }
        if (!is_string($filter)) {
            throw new \InvalidArgumentException('Method "setFilter" argument 2 only accepts a string. Input type was: ' . gettype($filter));
        }
        if (@preg_match($filter, '') === false) {
            throw new \InvalidArgumentException('Method "setFilter" argument 2 must be a valid regex string. Input was: ' . $filter);
        }

        $this->filters[$routeVar] = $filter;

        return $this;
    }

    /**
     * Get a specific filter.
     *
     * @param $key
     * @return null|string
     */
    public function getFilter($key)
    {
        if (array_key_exists($key, $this->filters)) {
            return $this->filters[$key];
        }

        return null;
    }

    /**
     * Get an array of all the filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Add a requisite.
     * All requisites will be invoked, and must return a bool.
     * If any of them return false, this route will fail to be matched.
     *
     * @param callable $requisite Must return bool when invoked.
     * @return Route
     */
    public function addRequisite(callable $requisite)
    {
        $this->requisites[] = $requisite;

        return $this;
    }

    /**
     * Get an array of all the requisites.
     *
     * @return array
     */
    public function getRequisites()
    {
        return $this->requisites;
    }

    /**
     * Used by the router.
     * Check if all requisites return true or not.
     *
     * @return bool
     */
    public function passesRequisites()
    {
        foreach ($this->requisites as $requisite) {
            $result = $requisite();
            if ($result === false) {
                return false;
            } elseif (!is_bool($result)) {
                trigger_error('A requisite for route rule: ' . $this->rule . ' does not return a boolean; it will be ignored.', E_USER_WARNING);
            }
        }

        return true;
    }
}
