<?php

require_once dirname(__FILE__)."/service/pomodoro.php";

$username = isset($_GET['u']) ? $_GET['u'] : null;

$command = $_GET['c'];

$pomodoro = new PomodoroService();

$rv = null;

switch($command)
{
  case "status_all":
    $rv = $pomodoro->get_all_status();
    break;
  case "status":
    $rv = $pomodoro->get_status($username);
    break;
  case "stop":
    $rv = $pomodoro->stop_pomodoro($username);
    break;
  case "break":
    $rv = $pomodoro->give_break($username);
    break;
  case "start":
    $rv = $pomodoro->start_pomodoro($username);
    break;
}

$json = json_encode($rv);

echo $json;

