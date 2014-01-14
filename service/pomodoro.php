<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../dao/userdao.php";
require_once dirname(__FILE__)."/../dao/recorddao.php";
require_once dirname(__FILE__)."/../dao/teamdao.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/../model/record.php";
require_once dirname(__FILE__)."/../model/user_status.php";
require_once dirname(__FILE__)."/../model/team_status.php";

class PomodoroService
{
  private $user_dao = null;
  private $record_dao = null;
  private $team_dao = null;

  function __construct()
  {
    $this->user_dao = new UserDao();
    $this->record_dao = new RecordDao();
    $this->team_dao = new TeamDao();
  }

  function get_todays_record ($username)
  {
    $date = time();
    $record = $this->record_dao->get($username, $date);
    if(is_null($record))
    {
      $record = new Record();
      $record->username = $username;
      $record->date = $date;
      $record->success = 0;
      $record->fail = 0;
      $this->record_dao->add($record);
    }
    return $record;
  }

  function get_all_status ()
  {
    $statuses = array();
    $users = $this->user_dao->get_all();

    foreach ($users as $user)
    {
      $statuses[] = $this->get_status($user->username);
    }

    return $statuses;
  }

  function get_team_status($teamname)
  {
    $users = $this->team_dao->get_users($teamname);
    $team_status = new TeamStatus();
    $team_status->teamname = $teamname;
    $team_status->error = false;
    
    foreach ($users as $user)
    {
      $user_status = $this->get_status($user->username);
      if($user_status->error){
        $team_status->error = true;
        $team_status->error_message = $user_status->error_message;
        break;
      }        
    }

    if($team_status->error)
      error_log("true");
    else
      error_log("false");
    
    if(!$team_status->error)
      $team_status->pomodoro_today = $this->get_team_pomodoro_count_for_today($teamname);

    return $team_status; 
  }

  function get_status ($username)
  {
    global $POMODORO_LENGTH, $SHORT_BREAK_LENGTH, $LONG_BREAK_LENGTH;

    $status = null;

    $user = $this->user_dao->get($username);

    if(is_null($user))
    {
      $user = new User();
      $user->username = $username;
      $user->status = "IDLE";
      $user->begin = time();
      $user = $this->user_dao->add($user);
    }

    if($user->status === "S_BREAK")
    {
      $diff = time() - $user->begin - $SHORT_BREAK_LENGTH;
      if($diff >= 0)
      {
        $status = $this->stop_pomodoro($username);
      }
    }

    if($user->status === "L_BREAK")
    {
      $diff = time() - $user->begin - $LONG_BREAK_LENGTH;
      if($diff >= 0)
      {
        $status = $this->stop_pomodoro($username);
      }
    }

    if(is_null($status))
    {
      $status = new UserStatus();
      $status->username = $user->username;
      $status->status = $user->status;
      $status->begin = $user->begin;
      $status->pomodoro_today = $this->get_pomodoro_count_for_today($username);
      switch($user->status)
      {
        case "POMODORO":
          $status->length = $POMODORO_LENGTH;
          break;
        case "S_BREAK":
          $status->length = $SHORT_BREAK_LENGTH;
          break;
        case "L_BREAK":
          $status->length = $LONG_BREAK_LENGTH;
          break;
      }
      $status->error = false;
    }
    return $status;
  }

  function get_team_pomodoro_count_for_today ($teamname)
  {
    $users = $this->team_dao->get_users($teamname);
    error_log(implode(",", $users));
    $records = array();
    foreach($users as $user){
      error_log($user->username);
      $records[] = $user->success;
    }
    error_log(implode(",", $records));
    return min($records);
  }

  function get_pomodoro_count_for_today ($username)
  {
    $record = $this->get_todays_record($username);
    return $record->success;
  }

  function stop_pomodoro ($username)
  {
    $status = null;
    $user = $this->user_dao->get($username);
    if(!is_null($user))
    {
      if($user->status === "POMODORO" || $user->status === "S_BREAK" || $user->status === "L_BREAK")
      {
         if($user->status === "POMODORO")
         {
           $this->fail($user->username);
         }
         $user->status = "IDLE";
         $user->begin = time();
         $this->user_dao->update($user);
      }
      $status = $this->get_status($user->username);
    }
    else
    {
      $status = new UserStatus();
      $status->error = true;
      $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function stop_team_pomodoro ($teamname)
  {
    $status = null;
    $users = $this->team_dao->get_users($teamname);

    $status = new TeamStatus();
    $status->error = false;

    foreach ($users as $user) {
      $status = $this->stop_pomodoro($user->username);
      if($status->error)
        break;
    }
    if(!$status->error)
    {
      $status = $this->get_team_status($teamname);
    }
    return $status;
  }

  function give_break ($username)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $user = $this->user_dao->get($username);
    if(!is_null($user))
    {
      if($user->status === "POMODORO")
      {
         $diff = time() - $user->begin;
         if($diff >= $POMODORO_LENGTH)
         {
           $success_count = $this->success($user->username);

           if($success_count % 4 === 0)
           {
             $user->status = "L_BREAK";
           }
           else
           {
             $user->status = "S_BREAK";
           }

           $user->begin = time();
           $this->user_dao->update($user);
           $status = $this->get_status($user->username);
         }
         else
         {
           $status = new UserStatus();
           $status->error = true;
           $status->error_message = "User's POMODORO not finished yet. Use stop instead. : ".$username;
         }
      }
      else
      {
         $status = new UserStatus();
         $status->error = true;
         $status->error_message = "User is not in POMODORO : ".$username;
      }
    }
    else
    {
       $status = new UserStatus();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function give_team_break ($teamname)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $users = $this->team_dao->get_users($teamname);
    
    $status = new TeamStatus();
    $status->error = false;

    foreach ($users as $user) {
      $status = $this->give_break($user->username);
      if($status->error)
        break;
    }
    
    if(!$status->error)
    {
      $status = $this->get_team_status($teamname);
    }
    return $status;
  }

  function start_pomodoro ($username)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $user = $this->user_dao->get($username);
    if(!is_null($user))
    {
      if($user->status === "POMODORO")
      {
        $diff = time() - $user->begin;
        if($diff >= $POMODORO_LENGTH)
        {
           $this->success($user->username);
        }
      }
      $user->status = "POMODORO";
      $user->begin = time();
      $this->user_dao->update($user);

      $status = $this->get_status($user->username);
    }
    else
    {
       $status = new UserStatus();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function start_team_pomodoro ($teamname)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $users = $this->team_dao->get_users($teamname);

    $status = new TeamStatus();
    $status->error = false;

    foreach ($users as $user) {
      $status = $this->start_pomodoro($user->username);
      if($status->error)
        break;
    }

    if(!$status->error)
    {
      $status = $this->get_team_status($teamname);
    }
    return $status;
  }

  function success ($username)
  {
    $record = $this->get_todays_record($username);
    $record->success += 1;
    $this->record_dao->update($record);
    return $record->success;
  }

  function fail ($username)
  {
    $record = $this->get_todays_record($username);
    $record->fail += 1;
    $this->record_dao->update($record);
    return $record->fail;
  }
}
