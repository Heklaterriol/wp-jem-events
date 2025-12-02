jQuery(function($){
    let offset = 10;

    $('#jem-load-more').on('click', function(){
        $.post(jemEvents.ajaxurl, {
            action: 'jem_events_load_more',
            offset: offset,
            atts: jemEvents.atts
        }, function(res){
            $('#jem-events-container').append(res.html);
            offset += 10;
            if(!res.more) $('#jem-load-more').hide();
        });
    });

    $('#jem-filter').on('submit', function(e){
        e.preventDefault();
        jemEvents.atts = $(this).serializeArray().reduce((o,i)=>(o[i.name]=i.value,o), {});
        offset = 0;
        $.post(jemEvents.ajaxurl, {
            action: 'jem_events_load_more',
            offset: offset,
            atts: jemEvents.atts
        }, function(res){
            $('#jem-events-container').html(res.html);
            if(res.more) $('#jem-load-more').show();
        });
    });
});
