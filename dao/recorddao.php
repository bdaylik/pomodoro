<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../model/record.php";
require_once dirname(__FILE__)."/dao.php";

class RecordDao extends Dao
{
  public function create_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE record(username VARCHAR(255), date DATE, success INTEGER, fail INTEGER, PRIMARY KEY (username, date)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    echo $result;
  }
  public function add($record)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("INSERT INTO record (username, date, success, fail) VALUES (?, ?, ?, ?) "))
    {
      $date = date( 'Y-m-d', $record->date);
      $stmt->bind_param('ssii', $record->username, $date, $record->success, $record->fail);
      $stmt->execute();
      $stmt->close();
    }
    return $record;
  }
  public function update($record)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("UPDATE record SET success = ?, fail = ? WHERE username = ? AND date = ?"))
    {
      $date = date( 'Y-m-d', $record->date);
      $stmt->bind_param('iiss', $record->success, $record->fail, $record->username, $date);
      $stmt->execute();
      $stmt->close();
    }
    return $record;
  }
  public function delete($username, $date)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM record WHERE username = ? AND date = ?"))
    {
      $date = date( 'Y-m-d', $date);
      $stmt->bind_param('ss', $username, $date);
      $stmt->execute();
      $stmt->close();
    }
    return true;
  }
  public function get($username, $date)
  {
    $record = null;
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT username, date, success, fail FROM record WHERE username = ? AND date = ?"))
    {
      $date = date( 'Y-m-d', $date);
      $stmt->bind_param('ss', $username, $date);
      $stmt->execute();
      $stmt->bind_result($username, $date, $success, $fail);
      if($stmt->fetch())
      {
        $record = new Record();
        $record->username = $username;
        $record->date = strtotime($date);
        $record->success = $success;
        $record->fail = $fail;
      }
      $stmt->close();
    }
    return $record;
  }
  public function get_between($username, $from, $to)
  {
    $records = array();
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT username, date, success, fail FROM record WHERE username = ? AND date >= ? AND date <= ?"))
    {
      $from = date( 'Y-m-d', $from);
      $to = date( 'Y-m-d', $to);
      $stmt->bind_param('sss', $username, $from, $to);
      $stmt->execute();
      $stmt->bind_result($username, $date, $success, $fail);
      while($stmt->fetch())
      {
        $record = new Record();
        $record->username = $username;
        $record->date = strtotime($date);
        $record->success = $success;
        $record->fail = $fail;
	$records[] = $record;
      }
      $stmt->close();
    }
    return $records;
  }
  function __destruct()
  {
    parent::__destruct();
  }
}

 // $record_dao = new RecordDao();
 // $record_dao->create_table();
