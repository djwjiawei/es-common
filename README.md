# easyswoole基础包

## 安装包
```
1. 在composer.json中添加该配置
"repositories": [
    {
        "type": "git",
        "url": "git@git.dev.enbrands.com:X/php/interaction/easyswoole_common.git"
    }
]

2. 执行composerrequire
composer require es-swoole/common:(dev-master或具体tag)
```

## 已开发功能
- [x] 日志格式化
- [x] 请求链路日志记录
- [x] 异常处理
- [x] 异常邮件报告和钉钉报告
- [x] http请求
- [x] 邮件发送
- [x] config包发布
- [x] command加载
- [x] 服务提供者功能
- [x] 一些公共函数
- [x] 向指定进程同步信息
- [x] 中间件
- [x] 代码格式校验和修复

## 使用步骤
1. 安装easyswoole
2. 在dev/produce.php中添加APP_ENV='dev/test/prod' 用于标识当前启动服务是开发/测试/生产 环境
3. 安装本包(composer require es-swoole/common:dev-master或具体tag)
4. 在根目录的bootstrap文件中添加:
\EsSwoole\Base\Provider\CommandLoad::getInstance()->load(); 用于加载vendor包的command
5. 执行php easyswoole publish:config --vendor=es-swoole/common 发布本包配置
6. 修改发布的配置（异常和邮件配置）
7. 在EasySwooleEvent的initialize方法中添加ServiceProvider::getInstance()->registerVendor(); 用户初始化vendor包的服务提供者
8. 在EasySwooleEvent中注入mysql和redis的配置


## 具体使用方法
```php
//1. 控制器继承BaseHttpController, 用于记录请求进来和结束的日志记录
$this->success([]);
$this->fail('msg');

//2. log记录(具体info/error...方法是记录日志级别,方法中第一个参数是记录的消息,第二个参数是文件名)
\EasySwoole\EasySwoole\Logger::getInstance()->info("test-log",'filename');
\EasySwoole\EasySwoole\Logger::getInstance()->error("test-log",'filename');

//3. 邮件发送
/**
 * @param $subject string 主题
 * @param $body string 邮件体
 * @param $to string|array 如果是多元素数组则发送多个人
 * @param bool $isBatch 如果是多个人的话,true代表同一批发送,false代表分批次发送
 * @param string $connection 连接配置config("esCommon.mail.{$connection}")
*/
//同步发送
\EsSwoole\Base\Common\Mail::syncSendMail('subject','body','dongjw.1@jifenn.com',true,'default');
//异步发送
\EsSwoole\Base\Common\Mail::asyncSendMail('subject','body','dongjw.1@jifenn.com',true,'default');
//根据进程信息自动选择同步或异步发送
\EsSwoole\Base\Common\Mail::smartSend('subject','body','dongjw.1@jifenn.com',true,'default');

//4. config包
//在需要添加config配置文件时,添加在项目根目录下的Config目录中
//比如在Config目录下创建了database.php的文件: return['host' => '127.0.0.1']
//获取该配置为config('database.host')

//5. http请求
//有需要调用第三方接口的,可以继承\EsSwoole\Base\Request\AbstractRequest该类,请求结束会自动记录响应日志

//6. 进程通信
//向worker进程发消息
\EsSwoole\Base\Common\ProcessSync::syncWorker('',0);
//向task进程发消息
\EsSwoole\Base\Common\ProcessSync::syncTask('',0);
//向自定义进程发消息
\EsSwoole\Base\Common\ProcessSync::syncCustomProcess('',100);
//通过进程id向指定进程发消息
\EsSwoole\Base\Common\ProcessSync::syncByPid('',100);
//向全部进程发消息
\EsSwoole\Base\Common\ProcessSync::syncAllProcess('');

//7. 中间件(在middleware配置文件中添加对应的中间件)
//支持1. 全局中间件(*) 2.路由完全匹配中间件 3.路由正则中间件(/index/*/test; /index*; /index/te*/dong$)
```

## 代码格式校验和修复
```
1. 引入该包后，如果需要使用代码检测和修复 需要在composer.json修改如下：
"repositories": [
    {
        "type": "git",
        "url": "git@git.dev.enbrands.com:php-team/coding-standard.git"
    }
]
dev 中引入：
"require-dev": {
    "enbrands/coding-standard": "dev-master",
    "squizlabs/php_codesniffer": "^3.6"
}

2. 代码格式检测命令：
公共包仓库检测的时候用bin/es phpcs 检查路径(默认为App或src)
项目仓库检查的时候可以用vendor/bin/es phpcs 检查路径(默认为App或src)

3. 代码格式修复命令：
公共包仓库检测的时候用bin/es phpcbf 修复路径(默认为App或src)
项目仓库检查的时候可以用vendor/bin/es phpcbf 修复路径(默认为App或src)
```