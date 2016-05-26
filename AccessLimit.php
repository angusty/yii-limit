<?php
namespace Angusty\Limit;

use Yii;
use yii\caching\Cache;

/**
 * 通用基于缓存的访问控制 基于这样的应用 多少秒内可以访问多少次(访问频率) 默认的规则是 5分钟可以访问1次
 * 该类依赖于Yii内置的缓存机制 只能用于yii 默认情况下使用的 Yii::$app->cache 缓存对象
 * 也可以自定义缓存对象 但必须是 \yii\caching\Cache的一个实例 否则还是会使用Yii::$app->cache缓存对象
 *
 * @package Angusty\Limit
 *
 * @author angusty
 */
class AccessLimit
{
    /**
     * @var string $keyPrefix cache key 前缀
     */
    private $keyPrefix = 'angusty.yii-limit.access-limit';

    /**
     * @var string $separator 前缀分隔符
     */
    private $separator = '@';

    /**
     * @var string $module 模块名称
     */
    private $module;

    /**
     * @var string $key 模块下面的可以唯一标识其身份的信息
     */
    private $key;

    /**
     * @var integer $recordCount 记录次数 从0开始
     */
    private $recordCount = 0;

    /**
     * @var integer $maxCount 允许最大的访问次数 默认最大访问次数是1次
     */
    private $maxCount = '1';

    /**
     * @var integer $maxSecond 多少时间内允许访问 默认允许的访问时间是 300秒 5分钟
     */
    private $maxSecond = '300';

    /**
     * @var array $rules 规则数组
     */
    private $rules = [];

    /**
     * @var object $cache
     */
    private $cache;

    /**
     * 构造函数
     *
     * @param string|array $rules 规则 为空则是默认规则
     * @param string $keyPrefix 缓存键值前缀
     * @param \yii\caching\Cache $cache|null $cache 默认为null 为空则使用 Yii::$app->cache对象
     *
     */
    public function __construct($rules = '', $keyPrefix = '', $cache = null)
    {
        //访问规则 多少时间内 访问多少次
        $this->setRules($rules);
        //传入了前缀则使用传入的前缀 否则使用默认的前缀
        $this->setkeyPrefix($keyPrefix);
        if ($cache instanceof Cache) {
            $this->setCache($cache);
        } else {
            $this->setCache(Yii::$app->cache);
        }
        return $this;
    }

    /**
     * 设置缓存的方法
     *
     * @param \yii\caching\Cache $cache
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * 设置最大访问次数
     *
     * @param integer $maxCount
     *
     * @return $this
     */
    public function setMaxCount($maxCount)
    {
        $maxCount = (string)$maxCount;
        if (ctype_digit($maxCount)) {
            $this->maxCount = $maxCount;
        }
        return $this;
    }

    /**
     * 获取最大访问次数
     *
     * @return int
     */
    public function getMaxCount()
    {
        return $this->maxCount;
    }

    /**
     * 设置最大访问时间 单位秒
     *
     * @param integer $maxSecond
     *
     * @return $this
     */
    public function setMaxSecond($maxSecond)
    {
        $maxSecond = (string)$maxSecond;
        if (ctype_digit($maxSecond)) {
            $this->maxSecond = $maxSecond;
        }
        return $this;
    }

    /**
     * 获取最大访问时间 单位秒
     *
     * @return int
     */
    public function getMaxSecond()
    {
        return $this->maxSecond;
    }

    /**
     * 获取缓存的方法
     *
     * @return object|Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * 设置默认前缀
     *
     * @param string $keyPrefix 默认前缀
     *
     * @return $this
     */
    public function setkeyPrefix($keyPrefix)
    {
        //传入了前缀则使用传入的前缀 否则使用默认的前缀
        if (!empty($keyPrefix)) {
            $this->keyPrefix = $keyPrefix;
        }
        return $this;
    }

    /**
     * 获取默认前缀
     *
     * @return string
     */
    public function getkeyPrefix()
    {
        return $this->keyPrefix;
    }

    /**
     * 添加一个规则 不添加规则则使用默认规则 默认规则是5分钟可以访问1次
     *
     * @param array $rule
     *
     * @return $this;
     */
    public function setRules($rule)
    {
        $rule = (array)$rule;
        //最大访问次数 $max_count 次
        if (isset($rule['max_count'])) {
            $rule['max_count'] = (string)$rule['max_count'];
            if (ctype_digit(($rule['max_count']))) {
                $this->maxCount = $rule['max_count'];
            }
        }
        //最大访问时间
        if (isset($rule['max_second'])) {
            $rule['max_second'] = (string)$rule['max_second'];
            if (ctype_digit(($rule['max_second']))) {
                $this->maxSecond = $rule['max_second'];
            }
        }
        $this->rules = [
            'max_count' => $this->maxCount,
            'max_second' => $this->maxSecond
        ];
        return $this;
    }


    /**
     *  获取访问规则
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * 从缓存中获取当前计数器次数
     *
     * @return int
     */
    public function getRecordCount()
    {
        $recordCount = $this->cache->get($this->module);
        //缓存不存在则为默认值$this->recordCount 存在则为缓存中的值
        $recordCount === false && $recordCount = $this->recordCount;
        $this->recordCount = $recordCount;
        return $this->recordCount;
    }

    /**
     * 设置模块
     *
     * @param string $module 模块名 用于分组 比如 mobile
     * @param string $key 模块下面的唯一可以标识其身份的值
     *
     * @return $this
     */
    public function setModule($module, $key)
    {
        $module = trim($module);
        $key = trim($key);
        $this->key = $key;
        $this->module = rtrim($this->keyPrefix, $this->separator)
            . $this->separator
            . rtrim($module, $this->separator)
            . $this->separator . $key;
        return $this;
    }

    /**
     * 获取一个模块
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module;
    }

    /**
     * 检查是否允许访问
     *
     * @return bool 允许访问返回true 不允许访问返回false
     */
    public function check()
    {
        $count = $this->getRecordCount();
        if ($count > $this->rules['max_count']) {
            return false;
        }
        //时间过期 或者统计次数小于最大次数 都返回true
        return true;
    }

    /**
     * 添加一个模块 记录一次访问次数 此模块会使用内部的计数器自增
     *
     * @param string $module 模块名 一般代表其属于某一个应用 模块名可以自定义 比如 mobile  email 等
     * @param string $key 具体的某一个唯一的键值 键值一般是标识模块名的唯一标识 比如 手机号 邮箱号 用户名 等等
     *
     * @return bool|int|mixed 添加成功返回 计数器的次数 添加失败 返回false
     */
    public function addModuleRecord($module, $key)
    {
        $this->setModule($module, $key);
        if (false === $this->check()) {
            return false;
        }
        $count = $this->getRecordCount();
        //验证成功 自增1
        $count++;
        $bool = $this->cache->set($this->module, $count, $this->maxSecond);
        //添加缓存成功返回true
        if ($bool) {
            $this->recordCount = $count;
            return $this->recordCount;
        } else {
            return false;
        }
    }

    /**
     * 移除一个模块
     *
     * @param string $module 模块名属于哪一个分组
     * @param string $key 具体的某一个唯一的键值
     *
     * @return bool
     */
    public function removeModuleRecord($module, $key)
    {
        $this->setModule($module, $key);
        return $this->cache->delete($this->module);
    }
}
