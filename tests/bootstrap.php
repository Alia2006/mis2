<?php

// PHPUnit 引导文件：初始化 ThinkPHP 容器，使 Db / Event / 模型 / WorkflowEngine 可用。
define('APP_PATH', __DIR__ . '/../app/');

require __DIR__ . '/../vendor/autoload.php';

$app = new \think\App(dirname(__DIR__));
$app->initialize();
