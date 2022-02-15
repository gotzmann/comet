<?php
declare(strict_types=1);

namespace Comet\Session;

/**
 * Class FileSessionHandler
 * @package Comet\Session
 */
class FileSessionHandler implements \SessionHandlerInterface
{
    /** @var string Session save path */
    protected static $_sessionSavePath = null;

    /** @var string Session file prefix */
    protected static $_sessionFilePrefix = 'sess_';

    /**
     * Init
     */
    public static function init() {
        $save_path = @\session_save_path();
        if (!$save_path || \strpos($save_path, 'tcp://') === 0) {
            $save_path = \sys_get_temp_dir();
        }
        static::sessionSavePath($save_path);
    }

    /**
     * FileSessionHandler constructor.
     * @param array $config
     */
    public function __construct($config = array()) {
        if (isset($config['save_path'])) {
            static::sessionSavePath($config['save_path']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($save_path, $name): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($session_id): string
    {
        $session_file = static::sessionFile($session_id);
        \clearstatcache();
        if (\is_file($session_file)) {
            $data = \file_get_contents($session_file);
            return $data ? $data : '';
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($session_id, $session_data): bool
    {
        $temp_file = static::$_sessionSavePath . uniqid(strval(mt_rand()), true);
        if (!\file_put_contents($temp_file, $session_data)) {
            return false;
        }
        return \rename($temp_file, static::sessionFile($session_id));
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
    public function destroy($session_id): bool
    {
        $session_file = static::sessionFile($session_id);
        if (\is_file($session_file)) {
            \unlink($session_file);
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime): int {
        $time_now = \time();
        foreach (\glob(static::$_sessionSavePath . static::$_sessionFilePrefix . '*') as $file) {
            if(\is_file($file) && $time_now - \filemtime($file) > $maxlifetime) {
                \unlink($file);
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
    protected static function sessionFile($session_id) {
        return static::$_sessionSavePath.static::$_sessionFilePrefix.$session_id;
    }

    /**
     * Get or set session file path.
     *
     * @param $path
     * @return string
     */
    public static function sessionSavePath($path) {
        if ($path) {
            if ($path[\strlen($path)-1] !== DIRECTORY_SEPARATOR) {
                $path .= DIRECTORY_SEPARATOR;
            }
            static::$_sessionSavePath = $path;
            if (!\is_dir($path)) {
                \mkdir($path, 0777, true);
            }
        }
        return $path;
    }
}

// --- Init

FileSessionHandler::init();