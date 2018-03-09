<?php

namespace Boom\Process;

class NotFound extends \Exception
{

}

class AutoReload
{
    /**
     * @var resource
     */
    protected $inotify;
    protected $pid;

    protected $reloadFileTypes = array('.php' => true);
    protected $watchFiles = array();
    protected $afterNSeconds = 3;

    /**
     * 正在reload
     */
    protected $reloading = false;

    protected $events;

    /**
     * 根目录
     * @var array
     */
    protected $rootDirs = array();

    function putLog($log)
    {
        $_log = "[".date('Y-m-d H:i:s')."]\t".$log."\n";
        echo $_log;
    }

    /**
     * @throws NotFound
     */
    function __construct($pid)
    {
        $this->pid = $pid;

        if (@posix_kill($pid, 0) === false)
        {
            throw new NotFound("Process#$pid not found.");
        }

        $this->inotify = inotify_init();
        $this->events = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;

        swoole_event_add($this->inotify, function ($ifd) {
            $events = inotify_read($this->inotify);
            if (!$events)
            {
                return;
            }
            var_dump($events);
            foreach($events as $ev)
            {
                if ($ev['mask'] == IN_IGNORED)
                {
                    $this->putLog("the watch was delete");
                    //continue;
                }
                else if ($ev['mask'] == IN_CREATE or $ev['mask'] == IN_DELETE or $ev['mask'] == IN_MODIFY or $ev['mask'] == IN_MOVED_TO or $ev['mask'] == IN_MOVED_FROM)
                {
                    $fileType = strrchr($ev['name'], '.');
                    //非重启类型
                    if (!isset($this->reloadFileTypes[$fileType]))
                    {
                        $this->putLog("this fileType not be monitor");
                       // continue;
                    }else{
                        //正在reload，不再接受任何事件，冻结10秒
                        if (!$this->reloading)
                        {
                            $this->putLog("after 3 seconds reload the server");
                            //有事件发生了，进行重启
                            swoole_timer_after($this->afterNSeconds * 1000, array($this, 'reload'));
                            $this->reloading = true;
                        }
                    }
                }
            }
        },null, SWOOLE_EVENT_READ);
    }

    function reload()
    {
        $this->putLog("reloading");
        //清理所有监听
        $this->clearWatch();
        //重新监听
        foreach($this->rootDirs as $root)
        {
            $this->watch($root);
        }
        //继续进行reload
        $this->reloading = false;
        
        //向主进程发送信号
        var_dump(@posix_kill($this->pid, SIGUSR1));
    }

    /**
     * 添加文件类型
     * @param $type
     */
    function addFileType($type)
    {
        $type = trim($type, '.');
        $this->reloadFileTypes['.' . $type] = true;
    }

    /**
     * 添加事件
     * @param $inotifyEvent
     */
    function addEvent($inotifyEvent)
    {
        $this->events |= $inotifyEvent;
    }

    /**
     * 清理所有inotify监听
     */
    function clearWatch()
    {
        foreach($this->watchFiles as $wd)
        {
            inotify_rm_watch($this->inotify, $wd);
        }
        $this->watchFiles = array();
    }

    /**
     * @param $dir
     * @param bool $root
     * @return bool
     * @throws NotFound
     */
    function watch($dir, $root = true)
    {
        //目录不存在
        if (!is_dir($dir))
        {
            throw new NotFound("[$dir] is not a directory.");
        }
        //避免重复监听
        if (isset($this->watchFiles[$dir]))
        {
            return false;
        }
        //根目录
        if ($root)
        {
            $this->rootDirs[] = $dir;
        }

        $wd = inotify_add_watch($this->inotify, $dir, $this->events);
        $this->watchFiles[$dir] = $wd;

        $files = scandir($dir);
        foreach ($files as $f)
        {
            if ($f == '.' or $f == '..')
            {
                continue;
            }
            $path = $dir . '/' . $f;
            //递归目录
            if (is_dir($path))
            {
                $this->watch($path, false);
            }
            //检测文件类型
            $fileType = strrchr($f, '.');
            if (isset($this->reloadFileTypes[$fileType]))
            {
                $wd = inotify_add_watch($this->inotify, $path, $this->events);
                $this->watchFiles[$path] = $wd;
            }
        }
        return true;
    }

    function run()
    {
        //php version < 5.4
//        swoole_event_wait();
    }
}
