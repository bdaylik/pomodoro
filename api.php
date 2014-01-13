<?php

require_once dirname(__FILE__)."/service/pomodoro.php";

$username = isset($_GET['u']) ? $_GET['u'] : null;
$teamname = isset($_GET['t']) ? $_GET['t'] : null;

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
  case "team_status":
    $rv = $pomodoro->get_team_status($teamname);
    break;
  case "stop":
    $rv = $pomodoro->stop_pomodoro($username);
    break;
  case "team_stop":
    $rv = $pomodoro->stop_team_pomodoro($teamname);
    break;
  case "break":
    $rv = $pomodoro->give_break($username);
    break;
  case "team_break":
    $rv = $pomodoro->give_team_break($teamname);
    break;
  case "start":
    $rv = $pomodoro->start_pomodoro($username);
    break;
  case "team_start":
    $rv = $pomodoro->start_team_pomodoro($teamname);
    break;

}

$json = json_encode($rv);

echo $json;
