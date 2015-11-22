function get_log( logfile )
{
    var filename = logfile.substr(0, logfile.lastIndexOf('.'));
    var extension = logfile.substr(logfile.lastIndexOf('.') + 1);
    $( '#logfiles-button' ).prop( 'disabled', true );
    $.getJSON( 'backend/logfile/' + filename + '/' + extension, function ( data )
    {
        $( '#logfiles-button' ).prop( 'disabled', false );
    } );
}

$( document ).ready( function ()
{

    $.getJSON( 'backend/logfiles', function ( data )
    {
        var logfiles = data;
        var list = $( '#logfiles' );
        $( '#logfiles-button' ).prop( 'disabled', false );
        $.each( logfiles, function ( key, value )
        {
            var item = $( '<li/>' );
            var link = $( '<a href="#" />' ).html( value );
            link.click( function ()
            {
                get_log( value );
            } );
            item.append( link );
            list.append( item );
        } );
    } );
} );