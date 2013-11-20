<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../model/team.php";
require_once dirname(__FILE__)."/dao.php";
require_once dirname(__FILE__)."/userteamdao.php";

class TeamDao extends Dao
{
  public function create_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE team(team_name VARCHAR(255) PRIMARY KEY) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    echo $result;
  }
  public function add($team)
  {
    $db = parent::get_connection();
    if($stmt = $db->prepare("INSERT INTO team (team_name) VALUES (?) "))
    {
      echo $team->team_name;
      $stmt->bind_param('s', $team->team_name);
      $stmt->execute();
      $stmt->close();
    }
    return $team;
  }
  public function delete($team_name)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM team WHERE team_name = ?"))
    {
      $stmt->bind_param('s', $team_name);
      $stmt->execute();
      $stmt->close();
    }
    $user_team_dao = new UserTeamDao();
    $team = new Team();
    $team->team_name = $team_name;
    $user_team_dao->delete($team);
    return true;
  }
  public function get($team_name)
  {
    $team = null;
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT team_name FROM team WHERE team_name = ?"))
    {
      $stmt->bind_param('s', $team_name);
      $stmt->execute();
      $stmt->bind_result($team_name);
      if($stmt->fetch())
      {
        $team = new Team();
        $team->team_name = $team_name;
      }
      $stmt->close();
    }
    return $team;
  }
  public function get_users($team_name)
  {
    $user_team_dao = new UserTeamDao();
    $team = $this->get($team_name);
    $users = $user_team_dao->get_users_for_team($team);
    return $users;
  }
  public function get_all()
  {
    $teams = array();
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT team_name FROM team ORDER BY team_name"))
    {
      $stmt->execute();
      $stmt->bind_result($team_name);
      while($stmt->fetch())
      {
        $team = new Team();
        $team->team_name = $team_name;
        $teams[] = $team;
      }
      $stmt->close();
    }
    return $teams;
  }
  function __destruct()
  {
    parent::__destruct();
  }
}

// $team_dao = new TeamDao();
// $team_dao->create_table();

// $team = new Team();
// $team->team_name = "unimog";
// $team_dao->add($team);
// $team->team_name = 'pv';
// $team_dao->add($team);
// $team->team_name = 'yoyo';
// $team_dao->add($team);
// $team->team_name = 'vngrs-qa';
// $team_dao->add($team);
// $team->team_name = 'vngrs-dev';
// $team_dao->add($team);
// $team->team_name = 'dev';
// $team_dao->add($team);
// $team->team_name = 'qa';
// $team_dao->add($team);