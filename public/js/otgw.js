google.load('visualization', '1.1', {packages: ['corechart', 'line']});

function draw_chart(ot_data) {
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Time');
    $.each(ot_data['ot_ids'], function (key, value) {
        data.addColumn('number', value);
    });

    $.each(ot_data['ot_data'], function (timestamp, values) {
        var row = [timestamp];
        $.each(values, function (id, value) {
            row.push(value);
        });
        data.addRow(row);
    });
    var options = {
        title: "OTGW Log",
        hAxis: {
            title: 'Time'
        },
        vAxis: {
            title: 'Temperature'
        },
        colors: ['#a52714', '#097138'],
        crosshair: {
            color: '#000',
            trigger: 'selection'
        },
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