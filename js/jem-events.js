jQuery(function($){
    var offset = 0;
    var atts = (typeof jemEvents !== 'undefined' && jemEvents.atts) ? jemEvents.atts : {};

    // initial offset is atts.offset or 0
    offset = parseInt(atts.offset || 0, 10);

    $('#jem-load-more').on('click', function(){
        var $btn = $(this);
        $btn.prop('disabled', true).text('Loading...');
        $.post(jemEvents.ajaxurl, {
            action: 'jem_events_load_more',
            offset: offset,
            atts: atts
        }, function(res){
            if (res.html) {
                $('#jem-events-container').append(res.html);
                offset += parseInt(atts.max || 10, 10);
            }
            if (!res.more) $btn.hide();
            $btn.prop('disabled', false).text('Load More');
        }).fail(function(){
            $btn.prop('disabled', false).text('Load More');
        });
    });

    // If you insert a filter form with id jem-filter, the script supports it:
    $('#jem-filter').on('submit', function(e){
        e.preventDefault();
        var data = $(this).serializeArray();
        atts = {};
        data.forEach(function(it){ atts[it.name] = it.value; });
        offset = 0;
        $.post(jemEvents.ajaxurl, {
            action: 'jem_events_load_more',
            offset: offset,
            atts: atts
        }, function(res){
            $('#jem-events-container').html(res.html);
            if (res.more) $('#jem-load-more').show();
            else $('#jem-load-more').hide();
        });
    });
});
