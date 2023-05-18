<?php

declare(strict_types=1);

namespace Meteor;

use Meteor\Exception\SessionException;
use Meteor\Session\FileSessionHandler;
use Exception;

/**
 * Handle PHP sessions data for web users
 *
 * @package Meteor
 */
class Session
{
    protected static string $sessionName = 'PHPSESSID';

    protected static string $handlerClass = FileSessionHandler::class;

    protected static ?array $handlerConfig = null;

    protected static int $sessionGcProbability = 1;

    protected static int $sessionGcDivisor = 1000;

    protected static int $sessionGcMaxLifeTime = 1440;

    protected static ?\SessionHandlerInterface $handler = null;

    protected array $data = [];

    protected bool $needSave = false;

    protected ?string $sessionId = null;

    public function __construct(?string $session_id = null)
    {
        if (!$session_id) {
            $session_id = self::createSessionId();
        }

        static::checkSessionId($session_id);
        if (static::$handler === null) {
            static::initHandler();
        }

        $this->sessionId = $session_id;
        if ($data = static::$handler->read($session_id)) {
            $this->data = unserialize($data, ['allowed_classes' => false]);
        }
    }

    protected static function createSessionId(): string
    {
        return bin2hex(pack('d', microtime(true)) . pack('N', mt_rand()));
    }

    public static function sessionName(?string $name = null): string
    {
        if ($name !== null && $name !== '') {
            static::$sessionName = $name;
        }
        return static::$sessionName;
    }

    public function getId(): ?string
    {
        return $this->sessionId;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->data[$name] ?? $default;
    }

    public function set($name, $value): void
    {
        $this->data[$name] = $value;
        $this->needSave = true;
    }

    public function delete($name): void
    {
        unset($this->data[$name]);
        $this->needSave = true;
    }

    /**
     * Retrieve and delete an item from the session
     *
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function pull($name, $default = null): mixed
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
    public function put($key, $value = null): void
    {
        if (!is_array($key)) {
            $this->set($key, $value);
            return;
        }

        foreach ($key as $k => $v) {
            $this->data[$k] = $v;
        }

        $this->needSave = true;
    }

    /**
     * Remove a piece of data from the session
     *
     * @param $name
     */
    public function forget($name): void
    {
        if (is_scalar($name)) {
            $this->delete($name);
            return;
        }
        if (is_array($name)) {
            foreach ($name as $key) {
                unset($this->data[$key]);
            }
        }
        $this->needSave = true;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function flush(): void
    {
        $this->needSave = true;
        $this->data = array();
    }

    public function has($name): bool
    {
        return isset($this->data[$name]);
    }

    public function exists($name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function save(): void
    {
        if ($this->needSave) {
            if (empty($this->_data)) {
                static::$handler->destroy($this->sessionId);
            } else {
                static::$handler->write($this->sessionId, serialize($this->_data));
            }
        }
        $this->needSave = false;
    }

    /**
     * Init
     *
     * @return void
     */
    public static function init(): void
    {
        if ($gc_probability = ini_get('session.gc_probability')) {
            self::$sessionGcProbability = (int) $gc_probability;
        }

        if ($gc_divisor = ini_get('session.gc_divisor')) {
            self::$sessionGcDivisor = (int) $gc_divisor;
        }

        if ($gc_max_life_time = ini_get('session.gc_maxlifetime')) {
            self::$sessionGcMaxLifeTime = (int) $gc_max_life_time;
        }
    }

    protected static function initHandler(): void
    {
        FileSessionHandler::init();

        if (static::$handlerConfig === null) {
            static::$handler = new static::$handlerClass();
        } else {
            static::$handler = new static::$handlerClass(static::$handlerConfig);
        }
    }

    /**
     * @throws Exception
     */
    public function tryGcSessions(): void
    {
        if (random_int(1, static::$sessionGcDivisor) > static::$sessionGcProbability) {
            return;
        }
        static::$handler->gc(static::$sessionGcMaxLifeTime);
    }

    /**
     * @throws Exception
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
    protected static function checkSessionId($session_id): void
    {
        if (!\preg_match('/^[a-zA-Z0-9]+$/', $session_id)) {
            throw new SessionException("session_id $session_id is invalid");
        }
    }
}
