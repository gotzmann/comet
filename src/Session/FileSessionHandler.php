<?php

declare(strict_types=1);

namespace Meteor\Session;

use RuntimeException;
use function session_save_path;

/**
 * Class FileSessionHandler
 * @package Meteor\Session
 */
class FileSessionHandler implements \SessionHandlerInterface
{
    protected static ?string $sessionSavePath = null;

    protected static string $sessionFilePrefix = 'sess_';

    /**
     * Init
     */
    public static function init(): void
    {
        $save_path = @session_save_path();
        if (!$save_path || str_starts_with($save_path, 'tcp://')) {
            $save_path = sys_get_temp_dir();
        }
        static::sessionSavePath($save_path);
    }

    public function __construct(array $config = [])
    {
        if (isset($config['save_path'])) {
            static::sessionSavePath($config['save_path']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($id): string
    {
        $session_file = static::sessionFile($id);
        clearstatcache();
        if (is_file($session_file)) {
            $data = file_get_contents($session_file);
            return $data ?: '';
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $id, string $data): bool
    {
        $temp_file = static::$sessionSavePath . uniqid((string)mt_rand(), true);
        if (!file_put_contents($temp_file, $data)) {
            return false;
        }
        return rename($temp_file, static::sessionFile($id));
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(string $id): bool
    {
        $session_file = static::sessionFile($id);
        if (is_file($session_file)) {
            unlink($session_file);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int
    {
        $time_now = time();
        foreach (glob(static::$sessionSavePath . static::$sessionFilePrefix . '*') as $file) {
            if (is_file($file) && $time_now - filemtime($file) > $maxlifetime) {
                unlink($file);
            }
        }
        return 0; // TODO https://www.php.net/manual/en/sessionhandlerinterface.gc.php
    }

    /**
     * Get session file path.
     *
     * @param $session_id
     * @return string
     */
    protected static function sessionFile($session_id): string
    {
        return static::$sessionSavePath . static::$sessionFilePrefix . $session_id;
    }

    /**
     * Get or set session file path.
     *
     * @param $path
     * @return string
     */
    public static function sessionSavePath($path): string
    {
        if ($path) {
            if ($path[strlen($path) - 1] !== DIRECTORY_SEPARATOR) {
                $path .= DIRECTORY_SEPARATOR;
            }
            static::$sessionSavePath = $path;
            if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }
        return $path;
    }
}
