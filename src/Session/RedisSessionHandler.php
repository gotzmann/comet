<?php

declare(strict_types=1);

namespace Meteor\Session;

use Redis;
use RedisException;
use RuntimeException;
use SessionHandler;

/**
 * Class RedisSessionHandler
 * @package Meteor\Session
 */
class RedisSessionHandler extends SessionHandler
{
    protected Redis $redis;

    protected int $maxLifeTime;

    /**
     * @throws RedisException
     */
    public function __construct($config)
    {
        if (false === extension_loaded('redis')) {
            throw new RuntimeException('Please install redis extension.');
        }
        $this->maxLifeTime = (int)ini_get('session.gc_maxlifetime');

        if (!isset($config['timeout'])) {
            $config['timeout'] = 2;
        }

        $this->redis = new Redis();
        if (false === $this->redis->connect($config['host'], $config['port'], $config['timeout'])) {
            throw new RuntimeException("Redis connect {$config['host']}:{$config['port']} fail.");
        }
        if (!empty($config['auth'])) {
            $this->redis->auth($config['auth']);
        }
        if (!empty($config['database'])) {
            $this->redis->select($config['database']);
        }
        if (empty($config['prefix'])) {
            $config['prefix'] = 'redis_session_';
        }
        $this->redis->setOption(Redis::OPT_PREFIX, $config['prefix']);
    }

    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * @throws RedisException
     */
    public function read($id): string|false
    {
        $value = $this->redis->get($id);
        return is_string($value) ? $value : false;
    }

    /**
     * @throws RedisException
     */
    public function write($id, $data): bool
    {
        return true === $this->redis->setex($id, $this->maxLifeTime, $data);
    }

    /**
     * @throws RedisException
     */
    public function destroy($id): bool
    {
        $this->redis->del($id);
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($max_lifetime): int|false
    {
        return $this->maxLifeTime;
    }
}
