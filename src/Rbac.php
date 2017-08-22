<?php
/**
 * Created by PhpStorm.
 * Author: 魏永强   <hayixia606@163.com>
 * GitHub: https://github.com/gmars
 * Blog: http://blog.csdn.net/marswill
 * Date: 2017/8/21
 * Time: 上午12:38
 */

namespace gmars\rbac;


use gmars\nestedsets\NestedSets;
use think\Db;
use think\Exception;

class Rbac
{
    /**
     * @var string 权限表
     */
    private $permissionTable = "permission";

    /**
     * @var string 角色表
     */
    private $roleTable = "role";

    /**
     * @var string 用户角色对应表
     */
    private $userRoleTable = "user_role";

    /**
     * @var string 角色权限对应表
     */
    private $rolePermissionTable = "role_permission";

    /**
     * @var string 用户表
     */
    private $userTable = "user";


    public function __construct()
    {
        $rbacConfig = config('rbac');
        if (!empty($rbacConfig)) {
            isset($rbacConfig['permission']) && $this->permissionTable = $rbacConfig['permission'];
            isset($rbacConfig['role']) && $this->roleTable = $rbacConfig['role'];
            isset($rbacConfig['user_role']) && $this->userRoleTable = $rbacConfig['user_role'];
            isset($rbacConfig['role_permission']) && $this->rolePermissionTable = $rbacConfig['role_permission'];
            isset($rbacConfig['user']) && $this->userTable = $rbacConfig['user'];
        }

    }

    /**
     * @param $config
     * 配置参数
     */
    public function setConfig($config)
    {
        if (!empty($config) && is_array($config)) {
            isset($config['permission']) && $this->permissionTable = $config['permission'];
            isset($config['role']) && $this->roleTable = $config['role'];
            isset($config['user_role']) && $this->userRoleTable = $config['user_role'];
            isset($config['role_permission']) && $this->rolePermissionTable = $config['role_permission'];
            isset($config['user']) && $this->userTable = $config['user'];
        }
    }

    /**
     * @param $data
     * @return int|string
     * 创建权限
     */
    public function createPermission(array $data = [])
    {
        if (empty($data) || !is_array($data)) {
            throw new Exception('传入参数错误');
        }

        return Db::name($this->permissionTable)
            ->insert($data);
    }

