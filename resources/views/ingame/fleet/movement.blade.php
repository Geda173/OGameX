@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif">
    </div>

    <div id="movementcomponent" class="maincontent">
        <div id="movement">
            <div id="inhalt">
                <header id="planet" class="planet-header ">
                    <h2>@lang('Fleet movement')</h2>
                    <a class="toggleHeader" data-name="movement">
                        <img alt="" src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="22" width="22">
                    </a>
                </header>
                <div class="c-left"></div>
                <div class="c-right"></div>
                <div class="fleetStatus">
            <span class="reload">
                <a class="dark_highlight_tablet" href="javascript:void(0);" onclick="reloadPage();">
                    <span class="icon icon_reload"></span>
                    <span>@lang('Reload')</span>
                </a>
            </span>
                    <span class="fleetSlots">
                @lang('Fleets:'): <span class="current">{{ count($fleet_events) }}</span> / <span class="all">{{ count($fleet_events) }}</span>
            </span>
                    <span class="expSlots">
                @lang('Expeditions:'): <span class="current">0</span> / <span class="all">1</span>
            </span>
                    <span class="closeAll">
                <a href="javascript:void(0);" class="all_open">
                    <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif">
                </a>
            </span>
                </div>
                @foreach($fleet_events as $fleet_event)
                <div id="fleet{{ $fleet_event->id }}" class="fleetDetails detailsOpened" data-mission-type="{{ $fleet_event->mission_type }}" data-return-flight="{{ $fleet_event->is_return_trip ? '1' : '' }}" data-arrival-time="{{ $fleet_event->mission_time_arrival }}">
                    <span class="timer tooltip tpd-hideOnClickOutside" title="{{ date('d.m.Y H:i:s', $fleet_event->mission_time_arrival) }}" id="timer_{{ $fleet_event->id }}">{{ gmdate('H\h i\m s\s', $fleet_event->mission_time_arrival - time()) }}</span>
                    <span class="absTime">{{ date('H:i:s', $fleet_event->mission_time_arrival) }}  Clock</span>
                    <span class="mission {{ $fleet_event->mission_status }} textBeefy">{{ $fleet_event->mission_label }}{{ $fleet_event->is_return_trip ? ' (R)' : '' }}</span>
                    <span class="allianceName"></span>
                    <span class="originData">
                    <span class="originCoords tooltip" title="{{ $fleet_event->origin_planet_name }}"><a href="{{ route('galaxy.index', ['galaxy' => $fleet_event->origin_planet_coords->galaxy, 'system' => $fleet_event->origin_planet_coords->system])  }}">[{{ $fleet_event->origin_planet_coords->galaxy }}:{{ $fleet_event->origin_planet_coords->system }}:{{ $fleet_event->origin_planet_coords->position }}]</a></span>
                    <span class="originPlanet">
                        @if($fleet_event->origin_planet_type && $fleet_event->origin_planet_type->value === 1)
                            <figure class="planetIcon planet"></figure>{{ $fleet_event->origin_planet_name }}
                        @elseif($fleet_event->origin_planet_type && $fleet_event->origin_planet_type->value === 2)
                            <figure class="planetIcon debris"></figure>{{ $fleet_event->origin_planet_name }}
                        @elseif($fleet_event->origin_planet_type && $fleet_event->origin_planet_type->value === 3)
                            <figure class="planetIcon moon"></figure>{{ $fleet_event->origin_planet_name }}
                        @else
                            {{ $fleet_event->origin_planet_name }}
                        @endif
                    </span>
                </span>
                    <span class="marker01"></span>
                    <span class="marker02"></span>
                    <span class="fleetDetailButton">
                    <a href="#bl{{ $fleet_event->id }}" rel="bl{{ $fleet_event->id }}" title="@lang('Fleet details')" class="tooltipRel tooltipClose fleet_icon_{{ $fleet_event->is_return_trip ? 'reverse' : 'forward' }}">
                    </a>
                </span>
                    @if($fleet_event->is_recallable && !$fleet_event->is_return_trip)
                    <span class="reversal reversal_time ipiHintable" ref="{{ $fleet_event->id }}" data-ipi-hint="ipiFleetRecall">
                        <a class="icon_link tooltipHTML" href="{{ route('fleet.movement', ['return' => $fleet_event->id]) }}&amp;token={{ csrf_token() }}" title="@lang('Recall:')| {{ date('d.m.Y', $fleet_event->recall_return_time) }}<br>{{ date('H:i:s', $fleet_event->recall_return_time) }}">
                            <img src="/img/icons/89624964d4b06356842188dba05b1b.gif" height="16" width="16">
                        </a>
                    </span>
                    @endif
                    <span class="starStreak">
                    <div style="position: relative;">
                        <div class="origin fixed">
                            <img class="tooltipHTML tpd-hideOnClickOutside" height="30" width="30" src="/img/icons/32926d2ee2884eab5015c14c73afa3.png" title="@lang('Start time:')| {{ date('d.m.Y', $fleet_event->mission_time_departure) }}<br>{{ date('H:i:s', $fleet_event->mission_time_departure) }}" alt="">
                        </div>

                        <div class="route fixed">

                            <a href="#bl{{ $fleet_event->id }}" rel="bl{{ $fleet_event->id }}" title="@lang('Fleet details')" class="tooltipRel tooltipClose basic2 fleet_icon_{{ $fleet_event->is_return_trip ? 'reverse' : 'forward' }}" id="route_{{ $fleet_event->id }}" style="margin-left: 11px;"></a>

                            <div style="display:none;" id="bl{{ $fleet_event->id }}">
                                <div class="htmlTooltip">
    <h1>@lang('Fleet details'):</h1>
    <div class="splitLine"></div>
    <table cellpadding="0" cellspacing="0" class="fleetinfo">
        <tbody><tr>
            <th colspan="2">@lang('Ships'):</th>
        </tr>
        @if($fleet_event->fleet_units && $fleet_event->fleet_units->units)
            @foreach($fleet_event->fleet_units->units as $unit)
                @if($unit && $unit->unitObject)
                    <tr>
                <td>{{ $unit->unitObject->title }}:</td>
                <td class="value">
                                {{ $unit->amount }}                        </td>
            </tr>
                @endif
            @endforeach
        @endif
                <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <th colspan="2">@lang('Shipment'):</th>
        </tr>
        <tr>
            <td>@lang('Metal'):</td>
            <td class="value">
                {{ number_format($fleet_event->resources->metal->get(), 0, '.', ',') }}            </td>
        </tr>
        <tr>
            <td>@lang('Crystal'):</td>
            <td class="value">
                {{ number_format($fleet_event->resources->crystal->get(), 0, '.', ',') }}            </td>
        </tr>
        <tr>
            <td>@lang('Deuterium'):</td>
            <td class="value">
                {{ number_format($fleet_event->resources->deuterium->get(), 0, '.', ',') }}            </td>
        </tr>
                    <tr>
                <td>@lang('Food'):</td>
                <td class="value">
                    {{ number_format($fleet_event->resources->energy->get(), 0, '.', ',') }}                </td>
            </tr>
            </tbody></table>
