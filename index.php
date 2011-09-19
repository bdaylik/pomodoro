<?php

$username = isset($_GET['u']) ? $_GET['u'] : null;

if(is_null($username) || $username === "")
{
  die ('<html><head></head><body>Please enter your user name!!</body></html>');
}
?><html>
<head>
  <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
  <link rel="stylesheet" type="text/css" href="http://yui.yahooapis.com/2.9.0/build/reset/reset-min.css">
  <link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" rel="stylesheet" type="text/css"/>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
  <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
  <script type="text/javascript" src="js/jquery.countdown.pack.js"></script>
  <style>
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
      font-size : 100px;
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
      <div id="name"><?php echo $username;?></div>
      <div id="timer"></div>
      <div id="progress"></div>
      <div id="buttons">
        <button id="start" onclick="start(); return false">Start</button>
        <button id="stop" onclick="stop(); return false">Stop</button>
      </div>
    </div>
  </div>
  <div id="dialog-confirm">
  </div>
<script type="text/javascript">
  var begin, length, username = "<?php echo $_GET['u'];?>";

  function update_progress(periods) {
      var remaining = periods[5] * 60 + periods[6];
      var perc = 100 - (remaining / length) * 100;
      $("#progress").progressbar("value", perc);
  }

  function reset() {
      $('#timer').countdown('destroy');
      $('#uptimer').countdown('destroy');
      $("#progress").progressbar("value", 0);
      $("#progress").progressbar("disable");
  }

  function ring() {
      $('#bell').remove();
      $('body').append('<embed id="bell" src="http://pomodoro.iletken.com.tr/media/TaDa.ogg" autostart="true" hidden="true" loop="false">');
  }

  function dialog() {
      var dialog_rv = "TAKE_A_BREAK";
      $("#dialog-confirm").dialog({
          title: "Pomodoro Finished",
          resizable: false,
          closeOnEscape: false,
          height: 140,
          width: 440,
          modal: true,
          close: function () {
              switch (dialog_rv) {
              case "TAKE_A_BREAK":
                  give_break();
                  break;
              case "SKIP_BREAK":
                  start();
                  break;
              case "VOID":
                  stop();
                  break;
              }
          },
          buttons: {
              "Take a Break": function () {
                  dialog_rv = "TAKE_A_BREAK";
                  $(this).dialog("close");
              },
              "Skip Break": function () {
                  dialog_rv = "SKIP_BREAK";
                  $(this).dialog("close");
              },
              "Void": function () {
                  dialog_rv = "VOID";
                  $(this).dialog("close");
              }
          }
      });
  }

  function pomodoro_finished() {
      ring();
      dialog();
  }

  function break_finished() {
      ring();
      stop();
  }

  function server_time() {
      var time = null;
      $.ajax({
          url: 'time.php',
          async: false,
          dataType: 'text',
          success: function (text) {
              time = new Date(text);
          },
          error: function (http, message, exc) {
              time = new Date();
          }
      });
      return time;
  }

  function pomodoro_timer(until) {
      reset();
      $("#progress").progressbar("enable");
      $('#content').css('background-color', 'F20000');
      $('#stop').button('enable');
      $('#start').button('disable');
      var until = new Date((begin + length) * 1000);
      $('#timer').countdown({
          alwaysExpire : true,
          until: until,
          format: 'MS',
          compact: true,
          serverSync: server_time,
          onTick: update_progress,
          onExpiry: pomodoro_finished
      });
  }

  function idle_timer(since) {
      reset();
      $('#content').css('background-color', 'C0C0C0');
      $('#stop').button('disable');
      $('#start').button("enable");
      $('#timer').html("IDLE");
  }

  function break_timer(begin, length) {
      reset();
      $("#progress").progressbar("enable");
      $('#content').css('background-color', '336600');
      $('#stop').button('enable');
      $('#start').button('disable');
      var until = new Date((begin + length) * 1000);
      $('#timer').countdown({
          until: until,
          format: 'MS',
          compact: true,
          serverSync: server_time,
          onTick: update_progress,
          onExpiry: break_finished
      });
  }

  function refresh(data) {
      var error = data.error;
      if (!error) {
          var status = data.status;
          switch (status) {
          case "IDLE":
              idle_timer(new Date(data.begin * 1000));
              break;
          case "S_BREAK":
          case "L_BREAK":
              begin = data.begin;
              length = data.length;
              break_timer(data.begin, data.length);
              break;
          case "POMODORO":
              begin = data.begin;
              length = data.length;
              pomodoro_timer(data.begin, data.length);
              break;
          }
      }
  }

  function status() {
      $.getJSON('api.php', {
          u: username,
          c: "status"
      }, refresh);
  }

  function start() {
      $.getJSON('api.php', {
          u: username,
          c: "start"
      }, refresh);
  }

  function stop() {
      $.getJSON('api.php', {
          u: username,
          c: "stop"
      }, refresh);
  }

  function give_break() {
      $.getJSON('api.php', {
          u: username,
          c: "break"
      }, refresh);
  }

  $("#progress").progressbar();
  $("#start").button();
  $("#stop").button();
  status();
</script>
</body>
</html>
