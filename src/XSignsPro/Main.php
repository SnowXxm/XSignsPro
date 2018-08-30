<?php

namespace XSignsPro;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class Main extends PluginBase
{
    private $version = '2.0.0';
    public $path;
    public $form;
    public $conf;
    public $msg;
    public $Eventlistener;
    public $signChange;
    public $signSet;


    public function onLoad()
    {
        ZXDA::init(703, $this);
        ZXDA::requestCheck();

        $this->path = $this->getDataFolder();
        @mkdir($this->path);
        @mkdir($this->getDataFolder() . 'Signs');
        foreach ($this->getServer()->getLevels() as $level) {
            $level_name = $level->getFolderName();
            @mkdir($this->getDataFolder() . "Signs/ {$level_name}");
        }
    }

    public function onEnable()
    {
        $this->setData();
        $this->checkUpdata();
        $this->registerEvents();
        $this->registerCommands();
        $this->getInfo();

        ZXDA::tokenCheck('MTQyMDExNzIzMzkzNjQxNzg1MzQwMTg2Njg2Nzk2MDg3MTU0ODM4NTQ0NjAzMTgxNzE0NjE2NDQ2ODk0MjYzNjgzOTk5NjUwNDczMDM2NzI2MTEwODE1ODIwOTY4OTEzNTI0NTM1NTg0NTYyOTM0ODE4MzkyNzI3NzUyNDgyNjAzMTk3MDkwOTg0MDE4NTEyODYxMDQ0MjAzNzQ4OTcyMzEzNDk2OTYyMjcwMDgxMTY0Nzk1Mzc4OTM2NDY0NTc3Mjc0MjM2OTkxMDM5Mzg0MTAzNTUzNjAyNzQwNzk4MjY2NzU4Njk4MQ==');
        $data = ZXDA::getInfo();
        if ($data['success']) {
            if (version_compare($data['version'], $this->getDescription()->getVersion()) > 0) {
                $this->getLogger()->info(TextFormat::GREEN . '检测到新版本,最新版:' . $data['version'] . ",更新日志:\n    " . str_replace("\n", "\n    ", $data['update_info']));
            }
        } else {
            $this->getLogger()->warning('更新检查失败:' . $data['message']);
        }
        if (ZXDA::isTrialVersion()) {
            $this->getLogger()->warning('当前正在使用试用版授权,试用时间到后将强制关闭服务器');
        }
        //继续加载插件

    }

    private function setData()
    {
        $this->conf = new Config($this->path . "Config.yml", Config::YAML, [
            'EnableAdmin' => false,
            'UnlockWorld' => ['zc'],
            'AdminList' => ['Your-Name'],
            "Version" => $this->version,
        ]);
        $this->msg = (new Config($this->path . "Message.yml", Config::YAML, [
            'SetSignMsg' => '§a没毛病老铁',
            'DelSignMsg' => '§e成功拆除',
            'UnsetSignMsg' => '§e然而你并没有权限这样做',
            'UnsetCmdMsg' => '§e你不写指令让我也很难做啊',
            'UnsetAnsMsg' => '§c这个问题还没有被设置，快去通知管理员设置吧！',
            'NotOpMsg' => '§c你没有权限拆除',
            'NotAdminMsg' => '§e腐竹开启了管理模式，你无法创立此木牌~',
            'CostMsg' => '§b执行这个操作是付费的，花费了',
            'BeOwnerMsg' => '§a你成为了这个箱子的主人',
            'UnsetChest' => '§a成功解锁这个箱子',
        ]))->getAll();
        $this->form = (new Config($this->path . 'Form.yml', Config::YAML, [
            'Title' => '§b§l§o［XSignsPro］',
            'C' => [
                '0' => '§f§o§l[§bCmd§f]',
                '2' => '§e*_        _*',
                '3' => '§7  ========  ',
            ],
            'O' => [
                '0' => '§f§o§l[§cOPCmd§f]',
                '2' => '§6*_        _*',
                '3' => '§7  ========  ',
            ],
            'H' => [
                '0' => '§f§o§l[§aHelps§f]',
                '2' => '§d*_        _*',
                '3' => '§7  ========  ',
            ],
            'L' => [
                '0' => '§f§o§l[§9Locked§f]',
                '2' => '§bOwner ',
            ],
            'M' => [
                '0' => '§a§o§l[§6MagicCmd§f]',
                '2' => '§9*_        _*',
                '3' => '§7  ========  ',
            ],
        ]))->getAll();
    }

