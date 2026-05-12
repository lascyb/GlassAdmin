-- 权限规则表（若在 .env 中配置了 DB_PREFIX，建表时请为表名加上相同前缀）
-- scope：{backend:后端,frontend:前端}
-- backend.type：{menu:目录,api:接口}
-- frontend.type：{menu:目录,view:页面,btn:按钮}
-- status：{1:启用,0:禁用}

CREATE TABLE IF NOT EXISTS `auth_rule` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `scope` varchar(16) NOT NULL COMMENT '{backend:后端,frontend:前端}',
  `type` varchar(16) NOT NULL COMMENT 'backend:{menu,api} frontend:{menu,view,btn}',
  `pid` bigint unsigned NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根',
  `name` varchar(100) NOT NULL COMMENT '名称',
  `path` varchar(255) DEFAULT NULL COMMENT '后端 api 路径或前端 path 片段',
  `method` varchar(16) DEFAULT NULL COMMENT 'HTTP 方法，api 用，如 GET POST *',
  `component` varchar(255) DEFAULT NULL COMMENT '前端组件路径，view 用',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标：{ep:ElementPlus图标组件名,url:图片绝对或相对地址}',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_scope_pid` (`scope`,`pid`),
  KEY `idx_scope_status` (`scope`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限规则（后端/前端双域树）';

-- 初始化后端接口规则（path 不含 api 前缀，与路由组内路径一致）
-- 根下分二级 menu：权限规则、权限角色、菜单与导航；api id 2–15 不变，便于与 auth_rule_frontend_api 等种子对齐
INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`sort`,`status`) VALUES
(1,'backend','menu',0,'后端接口权限',NULL,NULL,NULL,0,1),
(16,'backend','menu',1,'权限规则',NULL,NULL,NULL,1,1),
(17,'backend','menu',1,'权限角色',NULL,NULL,NULL,2,1),
(18,'backend','menu',1,'菜单与导航',NULL,NULL,NULL,3,1),
(2,'backend','api',16,'规则树','auth/rule/tree','GET',NULL,0,1),
(3,'backend','api',16,'规则详情','auth/rule/read','GET',NULL,1,1),
(4,'backend','api',16,'新增规则','auth/rule/save','POST',NULL,2,1),
(5,'backend','api',16,'更新规则','auth/rule/update','PUT',NULL,3,1),
(6,'backend','api',16,'删除规则','auth/rule/delete','DELETE',NULL,4,1),
(7,'backend','api',16,'绑定后端接口','auth/rule/bindApis','POST',NULL,5,1),
(8,'backend','api',16,'可选后端API列表','auth/rule/apiList','GET',NULL,6,1),
(9,'backend','api',17,'角色树','auth/role/tree','GET',NULL,0,1),
(10,'backend','api',17,'角色详情','auth/role/read','GET',NULL,1,1),
(11,'backend','api',17,'新增角色','auth/role/save','POST',NULL,2,1),
(12,'backend','api',17,'更新角色','auth/role/update','PUT',NULL,3,1),
(13,'backend','api',17,'删除角色','auth/role/delete','DELETE',NULL,4,1),
(14,'backend','api',17,'分配前端规则','auth/role/assignRules','PUT',NULL,5,1),
(15,'backend','api',18,'动态菜单','menu/menus','GET',NULL,0,1);

-- 初始化前端菜单树（与默认侧栏结构接近）
INSERT INTO `auth_rule` (`id`,`scope`,`type`,`pid`,`name`,`path`,`method`,`component`,`sort`,`status`) VALUES
(100,'frontend','view',0,'仪表盘','dashboard',NULL,'dashboard',0,1),
(102,'frontend','menu',0,'权限管理','auth',NULL,NULL,10,1),
(103,'frontend','view',102,'角色管理','role',NULL,'auth/role/index.vue',0,1),
(104,'frontend','btn',103,'添加','add',NULL,NULL,0,1),
(105,'frontend','btn',103,'编辑','edit',NULL,NULL,1,1),
(106,'frontend','btn',103,'删除','del',NULL,NULL,2,1),
(107,'frontend','view',102,'规则管理','rule',NULL,'auth/rule/index.vue',1,1);

ALTER TABLE `auth_rule` AUTO_INCREMENT=200;
