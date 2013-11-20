<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/dao.php";

class UserDao extends Dao
{
  public function create_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE user(user_name VARCHAR(255) PRIMARY KEY, status VARCHAR(10), begin INTEGER) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    echo $result;
  }
  public function add($user)
  {
    $db = parent::get_connection();
    if($stmt = $db->prepare("INSERT INTO user (user_name, status, begin) VALUES (?, ?, ?) "))
    {
      $stmt->bind_param('sss', $user->user_name, $user->status, $user->begin);
      $stmt->execute();
      $stmt->close();
    }
    return $user;
  }
  public function update($user)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("UPDATE user SET status = ?, begin = ? WHERE user_name = ?"))
    {
      $stmt->bind_param('sss', $user->status, $user->begin, $user->user_name);
      $stmt->execute();
      $stmt->close();
    }
    return $user;
  }
  public function delete($user_name)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM user WHERE user_name = ?"))
    {
      $stmt->bind_param('s', $user_name);
      $stmt->execute();
      $stmt->close();
    }
    return true;
  }
  public function get($user_name)
  {
    $user = null;
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT user_name, status, begin FROM user WHERE user_name = ?"))
    {
      $stmt->bind_param('s', $user_name);
      $stmt->execute();
      $stmt->bind_result($user_name, $status, $begin);
      if($stmt->fetch())
      {
        $user = new User();
        $user->user_name = $user_name;
        $user->status = $status;
        $user->begin = $begin;
      }
      $stmt->close();
    }
    return $user;
  }
  public function get_all()
  {
    $users = array();
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT user_name, status, begin FROM user ORDER BY user_name"))
    {
      $stmt->execute();
      $stmt->bind_result($user_name, $status, $begin);
      while($stmt->fetch())
      {
        $user = new User();
        $user->user_name = $user_name;
        $user->status = $status;
        $user->begin = $begin;
        $users[] = $user;
      }
      $stmt->close();
    }
    return $users;
  }
  function __destruct()
  {
    parent::__destruct();
  }
}

// $user_dao = new UserDao();
// $user_dao->create_table();

// $user = new User();
// $user->user_name = 'arif';
// $user_dao->add($user);
// $user->user_name = 'sefa';
// $user_dao->add($user);
// $user->user_name = 'meltem';
// $user_dao->add($user);
// $user->user_name = 'nigar';
// $user_dao->add($user);
// $user->user_name = 'ömer';
// $user_dao->add($user);
// $user->user_name = 'ali';
// $user_dao->add($user);