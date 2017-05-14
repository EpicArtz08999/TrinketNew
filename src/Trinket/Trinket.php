<?php
namespace Trinket;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Config;

/* Copyright (C) ImagicalGamer - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Jake C <imagicalgamer@outlook.com>, May 2017
 */

class Trinket extends PluginBase{

  protected $serverThread;
  protected $data;

  public function onEnable()
  {
    @mkdir($this->getDataFolder());
    $this->saveResource("/config.yml");

    $data = (new Config($this->getDataFolder() . "/config.yml", Config::YAML))->getAll();
    if(!isset($data["id"]))
    {
      $data["id"] = uniqid();
      @unlink($this->getDataFolder() . "/config.yml");
      (new Config($this->getDataFolder() . "/config.yml", Config::YAML, $data))->save();
    }
    if(!isset($data["password"]))
    {
      $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
      $pass = array();
      $alphaLength = strlen($alphabet) - 1;
      for($i = 0; $i < 8; $i++)
      {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
      }
      $pass = implode($pass);
      $data["password"] = $pass;
      @unlink($this->getDataFolder() . "/config.yml");
      (new Config($this->getDataFolder() . "/config.yml", Config::YAML, $data))->save();
    }

    $this->data = $data["id"];

    $this->serverThread = new ServerThread($data, $this->getLogger());
    if($this->serverThread->hasErrors())
    {
      $this->getLogger()->error("Unknown error occured within ServerThread");
      $this->getServer()->getPluginManager()->disablePlugin($this);
      return;
    }
  }

  public function onDisable()
  {
    $this->getServerThread()->kill();
  }

  public function getServerThread()
  {
    return $this->serverThread;
  }

  public function getServerId()
  {
    return $this->data["id"];
  }
}