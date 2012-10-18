<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Pomodoro Tracker</title>
  <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
<?php

$username = isset($_GET['u']) ? $_GET['u'] : null;

if(is_null($username) || $username === "")
{
  die ('</head><body><p>Please enter your user name!!</p></body></html>');
}
?>
  <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/reset/reset-min.css" />
  <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery.countdown.pack.js"></script>
  <script type="text/javascript" src="js/jquery.jplayer.min.js"></script>
  <script type="text/javascript">var username = "<?php echo $username;?>";</script>
  <style type="text/css">
    #content {
      width : 400px;
      height : 350px;
      margin : auto;
    }
    #timer {
      text-align : right;
      font-size : 100px;
      font-family : Arial;
      height: 100px;
    }
    #name {
      text-align : center;
      font-size : 80px;
      font-family : Arial;
      font-weight : bold;
      border-bottom : 2px solid black;
    }
    #progress {
      width : 380px;
      height : 50px;
      margin : auto;
      margin-top : 10px;
      margin-bottom : 10px;
    }
    #buttons {
      text-align : center;
    }
  </style>
</head>
<body>
  <div id="main">
    <div id="content">
      <div id="name"><?php echo $username;?> <span></span></div>
      <div id="timer"></div>
      <div id="progress"></div>
      <div id="buttons">
        <button id="start" onclick="start(); return false">Start</button>
        <button id="stop" onclick="stop(); return false">Stop</button>
      </div>
    </div>
  </div>
  <div id="dialog-confirm"></div>
  <div id="player"></div>
  <script type="text/javascript" src="js/index.js"></script>
</body>
</html>
