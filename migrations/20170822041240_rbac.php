<?php

use think\migration\Migrator;
use think\migration\db\Column;

class Rbac extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $userTable = $this->table("user", ['engine'=>'InnoDB', 'charset' => 'utf8', 'comment'=> '用户表']);
        $userTable->addColumn('user_name', 'string',array('limit' => 50,'default'=>'','comment'=>'用户账号'))
            ->addColumn('password', 'string',array('limit' => 64,'default'=>'','comment'=>'用户密码'))
            ->addColumn('mobile', 'string',array('limit' => 20,'default'=>'','comment'=>'手机号码'))
            ->addColumn('email', 'string',array('limit' => 50,'default'=>'','comment'=>'邮箱'))
            ->addColumn('last_login_time', 'integer',array('default'=>0,'comment'=>'最后登录时间'))
            ->addColumn('status', 'integer',array('limit' => 1,'default'=>0,'comment'=>'用户状态'))
            ->create();

        $roleTable = $this->table("role", ['engine'=>'InnoDB', 'charset' => 'utf8', 'comment'=> '角色']);
        $roleTable->addColumn('name', 'string', array('limit' => 50, 'default'=>'', 'comment' => '角色名称'))
            ->addColumn('parent_id', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '父角色id'))
            ->addColumn('description', 'string', array('limit' => '200', 'default' => '', 'comment' => '描述信息'))
            ->addColumn('status', 'integer', array('limit'=>1, 'default' => 0, 'comment' => '角色状态'))
            ->addColumn('sort_num', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '排序值'))
            ->addColumn('left_key', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '用来组织关系的左值'))
            ->addColumn('right_key', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '用来组织关系的右值'))
            ->addColumn('level', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '所处层级'))
            ->create();

        $permissionTable = $this->table('permission', ['engine'=>'InnoDB', 'charset' => 'utf8', 'comment'=> '权限表']);
        $permissionTable->addColumn('name', 'string', array('limit' => 50, 'default' => '', 'comment' => '权限名称'))
            ->addColumn('path', 'string', array('limit' => 100, 'default' => '', 'comment' => '权限路径'))
            ->addColumn('description', 'string', array('limit' => 200, 'default' => '', 'comment' => '权限描述'))
            ->addColumn('status', 'integer', array('limit' => 1, 'default' => 0, 'comment' => '权限状态'))
            ->addColumn('create_time', 'integer', array('limit' => 10, 'default' => 0, 'comment' => '创建时间'))
            ->create();

        $userRoleTable = $this->table('user_role', ['engine'=>'InnoDB', 'charset' => 'utf8', 'comment'=> '用户角色对应关系']);
        $userRoleTable->addColumn('user_id', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '用户id'))
            ->addColumn('role_id', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '角色id'))
            ->create();

        $rolePermissionTable = $this->table('role_permission', ['engine'=>'InnoDB', 'charset' => 'utf8', 'comment'=> '角色权限对应表']);
        $rolePermissionTable->addColumn('role_id', 'integer', array('limit' => 11, 'default' => 0, 'comment' => '角色Id'))
            ->addColumn('permission_id', 'integer', array('limit' => 11, 'default' =>0, 'comment' => '权限ID'))
            ->create();
    }
}