</div>

                            </div>

                        </div>

                        <div class="destination fixed">
                            <img class="tooltipHTML" height="30" width="30" src="/img/icons/0a0346dd4999bd04761fc6b086e7a1.png" title="@lang('Time of arrival:')| {{ date('d.m.Y', $fleet_event->mission_time_arrival) }}<br>{{ date('H:i:s', $fleet_event->mission_time_arrival) }}" alt="">
                        </div>
                    </div>
                </span><!-- Starstreak -->
                    <span class="destinationData">
                        <span class="destinationPlanet">
                            <span>
                                @if($fleet_event->destination_planet_type && $fleet_event->destination_planet_type->value === 1)
                                    <figure class="planetIcon planet"></figure>{{ $fleet_event->destination_planet_name }}
                                @elseif($fleet_event->destination_planet_type && $fleet_event->destination_planet_type->value === 2)
                                    <figure class="planetIcon debris"></figure>{{ $fleet_event->destination_planet_name }}
                                @elseif($fleet_event->destination_planet_type && $fleet_event->destination_planet_type->value === 3)
                                    <figure class="planetIcon moon"></figure>{{ $fleet_event->destination_planet_name }}
                                @elseif($fleet_event->destination_planet_type && $fleet_event->destination_planet_type->value === 4)
                                    <figure class="planetIcon planet"></figure>{{ $fleet_event->destination_planet_name }}
                                @else
                                    {{ $fleet_event->destination_planet_name }}
                                @endif
                            </span>
                        </span>

                        <span class="destinationCoords"><a href="{{ route('galaxy.index', ['galaxy' => $fleet_event->destination_planet_coords->galaxy, 'system' => $fleet_event->destination_planet_coords->system])  }}">[{{ $fleet_event->destination_planet_coords->galaxy }}:{{ $fleet_event->destination_planet_coords->system }}:{{ $fleet_event->destination_planet_coords->position }}]</a></span>
                    </span>

                    @if($fleet_event->mission_status === 'own' && !$fleet_event->is_return_trip)
                        @php
                            $fleetMissionServiceCheck = app(\OGame\Services\FleetMissionService::class);
                            $hasReturnMission = $fleetMissionServiceCheck->missionHasReturnMission($fleet_event->mission_type);
                            if ($hasReturnMission) {
                                $returnArrivalTime = $fleet_event->mission_time_arrival + ($fleet_event->mission_time_arrival - $fleet_event->mission_time_departure) + $fleet_event->mission_time_holding;
                            }
                        @endphp
                        @if($hasReturnMission)
                    <span class="nextTimer tooltip" title="{{ date('d.m.Y H:i:s', $returnArrivalTime) }}" id="timerNext_{{ $fleet_event->id }}">{{ gmdate('H\h i\m s\s', $returnArrivalTime - time()) }}</span>
                    <span class="nextabsTime">{{ date('H:i:s', $returnArrivalTime) }} Clock</span>
                    <span class="nextMission friendly textBeefy">@lang('Return')</span>
                        @endif
                    @endif

                    <span class="openDetails">
                    <a href="javascript:void(0);" class="openCloseDetails" data-mission-id="{{ $fleet_event->id }}" data-end-time="{{ $fleet_event->mission_time_arrival }}">
                        <img src="/img/icons/577565fadab7780b0997a76d0dca9b.gif" height="16" width="16">
                    </a>
                </span>
                </div>
                @endforeach
                <div class="placeholder"></div>
            </div>
        </div>
        <script type="text/javascript">
            function unionEdit(response)
            {
                var data = $.parseJSON(response);
                errorBoxAsArray(data["errorbox"]);
                token = data.token;
                $("#federation_" + data["fleetID"]).children().attr("href", "#federationlayer&ajax=1&union=" + data["unionID"] + "&fleet=" + data["fleetID"] + "&target=" + data["targetID"]);
                $("#FederationLayer").parent('.overlayDiv').dialog('close');
                $("#FederationLayer").remove();
            }

            function reloadPage()
            {
                openParentLocation("{{ route('fleet.movement') }}");
            }

            var currentMovementTabExtensionStates = JSON.parse("{}");
            var showInfos = 1;

            $(document).ready(function() {
                var movementLoca = "{\"callBack\":\"Recall\"}";

                if (showInfos == 0) {
                    showInfos = 1;
                    $(".closeAll").children().removeClass('all_open').addClass('all_closed');
                } else {
                    showInfos = 0;
                    $(".closeAll").children().removeClass('all_closed').addClass('all_open');
                }

                @foreach($fleet_events as $fleet_event)
                @php
                    $timeRemaining = $fleet_event->mission_time_arrival - time();
                    $totalDuration = $fleet_event->mission_time_arrival - $fleet_event->mission_time_departure;
                @endphp
                new reloadCountdown(
                    getElementByIdWithCache("timer_{{ $fleet_event->id }}"),
                    {{ $timeRemaining }},
                    "{{ route('fleet.movement') }}"
                );

                new movementImageCountdown(
                    getElementByIdWithCache("route_{{ $fleet_event->id }}"),
                    {{ $timeRemaining }},
                    {{ $totalDuration }},
                    {{ $fleet_event->is_return_trip ? '1' : '0' }},
                    0,
                    274
                );

                @if($fleet_event->is_recallable && !$fleet_event->is_return_trip)
                new recallShipCountdown(
                    {{ $fleet_event->id }},
                    {{ $fleet_event->recall_return_time }}
                );
                @endif

                @if($fleet_event->mission_status === 'own' && !$fleet_event->is_return_trip)
                    @php
                        $fleetMissionServiceCheck2 = app(\OGame\Services\FleetMissionService::class);
                        $hasReturnMission2 = $fleetMissionServiceCheck2->missionHasReturnMission($fleet_event->mission_type);
                        if ($hasReturnMission2) {
                            $returnArrivalTime2 = $fleet_event->mission_time_arrival + ($fleet_event->mission_time_arrival - $fleet_event->mission_time_departure) + $fleet_event->mission_time_holding;
                            $returnTimeRemaining = $returnArrivalTime2 - time();
                        }
                    @endphp
                    @if($hasReturnMission2)
                new simpleCountdown(
                    getElementByIdWithCache("timerNext_{{ $fleet_event->id }}"),
                    {{ $returnTimeRemaining }}
                );
                    @endif
                @endif

                @endforeach

                initMovement();
            });
        </script>
    </div>


@endsection
