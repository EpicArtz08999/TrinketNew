<?php
namespace Trinket;

/* Copyright (C) ImagicalGamer - All Rights Reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Jake C <imagicalgamer@outlook.com>, May 2017
 */

class Info{

  const ERROR_INVALID_PASSWORD = 01001010;
  const ERROR_INVALID_DATA = 10111000;

  const TYPE_DISCONNECT_FORCED = 11000001;
  const TYPE_DISCONNECT_PLANNED = 10110101;

  const TYPE_PLAYERS_ALL = 10001101;
  const TYPE_PLAYERS_OP = 10111001;

  const PACKET_UNKNOWN = 0x00;
  const DUMMY_PACKET = 0x001; //keep the connection alive (prevents host from freezing)
  const PACKET_LOGIN_ACCEPT = 0x068; //confusing, needs to be changed this packet does accepted and denied login
  const PACKET_DISCONNECT = 0x082;
  const PACKET_CHAT = 0x076;
}