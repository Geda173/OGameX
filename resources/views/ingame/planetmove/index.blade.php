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

                    <div id="dmRelocateContainer" style="margin-top: 20px;">
                        <div style="margin-bottom: 15px;">
                            <label for="dmGalaxyInput" style="display: inline-block; width: 100px;">@lang('Galaxy'):</label>
                            <input type="number" id="dmGalaxyInput" name="dmGalaxyInput" min="1" max="9" value="{{ $current_galaxy }}" required
                                   style="width: 100px; padding: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="dmSystemInput" style="display: inline-block; width: 100px;">@lang('System'):</label>
                            <input type="number" id="dmSystemInput" name="dmSystemInput" min="1" max="499" value="{{ $current_system }}" required
                                   style="width: 100px; padding: 5px;">
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="dmPositionInput" style="display: inline-block; width: 100px;">@lang('Position'):</label>
                            <input type="number" id="dmPositionInput" name="dmPositionInput" value="{{ $current_position }}" readonly
                                   style="width: 100px; padding: 5px; background: #333; color: #999; cursor: not-allowed;"
                                   title="@lang('Position cannot be changed - planets can only relocate to the same position in a different system')">
                            <span style="margin-left: 10px; color: #999;">(@lang('Fixed'))</span>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="button" id="dmRelocateButton" class="btn btn-primary" style="padding: 10px 20px;">
                                @lang('Relocate Planet')
                            </button>
                        </div>
                    </div>

                    <script type="text/javascript">
        // Override movePlanet IMMEDIATELY before anything else can use it
        console.log('=== DM Relocate: Script loading ===');
        if (typeof window.movePlanet !== 'undefined') {
            console.log('=== DM Relocate: Blocking global movePlanet ===');
        }
        window.movePlanet = function() {
            console.log('=== DM Relocate: movePlanet() was called but blocked ===');
            return false;
        };

        // Use vanilla JS with capturing phase to intercept clicks FIRST
        (function() {
            console.log('=== DM Relocate: Setting up immediate handler ===');

            function handleRelocate(e) {
                console.log('=== DM Relocate: Click captured! ===');
                console.log('Target:', e.target);
                console.log('CurrentTarget:', e.currentTarget);

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                var galaxy = document.getElementById('dmGalaxyInput').value;
                var system = document.getElementById('dmSystemInput').value;
                var position = document.getElementById('dmPositionInput').value;

                console.log('=== DM Relocate: Values ===', {galaxy: galaxy, system: system, position: position});
                console.log('=== DM Relocate: Making fetch call ===');

                fetch('{{ route('planetMove.relocate') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        galaxy: galaxy,
                        system: system,
                        position: position
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('=== DM Relocate: Success response ===', data);
                    if (data.success) {
                        if (typeof fadeBox !== 'undefined') {
                            fadeBox(data.message, false);
                        } else {
                            alert(data.message);
                        }
                        setTimeout(function() {
                            window.location.href = '{{ route('overview.index') }}';
                        }, 2000);
                    } else {
                        if (typeof fadeBox !== 'undefined') {
                            fadeBox(data.message, true);
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.log('=== DM Relocate: Error ===', error);
                    var message = 'An error occurred: ' + error.message;
                    if (typeof fadeBox !== 'undefined') {
                        fadeBox(message, true);
                    } else {
                        alert(message);
                    }
                });

                return false;
            }

            // Wait for button to exist, then bind with capture phase
            function bindButton() {
                var button = document.getElementById('dmRelocateButton');
                if (button) {
                    console.log('=== DM Relocate: Button found, binding handler with CAPTURE phase ===');
                    // Use capture phase (true) to intercept before other handlers
                    button.addEventListener('click', handleRelocate, true);
                    console.log('=== DM Relocate: Handler bound ===');
                } else {
                    console.log('=== DM Relocate: Button not found yet, retrying... ===');
                    setTimeout(bindButton, 100);
                }
            }

            // Start immediately
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bindButton);
            } else {
                bindButton();
            }
        })();
    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
