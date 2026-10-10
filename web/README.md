## 环境变量配置

在 `web/` 下配置 `VITE_API_BASE_URL`：PHP 源站（协议 + 主机 + 端口，或生产反代前缀）。**不含** `/admin`。接口路径自己带应用前缀，如 `admin/system.account.Profile/read`。未配置时请求走相对路径（[src/apis/request.ts](src/apis/request.ts)）。变量名必须以 `VITE_` 开头。

`php think run` 默认监听 `0.0.0.0:8000`，文档根是仓库的 `public/`。下面的地址是这个默认值的示例，不是仓库另开的端口：

```env
# web/.env.development
VITE_API_BASE_URL=http://127.0.0.1:8000
```

```env
# 生产反代示例（web/.env.production）
VITE_API_BASE_URL=/backend
```

安装向导在有请求域名时会生成 `web/.env.development`，`VITE_API_BASE_URL` 等于该域名（与根 `.env` 的 `APP_URL` 相同），不含 `/admin`。向导不改 `.env.production`；路径前缀仍由部署方填写，例如上面的 `/backend`。说明见根 [README.md](../README.md)。
