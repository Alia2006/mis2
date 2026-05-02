<?php

namespace app\worker\events;

use think\facade\Config;
use app\worker\library\Monitor;
use GatewayWorker\BusinessWorker;
use app\worker\library\WorkerWsApp;
use app\admin\library\module\Server;

/**
 * ws的业务(Business)进程回调类
 */
class WsBusiness
{
    /**
     * WorkerMan Ws APP 类
     */
    protected static WorkerWsApp $app;

    /**
     * 文件监听配置
     */
    protected static array $monitorConfig = [];

    /**
     * Worker子进程启动时的回调函数，每个子进程启动时都会执行。
     */
    public static function onWorkerStart(BusinessWorker $worker): void
    {
        self::$app         = new WorkerWsApp(root_path());
        self::$app->worker = $worker;
        self::$app->initialize();
        self::$app->cloneInstance();

        self::$monitorConfig = Config::get('worker_monitor');

        if (0 == $worker->id) {
            new Monitor(self::$monitorConfig);
        }

        self::callModuleEvent('onWorkerStart', [
            'worker' => $worker,
        ]);
    }

    public static function onWorkerStop(BusinessWorker $worker): void
    {
        self::callModuleEvent('onWorkerStop', [
            'worker' => $worker,
        ]);
    }

    /**
     * WebSocket 链接成功
     *
     * @param string $clientId 连接id
     * @param array  $data     websocket 握手时的 http 头数据，包含 get、server 等变量
     */
    public static function onWebSocketConnect(string $clientId, array $data): void
    {
        self::$app->servers     = $_SERVER;
        self::$app->requestData = $data;

        self::callModuleEvent('onWebSocketConnect', [
            'clientId' => $clientId,
            'data'     => $data,
        ]);
    }

    /**
     * 当客户端发来消息时触发
     * @param string $clientId 连接id
     * @param mixed  $message  具体消息
     */
    public static function onMessage(string $clientId, mixed $message): bool
    {
        if ($message == 'ping') {
            return true;
        }

        self::$app->clientId = $clientId;
        self::$app->message  = json_decode($message, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return self::$app->send('error', [
                'message' => 'Message parsing error:' . json_last_error_msg(),
                'code'    => 500,
            ]);
        }

        self::$app->init();

        $response = self::$app->http->run();
        $code     = $response->getCode();

        if ($code >= 300) {
            $content     = $response->getContent();
            $contentJson = json_decode($content, true);
            self::$app->send('error', [
                'code'    => $code,
                'content' => json_last_error() != JSON_ERROR_NONE ? $contentJson : $content,
                'data'    => $response->getData(),
                'header'  => $response->getHeader(),
            ]);
        }

        self::$app->http->end($response);
        return true;
    }

    /**
     * 当用户断开连接时触发
     * @param string $clientId 连接id
     */
    public static function onClose(string $clientId): void
    {
        self::callModuleEvent('onWebSocketClose', [
            'clientId' => $clientId,
        ]);
    }

    /**
     * 兼容模块开发：调用所有模块的核心控制器中的指定方法
     */
    public static function callModuleEvent($fun, $params): void
    {
        $installed = Server::installedList(root_path() . 'modules' . DIRECTORY_SEPARATOR);
        foreach ($installed as $item) {
            if ($item['state'] != 1) continue;
            $moduleClass = Server::getClass($item['uid']);
            if (class_exists($moduleClass) && method_exists($moduleClass, $fun)) {
                $handle = new $moduleClass();
                $handle->$fun($params);
            }
        }
    }
}