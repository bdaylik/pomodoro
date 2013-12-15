<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../model/team.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/dao.php";

class TeamDao extends Dao
{
  public function create_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE team(teamname VARCHAR(255) PRIMARY KEY) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
  }

  public function add($team)
  {
    $db = parent::get_connection();
    if($stmt = $db->prepare("INSERT INTO team (teamname) VALUES (?) "))
    {
      $stmt->bind_param('s', $team->teamname);
      $stmt->execute();
      $stmt->close();
    }
    return $team;
  }
  
  public function delete($team)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM team WHERE teamname = ?"))
    {
      $stmt->bind_param('s', $team->teamname);
      $stmt->execute();
      $stmt->close();
    }
    $this->delete_join_records();
    return true;
  }

  public function get($teamname)
  {
    $team = null;
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT teamname FROM team WHERE teamname = ?"))
    {
      $stmt->bind_param('s', $teamname);
      $stmt->execute();
      $stmt->bind_result($teamname);
      if($stmt->fetch())
      {
        $team = new Team();
        $team->teamname = $teamname;
      }
      $stmt->close();
    }
    return $team;
  }
  
  public function get_users($team)
  {
    $users = array();
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT user.username, user.status, user.begin FROM user INNER JOIN user_team on user.username=user_team.username WHERE user_team.teamname = ?"))
    {
      $stmt->bind_param('s', $team->teamname);
      $stmt->execute();
      $stmt->bind_result($username, $status, $begin);
      while($stmt->fetch())
      {
        $user = new User();
        $user->username = $username;
        $user->status = $status;
        $user->begin = $begin;
        $users[] = $user;
      }
      $stmt->close();
    }
    return $users; 
  }
  
  public function get_all()
  {
    $teams = array();
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT teamname FROM team ORDER BY teamname"))
    {
      $stmt->execute();
      $stmt->bind_result($teamname);
      while($stmt->fetch())
      {
        $team = new Team();
        $team->teamname = $teamname;
        $teams[] = $team;
      }
      $stmt->close();
    }
    return $teams;
  }

  public function create_join_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE user_team(username VARCHAR(255), teamname VARCHAR(255), FOREIGN KEY (username) REFERENCES user(username), FOREIGN KEY (teamname) REFERENCES team(teamname)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
  }
  
  public function add_users_to_team($team, $users)
  {
    $db = parent::get_connection();
    foreach ($users as $user) {
      
      if($stmt = $db->prepare("INSERT INTO user_team (username, teamname) VALUES (?, ?) "))
      {
        $stmt->bind_param('ss', $user->username, $team->teamname);
        $stmt->execute();
        $stmt->close();
      }
    }
    return $team;
  }
  
  public function delete_join_records($team)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM user_team WHERE teamname = ?"))
    {
      $stmt->bind_param('s', $team->teamname);
      $stmt->execute();
      $stmt->close();
    }
    return true;
  }
  
  function __destruct()
  {
    parent::__destruct();
  }
}

// $team_dao = new TeamDao();
// $team_dao->create_table();
// $team_dao->create_join_table();

// $team = new Team();
// $team->teamname = "unimog";
// $team_dao->add($team);
// $team->teamname = 'pv';
// $team_dao->add($team);
// $team->teamname = 'yoyo';
// $team_dao->add($team);
// $team->teamname = 'vngrs-qa';
// $team_dao->add($team);
// $team->teamname = 'vngrs-dev';
// $team_dao->add($team);
// $team->teamname = 'dev';
// $team_dao->add($team);
// $team->teamname = 'qa';
// $team_dao->add($team);

// require_once dirname(__FILE__)."/userdao.php";
// $user_dao = new UserDao();
// $team = $team_dao->get('pv');

// $users[] = $user_dao->get('arif');
// $users[] = $user_dao->get('sefa');
// $users[] = $user_dao->get('ömer');
// $users[] = $user_dao->get('meltem');
// $users[] = $user_dao->get('nigar');
// $users[] = $user_dao->get('ali');

// $team_dao->add_users_to_team($team, $users);

// // gets all users for a team:
// $team_dao->get_users($team);
