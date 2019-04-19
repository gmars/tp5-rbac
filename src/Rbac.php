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
use gmars\rbac\model\Permission;
use gmars\rbac\model\PermissionCategory;
use gmars\rbac\model\Role;
use gmars\rbac\model\UserRole;
use think\Db;
use think\db\Query;
use think\db\Where;
use think\Exception;
use think\facade\Cache;
use think\facade\Request;
use think\facade\Session;

class Rbac
{


    /**
     * 认证方式   service方式和jwt方式
     * @var string
     */
    private $type = "service";

    /**
     * rbac存放的数据库
     * @var string
     */
    private $db = '';

    /**
     * jwt token生成加密密钥
     * @var string
     */
    private $saltToken = 'asdfqet9#$@#GS#$%080asdfaasdg';

    /**
     * token名称
     * @var string
     */
    private $tokenKey = 'Authorization';

    public function __construct()
    {
        $rbacConfig = config('rbac');
        if (!empty($rbacConfig)) {
            isset($rbacConfig['db']) && $this->db = $rbacConfig['db'];
            isset($rbacConfig['type']) && $this->type = $rbacConfig['type'];
            isset($rbacConfig['salt_token']) && $this->saltToken = $rbacConfig['salt_token'];
            isset($rbacConfig['token_key']) && $this->tokenKey = $rbacConfig['token_key'];
        }

    }

    /**
     * 生成所需的数据表
     * @param string $db
     */
    public function createTable($db = '')
    {
        $createTable = new CreateTable();
        $createTable->create($db);
    }

    /**
     * 配置参数
     * @param string $db
     */
    public function setDb($db = '')
    {
       $this->db = $db;
    }


