<?php
declare(strict_types=1);

namespace app\admin\model;

use think\facade\Db;
use think\Model;

/**
 * AdminOauthFeishu 管理员飞书 OAuth 绑定（表 admin_oauth_feishu）
 *
 * @property int         $id
 * @property int         $admin_id
 * @property string      $open_id
 * @property string      $union_id
 * @property string|null $name
 * @property string|null $avatar
 * @property string|null $access_token
 * @property int|null    $expires_in
 * @property string|null $refresh_token
 * @property int|null    $refresh_token_expires_in
 * @property string|null $scope
 * @property int|null    $create_time
 * @property int|null    $update_time
 */
class AdminOauthFeishu extends Model
{
    protected $name = 'admin_oauth_feishu';

    protected array|bool $autoWriteTimestamp = true;

    protected array $type = [
        'id'                       => 'integer',
        'admin_id'                 => 'integer',
        'expires_in'               => 'integer',
        'refresh_token_expires_in' => 'integer',
        'create_time'              => 'integer',
        'update_time'              => 'integer',
    ];

    /**
     * findOrCreate 按 union_id 查找绑定；不存在则创建管理员并绑定（不自动分配角色）
     *
     * @param array $userInfo 飞书 user_info.data
     * @param array $token 飞书 OAuth token 响应
     * @return self
     */
    public static function findOrCreate(array $userInfo, array $token): self
    {
        $unionId = trim((string) ($userInfo['union_id'] ?? ''));
        $openId = trim((string) ($userInfo['open_id'] ?? ''));
        if ($unionId === '' || $openId === '') {
            throw new \InvalidArgumentException('飞书用户标识缺失');
        }

        $tokenPayload = [
            'access_token'             => (string) ($token['access_token'] ?? ''),
            'expires_in'               => time() + (int) ($token['expires_in'] ?? 0),
            'refresh_token'            => (string) ($token['refresh_token'] ?? ''),
            'refresh_token_expires_in' => !empty($token['refresh_token_expires_in'])
                ? time() + (int) $token['refresh_token_expires_in']
                : null,
            'scope'                    => (string) ($token['scope'] ?? ''),
            'name'                     => (string) ($userInfo['name'] ?? ''),
            'avatar'                   => (string) ($userInfo['avatar_middle'] ?? $userInfo['avatar_url'] ?? ''),
        ];

        /** @var self|null $bind */
        $bind = self::where('union_id', $unionId)->find();
        if ($bind) {
            $bind->save($tokenPayload);

            return $bind;
        }

        $email = (string) ($userInfo['enterprise_email'] ?? $userInfo['email'] ?? '');
        $username = $email !== '' ? explode('@', $email)[0] : ('fs_' . substr(md5($unionId), 0, 12));
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username) ?: ('fs_' . substr(md5($unionId), 0, 12));
        if (Admin::where('username', $username)->find()) {
            $username .= '_' . substr(md5($openId), 0, 6);
        }

        Db::startTrans();
        try {
            $admin = Admin::create([
                'username' => $username,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
                'nickname' => (string) ($userInfo['name'] ?? $username),
                'avatar'   => ($tokenPayload['avatar'] !== '' && strlen($tokenPayload['avatar']) <= 255)
                    ? $tokenPayload['avatar']
                    : null,
                'status'   => 1,
            ]);
            $adminId = (int) $admin->getAttr('id');

            $bind = self::create(array_merge($tokenPayload, [
                'admin_id' => $adminId,
                'open_id'  => $openId,
                'union_id' => $unionId,
            ]));
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }

        return $bind;
    }
}
