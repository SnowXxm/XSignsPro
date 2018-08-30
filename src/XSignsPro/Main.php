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

?>
