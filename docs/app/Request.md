# Request（应用请求）

源码：[app/Request.php](../../app/Request.php)

## 作用

扩展 ThinkPHP `Request`：从 `.env` 的 `PROXY_SERVER_IP`（逗号分隔，支持 CIDR）填充可信代理列表。仅当 `REMOTE_ADDR` 属于可信代理时，`$request->ip()` 才采用 `X-Real-IP` / `X-Forwarded-For`。

浏览器直连 nginx→php-fpm 时通常不必配置。见 [deploy/nginx.md](../deploy/nginx.md)。

## 已使用场景

全站 `$request->ip()`（含登录限流与操作日志）。

## 逃生舱

不设 `PROXY_SERVER_IP` 则行为与框架默认一致（`REMOTE_ADDR`）。
