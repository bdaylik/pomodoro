<?php

require_once dirname(__FILE__)."/../config.php";
require_once dirname(__FILE__)."/../model/team.php";
require_once dirname(__FILE__)."/../model/user.php";
require_once dirname(__FILE__)."/dao.php";
require_once dirname(__FILE__)."/userdao.php";
require_once dirname(__FILE__)."/teamdao.php";

class UserTeamDao extends Dao
{
  public function create_table()
  {
    $db = $this->get_connection();

    $result = $db->query("CREATE TABLE user_team(user_name VARCHAR(255), team_name VARCHAR(255), FOREIGN KEY (user_name) REFERENCES user(user_name), FOREIGN KEY (team_name) REFERENCES team(team_name)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci");
    echo $result;
  }
  public function add($team, $users)
  {
    $db = parent::get_connection();
    foreach ($users as $user) {
      
      if($stmt = $db->prepare("INSERT INTO user_team (user_name, team_name) VALUES (?, ?) "))
      {
        $stmt->bind_param('ss', $user->user_name, $team->team_name);
        $stmt->execute();
        $stmt->close();
      }
    }
    return $team;
  }
  public function delete($team)
  {
    $db = $this->get_connection();
    if($stmt = $db->prepare("DELETE FROM user_team WHERE team_name = ?"))
    {
      $stmt->bind_param('s', $team->team_name);
      $stmt->execute();
      $stmt->close();
    }
    return true;
  }
  public function get_users_for_team($team){
    $db = $this->get_connection();
    if($stmt = $db->prepare("SELECT user.user_name, user.status, user.begin FROM user INNER JOIN user_team on user.user_name=user_team.user_name WHERE user_team.team_name = ?"))
    {
      $stmt->bind_param('s', $team->team_name);
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
}

// $user_team_dao = new UserTeamDao();
// $user_team_dao->create_table();

// $team_dao = new TeamDao();
// $user_dao = new UserDao();
// $team = $team_dao->get('pv');

// $users[] = $user_dao->get('arif');
// $users[] = $user_dao->get('sefa');
// $users[] = $user_dao->get('ömer');
// $users[] = $user_dao->get('meltem');
// $users[] = $user_dao->get('nigar');
// $users[] = $user_dao->get('ali');

// $user_team_dao->add($team, $users);

// $team = new Team();
// $team->team_name = 'pv';
// // gets all user entries for a team:
// $user_team_dao->get_users_for_team($team);