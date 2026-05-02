<?php

namespace app\worker\library;

use think\App;
use GatewayWorker\Lib\Gateway;
use GatewayWorker\BusinessWorker;
use think\db\exception\PDOException;

/**
 * WorkerMan WS APP基础类
 * @property BusinessWorker $worker
 */
class WorkerWsApp extends App
{
    /**
     * 连接ID
     */
    public string $clientId;

    private array $containerInstanceBackup = [];

    public array $containerInstanceBackupKey = ['db', 'config', 'lang', 'event', 'http', 'middleware', 'route'];

    public array $containerInstanceCleanKey = ['request', 'log', 'session', 'view', 'response', 'cookie'];

    /**
     * WS 链接成功时的 $_SERVER 变量数据
     */
    public array $servers = [];

    /**
     * WebSocket 握手时的 http 头数据，包含 get、server 等变量
     */
    public array $requestData = [];

    /**
     * 当客户端发来消息时(onMessage)的具体消息内容
     */
    public mixed $message;

    /**
     * Worker App 初始化
     */
    public function initialize(): void
    {
        parent::initialize();

        // 初始化 Db 类单例并连接数据库
        try {
            $this->db->execute("SELECT 1");
        } catch (PDOException) {
        }
    }

    /**
     * 新的请求初始化
     */
    public function init(): void
    {
        // 输入过滤
        array_walk_recursive($this->servers, ['app\worker\library\Helper', 'cleanXss']);
        array_walk_recursive($this->message, ['app\worker\library\Helper', 'cleanXss']);
        array_walk_recursive($this->requestData, ['app\worker\library\Helper', 'cleanXss']);

        $this->beginTime = microtime(true);
        $this->beginMem  = memory_get_usage();
        $scriptFilePath  = public_path() . 'index.php';
        $this->setRuntimePath(root_path() . 'runtime' . DIRECTORY_SEPARATOR);

        $_GET     = $this->requestData['get'] ?? [];
        $_REQUEST = array_merge($_REQUEST, $_GET);
        $_SERVER  = array_merge([
            'PATH_INFO'       => $this->message['pathInfo'] ?? 'worker/WebSocket/index',
            'SCRIPT_FILENAME' => $scriptFilePath,
            'SCRIPT_NAME'     => DIRECTORY_SEPARATOR . pathinfo($scriptFilePath, PATHINFO_BASENAME),
            'DOCUMENT_ROOT'   => dirname($scriptFilePath),
            'HTTP_ACCEPT'     => 'application/json, text/plain, */*',
        ], $this->servers, $this->requestData['server'] ?? []);

        $this->message['MESSAGE_TIME'] = time();

        $this->clearInstances();
        $this->resetInstance();
    }

    /**
     * 发送消息
     * @param string $type 消息类型
     * @param mixed  $data 消息数据
     * @param ?mixed $uid  uid
     */
    public function send(string $type, mixed $data, mixed $uid = null): bool
    {
        $result = $this->assembleMessage($type, $data);

        if (!is_null($uid)) {
            if (Gateway::isUidOnline($uid)) {
                Gateway::sendToUid($uid, $result);
            }
            return true;
        }

        if (Gateway::isOnline($this->clientId)) {
            return Gateway::sendToClient($this->clientId, $result);
        }
        return true;
    }

    /**
     * 组装一条 ws 消息
     * @param string $type 消息类型
     * @param mixed  $data 消息数据
     */
    public function assembleMessage(string $type, mixed $data): string
    {
        return json_encode([
            'type' => $type,
            'data' => $data,
            'path' => $this->message['pathInfo'] ?? '',
            'time' => $this->message['MESSAGE_TIME'] ?? '',
        ]);
    }

    /**
     * 常驻内存模式下需要重置/不兼容的实例，全部删除
     */
    public function clearInstances(): void
    {
        foreach ($this->containerInstanceCleanKey as $instance) {
            $this->delete($instance);
        }
    }

    /**
     * 克隆一些实例（一般在 new app 后，立即克隆实例最初始的状态以便复用）
     */
    public function cloneInstance(): void
    {
        foreach ($this->containerInstanceBackupKey as $instance) {
            // 不存在的实例，APP 类中的魔术方法会自动创建一个新的
            $this->containerInstanceBackup[$instance] = clone $this->$instance;
        }
    }

    /**
     * 还原克隆的实例
     */
    public function resetInstance(): void
    {
        foreach ($this->containerInstanceBackupKey as $instance) {
            $this->instance($instance, clone $this->containerInstanceBackup[$instance]);
        }
    }
}