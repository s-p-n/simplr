<?php
namespace Simplr\Routing;

class Router
{
    const WILDCARD = '[*]';
    const ROUTE_VARIABLE_FIRST = '{';
    const ROUTE_VARIABLE_LAST = '}';

    /**
     * @var array
     */
    public $routes = [];

    /**
     * @var string
     */
    protected $uriPrefix = '';

    /**
     * @param string $uriPrefix
     * @throws \InvalidArgumentException
     */
    public function __construct($uriPrefix = '')
    {
        if (!is_string($uriPrefix)) {
            throw new \InvalidArgumentException('Router constructor only accepts a string. Input type was: ' . gettype($uriPrefix));
        } elseif (substr($uriPrefix, strlen($uriPrefix) - 1) === '/') {
            throw new \InvalidArgumentException('Router constructor does not accept a string that ends with a forward slash("/"). Input was: ' . $uriPrefix);
        }

        $this->uriPrefix = $uriPrefix;
    }

    /**
     * Set the URI prefix. Will be prepended on to all route rules.
     *
     * @param string $uriPrefix
     * @return Router
     * @throws \InvalidArgumentException
     */
    public function setUriPrefix($uriPrefix)
    {
        if (!is_string($uriPrefix)) {
            throw new \InvalidArgumentException('Method "setUriPrefix" only accepts a string. Input type was: ' . gettype($uriPrefix));
        } elseif (substr($uriPrefix, 0, 1) !== '/') {
            throw new \InvalidArgumentException('Method "setUriPrefix" only accepts a string that starts with a forward slash("/"). Input was: ' . $uriPrefix);
        }

        $this->uriPrefix = $uriPrefix;

        return $this;
    }

    /**
     * Get the URI prefix.
     *
     * @return string
     */
    public function getUriPrefix()
    {
        return $this->uriPrefix;
    }

    /**
     * Add a route.
     *
     * @param string   $rule
     * @param callable $callback
     *
     * @return Route
     * @throws Exception\RouterException
     * @throws \InvalidArgumentException
     */
    public function addRoute($rule, callable $callback)
    {
        if (!is_string($rule)) {
            throw new \InvalidArgumentException('Method "addRoute" argument 1 only accepts a string. Input type was: ' . gettype($rule));
        }

        $route = new Route($rule, $callback);

        if (!empty($this->uriPrefix) && substr($rule = $route->getRule(), 0, 1) === '/') { // Prefix is set and route is public, i.e. starts with '/'
            $route->setRule($this->uriPrefix . $rule);
        }

        $genericRule = $this->convertToGenericRule($rule);
        if (in_array($genericRule, array_keys($this->routes))) {
            throw new Exception\RouterException('Route rule: "' . $rule . '" already exists. To override this route rule, please use "overrideRoute" method.');
        } else {
            $this->routes[$genericRule] = $route;
        }

        return $route;
    }

    /**
     * Override a preexistant route.
     * If there is no route to override, will throw a warning but still add specified route as expected.
     *
     * @param string   $rule
     * @param callable $callback
     * @return Route
     */
    public function overrideRoute($rule, callable $callback)
    {
        $genericRule = $this->convertToGenericRule($rule);
        if (in_array($genericRule, array_keys($this->routes))) {
            unset($this->routes[$genericRule]);
        } else {
            trigger_error('There is no route to override with: "' . $rule . '". Adding specified route anyway...', E_USER_WARNING);
        }

        return $this->addRoute($rule, $callback);
    }

    /**
     * Will match the current URI to a route.
     * If not route can be matched, it will 404.
     * The 404 text can be overriden by adding a route with the "404" rule.
     *
     * @return Router
     */
    public function go()
    {
        $routeData = $this->match($_SERVER['REQUEST_URI']);

        if (is_null($routeData) && is_null($routeData = $this->match('404'))) {
            if (!headers_sent()) {
                header("HTTP/1.1 404 Not Found");
                header("Status: 404 Not Found");
            }
            echo 'Not found.';
            trigger_error('No 404 route is defined for the router', E_USER_WARNING);
            return $this;
        }

        $callback = $routeData->getCallback();
        $callback($routeData->getParams() + $routeData->getRouteVars());

        return $this;
    }

    /**
     * Check if the URI matches a route.
     *
     * @param string $uri
     * @param bool   $ignoreWildcard
     * @return null|RouteData If no match is made, will return null. Otherwise, will return RouteData object.
     * @throws \InvalidArgumentException
     */
    public function match($uri, $ignoreWildcard = false)
    {
        if (!is_string($uri)) {
            throw new \InvalidArgumentException('Method "match" argument 1 only accepts a string. Input type was: ' . gettype($uri));
        }
        if (!is_bool($ignoreWildcard)) {
            throw new \InvalidArgumentException('Method "match" argument 2 only accepts a boolean. Input type was: ' . gettype($ignoreWildcard));
        }

        $uriTokens = explode('/', $uri);
        $uriTokenCount = count($uriTokens);
        $pass = false;
        $route = null;
        $routeVars = null;

        foreach ($this->routes as $route) {
            if (!$route->passesRequisites()) {
                continue;
            }

            $routeTokens = explode('/', $route->getRule());
            $routeTokenCount = count($routeTokens);
            $routeVars = [];


            foreach ($routeTokens as $k => $routeToken) {
                $uriToken = array_key_exists($k, $uriTokens) ? $uriTokens[$k] : null;

                if (!$ignoreWildcard && $routeToken === self::WILDCARD) { // wildcard token
                    if (!is_null($this->match($uri, true))) { // Wildcard requires URI to not have otherwise valid route.
                        continue 2;
                    }

                    $pass = true;
                    break;
                } elseif (strpos($routeToken, self::ROUTE_VARIABLE_FIRST) !== 0
                    || strrpos($routeToken, self::ROUTE_VARIABLE_LAST) !== strlen($routeToken) - 1
                ) { // normal token
                    if ($routeToken !== $uriToken) {
                        continue 2;
                    }
                } else { // variable token
                    $varName = substr(substr($routeToken, 1), 0, -1); // Removes first and last character
                    if (!is_null($filter = $route->getFilter($varName))
                        && preg_match($filter, $uriToken) === 0
                    ) { // Route var filter didn't match URI token.
                        continue 2;
                    }
                    $routeVars[$varName] = $uriToken;
                }
            }

            if ($routeTokenCount === $uriTokenCount) {
                $pass = true;
            }

            if ($pass === true) {
                break;
            }
        }

        if ($pass === true) {
            return new RouteData(
                $route->getCallback(),
                $routeVars,
                $route->getParams()
            );
        }

        return null;
    }

    /**
     * @param string $rule
     * @return string
     * @throws \InvalidArgumentException
     */
    protected function convertToGenericRule($rule)
    {
        if (!is_string($rule)) {
            throw new \InvalidArgumentException('Method "convertToGenericRule" only accepts a string. Input type was: ' . gettype($rule));
        }

        $ruleTokens = explode('/', $rule);
        foreach ($ruleTokens as &$ruleToken) {
            if (strpos($ruleToken, self::ROUTE_VARIABLE_FIRST) === 0
                && strrpos($ruleToken, self::ROUTE_VARIABLE_LAST) === strlen($ruleToken) - 1
            ) { // variable token
                $ruleToken = self::ROUTE_VARIABLE_FIRST . self::ROUTE_VARIABLE_LAST;
            }
        }

        return implode('/', $ruleTokens);
    }
}
