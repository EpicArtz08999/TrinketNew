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
		$this->password = $array["password"];
		$this->serverId = $array["id"];

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
		//TODO ADD A QUEUE FOR PACKETS TO BE SENT EX) CHAT PACKETS FOR MULTI SERVER CHATTING: MIGHT BE ADDED ON NEW THREAD DONT WANT THIS 2 MESSY
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

			if(!isset($input["id"]))
			{
				continue;
			}

			switch($input["id"])
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
						$this->connect();
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
					$players = $data["selection"];
					if($msg === "" or empty($msg))
					{
						continue;
					}

					$this->sendChat($msg, $players);
				break;
				default:
					continue;
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
		$pk = json_encode(["id" => Info::PACKET_LOGIN_ACCEPT, "password" => $this->getPassword(), "serverId" => $this->serverId]);
		$write = @socket_write($this->socket, $pk);
		if(!$write)
		{
			exit(0);
		}
	}

	public function sendChat($msg, $players)
	{
		if(!$players === Info::TYPE_PLAYERS_ALL or !$players === Info::TYPE_PLAYERS_OP)
		{
			return;
		}
		//TODO: BROADCAST MESSAGE TO ENTIRE SERVER. WILL USE A QUEUE WITH A PLUGINTASK TO SEND A FEW MESSAGES AT ONCE (LESS LAG)
	}
}
