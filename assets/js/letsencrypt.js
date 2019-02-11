function lets_letsencrypt_helper(){
    //refresh the page without params when thickbox is closed
    jQuery( 'body' ).on( 'thickbox:removed', function() {
        window.location = window.location.href.split("&")[0];
    });
};