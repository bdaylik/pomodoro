<?php

require_once dirname(__FILE__)."/service/pomodoro.php";

$user_name = isset($_GET['u']) ? $_GET['u'] : null; // user_name
$team_name = isset($_GET['t']) ? $_GET['t'] : null; // team_name

$command = $_GET['c'];

$pomodoro = new PomodoroService();

$rv = null;

switch($command)
{
  case "status_all":
    $rv = $pomodoro->get_all_status();
    break;
  case "status":
    $rv = $pomodoro->get_status($user_name);
    break;
  case "team_status":
    $rv = $pomodoro->get_team_status($team_name);
    break;
  case "stop":
    $rv = $pomodoro->stop_pomodoro($user_name);
    break;
  case "team_stop":
    $rv = $pomodoro->stop_team_pomodoro($team_name);
    break;
  case "break":
    $rv = $pomodoro->give_break($user_name);
    break;
  case "team_break":
    $rv = $pomodoro->give_team_break($team_name);
    break;
  case "start":
    $rv = $pomodoro->start_pomodoro($user_name);
    break;
  case "team_start":
    $rv = $pomodoro->start_team_pomodoro($team_name);
    break;

}

$json = json_encode($rv);

echo $json;

