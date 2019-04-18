<?php
/**
 * Created by WeiYongQiang.
 * User: weiyongqiang <hayixia606@163.com>
 * Date: 2019-04-17
 * Time: 22:49
 */

namespace gmars\rbac\model;



use think\Db;
use think\Exception;
use think\facade\Cache;
use think\facade\Session;

class Permission extends Base
{
    /**
     * @var string 权限缓存前缀
     */
    private $_permissionCachePrefix = "_RBAC_PERMISSION_CACHE_";

    protected $auto = ['path_id'];

    protected function setPathIdAttr()
    {
        return md5($this->getData('path'));
    }

    /**
     * 编辑权限数据
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function savePermission($data = [])
    {
        if (!empty($data)) {
            $this->data($data);
        }
        $validate = new \gmars\rbac\validate\Permission();
        if (!$validate->check($this)) {
            throw new Exception($validate->getError());
        }
        $data = $this->getData();
        if (isset($data['id']) && !empty($data['id'])) {
            $this->isUpdate(true);
        }
        $this->save();
        return $this;
    }

    /**
     * 删除权限
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function delPermission($id)
    {
        $where = [];
        if (is_array($id)) {
            $where[] = ['id', 'IN', $id];
        } else {
            $id = (int)$id;
            if (is_numeric($id) && $id > 0) {
                $where[] = ['id', '=', $id];
            } else {
                throw new Exception('删除条件错误');
            }
        }

        if ($this->where($where)->delete() === false) {
            throw new Exception('删除权限出错');
        }
        return true;
    }

    /**
     * 获取用户权限
     * @param $userId
     * @param int $timeOut
     * @return array|mixed|\PDOStatement|string|\think\Collection
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userPermission($userId, $timeOut = 3600)
    {
        if (empty($userId)) {
            throw new Exception('参数错误');
        }
        $permission = Cache::get($this->_permissionCachePrefix . $userId);
        if (!empty($permission)) {
            return $permission;
        }
        $permission = $this->getPermissionByUserId($userId);
        if (empty($permission)) {
            throw new Exception('未查询到该用户的任何权限');
        }
        $newPermission = [];
        if (!empty($permission)) {
            foreach ($permission as $k=>$v)
            {
                $newPermission[$v['path']] = $v;
            }
        }
        Cache::set($this->_permissionCachePrefix . $userId, $newPermission, $timeOut);
        Session::set('gmars_rbac_permission_name', $this->_permissionCachePrefix . $userId);
        return $newPermission;
    }

    /**
     * 根据userid获取权限
     * @param $userId
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPermissionByUserId($userId)
    {
        $prefix = $this->getConfig('prefix');
        $permission = Db::name('permission')->setConnection($this->getConnection())->alias('p')
            ->join(["{$prefix}role_permission" => 'rp'], 'p.id = rp.permission_id')
            ->join(["{$prefix}user_role" => 'ur'], 'rp.role_id = ur.role_id')
            ->where('ur.user_id', $userId)->select();
        return $permission;
    }

    /**
     * 获取权限节点
     * @param $condition
     * @return array|\PDOStatement|string|\think\Collection|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPermission($condition)
    {
        $model = Db::name('permission')->setConnection($this->getConnection());
        if (is_numeric($condition)) {
           return $model->where('id', $condition)->find();
        } else {
            return $model->where($condition)->select();
        }
    }
}