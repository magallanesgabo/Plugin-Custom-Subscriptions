jQuery(document).ready(function($) {
    $('input, select, textarea').prop('disabled', true);
    $('#publish').prop('disabled', true);
    $('.editor-post-publish-button__button').prop('disabled', true);
    $('#poststuff').prepend('<div class="notice notice-warning"><p>Este post está archivado y no puede ser editado.</p></div>');
});