function update_progress_func(user_name, length) {
    "use strict";
    return function (periods) {
        var remaining = periods[5] * 60 + periods[6],
            perc = 100 - (remaining / length) * 100;
        $("#" + user_name + "_content .progress").progressbar("value", perc);
    };
}

function reset(user_name) {
    "use strict";
    $("#" + user_name + "_content .timer").countdown('destroy');
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

function pomodoro_timer(user_name, begin, length) {
    "use strict";
    reset();
    $("#" + user_name + "_content .progress").progressbar("enable");
    $("#" + user_name + "_content").css('background-color', '#F20000');
    var until = new Date((begin + length) * 1000);
    $("#" + user_name + "_content .timer").countdown({
        until: until,
        format: 'MS',
        compact: true,
        serverSync: server_time,
        onTick: update_progress_func(user_name, length)
    });
}

function idle_timer(user_name) {
    "use strict";
    reset();
    $("#" + user_name + "_content").css('background-color', '#C0C0C0');
    $("#" + user_name + "_content .timer").html("IDLE");
}

function break_timer(user_name, begin, length) {
    "use strict";
    reset();
    $("#" + user_name + "_content .progress").progressbar("enable");
    $("#" + user_name + "_content").css('background-color', '#336600');
    var until = new Date((begin + length) * 1000);
    $("#" + user_name + "_content .timer").countdown({
        until: until,
        format: 'MS',
        compact: true,
        serverSync: server_time,
        onTick: update_progress_func(user_name, length)
    });
}

function refresh(datas) {
    "use strict";
    var i, data, status, begin, error = datas.error;
    if (!error) {
        $("#main").html("");
        for (i = 0; i < datas.length; i += 1) {
            data = datas[i];
            $("#main").append('<div id="' + data.user_name + '_content" class="content"><div class="name">' + data.user_name + ' <span>' + data.pomodoro_today + '</span></div><div class="timer"></div><div class="progress"></div></div>');
            $("#" + data.user_name + "_content .progress").progressbar();
            status = data.status;
            switch (status) {
            case "IDLE":
                idle_timer(data.user_name, new Date(data.begin * 1000));
                break;
            case "S_BREAK":
            case "L_BREAK":
                begin = data.begin;
                break_timer(data.user_name, data.begin, data.length);
                break;
            case "POMODORO":
                begin = data.begin;
                pomodoro_timer(data.user_name, data.begin, data.length);
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
