## 依赖于yii的基于缓存的访问频率控制

### 相关说明

- 访问频率控制,多少秒内可以访问多少次(访问频率) 默认的规则是 5分钟可以访问1次. 
- 该类依赖于Yii内置的缓存机制 只能用于yii 默认情况下使用的 Yii::$app->cache 缓存对象.
- 可以自定义缓存对象 但必须是 \yii\caching\Cache的一个实例 否则还是会使用Yii::$app->cache缓存对象

### 使用

```php
<?php

// demo1: 用户登陆 要求一个用户在半个小时内 如果错误次数达到3次则不能登陆 基于ip地址的访问控制或者基于session_id
//AccessLimit 构造函数的第一个参数是 规则数组 规则数组由2部分组成 max_count 最大访问次数 max_second 最大访问时间
// 规则数组的意思是 在max_second秒内最多可以访问max_count次
$accessLimit = new AccessLimit(['max_count' => '3', 'max_second' => 1800]);
$module = 'address';
if ($accessLimit->check() === false) {
    //跳出逻辑
    exit('你在半小时内错误已达3次,请稍后再试');
}
if (账号或密码错误) {
    $accessLimit->addModuleRecord($module, '192.168.1.1');
} else {
    //登陆成功 删除访问次数信息
    $accessLimit->removeModuleRecord($module, '192.168.1.1');
}

//demo2: 用户30秒内只能发表一次评论
$accessLimit = new AccessLimit();
//也可以这样添加规则规则数组 
$accessLimit->setRules(['max_count' => '1', 'max_second' => 30]);
$module = 'article.reply.uid';
if ($accessLimit->check() === false) {
     //跳出逻辑
     exit('说话太快,你在30秒内只能发表1次评论,请稍后再试');
}
if (用户发表评论成功) {
    $accessLimit->addModuleRecord($module, $uid);
}
 
//demo3: 自定义缓存
//默认情况下使用的 Yii::$app->cache 缓存对象 具体使用的是什么缓存 Redis MemCache FileCache 依赖于具体的配置文件
//如果想自定义缓存 可以通过构造函数的第三个参数
use yii\caching\FileCache;

$cache = new FileCache();
$accessLimit = new AccessLimit('', '', $cache);
 
//或者这么干使用setCache
use yii\caching\FileCache;

$accessLimit = new AccessLimit();
$cache = new FileCache();
$accessLimit->setCache($cache);

```