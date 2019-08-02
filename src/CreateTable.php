<?php
/**
 * Created by PhpStorm.
 * User: hayix
 * Date: 2019-04-17
 * Time: 16:56
 */

namespace gmars\rbac;


use think\Db;
use think\facade\Env;

class CreateTable
{
    private $_lockFile = '';
    private $_sqlFile = '';

    public function __construct()
    {
        $this->_lockFile = Env::get('root_path') . 'runtime/rbac_sql.lock';
        $this->_sqlFile = dirname(__DIR__) . '/gmars_rbac.sql';
    }

    /**
     * 创建数据表
     * @param string $db
     */
    public function create($db = '')
    {
        $dbConfig = Db::getConfig();
        $prefix = $db == ''? $dbConfig['prefix'] : $dbConfig[$db]['prefix'];

        if (file_exists($this->_lockFile)) {
            echo "<b style='color:red'>数据库创建操作被锁定，请删除[{$this->_lockFile}]文件后重试</b>";
            exit;
        }

        if ($this->_generateSql($prefix) === false) {
            echo '执行sql语句出错，请检查配置';
            exit;
        }
        echo '执行成功,如非必要请不要解锁后再次执行，重复执行会清空原有rbac表中的数据';
        $this->_writeLock();
        exit;
    }

    /**
     * 执行sql语句
     * @param string $prefix
     * @return bool
     */
    private function _generateSql($prefix = '')
    {
        $sql = $this->_loadSqlFile();
        $prefix = empty($prefix)? '' : $prefix;
        $sql = str_replace('###', $prefix, $sql);
        $sqlArr = explode(';', $sql);
        if (Db::batchQuery($sqlArr) === false) {
            return false;
        }
        return true;
    }

    /**
     * 加载sql文件
     * @return bool|string
     */
    private function _loadSqlFile()
    {
        $fileObj = fopen($this->_sqlFile, 'r');
        $sql = fread($fileObj, filesize($this->_sqlFile));
        fclose($fileObj);
        return $sql;
    }

    /**
     * 创建数据库操作锁
     */
    private function _writeLock()
    {
        $fileObj = fopen($this->_lockFile, 'w');
        fwrite($fileObj, date("Y-m-d H:i:s") . '执行成功!');
        fclose($fileObj);
    }

}
