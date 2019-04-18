# tp5-rbac
>本扩展包是tp5的rbac包，使用了部分tp5的特性实现了关系型数据库中特殊数据结构的处理。

## 安装方法
先安装composer如果不知道怎么安装使用composer请自行百度。
打开命令行工具切换到你的tp5项目根目录

```
composer require gmars/tp5-rbac
```
如果该方法报错请按照以下方式操作：

1. 打开项目根目录下的composer.json
2. 在require中添加"gmars/tp5-rbac": "dev-master"
3. 运行composer update

添加后composer.json应该有这样的部分：

```
    "require": {
        "php": ">=5.4.0",
        "topthink/framework": "^5.0",
        "gmars/tp5-rbac": "dev-master"
    },
```

# v2.0+版本使用说明
## 配置
请将此配置加在config/app.php的配置中
```php
'rbac' => [
    'type' => 'jwt',    //验证方式 jwt(token方式)形式或者service(基于cookie)方式
    'db' => '',        //rbac要使用的数据库配置为空则为默认库(生成表的前缀依赖此配置)
    'salt_token' => 'asdfasfdafasf',    //token加密密钥
    'token_key' => 'Authorization'      //header中用于验证token的名称
]
```
## 使用说明
实例化rbac
```php
$rbac = new Rbac();
```
### 管理操作
#### 初始化rbac所需的表
```php
//可传入参数$db为数据库配置项默认为空则为默认数据库(考虑到多库的情形)
$rbac->createTable();
```
该方法会生成rbac所需要的表，一般只执行一次，为了安全，执行后会加锁，下次要执行需要删除锁文件再执行。

#### 创建权限分组
```php
$rbac->savePermissionCategory([
    'name' => '用户管理组',
    'description' => '网站用户的管理',
    'status' => 1
]);
```
编辑和修改调用同一个方法编辑时请在参数中包含主键id的值

#### 创建权限节点
```php
$rbac->createPermission([
    'name' => '文章列表查询',
    'description' => '文章列表查询',
    'status' => 1,
    'type' => 1,
    'category_id' => 1,
    'path' => 'article/content/list',
]);
```
- 如果为修改则在传入参数数组中加入主键id的键值
- type为权限类型1为后端权限2为前端权限主要考虑到spa使用
- category_id为上一步创建的权限分组的id
- 创建成功返回添加的该条权限数据，错误抛出异常
#### 创建角色&给角色分配权限
```php
$rbac->createRole([
    'name' => '内容管理员',
    'description' => '负责网站内容管理',
    'status' => 1
], '1,2,3');
```
- 如果修改请在第一个参数中传入主键的键值
- 第二个参数为权限节点的id拼接的字符串请使用英文逗号

#### 给用户分配角色
```php
$rbac->assignUserRole(1, [1]);
```
- 该方法会删除用户之前被分配的角色
- 第一个参数为用户id
- 第二个参数为角色id的数组
#### 获取权限分组列表
```php
$rbac->getPermissionCategory([['status', '=', 1]]);
```
- 参数支持传入id查询单条数据和标准的where表达式查询列表传为空数组则查询所有

#### 获取权限列表
```php
$rbac->getPermission([['status', '=', 1]]);
```
- 参数支持传入id查询单条数据和标准的where表达式查询列表传为空数组则查询所有

#### 获取角色列表
```php
$rbac->getRole([], true);
```
- 第一个参数支持传入id查询单条数据和标准的where表达式查询列表传为空数组则查询所有
- 第二个参数选择是否查询角色分配的所有权限id默认为true

#### 删除权限分组
```php
$rbac->delPermissionCategory([1,2,3,4]);
```
- 参数支持传入单个id或者id列表

#### 删除权限
```php
$rbac->delPermission([1,2,3,4]);
```
- 参数支持传入单个id或者id列表

#### 删除角色
```php
$rbac->delRole([1,2,3,4]);
```
- 参数支持传入单个id或者id列表
- 删除角色会删除给角色分配的权限[关联关系]

### 验证操作
#### service方式
service方式因为要用到session一般要依赖于cookie。在用户登录后要获取用户权限操作
```php
$rbac->cachePermission(1);
```
- 传入参数为登录用户的user_id
- 该方法会返回该用户所有的权限列表

用户请求时进行验证
```php
$rbac->can('article/channel/list');
```
- 如果有权限返回true如果没有权限返回false
#### jwt方式
jwt方式在前后端分离结构用的比较普遍。在用户登录后需要获取token
```php
$rbac->generateToken(1);
```
- 第一个参数为登录的用户id
- 第二个参数为token有效期默认为7200秒
- 第三个参数为token前缀
返回结果为
```php
array(3) {
  ["token"] => string(32) "4c56b80f06d3d8810b97db33a1291694"
  ["refresh_token"] => string(32) "17914241bde6bfc46b20e643b2c58279"
  ["expire"] => int(7200)
}
```
*使用refresh_token刷新权限*
```php
$rbac->refreshToken('17914241bde6bfc46b20e643b2c58279');
```
请在有效期内使用refresh_token来刷新授权
*用户请求时验证*
```php
$rbac->can('article/channel/list');
```


