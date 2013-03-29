<?php
namespace Simplr\Request;

class Request
{
    /**
     * @var ArrayContainer
     */
    private $post; // $_POST

    /**
     * @var ArrayContainer
     */
    private $get; // $_GET

    /**
     * @var ArrayContainer
     */
    private $put; // PUT request data. Will be empty array if no PUT request.

    /**
     * @var ArrayContainer
     */
    private $delete; // DELETE request data. Will be empty array if no DELETE request.

    /**
     * @var ArrayContainer
     */
    private $files; // $_FILES

    /**
     * @var ArrayContainer
     */
    private $server; // $_SERVER

    /**
     * @var ArrayContainer
     */
    private $cookies; // $_COOKIE

    /**
     * @var ArrayContainer
     */
    private $env; // $_ENV

    /**
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $args['post'] = array_key_exists('post', $args) ? $args['post'] : $_POST;
        $args['get'] = array_key_exists('cookies', $args) ? $args['cookies'] : $_GET;
        $args['put'] = array_key_exists('put', $args) ? $args['put'] : [];
        $args['delete'] = array_key_exists('delete', $args) ? $args['delete'] : [];
        $args['files'] = array_key_exists('files', $args) ? $args['files'] : $_FILES;
        $args['server'] = array_key_exists('server', $args) ? $args['server'] : $_SERVER;
        $args['cookies'] = array_key_exists('cookies', $args) ? $args['cookies'] : $_COOKIE;
        $args['env'] = array_key_exists('env', $args) ? $args['env'] : $_ENV;

        // Setup PUT/DELETE defaults if necessary.
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if (($requestMethod == 'PUT' || $requestMethod == 'DELETE') && empty($args['put']) && empty($args['delete'])) {
            $inputData = '';
            parse_str(file_get_contents("php://input"), $inputData);
            if ($requestMethod == 'PUT') {
                $args['put'] = $inputData;
            } elseif ($requestMethod == 'DELETE') {
                $args['delete'] = $inputData;
            }
        }

        $this->post = new ArrayContainer($args['post']);
        $this->get = new ArrayContainer($args['get']);
        $this->put = new ArrayContainer($args['put']);
        $this->delete = new ArrayContainer($args['delete']);
        $this->server = new ArrayContainer($args['server']);
        $this->files = new ArrayContainer($args['files']);
        $this->cookies = new ArrayContainer($args['cookies']);
        $this->env = new ArrayContainer($args['env']);
    }

    /**
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->server->get('REQUEST_METHOD');
    }

    /**
     * @return bool
     */
    public function isHttps()
    {
        $https = $this->server->get('HTTPS');
        if (!empty($https)) {
            return true;
        }

        return false;
    }

    public function __get($key)
    {
        $key = strtolower($key);
        $keys = ['post', 'get', 'put', 'delete', 'files', 'server', 'cookies', 'env'];
        if (in_array($key, $keys)) {
            return $this->$key;
        }

        trigger_error('Undefined property: "' . $key . '" via Simplr\Request\Request::__get()', E_USER_NOTICE);
        return null;
    }
}
