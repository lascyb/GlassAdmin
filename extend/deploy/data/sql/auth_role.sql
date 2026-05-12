-- 角色表；pid 仅用于界面树形组织，不表示权限继承
-- status：{1:启用,0:禁用}

CREATE TABLE IF NOT EXISTS `auth_role` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `pid` bigint unsigned NOT NULL DEFAULT 0 COMMENT '父级 id，0 为根（仅展示，无权限继承）',
  `name` varchar(100) NOT NULL COMMENT '角色名',
  `desc` varchar(255) DEFAULT NULL COMMENT '描述',
  `sort` int NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '{1:启用,0:禁用}',
  `create_time` datetime DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='权限角色';

INSERT INTO `auth_role` (`id`,`pid`,`name`,`desc`,`sort`,`status`) VALUES
(1,0,'超级管理员','拥有全部菜单（id=1 特例：未勾选也视为全量）',0,1);

ALTER TABLE `auth_role` AUTO_INCREMENT=10;
