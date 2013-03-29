<?php
namespace Simplr\Autoload;

// TODO: Add more standards & keep testing.
/* Features...
 * Autoload by namespace and/or directory
 * Autoloading via numerous popular standards, ex. Pear, PSR1, etc; allow people to add their own standards (via closure that accepts class name as argument)
 * Autoload specific files
 * Conditional autoloading, i.e. only if closure is met
 * Simple configuration via OOP API
 * Debugging functionality; perhaps dumping of autoloading details, attempting to load all classes, etc
 */

class Autoloader
{
    private $autoloadData = ['fileData' => [], 'data' => []];
    private $standards = [];
    private $rootPath = null;

    /**
     * @param null $rootPath
     * @throws \InvalidArgumentException
     */
    public function __construct($rootPath = null)
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'Exception' . DIRECTORY_SEPARATOR . 'AutoloaderException.php');

        if (!is_string($rootPath) && !is_null($rootPath)) {
            throw new \InvalidArgumentException('Autoloader constructor only accepts a string or null. Input type was: ' . gettype($rootPath));
        }
        if (substr($rootPath, -1) !== DIRECTORY_SEPARATOR) {
            $rootPath = $rootPath . DIRECTORY_SEPARATOR;
        }
        $this->rootPath = $rootPath;

        $this->standards['psr0'] = function ($name, $namespace, $options = []) {
            $fileName = '';

            $lastNamespacePos = strripos($name, '\\');
            if ($lastNamespacePos !== false) {
                // Full class name
                $className = substr($name, $lastNamespacePos + 1);

                // Namespace of class
                $nameNamespace = substr($name, 0, $lastNamespacePos + 1);

                // Namespace separator == Directory separator
                $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $nameNamespace);

                // If ignoring namespace for file structure purposes; disobeys part of PSR1
                if (array_key_exists('ignoreNamespace', $options) && $options['ignoreNamespace'] === true) {
                    $fileName = str_replace($nameNamespace, '', $fileName);
                }
            }

            // Underscores in name == Directory separator
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, isset($className) ? $className : $name);

            // Configurable file extension
            $fileName .= array_key_exists('ext', $options) ? $options['ext'] : '.php';

            // Returning file path
            return $options['path'] . $fileName;
        };
    }

    /**
     * @param string|null $rootPath
     * @throws \InvalidArgumentException
     */
    public function setRootPath($rootPath)
    {
        if (!is_string($rootPath) && !is_null($rootPath)) {
            throw new \InvalidArgumentException('Method "setRootPath" only accepts a string or null. Input type was: ' . gettype($rootPath));
        }

        $this->rootPath = $rootPath;
    }

    /**
     * @return string|null
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * @param string $namespace
     * @param array $options Key 'path', 'closure', 'standard' and 'nested' optionally used by the autoload function. This array is passed to the standards function.
     * @return Autoloader
     * @throws \InvalidArgumentException
     * @throws Exception\AutoloaderException
     */
    public function load($namespace = '', array $options = [])
    {
        if (!is_string($namespace)) {
            throw new \InvalidArgumentException('Method "load" argument 1 only accepts a string. Input type was: ' . gettype($namespace));
        }

        // Begin Defaults
        if (!array_key_exists('path', $options)) {
            $options['path'] = $this->rootPath;
        }
        if (!array_key_exists('closure', $options)) {
            $options['closure'] = null;
        }
        if (!array_key_exists('standard', $options)) {
            $options['standard'] = 'PSR0';
        }
        if (!array_key_exists('nested', $options)) {
            $options['nested'] = true;
        }
        // End Defaults

        if (!is_string($options['path']) && !is_null($options['path'])) {
            throw new \InvalidArgumentException('Method "load" argument 2 array key "path" only accepts a string or null. Input type was: ' . gettype($options['path']));
        }
        if (!($options['closure'] instanceof \Closure) && !is_null($options['closure'])) {
            throw new \InvalidArgumentException('Method "load" argument 2 array key "closure" only accepts a closure or null. Input type was: ' . gettype($options['closure']));
        }
        if (!is_string($options['standard'])) {
            throw new \InvalidArgumentException('Method "load" argument 2 array key "standard" only accepts a string. Input type was: ' . gettype($options['standard']));
        }
        if (!is_bool($options['nested'])) {
            throw new \InvalidArgumentException('Method "load" argument 2 array key "nested" only accepts a bool. Input type was: ' . gettype($options['nested']));
        }

        if (array_key_exists($namespace, $this->autoloadData['data'])) {
            throw new Exception\AutoloaderException('Namespace: "' . $namespace . '" already exists. Can not add this namespace twice.');
        }

        if (substr($options['path'], -1) !== DIRECTORY_SEPARATOR) {
            $options['path'] = $options['path'] . DIRECTORY_SEPARATOR;
        }

        $this->autoloadData['data'][$namespace] = $options;

        return $this;
    }

    /**
     * @param string $className
     * @param array $options Key 'path' and 'closure' optionally used by the autoload function.
     * @return Autoloader
     * @throws \InvalidArgumentException
     * @throws Exception\AutoloaderException
     */
    public function loadFile($className, array $options = [])
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Method "loadFile" argument 1 only accepts a string. Input type was: ' . gettype($className));
        }

        // Begin Defaults
        if (!array_key_exists('path', $options)) {
            $options['path'] = null;
        }
        if (!array_key_exists('closure', $options)) {
            $options['closure'] = null;
        }
        // End Defaults

        if (!is_string($options['path']) && !is_null($options['path'])) {
            throw new \InvalidArgumentException('Method "loadFile" argument 2 array key "path" only accepts a string or null. Input type was: ' . gettype($options['path']));
        }
        if (!($options['closure'] instanceof \Closure) && !is_null($options['closure'])) {
            throw new \InvalidArgumentException('Method "loadFile" argument 2 array key "closure" only accepts a closure or null. Input type was: ' . gettype($options['closure']));
        }

        if (array_key_exists($className, $this->autoloadData['fileData'])) {
            throw new Exception\AutoloaderException('Class name: "' . $className . '" already exists. Can not add this class twice.');
        }

        $this->autoloadData['fileData'][$className] = $options;

        return $this;
    }

    /**
     * @param string $standardName
     * @param callable $closure
     * @return Autoloader
     * @throws \InvalidArgumentException
     */
    public function addStandard($standardName, \Closure $closure)
    {
        if (!is_string($standardName)) {
            throw new \InvalidArgumentException('Method "addStandard" argument 1 only accepts a string. Input type was: ' . gettype($standardName));
        }

        $standardName = strtolower($standardName);

        $this->standards[$standardName] = $closure;

        return $this;
    }

    /**
     * @return array
     */
    public function dump()
    {
        return $this->autoloadData;
    }

    /**
     * Registers autoloader via spl_autoload_register.
     *
     * @return Autoloader
     */
    public function register()
    {
        spl_autoload_register([$this, 'autoload']);

        return $this;
    }

    /**
     * Used by spl_autoload_register.
     *
     * @param $name
     * @return bool
     * @throws Exception\AutoloaderException
     */
    private function autoload($name)
    {
        $filePath = false;

        // We have this here so we can report it if a standards-parsing issue occurs
        $standard = null;

        // Attempting to load per-file data first...
        if (array_key_exists($name, $this->autoloadData['fileData'])) {
            $filePath = $this->autoloadData['fileData'][$name]['path'];
            $closure = $this->autoloadData['fileData'][$name]['closure'];

            if (!is_null($closure)) {
                $response = $closure($name);
                if (!is_bool($response)) {
                    trigger_error('Class name: "' . $name . '"\'s closure does not return a bool. Ignoring...', E_USER_WARNING);
                } elseif ($response === false) {
                    return false;
                }
            }
            // Attempting to load per-namespace data...
        } else {
            $namespace = null;
            $pos = strrpos($name, '\\');
            if ($pos !== false) {
                $namespace = substr($name, 0, $pos);
            }

            foreach ($this->autoloadData['data'] as $loadNamespace => $options) {
                if ($loadNamespace === $namespace
                    // If nesting is allowed and $namespace starts with $loadNamespace
                    || ($options['nested'] === true && substr($namespace, 0, strlen($loadNamespace)) === $loadNamespace)
                ) {
                    $closure = $options['closure'];
                    if (!is_null($closure)) {
                        $response = $closure($name);
                        if (!is_bool($response)) {
                            trigger_error('Namespace: "' . $loadNamespace . '"\'s closure does not return a bool. Ignoring...', E_USER_WARNING);
                        } elseif ($response === false) {
                            continue;
                        }
                    }

                    $standard = $options['standard'];
                    if (!array_key_exists(strtolower($standard), $this->standards)) {
                        throw new Exception\AutoloaderException('Namespace: "' . $loadNamespace . '" trying to load non-existant standard: "' . $standard . '".');
                    }

                    $standardsClosure = $this->standards[strtolower($standard)];
                    $filePath = $standardsClosure($name, $loadNamespace, $options);
                    break;
                }
            }
        }

        // If it is false, that means it wasn't caught by our autoloader.
        if ($filePath !== false) {
            // The only way it can be null is if the standards function returns null.
            if (is_null($filePath)) {
                throw new Exception\AutoloaderException('A standards-parsing issue (Standard: "' . $standard . '") has occured while trying to autoload: "' . $name . '".');
            }
            if (!file_exists($filePath)) {
                throw new Exception\AutoloaderException('Attempted to autoload: "' . $name . '" with a non-existant file: "' . $filePath . '".');
            }
            require($filePath);
            return true;
        }

        return false;
    }
}
