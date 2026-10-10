# 生产部署（nginx）

同一台 nginx：文档根是仓库的 `public/`，页面走 Vue history，接口走 `/backend` 再进 ThinkPHP。

## 作用

把 `pnpm build` 的静态文件和 [public/index.php](../../public/index.php) 放在同一个文档根下。浏览器打开 `/` 得到前端；`VITE_API_BASE_URL=/backend` 时，接口请求形如 `/backend/admin/system.auth.Admin/list`，由 nginx 改写成 `index.php?s=...`。

## 前提

- PHP ≥ 8.3，扩展 `openssl`、`pdo_mysql`（[composer.json](../../composer.json)）。示例里的 php-fpm socket 写成 `php8.4`，只是一种安装结果，按机器改。
- 已 `composer install`。站点根必须是 `public/`，不是仓库根。
- 前端包管理器为 pnpm。[web/.env.production](../../web/.env.production) 里已有 `VITE_API_BASE_URL=/backend`。`vite build` 在构建时写进包，改前缀后要重新构建。
- [web/vite.config.ts](../../web/vite.config.ts) 没有 `build.outDir`、也没有 `base`。`pnpm build`（[web/package.json](../../web/package.json) 的 `vite build`）输出到 `web/dist/`，资源 URL 以 `/` 开头。不会写进 `public/`。
- 路由是 [createWebHistory()](../../web/src/router/index.ts)，刷新 `/login` 这类路径要由 nginx 回退到 `index.html`。
- [config/app.php](../../config/app.php) `with_route=false`。本仓库的 [config/route.php](../../config/route.php) 没有改 pathinfo 变量名。ThinkPHP 8 默认用查询参数 `s`（框架 `Request::$varPathinfo`）。下面的 `?s=` 对应该默认值。[public/.htaccess](../../public/.htaccess) 是另一条 Apache 写法（`index.php/$1`），nginx 示例不使用它。[public/router.php](../../public/router.php) 只给 `php think run` 用。

## 部署步骤

1. 在服务器上安装依赖并准备根目录 `.env`（安装向导或按 [.example.env](../../.example.env) 手写）。
2. 确认 [web/.env.production](../../web/.env.production) 的 `VITE_API_BASE_URL` 与 nginx 的 `/backend/` 一致，然后：

```bash
cd web && pnpm install && pnpm build
```

3. 把 `web/dist/` 里的文件拷进文档根，并保留已有的 PHP 入口和上传目录。`dist` 里有 `index.html`，没有 `index.php`。`favicon.ico` 会盖住 `public/favicon.ico`。

```bash
cp -a web/dist/. /var/www/glassadmin/public/
```

4. nginx 的 `root` 指到这个 `public/`（下面示例是 `/var/www/glassadmin/public`）。重载 nginx。

## nginx 示例

路径和 socket 换成自己的机器。`server_name _` 与 `default_server` 只适合单站点；有域名时改掉。

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    root /var/www/glassadmin/public;
    index index.html;

    location /backend/ {
        if (!-e $request_filename) {
            rewrite ^/backend(.*)$ /index.php?s=$1 last;
        }
    }

    location ~ ^/index\.php(/|$) {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;  # 按机器改
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }

    # Vue Router history：刷新深层路径时回到 index.html
    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

## 各 location 说明

`/backend/`：前端把站点源设成 `/backend`，后面再接 `admin/...`。磁盘上没有 `public/backend/` 这个文件，`if (!-e $request_filename)` 成立后，`rewrite` 把 `/backend` 后面的路径放进 `s`。例如 `/backend/admin/system.auth.Admin/list` 变成 `/index.php?s=/admin/system.auth.Admin/list`。`last` 会重新匹配 location，进入下面的 `index.php` 块。ThinkPHP 读到 `$_GET['s']` 后去掉前导斜杠，第一段 `admin` 是应用名。

`index.php`：只有这一处交给 php-fpm。`SCRIPT_FILENAME` 固定为文档根下的 `index.php`。`fastcgi_split_path_info` 留着是为了兼容 `index.php/...` 这种 PATH_INFO；本示例的改写走的是 `?s=`。

`/`：先找真实文件（`/assets/`、`/storage/`、`/favicon.ico`）。找不到则内部回退到 `/index.html`，否则刷新 `/login` 会 404。`index index.html` 让目录请求也进前端，而不是 `index.php`。

## 常见问题

**刷新页面 404。** history 模式依赖上面的 `try_files ... /index.html`。只配了 `/backend`、没配 `/` 的回退时，深层路由会 404。

**502 Bad Gateway。** `fastcgi_pass` 的 socket 与本机 php-fpm 不一致。看 `/run/php/` 里的实际文件名，改注释所在那一行。

**接口 404 或打到 `/admin/admin/...`。** 构建时写进包的 `VITE_API_BASE_URL` 必须和 location 前缀一致，且不含应用名 `/admin`。仓库里的生产示例是 `/backend`。改前缀后要重新 `pnpm build` 再拷贝 `web/dist/`。

**`/backend` 下打不开安装向导。** 向导页脚本写死请求 `/install/Index/`（[app/install/view/index/index.html](../../app/install/view/index/index.html)）。未安装时 [Index::index](../../app/index/controller/Index.php) `redirect('/install')`，路径也不带 `/backend`。本示例只把 `/backend/` 转给 PHP，`/install` 会落到前端的 `index.html`。可选做法：安装阶段让文档根仍是 `public/`，未命中的路径按 [public/.htaccess](../../public/.htaccess) 进 `index.php`，装完再换成这份配置。向导写的是 `web/.env.development`，不改 [web/.env.production](../../web/.env.production)。

**头像或上传文件 404。** 文件在 `public/storage`（[config/filesystem.php](../../config/filesystem.php) 的 `url` 是 `/storage`）。未设置 `VITE_API_BASE_URL` 时，[resolveStorageUrl](../../web/src/utils/storageUrl.ts) 拼的是 `/storage/...`，`location /` 能直接拿到文件。设成 `/backend` 之后，拼出来的是 `/backend/storage/...`。文档根下没有 `backend/storage/`，于是被改写进 `index.php`。可选：在 `/backend/` 之前加更具体的 location，把该前缀指回真实目录（路径按机器改）：

```nginx
location /backend/storage/ {
    alias /var/www/glassadmin/public/storage/;
}
```

## 可选加固

下面不是这份配置能跑起来的必要条件。

- `root` 保持为 `public/`。仓库根的 `.env` 因此不在文档根里。若把 `root` 指到仓库根，`.env` 会被静态访问。
- `public/router.php` 在 `location /` 下是普通文件，直接请求会把源码发下去。nginx 用不到它。可以 `location = /router.php { deny all; }`。
- 示例只把 `index.php` 交给 php-fpm。以后若往 `public/` 放了别的 `.php`，`try_files` 会按静态文件返回内容。
- 需要 HTTPS 或 `add_header` 安全响应头时，在这份 `server` 上另加，本仓库没有对应配置。
