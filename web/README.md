## 环境变量配置

项目使用环境变量来配置 API 基础地址。请创建 `.env` 文件并配置以下变量：

```env
# API 基础地址
VITE_API_BASE_URL=http://localhost:8080
```

### 环境变量说明

- `VITE_API_BASE_URL`: API 请求的基础地址。如果未配置，将使用相对路径。
- 环境变量必须以 `VITE_` 开头才能在客户端代码中访问。
- 可以创建 `.env.development` 和 `.env.production` 文件来区分不同环境的配置。

### 示例

```env
# 开发环境 (.env.development)
VITE_API_BASE_URL=http://localhost:8080

# 生产环境 (.env.production)
VITE_API_BASE_URL=https://api.example.com
```
