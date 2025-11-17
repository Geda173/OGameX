@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="planet" class="shortHeader">
            <h2>@lang('Server settings')</h2>
        </div>

        <div id="buttonz">
            <div class="header">
                <h2>@lang('Server settings')</h2>
            </div>
            <form action="{{ route('admin.serversettings.update') }}" name="form" method="post">
                {{ csrf_field() }}
                <div class="content">
                    <div class="buddylistContent">
                        <p class="box_highlight textCenter no_buddies">@lang('Basic settings.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Universe name:')</label>
                                <div class="thefield">
                                    <input class="textInput w200" type="text" maxlength="20" value="{{ $universe_name }}" size="30" name="universe_name">
                                </div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('You can change the server settings below. Changes will be applied immediately.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Economy speed:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $economy_speed }}" size="2" maxlength="9" name="economy_speed">
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Research speed:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $research_speed }}" size="2" maxlength="9" name="research_speed">
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Fleet speed:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $fleet_speed }}" size="2" maxlength="9" name="fleet_speed">
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Planet fields bonus')</label>
                                <div class="thefield">
                                    <select name="planet_fields_bonus" class="w130" data-value="{{ $planet_fields_bonus }}">
                                        <option value="0"{{ $planet_fields_bonus == 0 ? ' selected' : '' }}>+0</option>
                                        <option value="10"{{ $planet_fields_bonus == 10 ? ' selected' : '' }}>+10</option>
                                        <option value="25"{{ $planet_fields_bonus == 25 ? ' selected' : '' }}>+25</option>
                                        <option value="30"{{ $planet_fields_bonus == 30 ? ' selected' : '' }}>+30</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('Note: basic income values below are multiplied by economy speed.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Basic metal income per hour:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $basic_income_metal }}" size="6" name="basic_income_metal"> (= {{ \OGame\Facades\AppUtil::formatNumber($basic_income_metal * $economy_speed) }})
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Basic crystal income per hour:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $basic_income_crystal }}" size="6" name="basic_income_crystal"> (= {{ \OGame\Facades\AppUtil::formatNumber($basic_income_crystal * $economy_speed) }})
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Basic deuterium income per hour:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $basic_income_deuterium }}" size="6" name="basic_income_deuterium"> (= {{ \OGame\Facades\AppUtil::formatNumber($basic_income_deuterium * $economy_speed) }})
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Basic energy income per hour:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $basic_income_energy }}" size="6" name="basic_income_energy"> (= {{ \OGame\Facades\AppUtil::formatNumber($basic_income_energy * $economy_speed) }})
                                </div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('New player settings.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Amount of planets to give to player upon registration')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $registration_planet_amount }}" size="6" name="registration_planet_amount">
                                </div>
                            </div>

                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Dark Matter bonus:')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $dark_matter_bonus }}" size="6" name="dark_matter_bonus">
                                </div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('Battle settings.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Battle Engine:')</label>
                                <div class="thefield">
                                <select name="battle_engine" class="w130" data-value="{{ $battle_engine }}">
                                    <option value="rust"{{ $battle_engine == 'rust' ? ' selected' : '' }}>Rust</option>
                                        <option value="php"{{ $battle_engine == 'php' ? ' selected' : '' }}>PHP</option>
                                    </select>
                                </div>
                                <div class="smallFont">@lang('The Rust battle engine is up to 200x more performant than PHP. Only switch to PHP if Rust cannot be run on your server.')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Alliance Combat System:')</label>
                                <div class="thefield">
                                    <square-checkbox class="square-checkbox">
                                        <input type="checkbox" id="square-checkAllianceCombatSystem" name="alliance_combat_system_on" value="1" {{ $alliance_combat_system_on ? 'checked' : '' }}>
                                        <label for="square-checkAllianceCombatSystem"></label>
                                    </square-checkbox>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Destroyed ships in debris fields:')</label>
                                <div class="thefield">
                                    <select name="debris_field_from_ships" class="w130" data-value="{{ $debris_field_from_ships }}">
                                        <option value="0"{{ $debris_field_from_ships == 0 ? ' selected' : '' }}>0%</option>
                                        <option value="30"{{ $debris_field_from_ships == 30 ? ' selected' : '' }}>30%</option>
                                        <option value="40"{{ $debris_field_from_ships == 40 ? ' selected' : '' }}>40%</option>
                                        <option value="50"{{ $debris_field_from_ships == 50 ? ' selected' : '' }}>50%</option>
                                        <option value="60"{{ $debris_field_from_ships == 60 ? ' selected' : '' }}>60%</option>
                                        <option value="70"{{ $debris_field_from_ships == 70 ? ' selected' : '' }}>70%</option>
                                        <option value="80"{{ $debris_field_from_ships == 80 ? ' selected' : '' }}>80%</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Defensive structures in debris fields:')</label>
                                <div class="thefield">
                                    <select name="debris_field_from_defense" class="w130" data-value="{{ $debris_field_from_defense }}">
                                        <option value="0"{{ $debris_field_from_defense == 0 ? ' selected' : '' }}>0%</option>
                                        <option value="30"{{ $debris_field_from_defense == 30 ? ' selected' : '' }}>30%</option>
                                        <option value="40"{{ $debris_field_from_defense == 40 ? ' selected' : '' }}>40%</option>
                                        <option value="50"{{ $debris_field_from_defense == 50 ? ' selected' : '' }}>50%</option>
                                        <option value="60"{{ $debris_field_from_defense == 60 ? ' selected' : '' }}>60%</option>
                                        <option value="70"{{ $debris_field_from_defense == 70 ? ' selected' : '' }}>70%</option>
                                        <option value="80"{{ $debris_field_from_defense == 80 ? ' selected' : '' }}>80%</option>
                                    </select>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Deuterium in debris fields:')</label>
                                <div class="thefield">
                                    <square-checkbox class="square-checkbox">
                                        <input type="checkbox" id="square-checkBoxDeuteriumInDebris" name="debris_field_deuterium_on" value="1" {{ $debris_field_deuterium_on ? 'checked' : '' }}>
                                        <label for="square-checkBoxDeuteriumInDebris"></label>
                                    </square-checkbox>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Maximum moon chance:')</label>
                                <div class="thefield">
                                    <select name="maximum_moon_chance" class="w130" data-value="{{ $maximum_moon_chance }}">
                                        <option value="0"{{ $maximum_moon_chance == 0 ? ' selected' : '' }}>0%</option>
                                        <option value="10"{{ $maximum_moon_chance == 10 ? ' selected' : '' }}>10%</option>
                                        <option value="20"{{ $maximum_moon_chance == 20 ? ' selected' : '' }}>20%</option>
                                        <option value="30"{{ $maximum_moon_chance == 30 ? ' selected' : '' }}>30%</option>
                                        <option value="40"{{ $maximum_moon_chance == 40 ? ' selected' : '' }}>40%</option>
                                        <option value="50"{{ $maximum_moon_chance == 50 ? ' selected' : '' }}>50%</option>
                                        <option value="60"{{ $maximum_moon_chance == 60 ? ' selected' : '' }}>60%</option>
                                        <option value="70"{{ $maximum_moon_chance == 70 ? ' selected' : '' }}>70%</option>
                                        <option value="80"{{ $maximum_moon_chance == 80 ? ' selected' : '' }}>80%</option>
                                        <option value="90"{{ $maximum_moon_chance == 90 ? ' selected' : '' }}>90%</option>
                                        <option value="100"{{ $maximum_moon_chance == 100 ? ' selected' : '' }}>100%</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('Expedition settings.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <div class="smallFont" style="margin-bottom: 15px; padding: 10px; background-color: #1e2328; border: 1px solid #4a5568; border-radius: 4px;">
                                    @lang('Configure which expedition outcomes can occur during expeditions. Unchecked outcomes will never happen. Use these settings to customize your server or isolate specific outcomes for testing.')
                                </div>
                            </div>
                        </div>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Bonus expedition slots (for events):')</label>
                                <div class="thefield">
                                    <select name="bonus_expedition_slots" class="w130" data-value="{{ $bonus_expedition_slots }}">
                                        <option value="0"{{ $bonus_expedition_slots == 0 ? ' selected' : '' }}>+0</option>
                                        <option value="1"{{ $bonus_expedition_slots == 1 ? ' selected' : '' }}>+1</option>
                                        <option value="2"{{ $bonus_expedition_slots == 2 ? ' selected' : '' }}>+2</option>
                                        <option value="3"{{ $bonus_expedition_slots == 3 ? ' selected' : '' }}>+3</option>
                                        <option value="4"{{ $bonus_expedition_slots == 4 ? ' selected' : '' }}>+4</option>
                                        <option value="5"{{ $bonus_expedition_slots == 5 ? ' selected' : '' }}>+5</option>
                                    </select>
                                </div>
                                <div class="smallFont">@lang('Adds extra expedition slots for all players on top of their Astrophysics-based slots. Use for weekend events or special occasions. Set to 0 to disable.')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Expedition rewards multiplier (for events):')</label>
                                <div class="thefield">
                                    <select name="expedition_rewards_multiplier" class="w130" data-value="{{ $expedition_rewards_multiplier }}">
                                        <option value="1.0"{{ $expedition_rewards_multiplier == 1.0 ? ' selected' : '' }}>1x (normal)</option>
                                        <option value="1.5"{{ $expedition_rewards_multiplier == 1.5 ? ' selected' : '' }}>1.5x</option>
                                        <option value="2.0"{{ $expedition_rewards_multiplier == 2.0 ? ' selected' : '' }}>2x</option>
                                        <option value="3.0"{{ $expedition_rewards_multiplier == 3.0 ? ' selected' : '' }}>3x</option>
                                        <option value="5.0"{{ $expedition_rewards_multiplier == 5.0 ? ' selected' : '' }}>5x</option>
                                        <option value="10.0"{{ $expedition_rewards_multiplier == 10.0 ? ' selected' : '' }}>10x</option>
                                    </select>
                                </div>
                                <div class="smallFont">@lang('Multiplies all expedition resource and ship rewards. Great for weekend events! Set to 1x for normal rewards.')</div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('Expedition outcome probabilities.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <div class="smallFont" style="margin-bottom: 15px; padding: 10px; background-color: #1e2328; border: 1px solid #4a5568; border-radius: 4px;">
                                    @lang('Control the probability of each expedition outcome using relative weights (0-100 scale). These numbers are relative to each other - higher = more likely. Set to 0 to disable an outcome. Examples: For 100% black holes, set Black Hole to 100 and all others to 0. For 50/50 ships vs resources, set both to 50 and others to 0. OGame default total: 100 points.')
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Find ships (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_ships }}" size="6" name="expedition_weight_ships">
                                </div>
                                <div class="smallFont">@lang('OGame default: 22')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Find resources (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_resources }}" size="6" name="expedition_weight_resources">
                                </div>
                                <div class="smallFont">@lang('OGame default: 32')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Fleet delay (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_delay }}" size="6" name="expedition_weight_delay">
                                </div>
                                <div class="smallFont">@lang('OGame default: 7')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Fleet speedup (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_speedup }}" size="6" name="expedition_weight_speedup">
                                </div>
                                <div class="smallFont">@lang('OGame default: 2')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Find nothing (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_nothing }}" size="6" name="expedition_weight_nothing">
                                </div>
                                <div class="smallFont">@lang('OGame default: 18.6')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Black hole / Fleet loss (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_black_hole }}" size="6" name="expedition_weight_black_hole">
                                </div>
                                <div class="smallFont">@lang('OGame default: 0.33')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Battle pirates (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_pirates }}" size="6" name="expedition_weight_pirates">
                                </div>
                                <div class="smallFont">@lang('OGame default: 5.8')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Battle aliens (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_aliens }}" size="6" name="expedition_weight_aliens">
                                </div>
                                <div class="smallFont">@lang('OGame default: 2.6')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Find dark matter (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_dark_matter }}" size="6" name="expedition_weight_dark_matter">
                                </div>
                                <div class="smallFont">@lang('OGame default: 9')</div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Find merchant (%):')</label>
                                <div class="thefield">
                                    <input type="text" pattern="[0-9]*\.?[0-9]*" class="textInput w50 textCenter textBeefy" value="{{ $expedition_weight_merchant }}" size="6" name="expedition_weight_merchant">
                                </div>
                                <div class="smallFont">@lang('OGame default: 0.7')</div>
                            </div>
                        </div>

                        <p class="box_highlight textCenter no_buddies">@lang('Galaxy settings.')</p>

                        <div class="group bborder" style="display: block;">
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Empty systems are ignored:')</label>
                                <div class="thefield">
                                    <square-checkbox class="square-checkbox">
                                        <input type="checkbox" id="square-checkIgnoreEmptySystems" name="ignore_empty_systems_on" value="1" {{ $ignore_empty_systems_on ? 'checked' : '' }}>
                                        <label for="square-checkIgnoreEmptySystems"></label>
                                    </square-checkbox>
                                </div>
                            </div>
                            <div class="fieldwrapper">
                                <label class="styled textBeefy">@lang('Inactive systems are ignored:')</label>
                                <div class="thefield">
                                    <square-checkbox class="square-checkbox">
                                        <input type="checkbox" id="square-checkIgnoreInactiveSystems" name="ignore_inactive_systems_on" value="1" {{ $ignore_inactive_systems_on ? 'checked' : '' }}>
                                        <label for="square-checkIgnoreInactiveSystems"></label>
                                    </square-checkbox>
                                </div>
                            </div>
                            <div class="fieldwrapper" style="margin-bottom: 50px;">
                                <label class="styled textBeefy">@lang('Number of galaxies:')</label>
                                <div class="thefield">
                                    <select name="number_of_galaxies" class="w130" data-value="{{ $number_of_galaxies }}">
                                        <option value="5"{{ $number_of_galaxies == 5 ? ' selected' : '' }}>5</option>
                                        <option value="6"{{ $number_of_galaxies == 6 ? ' selected' : '' }}>6</option>
                                        <option value="7"{{ $number_of_galaxies == 7 ? ' selected' : '' }}>7</option>
                                        <option value="8"{{ $number_of_galaxies == 8 ? ' selected' : '' }}>8</option>
                                        <option value="9"{{ $number_of_galaxies == 9 ? ' selected' : '' }}>9</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="footer">
                        <div class="textCenter">
                            <input type="submit" class="btn_blue" value="@lang('Save settings')">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <script language="javascript">
            initBBCodes();
            initOverlays();
        </script>
    </div>

@endsection
