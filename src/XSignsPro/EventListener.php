<?php

namespace XSignsPro;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\Config;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\tile\Sign;
use pocketmine\event\block\SignChangeEvent;

use pocketmine\command\ConsoleCommandSender;

use onebone\economyapi\EconomyAPI;

class EventListener implements Listener
{
    public $plugin;
    public $conf;
    public $msg;
    public $form;
    public $locking;
    public $sign_data;

    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        $this->conf = $this->plugin->conf->getAll();
        $this->msg = $this->plugin->msg;
        $this->form = $this->plugin->form;
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    public function setSign(SignChangeEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $line = $event->getLines();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $level = $block->getLevel()->getFolderName();
        switch ($line[0]) {
            case 'c':
                if ($this->checkSet($player,$line) === false) {
                    return;
                }
                $cmd = $line[2] . $line[3];
                $sign_data = new Config($this->plugin->path . "Signs/$level/$line[0].yml", Config::YAML, array());
                $sign_data->set("$x|$y|$z", array(
                    'Cmd' => $cmd,
                    'Price' => 0,
                ));
                $sign_data->save();
                $this->setForm($event, $line[0]);
                $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
                break;

            case 'o':
                if ($this->checkSet($player,$line) === false) {
                    return;
                }
                $cmd = $line[2] . $line[3];
                $sign_data = new Config($this->plugin->path . "/Signs/$level/$line[0].yml", Config::YAML, array());
                $sign_data->set("$x|$y|$z", array(
                    'Cmd' => $cmd,
                    'Price' => 0,
                ));
                $sign_data->save();
                $this->setForm($event, $line[0]);
                $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
                break;

            case 'h':
                if ($this->checkSet($player,$line) === false) {
                    return;
                }
                $cmd = $line[2] . $line[3];
                $sign_data = new Config($this->plugin->path . "/Signs/$level/$line[0].yml", Config::YAML, array());
                $sign_data->set("$x|$y|$z", array(
                    'Qus' => $cmd,
                    'Cmd' => '',
                    'Price' => 0,
                ));
                $sign_data->save();
                $this->setForm($event, $line[0]);
                $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
                break;

            case 'm':
                if ($this->checkSet($player,$line,'m') === false) {
                    return;
                }
                $sign_data = new Config($this->plugin->path . "/Signs/$level/$line[0].yml", Config::YAML, array());
                $sign_data->set("$x|$y|$z", array(
                    'Cmd' => '请编写指令，用|分割喔',
                    'Price' => 0,
                ));
                $sign_data->save();
                $this->setForm($event, $line[0]);
                $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
                break;

            case 'l':
                $sign_data = new Config($this->plugin->path . "/Signs/$level/$line[0].yml", Config::YAML, array());
                if (isset($this->locking[$name])) {
                    if ($this->locking[$name]['status'] === true) {
                        $pos = $this->locking[$name]['pos'];
                        if (!$player->isOp()) {
                            if (!$sign_data->get("$x|$y|$z") === false) {
                                if (!$sign_data->get("$x|$y|$z") == $name) {
                                    $player->sendMessage($this->form['Title'] . "§e这个箱子已经被上锁了，主人 §b" . $sign_data->get("$x|$y|$z"));
                                    $event->setCancelled(true);
                                    return;
                                }
                            } else {
                                $sign_data->set($pos, $name);
                                $sign_data->save();
                                $this->setForm($event, $line[0]);
                                $player->sendMessage($this->form['Title'] . $this->msg['BeOwnerMsg']);
                            }
                            $this->setForm($event, $line[0]);
                        } else {
                            $sign_data->set($pos, $name);
                            $sign_data->save();
                            $this->setForm($event, $line[0]);
                            $player->sendMessage($this->form['Title'] . $this->msg['BeOwnerMsg']);
                        }
                    }
                }
                unset ($this->locking[$name]);
                break;

        }
    }

