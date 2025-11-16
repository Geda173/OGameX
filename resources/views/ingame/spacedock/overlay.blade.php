<div id="spacedock_overlay" style="background: #0d1014; color: #fff; min-width: 600px;">

    <!-- Wreckage Status or No Wreckage Message -->
    <div style="padding: 15px 20px; background: #0d1014; border-bottom: 1px solid #2a3a4a;">
        @if (count($wreckage_data) > 0)
            <!-- Wreckage Available -->
            <div style="margin-bottom: 10px;">
                <span style="color: #ff9800;">@lang('Wreckage burns up in'):</span>
                <span style="color: #fff; font-weight: bold; margin-left: 5px;">2d 17h 40m</span>
            </div>

            <div style="margin-bottom: 15px;">
                <span style="color: #6f9fc8;">@lang('Repairable Ships'):</span>
                <span style="color: #fff; margin-left: 5px;">
                    {{ number_format($total_repairable_ships) }} @lang('Ships')
                    @if($total_repair_time > 0)
                        @lang('in')
                        @php
                            $hours = floor($total_repair_time / 3600);
                            $minutes = floor(($total_repair_time % 3600) / 60);
                            $seconds = $total_repair_time % 60;
                        @endphp
                        @if($hours > 0){{ $hours }}h @endif{{ $minutes }}m {{ $seconds }}s
                    @endif
                </span>
            </div>

            <div style="margin-bottom: 15px; padding: 10px; background: #1a1d24; border-left: 3px solid #6f9fc8;">
                <div style="color: #6f9fc8; font-size: 12px; margin-bottom: 5px;">
                    <strong>ℹ️ @lang('Repair Information'):</strong>
                </div>
                <div style="color: #999; font-size: 11px; line-height: 1.5;">
                    • @lang('Repairs require no resources, only time')<br>
                    • @lang('Repair time: 30 minutes to 12 hours')<br>
                    • @lang('Ships auto-reactivate after 72 hours if not claimed')<br>
                    • @lang('Wreckage burns up after 72 hours')
                </div>
            </div>
        @else
            <div style="color: #999;">
                @lang('There is no wreckage at this position').
            </div>
        @endif

        <!-- Repair Queue Info -->
        @if (count($queue_data) > 0)
            <div style="margin-top: 15px; margin-bottom: 10px;">
                <span style="color: #6f9fc8;">@lang('Repair time remaining'):</span>
                <span style="color: #fff; margin-left: 5px;" class="countdown" data-end="{{ $queue_data[0]['time_end'] }}">...</span>

                <span style="color: #6f9fc8; margin-left: 15px;">@lang('Repaired Ships'):</span>
                <span style="color: #fff; margin-left: 5px;">
                    @php
                        $total_in_queue = array_sum(array_column($queue_data, 'ship_amount'));
                    @endphp
                    {{ $total_in_queue }} / {{ $total_repairable_ships }}
                </span>
            </div>
        @endif

        <!-- Ready Ships Info -->
        @if (count($pickup_data) > 0)
            <div style="margin-top: 15px; margin-bottom: 10px; color: #4CAF50;">
                <span style="font-weight: bold;">@lang('Repaired Ships'):</span>
                <span style="margin-left: 5px;">
                    @php
                        $total_ready = array_sum(array_column($pickup_data, 'ship_amount'));
                    @endphp
                    {{ number_format($total_ready) }} @lang('Ships ready for pickup')
                </span>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div style="padding: 15px 20px; background: #1a1d24; display: flex; justify-content: space-between; align-items: center;">
        <div>
            @if (count($pickup_data) > 0)
                <button class="btn-claim-all" style="padding: 8px 20px; background: #3a4a3a; color: #6ab871; border: 1px solid #6ab871; border-radius: 3px; cursor: pointer; font-weight: bold; margin-right: 10px;">
                    @lang('Collect')
                </button>
            @endif
            @if (count($wreckage_data) > 0)
                <button class="btn-leave-burn" style="padding: 8px 20px; background: #4a3a3a; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 3px; cursor: pointer; font-weight: bold; margin-right: 10px;">
                    @lang('Leave to burn up')
                </button>
                <button class="btn-start-all-repairs" style="padding: 8px 20px; background: #3a4a3a; color: #6ab871; border: 1px solid #6ab871; border-radius: 3px; cursor: pointer; font-weight: bold;">
                    @lang('Start repairs')
                </button>
            @endif
        </div>
        @if (count($wreckage_data) > 0 || count($queue_data) > 0 || count($pickup_data) > 0)
            <div>
                <a href="#" onclick="toggleDetails(); return false;" style="color: #6f9fc8; text-decoration: underline; font-size: 12px;">
                    @lang('Details')
                </a>
            </div>
        @endif
    </div>

    <!-- Details Section (Hidden by default) -->
    <div id="details-section" style="display: none; padding: 15px 20px; background: #0d1014; border-top: 1px solid #2a3a4a; max-height: 400px; overflow-y: auto;">

        <!-- Ready for Pickup Details -->
        @if (count($pickup_data) > 0)
            <div style="margin-bottom: 20px;">
                <h4 style="color: #4CAF50; margin: 0 0 10px 0; font-size: 14px;">@lang('Ready for Pickup')</h4>
                @foreach ($pickup_data as $pickup)
                    <div style="padding: 8px; margin-bottom: 5px; background: #1a1d24; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #fff;">{{ $pickup['ship_amount'] }}x {{ $pickup['ship_name'] }}</span>
                        <button class="btn-claim-repair" data-repair-id="{{ $pickup['id'] }}" style="padding: 6px 16px; background: #3a4a3a; color: #6ab871; border: 1px solid #6ab871; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: bold;">
                            @lang('Claim')
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Repairs in Progress Details -->
        @if (count($queue_data) > 0)
            <div style="margin-bottom: 20px;">
                <h4 style="color: #2196F3; margin: 0 0 10px 0; font-size: 14px;">@lang('Repairs in Progress')</h4>
                @foreach ($queue_data as $repair)
                    <div style="padding: 8px; margin-bottom: 5px; background: #1a1d24; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="color: #fff;">{{ $repair['ship_amount'] }}x {{ $repair['ship_name'] }}</span>
                            <span class="countdown" data-end="{{ $repair['time_end'] }}" style="margin-left: 10px; color: #ffa500;">...</span>
                        </div>
                        <button class="btn-cancel-repair" data-repair-id="{{ $repair['id'] }}" style="padding: 6px 16px; background: #4a3a3a; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 3px; cursor: pointer; font-size: 12px; font-weight: bold;">
                            @lang('Cancel')
                        </button>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Available Wreckage Details -->
        @if (count($wreckage_data) > 0)
            <div>
                <h4 style="color: #ff9800; margin: 0 0 10px 0; font-size: 14px;">@lang('Available Wreckage')</h4>
                @foreach ($wreckage_data as $wreckage)
                    <div style="margin-bottom: 15px; padding: 10px; background: #1a1d24; border: 1px solid #2a3a4a;">
                        <div style="color: #999; font-size: 11px; margin-bottom: 8px;">
                            @lang('Battle from') {{ $wreckage['created_at']->diffForHumans() }}
                        </div>
                        @foreach ($wreckage['ships'] as $ship)
                            <div style="padding: 8px; margin-bottom: 8px; background: #252930;">
                                <div style="margin-bottom: 5px;">
                                    <strong>{{ $ship['name'] }}</strong> - {{ $ship['amount'] }} @lang('available')
                                </div>
                                <div style="font-size: 11px; color: #6ab871;">
                                    @lang('Repair cost'): <strong>@lang('No resources required')</strong> - @lang('Time only')
                                </div>
                            </div>
                        @endforeach
                        <button class="btn-dismiss-wreckage"
                                data-battle-id="{{ $wreckage['battle_report_id'] }}"
                                style="margin-top: 5px; padding: 6px 12px; background: #4a3a3a; color: #e74c3c; border: 1px solid #e74c3c; border-radius: 3px; cursor: pointer; font-size: 11px; font-weight: bold;">
                            @lang('Dismiss All Wreckage from this Battle')
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

