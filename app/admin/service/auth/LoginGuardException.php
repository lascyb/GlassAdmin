<?php
declare(strict_types=1);

namespace app\admin\service\auth;

use RuntimeException;

/**
 * LoginGuardException 登录冷却拦截（account_locked / ip_locked）
 */
final class LoginGuardException extends RuntimeException
{
    /**
     * @param string $reason        account_locked | ip_locked
     * @param int    $remainSeconds 剩余冷却秒数
     */
    public function __construct(
        public readonly string $reason,
        public readonly int $remainSeconds,
    ) {
        parent::__construct($this->buildMessage());
    }

    /**
     * buildMessage 人类可读提示（含剩余等待时间）
     */
    private function buildMessage(): string
    {
        $sec = max(0, $this->remainSeconds);
        if ($sec >= 60) {
            $min = (int) ceil($sec / 60);

            return "尝试次数过多，请 {$min} 分钟后再试";
        }

        return "尝试次数过多，请 {$sec} 秒后再试";
    }
}
