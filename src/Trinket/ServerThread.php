<?php
namespace Trinket;

use pocketmine\Server;

/* Copyright (C) ImagicalGamer - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Jake C <imagicalgamer@outlook.com>, May 2017
 */

class ServerThread extends \Thread{

	private $socket;
	private $workerId;
	private $logger;
	private $isPluginEnabled;
	private $hasErrors;
	private $password;

	private static $connected;
	private static $queue; //needs to be implemented

	public function __construct($array, \Logger $logger)
	{
		set_time_limit(0);
		$this->workerId = mt_rand(5000, 10000);
		$this->logger = $logger;
		$this->hasErrors = false;
		$this->password = "testPassword"; //implement random password generation (probs uniqid())

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
		//TODO ADD A QUEUE FOR PACKETS TO BE SENT EX) CHAT PACKETS FOR MULTI SERVER CHATTING
		while($this->isPluginEnabled)
		{
			$read = @socket_read($this->socket, 1024, PHP_NORMAL_READ);
			if(!$read)
			{
				continue;
			}

			$input = $read;
			if($input === "")
			{
				return;
			}

			$input = json_decode($read);
			if(!is_array($input))
			{
				continue;
			}

			$id = isset($input["id"]) ? $input["id"] : Info::PACKET_UNKNOWN;
			switch($id)
			{
				case Info::PACKET_UNKNOWN:
					var_dump($input);
					continue;
				break;
				case Info::DUMMY_PACKET:
					continue;
				break;
				case PACKET_LOGIN_ACCEPT:
					if(self::$connected)
					{
						continue;
					}

					if($input["data"])
					{
						self::$connected = true;
						continue;
					}

					$error = $input["error"];
					if($error === Info::ERROR_INVALID_PASSWORD)
					{
						exit(0);
					}
					elseif($error === Info::ERROR_INVALID_DATA)
					{
						$this->connect(); //attempt a new connection
						continue;
					}
				break;
				case Info::PACKET_DISCONNECT:
					if(!self::$connected)
					{
						continue;
					}

					$reason = $data["reason"];
					if($reason === Info::TYPE_DISCONNECT_FORCED)
					{
						$this->kill();
						continue;
					}
					elseif($reason === Info::TYPE_DISCONNECT_PLANNED)
					{
						$this->kill();
						continue;
					}
				break;
				case Info::PACKET_CHAT:
					if(!self::$connected)
					{
						continue;
					}

					$msg = $data["chat"];
					$players = $data["selection"]; //selects OP or non OP players TODO: allow player list to be sent a certian players to be selected
					if($msg === "" or empty($msg))
					{
						continue;
					}

					$this->sendChat($msg, $players);
				break;
				default:
					continue; //todo unknown packet processing
				break;
			}
		}
		exit(0);
	}

	public function kill()
	{
		$this->isPluginEnabled = false;
		@socket_close($this->socket);
	}

	public function getLogger()
	{
		return $this->logger;
	}

	public function hasErrors() : bool 
	{
		return $this->hasErrors;
	}

	public function getPassword() : String
	{
		return $this->password;
	}

	public function connect()
	{
		$pk = ["id" => Info::PACKET_LOGIN_ACCEPT, "password" => $this->getPassword()] //todo convert to packet system similar to PocketMine's
		$write = @socket_write($this->socket, $pk);
		if(!$write)
		{
			//todo
		}
	}

	public function sendChat()
	{
		return; //todo
	}
}