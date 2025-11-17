@extends('ingame.layouts.main')

@section('content')

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <!-- JAVASCRIPT -->
    <script type="text/javascript">
        var textContent = [];
        textContent[0] = "@lang('Diameter'):";
        textContent[1] = "{!! $planet_diameter !!}km (<span>{{ $building_count }}<\/span>\/<span>{{ $max_building_count }}<\/span>)";
        textContent[2] = "@lang('Temperature'):";
        textContent[3] = "{!! $planet_temp_min !!}\u00b0C to {!! $planet_temp_max !!}\u00b0C";
        textContent[4] = "@lang('Position'):";
        textContent[5] = "<a  href=\"{{ route('galaxy.index', ['galaxy' => 4, 'system' => 4, 'position' => 4])  }}\" >[{!! $planet_coordinates !!}]<\/a>";
        textContent[6] = "@lang('Points'):";
        textContent[7] = "<a href='{{ route('highscore.index')  }}'>{{ $user_points }} (Place {!! $user_rank !!} of {!! $max_rank !!})<\/a>";
        textContent[8] = "@lang('Honour points'):";
        textContent[9] = "0";

        var textDestination = [];
        textDestination[0] = "diameterField";
        textDestination[1] = "diameterContentField";
        textDestination[2] = "temperatureField";
        textDestination[3] = "temperatureContentField";
        textDestination[4] = "positionField";
        textDestination[5] = "positionContentField";
        textDestination[6] = "scoreField";
        textDestination[7] = "scoreContentField";
        textDestination[8] = "honorField";
        textDestination[9] = "honorContentField";
        var currentIndex = 0;
        var currentChar = 0;
        var linetwo = 0;

        var cancelProduction_id;
        var production_listid;

        function cancelProduction(id, listid, question) {
            cancelProduction_id = id;
            production_listid = listid;
            errorBoxDecision("Caution", "" + question + "", "yes", "No", cancelProductionStart);
        }

        function cancelProductionStart() {
            $('<form id="cancelProductionStart" action="{{ route('resources.cancelbuildrequest') }}" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="building_id" value="' + cancelProduction_id + '" /> <input type="hidden" name="building_queue_id" value="' + production_listid + '" /> <input type="hidden" name="redirect" value="overview" /></form>').appendTo('body').submit();
        }

        function cancelResearch(id, listid, question) {
            cancelProduction_id = id;
            production_listid = listid;
            errorBoxDecision("Caution", "" + question + "", "yes", "No", cancelResearchStart);
        }

        function cancelResearchStart() {
            $('<form id="cancelProductionStart" action="{{ route('research.cancelbuildrequest') }}" method="POST" style="display: none;">{{ csrf_field() }}<input type="hidden" name="building_id" value="' + cancelProduction_id + '" /> <input type="hidden" name="building_queue_id" value="' + production_listid + '" /> <input type="hidden" name="redirect" value="overview" /></form>').appendTo('body').submit();
        }

        function initType() {
            type();
        }

        function openSpaceDockOverlayFromOverview() {
            // Create dialog container
            var dialogId = 'spaceDockDialog_' + Date.now();
            var $dialog = $('<div id="' + dialogId + '" class="overlayDiv"></div>');
            $dialog.html('<div style="text-align: center; padding: 20px;"><img src="/img/icons/4161a64a933a5345d00cb9fdaa25c7.gif" alt="Loading..."></div>');
            $('body').append($dialog);

            // Load content via AJAX first
            $.get("{{ route('spacedock.overlay') }}", function(data) {
                $dialog.html(data);

                // Initialize dialog AFTER content is loaded
                $dialog.dialog({
                    title: 'Space Dock',
                    modal: true,
                    width: 700,
                    height: 'auto',
                    maxHeight: 650,
                    closeText: '',
                    position: { my: "center", at: "center" },
                    close: function() {
                        $(this).dialog('destroy');
                        $(this).remove();
                    }
                });
            }).fail(function() {
                $dialog.html('<div style="padding: 20px; text-align: center; color: #ff6666;">Failed to load Space Dock.</div>');

                // Initialize dialog even on failure
                $dialog.dialog({
                    title: 'Space Dock - Error',
                    modal: true,
                    width: 400,
                    height: 'auto',
                    closeText: '',
                    position: { my: "center", at: "center" },
                    close: function() {
                        $(this).dialog('destroy');
                        $(this).remove();
                    }
                });
            });
        }

        $(document).ready(function () {
            gfSlider = new GFSlider(getElementByIdWithCache('detailWrapper'));
            initType();
            @if (!empty($ship_active))
            // Countdown for inline ship element (pusher)
            // TODO: shipCountdown is not defined - temporarily disabled
            // new shipCountdown(getElementByIdWithCache('shipAllCountdown'), getElementByIdWithCache('shipCountdown'), getElementByIdWithCache('shipSumCount'), {{ $ship_active->time_countdown }}, {{ $ship_active->time_countdown_object_next }}, {{ $ship_queue_time_countdown }}, {{ $ship_active->object_amount_remaining }}, "{{ route('shipyard.index') }}");
            @endif
        });
    </script>

    <div id="eventboxContent" style="display: none">
        <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif"/>
    </div>

    <div id="inhalt">
        <div id="planet" style="background-image:url({{ asset('img/headers/overview/' . $header_filename) }}.jpg);">

            <div id="detailWrapper">
                @if ($has_moon)
                    <div id="moon">
                        <a href="{{ request()->url() }}?{{ http_build_query([...request()->query(), 'cp' => $other_planet->getPlanetId()]) }}"
                           class="tooltipBottom js_hideTipOnMobile"
                           title="@lang('Switch to moon') {{ $other_planet->getPlanetName() }}">
                            <img alt="{{ $other_planet->getPlanetName() }}" src="{!! asset('img/moons/big/' . $other_planet->getPlanetImageType() . '.gif') !!}">
                        </a>
                    </div>
                @elseif ($has_planet)
                    <div id="planet_as_moon">
                        <a href="{{ request()->url() }}?{{ http_build_query([...request()->query(), 'cp' => $other_planet->getPlanetId()]) }}"
                           class="tooltipBottom js_hideTipOnMobile"
                           title="@lang('Switch to planet') {{ $other_planet->getPlanetName() }}">
                            <img alt="{{ $other_planet->getPlanetName() }}" src="{!! asset('img/planets/' . $other_planet->getPlanetBiomeType() . '_moon_view.jpg') !!}">
                        </a>
                    </div>
                @endif

                @if ($has_wreckage || $has_ready_repairs || $has_repairs_in_progress)
                    <div id="space_dock_alert" class="space-dock-notification" style="position: absolute; top: 10px; right: 10px; cursor: pointer; z-index: 100;" onclick="openSpaceDockOverlayFromOverview();">
                        <div class="space-dock-icon" style="position: relative; width: 48px; height: 48px; background: radial-gradient(circle, rgba(255,100,0,0.3) 0%, rgba(255,100,0,0.7) 100%); border-radius: 50%; border: 2px solid #ff6600; display: flex; align-items: center; justify-content: center; animation: pulse-orange 2s infinite; box-shadow: 0 2px 8px rgba(0,0,0,0.5);">
                            <img src="/img/objects/buildings/space_dock_micro.jpg" style="width: 32px; height: 32px; border-radius: 50%;" title="Space Dock" alt="Space Dock">
                            @if ($has_wreckage)
                                <div class="wreckage-badge" style="position: absolute; top: -5px; right: -5px; background: #ff3333; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; border: 2px solid #fff; box-shadow: 0 0 8px rgba(255,51,51,0.8);">
                                    {{ $wreckage_count }}
                                </div>
                            @endif
                            @if ($has_ready_repairs)
                                <div class="ready-badge" style="position: absolute; bottom: -5px; right: -5px; background: #4CAF50; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; border: 2px solid #fff; box-shadow: 0 0 8px rgba(76,175,80,0.8);">
                                    ✓
                                </div>
                            @elseif ($has_repairs_in_progress)
                                <div class="progress-badge" style="position: absolute; bottom: -5px; right: -5px; background: #2196F3; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold; border: 2px solid #fff; box-shadow: 0 0 8px rgba(33,150,243,0.8);">
                                    {{ $repairs_in_progress_count }}
                                </div>
                            @endif
                        </div>
                        <div class="tooltip-content" style="position: absolute; right: 55px; top: 0; background: rgba(0,0,0,0.95); padding: 8px 12px; border-radius: 4px; border: 1px solid #6f9fc8; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s; min-width: 200px;">
                            @if ($has_wreckage)
                                <div style="color: #ff9800; margin-bottom: 5px; font-weight: bold; font-size: 12px;">
                                    ⚠ {{ $wreckage_count }} @lang('Wreckage field(s) available')
                                </div>
                            @endif
                            @if ($has_ready_repairs)
                                <div style="color: #4CAF50; font-weight: bold; font-size: 12px;">
                                    ✓ {{ $ready_repairs_count }} @lang('Repair(s) ready for pickup')
                                </div>
                            @endif
                            @if ($has_repairs_in_progress)
                                <div style="color: #2196F3; font-weight: bold; font-size: 12px;">
                                    ⏳ {{ $repairs_in_progress_count }} @lang('Repair(s) in progress')
                                </div>
                            @endif
                        </div>
                    </div>
                    <style>
                        @keyframes pulse-orange {
                            0%, 100% {
                                box-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 0 0 0 rgba(255, 102, 0, 0.7);
                            }
                            50% {
                                box-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 0 15px 5px rgba(255, 102, 0, 0);
                            }
                        }
                        #space_dock_alert:hover .tooltip-content {
                            opacity: 1;
                        }
                    </style>
                @endif

                <div id="header_text">
                    <h2>
                        <a href="javascript:void(0);" class="openPlanetRenameGiveupBox">

                            <p class="planetNameOverview">@lang('Overview') -</p>
                            <span id="planetNameHeader">
                            {{ $planet_name }}
                        </span>
                            <img class="hinted tooltip" title="@lang('Abandon/Rename Planet')"
                                 src="/img/icons/1f57d944fff38ee51d49c027f574ef.gif" width="16" height="16"/>
                        </a>
                    </h2>
                </div>
                <div id="detail" class="detail_screen">
                    <div id="techDetailLoading"></div>
                </div>
                <div id="planetdata">
                    <div class="overlay"></div>
                    <div id="planetDetails">
                        <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td class="desc">
                                    <span id="diameterField"></span>
                                </td>
                                <td class="data">
                                    <span id="diameterContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="temperatureField"></span>
                                </td>
                                <td class="data">
                                    <span id="temperatureContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="positionField"></span>
                                </td>
                                <td class="data">
                                    <span id="positionContentField"></span>
                                </td>
                            </tr>
                            <tr>
                                <td class="desc">
                                    <span id="scoreField"></span></td>
                                <td class="data">
                                    <span id="scoreContentField"></span>
                                </td>
                            </tr>

                            <tr>
                                <td class="desc">
                                    <span id="honorField"></span></td>
                                <td class="data ">
                                    <span id="honorContentField"></span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div id="planetOptions">

                        <div class="planetMoveStart fleft" style="display: inline;">
                            <a class="tooltipLeft dark_highlight_tablet fleft"
                               href='{{ route('galaxy.index') }}'
                               title="@lang('The relocation allows you to move your planets to another position in a distant system of your choosing.<br /><br />
