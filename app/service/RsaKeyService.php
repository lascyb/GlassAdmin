<?php
declare (strict_types = 1);

namespace app\service;

use app\model\RsaKey;
use Cassandra\Uuid;
use Throwable;

class RsaKeyService
{
    private int $defaultExpireSeconds = 3600;
    private int $defaultSkipSeconds = 600;

    public function getAvailablePublicKey(): ?array
    {
        $now = time();
        $key = RsaKey::where('status', 1)
            ->where('expire_time', '>', $now+$this->defaultSkipSeconds)
            ->order('expire_time', 'asc')
            ->find();

        if ($key === null) {
            $key = $this->createOne($this->defaultExpireSeconds);
        }

        if ($key === null) {
            return null;
        }

        return [
            'serial'      => (string) $key->getAttr('serial'),
            'public_key'  => (string) $key->getAttr('public_key'),
            'expire_time' => (int) $key->getAttr('expire_time'),
        ];
    }

    public function decryptBySerial(string $serial, string $cipherText): ?string
    {
        $now = time();
        $key = RsaKey::where('serial', $serial)
            ->where('status', 1)
            ->where('expire_time', '>', $now-$this->defaultSkipSeconds)
            ->find();
        if ($key === null) {
            return null;
        }

        $privateKey = (string) $key->getAttr('private_key');
        $cipherRaw = base64_decode($cipherText, true);
        if ($cipherRaw === false) {
            return null;
        }

        $plainText = '';
        $ok = openssl_private_decrypt($cipherRaw, $plainText, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            return null;
        }

        return $plainText;
    }

    public function generateDailyKeys(int $count, int $expireSeconds): int
    {
        $generated = 0;
        for ($i = 0; $i < $count; $i++) {
            if ($this->createOne($expireSeconds) !== null) {
                $generated++;
            }
        }
        return $generated;
    }

    private function createOne(int $expireSeconds): ?RsaKey
    {
        try {
            $config = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ];
            $resource = openssl_pkey_new($config);
            if ($resource === false) {
                return null;
            }

            $privateKey = '';
            if (!openssl_pkey_export($resource, $privateKey)) {
                return null;
            }
            $publicInfo = openssl_pkey_get_details($resource);
            if (!is_array($publicInfo) || !isset($publicInfo['key'])) {
                return null;
            }

            $key = new RsaKey();
            $key->save([
                'serial'      => uniqid(),
                'public_key'  => $publicInfo['key'],
                'private_key' => $privateKey,
                'expire_time' => time() + $expireSeconds,
                'status'      => 1,
            ]);

            return $key;
        } catch (Throwable $e) {
            dump($e->getMessage());
            return null;
        }
    }
}
