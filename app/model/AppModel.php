<?php

/**
 * 基类模型层
 * @copyright Copyright 2012-2017, BAONAHAO Software Foundation, Inc. ( http://api.baonahao.com/ )
 * @link http://api.baonahao.com api(tm) Project
 * @author zhanglibo <zhanglibo@xiaohe.com>
 */
class AppModel extends \Phalcon\Mvc\Model
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var integer
     */
    public $is_usable;

    /**
     * @var integer
     */
    public $is_delete;

    /**
     * @var string
     */
    public $created;

    /**
     * @var string
     */
    public $modified;

    protected $now = '';

    /**
     * 通过ID查询
     * @param        $id
     * @param string $field
     * @param bool $to_array
     * @return array|\Phalcon\Mvc\Model
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getById($id, $field = '*', $to_array = true)
    {
        if (empty($id))
        {
            return [];
        }
        $result = $this->FindFirst(
            [
                'columns'    => $field,
                'conditions' => 'id=:id:',
                'bind'       => ['id' => $id],
            ]
        );
        if ($to_array)
        {
            return $result ? $result->toArray() : [];
        }

        return $result;
    }

    /**
     * 查询单条记录
     * @param        $condition
     * @param string $field
     * @param bool $to_array
     * @return array|\Phalcon\Mvc\Model
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getOne($condition, $field = "*", $to_array = true)
    {
        $where  = $this->getCondition($condition);
        $result = $this->findFirst(
            [
                'columns'    => $field,
                'conditions' => $where,
                'bind'       => $this->getBind($condition),
            ]
        );

        if ($to_array)
        {
            return $result ? $result->toArray() : [];
        }

        return $result;
    }

    /**
     * 查询多条记录-带分页
     * @param array $condition where条件
     * @param string $field 字段
     * @param int $page 页码
     * @param int $pageSize 记录条数
     * @param null $order 排序
     * @param bool $to_array 是否转数组
     * @return array|\Phalcon\Mvc\Model\ResultsetInterface
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getList($condition, $field = "*", $page = 1, $pageSize = 10, $order = null, $to_array = true)
    {
        $where  = $this->getCondition($condition);
        $offset = ($page - 1) * $pageSize;

        $result = $this->find(
            [
                'columns'    => $field,
                'conditions' => $where,
                'bind'       => $this->getBind($condition),
                'limit'      => $pageSize,
                'offset'     => $offset,
                'order'      => $order,
            ]
        );

        if ($to_array)
        {
            return $result ? $result->toArray() : [];
        }

        return $result;
    }

    /**
     * 查询多条记录-全部
     * @param        $condition
     * @param string $field
     * @param null $order
     * @param bool $to_array
     * @return array|\Phalcon\Mvc\Model\ResultsetInterface
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getAll($condition, $field = "*", $order = null, $to_array = true)
    {
        $where = $this->getCondition($condition);

        $result = $this->find(
            [
                'columns'    => $field,
                'conditions' => $where,
                'bind'       => $this->getBind($condition),
                'order'      => $order,
            ]
        );

        if ($to_array)
        {
            return $result ? $result->toArray() : [];
        }

        return $result;
    }

    /**
     * 添加-单条记录
     * @param array $data
     * @return bool
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function add($data)
    {
        if (empty($data))
        {
            return false;
        }
        $this->create($data);

        return $this->getWriteConnection()->affectedRows();
    }

    /**
     * 添加-多条记录
     * @param      $data
     * @param null $encode_field
     * @return mixed
     * @author zhanglibo <zhanglibo@xiaohe.com>
     * @throws Exception
     */
    public function addAll($data, $encode_field = null)
    {
        $db_security_code = getConfig('DB_SECURITY_CODE');

        $keys = array_keys(reset($data));
        foreach ($keys as &$val)
        {
            $val = "`{$val}`";
        }
        $keys = implode(',', $keys);
        $sql  = "INSERT INTO " . $this->getSource() . " ({$keys}) VALUES ";
        foreach ($data as $k => $v)
        {
            foreach ($v as $key => &$val)
            {
                if ($key == $encode_field)
                {
                    $val = "ENCODE('{$val}','{$db_security_code}')";
                } else
                {
                    $val = "'{$val}'";
                }
            }

            $values = implode(',', array_values($v));
            $sql    .= " ({$values}), ";
        }
        $sql = rtrim(trim($sql), ',');

        $result = $this->getWriteConnection()->execute($sql);
        if (!$result)
        {
            throw new \Exception('批量插入失败');
        }

        return $this->getWriteConnection()->affectedRows();

    }

    /**
     * 更新-通过ID
     * (不适用于联合主键)
     * @param array|null $data
     * @return bool
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function updateById($data)
    {
        $this->id = getArrVal($data, 'id');
        if (property_exists($this, 'modified'))
        {
            $data['modified'] = $this->getNow();
        }
        $attributes = $this->getModelsMetaData()->getAttributes($this);
        $diff       = array_diff($attributes, array_keys($data));
        if ($diff)
        {
            $this->skipAttributesOnUpdate(array_diff($attributes, array_keys($data)));
        }

        $this->update($data);

        return $this->getWriteConnection()->affectedRows();
    }

    /**
     * 添加、更新-通过ID
     * @param array|null $data
     * @param null $whiteList
     * @return bool
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function saveById(array $data = null, $whiteList = null)
    {
        if (property_exists($this, 'modified'))
        {
            $data['modified'] = $this->getNow();
        }
        $attributes = $this->getModelsMetaData()->getAttributes($this);
        $this->skipAttributesOnUpdate(array_diff($attributes, array_keys($data)));
        $this->save($data, $whiteList);

        return $this->getWriteConnection()->affectedRows();

    }

    /**
     * 更新
     * @param array|string $condition 条件
     * @param array $data 更新字段
     * @param string $encode_field encode字段
     * @return bool
     * @author zhanglibo <zhanglibo@xiaohe.com>
     * @throws Exception
     */
    public function updateRecord($condition, $data, $encode_field = null)
    {
        $db_security_code = getConfig('DB_SECURITY_CODE');

        if (count($condition) == 0 || count($data) == 0)
        {
            return false;
        }
        if (property_exists($this, 'modified'))
        {
            $data['modified'] = $this->getNow();
        }

        foreach ($data as $key => &$val)
        {
            if ($key == $encode_field)
            {
                $val = "`{$key}`=ENCODE('{$val}','{$db_security_code}')";
            } else
            {
                $val = "`{$key}`='{$val}'";
            }
        }
        $set = join(',', $data);
        if (!is_array($condition))
        {
            //字符串
            $where = $condition;
        } else
        {
            foreach ($condition as $key => &$val)
            {
                $val = "`{$key}`='{$val}'";
            }
            $where = join(',', $condition);
        }
        if (empty($set) || empty($where))
        {
            throw new \Exception('更新失败');
        }
        $sql = "UPDATE `{$this->getSource()}` SET {$set} WHERE {$where}";

        $result = $this->getWriteConnection()->execute($sql);
        if (!$result)
        {
            throw new \Exception('更新失败');
        }

        return $this->getWriteConnection()->affectedRows();
    }

    /**
     * where 条件
     * @param $condition
     * @return string
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    protected function getCondition($condition)
    {
        if (is_string($condition))
        {
            return $condition;
        }

        foreach ($condition as $k => $v)
        {
            if (is_array($v))
            {
                unset($condition[$k]);
            }
        }
        $keys = array_keys($condition);

        foreach ($keys as &$val)
        {
            $val = "{$val}=:{$val}:";
        }

        return join(' and ', $keys);

    }

    /**
     * 查询-通过ID列表
     * @param        $id
     * @param string $field 字段(不能使用带逗号的函数)
     * @param string $pk 主键
     * @param bool $id_to_key 是否以Id为主键返回
     * @return array|\Phalcon\Mvc\Model\ResultsetInterface
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getListById($id, $field = "*", $pk = 'id', $id_to_key = true)
    {
        if (empty($id))
        {
            return [];
        }
        $column = $field;
        if ($field != '*' && !in_array($pk, explode(',', strtolower($field))))
        {
            $column = "{$field},{$pk}";
        }
        $str    = "{$pk} in ({{$pk}:array})";
        $result = $this->find(
            [
                'columns'    => $column,
                'conditions' => $str,
                'bind'       => [$pk => $id],
            ]
        );
        if (empty($result))
        {
            return [];
        }
        $result = $result->toArray();

        if ($id_to_key)
        {
            if (count(reset($result)) == 2)
            {
                //单字段
                return array_column($result, $field, $pk);
            }

            //多字段
            return array_column($result, null, $pk);
        }

        return $result;
    }

    /**
     * 查询-统计
     * @param $condition
     * @return mixed
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getTotal($condition)
    {
        $where = $this->getCondition($condition);

        $result = $this->count(
            [
                'conditions' => $where,
                'bind'       => $this->getBind($condition),
            ]
        );

        return $result;
    }

    /**
     * bind 是否为数组
     * @param $condition
     * @return null
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    protected function getBind($condition)
    {
        return is_string($condition) ? null : $condition;
    }

    /**
     * 数据填充-Create
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function beforeValidationOnCreate()
    {
        $this->setDefaultValue();
    }

    /**
     * 关闭非空验证
     */
    public function beforeValidation(){
        $this->setup(
            array('notNullValidations'=>false)
        );
    }
    /**
     * 添加-有encode字段
     * @param $data
     * @param $encode_field
     * @return mixed
     * @author zhanglibo <zhanglibo@xiaohe.com>
     * @throws Exception
     */
    public function addEncode($data, $encode_field)
    {
        $db_security_code = getConfig('DB_SECURITY_CODE');

        //默认值
        if (property_exists($this, 'created') && empty($data['created']))
        {
            $data['created'] = $this->getNow();
        }
        if (property_exists($this, 'modified') && empty($data['modified']))
        {
            $data['modified'] = $this->getNow();
        }
        if (property_exists($this, 'is_usable') && empty($data['is_usable']))
        {
            $data['is_usable'] = 1;
        }
        if (property_exists($this, 'is_delete') && empty($data['is_delete']))
        {
            $data['is_delete'] = 2;
        }
        $keys = array_keys($data);
        foreach ($keys as &$val)
        {
            $val = "`{$val}`";
        }
        $keys = implode(',', $keys);
        $sql  = "INSERT INTO " . $this->getSource() . " ({$keys}) VALUES ";

        foreach ($data as $key => &$val)
        {
            if ($key != $encode_field)
            {
                $val = "'{$val}'";
            } else
            {
                $val = "ENCODE('{$val}','{$db_security_code}')";
            }
        }
        $sql    .= " (" . implode(',', $data) . ") ";
        $result = $this->getWriteConnection()->execute($sql);
        if (!$result)
        {
            throw new \Exception('添加失败');
        }

        return $this->getWriteConnection()->affectedRows();
    }

    /**
     * 统计-分组
     * @param      $condition
     * @param      $field
     * @param null $alias
     * @return array
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getCountGroupByField($condition, $field, $alias = null)
    {
        $result = $this->find(
            [
                'columns'    => "{$field},COUNT(1) as num",
                'conditions' => $this->getCondition($condition),
                'bind'       => $this->getBind($condition),
                'group'      => $alias ?: $field,
            ]
        );

        return $result ? $result->toArray() : [];
    }

    /**
     * 设置当前时间
     * @param string $format
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function setNow($format = "Y-m-d H:i:s")
    {
        $this->now = date($format);
    }

    /**
     * 获取当前时间
     * @param string $format
     * @return string
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function getNow($format = null)
    {
        if (empty($this->now || $format))
        {
            $this->setNow();
        }

        return $this->now;
    }

    /**
     * 默认值
     * @author zhanglibo <zhanglibo@xiaohe.com>
     */
    public function setDefaultValue()
    {
        if (property_exists($this, 'created') && empty($this->created))
        {
            $this->created = $this->getNow();
        }
        if (property_exists($this, 'modified') && empty($this->modified))
        {
            $this->modified = $this->getNow();
        }
        if (property_exists($this, 'is_usable') && empty($this->is_usable))
        {
            $this->is_usable = 1;
        }
        if (property_exists($this, 'is_delete') && empty($this->is_delete))
        {
            $this->is_delete = 2;
        }
    }


}