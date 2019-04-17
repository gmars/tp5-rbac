<?php
/**
 * Created by WeiYongQiang.
 * User: weiyongqiang <hayixia606@163.com>
 * Date: 2019-04-17
 * Time: 22:54
 */

namespace gmars\rbac\validate;


use think\Validate;

class Permission extends Validate
{
    protected $rule = [
        'name' => 'require|max:50|unique:gmars\rbac\model\permission,name',
        'path' => 'require|max:200|unique:gmars\rbac\model\permission,path',
        'category_id' => 'require|number',
        'type' => 'require'
    ];

    protected $message = [
        'name.require' => '权限名不能为空',
        'name.max' => '权限名不能长于50个字符',
        'path.require' => '路径不能为空',
        'path.max' => '路径不能长于200个字符',
        'category_id.require' => '权限分类必须选择',
        'category_id.number' => '权限分类必须是数字id',
        'name.unique' => '权限名称不能重复',
        'path.unique' => '权限路径不能为空',
        'type.require' => '权限类型不能为空'
    ];

}