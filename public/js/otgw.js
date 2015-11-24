google.load('visualization', '1', {packages: ['corechart']});

function draw_chart(ot_data) {
    /* https://developers.google.com/chart/interactive/docs/reference#dataparam */
    var data = new google.visualization.DataTable(ot_data);

    var options = {
        title: 'OTGW Log',
        width: 1100,
        height: 700,
        interpolateNulls: true
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

    chart.draw(data, options);
}

function get_log(logfile) {
    var filename = logfile.substr(0, logfile.lastIndexOf('.'));
    var extension = logfile.substr(logfile.lastIndexOf('.') + 1);
    $('#logfiles-button').prop('disabled', true);
    $.getJSON('backend/logfile/' + filename + '/' + extension, function (data) {
        $('#logfiles-button').prop('disabled', false);
        draw_chart(data);
    });
}

$(document).ready(function () {

    $.getJSON('backend/logfiles', function (data) {
        var logfiles = data;
        var list = $('#logfiles');
        $('#logfiles-button').prop('disabled', false);
        $.each(logfiles, function (key, value) {
            var item = $('<li/>');
            var link = $('<a href="#" />').html(value);
            link.click(function () {
                get_log(value);
            });
            item.append(link);
            list.append(item);
        });
    });
});