    /**
     * @param array $data
     * @param null $id
     * @return bool
     * @throws Exception
     * 修改权限数据
     */
    public function editPermission(array $data = [], $id = null)
    {
        if (empty($id) && !is_array($data)) {
            throw new Exception('参数错误');
        }

        $dbObj = Db::name($this->permissionTable);

        if (empty($id)) {
            if (!isset($data[$dbObj->getPk()])) {
                throw new Exception('不传id时data中必须包含要修改数据的主键');
            }

            if ($dbObj->update($data) === false) {
                return false;
            }

            return true;
        }

        if (isset($data[$dbObj->getPk()])) {
            unset($data[$dbObj->getPk()]);
        }

        if ($dbObj->where($dbObj->getPk(), $id)
            ->update($data) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param int $id
     * @return bool
     * @throws Exception
     * 根据主键删除权限
     */
    public function delPermission($id = 0)
    {
        if (empty($id)) {
            throw new Exception('参数错误');
        }

        if (Db::name($this->permissionTable)->delete($id) === false) {
            return false;
        }

        return true;
    }

    /**
     * @param $condition
     * @return bool
     * @throws Exception
     * 根据条件批量删除权限，本操作中必须传入符合tp5条件的语句
     */
    public function delPermissionBatch($condition)
    {
        if (!is_array($condition) || !is_string($condition)) {
            throw new Exception('请按照tp5条件语句的方式传入字符串或数组的条件');
        }

        if (Db::name($this->permissionTable)->where($condition)->delete() === false) {
            return false;
        }

        return true;
    }

    /**
     * @param $condition
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     * 传入的条件当为主键时直接按照主键查询，如果为条件语句必须符合tp5的where条件写法
     */
    public function getPermission($condition)
    {
        $dbObj = Db::name($this->permissionTable);
        if (is_numeric($condition)) {
            return $dbObj->where($dbObj->getPk(), $condition)->find();
        }

        return $dbObj->where($condition)->select();

    }

    /**
     * @param array $data
     * @return int|string
     * @throws Exception
     * 添加角色
     */
    public function createRole(array $data = [])
    {
        if (empty($data)) {
            throw new Exception('参数错误');
        }

        $parentId = isset($data['parent_id'])? $data['parent_id']:0;
        unset($data['parent_id']);
        $parent = $this->getRole($parentId);
        if ($parentId != 0 && empty($parent)) {
            throw new Exception('父角色不存在');
        }

        $nestedObj = new NestedSets($this->roleTable);
        return $nestedObj->insert($parentId, $data);

    }

    /**
     * @param $id
     * @param $parentId
     * @return bool
     * 将主键为id的角色移动到主键为parentId的角色下
     */
    public function moveRole($id, $parentId)
    {
        $nestedObj = new NestedSets($this->roleTable);
        return $nestedObj->moveUnder($id, $parentId);
    }

    /**
     * 修改角色数据
     * @param $data
     * @return int|string
     * @throws Exception
     */
    public function editRole($data)
    {
        $dbObj = Db::name($this->roleTable);

        if (!isset($data[$dbObj->getPk()])) {
            throw new Exception('数据中必须包含主键');
        }

        return $dbObj->update($data) === false? false:true;
    }

    /**
     * 根据id获取角色
     * @param $id
     * @param bool $child 为true时返回该角色以及所有的子角色
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     */
    public function getRole($id, $child = false)
    {
        $nestedObj = new NestedSets($this->roleTable);
        if ($child) {
            return $nestedObj->getPath($id);
        }else{
            $dbObj = Db::name($this->roleTable);
            return $dbObj->where($dbObj->getPk(), $id)->find();
        }

    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     * 删除角色同时将角色权限对应关系删除
     */
    public function delRole($id)
    {
        $data = $this->getRole($id);
        if (empty($data)) {
            throw new Exception('要删除的角色不存在');
        }

        Db::startTrans();

        try{
            $nestedObj = new NestedSets($this->roleTable);
            if ($nestedObj->delete($id) === false) {
                Db::rollback();
                return false;
            }

            if (Db::name($this->rolePermissionTable)->where('role_id', $id)->delete() === false) {
                Db::rollback();
                return false;
            }

            Db::commit();
            return true;
        }catch (Exception $e){
            Db::rollback();
            return false;
        }
    }


    /**
     * @param $userId
     * @param array $role
     * @return int|string
     * @throws Exception
     * 为用户分配角色
     */
    public function assignUserRole($userId, array $role = [])
    {
        if (empty($userId) || empty($role)) {
            throw new Exception('参数错误');
        }

        $userRole = [];
        foreach ($role as $v)
        {
            $userRole [] = ['user_id' => $userId, 'role_id' => $v];
        }

        return Db::name($this->userRoleTable)->insertAll($userRole);
    }

    /**
     * @param $roleId
     * @param array $permission
     * @return int|string
     * @throws Exception
     * 为角色分配权限
     */
    public function assignRolePermission($roleId, array $permission = [])
    {
        if (empty($roleId) || empty($permission)) {
            throw new Exception('参数错误');
        }

        $rolePermission = [];
        foreach ($permission as $v)
        {
            $rolePermission [] = ['role_id' => $roleId, 'permission_id' => $v];
        }

        return Db::name($this->rolePermissionTable)->insertAll($rolePermission);
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     * 删除用户[同时会删除用户的所有角色]
     */
    public function delUser($id)
    {
        if (empty($id)) {
            throw new Exception('参数错误');
        }

        Db::startTrans();

        $dbObj = Db::name($this->userTable);
        if ($dbObj->where($dbObj->getPk(), $id)->delete() === false) {
            Db::rollback();
            return false;
        }

        if (Db::name($this->userRoleTable)->where('user_id', $id)->delete() === false) {
            Db::rollback();
            return false;
        }

        Db::commit();
        return true;
    }

    /**
     * @param array $data
     * @return int|string
     * @throws Exception
     * 创建用户[建议在自己系统的业务逻辑中实现]
     */
    public function createUser(array $data = [])
    {
        if (empty($data)) {
            throw new Exception('参数错误');
        }

        return Db::name($this->userTable)->insert($data);
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     * 查询出该用户的所有权限并存入缓存
     */
    public function cachePermission($id)
    {
        if (empty($id)) {
            throw new Exception('参数错误');
        }

        $permission = Db::name($this->permissionTable)
            ->alias('p')
            ->join("{$this->rolePermissionTable} rp", "p.id = rp.permission_id")
            ->join("{$this->userRoleTable} ur", "rp.role_id = ur.role_id")
            ->where("ur.user_id = {$id}")->select();

        $newPermission = [];
        if (!empty($permission)) {
            foreach ($permission as $k=>$v)
            {
                $newPermission[$v['path']] = $v;
            }
        }

        cache("permission", $newPermission);
        return true;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     * 检查用户有没有权限执行某操作
     */
    public function can($path)
    {
        $permissionList = cache("permission");
        if (empty($permissionList)) {
            throw new Exception('你还没有登录或在登录后没有获取权限缓存');
        }

        if (isset($permissionList[$path]) && !empty($permissionList[$path])) {
            return true;
        }

        return false;
    }


}