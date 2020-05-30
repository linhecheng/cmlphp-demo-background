<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 实体
 * *********************************************************** */

namespace Cml\Entity;

use Cml\Cml;
use Cml\Db\Query;
use Cml\Exception\EntityNotFoundException;
use Cml\Interfaces\ArrayAble;
use Cml\Interfaces\Jsonable;
use ArrayAccess;
use Exception;
use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use Cml\Tools\ArrayJsonSpl;
use JsonSerializable;
use Cml\Lang;
use Cml\Model\Base;
use function Cml\studlyCase;

/**
 * 数据库实体实现，快捷方法不可用
 *
 * @mixin Query
 * @package Cml\Db
 */
class Entity implements ArrayAccess, ArrayAble, IteratorAggregate, Jsonable, JsonSerializable
{
    use Base;
    use ArrayJsonSpl;
    use OverrideDbMethod;
    use Relation;

    /**
     * 自动写入插入时间
     *
     * @var bool|int
     */
    protected $autoCreateTime = false;//'ctime';

    /**
     * 是否自动写入更新时间
     *
     * @var bool|int
     */
    protected $autoUpdateTime = false;//'utime';

    /**
     * 需要更新的字段
     *
     * @var array
     */
    private $needUpdateField = [];

    /**
     * save方法是新增还是修改
     *
     * @var bool
     */
    private $itemIsExists = false;

    /**
     * 关联数据缓存
     *
     * @var array
     */
    private $relateData = [];

    /**
     * 获取多条数据
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return static[]|Collection
     */
    public function select($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false)
    {
        return $this->__call('select', func_get_args());
    }

    /**
     * 分页获取数据
     *
     * @param int $limit 每页返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param null|int $page 当前页数-不传则获取配置中var_page配置的request值
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return static[]|Collection
     */
    public function paginate($limit, $useMaster = false, $page = null, $fieldAsKey = false)
    {
        return $this->__call('paginate', func_get_args());
    }

    /**
     * 获取一条数据
     *
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return static | bool
     */
    public function getOne($useMaster = false)
    {
        return $this->__call('getOne', func_get_args());
    }

    /**
     * 实体查询join相关语句通过配置关联关系来操作
     *
     * @param array $items 初始化赋值
     *
     * Entity constructor.
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * 返回一个全新的实例
     *
     * @param mixed $items
     *
     * @return static;
     */
    public static function make(array $items = [])
    {
        return new static($items);
    }

    /**
     * 获取某个字段的值
     *
     * @param string $field 字段
     *
     * @return mixed
     */
    public function __get($field)
    {
        if (empty($this->items)) {
            return null;
        }

        $value = $this->items[$field] ?? null;

        if (!$value) {
            $relation = studlyCase($field, false);
            if (isset($this->relateData[$relation])) {
                $value = $this->relateData[$relation];
            } else if (method_exists($this, $relation) && !method_exists(Entity::class, $relation)) {
                $value = $this->$relation()->getRelation();
                $this->relateData[$relation] = $value;
            }
        }

        $value = $this->triggerGetterAttribute($field, $value);

        if (is_null($value) && !array_key_exists($field, $this->items)) {
            throw new InvalidArgumentException(Lang::get('_NOT_FOUND_', $field));
        }

        return $value;
    }

    /**
     * 调用获取器
     *
     * @param string $field
     * @param mixed $value
     *
     * @return mixed
     */
    private function triggerGetterAttribute($field, $value)
    {
        $getter = "get" . studlyCase($field) . 'Attribute';
        method_exists($this, $getter) && $value = $this->$getter($value);
        return $value;
    }

    /**
     * 更新字段
     *
     * @param string $field 字段
     * @param mixed $value
     */
    public function __set($field, $value)
    {
        $setter = "set" . studlyCase($field) . 'Attribute';
        method_exists($this, $setter) && $value = $this->$setter($value);

        $this->needUpdateField[$field] = 1;
        $this->items[$field] = $value;
    }

    /**
     * 设置一个值-不触发更新
     *
     * @param string $field
     * @param mixed $value
     *
     * @return static
     */
    public function setItem($field, $value)
    {
        $this->items[$field] = $value;
        return $this;
    }