The actual relocation first takes place 24 hours after activation. In this time, you can use your planets as normal. A countdown shows you how much time remains prior to the relocation.<br /><br />
Once the countdown has run down and the planet is to be moved, none of your fleets that are stationed there can be active. At this time, there should also be nothing in construction, nothing being repaired and nothing researched. If there is a construction task, a repair task or a fleet still active upon the countdown`s expiry, the relocation will be cancelled.<br /><br />
If the relocation is successful, you will be charged 240.000 Dark Matter. The planets, the buildings and the stored resources including moon will be moved immediately. Your fleets travel to the new coordinates automatically with the speed of the slowest ship. The jump gate to a relocated moon is deactivated for 24 hours.')"
                               data-tooltip-button="To galaxy">
                                <span class="planetMoveIcons settings planetMoveDefault icon fleft"></span>
                                <span class="planetMoveOverviewMoveLink">@lang('Relocate')</span>
                            </a>

                        </div>

                        <a class="dark_highlight_tablet float_right openPlanetRenameGiveupBox"
                           href="javascript:void(0);">
                            <span class="planetMoveOverviewGivUpLink">@lang('Abandon/Rename')</span>
                            <span class="planetMoveIcons settings planetMoveGiveUp icon"></span>
                        </a>
                    </div>
                </div>
            </div>

            <div id="buffBar" class="sliderWrapper">
                <div data-uuid="" data-id="" class="add_item">
                    <a class="activate_item border3px" href="javascript:void(0);" ref="1"></a>
                </div>

                <ul class="active_items hidden">
                    <li>
                    </li>
                </ul>
            </div>


        </div>
        <div class="c-left"></div>
        <div class="c-right"></div>

        <div id="productionboxBottom">
            <div class="productionBoxBuildings boxColumn building">
                <div id="productionboxbuildingcomponent" class="productionboxbuilding injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header">
                            <h3>@lang('Buildings')</h3>
                        </div>
                        <div class="content">
                            <table cellpadding="0" cellspacing="0" class="construction active">
                                <tbody>
                                {{-- Building is actively being built. --}}
                                @include ('ingame.shared.buildqueue.building-active', ['build_active' => $build_active])
                                {{-- Building queue has items. --}}
                                @include ('ingame.shared.buildqueue.building-queue', ['build_queue' => $build_queue])
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
                <!--<div id="productionboxlfbuildingcomponent" class="productionboxlfbuilding injectedComponent parent overview"><div class="content-box-s">
                        <div class="header">
                            <h3>Lifeform Buildings
                            </h3>
                        </div>
                        <div class="content">
                            <table cellspacing="0" cellpadding="0" class="construction active">
                                <tbody>
                                <tr>
                                    <td colspan="2" class="idle">
                                        <a class="tooltip js_hideTipOnMobile " title="The lifeforms are not currently constructing any buildings. Click here to view buildings.
" href="#TODO_=ingame&amp;component=lfbuildings">
                                            No buildings in construction.
                                            <br>
                                            (View Buildings
                                            )
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>
                    <script type="text/javascript">
                        var scheduleBuildListEntryUrl = '#TODOpage=componentOnly&component=buildlistactions&action=scheduleEntry&asJson=1';
                        var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                        redirectPremiumLink = '#TODOpage=premium&showDarkMatter=1'
                    </script>
                </div>
            </div>-->
            </div>
            <div class="productionBoxResearch boxColumn research">
                <div id="productionboxresearchcomponent" class="productionboxresearch injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header"><h3>@lang('Research')</h3></div>
                        <div class="content">
                            {{-- Building is actively being built. --}}
                            @include ('ingame.shared.buildqueue.research-active', ['build_active' => $research_active])
                            {{-- Building queue has items. --}}
                            @include ('ingame.shared.buildqueue.research-queue', ['build_queue' => $research_queue])
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
                <!--<div id="productionboxlfresearchcomponent" class="productionboxlfresearch injectedComponent parent overview"><div class="content-box-s">
                        <div class="header">
                            <h3>Lifeform Research
                            </h3>
                        </div>
                        <div class="content">
                            <table cellspacing="0" cellpadding="0" class="construction active">
                                <tbody>
                                <tr>
                                    <td colspan="2" class="idle">
                                        <a class="tooltip js_hideTipOnMobile " title="There is currently no research in progress. Click here to view lifeform techs.
" href="#TODOpage=ingame&amp;component=lfresearch">
                                            There is no research in progress at the moment.
                                            <br>
                                            (View Lifeform Development
                                            )
                                        </a>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="footer"></div>
                    </div>-->
                <script type="text/javascript">
                    var scheduleBuildListEntryUrl = '#TODOpage=componentOnly&component=buildlistactions&action=scheduleEntry&asJson=1';
                    var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                    redirectPremiumLink = '#TODOpage=premium&showDarkMatter=1'
                </script>
            </div>

            <div class="productionBoxShips boxColumn ship">

                <div id="productionboxshipyardcomponent" class="productionboxshipyard injectedComponent parent overview">
                    <div class="content-box-s">
                        <div class="header"><h3>@lang('Shipyard')</h3></div>
                        <div class="content">
                            {{-- Building is actively being built. --}}
                            @include ('ingame.shared.buildqueue.unit-active', ['build_active' => $ship_active, 'build_queue_countdown' => $ship_queue_time_countdown])
                            {{-- Building queue has items. --}}
                            @include ('ingame.shared.buildqueue.unit-queue', ['build_queue' => $ship_queue])
                        </div>
                        <div class="footer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
