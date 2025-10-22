<?php /** @var OGame\Services\PlayerService $currentPlayer */ ?>
<?php /** @var OGame\Services\SettingsService $settings */ ?>
        <!DOCTYPE html>
<html lang="<?php echo e(app()->getLocale()); ?>">
<head>
    <!--
     ===========================================
       ____   _____                     __   __
      / __ \ / ____|                    \ \ / /
     | |  | | |  __  __ _ _ __ ___   ___ \ V /
     | |  | | | |_ |/ _` | '_ ` _ \ / _ \ > <
     | |__| | |__| | (_| | | | | | |  __// . \
      \____/ \_____|\__,_|_| |_| |_|\___/_/ \_\
     ===========================================

     Powered by OGameX - Explore the universe! Conquer your enemies!
     GitHub: https://github.com/lanedirt/OGameX
     Version: <?php echo e(\OGame\Facades\GitInfoUtil::getAppVersionBranchCommit()); ?>


    This application is released under the MIT License. For more details, visit the GitHub repository.
    -->
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="apple-touch-icon" href="/img/icons/20da7e6c416e6cd5f8544a73f588e5.png"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Language" content="en"/>
    <meta name="ogame-session" content="3c442273a6de4c8f79549e78f4c3ca50e7ea7580"/>
    <meta name="ogame-version" content="<?php echo e(\OGame\Facades\GitInfoUtil::getAppVersion()); ?>"/>
    <meta name="ogame-timestamp" content="1513426692"/>
    <meta name="ogame-universe" content="s1"/>
    <meta name="ogame-universe-name" content="Home"/>
    <meta name="ogame-universe-speed" content="<?php echo e($settings->economySpeed()); ?>"/>
    <meta name="ogame-universe-speed-fleet" content="<?php echo e($settings->fleetSpeed()); ?>"/>
    <meta name="ogame-language" content="en"/>
    <meta name="ogame-donut-galaxy" content="1"/>
    <meta name="ogame-donut-system" content="1"/>
    <meta name="ogame-player-id" content="<?php echo e($currentPlayer->getId()); ?>"/>
    <meta name="ogame-player-name" content="<?php echo e($currentPlayer->getUsername()); ?>"/>
    <meta name="ogame-alliance-id" content=""/>
    <meta name="ogame-alliance-name" content=""/>
    <meta name="ogame-alliance-tag" content=""/>
    <!-- TODO: update with current planet details -->
    <meta name="ogame-planet-id" content="<?php echo e($currentPlanet->getPlanetId()); ?>"/>
    <meta name="ogame-planet-name" content="<?php echo e($currentPlanet->getPlanetName()); ?>"/>
    <meta name="ogame-planet-coordinates" content="<?php echo e($currentPlanet->getPlanetCoordinates()->asString()); ?>"/>
    <meta name="ogame-planet-type" content="planet"/>

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

    <link rel="stylesheet" href="<?php echo e(mix('css/ingame.css')); ?>">
    <script src="<?php echo e(mix('js/ingame.min.js')); ?>"></script>

    <script type="text/javascript">
        window.token = "<?php echo e(csrf_token()); ?>";
        var inventoryObj;
        $.holdReady(true);

        var s = setInterval(function () {
            if (typeof initEmpireEquipment === "function") {
                $.holdReady(false);
                clearInterval(s);
            }
        }, 1);
    </script>
<script defer src="https://umami.mierau-kiss.de/script.js" data-website-id="78f0acb4-da82-4de8-b6bd-bcad6a566d17"></script>
</head>
<body id="<?php echo e(!empty($body_id) ? $body_id : 'ingamepage'); ?>" class="ogame lang-en default no-touch">
<div id="initial_welcome_dialog" title="Welcome to OGame!" style="display: none;">
    To help your game start get moving quickly, we've assigned you the name Commodore Nebula. You can change this at any
    time by clicking on the username.<br/>
    Fleet Command has left you information on your first steps in your inbox, to help you be well-equipped for your
    start.<br/>
    <br/>
    Have fun playing!
</div>
<?php if($currentPlayer->isAdmin()): ?>
    <?php echo $__env->make('ingame.layouts.admin-menu', ['currentPlayer' => $currentPlayer], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>
<div id="siteHeader"></div>
<div id="pageContent">
    <div id="top">
        <div id="pageReloader" onclick="javascript: redirectOverview();"></div>
        <div id="headerbarcomponent" class="">
            <div id="bar">
                <ul>
                    <li id="playerName">
                        <?php echo app('translator')->get('Player'); ?>:
                        <selected-language-icon
                                style="background-image: url('/img/flags/a176fcd6f3e3de2bed6a73a8b1d5e7.png');"></selected-language-icon>

                        <span class="textBeefy">
                                <a href="<?php echo e(route('changenick.overlay')); ?>"
                                   class="overlay textBeefy"
                                   data-overlay-title="Change player name"
                                   data-overlay-popup-width="400"
                                   data-overlay-popup-height="200"
                                >
                                    <?php echo $currentPlayer->getUsername(); ?>

                                </a>
                            </span>
                    </li>
                    <li>
                        <a href="<?php echo e(route('highscore.index')); ?>" accesskey=""><?php echo app('translator')->get('Highscore'); ?></a>
                        (<?php echo e($highscoreRank); ?>)
                    </li>
                    <li>
                        <a href="<?php echo e(route('notes.overlay')); ?>"
                           class="overlay" data-overlay-title="My notes"
                           data-overlay-class="notices"
                           data-overlay-popup-width="750"
                           data-overlay-popup-height="480"
                           accesskey="">
                            <?php echo app('translator')->get('Notes'); ?></a>
                    </li>
                    <li>
                        <a class=""
                           accesskey=""
                           href="<?php echo e(route('buddies.index')); ?>"
                        >
                            <?php echo app('translator')->get('Buddies'); ?></a>
                    </li>
                    <li><a class="overlay"
                           href="<?php echo e(route('search.overlay')); ?>"
                           data-overlay-title="Search Universe"
                           data-overlay-close="__default closeSearch"
                           data-overlay-class="search"
                           accesskey=""><?php echo app('translator')->get('Search'); ?></a>
                    </li>
                    <li><a href="<?php echo e(route('options.index')); ?>" accesskey=""><?php echo app('translator')->get('Options'); ?></a></li>
                    <li><a href="#"><?php echo app('translator')->get('Support'); ?></a></li>
                    <li>
                        <a href="<?php echo e(route('logout')); ?>" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();"><?php echo app('translator')->get('Log out'); ?></a>

                        <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST"
                              style="display: none;">
                            <?php echo e(csrf_field()); ?>

                        </form>
                    </li>
                    <li class="OGameClock"><?php echo e(\Carbon\Carbon::now()->format('d.m.Y H:i:s')); ?></li>
                </ul>
            </div>
        </div>
        <div id="resourcesbarcomponent" class="">
            <div id="resources">

                <div class="resource_tile metal">
                    <div id="metal_box" class="metal tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="Metal|<table class=&quot;resourceTooltip&quot;><tr><th><?php echo app('translator')->get('Available'); ?>:</th><td><span class=&quot;&quot;><?php echo $resources['metal']['amount_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?></th><td><span class=&quot;&quot;><?php echo $resources['metal']['storage_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Current production'); ?>:</th><td><span class=&quot;undermark&quot;>+<?php echo $resources['metal']['production_hour']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:</th><td><span class=&quot;middlemark&quot;>0</span></td></tr></table>"
                         data-shop-url="#TODO_shop#category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&amp;item=859d82d316b83848f7365d21949b3e1e63c7841f&amp;page=shop&amp;panel1-1="
                         data-ipi-hint="ipiResourcemetal">
                        <div class="resourceIcon metal"></div>
                        <span class="value">
                        <span id="resources_metal"
                              class="<?php echo e($resources['metal']['storage_almost_full'] ? 'middlemark' : ''); ?><?php echo e($resources['metal']['amount'] >= $resources['metal']['storage'] ? 'overmark' : ''); ?>"
                              data-raw="<?php echo $resources['metal']['amount']; ?>"><?php echo $resources['metal']['amount_formatted']; ?></span>
                    </span>
                    </div>
                </div>
                <div class="resource_tile crystal">
                    <div id="crystal_box" class="crystal tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="<?php echo app('translator')->get('Crystal'); ?>|<table class=&quot;resourceTooltip&quot;><tr><th><?php echo app('translator')->get('Available'); ?>:</th><td><span class=&quot;&quot;><?php echo $resources['crystal']['amount_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?></th><td><span class=&quot;&quot;><?php echo $resources['crystal']['storage_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Current production'); ?>:</th><td><span class=&quot;undermark&quot;>+<?php echo $resources['crystal']['production_hour']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:</th><td><span class=&quot;middlemark&quot;>0</span></td></tr></table>"
                         data-shop-url="#TODO_shop#category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&amp;item=859d82d316b83848f7365d21949b3e1e63c7841f&amp;page=shop&amp;panel1-1="
                         data-ipi-hint="ipiResourcecrystal">
                        <div class="resourceIcon crystal"></div>
                        <span class="value">
                        <span id="resources_crystal"
                              class="<?php echo e($resources['crystal']['storage_almost_full'] ? 'middlemark' : ''); ?><?php echo e($resources['crystal']['amount'] >= $resources['crystal']['storage'] ? 'overmark' : ''); ?>"
                              data-raw="<?php echo $resources['crystal']['amount']; ?>"><?php echo $resources['crystal']['amount_formatted']; ?></span>
                    </span>
                    </div>
                </div>
                <div class="resource_tile deuterium">
                    <div id="deuterium_box" class="deuterium tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="<?php echo app('translator')->get('Deuterium'); ?>|<table class=&quot;resourceTooltip&quot;><tr><th><?php echo app('translator')->get('Available'); ?>:</th><td><span class=&quot;&quot;><?php echo $resources['deuterium']['amount_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?></th><td><span class=&quot;&quot;><?php echo $resources['deuterium']['storage_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Current production'); ?>:</th><td><span class=&quot;undermark&quot;>+<?php echo $resources['deuterium']['production_hour']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:</th><td><span class=&quot;middlemark&quot;>0</span></td></tr></table>"
                         data-shop-url="#TODO_shop#category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&amp;item=859d82d316b83848f7365d21949b3e1e63c7841f&amp;page=shop&amp;panel1-1="
                         data-ipi-hint="ipiResourcedeuterium">
                        <div class="resourceIcon deuterium"></div>
                        <span class="value">
                        <span id="resources_deuterium"
                              class="<?php echo e($resources['deuterium']['storage_almost_full'] ? 'middlemark' : ''); ?><?php echo e($resources['deuterium']['amount'] >= $resources['deuterium']['storage'] ? 'overmark' : ''); ?>"
                              data-raw="<?php echo $resources['deuterium']['amount']; ?>"><?php echo $resources['deuterium']['amount_formatted']; ?></span>
                    </span>
                    </div>
                </div>
                <div class="resource_tile energy">
                    <div id="energy_box" class="energy tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="<?php echo app('translator')->get('Energy'); ?>|<table class=&quot;resourceTooltip&quot;><tr><th><?php echo app('translator')->get('Available'); ?>:</th><td><span class=&quot;&quot;><?php echo $resources['energy']['amount_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Current production:'); ?></th><td><span class=&quot;undermark&quot;>+<?php echo $resources['energy']['production_formatted']; ?></span></td></tr><tr><th><?php echo app('translator')->get('Consumption'); ?></th><td><span class=&quot;overmark&quot;>-<?php echo $resources['energy']['consumption_formatted']; ?></span></td></tr></table>"
                         data-ipi-hint="ipiResourceenergy">
                        <div class="resourceIcon energy"></div>
                        <span class="value">
                        <span id="resources_energy" class="<?php echo e($resources['energy']['amount'] < 0 ? 'overmark' : ''); ?>"
                              data-raw="<?php echo $resources['energy']['amount']; ?>"><?php echo $resources['energy']['amount_formatted']; ?></span>
                    </span>
                    </div>
                </div>
                <!--<div class="resource_tile population">
                    <div id="population_box" class="population tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="Population|<table class=&quot;resourceTooltip&quot;><tr><th>Available:</th><td><span class=&quot;overmark&quot;>100</span></td></tr><tr><th>Living Space
</th><td><span class=&quot;overmark&quot;>0</span></td></tr><tr><th>Satisfied</th><td><span class=&quot;undermark&quot;>0</span></td></tr><tr><th>Hungry</th><td><span class=&quot;overmark&quot;>0</span></td></tr><tr><th>Growth rate</th><td><span class=&quot;&quot;>±0</span></td></tr><tr><th>Bunker Space
</th><td><span class=&quot;middlemark&quot;>100</span></td></tr></table>" data-ipi-hint="ipiResourcepopulation">
                        <div class="resourceIcon population"></div>
                        <span class="value">
                        <span id="resources_population" data-raw="100" class="overmark">100</span>
                    </span>
                    </div>
                </div>
                <div class="resource_tile food">
                    <div id="food_box" class="food tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="Food|<table class=&quot;resourceTooltip&quot;><tr><th>Available:</th><td><span class=&quot;overmark&quot;>0</span></td></tr><tr><th>Storage capacity</th><td><span class=&quot;overmark&quot;>0</span></td></tr><tr><th>Overproduction</th><td><span class=&quot;undermark&quot;>0</span></td></tr><tr><th>Consumption</th><td><span class=&quot;overmark&quot;>0</span></td></tr><tr><th>Consumed in</th><td><span class=&quot;overmark timeTillFoodRunsOut&quot;>~</span></td></tr></table>"
                         data-ipi-hint="ipiResourcefood">
                        <div class="resourceIcon food"></div>
                        <span class="value">
                        <span id="resources_food" data-raw="0" class="overmark">0</span>
                    </span>
                    </div>
                </div>
                <div class="resource_tile darkmatter">
                    <div id="darkmatter_box" class="darkmatter tooltipHTML resource ipiHintable tpd-hideOnClickOutside"
                         title="<?php echo app('translator')->get('Dark Matter'); ?>|<table class=&quot;resourceTooltip&quot;><tr><th>Available:</th><td><span class=&quot;&quot;>19,890</span></td></tr><tr><th>Purchased</th><td><span class=&quot;&quot;>225</span></td></tr><tr><th>Found</th><td><span class=&quot;&quot;>19,665</span></td></tr></table>"
                         data-tooltip-button="Purchase Dark Matter" data-ipi-hint="ipiResourcedarkmatter">
                        <a href="#TODO_page=payment" class="overlay">
                            <img src="/img/icons/401d1a91ff40dc7c8acfa4377d3d65.gif">
                            <div class="resourceIcon darkmatter"></div>
                        </a>
                        <span class="value">
                        <span id="resources_darkmatter" data-raw="19890" class="overlay">19,890</span>
                    </span>
                    </div>
                </div>-->
            </div>
        </div>
        <div id="commandercomponent" class="">
            <div id="lifeform" class="fleft">
                <a href="#TODO_page=ingame&amp;component=lfsettings" class="tooltipHTML js_hideTipOnMobile ipiHintable"
                   title="Lifeform|No lifeforms
" data-ipi-hint="ipiLifeformSettings">
                    <div class="resourceIcon population"></div>
                </a>
            </div>
            <div id="characterclass" class="fleft">
                <a href="#TODO_=ingame&amp;component=characterclassselection"
                   class="tooltipHTML js_hideTipOnMobile ipiHintable"
                   title="Your class: Collector|+25% mine production<br>+10% energy production<br>+100% speed for Transporters<br>+25% cargo bay for Transporters<br>+50% Crawler bonus<br>+10% more usable Crawlers with Geologist<br>Overload the Crawlers up to 150%<br>+10% discount on acceleration (building)"
                   data-ipi-hint="ipiCharacterclassSettings">
                    <div class="sprite characterclass medium miner"></div>
                </a>
            </div>
            <div id="officers" class="  fright">
                <a href="#TODO_=premium&amp;openDetail=2" class="tooltipHTML   commander js_hideTipOnMobile "
                   title="Hire commander|&amp;#43;40 favorites, building queue, shortcuts, transport scanner, advertisement-free* <span style=&quot;font-size: 10px; line-height: 10px&quot;>(*excludes: game related references)</span>">
                    <img src="/img/layout/pixel.gif" width="30" height="30">
                </a>
                <a href="#TODO_page=premium&amp;openDetail=3" class="tooltipHTML    admiral js_hideTipOnMobile " title="Hire admiral|Max. fleet slots +2,
Max. expeditions +1,
Improved fleet escape rate,
Combat simulation save slots +20">
                    <img src="/img/layout/pixel.gif" width="30" height="30">
                </a>
                <a href="#TODO_page=premium&amp;openDetail=4" class="tooltipHTML    engineer js_hideTipOnMobile "
                   title="Hire engineer|Halves losses to defenses, +10% energy production">
                    <img src="/img/layout/pixel.gif" width="30" height="30">
                </a>
                <a href="#TODO_page=premium&amp;openDetail=5" class="tooltipHTML    geologist js_hideTipOnMobile "
                   title="Hire geologist|+10% mine production">
                    <img src="/img/layout/pixel.gif" width="30" height="30">
                </a>
                <a href="#TODO_page=premium&amp;openDetail=6" class="tooltipHTML    technocrat js_hideTipOnMobile "
                   title="Hire technocrat|+2 espionage levels, 25% less research time">
                    <img src="/img/layout/pixel.gif" width="30" height="30">
                </a>
            </div>
        </div>
        <div id="notificationbarcomponent" class="">
            <div id="message-wrapper">
                <a class=" comm_menu messages tooltip js_hideTipOnMobile"
                   href="<?php echo e(route('messages.index')); ?>"
                   title="<?php echo e($unreadMessagesCount); ?> <?php echo app('translator')->get('unread message(s)'); ?>">
                    <?php if($unreadMessagesCount > 0): ?>
                        <span class="new_msg_count totalMessages  news"
                              data-new-messages="<?php echo e($unreadMessagesCount); ?>">
                                <?php echo e($unreadMessagesCount); ?>

                            </span>
                    <?php endif; ?>
                </a>
                <!-- Neue Chatnachrichten-Zähler -->
                <a class=" comm_menu chat tooltip js_hideTipOnMobile tpd-hideOnClickOutside"
                   href="#"
                   title="0 unread conversation(s)">
                    <!-- js modification !-->
                    <span class="new_msg_count totalChatMessages noMessage" data-new-messages="0">
                    0                </span>
                </a>
                <div id="messages_collapsed">
                    <div id="eventboxFilled" class="eventToggle" style="display: none;">
                        <a id="js_eventDetailsClosed" class="tooltipRight js_hideTipOnMobile"
                           href="javascript:void(0);"
                           title="More details"></a>
                        <a id="js_eventDetailsOpen" class="tooltipRight open js_hideTipOnMobile"
                           href="javascript:void(0);"
                           title="Less detail"></a>


                    </div>
                    <div id="eventboxLoading" class="textCenter textBeefy" style="display: block;">
                        <img height="16" width="16"
                             src="/img/icons/3f9884806436537bdec305aa26fc60.gif"/><?php echo app('translator')->get('load...'); ?>
                    </div>
                    <div id="eventboxBlank" class="textCenter" style="display: none;">
                        <?php echo app('translator')->get('No fleet movement'); ?>
                    </div>
                </div>
                <div id="attack_alert" class="tooltip <?php if($underAttack): ?> soon <?php else: ?> noAttack <?php endif; ?>"
                     title="<?php if($underAttack): ?> <?php echo app('translator')->get('You are under attack!'); ?> <?php endif; ?>">
                    <a href="#TODO_componentOnly&amp;component=eventList" class=" tooltipHTML js_hideTipOnMobile"></a>
                </div>
            </div>
        </div>

    </div>
    <div id="left">
        <div id="<div id="toolbarcomponent" class="">
<div id="ipimenucomponent" class="">
    <div id="ipiMenuWrapper" class="ipiMenuTrackedAction ipiHintable" title="" data-ipi-hint="ipiMenu">
        <div id="ipimenucontent">
            <a href="https://discord.gg/n9uCcjQ7Xj"
               class="textBeefy"
               id="ipiInnerMenuContentHolder"
               target="_blank"
               rel="noopener"
               title="Galaktischer Senat (Discord)"
style="display: block; text-align: center; font-size: 12px; line-height: 1.1; padding: 4px 0;">
                <div class="ipiMenuHead" style="line-height: 1.1; margin: 0; padding: 0;" >
                    Galaktischer Senat (Discord)
                </div>

                <!-- keep placeholders so styling remains intact -->
                <div class="ipiMenuBody hidden"></div>
                <div class="ipiMenuFooter hidden"></div>
            </a>
        </div>
    </div>
</div>
            <div id="links">
                <ul id="menuTable" class="leftmenu">

                    <li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('rewards.index')); ?>"
                               class="tooltipRight js_hideTipOnMobile "
                               target="_self"
                               title="Rewards">
                                <div class="menuImage overview <?php echo e((Request::is('rewards') || Request::is('overview') ? 'highlighted' : '')); ?>"></div>
                            </a>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('overview') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('overview.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Overview'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('resources.settings')); ?>"
                               class="tooltipRight js_hideTipOnMobile "
                               target="_self"
                               title="Resource settings">
                                <div class="menuImage resources <?php echo e((Request::is('resources*') ? 'highlighted' : '')); ?>"></div>
                            </a>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('resources*') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('resources.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Resources'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <div class="menuImage station <?php echo e((Request::is('facilities') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('facilities') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('facilities.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Facilities'); ?></span>
                        </a>
                    </li>

                    <!--<li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('merchant.index')); ?>#page=traderResources&amp;animation=false"
                               class="trader tooltipRight js_hideTipOnMobile "
                               target="_self"
                               title="Resource Market">
                                <div class="menuImage traderOverview <?php echo e((Request::is('merchant') ? 'highlighted' : '')); ?>">
                                </div>
                            </a>
                        </span>
                        <a class="menubutton premiumHighligt <?php echo e((Request::is('merchant') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('merchant.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Merchant'); ?></span>
                        </a>
                    </li>-->
                    <li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('techtree.ajax', ['tab' => 3, 'object_id' => 1, 'open' => 'all'])); ?>"
                               class="overlay tooltipRight js_hideTipOnMobile "
                               target="_blank"
                               title="Technology">
                                <div class="menuImage research <?php echo e((Request::is('research') ? 'highlighted' : '')); ?>">
                                </div>
                            </a>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('research') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('research.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Research'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <div class="menuImage shipyard <?php echo e((Request::is('shipyard') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('shipyard') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('shipyard.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Shipyard'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <div class="menuImage defense <?php echo e((Request::is('defense') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('defense') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('defense.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Defense'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('fleet.movement')); ?>"
                               class="tooltipRight js_hideTipOnMobile "
                               target="_self"
                               title="Fleet movement">
                                <div class="menuImage fleet1 <?php echo e((Request::is('fleet*') ? 'highlighted' : '')); ?>">
                                </div>
                            </a>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('fleet*') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('fleet.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Fleet'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <div class="menuImage galaxy <?php echo e((Request::is('galaxy') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('galaxy') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('galaxy.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Galaxy'); ?></span>
                        </a>
                    </li>
                    <!--
                    <li>
                        <span class="menu_icon">
                            <div class="menuImage alliance <?php echo e((Request::is('alliance') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton <?php echo e((Request::is('alliance') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('alliance.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Alliance'); ?></span>
                        </a>
                    </li>

                    <li>
                        <span class="menu_icon">
                            <div class="menuImage premium <?php echo e((Request::is('premium') ? 'highlighted' : '')); ?>"></div>
                        </span>
                        <a class="menubutton premiumHighligt officers <?php echo e((Request::is('premium') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('premium.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Recruit Officers'); ?></span>
                        </a>
                    </li>
                    <li>
                        <span class="menu_icon">
                            <a href="<?php echo e(route('shop.index')); ?>#page=inventory"
                               class="tooltipRight js_hideTipOnMobile "
                               target="_self"
                               title="Inventory">
                                <div class="menuImage shop <?php echo e((Request::is('shop') ? 'highlighted' : '')); ?>">
                                </div>
                            </a>
                        </span>
                        <a class="menubutton premiumHighligt <?php echo e((Request::is('shop') ? 'selected' : '')); ?>"
                           href="<?php echo e(route('shop.index')); ?>"
                           accesskey=""
                           target="_self"
                        >
                            <span class="textlabel"><?php echo app('translator')->get('Shop'); ?></span>
                        </a>
                    </li>-->
                </ul>

                <div id="toolLinksWrapper">
                    <ul id="menuTableTools" class="leftmenu"></ul>
                </div>
                <br class="clearfloat">
            </div>
        </div>
        <div id="advicebarcomponent" class="">
            <div class="adviceWrapper">

                <div id="advice-bar">
                </div>
            </div>

        </div>
    </div>
    <div id="middle">
        <div id="eventlistcomponent" class="">
            <div id="eventboxContent" style="display: none;">
            </div>

            <script type="text/javascript">
                var session = "3c442273a6de4c8f79549e78f4c3ca50e7ea7580";
                var vacation = 0;
                var timerHandler = new TimerHandler();

                function redirectPremium() {
                    location.href = "<?php echo e(route('premium.index', ['showDarkMatter' => 1])); ?>#TODO_premium&showDarkMatter=1";
                }

                var playerId = "<?php echo e($currentPlayer->getId()); ?>";
                var playerName = "<?php echo e($currentPlayer->getUsername()); ?>";
                var player = {
                    "playerId": <?php echo e($currentPlayer->getId()); ?>,
                    "name": "<?php echo e($currentPlayer->getUsername()); ?>",
                    "hasCommander": false,
                    "hasAPassword": true
                };
                var hasAPassword = true;
                var jsloca = {
                    "INTERNAL_ERROR": "A previously unknown error has occurred. Unfortunately your last action couldn`t be executed!",
                    "LOCA_ALL_YES": "yes",
                    "LOCA_ALL_NO": "No",
                    "LOCA_NOTIFY_ERROR": "Error",
                    "LOCA_NOTIFY_INFO": "Info",
                    "LOCA_NOTIFY_SUCCESS": "Success",
                    "LOCA_NOTIFY_WARNING": "Warning",
                    "COMBATSIM_PLANNING": "Planning",
                    "COMBATSIM_PENDING": "Simulation running...",
                    "COMBATSIM_DONE": "Complete",
                    "MSG_RESTORE": "restore",
                    "MSG_DELETE": "delete",
                    "COPIED_TO_CLIPBOARD": "Copied to clipboard",
                    "LOCA_ALL_NETWORK_ATTENTION": "Caution",
                    "LOCA_NETWORK_MSG_GAMEOPERATOR": "Report this message to a game operator?"
                };
                var session = "3c442273a6de4c8f79549e78f4c3ca50e7ea7580";
                var isMobile = false;
                var isMobileApp = false;
                var isMobileOnly = false;
                var isFacebookUser = false;
                var overlayWidth = 770;
                var overlayHeight = 600;
                var isRTLEnabled = 0;
                var activateToken = "e018389e3827e1499e41d35e3c811283";
                var miniFleetToken = "4002a42efaeb2808f6c232594fb09aa4";
                var currentPage = "overview";
                var bbcodePreviewUrl = "<?php echo e(route('overview.index')); ?>#TODO_page=bbcodePreview";
                var popupWindows = [];
                var fleetDeutSaveFactor = 1;
                var honorScore = 0;
                var darkMatter = 0;
                var serverTime = new Date('<?php echo e(Carbon\Carbon::now()); ?>');
                var localTime = new Date();
                var timeDiff = serverTime - localTime;
                localTS = localTime.getTime();
                var startServerTime = localTime.getTime() - (0) - localTime.getTimezoneOffset() * 60 * 1000;
                var LocalizationStrings = {
                    "timeunits": {
                        "short": {
                            "year": "y",
                            "month": "m",
                            "week": "w",
                            "day": "d",
                            "hour": "h",
                            "minute": "m",
                            "second": "s"
                        }
                    },
                    "status": {
                        "ready": "done"
                    },
                    "decimalPoint": ".",
                    "thousandSeperator": ",",
                    "unitMega": "M",
                    "unitKilo": "K",
                    "unitMilliard": "B",
                    "question": "Question",
                    "error": "Error",
                    "loading": "load...",
                    "yes": "yes",
                    "no": "No",
                    "ok": "Ok",
                    "attention": "Caution",
                    "outlawWarning": "You are about to attack a stronger player. If you do this, your attack defenses will be shut down for 7 days and all players will be able to attack you without punishment. Are you sure you want to continue?",
                    "lastSlotWarningMoon": "This building will use the last available building slot. Expand your Lunar Base to receive more space. Are you sure you want to build this building?",
                    "lastSlotWarningPlanet": "This building will use the last available building slot. Expand your Terraformer or buy a Planet Field item to obtain more slots. Are you sure you want to build this building?",
                    "forcedVacationWarning": "Some game features are unavailable until your account is validated.",
                    "moreDetails": "More details",
                    "lessDetails": "Less detail",
                    "planetOrder": {
                        "lock": "Lock arrangement",
                        "unlock": "Unlock arrangement"
                    },
                    "darkMatter": "Dark Matter",
                    "activateItem": {
                        "upgradeItemQuestion": "Would you like to replace the existing item? The old bonus will be lost in the process.",
                        "upgradeItemQuestionHeader": "Replace item?"
                    }
                };
                var constants = {
                    "espionage": 6,
                    "missleattack": 10,
                    "language": "en",
                    "name": "144"
                };
                var userData = {
                    "id": "108130"
                };
                var missleAttackLink = "<?php echo e(route('overview.index')); ?>#TODO_page=missileattacklayer&width=669&height=250";
                var changeNickLink = "<?php echo e(route('changenick.overlay')); ?>";
                var showOutlawWarning = true;
                var miniFleetLink = "<?php echo e(route('fleet.dispatch.sendminifleet')); ?>";
                var ogameUrl = "<?php echo e(str_replace('/', '\/', URL::to('/'))); ?>";
                var startpageUrl = "<?php echo e(str_replace('/', '\/', URL::to('/'))); ?>";
                var nodePort = 19603;
                var nodeUrl = "<?php echo e(route('overview.index')); ?>#TODO_19603\/socket.io\/socket.io.js";
                var nodeParams = {
                    "port": 19603,
                    "secure": "true"
                };
                var chatUrl = "/"; //#TODO_page=ajaxChat
                var chatUrlLoadMoreMessages = "<?php echo e(route('overview.index')); ?>#TODO_page=chatGetAdditionalMessages";
                var chatLoca = {
                    "TEXT_EMPTY": "Where is the message?",
                    "TEXT_TOO_LONG": "The message is too long.",
                    "SAME_USER": "You cannot write to yourself.",
                    "IGNORED_USER": "You have ignored this player.",
                    "NO_DATABASE_CONNECTION": "A previously unknown error has occurred. Unfortunately your last action couldn`t be executed!",
                    "INVALID_PARAMETERS": "A previously unknown error has occurred. Unfortunately your last action couldn`t be executed!",
                    "SEND_FAILED": "A previously unknown error has occurred. Unfortunately your last action couldn`t be executed!",
                    "LOCA_ALL_ERROR_NOTACTIVATED": "This function is only available after your accounts activation.",
                    "X_NEW_CHATS": "#+# unread conversation(s)",
                    "MORE_USERS": "show more"
                };
                var eventboxLoca = {
                    "mission": "Mission",
                    "missions": "Missions",
                    "next misson": "DUMMY_KEY_N\u00e4chster_fertig",
                    "type": "DUMMY_KEY_Art",
                    "friendly": "own",
                    "neutral": "friendly",
                    "hostile": "hostile",
                    "nextEvent": "Next",
                    "nextEventText": "Type"
                };

                var ajaxEventboxURI = "<?php echo e(route('fleet.eventbox.fetch')); ?>";
                var ajaxRecallFleetURI = "<?php echo e(route('fleet.dispatch.recallfleet')); ?>";
                var currentSpaceObjectId = 33624092;
                var ajaxReloadComponentURI = "#TODO_index.php?page=standalone&ajax=1";

                function redirectLogout() {
                    location.href = "<?php echo e(route('overview.index')); ?>";
                }

                function redirectOverview() {
                    location.href = "<?php echo e(route('overview.index')); ?>";
                }

                function redirectPlatformLogout() {
                    location.href = "<?php echo e(route('overview.index')); ?>";
                }

                function redirectSpaceDock() {
                    location.href = "<?php echo e(route('facilities.index', ['openTech' => 36])); ?>";
                }

                reloadResources({
                    "resources": {
                        "population": {
                            "amount": 100,
                            "storage": 0,
                            "safeCapacity": 0,
                            "growthRate": 0,
                            "capableToFeed": 0,
                            "needFood": 0,
                            "singleFoodConsumption": 0,
                            "tooltip": "<?php echo app('translator')->get('Population'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"overmark\">100<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Living Space'); ?>\n<\/th><td><span class=\"overmark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Satisfied'); ?><\/th><td><span class=\"undermark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Hungry'); ?><\/th><td><span class=\"overmark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Growth rate'); ?><\/th><td><span class=\"\">\u00b10<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Bunker Space'); ?>\n<\/th><td><span class=\"middlemark\">100<\/span><\/td><\/tr><\/table>",
                            "classesListItem": ""
                        },
                        "food": {
                            "amount": 0,
                            "storage": 0,
                            "capableToFeed": 0,
                            "production": 0,
                            "consumption": 0,
                            "timeTillFoodRunsOut": 0,
                            "vacationMode": "",
                            "tooltip": "<?php echo app('translator')->get('Food'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"overmark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?><\/th><td><span class=\"overmark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Overproduction'); ?><\/th><td><span class=\"undermark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Consumption'); ?><\/th><td><span class=\"overmark\">0<\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Consumed in'); ?><\/th><td><span class=\"overmark timeTillFoodRunsOut\">~<\/span><\/td><\/tr><\/table>",
                            "classesListItem": ""
                        },
                        "metal": {
                            "amount": <?php echo $resources['metal']['amount']; ?>,
                            "storage": <?php echo $resources['metal']['storage']; ?>,
                            "baseProduction": 0, // TODO: add base production separately?
                            "production": <?php echo $resources['metal']['production_second']; ?>,
                            "tooltip": "<?php echo app('translator')->get('Metal'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"\"><?php echo $resources['metal']['amount_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?><\/th><td><span class=\"\"><?php echo $resources['metal']['storage_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Current production'); ?>:<\/th><td><span class=\"<?php if($resources['metal']['production_hour'] <= 0): ?> overmark <?php else: ?> undermark <?php endif; ?>\"><?php if($resources['metal']['production_hour'] > 0): ?>+<?php endif; ?><?php echo $resources['metal']['production_hour_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:<\/th><td><span class=\"overermark\">0<\/span><\/td><\/tr><\/table>",
                            "classesListItem": "",
                            "shopUrl": "#TODO_category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&item=859d82d316b83848f7365d21949b3e1e63c7841f&page=shop&panel1-1="
                        },
                        "crystal": {
                            "amount": <?php echo $resources['crystal']['amount']; ?>,
                            "storage": <?php echo $resources['crystal']['storage']; ?>,
                            "baseProduction": 0, // TODO: add base production separately?
                            "production": <?php echo $resources['crystal']['production_second']; ?>,
                            "tooltip": "<?php echo app('translator')->get('Crystal'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"\"><?php echo $resources['crystal']['amount_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?><\/th><td><span class=\"\"><?php echo $resources['crystal']['storage_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Current production'); ?>:<\/th><td><span class=\"<?php if($resources['crystal']['production_hour'] <= 0): ?> overmark <?php else: ?> undermark <?php endif; ?>\"><?php if($resources['crystal']['production_hour'] > 0): ?>+<?php endif; ?><?php echo $resources['crystal']['production_hour_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:<\/th><td><span class=\"overermark\">0<\/span><\/td><\/tr><\/table>",
                            "classesListItem": "",
                            "shopUrl": "#TODO_page=shop#category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&item=bb2f6843226ef598f0b567b92c51b283de90aa48&page=shop&panel1-1="
                        },
                        "deuterium": {
                            "amount": <?php echo $resources['deuterium']['amount']; ?>,
                            "storage": <?php echo $resources['deuterium']['storage']; ?>,
                            "baseProduction": 0, // TODO: add base production separately?
                            "production": <?php echo $resources['deuterium']['production_second']; ?>,
                            "tooltip": "<?php echo app('translator')->get('Deuterium'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"\"><?php echo $resources['deuterium']['amount_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Storage capacity'); ?><\/th><td><span class=\"\"><?php echo $resources['deuterium']['storage_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Current production'); ?>:<\/th><td><span class=\"<?php if($resources['deuterium']['production_hour'] <= 0): ?> overmark <?php else: ?> undermark <?php endif; ?>\"><?php if($resources['deuterium']['production_hour'] > 0): ?>+<?php endif; ?><?php echo $resources['deuterium']['production_hour_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Den Capacity'); ?>:<\/th><td><span class=\"overermark\">0<\/span><\/td><\/tr><\/table>",
                            "classesListItem": "",
                            "shopUrl": "#TODO_shop#category=d8d49c315fa620d9c7f1f19963970dea59a0e3be&item=cb72ed207dd871832a850ee29f1c1f83aa3f4f36&page=shop&panel1-1="
                        },
                        "energy": {
                            "amount": <?php echo $resources['energy']['amount']; ?>,
                            "tooltip": "<?php echo app('translator')->get('Energy'); ?>|<table class=\"resourceTooltip\"><tr><th><?php echo app('translator')->get('Available'); ?>:<\/th><td><span class=\"\"><?php echo $resources['energy']['amount_formatted']; ?><\/span><\/td><\/tr><tr><th>Current production:<\/th><td><span class=\"<?php echo e($resources['energy']['production'] > 0 ? 'undermark' : 'overmark'); ?>\"><?php echo e($resources['energy']['production'] > 0 ? '+' : ''); ?><?php echo $resources['energy']['production_formatted']; ?><\/span><\/td><\/tr><tr><th><?php echo app('translator')->get('Consumption'); ?><\/th><td><span class=\"<?php echo e($resources['energy']['consumption'] > 0 ? 'overmark' : ''); ?>\"><?php echo e($resources['energy']['consumption'] > 0 ? '-' : ''); ?><?php echo $resources['energy']['consumption_formatted']; ?><\/span><\/td><\/tr><\/table>",
                            "classesListItem": ""
                        },
                        "darkmatter": {
                            "amount": 22500,
                            "tooltip": "Dark Matter|<table class=\"resourceTooltip\"><tr><th>Available:<\/th><td><span class=\"\">22,500<\/span><\/td><\/tr><tr><th>Purchased<\/th><td><span class=\"\">0<\/span><\/td><\/tr><tr><th>Found<\/th><td><span class=\"\">22,500<\/span><\/td><\/tr><\/table>",
                            "classesListItem": "",
                            "classes": "overlay",
                            "link": "#TODO_page=payment",
                            "img": "/img/icons/401d1a91ff40dc7c8acfa4377d3d65.gif"
                        }
                    },
                    "techs": {
                        // TODO: add tech levels as far as they are available
                    },
                    "honorScore": 11,
                });

                function updateAjaxResourcebox(data) {
                    reloadResources(data);
                }

                function getAjaxResourcebox(callback) {
                    $.get("<?php echo e(route('overview.index')); ?>#TODO_page=fetchResources&ajax=1", function (data) {
                        reloadResources(data, callback);
                    }, "text");
                }

                var changeSettingsLink = "#TODO_page=changeSettings";
                var changeSettingsToken = "ea77594feda8933a60595311a0f56512";
                var eventlistLink = "<?php echo e(route('fleet.eventlist.fetch')); ?>";

                function openAnnouncement() {
                    openOverlay("<?php echo e(route('overview.index')); ?>#TODO_page=announcement&ajax=1", {
                        'class': 'announcement',
                        zIndex: 4000
                    });
                }

                var planetMoveLoca = {
                    "askTitle": "Resettle Planet",
                    "askCancel": "Are you sure that you wish to cancel this planet relocation? The normal waiting time will thereby be maintained.",
                    "yes": "yes",
                    "no": "No",
                    "success": "The planet relocation was successfully cancelled.",
                    "error": "Error"
                };

                function openPlanetRenameGiveupBox() {
                    openOverlay("<?php echo e(route('planetabandon.overlay')); ?>", {
                        title: "Abandon\/Rename <?php echo e($currentPlanet->getPlanetName()); ?>",
                        'class': "planetRenameOverlay"
                    });
                }

                var locaPremium = {
                    "buildingHalfOverlay": "Do you want to reduce the construction time by 50% of the total construction time () for <b>750 Dark Matter<\/b>?",
                    "buildingFullOverlay": "Do you want to immediately complete the construction order for <b>750 Dark Matter<\/b>?",
                    "shipsHalfOverlay": "Do you want to reduce the construction time by 50% of the total construction time () for <b>750 Dark Matter<\/b>?",
                    "shipsFullOverlay": "Do you want to immediately complete the construction order for <b>750 Dark Matter<\/b>?",
                    "researchHalfOverlay": "Do you want to reduce the research time by 50% of the total research time () for <b>750 Dark Matter<\/b>?",
                    "researchFullOverlay": "Do you want to immediately complete the research order for <b>750 Dark Matter<\/b>?"
                };
                var priceBuilding = 750;
                var priceResearch = 750;
                var priceShips = 750;
                var loca = loca || {};
                loca = $.extend({}, loca, {
                    "error": "Error",
                    "errorNotEnoughDM": "Not enough Dark Matter available! Do you want to buy some now?",
                    "notice": "Reference",
                    "planetGiveupQuestion": "Are you sure you want to abandon the planet %planetName% %planetCoordinates%?",
                    "moonGiveupQuestion": "Are you sure you want to abandon the moon %planetName% %planetCoordinates%?"
                });

                function type() {
                    var destination = document.getElementById(textDestination[currentIndex]);
                    if (destination) {
                        if (textContent[currentIndex].substr(currentChar, 1) == "<" && linetwo != 1) {
                            while (textContent[currentIndex].substr(currentChar, 1) != ">") {
                                currentChar++;
                            }
                        }
                        if (linetwo == 1) {
                            destination.innerHTML = textContent[currentIndex];
                            currentChar = destination.innerHTML = textContent[currentIndex].length + 1;
                        } else {
                            destination.innerHTML = textContent[currentIndex].substr(0, currentChar) + "_";
                            currentChar++;
                        }
                        if (currentChar > textContent[currentIndex].length) {
                            destination.innerHTML = textContent[currentIndex];
                            currentIndex++;
                            currentChar = 0;
                            if (currentIndex < textContent.length) {
                                type();
                            }
                        } else {
                            setTimeout("type()", 15);
                        }
                    }
                }

                function planetRenamed(data) {
                    if (data["status"]) {
                        $("#planetNameHeader").html(data["newName"]);
                        reloadPage();
                        $(".overlayDiv.planetRenameOverlay").dialog('close');
                    }
                    errorBoxAsArray(data["errorbox"]);
                }

                function reloadPage() {
                    location.href = "<?php echo e(url()->current()); ?>";
                }

                var demolish_id;
                var buildUrl;

                function loadDetails(type) {
                    url = "<?php echo e(route('overview.index', ['ajax' => 1])); ?>";
                    if (typeof (detailUrl) != 'undefined') {
                        url = detailUrl;
                    }
                    $.get(url, {type: type}, function (data) {
                        $("#detail").html(data);
                        $("#techDetailLoading").hide();
                        $("input[type='text']:first", document.forms["form"]).focus();
                        $(document).trigger("ajaxShowElement", (typeof techID === 'undefined' ? 0 : techID));
                    });
                }

                function sendBuildRequest(url, ev, showSlotWarning) {
                    console.debug("sendBuildRequest");
                    if (ev != undefined) {
                        var keyCode;
                        if (window.event) {
                            keyCode = window.event.keyCode;
                        } else if (ev) {
                            keyCode = ev.which;
                        } else {
                            return true;
                        }
                        console.debug("KeyCode: " + keyCode);
                        if (keyCode != 13 || $('#premiumConfirmButton')) {
                            return true;
                        }
                    }

                    function build() {
                        if (url == null) {
                            sendForm();
                        } else {
                            fastBuild();
                        }
                    }

                    if (url == null) {
                        fallBackFunc = sendForm;
                    } else {
                        fallBackFunc = build;
                        buildUrl = url;
                    }
                    if (showSlotWarning) {
                        build();
                    } else {
                        build();
                    }
                    return false;
                }

                function fastBuild() {
                    location.href = buildUrl;
                    return false;
                }

                function sendForm() {
                    document.form.submit();
                    return false;
                }

                function demolishBuilding(id, question) {
                    demolish_id = id;
                    question += "<br/><br/>" + $("#demolish" + id).html();
                    errorBoxDecision("Caution", "" + question + "", "yes", "No", demolishStart);
                }

                function demolishStart() {
                    window.location.replace("<?php echo e(route('resources.index', ['modus' => 3])); ?>&token=9c8a2a05984ebfd30e88ea2fd9da03df&type=" + demolish_id);
                }

                $('#planet').find('h2 a').hover(function () {
                    $('#planet').find('h2 a img').toggleClass('hinted');
                }, function () {
                    $('#planet').find('h2 a img').toggleClass('hinted');
                });
                var player = {hasCommander: false};
                var localizedBBCode = {
                    "bold": "Bold",
                    "italic": "Italic",
                    "underline": "Underline",
                    "stroke": "Strikethrough",
                    "sub": "Subscript",
                    "sup": "Superscript",
                    "fontColor": "Font colour",
                    "fontSize": "Font size",
                    "backgroundColor": "Background colour",
                    "backgroundImage": "Background image",
                    "tooltip": "Tool-tip",
                    "alignLeft": "Left align",
                    "alignCenter": "Centre align",
                    "alignRight": "Right align",
                    "alignJustify": "Justify",
                    "block": "Break",
                    "code": "Code",
                    "spoiler": "Spoiler",
                    "moreopts": "More Options",
                    "list": "List",
                    "hr": "Horizontal line",
                    "picture": "Image",
                    "link": "Link",
                    "email": "Email",
                    "player": "Player",
                    "item": "Item",
                    "coordinates": "Coordinates",
                    "preview": "Preview",
                    "textPlaceHolder": "Text...",
                    "playerPlaceHolder": "Player ID or name",
                    "itemPlaceHolder": "Item ID",
                    "coordinatePlaceHolder": "Galaxy:system:position",
                    "charsLeft": "Characters remaining",
                    "colorPicker": {
                        "ok": "Ok",
                        "cancel": "Cancel",
                        "rgbR": "R",
                        "rgbG": "G",
                        "rgbB": "B"
                    },
                    "backgroundImagePicker": {
                        "ok": "Ok",
                        "repeatX": "Repeat horizontally",
                        "repeatY": "Repeat vertically"
                    }
                }, itemNames = {
                    "090a969b05d1b5dc458a6b1080da7ba08b84ec7f": "Bronze Crystal Booster",
                    "e254352ac599de4dd1f20f0719df0a070c623ca8": "Bronze Deuterium Booster",
                    "b956c46faa8e4e5d8775701c69dbfbf53309b279": "Bronze Metal Booster",
                    "3c9f85221807b8d593fa5276cdf7af9913c4a35d": "Bronze Crystal Booster",
                    "422db99aac4ec594d483d8ef7faadc5d40d6f7d3": "Silver Crystal Booster",
                    "118d34e685b5d1472267696d1010a393a59aed03": "Gold Crystal Booster",
                    "d3d541ecc23e4daa0c698e44c32f04afd2037d84": "DETROID Bronze",
                    "0968999df2fe956aa4a07aea74921f860af7d97f": "DETROID Gold",
                    "27cbcd52f16693023cb966e5026d8a1efbbfc0f9": "DETROID Silver",
                    "d9fa5f359e80ff4f4c97545d07c66dbadab1d1be": "Bronze Deuterium Booster",
                    "e4b78acddfa6fd0234bcb814b676271898b0dbb3": "Silver Deuterium Booster",
                    "5560a1580a0330e8aadf05cb5bfe6bc3200406e2": "Gold Deuterium Booster",
                    "40f6c78e11be01ad3389b7dccd6ab8efa9347f3c": "KRAKEN Bronze",
                    "929d5e15709cc51a4500de4499e19763c879f7f7": "KRAKEN Gold",
                    "4a58d4978bbe24e3efb3b0248e21b3b4b1bfbd8a": "KRAKEN Silver",
                    "de922af379061263a56d7204d1c395cefcfb7d75": "Bronze Metal Booster",
                    "ba85cc2b8a5d986bbfba6954e2164ef71af95d4a": "Silver Metal Booster",
                    "05294270032e5dc968672425ab5611998c409166": "Gold Metal Booster",
                    "be67e009a5894f19bbf3b0c9d9b072d49040a2cc": "Bronze Moon Fields",
                    "05ee9654bd11a261f1ff0e5d0e49121b5e7e4401": "Gold Moon Fields",
                    "c21ff33ba8f0a7eadb6b7d1135763366f0c4b8bf": "Silver Moon Fields",
                    "485a6d5624d9de836d3eb52b181b13423f795770": "Bronze M.O.O.N.S.",
                    "45d6660308689c65d97f3c27327b0b31f880ae75": "Gold M.O.O.N.S.",
                    "fd895a5c9fd978b9c5c7b65158099773ba0eccef": "Silver M.O.O.N.S.",
                    "da4a2a1bb9afd410be07bc9736d87f1c8059e66d": "NEWTRON Bronze",
                    "8a4f9e8309e1078f7f5ced47d558d30ae15b4a1b": "NEWTRON Gold",
                    "d26f4dab76fdc5296e3ebec11a1e1d2558c713ea": "NEWTRON Silver",
                    "16768164989dffd819a373613b5e1a52e226a5b0": "Bronze Planet Fields",
                    "04e58444d6d0beb57b3e998edc34c60f8318825a": "Gold Planet Fields",
                    "0e41524dc46225dca21c9119f2fb735fd7ea5cb3": "Silver Planet Fields"
                };
                $(document).ready(function () {
                    initIndex();
                    initOverview();
                    initBuffBar();
                    tabletInitOverviewAdvice();
                    ogame.chat.showPlayerList('#chatBarPlayerList .cb_playerlist_box');
                    ogame.chat.showPlayerList('#sideBar');
                    var initChatAsyncInterval = window.setInterval(initChatAsync, 100);

                    function initChatAsync() {
                        if (ogame.chat.isLoadingPlayerList === false && ogame.chat.playerList !== null) {
                            clearInterval(initChatAsyncInterval);
                            ogame.chat.initChatBar(playerId);
                            ogame.chat.initChat(playerId, isMobile);
                            ogame.chat.updateCustomScrollbar($('.scrollContainer'));
                        }
                    }

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                });</script>            <!-- END JAVASCRIPT -->


            <?php echo $__env->yieldContent('content'); ?>
        </div>
    </div>
    <div id="right">
        <div id="planetbarcomponent" class="">
            <div id="rechts">
                <?php
                    // Get all current query parameters
                    $currentQueryParams = request()->query();
                    $totalPlanets = $currentPlayer->planets->planetCount();
                    $useCompactLayout = $totalPlanets >= 6;
                ?>
                <div id="<?php echo e($useCompactLayout ? 'cutty' : 'norm'); ?>">
                    <div id="<?php echo e($useCompactLayout ? 'myPlanets' : 'myWorlds'); ?>">
                        <div id="countColonies">
                            <p class="textCenter">
                                <span><?php echo e($currentPlayer->planets->planetCount()); ?>/<?php echo e($currentPlayer->getMaxPlanetAmount()); ?></span> <?php echo app('translator')->get('Planets'); ?>
                            </p>
                        </div>
                        <div id="planetList">
                            <?php $__currentLoopData = $planets->allPlanets(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $planet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    // Set or replace the 'cp' parameter
                                    $currentQueryParams['cp'] = $planet->getPlanetId();
                                    // Generate the URL to the current route with the updated query parameters
                                    $urlToPlanetWithUpdatedParam = request()->url() . '?' . http_build_query($currentQueryParams);
                                ?>
                                <div class="smallplanet <?php echo e($useCompactLayout ? 'smaller' : ''); ?> <?php echo e(($planet->getPlanetId() === $currentPlanet->getPlanetId() && $currentPlayer->planets->allCount() > 1) ? 'hightlightPlanet' : ''); ?>"
                                     data-planet-id="<?php echo e($planet->getPlanetId()); ?>" id="planet-<?php echo e($key + 1); ?>">
                                    <a href="<?php echo e($urlToPlanetWithUpdatedParam); ?>"
                                       data-link="<?php echo e($urlToPlanetWithUpdatedParam); ?>"
                                       title="<b><?php echo e($planet->getPlanetName()); ?> [<?php echo e($planet->getPlanetCoordinates()->asString()); ?>]</b><br/>
                                        <?php echo app('translator')->get('Lifeform'); ?>: Humans<br/>
                                        <?php echo e(OGame\Facades\AppUtil::formatNumber($planet->getPlanetDiameter())); ?>km (<?php echo e($planet->getBuildingCount()); ?>/<?php echo e($planet->getPlanetFieldMax()); ?>)<br>
                                        <?php echo e($planet->getPlanetTempMin()); ?> to <?php echo e($planet->getPlanetTempMax()); ?>°C<br/>
                                        <a href=&quot;<?php echo e(route('overview.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Overview'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('resources.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Resources'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('research.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Research'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('facilities.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Facilities'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('shipyard.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Shipyard'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('defense.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Defense'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('fleet.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Fleet'); ?></a><br/>
                                        <a href=&quot;<?php echo e(route('galaxy.index')); ?>?cp=<?php echo e($planet->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Galaxy'); ?></a><br/>"
                                       class="planetlink <?php echo e(($planet->getPlanetId() === $currentPlanet->getPlanetId() && $currentPlayer->planets->allCount() > 1) ? 'active' : ''); ?> tooltipRight tooltipClose js_hideTipOnMobile ipiHintable"
                                       data-ipi-hint="ipiPlanetHomeplanet">
                                        <?php if($useCompactLayout): ?>
                                            <div class="planetBarSpaceObjectContainer" style="height: 33px">
                                                <div class="planetBarSpaceObjectHighlightContainer" style="width: 25px; height: 25px;"></div>
                                                <img id="planetBarSpaceObjectImg_<?php echo e($planet->getPlanetId()); ?>"
                                                     class="planetPic js_replace2x"
                                                     alt="<?php echo e($planet->getPlanetName()); ?>"
                                                     src="<?php echo asset('img/planets/medium/' . $planet->getPlanetBiomeType() . '_' . $planet->getPlanetImageType() . '.png'); ?>"
                                                     width="30" height="30">
                                            </div>
                                        <?php else: ?>
                                            <img class="planetPic js_replace2x"
                                                 alt="<?php echo e($planet->getPlanetName()); ?>"
                                                 src="<?php echo asset('img/planets/medium/' . $planet->getPlanetBiomeType() . '_' . $planet->getPlanetImageType() . '.png'); ?>"
                                                 width="48" height="48">
                                        <?php endif; ?>
                                        <span class="planet-name "><?php echo $planet->getPlanetName(); ?></span>
                                        <span class="planet-koords ">[<?php echo $planet->getPlanetCoordinates()->asString(); ?>]</span>
                                    </a>

                                    <?php if($planet->isBuilding()): ?>
                                        <a class="constructionIcon tooltip js_hideTipOnMobile tpd-hideOnClickOutside"
                                           data-link="<?php echo e($urlToPlanetWithUpdatedParam); ?>"
                                           href="<?php echo e($urlToPlanetWithUpdatedParam); ?>"
                                           title="">
                                            <span class="icon12px icon_wrench"></span>
                                        </a>
                                    <?php endif; ?>

                                    <?php if($planet->hasMoon()): ?>
                                        <?php
                                            $moon = $planet->moon();
                                            $currentQueryParams['cp'] = $moon->getPlanetId();
                                            $urlToMoonWithUpdatedParam = request()->url() . '?' . http_build_query($currentQueryParams);
                                        ?>
                                        <a class="moonlink <?php echo e(($moon->getPlanetId() === $currentPlanet->getPlanetId() && $currentPlayer->planets->allCount() > 1) ? 'active' : ''); ?> tooltipLeft tooltipClose js_hideTipOnMobile"
                                           title="<b><?php echo e($moon->getPlanetName()); ?> [<?php echo e($moon->getPlanetCoordinates()->asString()); ?>]</b><br>
                                           <?php echo e(OGame\Facades\AppUtil::formatNumber($moon->getPlanetDiameter())); ?>km (<?php echo e($moon->getBuildingCount()); ?>/<?php echo e($moon->getPlanetFieldMax()); ?>)<br/>
                                           <a href=&quot;<?php echo e(route('overview.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Overview'); ?></a><br/>
                                           <a href=&quot;<?php echo e(route('resources.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Resources'); ?></a><br/>
                                           <a href=&quot;<?php echo e(route('facilities.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Facilities'); ?></a><br/>
                                           <a href=&quot;<?php echo e(route('defense.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Defense'); ?></a><br/>
                                           <a href=&quot;<?php echo e(route('fleet.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Fleet'); ?></a><br/>
                                           <a href=&quot;<?php echo e(route('galaxy.index')); ?>?cp=<?php echo e($moon->getPlanetId()); ?>&quot;><?php echo app('translator')->get('Galaxy'); ?></a><br/>"
                                           href="<?php echo e($urlToMoonWithUpdatedParam); ?>"
                                           data-link="<?php echo e($urlToMoonWithUpdatedParam); ?>"
                                           data-jumpgatelevel="0">
                                            <?php if($useCompactLayout): ?>
                                                <div class="planetBarSpaceObjectContainer" style="height: 20px">
                                                    <div class="planetBarSpaceObjectHighlightContainer" style="width: 16px; height: 16px;"></div>
                                                    <img id="planetBarSpaceObjectImg_<?php echo e($moon->getPlanetId()); ?>"
                                                         src="/img/moons/small/<?php echo e($moon->getPlanetImageType()); ?>.gif"
                                                         width="16" height="16"
                                                         alt="Moon"
                                                         class="icon-moon">
                                                </div>
                                            <?php else: ?>
                                                <img src="/img/moons/small/<?php echo e($moon->getPlanetImageType()); ?>.gif"
                                                     width="16" height="16"
                                                     alt="Moon"
                                                     class="icon-moon">
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="bannerSkyscrapercomponent" class="">
            <div id="banner_skyscraper" class="desktop" name="banner_skyscraper">
                <div style="position: relative;">
                    <a class="tooltipLeft " title="" href="#TODO=shop">
                        <img src="/img/banners/de0dadddb0285ba78b026ce18fc898.jpg" alt="">
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chat Bar -->
<div id="chatBar">
    <ul class="chat_bar_list">
        <li id="chatBarPlayerList" class="chat_bar_pl_list_item">
            <div class="cb_playerlist_box"
                 style="display:none;">
            </div>
            <span class="onlineCount"><?php echo app('translator')->get(':count Contact(s) online', ['count' => 0]); ?></span>
        </li>
    </ul><!-- END Chat Bar List -->
</div>
<!-- END Chat Bar -->

<button class="scroll_to_top">
    <span class="arrow"></span><?php echo app('translator')->get('Back to top'); ?>
</button>

<div id="siteFooter">
    <div class="content" style="font-size:10px">
        <div class="fleft textLeft">
            <a href="#TODO_changelog&ajax=1" class="tooltip js_hideTipOnMobile overlay" data-class="noXScrollbar"
               data-overlay-iframe="true" data-iframe-width="680" data-overlay-title="Patch notes">
                <?php echo e(\OGame\Facades\GitInfoUtil::getAppVersion()); ?></a>
            <a class="homeLink" href="https://github.com/lanedirt/ogamex" target="_blank">©
                OGameX. <?php echo app('translator')->get('All rights reserved.'); ?></a>
        </div>
        <div class="fright textRight">
            <a href="<?php echo e(route('serversettings.overlay')); ?>" class="overlay"
               data-overlay-title="<?php echo app('translator')->get('Server Settings'); ?>" data-overlay-class="serversettingsoverlay"
               data-overlay-popup-width="400" data-overlay-popup-height="510"><?php echo app('translator')->get('Server Settings'); ?></a>|
            <a href="http://wiki.ogame.org/" target="_blank">Help</a>|
            <?php switch($locale):
                case ('en'): ?>
                    <a href="<?php echo e(route('language.switch', ['lang' => 'en'])); ?>" class="bold">English</a>|
                    <a href="<?php echo e(route('language.switch', ['lang' => 'nl'])); ?>">Dutch</a>|
                    <?php break; ?>
                <?php case ('nl'): ?>
                    <a href="<?php echo e(route('language.switch', ['lang' => 'en'])); ?>">English</a>|
                    <a href="<?php echo e(route('language.switch', ['lang' => 'nl'])); ?>" class="bold">Dutch</a>|
                    <?php break; ?>
                <?php default: ?>
                    <a href="<?php echo e(route('language.switch', ['lang' => 'en'])); ?>" class="bold">English</a>|
                    <a href="<?php echo e(route('language.switch', ['lang' => 'nl'])); ?>">Dutch</a>|
            <?php endswitch; ?>
            <a href="#">Board</a>|
            <a class="overlay-temp" href="#" data-overlay-iframe="true" data-iframe-width="450"
               data-overlay-title="Rules"><?php echo app('translator')->get('Rules'); ?></a>|
            <a href="#"><?php echo app('translator')->get('Legal'); ?></a>
        </div>
    </div><!-- -->
</div>

<!-- #MMO:NETBAR# -->
<div id="pagefoldtarget"></div>

<!-- ogame/en ingame 16.12.2017 12:46 -->
<script type="text/javascript">
    //mmoInitSelect();
    //mmoTicker();    mmoToggleDisplay.init("mmoGamesOverviewPanel");

    <?php if(\Session::has('success')): ?>
    $(document).ready(function () {
        fadeBox("<?php echo \Session::get('success'); ?>", 0);
    });
    <?php endif; ?>
    <?php if(\Session::has('error')): ?>
    $(document).ready(function () {
        fadeBox("<?php echo \Session::get('error'); ?>", 1);
    });
    <?php endif; ?>
    <?php if(\Session::has('success_logout')): ?>
    $(document).ready(function () {
        errorBoxNotify("Ok", "<?php echo \Session::get('success_logout'); ?>", "Ok", redirectLogout);
    });
    <?php endif; ?>
</script>


<!-- #/MMO:NETBAR# -->
<div id="decisionTB" style="display:none;">
    <div id="errorBoxDecision" class="errorBox TBfixedPosition">
        <div class="head"><h4 id="errorBoxDecisionHead">-</h4></div>
        <div class="middle">
            <span id="errorBoxDecisionContent">-</span>
            <div class="response">
                <div style="float:left; width:180px;">
                    <a href="javascript:void(0);" class="yes"><span id="errorBoxDecisionYes">.</span></a>
                </div>
                <div style="float:left; width:180px;">
                    <a href="javascript:void(0);" class="no"><span id="errorBoxDecisionNo">.</span></a>
                </div>
                <br class="clearfloat"/>
            </div>
        </div>
        <div class="foot"></div>
    </div>
</div>

<div id="fadeBox" class="fadeBox fixedPostion" style="display:none;">
    <span id="fadeBoxStyle" class="success"></span>
    <p id="fadeBoxContent"></p>
</div>

<div id="notifyTB" style="display:none;">
    <div id="errorBoxNotify" class="errorBox TBfixedPosition">
        <div class="head"><h4 id="errorBoxNotifyHead">-</h4></div>
        <div class="middle">
            <span id="errorBoxNotifyContent">-</span>
            <div class="response">
                <div>
                    <a href="javascript:void(0);" class="ok">
                        <span id="errorBoxNotifyOk">.</span>
                    </a>
                </div>
                <br class="clearfloat"/>
            </div>
        </div>
        <div class="foot"></div>
    </div>
</div>
<script type="text/javascript">var visibleChats = {"players": [], "associations": []};
    var bigChatLink = "<?php echo e(route('overview.index')); ?>#TODO_page=chat";
    var locaKeys = {
        "bold": "<?php echo app('translator')->get('Bold'); ?>",
        "italic": "<?php echo app('translator')->get('Italic'); ?>",
        "underline": "<?php echo app('translator')->get('Underline'); ?>",
        "stroke": "<?php echo app('translator')->get('Strikethrough'); ?>",
        "sub": "<?php echo app('translator')->get('Subscript'); ?>",
        "sup": "<?php echo app('translator')->get('Superscript'); ?>",
        "fontColor": "<?php echo app('translator')->get('Font colour'); ?>",
        "fontSize": "<?php echo app('translator')->get('Font size'); ?>",
        "backgroundColor": "<?php echo app('translator')->get('Background colour'); ?>",
        "backgroundImage": "<?php echo app('translator')->get('Background image'); ?>",
        "tooltip": "<?php echo app('translator')->get('Tool-tip'); ?>",
        "alignLeft": "<?php echo app('translator')->get('Left align'); ?>",
        "alignCenter": "<?php echo app('translator')->get('Centre align'); ?>",
        "alignRight": "<?php echo app('translator')->get('Right align'); ?>",
        "alignJustify": "<?php echo app('translator')->get('Justify'); ?>",
        "block": "<?php echo app('translator')->get('Break'); ?>",
        "code": "<?php echo app('translator')->get('Code'); ?>",
        "spoiler": "<?php echo app('translator')->get('Spoiler'); ?>",
        "moreopts": "<?php echo app('translator')->get('More Options'); ?>",
        "list": "<?php echo app('translator')->get('List'); ?>",
        "hr": "<?php echo app('translator')->get('Horizontal line'); ?>",
        "picture": "<?php echo app('translator')->get('Image'); ?>",
        "link": "<?php echo app('translator')->get('Link'); ?>",
        "email": "<?php echo app('translator')->get('Email'); ?>",
        "player": "<?php echo app('translator')->get('Player'); ?>",
        "item": "<?php echo app('translator')->get('Item'); ?>",
        "coordinates": "<?php echo app('translator')->get('Coordinates'); ?>",
        "preview": "<?php echo app('translator')->get('Preview'); ?>",
        "textPlaceHolder": "<?php echo app('translator')->get('Text...'); ?>",
        "playerPlaceHolder": "<?php echo app('translator')->get('Player ID or name'); ?>",
        "itemPlaceHolder": "<?php echo app('translator')->get('Item ID'); ?>",
        "coordinatePlaceHolder": "<?php echo app('translator')->get('Galaxy:system:position'); ?>",
        "charsLeft": "<?php echo app('translator')->get('Characters remaining'); ?>",
        "colorPicker": {"ok": "<?php echo app('translator')->get('Ok'); ?>", "cancel": "<?php echo app('translator')->get('Cancel'); ?>", "rgbR": "R", "rgbG": "G", "rgbB": "B"},
        "backgroundImagePicker": {
            "ok": "Ok",
            "repeatX": "<?php echo app('translator')->get('Repeat horizontally'); ?>",
            "repeatY": "<?php echo app('translator')->get('Repeat vertically'); ?>"
        }
    };</script>
</body>
</html>

<?php /**PATH /var/www/resources/views/ingame/layouts/main.blade.php ENDPATH**/ ?>