    private function getInfo()
    {
        $this->getLogger()->info("§b -------------------------------");
        $this->getLogger()->info("§a XSignsPro v2.0.0§e>>> §6isLoaded!");
        $this->getLogger()->info("§d Made By §b[SnowXxm]");
        $this->getLogger()->info("§b -------------------------------");
    }

    private function registerEvents()
    {
        $this->Eventlistener = new EventListener($this);
    }

    private function registerCommands()
    {
        $this->getCommand("xsp")->setExecutor(new Commands($this));
    }

    private function checkUpdata()
    {
        $version = $this->conf->get("Version");
        if ($version !== $this->version) {
            $this->conf->set("Version", $this->version);
            $this->conf->save();
            $this->getLogger()->info("§b[XSignsPro]" . "§e［更新检测］>>>检测到您配置文件为旧版，已经自动为您升级配置文件");
        } else {
            $this->getLogger()->info("§b[XSignsPro]" . "§e［更新检测］>>>没有检测到旧版本配置文件，无需更新");
        }
    }


}

class ZXDA
{
    private static $_PID = false;
    private static $_TOKEN = false;
    private static $_PLUGIN = null;
    private static $_VERIFIED = false;
    private static $_API_VERSION = 5012;

    public static function init($pid, $plugin)
    {
        if (!is_numeric($pid)) {
            self::killit('参数错误,请传入正确的PID(0001)');
            exit();
        }
        self::$_PLUGIN = $plugin;
        if (self::$_PID !== false && self::$_PID != $pid) {
            self::killit('非法访问(0002)');
            exit();
        }
        self::$_PID = $pid;
    }

    public static function checkKernelVersion()
    {
        if (self::$_PID === false) {
            self::killit('SDK尚未初始化(0003)');
            exit();
        }
        if (!class_exists('\\ZXDAKernel\\Main', true)) {
            self::killit('请到 https://pl.zxda.net/ 下载安装最新版ZXDA Kernel后再使用此插件(0004)');
            exit();
        }
        $version = \ZXDAKernel\Main::getVersion();
        if ($version < self::$_API_VERSION) {
            self::killit('当前ZXDA Kernel版本太旧,无法使用此插件,请到 https://pl.zxda.net/ 下载安装最新版后再使用此插件(0005)');
            exit();
        }
        return $version;
    }

    public static function isTrialVersion()
    {
        try {
            self::checkKernelVersion();
            return \ZXDAKernel\Main::isTrialVersion(self::$_PID);
        } catch (\Exception $err) {
            @file_put_contents(self::$_PLUGIN->getServer()->getDataPath() . '0007_data.dump', var_export($err, true));
            self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
        }
    }

    public static function requestCheck()
    {
        try {
            self::checkKernelVersion();
            self::$_VERIFIED = false;
            self::$_TOKEN = sha1(uniqid());
            if (!\ZXDAKernel\Main::requestAuthorization(self::$_PID, self::$_PLUGIN, self::$_TOKEN)) {
                self::killit('请求授权失败,请检查PID是否已正确传入(0006)');
                exit();
            }
        } catch (\Exception $err) {
            @file_put_contents(self::$_PLUGIN->getServer()->getDataPath() . '0007_data.dump', var_export($err, true));
            self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
        }
    }

