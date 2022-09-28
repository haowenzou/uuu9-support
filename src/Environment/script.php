<?php
/**
 * 编译脚本
 *
 * 执行规则：
 * 1、在项目根目录下，生成脚本文件 script/compile.php
 * 2、在项目根目录下，生成配置文件 zookeeper-config.json
 * 2、执行 php -f script/compile.php develop|testing|production
 */

require_once __DIR__ . '/../vendor/autoload.php';

if ($argc < 2) {
    echo '参数错误。';
    exit(1);
}

$rootPath = dirname(__DIR__) . DIRECTORY_SEPARATOR;

$zkConfigFilePath = $rootPath . 'zookeeper-config.json';
$envTpFilePath    = $rootPath . '.env.tp';
$envFilePath      = $rootPath . '.env';

$appConfig = [
    'develop' => [
        'app_debug'   => 'true',
        'app_env'     => 'develop',
        'mail_enable' => 'false'
    ],
    'testing' => [
        'app_debug'   => 'false',
        'app_env'     => 'testing',
        'mail_enable' => 'false'
    ],
    'production' => [
        'app_debug'   => 'false',
        'app_env'     => 'production',
        'mail_enable' => 'true'
    ],
];

$compile = new U9\Support\Environment\Compile($envTpFilePath, $envFilePath);

$config = $compile->getZookeeperConfig($zkConfigFilePath);

$config['app'] = $appConfig[$argv[1]] ?? $appConfig['production'];

// 自定义别名
// $compile->setAliases($aliases);

$compile->run($argv[1], $config);