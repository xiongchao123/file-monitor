<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/3/9
 * Time: 15:50
 */


class Reload
{
    /**
     * 监听文件变化的路径
     *
     * @var string
     */
    private $watchDir;

    /**
     * the lasted md5 of dir
     *
     * @var string
     */
    private $md5File = '';

    /**
     * the interval of scan
     *
     * @var int
     */
    private $interval = 3;

    /**
     * 初始化方法
     */
    public function __construct($dir)
    {
        $this->watchDir = $dir;
        $this->md5File = FileHelper::md5File($this->watchDir);
    }


    /**
     * 启动监听
     */
    public function run()
    {
        while (true) {
            sleep($this->interval);
            $md5File = FileHelper::md5File($this->watchDir);
            if (strcmp($this->md5File, $md5File) !== 0) {
                echo "Start reloading...\n";
                /*
                 * //进行reload操作
                 */
                $pidFile = file_get_contents("/tmp/server.pid");
                $pids = explode(',', $pidFile);
                posix_kill($pids[1], SIGUSR1);
                echo "Reloaded\n";
            }
            $this->md5File = $md5File;
        }
    }
}


class FileHelper
{

    /**
     * 获得文件扩展名、后缀名
     * @param $filename
     * @param bool $clearPoint 是否带点
     * @return string
     */
    public static function getSuffix($filename, $clearPoint = false): string
    {
        $suffix = strrchr($filename, '.');

        return (bool)$clearPoint ? trim($suffix, '.') : $suffix;
    }

    /**
     * @param $path
     * @return bool
     */
    public static function isAbsPath($path): bool
    {
        if (!$path || !is_string($path)) {
            return false;
        }

        if (
            $path{0} === '/' ||  // linux/mac
            1 === preg_match('#^[a-z]:[\/|\\\]{1}.+#i', $path) // windows
        ) {
            return true;
        }

        return false;
    }

    /**
     * md5 of dir
     *
     * @param string $dir
     *
     * @return bool|string
     */
    public static function md5File($dir)
    {
        if (!is_dir($dir)) {
            return "";
        }

        $md5File = array();
        $d = dir($dir);
        while (false !== ($entry = $d->read())) {
            if ($entry != '.' && $entry != '..') {
                if (is_dir($dir . '/' . $entry)) {
                    $md5File[] = self::md5File($dir . '/' . $entry);
                } elseif (substr($entry, -4) === '.php') {
                    $md5File[] = md5_file($dir . '/' . $entry);
                }
                $md5File[] = $entry;
            }
        }
        $d->close();

        return md5(implode('', $md5File));
    }
}