    /**
     * 检测数据对象的值
     *
     * @param string $field 字段
     *
     * @return bool
     */
    public function __isset($field)
    {
        return array_key_exists($field, $this->items);
    }

    /**
     * 销毁数据对象的值
     *
     * @param string $field 字段
     *
     * @return void
     */
    public function __unset($field)
    {
        unset($this->items[$field], $this->needUpdateField[$field]);
    }

    /**
     * 根据主键删除
     *
     * @param mixed $val 要删除的值
     * @param mixed $field 条件字段-不传自动获取主键
     *
     * @return bool
     */
    public static function destroy($val, $field = null)
    {
        if (!$val) {
            throw new InvalidArgumentException($val);
        }
        $inst = static::make();
        is_null($field) && $field = $inst->getPrimaryKey();
        is_array($val) ? $inst->whereIn($field, $val) : $inst->where($field, $val);
        return $inst->delete();
    }

    /**
     * 根据主键获取数据
     *
     * @param int $val
     * @param mixed $field 条件字段-不传自动获取主键
     *
     * @return static
     */
    public static function find($val = null, $field = null)
    {
        if (!$val) {
            throw new InvalidArgumentException('$val');
        }
        $inst = static::make();
        $inst->where($field ?: $inst->getPrimaryKey(), $val);

        $item = $inst->getOne();

        if (!$item) {
            return null;
        }

        return $item;
    }

    /**
     * 获取多条数据
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     * @param mixed $fieldAsKey 返回以某个字段做为key的数组
     *
     * @return static[]|Collection
     */
    public static function findMany($offset = null, $limit = null, $useMaster = false, $fieldAsKey = false)
    {
        return static::make()->select(...func_get_args());
    }

    /**
     * 获取给定查询的生成器
     *
     * @param int $offset 偏移量
     * @param int $limit 返回的条数
     * @param bool $useMaster 是否使用主库 默认读取从库
     *
     * @return Generator
     */
    public static function cursor($offset = null, $limit = null, $useMaster = false)
    {
        $inst = static::make();
        return $inst->db($inst->getDbConf())->cursor(...func_get_args());
    }

    /**
     * 根据主键获取数据-不存在则抛出异常
     *
     * @param int $val
     * @param mixed $field 条件字段-不传自动获取主键
     *
     * @return static
     * @throws EntityNotFoundException
     *
     */
    public static function findOrFail($val = null, $field = null)
    {
        $data = static::find($val, $field);
        if (is_null($data)) {
            throw new EntityNotFoundException($val);
        }
        return $data;
    }

    /**
     * save操作是否为update
     *
     * @param bool $isUpdate true为update false为insert
     * @param bool $isRead 是否为数据库读取回来
     *
     * @return static
     */
    public function itemIsExists($isUpdate = true, $isRead = false)
    {
        $this->itemIsExists = $isUpdate;
        $isRead && $this->triggerHandler('onAfterFetchHandler');
        return $this;
    }

    /**
     * 保存数据
     *
     * @param array $item 数据
     * @param bool $startTransAction 是否使用事务
     *
     * 更新时，配合::where()->save([])使用，新增直接用create方法
     *
     * @return int 返回新增的id/更新的条数
     */
    public function save($item = [], $startTransAction = true)
    {
        $save = function () use ($item, $startTransAction) {
            $startTransAction && $this->startTransAction();
            try {
                if ($item || $this->itemIsExists) {
                    if (false === $this->triggerHandler('onBeforeUpdateHandler')) {
                        return false;
                    }
                    $item && $this->fill($item);
                    $data = array_intersect_key($this->items, $this->needUpdateField);
                    $this->autoUpdateTime && $data[$this->autoUpdateTime] = Cml::$nowTime;

                    $pk = $this->getPrimaryKey();
                    $this->items[$pk] && $this->where($pk, $this->items[$pk]);
                    $result = $this->update($data);
                } else {
                    if (false === $this->triggerHandler('onBeforeInsertHandler')) {
                        return false;
                    }
                    $this->itemIsExists(true);
                    $data = $this->items;
                    $this->autoCreateTime && $data[$this->autoCreateTime] = Cml::$nowTime;
                    $result = $this->insert($data);
                    $result && $this->items[$this->getPrimaryKey()] = $result;
                }
                $this->writeWithSave();
                $startTransAction && $this->commit();
                return $result;
            } catch (Exception $e) {
                $startTransAction && $this->rollBack();
                throw $e;
            }
        };
        return $save();
    }

