<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../dao/userdao.php";
require_once dirname(__FILE__)."/../dao/recorddao.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/../model/record.php";
require_once dirname(__FILE__)."/../model/status.php";

class PomodoroService
{
  private $user_dao = null;
  private $record_dao = null;

  function __construct()
  {
    $this->user_dao = new UserDao();
    $this->record_dao = new RecordDao();
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

    if($user->status === "BREAK")
    {
      $diff = time() - $user->begin - $SHORT_BREAK_LENGTH;
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
      switch($user->status)
      {
        case "POMODORO":
          $status->length = $POMODORO_LENGTH;
          break;
        case "BREAK":
          $status->length = $SHORT_BREAK_LENGTH;
          break;
      }
      $status->error = false;
    }
    return $status;
  }

  function stop_pomodoro ($username)
  {
    $status = null;
    $user = $this->user_dao->get($username);
    if(!is_null($user))
    {
      if($user->status === "POMODORO" || $user->status === "BREAK")
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
           $this->success($user->username);

           $user->status = "BREAK";
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

  function success ($username)
  {
    $record = $this->get_todays_record($username);
    $record->success += 1;
    $this->record_dao->update($record);
  }

  function fail ($username)
  {
    $record = $this->get_todays_record($username);
    $record->fail += 1;
    $this->record_dao->update($record);
  }
}
