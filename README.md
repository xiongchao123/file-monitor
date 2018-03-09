# 自动重启脚本

### 方法一

* 使用`inotify`监听PHP源码目录
* 程序文件更新时自动`reload`服务器程序

运行脚本
----
依赖`inotify`和`swoole`扩展
```shell
pecl install swoole
pecl install inotify
```

运行程序
```shell
php inotify.php

```

测试效果
```shell
echo "Test" > /test/test.php

```

### 方法二

* 通过计算文件散列表监听文件变化
* 程序文件更新时自动`reload`服务器程序

运行脚本
----
依赖`inotify`和`swoole`扩展
```shell
pecl install swoole
```

运行程序
```shell
php md5.php

```

测试效果
```shell
echo "Test2" > /test/test.php

