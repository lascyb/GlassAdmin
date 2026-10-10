# envelope.ts

源码：[web/src/apis/envelope.ts](../../../../web/src/apis/envelope.ts)

与 [request.ts](request.md) 配套：解析后端 `{code,msg,data}`。

## 作用

| 导出 | 作用 |
|---|---|
| `ApiEnvelope<T>` | `{ code: number; msg: string; data: T }` |
| `unwrap(res)` | `code === 0` 返回 `data`，否则抛 `ApiError` |
| `ApiError` | `code` / `msg` / `data` / `shown`（请求层已 `ElMessage` 则为 true） |

后端：[ApiResponse.php](../../../app/common/ApiResponse.md)。

## 推荐使用场景

- 自写 `fetch` / 非 axios 响应后，要把 `{code,msg,data}` 解成 `data`。
- 业务页走 `get`/`post` 时不必直接 import：拦截器已 `unwrap`。
- 只要判断 `code === 0`：自行分支，不必用本模块。

## 最小示例

```ts
import { unwrap, type ApiEnvelope } from '@/apis/envelope'

const json = (await res.json()) as ApiEnvelope<{ list: unknown[] }>
const data = unwrap(json)
```

`get`/`post` 默认已调用 `unwrap`，业务页一般不必再调。

## 已使用场景

- [web/src/apis/request.ts](../../../../web/src/apis/request.ts)（import 并 re-export）
- [web/src/pinia/admin/token.ts](../../../../web/src/pinia/admin/token.ts)（`fetch` 续期后 `unwrap`）

## 逃生舱

自行判断 `code === 0`。见 [escape-hatches.md](../../../escape-hatches.md)。
