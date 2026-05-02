<?php

namespace app\worker\events;

use Throwable;
use ba\Filesystem;
use Workerman\Timer;
use Workerman\Worker;
use think\facade\Config;
use app\ExceptionHandle;
use app\worker\library\Helper;
use app\worker\library\Monitor;
use app\worker\library\WorkerHttpApp;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Connection\TcpConnection;

/**
 * http的回调类
 * 实现了静态文件访问和API访问
 */
class Http
{

    /**
     * 文件监听配置
     */
    protected static array $monitorConfig = [];

    /**
     * 等待响应的请求计数
     */
    protected static int $waitResponseCount = 0;

    /**
     * WorkerMan Http APP 类
     */
    protected WorkerHttpApp $app;

    /**
     * 构造函数
     * 此函数内不能初始化资源(MySQL连接、Redis连接、Memcache连接等，比如不能使用Db)
     */
    public function __construct()
    {
    }

    /**
     * Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     */
    public function onWorkerStart(Worker $worker): void
    {
        Helper::cleanRuntimeCache();

        $this->app          = new WorkerHttpApp(root_path());
        $this->app->worker  = $worker;
        $this->app->servers = $_SERVER;
        $this->app->initialize();
        $this->app->cloneInstance();

        // 文件监听配置
        if (!self::$monitorConfig) {
            self::$monitorConfig = Config::get('worker_monitor');
        }

        if (0 == $worker->id) {
            new Monitor(self::$monitorConfig);
        }
    }

    /**
     * 当客户端通过连接发来数据时(WorkerMan收到数据时)触发的回调函数
     */
    public function onMessage(TcpConnection $connection, Request $request): void
    {
        $this->app->init($connection, $request);

        $path = $request->path() ?: '/';
        $file = Filesystem::fsFit(public_path() . urldecode($path));

        if (!is_file($file)) {
            // 访问控制器

            self::$waitResponseCount++;
            if (self::$monitorConfig['soft_reboot'] && !Monitor::isPaused()) {
                Monitor::pause();
            }

            try {
                $level = ob_get_level();
                ob_start();

                $response = $this->app->http->run();

                if (ob_get_length() > 0) {
                    $content = $response->getContent();
                    $response->content(ob_get_contents() . $content);
                }

                while (ob_get_level() > $level) {
                    ob_end_clean();
                }
            } catch (Throwable $e) {
                $handle = $this->app->make(ExceptionHandle::class);
                $handle->report($e);
                $response = $handle->render($this->app->request, $e);
            }

            $connection->send(new Response($response->getCode(), $response->getHeader(), $response->getContent()));
            $this->app->http->end($response);

            self::$waitResponseCount--;
            if (self::$waitResponseCount <= 0 && self::$monitorConfig['soft_reboot']) {
                // 隔一次时间间隔再启动检测
                Timer::add(self::$monitorConfig['interval'], function () {
                    if (Monitor::isPaused()) {
                        Monitor::resume();
                    }
                }, [], false);
            }
        } else {
            // 访问静态文件

            // 文件未修改，且存在 if-modified-since 则返回 304
            if (!empty($ifModifiedSince = $request->header('if-modified-since'))) {
                $modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
                if ($modifiedTime === $ifModifiedSince) {
                    $connection->send(new Response(304));
                    return;
                }
            }

            $pathInfo = pathinfo($file);
            $response = (new Response())->withFile($file);

            // 已经检查过文件存在，无需担心后缀识别上的 /.（无后缀） 攻击
            if (!empty($pathInfo['extension'])) {
                $extension = strtolower($pathInfo['extension']);

                // 禁止访问的文件
                if (in_array($extension, Config::get('worker_http.staticServer.deny'))) {
                    $connection->send(new Response(404));
                    return;
                }

                // 要求浏览器下载而不是预览
                if (in_array($extension, Config::get('worker_http.staticServer.attachment'))) {
                    $response->withHeader('Content-Disposition', "attachment; filename={$pathInfo['basename']}");
                }
            }

            // 文件修改过或没有 if-modified-since 头则发送文件
            $connection->send($response);
        }
    }

    /**
     * 当客户端的连接上发生错误时触发。
     */
    public function onError(TcpConnection $connection, $code, $msg)
    {
    }
}