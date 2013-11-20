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

  function get_todays_record ($user_name)
  {
    $date = time();
    $record = $this->record_dao->get($user_name, $date);
    if(is_null($record))
    {
      $record = new Record();
      $record->user_name = $user_name;
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
      $statuses[] = $this->get_status($user->user_name);
    }

    return $statuses;
  }

  function get_team_status($team_name)
  {
    $statuses = array();
    $users = $this->team_dao->get_users($team_name);

    foreach ($users as $user)
    {
      $statuses[] = $this->get_status($user->user_name);
    }

    // $rv is the status of the team. It's actually the first status
    // that comes in the $statuses array. This object (except the pomodoro count
    // and user_name fields) is the same for all users in a team, because they
    // all have pomodoros that were initiated with a *team_start* call.
    // For this reason, the following changes the user_name of $rv to *team* and pomodoro_today
    // to -1.

    // This part needs further thought and development. With the following lines, we only
    // have a hack to make team implementations compatible with the old pomodoro app.

    // hack start
    $rv = $statuses[0];
    $rv->user_name = 'team';
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

  function get_status ($user_name)
  {
    global $POMODORO_LENGTH, $SHORT_BREAK_LENGTH, $LONG_BREAK_LENGTH;

    $status = null;

    $user = $this->user_dao->get($user_name);

    if(is_null($user))
    {
      $user = new User();
      $user->user_name = $user_name;
      $user->status = "IDLE";
      $user->begin = time();
      $user = $this->user_dao->add($user);
    }

    if($user->status === "S_BREAK")
    {
      $diff = time() - $user->begin - $SHORT_BREAK_LENGTH;
      if($diff >= 0)
      {
        $status = $this->stop_pomodoro($user_name);
      }
    }

    if($user->status === "L_BREAK")
    {
      $diff = time() - $user->begin - $LONG_BREAK_LENGTH;
      if($diff >= 0)
      {
        $status = $this->stop_pomodoro($user_name);
      }
    }

    if(is_null($status))
    {
      $status = new Status();
      $status->user_name = $user->user_name;
      $status->status = $user->status;
      $status->begin = $user->begin;
      $status->pomodoro_today = $this->get_pomodoro_count_for_today($user_name);
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

  function get_pomodoro_count_for_today ($user_name)
  {
    $record = $this->get_todays_record($user_name);
    return $record->success;
  }

  function stop_pomodoro ($user_name)
  {
    $status = null;
    $user = $this->user_dao->get($user_name);
    if(!is_null($user))
    {
      if($user->status === "POMODORO" || $user->status === "S_BREAK" || $user->status === "L_BREAK")
      {
         if($user->status === "POMODORO")
         {
           $this->fail($user->user_name);
         }
         $user->status = "IDLE";
         $user->begin = time();
         $this->user_dao->update($user);
      }
      $status = $this->get_status($user->user_name);
    }
    else
    {
      $status = new Status();
      $status->error = true;
      $status->error_message = "There is no such user named : ".$user_name;
    }
    return $status;
  }

  function stop_team_pomodoro ($team_name)
  {
    $status = null;
    $users = $this->team_dao->get_users($team_name);

    $status = new Status();
    $status->error = false;

    foreach ($users as $user) {
      if(!is_null($user))
      {
        if($user->status === "POMODORO" || $user->status === "S_BREAK" || $user->status === "L_BREAK")
        {
           if($user->status === "POMODORO")
           {
             $this->fail($user->user_name);
           }
           $user->status = "IDLE";
           $user->begin = time();
           $this->user_dao->update($user);
        }
      }
      else
      {
        $status = new Status();
        $status->error = true;
        $status->error_message = "There is no such user named : ".$user_name;
        break;
      }
    }
    if(!$status->error)
    {
      $status = $this->get_team_status($team_name);
    }
    return $status;
  }

  function give_break ($user_name)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $user = $this->user_dao->get($user_name);
    if(!is_null($user))
    {
      if($user->status === "POMODORO")
      {
         $diff = time() - $user->begin;
         if($diff >= $POMODORO_LENGTH)
         {
           $success_count = $this->success($user->user_name);

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
           $status = $this->get_status($user->user_name);
         }
         else
         {
           $status = new Status();
           $status->error = true;
           $status->error_message = "User's POMODORO not finished yet. Use stop instead. : ".$user_name;
         }
      }
      else
      {
         $status = new Status();
         $status->error = true;
         $status->error_message = "User is not in POMODORO : ".$user_name;
      }
    }
    else
    {
       $status = new Status();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$user_name;
    }
    return $status;
  }

  function give_team_break ($team_name)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $users = $this->team_dao->get_users($team_name);
    
    $status = new Status();
    $status->error = false;

    foreach ($users as $user) {
      if(!is_null($user))
      {
        if($user->status === "POMODORO")
        {
           $diff = time() - $user->begin;
           if($diff >= $POMODORO_LENGTH)
           {
             $success_count = $this->success($user->user_name);

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
           }
           else
           {
             $status = new Status();
             $status->error = true;
             $status->error_message = "User's POMODORO not finished yet. Use stop instead. : ".$user_name;
           }
        }
        else
        {
           $status = new Status();
           $status->error = true;
           $status->error_message = "User is not in POMODORO : ".$user_name;
        }
      }
      else
      {
         $status = new Status();
         $status->error = true;
         $status->error_message = "There is no such user named : ".$user_name;
      }
    }
    
    if(!$status->error)
    {
      $status = $this->get_team_status($team_name);
    }
    return $status;
  }

  function start_pomodoro ($user_name)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $user = $this->user_dao->get($user_name);
    if(!is_null($user))
    {
      if($user->status === "POMODORO")
      {
        $diff = time() - $user->begin;
        if($diff >= $POMODORO_LENGTH)
        {
           $this->success($user->user_name);
        }
      }
      $user->status = "POMODORO";
      $user->begin = time();
      $this->user_dao->update($user);

      $status = $this->get_status($user->user_name);
    }
    else
    {
       $status = new Status();
       $status->error = true;
       $status->error_message = "There is no such user named : ".$user_name;
    }
    return $status;
  }

  function start_team_pomodoro ($team_name)
  {
    global $POMODORO_LENGTH;
    $status = null;
    $users = $this->team_dao->get_users($team_name);

    $status = new Status();
    $status->error = false;

    foreach ($users as $user) {

      if(!is_null($user))
      {
        if($user->status === "POMODORO")
        {
          $diff = time() - $user->begin;
          if($diff >= $POMODORO_LENGTH)
          {
             $this->success($user->user_name);
          }
        }
        $user->status = "POMODORO";
        $user->begin = time();
        $this->user_dao->update($user);
      }
      else
      {
         $status = new Status();
         $status->error = true;
         $status->error_message = "There is no such user named : ".$user_name;
      }
    }

    if(!$status->error)
    {
      $status = $this->get_team_status($team_name);
    }
    return $status;
  }

  function success ($user_name)
  {
    $record = $this->get_todays_record($user_name);
    $record->success += 1;
    $this->record_dao->update($record);
    return $record->success;
  }

  function fail ($user_name)
  {
    $record = $this->get_todays_record($user_name);
    $record->fail += 1;
    $this->record_dao->update($record);
    return $record->fail;
  }
}
