@extends('ingame.layouts.main')

@section('content')
    <div id="planetRelocate" class="inhalt">
        <div id="planet">
            <h2>@lang('Relocate Planet')</h2>

            <div class="contentBox">
                <div style="padding: 20px;">
                    <p>
                        @lang('Relocate your planet to a new position in the universe using Dark Matter.')
                        <br>
                        @lang('Current position:') <strong>{{ $current_galaxy }}:{{ $current_system }}:{{ $current_position }}</strong>
                    </p>

                    <div style="margin: 20px 0; padding: 15px; background: #1a1a2e; border: 1px solid #333;">
                        <p style="font-weight: bold; color: #ffd700;">
                            @lang('Cost:') {{ number_format($relocation_cost) }} @lang('Dark Matter')
                        </p>
                        <p>
                            @lang('Available Dark Matter:') <span id="available-dm">{{ number_format($dark_matter) }}</span>
                        </p>
                    </div>

                    <div style="margin: 20px 0; padding: 15px; background: #2a2a3e; border: 1px solid #555;">
                        <p style="color: #ff9800; font-weight: bold;">
                            ⚠️ @lang('Restriction:')
                        </p>
                        <p>
                            @lang('Your planet can only be relocated to position') <strong>{{ $current_position }}</strong> @lang('in a different solar system.')
                            <br>
                            @lang('You cannot change the position number during relocation.')
                        </p>
                    </div>

                    <div id="relocateForm" style="margin-top: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label for="galaxy" style="display: inline-block; width: 100px;">@lang('Galaxy'):</label>
                            <input type="number" id="galaxy" name="galaxy" min="1" max="9" value="{{ $current_galaxy }}" required
                                   style="width: 100px; padding: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="system" style="display: inline-block; width: 100px;">@lang('System'):</label>
                            <input type="number" id="system" name="system" min="1" max="499" value="{{ $current_system }}" required
                                   style="width: 100px; padding: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="position" style="display: inline-block; width: 100px;">@lang('Position'):</label>
                            <input type="number" id="position" name="position" value="{{ $current_position }}" readonly
                                   style="width: 100px; padding: 5px; background: #333; color: #999; cursor: not-allowed;"
                                   title="@lang('Position cannot be changed - planets can only relocate to the same position in a different system')">
                            <span style="margin-left: 10px; color: #999;">(@lang('Fixed'))</span>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="button" id="relocateBtn" class="btn btn-primary" style="padding: 10px 20px;">
                                @lang('Relocate Planet')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        // Prevent the global movePlanet function from interfering
        var originalMovePlanet = window.movePlanet;

        $(document).ready(function() {
            // Temporarily disable the global movePlanet function on this page
            window.movePlanet = function() { return false; };

            function executePlanetRelocation() {
                var galaxy = $('#galaxy').val();
                var system = $('#system').val();
                var position = $('#position').val();

                $.post('{{ route('planetMove.relocate') }}', {
                    _token: '{{ csrf_token() }}',
                    galaxy: galaxy,
                    system: system,
                    position: position
                }, function(data) {
                    if (data.success) {
                        fadeBox(data.message, false);
                        // Reload after successful relocation
                        setTimeout(function() {
                            window.location.href = '{{ route('overview.index') }}';
                        }, 2000);
                    } else {
                        fadeBox(data.message, true);
                    }
                }).fail(function(xhr) {
                    var message = 'An error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    fadeBox(message, true);
                });
            }

            $('#relocateBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var galaxy = $('#galaxy').val();
                var system = $('#system').val();
                var position = $('#position').val();
                var cost = {{ $relocation_cost }};
                var costFormatted = '{{ number_format($relocation_cost) }}';

                // Build the question with position restriction info
                var question = 'Do you want to relocate your planet to <strong>' + galaxy + ':' + system + ':' + position + '</strong> for <span style="font-weight: bold; color: #ffd700;">' + costFormatted + ' Dark Matter</span>?<br><br>';
                question += '<span style="color: #ff9800; font-size: 0.9em;"><strong>⚠️ Note:</strong> Your planet can only be relocated to position <strong>' + position + '</strong> in a different solar system.</span>';

                errorBoxDecision('@lang('Relocate Planet')', question, '@lang('Yes')', '@lang('No')', executePlanetRelocation);

                return false;
            });
        });
    </script>
@endsection
