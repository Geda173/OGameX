@php /** @var OGame\Services\PlayerService $player */ @endphp
@php /** @var OGame\Services\PlanetService $planet */ @endphp

@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="jumpgatecomponent" class="maincontent">
        <div id="jumpgate">
            <div class="c-left"></div>
            <div class="c-right"></div>
            <header style="background-image:url({{ asset('img/headers/facilities/moon_1_41_42_43.jpg') }});">
                <h2>Jump Gate - {{ $planet->getPlanetName() }}</h2>
            </header>

            <div class="contentWrapper">
                @if ($cooldown_remaining > 0)
                    <div class="alert alert-warning">
                        <strong>Jump Gate on Cooldown</strong>
                        <p>Time remaining: <span id="cooldown-timer">{{ gmdate('H:i:s', $cooldown_remaining) }}</span></p>
                    </div>
                @endif

                <div class="jumpgate-section">
                    <h3>Select Destination Moon</h3>
                    <div class="destination-moons">
                        @if (empty($available_moons))
                            <p class="info">No other moons with jump gates available.</p>
                        @else
                            <form id="jumpgate-form" action="{{ route('jumpgate.execute') }}" method="POST">
                                @csrf
                                <div class="moon-list">
                                    @foreach ($available_moons as $moon)
                                        <div class="moon-item">
                                            <label>
                                                <input type="radio" name="target_moon_id" value="{{ $moon['id'] }}" required>
                                                <strong>{{ $moon['name'] }}</strong>
                                                [{{ $moon['coordinates']->galaxy }}:{{ $moon['coordinates']->system }}:{{ $moon['coordinates']->position }}]
                                                @if ($moon['cooldown_remaining'] > 0)
                                                    <span class="cooldown-text">(Cooldown: {{ gmdate('H:i:s', $moon['cooldown_remaining']) }})</span>
                                                @endif
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                <h3>Select Ships to Jump</h3>
                                <div class="ship-selection">
                                    @if (empty($available_ships))
                                        <p class="info">No ships available on this moon.</p>
                                    @else
                                        <table class="ship-table">
                                            <thead>
                                                <tr>
                                                    <th>Ship Type</th>
                                                    <th>Available</th>
                                                    <th>Amount to Jump</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($available_ships as $ship)
                                                    <tr>
                                                        <td>{{ $ship['title'] }}</td>
                                                        <td>{{ number_format($ship['amount']) }}</td>
                                                        <td>
                                                            <input type="number"
                                                                   name="ship_{{ $ship['machine_name'] }}"
                                                                   min="0"
                                                                   max="{{ $ship['amount'] }}"
                                                                   value="0"
                                                                   class="ship-amount-input">
                                                            <button type="button" class="btn-small btn-max" onclick="this.previousElementSibling.value = {{ $ship['amount'] }}">Max</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @endif
                                </div>

                                @if (!empty($available_ships) && !empty($available_moons))
                                    <div class="action-buttons">
                                        <button type="submit" class="btn btn-primary" @if($cooldown_remaining > 0) disabled @endif>
                                            Execute Jump
                                        </button>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>

                <div class="jumpgate-info">
                    <h3>Jump Gate Information</h3>
                    <ul>
                        <li>The Jump Gate allows instant transportation of fleets between moons.</li>
                        <li>Both the source and destination moons must have an active Jump Gate.</li>
                        <li>After a jump, both Jump Gates enter a cooldown period of 1 hour.</li>
                        <li>Ships are transferred instantly without consuming deuterium.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <style>
        .contentWrapper {
            padding: 20px;
        }

        .jumpgate-section {
            margin-bottom: 30px;
        }

        .jumpgate-section h3 {
            margin-top: 20px;
            margin-bottom: 15px;
            color: #6f9fc8;
            font-size: 18px;
        }

        .moon-list {
            margin-bottom: 20px;
        }

        .moon-item {
            padding: 10px;
            margin: 5px 0;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }

        .moon-item label {
            cursor: pointer;
            display: block;
        }

        .moon-item input[type="radio"] {
            margin-right: 10px;
        }

        .cooldown-text {
            color: #ff9900;
            font-style: italic;
        }

        .ship-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .ship-table th,
        .ship-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ship-table th {
            background: rgba(0, 0, 0, 0.3);
            font-weight: bold;
        }

        .ship-amount-input {
            width: 100px;
            padding: 5px;
            margin-right: 5px;
        }

        .btn-max {
            padding: 5px 10px;
            cursor: pointer;
        }

        .action-buttons {
            margin-top: 20px;
            text-align: center;
        }

        .btn-primary {
            padding: 10px 30px;
            font-size: 16px;
            cursor: pointer;
            background: #6f9fc8;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .btn-primary:hover:not(:disabled) {
            background: #5a8ab5;
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .jumpgate-info {
            margin-top: 30px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 5px;
        }

        .jumpgate-info ul {
            list-style-type: disc;
            margin-left: 20px;
        }

        .jumpgate-info li {
            margin: 8px 0;
        }

        .info {
            color: #aaa;
            font-style: italic;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }
    </style>

    <script>
        // Handle form submission via AJAX
        document.getElementById('jumpgate-form')?.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('{{ route("jumpgate.execute") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while executing the jump.');
            });
        });

        // Countdown timer
        @if ($cooldown_remaining > 0)
        let remainingSeconds = {{ $cooldown_remaining }};
        const timerElement = document.getElementById('cooldown-timer');

        setInterval(function() {
            if (remainingSeconds > 0) {
                remainingSeconds--;
                const hours = Math.floor(remainingSeconds / 3600);
                const minutes = Math.floor((remainingSeconds % 3600) / 60);
                const seconds = remainingSeconds % 60;
                timerElement.textContent = String(hours).padStart(2, '0') + ':' +
                                          String(minutes).padStart(2, '0') + ':' +
                                          String(seconds).padStart(2, '0');
            } else {
                window.location.reload();
            }
        }, 1000);
        @endif
    </script>

@endsection