    public static function tokenCheck($key)
    {
        try {
            self::checkKernelVersion();
            self::$_VERIFIED = false;
            $manager = self::$_PLUGIN->getServer()->getPluginManager();
            if (!($plugin = $manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main) {
                self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
            }
            if (!$manager->isPluginEnabled($plugin)) {
                $manager->enablePlugin($plugin);
            }
            $key = base64_decode($key);
            if (($token = \ZXDAKernel\Main::getResultToken(self::$_PID)) === false) {
                self::killit('请勿进行非法破解(0009)');
            }
            if (self::rsa_decode(base64_decode($token), $key, 768) != sha1(strrev(self::$_TOKEN))) {
                self::killit('插件Key错误,请更新插件或联系作者(0010)');
            }
            self::$_VERIFIED = true;
        } catch (\Exception $err) {
            @file_put_contents(self::$_PLUGIN->getServer()->getDataPath() . '0007_data.dump', var_export($err, true));
            self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
        }
    }

    public static function isVerified()
    {
        return self::$_VERIFIED;
    }

    public static function getInfo()
    {
        try {
            self::checkKernelVersion();
            $manager = self::$_PLUGIN->getServer()->getPluginManager();
            if (!($plugin = $manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main) {
                self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
            }
            if (($data = \ZXDAKernel\Main::getPluginInfo(self::$_PID)) === false) {
                self::killit('请勿进行非法破解(0009)');
            }
            if (count($data = explode(',', $data)) != 2) {
                return array(
                    'success' => false,
                    'message' => '未知错误');
            }
            return array(
                'success' => true,
                'version' => base64_decode($data[0]),
                'update_info' => base64_decode($data[1]));
        } catch (\Exception $err) {
            @file_put_contents(self::$_PLUGIN->getServer()->getDataPath() . '0007_data.dump', var_export($err, true));
            self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
        }
    }

    public static function killit($msg)
    {
        if (self::$_PLUGIN === null) {
            echo('抱歉,插件授权验证失败[SDK:' . self::$_API_VERSION . "]\n附加信息:" . $msg);
        } else {
            @self::$_PLUGIN->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:' . self::$_API_VERSION . ']');
            @self::$_PLUGIN->getLogger()->warning('§e附加信息:' . $msg);
            @self::$_PLUGIN->getServer()->forceShutdown();
        }
        exit();
    }

    //RSA加密算法实现
    public static function rsa_encode($message, $modulus, $keylength = 1024, $isPriv = true)
    {
        $result = array();
        while (strlen($msg = substr($message, 0, $keylength / 8 - 5)) > 0) {
            $message = substr($message, strlen($msg));
            $result[] = self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg, $isPriv, $keylength / 8)), '65537', $modulus), $keylength / 8);
            unset($msg);
        }
        return implode('***&&&***', $result);
    }

    public static function rsa_decode($message, $modulus, $keylength = 1024)
    {
        $result = array();
        foreach (explode('***&&&***', $message) as $message) {
            $result[] = self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message), '65537', $modulus), $keylength / 8), $keylength / 8);
            unset($message);
        }
        return implode('', $result);
    }

    private static function pow_mod($p, $q, $r)
    {
        $factors = array();
        $div = $q;
        $power_of_two = 0;
        while (bccomp($div, '0') == 1) {
            $rem = bcmod($div, 2);
            $div = bcdiv($div, 2);
            if ($rem) {
                array_push($factors, $power_of_two);
            }
            $power_of_two++;
        }
        $partial_results = array();
        $part_res = $p;
        $idx = 0;
        foreach ($factors as $factor) {
            while ($idx < $factor) {
                $part_res = bcpow($part_res, '2');
                $part_res = bcmod($part_res, $r);
                $idx++;
            }
            array_push($partial_results, $part_res);
        }
        $result = '1';
        foreach ($partial_results as $part_res) {
            $result = bcmul($result, $part_res);
            $result = bcmod($result, $r);
        }
        return $result;
    }

    private static function add_PKCS1_padding($data, $isprivateKey, $blocksize)
    {
        $pad_length = $blocksize - 3 - strlen($data);
        if ($isprivateKey) {
            $block_type = "\x02";
            $padding = '';
            for ($i = 0; $i < $pad_length; $i++) {
                $rnd = mt_rand(1, 255);
                $padding .= chr($rnd);
            }
        } else {
            $block_type = "\x01";
            $padding = str_repeat("\xFF", $pad_length);
        }
        return "\x00" . $block_type . $padding . "\x00" . $data;
    }

    private static function remove_PKCS1_padding($data, $blocksize)
    {
        assert(strlen($data) == $blocksize);
        $data = substr($data, 1);
        if ($data{0} == '\0') {
            return '';
        }
        assert(($data{0} == "\x01") || ($data{0} == "\x02"));
        $offset = strpos($data, "\0", 1);
        return substr($data, $offset + 1);
    }

    private static function binary_to_number($data)
    {
        $radix = '1';
        $result = '0';
        for ($i = strlen($data) - 1; $i >= 0; $i--) {
            $digit = ord($data{$i});
            $part_res = bcmul($digit, $radix);
            $result = bcadd($result, $part_res);
            $radix = bcmul($radix, '256');
        }
        return $result;
    }

    private static function number_to_binary($number, $blocksize)
    {
        $result = '';
        $div = $number;
        while ($div > 0) {
            $mod = bcmod($div, '256');
            $div = bcdiv($div, '256');
            $result = chr($mod) . $result;
        }
        return str_pad($result, $blocksize, "\x00", STR_PAD_LEFT);
    }
}


?>