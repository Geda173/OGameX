<div class="jumpgate_overlay_content">
    <h2 style="margin: 0 0 15px 0; padding: 10px; background: rgba(0,0,0,0.3); text-align: center;">Jump Gate - {{ $planet->getPlanetName() }}</h2>
    <div style="padding: 15px;">

                            @if ($cooldown_remaining > 0)
                                <div class="textCenter" style="padding: 15px; margin: 15px 0; background: rgba(255, 193, 7, 0.2); border: 1px solid #ffc107; border-radius: 5px; color: #ffc107;">
                                    <strong>Jump Gate on Cooldown</strong>
                                    <p style="margin: 5px 0 0 0;">Time remaining: <span id="cooldown-timer">{{ gmdate('H:i:s', $cooldown_remaining) }}</span></p>
                                </div>
                            @endif

                            @if (empty($available_moons))
                                <div class="textCenter" style="padding: 20px; color: #ff6666;">
                                    <strong>No other moons with jump gates available.</strong>
                                </div>
                            @elseif (empty($available_ships))
                                <div class="textCenter" style="padding: 20px; color: #ff6666;">
                                    <strong>No ships available on this moon.</strong>
                                </div>
                            @else
                                <form id="jumpgate-form" action="{{ route('jumpgate.execute') }}" method="POST">
                                    @csrf

                                    <div style="margin-bottom: 20px;">
                                        <h3 style="color: #6f9fc8; margin-bottom: 10px; font-size: 14px;">Select Destination Moon</h3>
                                        <div class="moon-list">
                                            @foreach ($available_moons as $moon)
                                                <div class="moon-item" style="padding: 8px; margin: 5px 0; background: rgba(0, 0, 0, 0.3); border-radius: 3px;">
                                                    <label style="cursor: pointer; display: block;">
                                                        <input type="radio" name="target_moon_id" value="{{ $moon['id'] }}" required style="margin-right: 8px;">
                                                        <strong>{{ $moon['name'] }}</strong>
                                                        [{{ $moon['coordinates']->galaxy }}:{{ $moon['coordinates']->system }}:{{ $moon['coordinates']->position }}]
                                                        @if ($moon['cooldown_remaining'] > 0)
                                                            <span style="color: #ff9900; font-style: italic;">(Cooldown: {{ gmdate('H:i:s', $moon['cooldown_remaining']) }})</span>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div style="margin-bottom: 20px;">
                                        <h3 style="color: #6f9fc8; margin-bottom: 10px; font-size: 14px;">Select Ships to Jump</h3>
                                        <table cellpadding="5" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                                            <thead>
                                                <tr style="background: rgba(0, 0, 0, 0.3);">
                                                    <th style="text-align: left; padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Ship Type</th>
                                                    <th style="text-align: right; padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Available</th>
                                                    <th style="text-align: center; padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">Amount to Jump</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($available_ships as $ship)
                                                    <tr>
                                                        <td style="padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">{{ $ship['title'] }}</td>
                                                        <td style="padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); text-align: right;">{{ number_format($ship['amount']) }}</td>
                                                        <td style="padding: 8px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); text-align: center;">
                                                            <input type="number"
                                                                   name="ship_{{ $ship['machine_name'] }}"
                                                                   id="ship_{{ $ship['machine_name'] }}"
                                                                   min="0"
                                                                   max="{{ $ship['amount'] }}"
                                                                   value="0"
                                                                   class="textInput ship-amount-input"
                                                                   style="width: 80px; padding: 3px; margin-right: 5px;">
                                                            <button type="button"
                                                                    class="btn_blue"
                                                                    style="padding: 3px 8px; font-size: 11px;"
                                                                    onclick="document.getElementById('ship_{{ $ship['machine_name'] }}').value = {{ $ship['amount'] }}">Max</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="textCenter" style="padding-top: 10px;">
                                        <button type="submit" class="btn_blue" @if($cooldown_remaining > 0) disabled @endif>
                                            Execute Jump
                                        </button>
                                    </div>
                                </form>

                                <div class="textCenter" style="padding-top: 20px; font-size: 0.9em; color: #999;">
                                    <p>The Jump Gate allows instant transportation of fleets between moons.</p>
                                    <p>After a jump, both Jump Gates enter a cooldown period of 1 hour.</p>
                                </div>
                            @endif
    </div>
</div>

<script type="text/javascript">
// Handle form submission via AJAX
$('#jumpgate-form').off('submit').on('submit', function(e) {
    e.preventDefault();

    var form = $(this);
    var url = form.attr('action');
    var data = form.serialize();

    // Check if at least one ship is selected
    var hasShips = false;
    $('.ship-amount-input').each(function() {
        if (parseInt($(this).val()) > 0) {
            hasShips = true;
            return false;
        }
    });

    if (!hasShips) {
        fadeBox('Please select at least one ship to jump.', true);
        return;
    }

    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                fadeBox(response.message, false);
                $('.jumpgate_overlay_content').closest('.ui-dialog-content').dialog('close');
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                fadeBox(response.message || 'An error occurred', true);
            }
        },
        error: function(xhr) {
            var message = 'An error occurred while executing the jump.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            fadeBox(message, true);
        }
    });
});

// Countdown timer
@if ($cooldown_remaining > 0)
(function() {
    var remainingSeconds = {{ $cooldown_remaining }};
    var timerElement = $('#cooldown-timer');

    if (timerElement.length > 0) {
        setInterval(function() {
            if (remainingSeconds > 0) {
                remainingSeconds--;
                var hours = Math.floor(remainingSeconds / 3600);
                var minutes = Math.floor((remainingSeconds % 3600) / 60);
                var seconds = remainingSeconds % 60;
                timerElement.text(String(hours).padStart(2, '0') + ':' +
                                  String(minutes).padStart(2, '0') + ':' +
                                  String(seconds).padStart(2, '0'));
            } else {
                fadeBox('Jump Gate cooldown expired!', false);
                setTimeout(function() {
                    $('.jumpgate_overlay_content').closest('.ui-dialog-content').dialog('close');
                }, 1000);
            }
        }, 1000);
    }
})();
@endif
</script>
