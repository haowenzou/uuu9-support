<?php

namespace Vpgame\Support\Environment;

class Compile
{
    /**
     * @var string 环境模板文件
     */
    private $envTpFilePath;

    /**
     * @var string 环境文件
     */
    private $envFilePath;

    /**
     * @var array 别名配置
     */
    private $aliases = [];

    public function __construct(string $envTpFilePath, string $envFilePath)
    {
        $this->envTpFilePath = $envTpFilePath;
        $this->envFilePath   = $envFilePath;
    }

    public function setAliases($aliases)
    {
        $this->aliases = $aliases;
    }

    /**
     * 编译开发环境配置
     *
     * @param string $env    环境变量名
     * @param array  $config 环境配置
     */
    public function run(string $env, array $config)
    {
        $this->checkEnv($env);

        $content = file_get_contents($this->envTpFilePath);

        if (empty($content)) {
            echo '环境配置文件不存在！';
            exit(1);
        }

        $content = $this->replace($content, $config);
        $content = preg_replace('#\[(.*?)\]#', '', $content);

        $result = file_put_contents($this->envFilePath, $content);

        echo false === $result
            ? '环境配置编译失败。'
            : '环境配置编译成功。';

        echo "\n";
    }

    /**
     * 从 zookeeper 获取配置信息
     *
     * @param string $filePath
     *
     * @return array
     */
    public function getZookeeperConfig($filePath)
    {
        if (!is_readable($filePath) ||
            !is_file($filePath))
        {
            echo sprintf('Unable to read the environment file at %s.', $filePath);
            exit(1);
        }

        $configs = (array)json_decode(file_get_contents($filePath), true);

        // 只要一个节点获取失败，就要终止编译
        if (empty($configs)) {
            echo 'the environment file is empty';
            exit(1);
        }

        $data = [];
        foreach ($configs as $key => $config) {

            $data[$key] = json_decode($config, true);
        }

        return $data;
    }

    /**
     * 检测环境变量
     *
     * @param string $env 环境变量名
     */
    private function checkEnv(string $env)
    {
        $envName = strtolower($env);

        $allowEnv = ['develop', 'testing', 'production'];

        if (!in_array($envName, $allowEnv, true)) {
            echo '只允许[develop, testing, production]参数错误。';
            exit(1);
        }
    }

    /**
     * 获取远程配置信息并处理替换
     *
     * @param string $content
     * @param array  $config
     *
     * @return mixed
     */
    private function replace(string $content, array $config)
    {
        //获取配置标签
        preg_match_all('#\[(.*?)\]#', $content, $envTag);
        if (empty($envTag[1])) {
            echo '获取配置标签不存在！';
            exit(1);
        }

        $tags = (array)$envTag[1];

        $aliases  = $this->getAlias();

        $patterns = [];
        $replaces = [];
        foreach ($tags as $tag) {

            if (empty($config[$tag])) {
                echo sprintf('[%s]配置不存在，请确认！', $tag);
                exit(1);
            }

            foreach ((array)$config[$tag] as $key => $value) {
                // 优先取别名
                if (!empty($aliases[$tag][$key])) {
                    $key = $aliases[$tag][$key];
                }

                $key = strtoupper($key);
                $patterns[] = sprintf('/\n%s.*/', $key);
                $replaces[] = sprintf("\n%s=%s", $key, $value);
            }
        }

        foreach ((array)$config['app'] as $key => $value) {

            $key = strtoupper($key);
            $patterns[] = sprintf('/\n%s.*/', $key);
            $replaces[] = sprintf("\n%s=%s", $key, $value);
        }

        if (!empty($patterns) &&
            !empty($replaces))
        {
            $content = preg_replace($patterns, $replaces, $content);
        }

        return $content;
    }

    /**
     * 设置别名
     * 解决 zookeeper 配置key 与 env 配置key不一致
     *
     * @return array
     */
    private function getAlias()
    {
        if (empty($this->aliases)) {

            $database = $this->getDatabaseAlias();
            $cache    = $this->getCacheAlias();

            $this->aliases = array_merge($database, $cache);
        }

        return $this->aliases;
    }

    /**
     * 设置数据库别名
     *
     * 解决 zookeeper 配置key 与 env 配置key不一致
     *
     * @return array
     */
    private function getDatabaseAlias()
    {
        $aliases = [
            '/config/mysqldb/vpgame' => [
                'username' => 'DB_USERNAME',
                'password' => 'DB_PASSWORD',
                'host'     => 'DB_HOST',
                'dbname'   => 'DB_DATABASE',
                'port'     => 'DB_PORT'
            ],
            '/config/mysqldb/economy' => [
                'username' => 'DB_BOCAI_USERNAME',
                'password' => 'DB_BOCAI_PASSWORD',
                'host'     => 'DB_BOCAI_HOST',
                'dbname'   => 'DB_BOCAI_DATABASE',
                'port'     => 'DB_BOCAI_PORT'
            ],
            '/config/mysqldb/logs' => [
                'username' => 'DB_LOGS_USERNAME',
                'password' => 'DB_LOGS_PASSWORD',
                'host'     => 'DB_LOGS_HOST',
                'dbname'   => 'DB_LOGS_DATABASE',
                'port'     => 'DB_LOGS_PORT'
            ],
            '/config/mysqldb/verification' => [
                'username' => 'DB_VERIFICATION_USERNAME',
                'password' => 'DB_VERIFICATION_PASSWORD',
                'host'     => 'DB_VERIFICATION_HOST',
                'dbname'   => 'DB_VERIFICATION_DATABASE',
                'port'     => 'DB_VERIFICATION_PORT'
            ],
            '/config/mysqldb/mission' => [
                'username' => 'DB_MISSION_USERNAME',
                'password' => 'DB_MISSION_PASSWORD',
                'host'     => 'DB_MISSION_HOST',
                'dbname'   => 'DB_MISSION_DATABASE',
                'port'     => 'DB_MISSION_PORT'
            ],
            '/config/mysqldb/social' => [
                'username' => 'DB_SOCIAL_USERNAME',
                'password' => 'DB_SOCIAL_PASSWORD',
                'host'     => 'DB_SOCIAL_HOST',
                'dbname'   => 'DB_SOCIAL_DATABASE',
                'port'     => 'DB_SOCIAL_PORT'
            ],
            '/config/mysqldb/roll' => [
                'username' => 'DB_ROLL_USERNAME',
                'password' => 'DB_ROLL_PASSWORD',
                'host'     => 'DB_ROLL_HOST',
                'dbname'   => 'DB_ROLL_DATABASE',
                'port'     => 'DB_ROLL_PORT'
            ]
        ];

        return $aliases;
    }

    /**
     * 设置缓存别名
     *
     * 解决 zookeeper 配置key 与 env 配置key不一致
     *
     * @return array
     */
    private function getCacheAlias()
    {
        $redisConfig = [
            'host'     => 'REDIS_HOST',
            'port'     => 'REDIS_PORT',
            'database' => 'REDIS_DATABASE',
            'password' => 'REDIS_PASSWORD',
            'cluster'  => 'REDIS_CLUSTER'
        ];

        $aliases = [
            '/config/memcache' => [
                'host'     => 'MEMCACHED_HOST',
                'port'     => 'MEMCACHED_PORT'
            ],
            '/config/redis/default' => $redisConfig,
            '/config/redis/queue'   => $redisConfig,
            '/config/redis/aly'     => $redisConfig,
        ];

        return $aliases;
    }
}