## 环境变量配置

项目使用环境变量来配置站点入口。请创建 `.env` 文件并配置以下变量：

```env
# 站点入口（不含多应用名 /admin；管理端接口路径自行带 admin/）
VITE_API_BASE_URL=http://localhost:8080
```

### 环境变量说明

- `VITE_API_BASE_URL`: 站点入口（协议+主机，或生产反代前缀如 `/backend`）。**不含** ThinkPHP 应用名 `/admin`。未配置时使用相对路径。
- 管理端接口调用串需带应用前缀，如 `admin/system.account.Profile/read`。
- 环境变量必须以 `VITE_` 开头才能在客户端代码中访问。
- 可以创建 `.env.development` 和 `.env.production` 文件来区分不同环境的配置。

### 示例

```env
# 开发环境 (.env.development)
VITE_API_BASE_URL=http://localhost:8080

# 生产环境 (.env.production)
VITE_API_BASE_URL=/backend
```
