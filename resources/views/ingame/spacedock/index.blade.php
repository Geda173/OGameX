@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="spacedockcomponent" class="maincontent">
        <div id="spacedock">
            <header id="planet">
                <h2>@lang('Space Dock')</h2>
            </header>

            @if ($space_dock_level == 0)
                <div class="contentRS">
                    <div class="content-box-s">
                        <div class="content">
                            <p style="padding: 20px;">@lang('You need to build a Space Dock first to repair ships from wreckage.')</p>
                        </div>
                    </div>
                </div>
            @else
                {{-- Ready for Pickup Section --}}
                @if (!empty($ready_for_pickup) && count($ready_for_pickup) > 0)
                    <div class="contentRS" style="margin-bottom: 20px;">
                        <div class="content-box-s">
                            <div class="header">
                                <h3>@lang('Ready for Pickup')</h3>
                            </div>
                            <div class="content">
                                <table class="list">
                                    <thead>
                                    <tr>
                                        <th>@lang('Ship')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Completed')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($ready_for_pickup as $repair)
                                        <tr>
                                            <td>{{ $repair['ship_name'] }}</td>
                                            <td>{{ number_format($repair['ship_amount']) }}</td>
                                            <td>{{ date('Y-m-d H:i:s', $repair['completed_at']) }}</td>
                                            <td>
                                                <button class="btn-claim-repair" data-repair-id="{{ $repair['id'] }}">
                                                    @lang('Claim Ships')
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div style="margin-top: 10px;">
                                    <button id="btn-claim-all-repairs" class="btn-claim-all">
                                        @lang('Claim All Ships')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Active Repairs Section --}}
                @if (!empty($repair_queue) && count($repair_queue) > 0)
                    <div class="contentRS" style="margin-bottom: 20px;">
                        <div class="content-box-s">
                            <div class="header">
                                <h3>@lang('Repairs in Progress')</h3>
                            </div>
                            <div class="content">
                                <table class="list">
                                    <thead>
                                    <tr>
                                        <th>@lang('Ship')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Time Remaining')</th>
                                        <th>@lang('Action')</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($repair_queue as $repair)
                                        <tr>
                                            <td>{{ $repair['ship_name'] }}</td>
                                            <td>{{ number_format($repair['ship_amount']) }}</td>
                                            <td>
                                                <span class="countdown" data-time-end="{{ $repair['time_end'] }}">
                                                    {{ gmdate('H:i:s', $repair['time_remaining']) }}
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn-cancel-repair" data-repair-id="{{ $repair['id'] }}">
                                                    @lang('Cancel')
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Available Wreckage Section --}}
                @if (!empty($wreckage_data) && count($wreckage_data) > 0)
                    <div class="contentRS">
                        <div class="content-box-s">
                            <div class="header">
                                <h3>@lang('Available Wreckage')</h3>
                            </div>
                            <div class="content">
                                @foreach ($wreckage_data as $wreckage)
                                    <div class="wreckage-group" style="margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 15px;">
                                        <h4>@lang('Battle Report') #{{ $wreckage['battle_report_id'] }}
                                            <small>({{ $wreckage['created_at']->diffForHumans() }})</small>
                                            <button class="btn-dismiss-wreckage" data-battle-report-id="{{ $wreckage['battle_report_id'] }}"
                                                    style="float: right; font-size: 12px;">
                                                @lang('Dismiss Wreckage')
                                            </button>
                                        </h4>
                                        <table class="list" style="margin-top: 10px;">
                                            <thead>
                                            <tr>
                                                <th>@lang('Ship')</th>
                                                <th>@lang('Available')</th>
                                                <th>@lang('Repair Cost')</th>
                                                <th>@lang('Amount to Repair')</th>
                                                <th>@lang('Action')</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach ($wreckage['ships'] as $ship)
                                                <tr>
                                                    <td>{{ $ship['name'] }}</td>
                                                    <td>{{ number_format($ship['amount']) }}</td>
                                                    <td>
                                                        @if ($ship['metal_cost'] > 0)
                                                            <span class="metal">{{ number_format($ship['metal_cost']) }}</span>
                                                        @endif
                                                        @if ($ship['crystal_cost'] > 0)
                                                            <span class="crystal">{{ number_format($ship['crystal_cost']) }}</span>
                                                        @endif
                                                        @if ($ship['deuterium_cost'] > 0)
                                                            <span class="deuterium">{{ number_format($ship['deuterium_cost']) }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <input type="number"
                                                               class="repair-amount"
                                                               min="1"
                                                               max="{{ $ship['amount'] }}"
                                                               value="{{ $ship['amount'] }}"
                                                               data-battle-report-id="{{ $wreckage['battle_report_id'] }}"
                                                               data-ship-machine-name="{{ $ship['machine_name'] }}"
                                                               style="width: 80px;" />
                                                    </td>
                                                    <td>
                                                        <button class="btn-start-repair"
                                                                data-battle-report-id="{{ $wreckage['battle_report_id'] }}"
                                                                data-ship-machine-name="{{ $ship['machine_name'] }}"
                                                                data-max-amount="{{ $ship['amount'] }}">
                                                            @lang('Start Repair')
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- No Wreckage Message --}}
                @if (empty($wreckage_data) || count($wreckage_data) == 0)
                    @if (empty($repair_queue) || count($repair_queue) == 0)
                        @if (empty($ready_for_pickup) || count($ready_for_pickup) == 0)
                            <div class="contentRS">
                                <div class="content-box-s">
                                    <div class="content">
                                        <p style="padding: 20px;">@lang('No wreckage available for repair. Wreckage appears after battles with more than 150,000 units destroyed.')</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                @endif
            @endif
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function() {
            // CSRF token for AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Start repair button
            $('.btn-start-repair').on('click', function() {
                var button = $(this);
                var battleReportId = button.data('battle-report-id');
                var shipMachineName = button.data('ship-machine-name');
                var input = $('.repair-amount[data-battle-report-id="' + battleReportId + '"][data-ship-machine-name="' + shipMachineName + '"]');
                var amount = parseInt(input.val());
                var maxAmount = button.data('max-amount');

                if (amount <= 0 || amount > maxAmount) {
                    alert('Invalid amount. Please enter a value between 1 and ' + maxAmount);
                    return;
                }

                button.prop('disabled', true).text('Starting...');

                $.ajax({
                    url: '{{ route('spacedock.startrepair') }}',
                    method: 'POST',
                    data: {
                        battle_report_id: battleReportId,
                        ship_machine_name: shipMachineName,
                        amount: amount
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Unknown error'));
                            button.prop('disabled', false).text('Start Repair');
                        }
                    },
                    error: function(xhr) {
                        var error = 'Unknown error';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            error = xhr.responseJSON.error;
                        }
                        alert('Error: ' + error);
                        button.prop('disabled', false).text('Start Repair');
                    }
                });
            });

            // Cancel repair button
            $('.btn-cancel-repair').on('click', function() {
                var button = $(this);
                var repairQueueId = button.data('repair-id');

                if (!confirm('Are you sure you want to cancel this repair? Resources will be refunded.')) {
                    return;
                }

                button.prop('disabled', true).text('Canceling...');

                $.ajax({
                    url: '{{ route('spacedock.cancelrepair') }}',
                    method: 'POST',
                    data: {
                        repair_queue_id: repairQueueId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Unknown error'));
                            button.prop('disabled', false).text('Cancel');
                        }
                    },
                    error: function(xhr) {
                        var error = 'Unknown error';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            error = xhr.responseJSON.error;
                        }
                        alert('Error: ' + error);
                        button.prop('disabled', false).text('Cancel');
                    }
                });
            });

            // Claim repair button
            $('.btn-claim-repair').on('click', function() {
                var button = $(this);
                var repairQueueId = button.data('repair-id');

                button.prop('disabled', true).text('Claiming...');

                $.ajax({
                    url: '{{ route('spacedock.claimrepairs') }}',
                    method: 'POST',
                    data: {
                        repair_queue_id: repairQueueId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Unknown error'));
                            button.prop('disabled', false).text('Claim Ships');
                        }
                    },
                    error: function(xhr) {
                        var error = 'Unknown error';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            error = xhr.responseJSON.error;
                        }
                        alert('Error: ' + error);
                        button.prop('disabled', false).text('Claim Ships');
                    }
                });
            });

            // Claim all repairs button
            $('#btn-claim-all-repairs').on('click', function() {
                var button = $(this);

                if (!confirm('Claim all ready repairs?')) {
                    return;
                }

                button.prop('disabled', true).text('Claiming...');

                $.ajax({
                    url: '{{ route('spacedock.claimrepairs') }}',
                    method: 'POST',
                    data: {},
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Unknown error'));
                            button.prop('disabled', false).text('Claim All Ships');
                        }
                    },
                    error: function(xhr) {
                        var error = 'Unknown error';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            error = xhr.responseJSON.error;
                        }
                        alert('Error: ' + error);
                        button.prop('disabled', false).text('Claim All Ships');
                    }
                });
            });

            // Dismiss wreckage button
            $('.btn-dismiss-wreckage').on('click', function() {
                var button = $(this);
                var battleReportId = button.data('battle-report-id');

                if (!confirm('Are you sure you want to dismiss this wreckage? It will be permanently lost.')) {
                    return;
                }

                button.prop('disabled', true).text('Dismissing...');

                $.ajax({
                    url: '{{ route('spacedock.dismisswreckage') }}',
                    method: 'POST',
                    data: {
                        battle_report_id: battleReportId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.error || 'Unknown error'));
                            button.prop('disabled', false).text('Dismiss Wreckage');
                        }
                    },
                    error: function(xhr) {
                        var error = 'Unknown error';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            error = xhr.responseJSON.error;
                        }
                        alert('Error: ' + error);
                        button.prop('disabled', false).text('Dismiss Wreckage');
                    }
                });
            });

            // Simple countdown timer for active repairs
            function updateCountdowns() {
                $('.countdown').each(function() {
                    var element = $(this);
                    var timeEnd = parseInt(element.data('time-end'));
                    var now = Math.floor(Date.now() / 1000);
                    var remaining = timeEnd - now;

                    if (remaining <= 0) {
                        element.text('Completed!');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        var hours = Math.floor(remaining / 3600);
                        var minutes = Math.floor((remaining % 3600) / 60);
                        var seconds = remaining % 60;

                        element.text(
                            String(hours).padStart(2, '0') + ':' +
                            String(minutes).padStart(2, '0') + ':' +
                            String(seconds).padStart(2, '0')
                        );
                    }
                });
            }

            // Update countdowns every second
            if ($('.countdown').length > 0) {
                setInterval(updateCountdowns, 1000);
                updateCountdowns();
            }
        });
    </script>

@endsection