# < v2.0使用说明
## `数据迁移(可选，可以直接使用包中的gmars_rbac.sql文件导入)`
在使用本插件之前需要有rbac锁需要的数据库。在迁移之前如果你的数据库中已有user数据表那么请你备份自己的user数据表后删除。

在你的项目的某个config.php中加入如下配置：
```php
'migration' => [
    'path' => ROOT_PATH .'vendor/gmars/tp5-rbac/'
],
```
然后把命令行切换到你的项目根目录Windows是cmd运行如下命令

```php
php think migrate:run
```
如果迁移运行成功会在你的数据库中生成如下几张表:
```php
user              用户表
user_role         用户角色对应表
role              角色表
role_permission   角色权限对应表
permission        角色表
```
### 使用该插件--RBAC的管理

在一个系统中RBAC是基于角色的权限控制。作为开发人员需要明白这是两个不同的过程。第一个就是构建系统的RBAC结构，包括添加权限，角色，用户，用户角色对应关系，角色权限对应关系等。

在此先说明RBAC管理：

1.添加用户

这一步是在用户注册时要做的一步，就是讲注册的用户添加到user表中。

```php
$rbacObj = new Rbac();
$data = ['user_name' => 'zhangsan', 'status' => 1, 'password' => md5('zhangsan')];
$rbacObj->createUser($data);
```
创建用户时传入唯一一个参数必须是数组。数组中应该包含用户表需要的数据。如果出现其他非user表的字段则会抛出异常。
该方法返回的结果为false或者Exception或者**新添加用户的id**。

2.添加权限

这一步是构建系统的权限。一般我们是以请求的路由为权限的识别标志。在该插件中使用path字段。

例如我们的系统中有商品列表这样的一个操作需要授权。

其路由为  /index/goods/list

添加路由如下：
```php
$rbacObj = new Rbac();
$data = [
    'name' => '商品列表',
    'status' => 1,
    'description' => '查看商品的所有列表',
    'path' => '/index/goods/list',
    'create_time' => time()
];
$rbacObj->createPermission($data);
```
3.添加角色

在RBAC的角色中角色是有父子关系的，也就是说所添加的角色可以是另一个角色的子角色。

```php
$rbacObj = new Rbac();
$data = [
    'name' => '商品管理员',
    'status' => 1,
    'description' => '商品管理员负责商品的查看修改删除等操作',
    'sort_num' => 10,
    'parent_id' => 1
];
$rbacObj->createRole($data);
```

需要注意的是在data中有个字段为parent_id这个字段标识了所要添加的角色的父角色。如果留为空则便是添加的父角色。

4.为用户分配角色

当然一个用户可以有多个角色。一般是使用多选框或其他形式选择后以数组的方式传入的。

例如：

```php
$rbacObj = new Rbac();
$rbacObj->assignUserRole(1, [1, 2]);
```

assignUserRole($userId, array $roleArray = [])

该方法的第一个参数为用户id第二个参数是一个一位数组，其元素为角色的id

5.为角色分配权限

例如：
```php
$rbacObj = new Rbac();
$rbacObj->assignRolePermission(1, [1, 2]);
```
将id分别为1，2的权限分配给id为1的角色

6.删除角色
>删除角色的同时必须删除角色和权限的对应数据

```php
$rbacObj = new Rbac();
$rbacObj->delRole(1);
```
其中需要传入的是角色id

7.将一个角色移到另一个角色下

以上已经说明了角色是有父子关系的那么肯定能够移动其位置

```php
$rbacObj = new Rbac();
$rbacObj->moveRole(1,3);
```
该例子是将id为1的角色移动到id为3的角色下作为子角色。

>还有其他修改删除等方法的文档日后再补全，功能是有的

### 使用该插件--RBAC权限验证

#### 登录后获取权限列表
如果自己写权限验证则请忽略这一步，如果要使用rbac插件来验证权限则必须要这样做。

在登录成功后做如下操作：

```php
$rbacObj = new Rbac();
$rbacObj->cachePermission(1);
```
这个方法是查询id为1的用户的所有权限并且以path索引后存入cache

#### 请求时的权限验证

当然对于每一个方法都要进行权限验证时我们一般是在某一个父类中定义一个方法进行权限验证，验证如下：

```php
$rbacObj = new Rbac();
$rbacObj->can('/index/goods/list');
```

该方法是验证当前用户有没有操作/index/goods/list的权限，如果有则返回true如果无则返回false

其中can的参数可以使用tp5的特性获取。
