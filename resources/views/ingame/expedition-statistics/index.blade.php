@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="inhalt">
        <div id="expeditionStatisticsContent">
            <div class="header">
                <h2>Expedition Statistics</h2>
            </div>
            <div class="content">
                <!-- Overview Statistics -->
                <div class="statistics-section">
                    <h3>Overview</h3>
                    <table class="table statistics-table">
                        <tbody>
                            <tr>
                                <td class="label">Total Expeditions:</td>
                                <td class="value">{{ number_format($statistics['overview']['total_expeditions']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Completed Expeditions:</td>
                                <td class="value">{{ number_format($statistics['overview']['completed_expeditions']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">In Progress:</td>
                                <td class="value">{{ number_format($statistics['overview']['in_progress_expeditions']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Success Rate:</td>
                                <td class="value">{{ $statistics['overview']['success_rate'] }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Outcome Distribution -->
                <div class="statistics-section">
                    <h3>Outcome Distribution</h3>
                    <table class="table statistics-table">
                        <thead>
                            <tr>
                                <th>Outcome Type</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($statistics['outcomes'] as $outcomeType => $outcomeData)
                                <tr>
                                    <td>{{ ucfirst(str_replace('_', ' ', str_replace('expedition_', '', $outcomeType))) }}</td>
                                    <td>{{ number_format($outcomeData['count']) }}</td>
                                    <td>{{ $outcomeData['percentage'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" style="text-align: center;">No expedition outcomes recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Resources Gained -->
                <div class="statistics-section">
                    <h3>Total Resources Gained</h3>
                    <table class="table statistics-table">
                        <tbody>
                            <tr>
                                <td class="label">Metal:</td>
                                <td class="value">{{ number_format($statistics['resources']['total_metal']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Crystal:</td>
                                <td class="value">{{ number_format($statistics['resources']['total_crystal']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Deuterium:</td>
                                <td class="value">{{ number_format($statistics['resources']['total_deuterium']) }}</td>
                            </tr>
                            @if ($statistics['resources']['total_dark_matter'] > 0)
                                <tr>
                                    <td class="label">Dark Matter:</td>
                                    <td class="value">{{ number_format($statistics['resources']['total_dark_matter']) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Ships Gained -->
                @if (count($statistics['ships']) > 0)
                    <div class="statistics-section">
                        <h3>Total Ships Gained</h3>
                        <table class="table statistics-table">
                            <thead>
                                <tr>
                                    <th>Ship Type</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statistics['ships'] as $shipType => $count)
                                    <tr>
                                        <td>{{ ucfirst(str_replace('_', ' ', $shipType)) }}</td>
                                        <td>{{ number_format($count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- Battle Statistics -->
                <div class="statistics-section">
                    <h3>Battle Statistics</h3>
                    <table class="table statistics-table">
                        <tbody>
                            <tr>
                                <td class="label">Total Battles:</td>
                                <td class="value">{{ number_format($statistics['battles']['total_battles']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Pirate Battles:</td>
                                <td class="value">{{ number_format($statistics['battles']['pirate_battles']) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Alien Battles:</td>
                                <td class="value">{{ number_format($statistics['battles']['alien_battles']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Timeline Chart (Last 30 Days) -->
                @if (count($statistics['timeline']) > 0)
                    <div class="statistics-section">
                        <h3>Expedition Timeline (Last 30 Days)</h3>
                        <table class="table statistics-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Expeditions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($statistics['timeline'] as $timelineData)
                                    <tr>
                                        <td>{{ $timelineData['date'] }}</td>
                                        <td>{{ number_format($timelineData['count']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .statistics-section {
            margin-bottom: 30px;
            padding: 15px;
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
        }

        .statistics-section h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #6f9fc8;
            font-size: 18px;
        }

        .statistics-table {
            width: 100%;
            border-collapse: collapse;
        }

        .statistics-table th,
        .statistics-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .statistics-table th {
            background-color: rgba(0, 0, 0, 0.3);
            color: #6f9fc8;
            font-weight: bold;
        }

        .statistics-table td.label {
            width: 50%;
            font-weight: bold;
        }

        .statistics-table td.value {
            width: 50%;
            text-align: right;
        }

        .statistics-table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
    </style>

@endsection
