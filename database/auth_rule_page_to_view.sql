-- 废弃前端规则 type=page（与 view 重复），统一为 view
UPDATE `auth_rule`
SET `type` = 'view',
    `update_time` = NOW()
WHERE `scope` = 'frontend'
  AND `type` = 'page';
