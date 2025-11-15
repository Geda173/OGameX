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
        // When we have nested tabs (tab > subtab), we need to get the innermost active tab
        // which will be the subtab. We look for all active tabs and take the last one.
        var activeElements = $('.ui-tabs-active a');
        var currentTabElement = activeElements.last(); // Get the innermost (subtab if exists)
        var tabUrl = currentTabElement.attr('href') || currentTabElement.attr('rel');

        // Parse the tab and subtab from the URL
        var currentTab = '';
        var currentSubtab = '';

        if (tabUrl) {
            // Extract query string from URL
            var queryString = '';
            if (tabUrl.indexOf('?') !== -1) {
                queryString = tabUrl.split('?')[1];
            }

            // Parse parameters manually for better compatibility
            if (queryString) {
                var params = queryString.split('&');
                for (var i = 0; i < params.length; i++) {
                    var pair = params[i].split('=');
                    var key = decodeURIComponent(pair[0]);
                    var value = pair[1] ? decodeURIComponent(pair[1]) : '';

                    if (key === 'tab') {
                        currentTab = value;
                    } else if (key === 'subtab') {
                        currentSubtab = value;
                    }
                }
            }
        }

        // Validate that we have a tab before proceeding
        if (!currentTab) {
            console.error('Could not determine current tab from URL:', tabUrl);
            fadeBox('Error: Could not determine current tab. Please try again.', true);
            return;
        }

        errorBoxDecision(
            'Delete All Messages',
            'Are you sure you want to delete all messages in this tab? This action cannot be undone.',
            'Yes',
            'No',
            function() {
                // Prepare data for AJAX request
                var requestData = {
                    tab: currentTab,
                    _token: '{{ csrf_token() }}'
                };

                // Only include subtab if it has a value
                if (currentSubtab) {
                    requestData.subtab = currentSubtab;
                }

                // Make AJAX request to delete all messages
                $.ajax({
                    url: '{{ route('messages.deleteall') }}',
                    type: 'POST',
                    data: requestData,
                    success: function(response) {
                        if (response.success) {
                            // Show success message and redirect to the current tab
                            fadeBox('All messages deleted successfully.', false);

                            // Build the messages URL with current tab and subtab
                            var redirectUrl = '{{ route('messages.index') }}?tab=' + encodeURIComponent(currentTab);
                            if (currentSubtab) {
                                redirectUrl += '&subtab=' + encodeURIComponent(currentSubtab);
                            }

                            // Redirect after a short delay to show the success message
                            setTimeout(function() {
                                window.location.href = redirectUrl;
                            }, 500);
                        } else {
                            console.error('Server error:', response.error);
                            fadeBox(response.error || 'Failed to delete messages. Please try again.', true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting messages:', error, xhr.responseText);
                        var errorMsg = 'Failed to delete messages. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMsg = xhr.responseJSON.error;
                        }
                        fadeBox(errorMsg, true);
                    }
                });
            }
        );
    });
</script>