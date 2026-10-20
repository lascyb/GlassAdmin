<?php

namespace app;

/**
 * 应用请求对象：可选信任反向代理以解析真实客户端 IP
 *
 * 配置 .env 的 PROXY_SERVER_IP（逗号分隔，支持 CIDR，如 127.0.0.1,10.0.0.0/8）。
 * 仅当 REMOTE_ADDR 属于可信代理时，才采用 X-Real-IP / X-Forwarded-For。
 */
class Request extends \think\Request
{
    /** 是否已从环境变量加载代理配置 */
    private bool $proxyConfigLoaded = false;

    /**
     * ip 返回客户端 IP；先加载可信代理列表再委托父类
     */
    public function ip(): string
    {
        $this->loadProxyServerIpFromEnv();

        return parent::ip();
    }

    /**
     * loadProxyServerIpFromEnv 从 PROXY_SERVER_IP 填充 $proxyServerIp（只执行一次）
     */
    private function loadProxyServerIpFromEnv(): void
    {
        if ($this->proxyConfigLoaded) {
            return;
        }
        $this->proxyConfigLoaded = true;

        $raw = trim((string) env('PROXY_SERVER_IP', ''));
        if ($raw === '') {
            return;
        }

        $list = [];
        foreach (explode(',', $raw) as $part) {
            $ip = trim($part);
            if ($ip !== '') {
                $list[] = $ip;
            }
        }
        if ($list !== []) {
            $this->proxyServerIp = $list;
        }
    }
}
