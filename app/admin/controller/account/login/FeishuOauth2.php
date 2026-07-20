<?php
declare(strict_types=1);

namespace app\admin\controller\account\login;

use app\admin\model\Admin;
use app\admin\model\AdminOauthFeishu;
use feishu\Client as FeishuClient;
use feishu\FeishuException;
use feishu\OAuth2;
use feishu\open_apis\authen\v1\user_info\UserInfoGetRequest;
use Random\RandomException;
use think\facade\Config;
use think\Response;

/**
 * FeishuOauth2 飞书 OAuth2 授权登录
 */
class FeishuOauth2 extends BaseLogin
{
    /**
     * authorize 跳转飞书授权页
     * @return Response
     * @throws RandomException
     */
    public function authorize(): Response
    {
        try {
            $oauth = $this->makeOAuth2();
        } catch (\InvalidArgumentException $e) {
            return $this->fail(500, $e->getMessage());
        }

        $scopes = [
            'contact:user.employee_id:readonly',
            'contact:contact.base:readonly',
            'contact:user.base:readonly',
        ];
        $state = bin2hex(random_bytes(8));

        return redirect($oauth->authorize($scopes, $state));
    }

    /**
     * login 飞书授权码登录
     * @return Response
     */
    public function login(): Response
    {
        if (!$this->request->isPost()) {
            return $this->fail(405, '仅支持 POST');
        }

        $code = trim((string) $this->request->post('code', ''));
        $remember = (bool) $this->request->post('remember', false);
        if ($code === '') {
            return $this->fail(400, '缺少授权码');
        }

        try {
            $oauth = $this->makeOAuth2();
            $token = $oauth->getAccessToken($code);
        } catch (FeishuException $e) {
            return $this->fail(401, '飞书授权失败：' . $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->fail(500, $e->getMessage());
        }

        if ((int) ($token['code'] ?? -1) !== 0 || empty($token['access_token'])) {
            return $this->fail(401, '用户授权失效，请重试');
        }

        $appId = (string) Config::get('feishu.app_id', '');
        $appSecret = (string) Config::get('feishu.app_secret', '');
        try {
            $client = new FeishuClient($appId, $appSecret);
            $userResponse = $client->requestWithUserAccessToken(
                new UserInfoGetRequest(),
                (string) $token['access_token'],
            );
            $userInfo = $userResponse->data();
        } catch (FeishuException $e) {
            return $this->fail(401, '获取飞书用户信息失败：' . $e->getMessage());
        }

        if (!is_array($userInfo) || empty($userInfo['union_id'])) {
            return $this->fail(401, '飞书用户信息无效');
        }

        try {
            $bind = AdminOauthFeishu::findOrCreate($userInfo, $token);
        } catch (\Throwable $e) {
            return $this->fail(500, '用户绑定失败：' . $e->getMessage());
        }

        /** @var Admin|null $user */
        $user = Admin::find((int) $bind->getAttr('admin_id'));
        if (!$user || (int) $user->getAttr('status') !== 1) {
            return $this->fail(403, '账号不存在或已禁用');
        }

        return $this->issueLoginOk(
            $user,
            $remember,
            'auth.login.feishu',
            '登录成功（飞书）' . ($remember ? '，记住我' : ''),
        );
    }

    /**
     * makeOAuth2 创建飞书 OAuth2 客户端
     * @return OAuth2
     */
    private function makeOAuth2(): OAuth2
    {
        $appId = trim((string) Config::get('feishu.app_id', ''));
        $appSecret = trim((string) Config::get('feishu.app_secret', ''));
        $redirectUri = trim((string) Config::get('feishu.redirect_uri', ''));
        if ($appId === '' || $appSecret === '' || $redirectUri === '') {
            throw new \InvalidArgumentException('飞书登录未配置（请在 .env [FEISHU] 填写 APP_ID / APP_SECRET / REDIRECT_URI）');
        }

        return new OAuth2($appId, $appSecret, $redirectUri);
    }
}
