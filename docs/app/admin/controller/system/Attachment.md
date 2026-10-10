# Attachment

源码：[app/admin/controller/system/Attachment.php](../../../../../app/admin/controller/system/Attachment.php)  
前端 API：[web/src/apis/system/attachment.ts](../../../../../web/src/apis/system/attachment.ts)

## 作用

系统附件：**列表 / 详情 / 删除**（库记录 + 磁盘）。注释写明上传另议。继承 [AdminController](../../../common/controller/AdminController.md)。

## 推荐使用场景

- 管理端查看/清理已落地的附件。
- 头像等上传走业务接口（如 Profile），不要指望本控制器提供 upload action。

## 要点

| 方法 | HTTP | 说明 |
|---|---|---|
| `list` | GET | `page`/`limit`/`keyword`/`scene`/`mime`/`admin_id`；`paginate` |
| `read` | GET | `id` |
| `delete` | DELETE | `AttachmentModel::deleteById` |

## 已使用场景

- [web/src/views/system/attachment/index.vue](../../../../../web/src/views/system/attachment/index.vue)

## 逃生舱

直接操作存储与表，或业务控制器自己存文件。见 [escape-hatches.md](../../../../escape-hatches.md)。
