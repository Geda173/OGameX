<script type="text/javascript">
    var activeTabid = $('.ui-tabs-active a').attr('id'); //erster tab als default
    var hasSubtabs = $('div[aria-labelledby="' + activeTabid + '"] .tab_ctn div ul.subtabs').length;
    var activeSubtabid = '';

    $('.ui-tabs-active a').each(function () {
        activeSubtabid = $(this).attr('id');
    });

    var msgids = [];
    var index = 0;

    if (hasSubtabs > 0) {
        $('div[aria-labelledby="' + activeSubtabid + '"] .msg_new').each(function () {
            msgids[index] = $(this).data('msg-id');
            index++;
        });
    } else {
        $('div[aria-labelledby="' + activeTabid + '"] .msg_new').each(function () {
            msgids[index] = $(this).data('msg-id');
            index++;
        });
    }

    msgids = JSON.stringify(msgids);

    // Remove new message count indicator as soon as the tab is opened
    var message_menu_count = $('.comm_menu.messages span.new_msg_count');
    var message_tab_count = $('.ui-tabs-active .new_msg_count');

    if (message_menu_count.length > 0 && message_tab_count.length > 0) {
        var menuCount = parseInt(message_menu_count[0].innerHTML);
        var tabCount = parseInt(message_tab_count[0].innerHTML);
        var newCount = menuCount - tabCount;

        if (newCount > 0) {
            message_menu_count.html(newCount);
        } else {
            message_menu_count.remove();
        }
    }

    $('.ui-tabs-active .new_msg_count').remove();

    if (hasSubtabs > 0) {
        $('.ui-tabs-active a span:not(.icon_caption)').remove();
    }

    // Handle delete all messages button
    $('.delete-all-messages').off('click').on('click', function(e) {
        e.preventDefault();

        // Get current tab and subtab from URL
        var currentTabElement = $('.ui-tabs-active a').first();
        var tabUrl = currentTabElement.attr('href') || currentTabElement.attr('rel');

        // Parse the tab and subtab from the URL
        var urlParams = new URLSearchParams(tabUrl.split('?')[1]);
        var currentTab = urlParams.get('tab');
        var currentSubtab = urlParams.get('subtab') || '';

        errorBoxDecision(
            'Delete All Messages',
            'Are you sure you want to delete all messages in this tab? This action cannot be undone.',
            'Yes',
            'No',
            function() {
                // Make AJAX request to delete all messages
                $.ajax({
                    url: '{{ route('messages.deleteall') }}',
                    type: 'POST',
                    data: {
                        tab: currentTab,
                        subtab: currentSubtab,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        // Reload the current tab/subtab
                        ogame.messages.loadContentNew(tabUrl, null);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting messages:', error);
                        fadeBox('Failed to delete messages. Please try again.', true);
                    }
                });
            }
        );
    });
</script>