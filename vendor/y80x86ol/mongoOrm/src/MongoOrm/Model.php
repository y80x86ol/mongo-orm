<?php

namespace mongoOrm;

use mongoOrm\Handle;

/**
 * 核心处理类
 */
class Model {
    private $fields = [];//字段
    private $db;//数据库
    private $collection;//集合
    private $isSuccess = 1;//操作是否成功

    /**
     * 初始化数据
     */
    public function __construct() {
        $this->fields = $this->fields();

        try {
            $connect = new \MongoClient(); // 连接默认主机和端口为：mongodb://localhost:27017
        } catch (MongoConnectionException $e) {
            return Handle::showError("db connect error:" . $e->getMessage());
        }

        try {
            $dbName = $this->dbName;
            $collName = $this->collName;

            $this->db = $connect->selectDB($dbName);
            $this->collection = $this->db->$collName;
        } catch (MongoConnectionException $e) {
            return Handle::showError("db collection error:" . $e->getMessage());
        }

        //实例化自增ID
        $this->initId();
    }

    public function __call($name, $arguments) {
        //暂时不做特殊处理
    }

    /**
     * ===============================================
     * 属性操作
     * ===============================================
     */
    /**
     * set方法
     * @param string $name 键名
     * @param string $arguments 键值
     */
    public function __set($name, $arguments) {
        $this->checkField($name);

        $this->fields[$name]['value'] = $arguments;
    }

    /**
     * get方法
     * @param $name
     * @return string
     */
    public function __get($name) {
        $this->checkField($name);

        $field = $this->fields[$name];

        //字段没有值则获取默认值
        if (isset($field['value']) && !empty($field['value'])) {
            return $field['value'];
        } else {
            return $this->getDefaultValue($field);
        }
    }

    /**
     * 检查字段
     * @param $name
     */
    private function checkField($name) {
        if (!isset($this->fields[$name])) {
            return Handle::showError("field " . $name . " does not exist");
        }
    }
    /**
     * ===============================================
     * 查询数据
     * ===============================================
     */
    /**
     * 查询多条数据
     * @param array $where 查询条件
     * @param array $sort 排序条件
     * @param int $page 页码
     * @param int $size 每页多少条
     * @return array
     */
    public function find($where, $sort = [], $page = 0, $size = 0) {
        $findList = $this->collection->find($where);

        //排序处理
        if (empty($sort)) {
            $sort = ['id' => 1];
        }
        $findList = $findList->sort($sort);

        //做分页处理
        $limit = $size;
        $skip = $page * $size;
        $findList = $findList->limit($limit)->skip($skip);

        //处理结果为对象
        $list = [];
        foreach ($findList as $item) {
            $itemObj = clone $this;
            foreach ($itemObj->fields as $name => $field) {
                $itemObj->fields[$name]['value'] = isset($item[$name]) ? $item[$name] : $itemObj->getDefaultValue($field);
            }
            //结果中返回ID
            $itemObj->fields['id']['value'] = isset($item['id']) ? $item['id'] : 0;

            $list[] = $itemObj;
        }

        return $list;
    }

    /**
     * 根据条件查询单条记录
     * @param array $where 查询条件
     * @return $this
     */
    public function findOne($where, $fields = [], $options = []) {
        $result = $this->collection->findOne($where, $fields, $options);
        //处理结果数据到fields中
        foreach ($this->fields as $name => $field) {
            $this->fields[$name]['value'] = isset($result[$name]) ? $result[$name] : $this->getDefaultValue($field);
        }

        //结果中返回ID
        $this->fields['id']['value'] = isset($result['id']) ? $result['id'] : 0;

        return $this;
    }

    /**
     * 通过ID查询单条记录
     * @param $id
     * @return mongoORM
     */
    public function findOneById($id, $fields = [], $options = []) {
        return $this->findOne(['id' => $id], $fields, $options);
    }

    /**
     * 统计查询
     * @param array $where 查询条件
     * @return int
     */
    public function count($where = []) {
        return $this->collection->count($where);
    }

    /**
     * 查询指定字段的去重后的数据
     * @param string $field 指定字段值
     * @param array $where 查询条件
     * @return array|bool
     */
    public function distinct($field, $where) {
        return $this->collection->distinct($field, $where);
    }

    /**
     * 聚合查询
     */
    public function aggregate($where, $options = []) {
        return $this->collection->aggregate($where, $options);
    }

    /**
     * ===============================================
     * 更新数据
     * ===============================================
     */
    /**
     * 对象保存内容
     */
    public function save() {
        // 更新文档
        $id = $this->checkId();

        //字段处理
        $document = [];
        foreach ($this->fields as $name => $field) {
            $document[$name] = isset($field['value']) ? $field['value'] : $this->getDefaultValue($field);
        }

        //删除主键ID
        unset($document['id']);

        $result = $this->collection->update(array("id" => $id), array('$set' => $document));
        return $this->handleResult($result);
    }

    /**
     * 更新内容
     * @param $where
     * @param $data
     * @return bool
     */
    public function update($where, $data) {
        $result = $this->collection->update($where, array('$set' => $data));
        return $this->handleResult($result);
    }