    public function delSign(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $level = $block->getLevel()->getFolderName();
        //如果是木牌
        if ($block->getID() == 323 || $block->getID() == 63 || $block->getID() == 68) {
            $tile = $event->getPlayer()->getLevel()->getTile($block);
            if(!$tile instanceof Sign){
                return false;
            }
            $this->sign_data = new Config($this->plugin->path . "/Signs/{$level}/{$this->getSignType($tile->getText())}.yml", Config::YAML, array());
            if (!$this->sign_data->get("$x|$y|$z") === false) {
                if ($player->isOp()) {
                    $this->sign_data->remove("$x|$y|$z");
                    $this->sign_data->save();
                    $player->sendMessage($this->form['Title'] . $this->msg['DelSignMsg']);
                } else {
                    $event->setCancelled();
                    $player->sendMessage($this->form['Title'] . $this->msg['NotOpMsg']);
                }
            }
            //如果是箱子
        } elseif ($block->getId() == 54) {
            $chest_data = new Config($this->plugin->path . "/Signs/l/{$level}.yml", Config::YAML, array());
            if (!$chest_data->get("$x|$y|$z") === false) {
                if (!$player->isOp()) {
                    if ($name == $chest_data->get("$x|$y|$z")) {
                        $chest_data->remove("$x|$y|$z");
                        $chest_data->save();
                        $event->setCancelled();
                        $player->sendMessage($this->form['Title'] . $this->msg['UnsetChest']);
                    } else {
                        $event->setCancelled();
                        $player->sendMessage($this->form['Title'] . "§e这个箱子已经被上锁了，主人 §b" . $chest_data->get("$x|$y|$z"));
                    }

                } else {
                    $chest_data->remove("$x|$y|$z");
                    $chest_data->save();
                    $event->setCancelled();
                    $player->sendMessage($this->form['Title'] . $this->msg['UnsetChest']);
                }
            }
        }
        return true;
    }

