<?php

namespace app\worker\library;

use think\App;
use Workerman\Worker;
use think\db\exception\PDOException;
use Workerman\Protocols\Http\Request;
use Workerman\Connection\TcpConnection;

/**
 * WorkerMan HTTP APP基础类
 * @property Worker        $worker
 * @property Request       $woRequest
 * @property TcpConnection $connection
 */
class WorkerHttpApp extends App
{
    /**
     * WorkerMan 进程启动时的 $_SERVER 变量数据
     */
    public array $servers = [];

    private array $containerInstanceBackup = [];

    public array $containerInstanceBackupKey = ['db', 'config', 'lang', 'event', 'http', 'middleware', 'route'];

    public array $containerInstanceCleanKey = ['request', 'log', 'session', 'view', 'response', 'cookie'];

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
    public function init(TcpConnection $connection, Request $request): void
    {
        $this->beginTime  = microtime(true);
        $this->beginMem   = memory_get_usage();
        $this->woRequest  = $request;
        $this->connection = $connection;
        $this->setRuntimePath(root_path() . 'runtime' . DIRECTORY_SEPARATOR);

        $scriptFilePath = public_path() . 'index.php';
        $_SERVER        = array_merge($this->servers, [
            'QUERY_STRING'    => $request->queryString(),
            'REQUEST_TIME'    => time(),
            'REQUEST_METHOD'  => $request->method(),
            'REQUEST_URI'     => $request->uri(),
            'SERVER_NAME'     => $request->host(true),
            'SERVER_PROTOCOL' => 'HTTP/' . $request->protocolVersion(),
            'SERVER_ADDR'     => $connection->getLocalIp(),
            'SERVER_PORT'     => $connection->getLocalPort(),
            'REMOTE_ADDR'     => $connection->getRemoteIp(),
            'REMOTE_PORT'     => $connection->getRemotePort(),
            'SCRIPT_FILENAME' => $scriptFilePath,
            'SCRIPT_NAME'     => DIRECTORY_SEPARATOR . pathinfo($scriptFilePath, PATHINFO_BASENAME),
            'DOCUMENT_ROOT'   => dirname($scriptFilePath),
            'PATH_INFO'       => $request->path(),
            'SERVER_SOFTWARE' => 'WorkerMan Development Server',
        ]);

        $headers = $request->header();
        foreach ($headers as $key => $item) {
            $hKey = str_replace('-', '_', $key);
            if ($hKey == 'content_type') {
                $_SERVER['CONTENT_TYPE'] = $item;
                continue;
            }
            if ($hKey == 'content_length') {
                $_SERVER['CONTENT_LENGTH'] = $item;
                continue;
            }

            $hKey           = strtoupper(str_starts_with($hKey, 'HTTP_') ? $hKey : 'HTTP_' . $hKey);
            $_SERVER[$hKey] = $item;
        }

        $_GET     = $request->get();
        $_POST    = $request->post();
        $_FILES   = $request->file();
        $_REQUEST = array_merge($_REQUEST, $_GET, $_POST);

        $this->clearInstances();
        $this->resetInstance();
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