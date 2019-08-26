/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50709
Source Host           : localhost:3306
Source Database       : oms

Target Server Type    : MYSQL
Target Server Version : 50709
File Encoding         : 65001

Date: 2017-06-16 10:40:46
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for pr_admin_access
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_access`;
CREATE TABLE `pr_admin_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `userid` int(11) DEFAULT '0' COMMENT '所属用户权限ID',
  `groupid` int(11) DEFAULT '0' COMMENT '所属群组权限ID',
  `menuid` int(11) NOT NULL DEFAULT '0' COMMENT '权限模块ID',
  PRIMARY KEY (`id`),
  KEY `idx_userid` (`userid`) USING BTREE,
  KEY `idx_groupid` (`groupid`) USING BTREE,
  KEY `idx_menuid` (`menuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户或者用户组权限记录';

-- ----------------------------
-- Records of pr_admin_access
-- ----------------------------

-- ----------------------------
-- Table structure for pr_admin_actionlog
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_actionlog`;
CREATE TABLE `pr_admin_actionlog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `action` varchar(500) DEFAULT NULL COMMENT '操作演示',
  `ctime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for pr_admin_app
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_app`;
CREATE TABLE `pr_admin_app` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '应用名',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of pr_admin_app
-- ----------------------------
INSERT INTO `pr_admin_app` VALUES ('1', '系统维护');
INSERT INTO `pr_admin_app` VALUES ('2', ' 演示');

-- ----------------------------
-- Table structure for pr_admin_groups
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_groups`;
CREATE TABLE `pr_admin_groups` (
  `id` smallint(3) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '1正常，0删除',
  `remark` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of pr_admin_groups
-- ----------------------------
INSERT INTO `pr_admin_groups` VALUES ('1', '管理员', '1', '');
INSERT INTO `pr_admin_groups` VALUES ('2', '运营', '1', '');
INSERT INTO `pr_admin_groups` VALUES ('3', '测试用户组', '1', '测试用户组备注');

-- ----------------------------
-- Table structure for pr_admin_loginlog
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_loginlog`;
CREATE TABLE `pr_admin_loginlog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `nickname` varchar(50) DEFAULT NULL COMMENT '操作的url',
  `ip` char(15) NOT NULL,
  `ctime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `ctime` (`ctime`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for pr_admin_menus
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_menus`;
CREATE TABLE `pr_admin_menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '父模块ID编号 0则为顶级模块',
  `title` varchar(64) NOT NULL COMMENT '标题',
  `url` varchar(64) NOT NULL COMMENT 'url路径',
  `params` varchar(64) NOT NULL DEFAULT '' COMMENT 'url参数',
  `isshow` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否显示',
  `sort` smallint(3) unsigned NOT NULL DEFAULT '0' COMMENT '排序倒序',
  `app` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '菜单所属app，对应app表中的主键',
  PRIMARY KEY (`id`),
  KEY `idex_pid` (`pid`) USING BTREE,
  KEY `idex_order` (`sort`) USING BTREE,
  KEY `idx_action` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COMMENT='权限模块信息表';

-- ----------------------------
-- Records of pr_admin_menus
-- ----------------------------
INSERT INTO `pr_admin_menus` VALUES ('1', '0', '权限管理', 'acl', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('2', '1', '用户管理', 'adminbase/Acl/Users/index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('3', '1', '菜单管理', 'adminbase/Acl/Menus/menusList', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('4', '1', '授权', 'adminbase/Acl/Acl/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('5', '2', '用户增加', 'adminbase/Acl/Users/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('6', '3', '添加菜单', 'adminbase/Acl/Menus/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('7', '0', '系统管理', 'adminbase/System/Index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('8', '3', '编辑菜单', 'adminbase/Acl/Menus/edit', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('9', '2', '编辑用户', 'adminbase/Acl/Users/edit', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('10', '7', '系统日志', 'adminbase/System/SystemLog/index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('11', '3', '删除菜单', 'adminbase/Acl/Menus/del', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('12', '2', '删除用户', 'adminbase/Acl/Users/del', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('13', '1', '用户组管理', 'adminbase/Acl/Groups/index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('14', '13', '用户组添加', 'adminbase/Acl/Groups/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('15', '13', '用户组编辑', 'adminbase/Acl/Groups/edit', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('16', '13', '用户组删除', 'adminbase/Acl/Groups/del', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('17', '4', '用户授权', 'adminbase/Acl/Acl/user', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('18', '4', '用户组授权', 'adminbase/Acl/Acl/group', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('19', '7', '登录日志', 'adminbase/System/LoginLog/index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('20', '7', '后台首页', 'adminbase/System/Index/index', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('21', '7', '重要操作日志', 'adminbase/System/ActionLog/index', '', '1', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('22', '0', '演示自定义模块', 'cusadmin/demo', '', '1', '0', '2');
INSERT INTO `pr_admin_menus` VALUES ('23', '22', '点我', 'cusadmin/demo/Index/index', '', '1', '0', '2');
INSERT INTO `pr_admin_menus` VALUES ('25', '1', '应用管理', 'adminbase/Acl/App/index', '', '1', '10', '1');
INSERT INTO `pr_admin_menus` VALUES ('26', '25', '增加应用', 'adminbase/Acl/App/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('27', '25', '编辑应用', 'adminbase/Acl/App/add', '', '0', '0', '1');
INSERT INTO `pr_admin_menus` VALUES ('28', '25', '删除应用', 'adminbase/Acl/App/del', '', '0', '0', '1');


-- ----------------------------
-- Table structure for pr_admin_systemlog
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_systemlog`;
CREATE TABLE `pr_admin_systemlog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) DEFAULT NULL,
  `username` varchar(40) DEFAULT NULL,
  `url` varchar(100) DEFAULT NULL COMMENT '操作的url',
  `action` varchar(100) DEFAULT NULL COMMENT 'url对应的菜单名',
  `get` varchar(500) DEFAULT NULL,
  `post` text,
  `ip` char(15) NOT NULL,
  `ctime` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `ctime` (`ctime`),
  KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;


-- ----------------------------
-- Table structure for pr_admin_users
-- ----------------------------
DROP TABLE IF EXISTS `pr_admin_users`;
CREATE TABLE `pr_admin_users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `groupid` varchar(255) NOT NULL DEFAULT '0',
  `username` varchar(40) NOT NULL DEFAULT '',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `password` char(32) NOT NULL DEFAULT '',
  `lastlogin` int(10) unsigned NOT NULL DEFAULT '0',
  `ctime` int(10) unsigned NOT NULL DEFAULT '0',
  `stime` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '1正常，0删除',
  `remark` text NOT NULL,
  `from_type` tinyint(3) unsigned DEFAULT '1' COMMENT '用户类型。1为系统用户',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Records of pr_admin_users
-- ----------------------------
INSERT INTO `pr_admin_users` VALUES ('1', '1', 'admin', '超级管理员', 'd1605189628b90a61dbac18518313b21', '1497580067', '0', '1476244882', '1', '', '1');
