<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../dao/userdao.php";
require_once dirname(__FILE__)."/../dao/recorddao.php";
require_once dirname(__FILE__)."/../dao/teamdao.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/../model/record.php";
require_once dirname(__FILE__)."/../model/status.php";

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
    $statuses = array();
    $team = $this->team_dao->get($teamname);
    $users = $this->team_dao->get_users($team);

    foreach ($users as $user)
    {
      $statuses[] = $this->get_status($user->username);
    }

    // $rv is the status of the team. It's actually the first status
    // that comes in the $statuses array. This object (except the pomodoro count
    // and username fields) is the same for all users in a team, because they
    // all have pomodoros that were initiated with a *team_start* call.
    // For this reason, the following changes the username of $rv to *team* and pomodoro_today
    // to -1.

    // This part needs further thought and development. With the following lines, we only
    // have a hack to make team implementations compatible with the old pomodoro app.

    // hack start
    $rv = $statuses[0];
    $rv->username = 'team';
    $rv->pomodoro_today = -1;
    // hack end

    foreach ($statuses as $status) {
      if($status->error)
      {
        $rv = $status;
        break;
      }
    }

    return $rv; 
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
      $status = new Status();
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
      $status = new Status();
      $status->error = true;
      $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function stop_team_pomodoro ($teamname)
  {
    $status = null;
    $team = $this->team_dao->get($teamname);
    $users = $this->team_dao->get_users($team);

    $status = new Status();
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
           $status = new Status();
           $status->error = true;
           $status->error_message = "User's POMODORO not finished yet. Use stop instead. : ".$username;
         }
      }
      else
      {
         $status = new Status();
         $status->error = true;
         $status->error_message = "User is not in POMODORO : ".$username;
      }
    }
    else
    {
       $status = new Status();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function give_team_break ($teamname)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $team = $this->team_dao->get($teamname);
    $users = $this->team_dao->get_users($team);
    
    $status = new Status();
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
       $status = new Status();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$username;
    }
    return $status;
  }

  function start_team_pomodoro ($teamname)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $team = $this->team_dao->get($teamname);
    $users = $this->team_dao->get_users($team);

    $status = new Status();
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
