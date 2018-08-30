<?php

namespace XSignsPro;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandExecutor;


class Commands extends PluginBase implements CommandExecutor
{

    public $plugin;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        if (!$sender instanceof Player || !$sender->isOp()) {
            $sender->sendMessage($this->plugin->form['Title'] . '§b不能在后台用喔');
            return true;
        }
        if (!isset($args[0])) return $sender->sendMessage($this->plugin->form['Title']."指令有误，请查看帮助 /xsp help");
        switch ($args[0]) {
        
            case "help":
                $helpList = [
                    '§a------- §f[§eXSignsPro§f] -------',
                    '§b /xsp s <第一行> <第二行> <第三行> <第四行>',
                    '§b /xsp c <行数(0-3)> <内容>',
                    '§9------------------------------------',
                    '§6* 请使用@代替空格',
                ];
                foreach ($helpList as $help) $sender->sendMessage($help);
                break;

            case "s":
                if (isset($args[0]) === true && isset($args[1]) === true && isset($args[2]) === true && isset($args[3]) === true && isset($args[4]) === true) {
                    if (isset($this->plugin->signChange[$sender->getName()])) {
                        unset ($this->plugin->signChange[$sender->getName()]);
                    }
                    $this->plugin->signSet[$sender->getName()] = [
                        "0" => $args[1],
                        "1" => $args[2],
                        "2" => $args[3],
                        "3" => $args[4],
                    ];
                    $sender->sendMessage($this->plugin->form['Title'] . "§a点击一个木牌完成刻印~");
                } else {
                    $sender->sendMessage($this->plugin->form['Title'] . "§b /xsp s <第一行> <第二行> <第三行> <第四行>");
                }
                break;

            case "c":
                if ($args[1] == 0 ||$args[1] == 1 || $args[1] == 2 || $args[1] == 3 && isset($args[0]) === true) {
                    if (isset($this->plugin->signSet[$sender->getName()])) {
                        unset ($this->plugin->signSet[$sender->getName()]);
                    }
                    if (isset($args[2])) {
                        $this->plugin->signChange[$sender->getName()] = [
                            'l' => $args[1],
                            'm' => $args[2],
                        ];
                        $sender->sendMessage($this->plugin->form['Title'] . "§b点击一个木牌完成修改~");
                    }
                } else {
                    $sender->sendMessage($this->plugin->form['Title'] . "§b /xsp c <行数(0-3)> <内容>");
                }

    unset($sender, $command, $label, $args);
        }
        return true;
    }
}