<script type="text/javascript">
function toggleDetails() {
    $('#details-section').slideToggle(300);
}

// Global countdown interval
var countdownInterval = null;

// Function to reload overlay content
function reloadOverlay() {
    var spaceDockOverlayUrl = "{{ route('spacedock.overlay') }}";
    $.get(spaceDockOverlayUrl, function(data) {
        // Clear countdown interval before replacing content
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }

        // Find the parent dialog and replace its content
        var $dialog = $('#spacedock_overlay').closest('.ui-dialog-content');
        if ($dialog.length > 0) {
            $dialog.html(data);
        } else {
            // Fallback: replace the overlay div directly
            $('#spacedock_overlay').replaceWith(data);
        }
    }).fail(function() {
        alert('Failed to reload Space Dock. Please close and reopen.');
    });
}

$(document).ready(function() {
    // Update countdowns
    function updateCountdowns() {
        $('.countdown').each(function() {
            var $element = $(this);
            var timeEnd = parseInt($element.data('end'));
            var now = Math.floor(Date.now() / 1000);
            var remaining = timeEnd - now;

            if (remaining <= 0) {
                $element.text('Ready!');
                $element.css('color', '#4CAF50');
            } else {
                var hours = Math.floor(remaining / 3600);
                var minutes = Math.floor((remaining % 3600) / 60);
                var seconds = remaining % 60;
                $element.text(
                    String(hours).padStart(2, '0') + 'h ' +
                    String(minutes).padStart(2, '0') + 'm ' +
                    String(seconds).padStart(2, '0') + 's'
                );
            }
        });
    }

    updateCountdowns();
    countdownInterval = setInterval(updateCountdowns, 1000);

    // Start all repairs button - repairs ALL wreckage immediately
    $(document).on('click', '.btn-start-all-repairs', function() {
        // Collect all wreckage data
        var repairRequests = [];
        @foreach ($wreckage_data as $wreckage)
            @foreach ($wreckage['ships'] as $ship)
                repairRequests.push({
                    battle_report_id: {{ $wreckage['battle_report_id'] }},
                    ship_machine_name: '{{ $ship['machine_name'] }}',
                    amount: {{ $ship['amount'] }}
                });
            @endforeach
        @endforeach

        // Send all repair requests - use allSettled to handle partial failures
        var promises = repairRequests.map(function(repair) {
            return $.ajax({
                url: '{{ route('spacedock.startrepair') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    battle_report_id: repair.battle_report_id,
                    ship_machine_name: repair.ship_machine_name,
                    amount: repair.amount
                }
            }).then(
                function(response) { return { status: 'fulfilled', value: response }; },
                function(error) { return { status: 'rejected', reason: error }; }
            );
        });

        Promise.all(promises).then(function(results) {
            var successCount = results.filter(function(r) { return r.status === 'fulfilled'; }).length;
            var failureCount = results.filter(function(r) { return r.status === 'rejected'; }).length;

            // Always reload overlay to show current state
            reloadOverlay();

            // Show message if some failed (but don't if all succeeded or all failed due to duplicates)
            if (failureCount > 0 && successCount > 0) {
                console.log('Some repairs started (' + successCount + ' succeeded, ' + failureCount + ' already in progress)');
            }
        });
    });

    // Cancel repair
    $(document).on('click', '.btn-cancel-repair', function() {
        var repairId = $(this).data('repair-id');

        $.ajax({
            url: '{{ route('spacedock.cancelrepair') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                repair_queue_id: repairId
            },
            success: function(response) {
                if (response.success) {
                    reloadOverlay();
                } else {
                    alert(response.message || 'Error canceling repair');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Claim single repair
    $(document).on('click', '.btn-claim-repair', function() {
        var repairId = $(this).data('repair-id');

        $.ajax({
            url: '{{ route('spacedock.claimrepairs') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                repair_queue_id: repairId
            },
            success: function(response) {
                if (response.success) {
                    reloadOverlay();
                } else {
                    alert(response.message || 'Error claiming repair');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Claim all repairs
    $(document).on('click', '.btn-claim-all', function() {
        $.ajax({
            url: '{{ route('spacedock.claimrepairs') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    reloadOverlay();
                } else {
                    alert(response.message || 'Error claiming repairs');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Dismiss wreckage
    $(document).on('click', '.btn-dismiss-wreckage', function() {
        var battleId = $(this).data('battle-id');

        if (!confirm('Are you sure you want to dismiss this wreckage? It cannot be recovered.')) {
            return;
        }

        $.ajax({
            url: '{{ route('spacedock.dismisswreckage') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                battle_report_id: battleId
            },
            success: function(response) {
                if (response.success) {
                    reloadOverlay();
                } else {
                    alert(response.message || 'Error dismissing wreckage');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Leave to burn up (dismiss all wreckage)
    $(document).on('click', '.btn-leave-burn', function() {
        if (!confirm('Are you sure you want to leave all wreckage to burn up? This action cannot be undone.')) {
            return;
        }

        var battleIds = [];
        @foreach ($wreckage_data as $wreckage)
            battleIds.push({{ $wreckage['battle_report_id'] }});
        @endforeach

        var promises = battleIds.map(function(battleId) {
            return $.ajax({
                url: '{{ route('spacedock.dismisswreckage') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    battle_report_id: battleId
                }
            });
        });

        Promise.all(promises).then(function() {
            reloadOverlay();
        }).catch(function() {
            alert('Error dismissing wreckage');
        });
    });
});
</script>
