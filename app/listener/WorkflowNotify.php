<?php

namespace app\listener;

use Throwable;
use think\facade\Db;
use think\facade\Log;
use app\admin\model\workflow\Notification;
use app\common\library\Email;

/**
 * 工作流事件监听器
 *
 * 监听 WorkflowEngine 触发的 workflow.* 事件，完成两类副作用：
 *   1. 写入站内信（workflow_notification）
 *   2. 通过系统邮件配置发送邮件通知（未配置邮件时自动跳过）
 *
 * 监听器不阻断主流程：邮件发送失败仅记录日志，不影响审批流转与站内信。
 *
 * 方法均为静态，便于在 app/event.php 中以闭包方式注册到具体事件。
 */
class WorkflowNotify
{
    /**
     * 写入一条站内信
     */
    private static function notify(int $adminId, string $type, string $title, string $content, array $extra = []): void
    {
        Notification::create(array_merge([
            'admin_id'      => $adminId,
            'type'         => $type,
            'title'        => $title,
            'content'      => $content,
            'is_read'      => 0,
        ], $extra));
    }

    /**
     * 发送邮件（best-effort）
     */
    private static function sendMail(int $adminId, string $title, string $content): void
    {
        try {
            $email = Db::name('admin')->where('id', $adminId)->value('email');
            if (!$email) {
                return;
            }
            $mail = new Email();
            if (!$mail->configured) {
                return;
            }
            $mail->isSMTP();
            $mail->addAddress($email);
            $mail->setSubject($title);
            $mail->isHTML(true);
            $mail->Body = nl2br(htmlspecialchars($content));
            $mail->send();
        } catch (Throwable $e) {
            Log::record('工作流邮件通知发送失败: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * 站内信 + 邮件
     */
    private static function notifyAndMail(int $adminId, string $type, string $title, string $content, array $extra = []): void
    {
        self::notify($adminId, $type, $title, $content, $extra);
        self::sendMail($adminId, $title, $content);
    }

    /**
     * 任务分配 → 通知待办人
     * payload: ['instance_id', 'node_name', 'approver_ids' => int[]]
     */
    public static function onTaskAssigned(array $data): void
    {
        $instance = Db::name('workflow_instance')->where('id', $data['instance_id'] ?? 0)->find();
        if (!$instance) {
            return;
        }
        $title   = $instance['title'] ?? '审批任务';
        $content = "您有一个新的审批任务：【{$data['node_name']}】，流程：{$title}";
        foreach (($data['approver_ids'] ?? []) as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            self::notifyAndMail($uid, 'task_assigned', '新的审批任务', $content, [
                'business_type' => $instance['business_type'] ?? '',
                'instance_id'  => $instance['id'],
            ]);
        }
    }

    /**
     * 流程通过 → 通知发起人
     */
    public static function onInstanceCompleted($instance): void
    {
        $title   = $instance->title ?? '审批流程';
        $content = "您的审批流程【{$title}】已通过";
        self::notifyAndMail((int)$instance->initiator_id, 'instance_completed', '审批通过', $content, [
            'business_type' => $instance->business_type ?? '',
            'instance_id'  => $instance->id,
        ]);
    }

    /**
     * 流程驳回 → 通知发起人
     */
    public static function onInstanceRejected($instance): void
    {
        $title   = $instance->title ?? '审批流程';
        $content = "您的审批流程【{$title}】已被驳回";
        self::notifyAndMail((int)$instance->initiator_id, 'instance_rejected', '审批驳回', $content, [
            'business_type' => $instance->business_type ?? '',
            'instance_id'  => $instance->id,
        ]);
    }

    /**
     * 流程撤回 → 通知发起人
     */
    public static function onInstanceCancelled($instance): void
    {
        $title   = $instance->title ?? '审批流程';
        $content = "您的审批流程【{$title}】已被发起人撤回";
        self::notifyAndMail((int)$instance->initiator_id, 'instance_cancelled', '审批撤回', $content, [
            'business_type' => $instance->business_type ?? '',
            'instance_id'  => $instance->id,
        ]);
    }

    /**
     * 抄送节点 → 通知抄送人
     * payload: ['instance_id', 'node_name', 'cc_ids' => int[], 'cc_names' => string]
     */
    public static function onTaskCc(array $data): void
    {
        $instance = Db::name('workflow_instance')->where('id', $data['instance_id'] ?? 0)->find();
        if (!$instance) {
            return;
        }
        $title   = $instance['title'] ?? '审批流程';
        $content = "您被抄送：【{$data['node_name']}】，流程：{$title}";
        foreach (($data['cc_ids'] ?? []) as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            self::notifyAndMail($uid, 'task_cc', '流程抄送', $content, [
                'business_type' => $instance['business_type'] ?? '',
                'instance_id'  => $instance['id'],
            ]);
        }
    }
}
