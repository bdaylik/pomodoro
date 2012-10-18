function update_progress_func(username, length) {
    "use strict";
    return function (periods) {
        var remaining = periods[5] * 60 + periods[6],
            perc = 100 - (remaining / length) * 100;
        $("#" + username + "_content .progress").progressbar("value", perc);
    };
}

function reset(username) {
    "use strict";
    $("#" + username + "_content .timer").countdown('destroy');
}

function server_time() {
    "use strict";
    var time = null;
    $.ajax({
        url: 'time.php',
        async: false,
        dataType: 'text',
        success: function (text) {
            time = new Date(text);
        },
        error: function () {
            time = new Date();
        }
    });
    return time;
}

function pomodoro_timer(username, begin, length) {
    "use strict";
    reset();
    $("#" + username + "_content .progress").progressbar("enable");
    $("#" + username + "_content").css('background-color', '#F20000');
    var until = new Date((begin + length) * 1000);
    $("#" + username + "_content .timer").countdown({
        until: until,
        format: 'MS',
        compact: true,
        serverSync: server_time,
        onTick: update_progress_func(username, length)
    });
}

function idle_timer(username) {
    "use strict";
    reset();
    $("#" + username + "_content").css('background-color', '#C0C0C0');
    $("#" + username + "_content .timer").html("IDLE");
}

function break_timer(username, begin, length) {
    "use strict";
    reset();
    $("#" + username + "_content .progress").progressbar("enable");
    $("#" + username + "_content").css('background-color', '#336600');
    var until = new Date((begin + length) * 1000);
    $("#" + username + "_content .timer").countdown({
        until: until,
        format: 'MS',
        compact: true,
        serverSync: server_time,
        onTick: update_progress_func(username, length)
    });
}

function refresh(datas) {
    "use strict";
    var i, data, status, begin, error = datas.error;
    if (!error) {
        $("#main").html("");
        for (i = 0; i < datas.length; i += 1) {
            data = datas[i];
            $("#main").append('<div id="' + data.username + '_content" class="content"><div class="name">' + data.username + ' <span>' + data.pomodoro_today + '</span></div><div class="timer"></div><div class="progress"></div></div>');
            $("#" + data.username + "_content .progress").progressbar();
            status = data.status;
            switch (status) {
            case "IDLE":
                idle_timer(data.username, new Date(data.begin * 1000));
                break;
            case "S_BREAK":
            case "L_BREAK":
                begin = data.begin;
                break_timer(data.username, data.begin, data.length);
                break;
            case "POMODORO":
                begin = data.begin;
                pomodoro_timer(data.username, data.begin, data.length);
                break;
            }
        }
    }
}

function status_all() {
    "use strict";
    $.getJSON('api.php', {
        c: "status_all"
    }, refresh);
}

status_all();
window.setInterval(status_all, 1000);
