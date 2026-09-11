# ApiResponse

源码：[app/common/ApiResponse.php](../../../app/common/ApiResponse.php)

## 作用

统一 JSON `{code,msg,data}`。[BaseController](../BaseController.md) 的 `ok` / `fail` / `paginate` / `rsaKeyInvalid` 委托本类。[AdminController](controller/AdminController.md) 覆盖 `ok` / `paginate`，成功时写操作日志。前端解包：[envelope.ts](../../web/src/apis/envelope.md)、[request.ts](../../web/src/apis/request.md)。

## 约定

成功：`code === 0`，`msg` 默认 `'ok'`。失败：非 0 业务码；HTTP 默认 200，传入 `$httpStatus` 时使用该状态码。

分页 `data`：`{ list, total, page, limit }`。

| 方法 | 业务码 / HTTP |
|---|---|
| `success` / 控制器 `ok` | `code=0` |
| `fail` | 指定 `$code`；HTTP 默认 200 |
| `unauthorized` | 401 / HTTP 401 |
| `forbidden` | 403 / HTTP 403 |
| `notFound` | 404 / HTTP 404 |
| `badRequest` | 400 |
| `methodNotAllowed` | 405 |
| `tooManyRequests` | 429 / HTTP 429 |
| `serverError` | 500 |
| `rsaKeyInvalid` | 40001（前端重放加密请求） |

```php
return $this->ok($data);
return $this->fail(400, 'id 无效');
return $this->paginate($items, $total, $page, $limit);
```

示例：[app/admin/controller/system/auth/Admin.php](../../../app/admin/controller/system/auth/Admin.php)。

## 逃生舱

```php
return json(['code' => 0, 'msg' => 'ok', 'data' => $payload]);
```

[app/index/controller/Index.php](../../../app/index/controller/Index.php) 未继承 `BaseController`。见 [escape-hatches.md](../../escape-hatches.md)。
