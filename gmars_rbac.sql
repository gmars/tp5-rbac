SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `###permission`;
CREATE TABLE `###permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '权限节点名称',
  `path` varchar(100) NOT NULL DEFAULT '' COMMENT '权限路径',
  `path_id` varchar(100) NOT NULL DEFAULT '' COMMENT '路径唯一编码',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '描述信息',
  `status` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态0未启用1正常',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_permission` (`path_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限节点';
DROP TABLE IF EXISTS `###role`;
CREATE TABLE `###role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '角色名',
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '父级id（一般不建议角色的继承）',
  `description` varchar(200) NOT NULL DEFAULT '' COMMENT '角色描述',
  `status` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态1正常0未启用',
  `sort_num` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '排序值',
  `left_key` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '左值',
  `right_key` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '右值',
  `level` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '层级',
  PRIMARY KEY (`id`),
  KEY `idx_role` (`status`,`left_key`,`right_key`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色';
DROP TABLE IF EXISTS `###role_permission`;
CREATE TABLE `###role_permission` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色编号',
  `permission_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '权限编号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限对应表';
DROP TABLE IF EXISTS `###user`;
CREATE TABLE `###user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(64) NOT NULL DEFAULT '' COMMENT '用户密码',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号码',
  `last_login_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后一次登录时间',
  `status` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '状态0禁用1正常',
  `create_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '账号创建时间',
  `update_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '信息更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_name`,`mobile`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';
DROP TABLE IF EXISTS `###user_role`;
CREATE TABLE `###user_role` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '用户id',
  `role_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '角色id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户角色对应关系'