<?php

declare(strict_types=1);

namespace app\install\controller;

use app\BaseController;
use app\install\service\Installer;
use app\common\middleware\ApiThrottle;
use phinx\InstallState;
use think\exception\HttpResponseException;
use think\Response;

/**
 * Index 安装向导页面与 JSON 接口
 */
class Index extends BaseController
{
    protected function initialize(): void
    {
        parent::initialize();
        if ($this->request->isOptions()) {
            return;
        }
        InstallState::ensureLockIfAlreadyProvisioned();
        $throttle = ApiThrottle::enforce($this->request);
        if ($throttle instanceof Response) {
            throw new HttpResponseException($throttle);
        }
        $action = $this->request->action();
        if (InstallState::isInstalled() && in_array($action, ['testConnection', 'run'], true)) {
            throw new HttpResponseException($this->fail(403, '系统已安装'));
        }
    }

    /**
     * index 向导页
     */
    public function index(): mixed
    {
        if (!$this->request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $login = rtrim((string) env('APP_URL', ''), '/');
        $loginUrl = $login === '' ? '/login' : $login . '/login';

        return view('index/index', [
            'installed' => InstallState::isInstalled(),
            'login_url' => $loginUrl,
        ]);
    }

    /**
     * envCheck GET 环境检测
     */
    public function envCheck(): Response
    {
        if (!$this->request->isGet()) {
            return $this->fail(405, '仅支持 GET');
        }
        $items = (new Installer())->envCheck();
        $ok = true;
        foreach ($items as $item) {
            if (!$item['ok']) {
                $ok = false;
                break;
            }
        }

        return $this->ok([
            'ok'        => $ok,
            'items'     => $items,
            'installed' => InstallState::isInstalled(),
        ]);
    }

    /**
     * testConnection POST 测试数据库
     */
    public function testConnection(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        try {
            $result = (new Installer())->testConnection($this->dbPayload());
        } catch (\InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->fail(400, $e->getMessage());
        }

        if ($result['occupied']) {
            return $this->fail(400, '目标数据库已有数据表，请使用空库或清空后再安装', $result);
        }
        $msg = $result['exists'] ? '连接成功' : '账号连接成功，数据库不存在，开始安装时将自动创建';

        return $this->ok($result, $msg);
    }

    /**
     * run POST 执行安装
     */
    public function run(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        $body = $this->payload();
        try {
            $result = (new Installer())->run(
                $this->dbPayload(),
                (string) ($body['admin_username'] ?? ''),
                (string) ($body['admin_password'] ?? ''),
                (string) ($body['admin_nickname'] ?? ''),
                (string) ($body['site_title'] ?? 'GlassAdmin'),
                (string) ($body['site_description'] ?? '透明可视的后台框架'),
                rtrim((string) $this->request->domain(), '/'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->fail(400, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->fail(500, $e->getMessage());
        }

        return $this->ok($result, '安装成功');
    }

    /**
     * remove POST 删除 app/install
     */
    public function remove(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }
        try {
            (new Installer())->removeInstallApp();
        } catch (\RuntimeException $e) {
            return $this->fail(500, $e->getMessage());
        }

        return $this->ok(['removed' => true], '安装程序已删除');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $raw = (string) $this->request->getContent();
        if ($raw !== '') {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        /** @var array<string, mixed> $post */
        $post = $this->request->post();

        return is_array($post) ? $post : [];
    }

    /**
     * @return array{hostname:string,hostport:string,database:string,username:string,password:string,prefix:string}
     */
    private function dbPayload(): array
    {
        $body = $this->payload();

        return [
            'hostname' => (string) ($body['hostname'] ?? ''),
            'hostport' => (string) ($body['hostport'] ?? '3306'),
            'database' => (string) ($body['database'] ?? ''),
            'username' => (string) ($body['username'] ?? ''),
            'password' => (string) ($body['password'] ?? ''),
            'prefix'   => (string) ($body['prefix'] ?? ''),
        ];
    }
}
