# JSON 信封

后端统一响应 `{code,msg,data}`。

## 作用

`ApiResponse` 生成 JSON。`BaseController` 的 `ok` / `fail` / `paginate` / `rsaKeyInvalid` 委托该类。前端默认解包见 [request.md](../frontend/request.md)。

## 关键路径

| 路径 | 作用 |
|---|---|
| [app/common/ApiResponse.php](../../app/common/ApiResponse.php) | `success` / `fail` / `paginate` 及 HTTP 语义方法 |
| [app/BaseController.php](../../app/BaseController.php) | 控制器 `ok` / `fail` / `paginate` / `rsaKeyInvalid` |
| [app/common/controller/AdminController.php](../../app/common/controller/AdminController.php) | 覆盖 `ok` / `paginate`，成功时写操作日志 |
| [web/src/apis/envelope.ts](../../web/src/apis/envelope.ts) | `ApiEnvelope`、`unwrap`（`code === 0`）、`ApiError` |

## 约定

成功：`code === 0`，`msg` 默认 `'ok'`。失败：非 0 业务码；默认 HTTP 200，传入 `$httpStatus` 时使用该状态码。

分页 `success` 的 `data`：

```json
{ "list": [], "total": 0, "page": 1, "limit": 20 }
```

| 方法 | 业务码 / HTTP |
|---|---|
| `success` / `ok` | `code=0` |
| `fail` | 指定 `$code`；HTTP 默认 200 |
| `unauthorized` | 401 / HTTP 401 |
| `forbidden` | 403 / HTTP 403 |
| `notFound` | 404 / HTTP 404 |
| `badRequest` | 400 |
| `methodNotAllowed` | 405 |
| `tooManyRequests` | 429 / HTTP 429 |
| `serverError` | 500 |
| `rsaKeyInvalid` | 40001（前端重放加密请求） |

控制器：

```php
return $this->ok($data);
return $this->fail(400, 'id 无效');
return $this->paginate($items, $total, $page, $limit);
```

示例：[app/admin/controller/system/auth/Admin.php](../../app/admin/controller/system/auth/Admin.php) 的 `list()` / `read()`。

## 逃生舱

不使用 `ok()` / `fail()` 时直接 `json()`：

```php
return json(['code' => 0, 'msg' => 'ok', 'data' => $payload]);
```

[app/index/controller/Index.php](../../app/index/controller/Index.php) 未继承 `BaseController`。前端不解包：`skipUnwrap` 或原生 `fetch`，见 [escape-hatches.md](../escape-hatches.md)。
