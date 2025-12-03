jQuery(function($){
    $(document).on('click', '.jem-insert-tag', function(e){
        e.preventDefault();
        var tag = $(this).data('tag');
        var $ta = $('#jem_template');
        if (!$ta.length) return;
        // insert at cursor position
        var textarea = $ta.get(0);
        if (document.selection) {
            textarea.focus();
            var sel = document.selection.createRange();
            sel.text = tag;
        } else if (textarea.selectionStart || textarea.selectionStart === 0) {
            var startPos = textarea.selectionStart;
            var endPos = textarea.selectionEnd;
            var scrollTop = textarea.scrollTop;
            textarea.value = textarea.value.substring(0, startPos) + tag + textarea.value.substring(endPos, textarea.value.length);
            textarea.focus();
            textarea.selectionStart = startPos + tag.length;
            textarea.selectionEnd = startPos + tag.length;
            textarea.scrollTop = scrollTop;
        } else {
            textarea.value += tag;
            textarea.focus();
        }
    });
});
