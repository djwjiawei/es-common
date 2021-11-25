# easyswoole基础包

## 安装包
```
1. 在composer.json中添加该配置
"repositories": [
    {
        "type": "git",
        "url": "git@git.dev.enbrands.com:ebs/php/easyswoole_common.git"
    }
]

2. 执行composerrequire
composer require es-swoole/common:(dev-master或具体tag)
```

## 开发计划
- [x] 日志格式化
- [x] 请求链路日志记录
- [x] 异常处理
- [x] http请求
- [x] 邮件发送
- [x] config包发布
- [x] 服务提供者功能
- [x] 一些公共函数
- [ ] 协程异常处理
- [ ] nacos集成

## 使用步骤
1. 安装easyswoole
2. 在dev/produce.php中添加APP_ENV='dev/test/prod' 用于标识当前启动服务是开发/测试/生产 环境
3. 安装本包(composer require es-swoole/common:dev-master或具体tag)
4. 在根目录的bootstrap文件中添加:
\EasySwoole\Command\CommandManager::getInstance()->addCommand(new \EsSwoole\Base\Command\PublishConfig());  用于发布vendor包配置 (命令为php easyswoole publish:config --vendor=包名)
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
```