    /**
     * 查找并且修改
     */
    public function findAndModify($query, $update, $fields = [], $options = []) {
        $result = $this->collection->findAndModify($query, $update, $fields, $options);
        return $this->handleResult($result);
    }

    /**
     * ===============================================
     * 删除数据
     * ===============================================
     */
    /**
     * 根据ID删除数据
     * @param array $options
     * @return bool
     */
    public function remove($options = []) {
        $where = ['id' => $this->checkId()];
        return $this->delete($where, $options);
    }

    /**
     * 根据条件删除数据
     * @param array $where 查询条件
     * @param array $options 额外参数
     * @return bool
     */
    public function delete($where, $options = []) {
        $result = $this->collection->remove($where, $options);
        return $this->handleResult($result);
    }

    /**
     * 删除集合表
     * @return bool
     */
    public function drop() {
        $result = $this->collection->drop();
        return $this->handleResult($result);
    }

    /**
     * ===============================================
     * 写入数据
     * ===============================================
     */
    /**
     * 插入单条数据
     */
    public function insert($options = []) {
        $document = [];
        //自增ID
        $document['id'] = $this->atuoIncrementId();

        //字段处理
        foreach ($this->fields as $name => $field) {
            $document[$name] = isset($field['value']) ? $field['value'] : $this->getDefaultValue($field);
        }

        $result = $this->collection->insert($document, $options);
        if (empty($result['err'])) {
            //设置自增ID
            $this->setId($document['id']);
            return $document['id'];
        }
        return false;
    }

    /**
     * 批量插入数据
     * @param array $data 需要插入的数据
     * @return mixed
     */
    public function batchInsert($data, $options = []) {
        //对数据批量处理
        $newData = [];
        foreach ($data as $item) {
            $document = [];
            foreach ($this->fields as $name => $field) {
                $document[$name] = isset($item[$name]) ? $item[$name] : $this->getDefaultValue($field);
            }

            $document['id'] = $this->atuoIncrementId();
            $newData[] = $document;
        }
        return $this->collection->batchInsert($newData, $options);
    }

    /**
     * ===============================================
     * 关联查询
     * ===============================================
     */
    /**
     * 关联查询
     * @param string $collection 这个需要写成命名空间的形式，这样才能直接找到该类，而且实例化出对象
     * @param string $foreignKey 关联外键
     * @param string $primaryKey 关联主键，默认为id，可自定义
     */
    public function hasMore($collection, $foreignKey, $primaryKey = 'id') {

    }


    /**
     * ===============================================
     * 基础
     * ===============================================
     */
    /**
     * 获取字段的值
     * @param $field
     * @return array|float|int|string
     */
    private function getDefaultValue($field) {
        //1、先检查默认值
        if (isset($field['default'])) {
            return $field['default'];
        }

        //2、再根据字段类型获取值
        $type = isset($field['type']) ? $field['type'] : 'string';
        return $this->getEmptyValue($type);
    }

    /**
     * 获取字段的默认值
     * @param string $type 类型
     * @return array|float|int|string
     */
    private function getEmptyValue($type) {
        switch ($type) {
            case 'int':
                $value = 0;
                break;
            case 'float':
                $value = 0.0;
                break;
            case 'double':
                $value = 0.00;
                break;
            case 'string':
                $value = '';
                break;
            case 'array':
                $value = [];
                break;
            default:
                $value = '';
        }
        return $value;
    }

    /**
     * 展示错误信息
     * @param string $errorMsg
     */
    private function showError($errorMsg = '') {
        $errorMsg = $errorMsg ? $errorMsg : 'have a error';
        die($errorMsg);
    }

    /**
     * ===============================================
     * 失败成功处理
     * ===============================================
     */
    public function isSuccess() {
        if ($this->isSuccess) {
            return true;
        }
        return false;
    }

    /**
     * 处理查询结果
     * @param $result
     * @return bool
     */
    public function handleResult($result) {
        if (isset($result['err']) && !empty($result['err'])) {
            //报错
            $this->isSuccess = 0;
            return false;
        } else {
            $this->isSuccess = 1;
            //正确
            return true;
        }
    }

    /**
     * ===============================================
     * 自增实现
     * ===============================================
     */
    private function atuoIncrementId() {
        $collection = $this->db->ids;

        //处理操作
        $query = [
            'name' => $this->collName,
        ];
        $update = [
            '$inc' => ['id' => 1]
        ];
        $result = $collection->findAndModify($query, $update);
        return $result['id'] + 1;
    }

    /**
     * 初始化ID
     */
    private function initId() {
        //链接集合
        $collection = $this->db->ids;

        $isExist = $collection->find(['name' => $this->collName]);
        if ($isExist->count() == 0) {
            $document = [
                'name' => $this->collName,
                'id' => 0
            ];
            $collection->insert($document);
        }
    }

    /**
     * 检查主键ID
     */
    private function checkId() {
        if (!isset($this->fields['id']) || !isset($this->fields['id']['value'])) {
            return Handle::showError("id is not exists");
        }
        return $this->fields['id']['value'];
    }

    /**
     * 设置主键ID
     * @param int $id 主键ID
     */
    private function setId($id) {
        $this->fields['id']['value'] = $id;
    }
}