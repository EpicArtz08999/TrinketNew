<?php
namespace Trinket;

use pocketmine\Server;

/* Copyright (C) ImagicalGamer - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Jake C <imagicalgamer@outlook.com>, May 2017
 */

class ServerThread extends \Thread{

	private $config;
	private $socket;
	private $workerId;
	private $logger;
	private $isPluginEnabled;
	private $hasErrors;

	private static $connected;

	public function __construct($array, \Logger $logger)
	{
		set_time_limit(0);
		$this->workerId = mt_rand(5000, 10000);
		$this->logger = $logger;
		$this->hasErrors = false;

		$host = isset($array["ip"]) ? $array["ip"] : "0.0.0.0";
		$host = str_replace(" ", "", $array["ip"]);
		$port = isset($array["port"]) ? $array["port"] : 33657;

		$this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
		if(!$this->socket)
		{
			$this->hasErrors = true;
			$this->getLogger()->error("Unable to create socket Error: " . socket_strerror(socket_last_error()));
			return;
		}

		$connect = @socket_connect($this->socket, $host, $port);
		if(!$connect)
		{
			$this->hasErrors = true;
			$this->getLogger()->error("Unable to connect to host server Error: " . socket_strerror(socket_last_error()));
			return;
		}

		self::$connected = false;

		$this->connect();
	}

	public function run()
	{
		while($this->isPluginEnabled)
		{
			$read = @socket_read($this->socket, 1024, PHP_NORMAL_READ);
			if(!$read)
			{
				continue; //invalid socket and/or data
			}

			$input = $read;
			if($input === "")
			{
				return;
			}

			$input = json_decode($read);
			if(!is_array($input))
			{
				continue; //invalid data
			}

			switch($input["id"])
			{
				case 0x068: //connection accept packet
					if(self::$connected)
					{
						continue;//already connected to host server?
					}

					if($input["data"])//connection accepted
					{
						self::$connected = true;
						continue;
					}

					//connection denied
					$error = $input["error"];
					if($error === 01001010) //invalid password
					{
						exit(0); //todo disable plugin
					}
					elseif($error === 10111000) //invalid array
					{
						$this->connect(); //attempt a new connection
						continue;
					}
				break;
				case 0x082: //disconnect packet
					if(!self::$connected)
					{
						continue;//already disconnected from host server?
					}

					$reason = $data["reason"];
					if($reason === 11000001) //forced disconnect (plugin crash or server crash)
					{
						continue; //todo: idek?
					}
					elseif($reason === 10110101) //planned disconnect (server restarting)
					{
						continue; //todo: wait aprox 15 seconds and try to reconnect
					}
				break;
				case 0x076: //chat packet
					if(!self::$connected)
					{
						continue; //not connected to host server stop sending me random packets
					}

					$msg = $data["chat"];
					$players = $data["selection"]; //selects OP or non OP players TODO: allow player list to be sent a certian players to be selected
					if($msg === "" or empty($msg))
					{
						continue;
					}

					$this->sendChat($msg, $players);
				break;
			}
		}
	}

	public function kill()
	{
		$this->isPluginEnabled = false;
	}

	public function getLogger()
	{
		return $this->logger;
	}

	public function hasErrors() : bool 
	{
		return $this->hasErrors;
	}

	public function connect()
	{
		$pk = ["id" => 0x068] //todo convert to packet system similar to PocketMine's
		$write = @socket_write($this->socket, $pk);
		if(!$write)
		{
			//todo
		}
	}
}