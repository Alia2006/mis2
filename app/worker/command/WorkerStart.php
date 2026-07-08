<?php

namespace app\worker\command;

use GatewayWorker\Gateway;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Register;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\facade\Config;
use Workerman\Worker;

/**
 * Workerman 启动命令
 *
 * 用法:
 *   php think worker:start            # 启动所有服务（前台）
 *   php think worker:start -d         # 启动所有服务（守护进程）
 *   php think worker:stop             # 停止所有服务
 *   php think worker:reload           # 平滑重启所有服务
 *   php think worker:status           # 查看服务状态
 *   php think worker:restart          # 重启所有服务
 *   php think worker:start --mode=http   # 仅启动 HTTP 服务
 *   php think worker:start --mode=ws     # 仅启动 WebSocket 服务
 */
class WorkerStart extends Command
{
    /**
     * 配置命令
     */
    protected function configure(): void
    {
        $this->setName('worker:start')
            ->addArgument('action', null, '操作类型: start|stop|reload|status|restart', 'start')
            ->addOption('mode', 'm', Option::VALUE_OPTIONAL, '启动模式: all|http|ws', 'all')
            ->addOption('daemon', 'd', Option::VALUE_NONE, '以守护进程方式运行')
            ->setDescription('Workerman 服务管理 (WebSocket + HTTP)');
    }

    /**
     * 执行命令
     */
    protected function execute(Input $input, Output $output): int
    {
        // 检查环境
        if (DIRECTORY_SEPARATOR === '\\') {
            $output->error('Workerman 无法在 Windows 原生环境下运行，请在 WSL 中执行此命令。');
            $output->writeln('');
            $output->writeln('请在 WSL 中运行:');
            $output->writeln('  cd /mnt/d/www/mis2');
            $output->writeln('  php think worker:start');
            return 1;
        }

        // 检查必需扩展
        $this->checkExtensions($output);

        $action = $input->getArgument('action');
        $mode   = $input->getOption('mode');
        $daemon = $input->getOption('daemon');

        // Workerman 通过 $argv 解析全局命令（start/stop/reload/status/restart）
        global $argv;
        $argv[1] = $action;
        if ($daemon) {
            $argv[2] = '-d';
        }

        // 设置 Worker 静态属性（logFile, pidFile）
        $wsConfig    = Config::get('worker_ws', []);
        $httpConfig  = Config::get('worker_http', []);

        // 优先使用 WS 的 option（所有 Worker 共享同一个 pidFile 和 logFile）
        $option = $wsConfig['option'] ?? $httpConfig['option'] ?? [];
        if (!empty($option['logFile'])) {
            Worker::$logFile = $option['logFile'];
        }
        if (!empty($option['pidFile'])) {
            Worker::$pidFile = $option['pidFile'];
        }

        // 初始化 Worker 进程
        if ($mode === 'all' || $mode === 'ws') {
            $this->startWebSocketServices();
        }
        if ($mode === 'all' || $mode === 'http') {
            $this->startHttpService();
        }

        // 输出信息
        $output->writeln('<info>═══════════════════════════════════════════════</info>');
        $output->writeln('<info>  Workerman 服务管理 - BuildAdmin (ThinkPHP8)</info>');
        $output->writeln('<info>═══════════════════════════════════════════════</info>');

        if ($mode === 'all' || $mode === 'http') {
            $httpOpt = $httpConfig['option'];
            $output->writeln("<info>  HTTP 服务:  http://{$httpOpt['ip']}:{$httpOpt['port']}</info>");
        }
        if ($mode === 'all' || $mode === 'ws') {
            $wsGateway = $wsConfig['gateway'];
            $output->writeln("<info>  WS  服务:  ws://{$wsGateway['ip']}:{$wsGateway['port']}</info>");
        }

        $output->writeln("<info>  模式:      {$mode}</info>");
        $output->writeln("<info>  动作:      {$action}" . ($daemon ? ' (守护进程)' : '') . "</info>");
        $output->writeln('<info>═══════════════════════════════════════════════</info>');
        $output->writeln('');

        Worker::runAll();

        return 0;
    }

    /**
     * 启动 WebSocket 相关服务 (Register + Gateway + BusinessWorker)
     */
    protected function startWebSocketServices(): void
    {
        $config = Config::get('worker_ws', []);

        // ============================
        // 1. Register 注册服务
        // ============================
        $registerConfig = $config['register'];
        new Register("text://{$registerConfig['ip']}:{$registerConfig['port']}");

        // ============================
        // 2. Gateway 网关服务
        // ============================
        $gatewayConfig = $config['gateway'];

        $gateway = new Gateway(
            "{$gatewayConfig['protocol']}://{$gatewayConfig['ip']}:{$gatewayConfig['port']}",
            $config['gatewayContext'] ?? []
        );

        // 应用 Gateway 实例属性（排除用于构造函数的 protocol/ip/port）
        $constructorKeys = ['protocol', 'ip', 'port'];
        foreach ($gatewayConfig as $key => $value) {
            if (in_array($key, $constructorKeys)) {
                continue;
            }
            if (property_exists($gateway, $key)) {
                $gateway->$key = $value;
            }
        }

        // ============================
        // 3. BusinessWorker 业务进程
        // ============================
        $businessConfig = $config['business'];
        $worker         = new BusinessWorker();

        foreach ($businessConfig as $key => $value) {
            if (property_exists($worker, $key)) {
                $worker->$key = $value;
            }
        }

        $worker->eventHandler = $businessConfig['eventHandler'];
    }

    /**
     * 启动 HTTP Worker 服务
     */
    protected function startHttpService(): void
    {
        $config = Config::get('worker_http', []);
        $option = $config['option'];

        $worker = new Worker(
            "{$option['protocol']}://{$option['ip']}:{$option['port']}",
            $config['context'] ?? []
        );

        // 应用实例属性（pidFile/logFile 是静态属性，已在 execute() 中统一设置）
        $instanceProps = ['name', 'count'];
        foreach ($instanceProps as $prop) {
            if (isset($option[$prop])) {
                $worker->$prop = $option[$prop];
            }
        }

        // 注册回调事件
        $eventHandler    = $config['eventHandler'];
        $eventsToRegister = $config['events'] ?? [];
        $instance         = new $eventHandler();

        foreach ($eventsToRegister as $event) {
            if (method_exists($instance, $event)) {
                $worker->$event = [$instance, $event];
            }
        }
    }

    /**
     * 检查必需的 PHP 扩展
     */
    protected function checkExtensions(Output $output): void
    {
        $required = ['pcntl', 'posix'];
        $missing  = [];

        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }

        if ($missing) {
            $output->error('缺少必需的 PHP 扩展: ' . implode(', ', $missing));
            $output->writeln('');
            $output->writeln('请在 WSL 中安装:');
            $output->writeln('  sudo apt-get install -y php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-' . implode(' php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-', $missing));
            $output->writeln('');
            $output->writeln('或者安装全部常用扩展:');
            $output->writeln('  sudo apt-get install -y php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-{cli,fpm,mysql,gd,bcmath,mbstring,xml,curl,zip,pcntl,posix}');
            return;
        }

        $output->writeln('<info>[√] PHP 扩展检查通过</info>');
    }
}
