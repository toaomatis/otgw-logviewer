$(document).ready(function () {
    $('#log_file').change(function () {
        var fileName = $(this).val();
        fileName = fileName.replace(/C:\\fakepath\\/i, '');
        fileName = fileName.replace('otlog-', '');
        fileName = fileName.replace('.txt', '');
        $('#log_date').val(fileName);
    });
});