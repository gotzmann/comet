<?php
declare(strict_types=1);

namespace Comet;

use Comet\Exception\SessionException;

/**
 * Handle PHP sessions data for web users
 *
 * @package Comet
 */
class Session
{
    /** @var string Session name */
    protected static $_sessionName = 'PHPSESSID';

    /** @var string Session handler class which implements SessionHandlerInterface */
    protected static $_handlerClass = 'Comet\Session\FileSessionHandler';

    /** @var null Parameters of __constructor for session handler class */
     protected static $_handlerConfig = null;

    /** @var int Session.gc_probability */
    protected static $_sessionGcProbability = 1;

    /** @var int Session.gc_divisor */
    protected static $_sessionGcDivisor = 1000;

    /** @var int Session.gc_maxlifetime */
    protected static $_sessionGcMaxLifeTime = 1440;

    /** @var \SessionHandlerInterface Session handler instance */
    protected static $_handler = null;

    /** @var array Session data */
    protected $_data = array();

    /** @var bool Session changed and need to save */
    protected $_needSave = false;

    /** @var null Session id */
    protected $_sessionId = null;

   /**
    * Session constructor
    *
    * @param $session_id
    */
    public function __construct($session_id = null)
    {
        if (!$session_id) {
            $session_id = self::createSessionId();
        }

        static::checkSessionId($session_id);
        if (static::$_handler === null) {
            static::initHandler();
        }

        $this->_sessionId = $session_id;
        if ($data = static::$_handler->read($session_id)) {
            $this->_data = \unserialize($data);
        }
    }

    /**
     * Create session id
     *
     * @return string
     */
    protected static function createSessionId()
    {
        return \bin2hex(\pack('d', \microtime(true)) . \pack('N', \mt_rand()));
    }

    /**
     * Get or set session name
     *
     * @param null $name
     * @return string
     */
    public static function sessionName($name = null)
    {
        if ($name !== null && $name !== '') {
            static::$_sessionName = (string)$name;
        }
        return static::$_sessionName;
    }

    /**
     * Get session id
     *
     * @return string
     */
    public function getId()
    {
        return $this->_sessionId;
    }

    /**
     * Get session
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        return isset($this->_data[$name]) ? $this->_data[$name] : $default;
    }

    /**
     * Store data in the session
     *
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
        $this->_needSave = true;
    }

    /**
     * Delete an item from the session
     *
     * @param $name
     */
    public function delete($name)
    {
        unset($this->_data[$name]);
        $this->_needSave = true;
    }

    /**
     * Retrieve and delete an item from the session
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function pull($name, $default = null)
    {
        $value = $this->get($name, $default);
        $this->delete($name);
        return $value;
    }

    /**
     * Store data in the session
     *
     * @param $key
     * @param null $value
     */
    public function put($key, $value = null)
    {
        if (!\is_array($key)) {
            $this->set($key, $value);
            return;
        }

        foreach ($key as $k => $v) {
            $this->_data[$k] = $v;
        }

        $this->_needSave = true;
    }

    /**
     * Remove a piece of data from the session
     *
     * @param $name
     */
    public function forget($name)
    {
        if (\is_scalar($name)) {
            $this->delete($name);
            return;
        }
        if (\is_array($name)) {
            foreach ($name as $key) {
                unset($this->_data[$key]);
            }
        }
        $this->_needSave = true;
    }

    /**
     * Retrieve all the data in the session
     *
     * @return array
     */
    public function all()
    {
        return $this->_data;
    }

    /**
     * Remove all data from the session
     *
     * @return void
     */
    public function flush()
    {
        $this->_needSave = true;
        $this->_data = array();
    }

    /**
     * Determining If An Item Exists In The Session
     *
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_data[$name]);
    }

    /**
     * To determine if an item is present in the session, even if its value is null
     *
     * @param $name
     * @return bool
     */
    public function exists($name)
    {
        return \array_key_exists($name, $this->_data);
    }

    /**
     * Save session to store
     *
     * @return void
     */
    public function save()
    {
        if ($this->_needSave) {
            if (empty($this->_data)) {
                static::$_handler->destroy($this->_sessionId);
            } else {
                static::$_handler->write($this->_sessionId, \serialize($this->_data));
            }
        }
        $this->_needSave = false;
    }

    /**
     * Init
     *
     * @return void
     */
    public static function init()
    {
        if ($gc_probability = \ini_get('session.gc_probability')) {
            self::$_sessionGcProbability = (int)$gc_probability;
        }

        if ($gc_divisor = \ini_get('session.gc_divisor')) {
            self::$_sessionGcDivisor = (int)$gc_divisor;
        }

        if ($gc_max_life_time = \ini_get('session.gc_maxlifetime')) {
            self::$_sessionGcMaxLifeTime = (int)$gc_max_life_time;
        }
    }

    /**
     * Set session handler class
     *
     * @param null $class_name
     * @param null $config
     * @return string
     */
    public static function handlerClass($class_name = null, $config = null)
    {
        if ($class_name) {
            static::$_handlerClass = $class_name;
        }
        if ($config) {
            static::$_handlerConfig = $config;
        }
        return static::$_handlerClass;
    }

    /**
     * Init handler
     *
     * @return void
     */
    protected static function initHandler()
    {
        if (static::$_handlerConfig === null) {
            static::$_handler = new static::$_handlerClass();
        } else {
            static::$_handler = new static::$_handlerClass(static::$_handlerConfig);
        }
    }

    /**
     * Try GC sessions
     *
     * @return void
     */
    public function tryGcSessions()
    {
        if (\rand(1, static::$_sessionGcDivisor) > static::$_sessionGcProbability) {
            return;
        }
        static::$_handler->gc(static::$_sessionGcMaxLifeTime);
    }

    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        $this->save();
        $this->tryGcSessions();
    }

    /**
     * Check session id.
     *
     * @param $session_id
     */
    protected static function checkSessionId($session_id)
    {
        if (!\preg_match('/^[a-zA-Z0-9]+$/', $session_id)) {
            throw new SessionException("session_id $session_id is invalid");
        }
    }
}

// --- Init session

Session::init();