    public function getSign(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $block = $event->getBlock();
        $item = $event->getItem();
        $x = $block->getX();
        $y = $block->getY();
        $z = $block->getZ();
        $level = $block->getLevel()->getFolderName();
        //如果是箱子
        if ($event->getBlock()->getID() == 54) {
            $chest_data = new Config($this->plugin->path . "/Signs/l/{$level}.yml", Config::YAML, array());
            //手持木牌的处理
            if (($item->getId() == 323 || $item->getId() == 63 || $item->getId() == 68) && (!in_array($level,$this->conf['UnlockWorld']) && !$player->isOp())) {
                if (!$player->isOp()) {
                    if (!$chest_data->get("$x|$y|$z") == false && $chest_data->get("$x|$y|$z") != $name) {
                        $player->sendMessage($this->form['Title'] . "§e这个箱子已经被上锁了，主人 §b" . $chest_data->get("$x|$y|$z"));
                        $event->setCancelled();
                        return;
                    }
                }
                $this->locking[$player->getName()] = [
                    'status' => true,
                    'pos' => "$x|$y|$z"
                ];
                return;
                
            } else {
                if (!$player->isOp()) {
                    if (!$chest_data->get("$x|$y|$z") == false && $chest_data->get("$x|$y|$z") != $name) {
                        $player->sendMessage($this->form['Title'] . "§e这个箱子已经被上锁了，主人 §b" . $chest_data->get("$x|$y|$z"));
                        $event->setCancelled(true);
                        return;
                    }
                }
            }
        } elseif ($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68) {
            $sign = $event->getPlayer()->getLevel()->getTile($block);
            if ($sign instanceof Sign) {
                //如果存在打印木牌的信息
                if (isset($this->plugin->signSet[$name])) {
                    $this->setSignInfo($player,$sign,'SetSign');
                    return;
                    //如果存在修改木牌的信息
                } elseif (isset($this->plugin->signChange[$name])) {
                    $this->setSignInfo($player,$sign,'ChangeSign');
                    return;
                }
                //$player->sendMessage("§a通过测试1");
                $sign = $sign->getText();
                $pos = "$x|$y|$z";
                $sign_data = new Config($this->plugin->path . "/Signs/{$level}/{$this->getSignType($sign[0])}.yml", Config::YAML, array());
                if ($sign_data->get($pos) !== null) {
                    $sign_cmd = $sign_data->get($pos)["Cmd"];
                    //$player->sendMessage("§a通过测试2");
                    switch ($this->getSignType($sign[0])) {
                        case"c":
                            //$player->sendMessage("§a通过测试3");
                            //$player->sendMessage("§a输入指令 §b$sign_cmd");
                            $check_money = $this->checkMoney($player, $level, $pos, 'c');
                            if ($check_money) {
                                $this->plugin->getServer()->dispatchCommand($player, $sign_cmd);
                                //$player->sendMessage("§a恭喜xxm，嘿嘿");
                            }
                            break;

                        case"o":
                            $name = $player->getName();
                            $sign_cmd = str_ireplace('%p', $name, $sign_cmd);
                            $check_money = $this->checkMoney($player, $level, $pos, 'o');
                            if ($check_money) {
                                if (!$player->isOp()) {
                                    $this->plugin->getServer()->addOp($player);
                                    $this->plugin->getServer()->dispatchCommand($player, $sign_cmd);
                                    $this->plugin->getServer()->removeOp($player);
                                } else {
                                    $this->plugin->getServer()->dispatchCommand($player, $sign_cmd);
                                }
                            }
                            break;

                        case"h":
                            $check_money = $this->checkMoney($player, $level, $pos, 'h');
                            if ($check_money) {
                                $sign_qus = $sign_data->get($pos)["Cmd"];
                                if (!$sign_qus == null) {
                                    $player->sendMessage("§e§l========§a[Helper]§e§l========");
                                    $sign_qus = explode("|", $sign_qus);
                                    foreach ($sign_qus as $qus) {
                                        $player->sendMessage($qus);
                                    }
                                } else {
                                    $player->sendMessage($this->form['Title'] . $this->msg['UnsetAns']);
                                }
                            }
                            break;

                        case"m":
                            $name = $player->getName();
                            $sign_cmd = str_ireplace('%p', $name, $sign_cmd);
                            $sign_cmd = explode("|", $sign_cmd);
                            $check_money = $this->checkMoney($player, $pos, $level, 'm');
                            if ($check_money) {
                                foreach ($sign_cmd as $cmds) {
                                    $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmds);
                                }
                            }
                            break;
                    }
                }
            }
        }
    }

    public function getSignTool(Player $player,Sign $sign,$type = null){
        $name = $player->getName();
        if($type == 'SetSign'){
            $sign->setText($this->setMessage($this->plugin->signSet[$name][0]), $this->setMessage($this->plugin->signSet[$name][1]), $this->setMessage($this->plugin->signSet[$name][2]), $this->setMessage($this->plugin->signSet[$name][3]));
            $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
            unset($this->plugin->signSet[$name]);
        }
        elseif ($type == 'ChangeSign'){
            $sign_text = $sign->getText();
            switch ($this->plugin->signChange[$name]["l"]) {
                case"0":
                    $sign->setText($this->setMessage($this->plugin->signChange[$name]["m"]), $sign_text[1], $sign_text[2], $sign_text[3]);
                    break;
                case "1":
                    $sign->setText($sign_text[0], $this->setMessage($this->plugin->signChange[$name]["m"]), $sign_text[2], $sign_text[3]);
                    break;
                case "2":
                    $sign->setText($sign_text[0], $sign_text[1], $this->setMessage($this->plugin->signChange[$name]["m"]), $sign_text[3]);
                    break;
                case "3":
                    $sign->setText($sign_text[0], $sign_text[1], $sign_text[2], $this->setMessage($this->plugin->signChange[$name]["m"]));
                    break;
            }
            $player->sendMessage($this->form['Title'] . $this->msg['SetSignMsg']);
            unset($this->plugin->signChange[$name]);
        }
        unset($player,$name,$sign,$type);
    }

    private function getSignType($info)
    {
        foreach ($this->form as $type => $type_info) {
            if ($info == $type_info[0]) {
                return $type;
            }
        }
        return null;
    }

    public function setMessage($msg)
    {
        $msg = str_ireplace('@', ' ', $msg);
        return $msg;
    }

    public function setForm(SignChangeEvent $event, $type)
    {
        $type = strtoupper($type);
        $sign_type = $this->form[$type];
        if ($type == 'L') {
            $event->setLine(0, $sign_type[0]);
            $event->setLine(2, $sign_type[2]);
            $event->setLine(3, "§e§l{$event->getPlayer()->getName()}");
        } else {
            $event->setLine(0, $sign_type[0]);
            $event->setLine(2, $sign_type[2]);
            $event->setLine(3, $sign_type[3]);
        }
    }

    public function checkSet(Player $player, $line, $type = null)
    {
        $name = $player->getName();
            if ($this->conf['EnableAdmin'] === true) {
                if (!in_array($name, $this->conf['AdminList'])) {
                    $player->sendMessage($this->form['Title'] . $this->msg['NotAdminMsg']);
                    return false;
                }
            }
            if (!$player->isOp()) {
                $player->sendMessage($this->form['Title'] . $this->msg['UnsetSignMsg']);
                return false;
            }
            if ($line[0] != 'm'){
                if ($line[2] == null && $line[3] == null) {
                    $player->sendMessage($this->form['Title'] . $this->msg['UnsetCmdMsg']);
                    return false;
                }
            }
            if($type == null){
                return true;
            }
            return true;
    }

    private function checkMoney(Player $player, $level, $pos, $type)
    {
        $name = strtolower($player->getName());
        $money = EconomyAPI::getInstance()->myMoney($name);
        $sign_data = new Config($this->plugin->path . "/Signs/{$type}/{$level} .yml", Config::YAML, array());
        if (!$player->isOp()) {
            $sign_price = $sign_data->get($pos)['Price'];
            if ($sign_price == 0) {
                return true;
            } else {
                if ($money < $sign_price) {
                    $player->sendMessage($this->form['Title'] . "§e啊呦，执行这个需要付费§a[  {$sign_price}  ]§e，然而你只有§b[ . {$money} . ]");
                    return false;
                } else {
                    EconomyAPI::getInstance()->reduceMoney($player, $sign_price);
                    $player->sendMessage($this->form['Title'] . $this->msg['CostMsg'] . "§b[ {$sign_price} ]");
                    return true;
                }
            }
        } else {
            return true;
        }
    }


}
	