<div id="spacedock_overlay" style="padding: 15px; min-width: 600px; max-height: 600px; overflow-y: auto;">
    <h3 style="margin-top: 0; color: #6f9fc8;">Space Dock (Level {{ $space_dock_level }})</h3>

    {{-- Ready for Pickup --}}
    @if (count($pickup_data) > 0)
        <div class="pickup-section" style="margin-bottom: 20px; padding: 10px; background: #0d1014; border: 1px solid #405064;">
            <h4 style="color: #6f9fc8; margin-top: 0;">Ready for Pickup</h4>
            @foreach ($pickup_data as $pickup)
                <div style="padding: 8px; margin-bottom: 5px; background: #1a1d24; border-left: 3px solid #4CAF50;">
                    <strong>{{ $pickup['ship_amount'] }}x {{ $pickup['ship_name'] }}</strong>
                    <button class="btn-claim-repair" data-repair-id="{{ $pickup['id'] }}" style="float: right; padding: 4px 12px; background: #4CAF50; color: white; border: none; cursor: pointer;">
                        Claim
                    </button>
                    <div style="clear: both;"></div>
                </div>
            @endforeach
            <button class="btn-claim-all" style="margin-top: 10px; padding: 6px 16px; background: #4CAF50; color: white; border: none; cursor: pointer;">
                Claim All Ships
            </button>
        </div>
    @endif

    {{-- Repairs in Progress --}}
    @if (count($queue_data) > 0)
        <div class="queue-section" style="margin-bottom: 20px; padding: 10px; background: #0d1014; border: 1px solid #405064;">
            <h4 style="color: #6f9fc8; margin-top: 0;">Repairs in Progress</h4>
            @foreach ($queue_data as $repair)
                <div style="padding: 8px; margin-bottom: 5px; background: #1a1d24; border-left: 3px solid #2196F3;">
                    <strong>{{ $repair['ship_amount'] }}x {{ $repair['ship_name'] }}</strong>
                    <span class="countdown" data-end="{{ $repair['time_end'] }}" style="margin-left: 10px; color: #ffa500;">
                        ...
                    </span>
                    <button class="btn-cancel-repair" data-repair-id="{{ $repair['id'] }}" style="float: right; padding: 4px 12px; background: #f44336; color: white; border: none; cursor: pointer;">
                        Cancel
                    </button>
                    <div style="clear: both;"></div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Available Wreckage --}}
    @if (count($wreckage_data) > 0)
        <div class="wreckage-section" style="padding: 10px; background: #0d1014; border: 1px solid #405064;">
            <h4 style="color: #6f9fc8; margin-top: 0;">Available Wreckage</h4>
            @foreach ($wreckage_data as $wreckage)
                <div class="wreckage-report" style="margin-bottom: 15px; padding: 10px; background: #1a1d24; border: 1px solid #2a3a4a;">
                    <div style="color: #999; font-size: 11px; margin-bottom: 8px;">
                        Battle from {{ $wreckage['created_at']->diffForHumans() }}
                    </div>
                    @foreach ($wreckage['ships'] as $ship)
                        <div style="padding: 8px; margin-bottom: 8px; background: #252930; border-left: 3px solid #ff9800;">
                            <div style="margin-bottom: 5px;">
                                <strong>{{ $ship['name'] }}</strong> ({{ $ship['amount'] }} available)
                            </div>
                            <div style="font-size: 11px; color: #999; margin-bottom: 5px;">
                                Repair cost per unit:
                                <span style="color: #c5c5c5;">{{ number_format($ship['metal_cost']) }}</span> Metal,
                                <span style="color: #7d9fc2;">{{ number_format($ship['crystal_cost']) }}</span> Crystal,
                                <span style="color: #5fa569;">{{ number_format($ship['deuterium_cost']) }}</span> Deuterium
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="number"
                                       class="repair-amount"
                                       data-battle-id="{{ $wreckage['battle_report_id'] }}"
                                       data-ship="{{ $ship['machine_name'] }}"
                                       min="1"
                                       max="{{ $ship['amount'] }}"
                                       value="{{ $ship['amount'] }}"
                                       style="width: 80px; padding: 4px; background: #0d1014; border: 1px solid #405064; color: white;">
                                <button class="btn-start-repair"
                                        data-battle-id="{{ $wreckage['battle_report_id'] }}"
                                        data-ship="{{ $ship['machine_name'] }}"
                                        style="padding: 4px 12px; background: #2196F3; color: white; border: none; cursor: pointer;">
                                    Start Repair
                                </button>
                            </div>
                        </div>
                    @endforeach
                    <button class="btn-dismiss-wreckage"
                            data-battle-id="{{ $wreckage['battle_report_id'] }}"
                            style="margin-top: 5px; padding: 4px 12px; background: #666; color: white; border: none; cursor: pointer; font-size: 11px;">
                        Dismiss All Wreckage from this Battle
                    </button>
                </div>
            @endforeach
        </div>
    @else
        <div style="padding: 20px; text-align: center; color: #999;">
            No wreckage available for repair.
        </div>
    @endif
</div>

<script type="text/javascript">
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
                    String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0')
                );
            }
        });
    }

    updateCountdowns();
    setInterval(updateCountdowns, 1000);

    // Start repair
    $('.btn-start-repair').on('click', function() {
        var $btn = $(this);
        var battleId = $btn.data('battle-id');
        var shipMachineName = $btn.data('ship');
        var $input = $('.repair-amount[data-battle-id="' + battleId + '"][data-ship="' + shipMachineName + '"]');
        var amount = parseInt($input.val());

        if (!amount || amount < 1) {
            alert('Please enter a valid amount');
            return;
        }

        $.ajax({
            url: '{{ route('spacedock.startrepair') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                battle_report_id: battleId,
                ship_machine_name: shipMachineName,
                amount: amount
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Error starting repair');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });

    // Cancel repair
    $('.btn-cancel-repair').on('click', function() {
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
                    location.reload();
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
    $('.btn-claim-repair').on('click', function() {
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
                    location.reload();
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
    $('.btn-claim-all').on('click', function() {
        $.ajax({
            url: '{{ route('spacedock.claimrepairs') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
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
    $('.btn-dismiss-wreckage').on('click', function() {
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
                    location.reload();
                } else {
                    alert(response.message || 'Error dismissing wreckage');
                }
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
            }
        });
    });
});
</script>
