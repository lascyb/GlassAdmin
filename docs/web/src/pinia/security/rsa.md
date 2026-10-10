# useSecurityRsa

源码：[web/src/pinia/security/rsa.ts](../../../../../web/src/pinia/security/rsa.ts)

## 作用

拉并缓存 RSA 公钥（`GET admin/system.Security/rsaPublicKey`），按 serial 加密明文。优先 Web Crypto RSA-OAEP(SHA-1)；非安全上下文降级 `node-forge`。距过期 600s 内视为过期（与后端 `RsaKeyService` 的 `SKIP_SECONDS` 对齐）。

[request.ts](../../apis/request.md) 的 `encryptFields` 走本 store；[ga-password-input](../../components/ga-password-input/index.md) 的 `getEncryptedPayload` 也是。

## 推荐使用场景

- 登录/改密等需 RSA 的字段：优先 `post(..., { encryptFields })`。
- 业务码 `40001`：请求层会 `forceRefreshPublicKey` 后重放。
- 不要在页面里复制 PEM 解析。

## 要点

`encrypt()` 返回 `{ serial, texts[] }`；空串不加密。公钥接口见 [Security.md](../../../../app/admin/controller/system/Security.md)。

## 已使用场景

- [web/src/apis/request.ts](../../../../../web/src/apis/request.ts)
- [web/src/components/ga-password-input/index.vue](../../../../../web/src/components/ga-password-input/index.vue)
- [web/src/views/login.vue](../../../../../web/src/views/login.vue)（`encryptFields`）

## 逃生舱

字段不加密（仅限内网调试），或页面自己 `get` 公钥再加密。见 [escape-hatches.md](../../../../escape-hatches.md)。