    /**
     * 保存数据-多条
     *
     * @param array $entityArray 集合数组
     *
     * @return Collection
     */
    public function saveMulti(array $entityArray)
    {
        $result = new Collection();
        $this->startTransAction();
        try {
            foreach ($entityArray as $entity) {
                $entity->save([], false);
                $result->push($entity);
            }
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
        }
        return $result;
    }

    /**
     * 新增
     *
     * @param array $item
     *
     * @return static
     */
    public static function create(array $item)
    {
        $inst = self::make($item);
        $inst->save();
        return $inst;
    }

    /**
     * 填充
     *
     * @param array $items
     *
     * @return static
     */
    public function fill(array $items)
    {
        foreach ($items as $key => $item) {
            $this->{$key} = $item;//触发修改器
        }

        return $this;
    }

    /**
     * 删除
     *
     * @return mixed
     */
    public function remove()
    {
        $remove = function () {
            try {
                if (false === $this->triggerHandler('onBeforeDeleteHandler')) {
                    return false;
                }

                $this->startTransAction();
                $pk = $this->getPrimaryKey();
                $result = $this->where($pk, $this->items[$pk])->delete();

                $this->writeWithDelete();

                $this->commit();
                return $result;
            } catch (Exception $e) {
                $this->rollBack();
                throw $e;
            }
        };
        return $remove();
    }

    /**
     * 重置
     *
     * @param array $items 初始化赋值
     *
     * @return static
     */
    public function reset($items = [])
    {
        $this->items = $items;
        $this->itemIsExists = false;
        $this->needUpdateField = [];
        $this->relateData = [];
        return $this;
    }

    /**
     * __debugInfo
     *
     * @return array
     */
    public function __debugInfo()
    {
        return $this->items;
    }

    /**
     * 自动维护时间戳
     *
     * @param string $createTimeField
     * @param string $updateTimeField
     *
     * @return $this
     */
    public function withTimestamps($createTimeField = 'ctime', $updateTimeField = 'utime')
    {
        $this->autoCreateTime = $createTimeField;
        $this->autoUpdateTime = $updateTimeField;

        return $this;
    }

    /**
     * 触发处理器
     *
     * @param $handler
     *
     * @return bool
     */
    private function triggerHandler($handler)
    {
        if (method_exists($this, $handler)) {
            return call_user_func_array([$this, $handler], []) === false ? false : true;
        } else {
            return true;
        }
    }

    /**
     * 当前集合转换成数组返回
     *
     * @param bool $transform 是否自动使用获取器转换
     *
     * @return array
     */
    public function toArray($transform = true)
    {
        $return = [];
        foreach ($this->items as $key => $value) {
            /**
             * @var static $value
             */
            $return[$key] = $value instanceof ArrayAble ? $value->toArray($transform) : ($transform ? $this->triggerGetterAttribute($key, $value) : $value);
        }
        return $return;
    }

    /**
     * 将元素转换为可序列化JSON
     *
     * @param bool $transform 是否自动使用获取器转换
     *
     * @return array
     */
    public function jsonSerialize($transform = true)
    {
        $return = [];
        foreach ($this->items as $key => $value) {
            /**
             * @var static $value
             */
            if ($value instanceof JsonSerializable) {
                $return[$key] = $value->jsonSerialize($transform);
            } elseif ($value instanceof Jsonable) {
                $return[$key] = json_decode($value->toJson($transform), true);
            } else if ($value instanceof ArrayAble) {
                $return[$key] = $value->toArray($transform);
            } else {
                $return[$key] = ($transform ? $this->triggerGetterAttribute($key, $value) : $value);
            }

        }
        return $return;
    }

    /**
     * 获取元素转换成json的结果
     *
     * @param bool $transform 是否自动使用获取器转换
     * @param int $options
     *
     * @return false|string
     */
    public function toJson($transform = true, $options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->jsonSerialize($transform), $options);
    }

    /**
     * 获取一个元素
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->$key;
    }

    /**
     * 设置一个元素
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->$key = $value;
    }
}
