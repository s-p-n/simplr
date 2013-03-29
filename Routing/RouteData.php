<?php
namespace Simplr\Routing;

class RouteData
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var array
     */
    protected $routeVars = [];

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var bool
     */
    protected $ignoreWildcard = false;

    /**
     * @param callable $callback
     * @param array    $routeVars
     * @param array    $params
     * @param bool     $ignoreWildcard
     * @throws \InvalidArgumentException
     */
    public function __construct(callable $callback, array $routeVars = [], array $params = [], $ignoreWildcard = false)
    {
        if (!is_bool($ignoreWildcard)) {
            throw new \InvalidArgumentException('RouteData constructor argument 4 only accepts a boolean. Input type was: ' . gettype($ignoreWildcard));
        }

        $this->callback = $callback;
        $this->routeVars = $routeVars;
        $this->params = $params;
        $this->ignoreWildcard = $ignoreWildcard;
    }

    /**
     * Get the callable.
     *
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * Get the route variables.
     *
     * @return array
     */
    public function getRouteVars()
    {
        return $this->routeVars;
    }

    /**
     * Get the parameters.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Check if this ignores wildcards or not.
     *
     * @return bool
     */
    public function doesIgnoreWildcard()
    {
        return $this->ignoreWildcard;
    }
}