    /**
     * 创建权限
     * @param array $data
     * @return Permission
     * @throws Exception
     */
    public function createPermission(array $data = [])
    {
        $model = new Permission($this->db);
        $model->data($data);
        try{
            $res = $model->savePermission();
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 修改权限数据(版本兼容暂时保留建议使用createPermission方法)
     * @param array $data
     * @param null $id
     * @return Permission
     * @throws Exception
     */
    public function editPermission(array $data = [], $id = null)
    {
        if (!empty($id)) {
            $data['id'] = $id;
        }
        try{
            return $this->createPermission($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据主键删除权限(支持多主键用数组的方式传入)
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delPermission($id = 0)
    {
        $model = new Permission($this->db);
        try {
            return $model->delPermission($id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据条件删除权限条件请参考tp5 where条件的写法
     * @param $condition
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delPermissionBatch($condition)
    {
        $model = new Permission($this->db);
        if ($model->where($condition)->delete() === false) {
            throw new Exception('批量删除数据出错');
        }
        return true;
    }

    /**
     * 根据主键/标准条件来查询权限
     * @param $condition
     * @return array|\PDOStatement|string|\think\Collection|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPermission($condition)
    {
        $model = new Permission($this->db);
        return $model->getPermission($condition);
    }

    /**
     * 编辑权限分组
     * @param array $data
     * @return PermissionCategory
     * @throws Exception
     */
    public function savePermissionCategory(array $data = [])
    {
        $model = new PermissionCategory($this->db);
        $model->data($data);
        try{
            $res = $model->saveCategory();
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据主键删除权限分组(支持多主键用数组的方式传入)
     * @param int $id
     * @return bool
     * @throws Exception
     */
    public function delPermissionCategory($id = 0)
    {
        $model = new PermissionCategory($this->db);
        try {
            $res = $model->delCategory($id);
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 获取权限分组
     * @param $where
     * @return array|\PDOStatement|string|\think\Collection|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPermissionCategory($where)
    {
        $model = new PermissionCategory($this->db);
        return $model->getCategory($where);
    }

    /**
     * 编辑角色
     * @param array $data
     * @param string $permissionIds
     * @return Role
     * @throws Exception
     */
    public function createRole(array $data = [], $permissionIds = '')
    {
        $model = new Role($this->db);
        $model->data($data);
        try{
            $res = $model->saveRole($permissionIds);
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    /**
     * 根据id或标准条件获取角色
     * @param $condition
     * @param bool $withPermissionId
     * @return array|\PDOStatement|string|\think\Collection|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRole($condition, $withPermissionId = true)
    {
        $model = new Role($this->db);
        return $model->getRole($condition, $withPermissionId);
    }

    /**
     * @param $id
     * @return bool
     * @throws Exception
     * 删除角色同时将角色权限对应关系删除(注意，会删除角色分配的权限关联数据)
     */
    public function delRole($id)
    {
        $model = new Role($this->db);
        try {
            $res = $model->delRole($id);
            return $res;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
        $model = new UserRole($this->db);
        $model->startTrans();
        if ($model->where('user_id', $userId)->delete() === false) {
            $model->rollback();
            throw new Exception('删除用户原有角色出错');
        }
        $userRole = [];
        foreach ($role as $v)
        {
            $userRole [] = ['user_id' => $userId, 'role_id' => $v];
        }
        if ($model->saveAll($userRole) === false) {
            $model->rollback();
            throw new Exception('给用户分配角色出错');
        }
        $model->commit();
        return ;
    }

    /**
     * 删除用户角色
     * @param $id
     * @return bool
     * @throws Exception
     * @throws \think\exception\PDOException
     */
    public function delUserRole($id)
    {
        if (empty($id)) {
            throw new Exception('参数错误');
        }
        $model = new UserRole($this->db);
        if ($model->where('user_id', $id)->delete() === false) {
            throw new Exception('删除用户角色出错');
        }
        return true;
    }

    /**
     * 获取用户权限并缓存
     * @param $id
     * @param int $timeOut
     * @return array|bool|mixed|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cachePermission($id, $timeOut = 3600)
    {
        if (empty($id)) {
            throw new Exception('参数错误');
        }
        $model = new Permission($this->db);
        $permission = $model->userPermission($id, $timeOut);
        return $permission;
    }

    /**
     * @param $path
     * @return bool
     * @throws Exception
     * 检查用户有没有权限执行某操作
     */
    public function can($path)
    {
        if ($this->type == 'jwt') {
            $token = Request::header($this->tokenKey);
            if (empty($token)) {
                throw new Exception('未获取到token');
            }
            $permissionList = Cache::get($token);
        } else {
            //获取session中的缓存名
            $cacheName = Session::get('gmars_rbac_permission_name');
            if (empty($cacheName)) {
                throw new Exception('未查询到登录信息');
            }
            $permissionList = Cache::get($cacheName);
        }

        if (empty($permissionList)) {
            throw new Exception('您的登录信息已过期请重新登录');
        }

        if (isset($permissionList[$path]) && !empty($permissionList[$path])) {
            return true;
        }
        return false;
    }

    /**
     * 生成jwt的token
     * @param $userId
     * @param int $timeOut
     * @param string $prefix
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function generateToken($userId, $timeOut = 7200, $prefix = '')
    {
        $token = md5($prefix . $this->randCode(32) . $this->saltToken . time());
        $freshTOken = md5($prefix . $this->randCode(32) . $this->saltToken . time());
        $permissionModel = new Permission($this->db);
        $permission = $permissionModel->getPermissionByUserId($userId);
        //无权限时为登录验证用
        if (!empty($permission)) {
            $newPermission = [];
            if (!empty($permission)) {
                foreach ($permission as $k=>$v)
                {
                    $newPermission[$v['path']] = $v;
                }
            }
            Cache::set($token, $newPermission, $timeOut);
        } else {
            //权限为空时token仅仅用作登录身份验证
            Cache::set($token, '', $timeOut);
        }
        Cache::set($freshTOken, $token, $timeOut);
        return [
            'token' => $token,
            'refresh_token' => $freshTOken,
            'expire' => $timeOut
        ];
    }

    /**
     * 刷新token
     * @param $refreshToken
     * @param int $timeOut
     * @param string $prefix
     * @return array
     * @throws Exception
     */
    public function refreshToken($refreshToken, $timeOut = 7200, $prefix = '')
    {
        $token = Cache::get($refreshToken);
        if (empty($token)) {
            throw new Exception('refresh_token已经过期');
        }
        $permission = Cache::get($token);
        if (empty($permission)) {
            throw new Exception('token已经过期');
        }
        $token = md5($prefix . $this->randCode(32) . $this->saltToken . time());
        $freshTOken = md5($prefix . $this->randCode(32) . $this->saltToken . time());
        Cache::set($token, $permission, $timeOut);
        Cache::set($freshTOken, $token);
        return [
            'token' => $token,
            'refresh_token' => $freshTOken,
            'expire' => $timeOut
        ];

    }

    /**
     * 生成随机码
     * @param int $length
     * @param string $type string|mix|number|special
     * @return string
     */
    private function randCode($length = 6, $type = 'mix')
    {
        $number = '0123456789';
        $seed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $specialChar = '!@#$%^&*()_+[]|';
        $randRes = "";
        switch ($type) {
            case 'string':
                for ($i = 0; $i < $length; $i++)
                {
                    $randomInt = rand(0, strlen($seed) - 1);
                    $randRes .= $seed{$randomInt};
                }
                break;
            case 'number':
                for ($i = 0; $i < $length; $i++)
                {
                    $randomInt = rand(0, strlen($number) - 1);
                    $randRes .= $number{$randomInt};
                }
                break;
            case 'mix':
                $mix = $number . $seed;
                for ($i = 0; $i < $length; $i++)
                {
                    $randomInt = rand(0, strlen($mix) - 1);
                    $randRes .= $mix{$randomInt};
                }
                break;
            case 'special':
                $special = $number . $seed . $specialChar;
                for ($i = 0; $i < $length; $i++)
                {
                    $randomInt = rand(0, strlen($special) - 1);
                    $randRes .= $special{$randomInt};
                }
                break;
        }
        return $randRes;
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
        throw new Exception('该方法已经弃用，请在创建角色时分配权限，调用createRole方法。如果你得项目中依旧想使用此方法请安装v1.3.1版本');
    }

    /**
     * 移动角色
     * @param $id
     * @param $parentId
     * @throws Exception
     */
    public function moveRole($id, $parentId)
    {
        throw new Exception('新版本中已经弃用角色的可继承关系，请使用用户可分配多个角色替代，如果你得项目中依旧想使用此方法请安装v1.3.1版本');
    }

    /**
     * 修改角色数据
     * @param $data
     * @return int|string
     * @throws Exception
     */
    public function editRole($data)
    {
        throw new Exception('请使用createRole方法在data中传入主键，如果你得项目中依旧想使用此方法请安装v1.3.1版本');
    }
    
    /**
     * @param array $data
     * @return int|string
     * @throws Exception
     * 创建用户[建议在自己系统的业务逻辑中实现]
     */
    public function createUser(array $data = [])
    {
        throw new Exception('该方法在新版本中已经废弃，因为用户表的差异比较大请大家自行实现');
    }

}