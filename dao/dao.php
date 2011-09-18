<?php

require_once dirname(__FILE__)."/../config.php";

class Dao
{
  private $db = null;
  protected function get_connection()
  {
    global $DB_HOST,$DB_USER,$DB_PASS,$DB_NAME;

    if(is_null($this->db))
    {
      $this->db = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
      $this->db->query("SET NAMES 'utf8';");
    }
    return $this->db;
  }

  protected function close_connection()
  {
    if(!is_null($this->db))
    {
      $this->db->close();
    }
  }

  function __destruct()
  {
    $this->close_connection();
  }
}
