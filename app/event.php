<?php
// 事件定义文件
return [
    'bind' => [
    ],

    'listen' => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => [],

        // 工作流通知（站内信 + 邮件）
        'workflow.task.assigned'       => [function ($data) { \app\listener\WorkflowNotify::onTaskAssigned($data); }],
        'workflow.instance.completed'  => [function ($instance) { \app\listener\WorkflowNotify::onInstanceCompleted($instance); }],
        'workflow.instance.rejected'   => [function ($instance) { \app\listener\WorkflowNotify::onInstanceRejected($instance); }],
        'workflow.instance.cancelled'  => [function ($instance) { \app\listener\WorkflowNotify::onInstanceCancelled($instance); }],
        'workflow.task.cc'             => [function ($data) { \app\listener\WorkflowNotify::onTaskCc($data); }],
    ],

    'subscribe' => [
    ],
];
