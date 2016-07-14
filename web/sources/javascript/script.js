// Set up
var $ajax_links     = $( 'a.ajax' ),
    $ajax_container = $( '.ajax-container' ),
    $body           = $( 'body' ),
    $title          = $( 'title' ),
    ajax_instance   = null,
    ajax_url        = null;

// Load
function load()
{
    // Ajax already running
    if( ajax_instance )
        return;

    // Hide (should use promises)
    $ajax_container.fadeTo( 400, 0, function()
    {
        // Ajax call
        ajax_instance = $.ajax( {
            url: ajax_url,
            dataType: 'json'
        } );

        // Ajax always event
        ajax_instance.always( function()
        {
            ajax_instance = null;
        } );

        // Ajax done (success) event
        ajax_instance.done( function( result )
        {
            // Update HTML
            $ajax_container.html( result.html );

            // Update <title>
            $title.text( result.title );

            // Update <body> classes
            $body.removeClass( $body.data( 'route-name' ) );
            $body.addClass( result.route_name );
            $body.data( 'route-name', result.route_name );

            // Show
            $ajax_container.fadeTo( 400, 1 );
        } );
    } );
}

// On ajax links click
$ajax_links.on( 'click', function()
{
    // Set ajax url
    ajax_url = $( this ).attr( 'href' );

    // Load
    load();

    // Prevent default
    return false;
} );
