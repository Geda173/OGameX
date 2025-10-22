<?php /** @var \OGame\ViewModels\FleetEventRowViewModel $fleet_event_row */ ?>

<?php if($fleet_event_row->is_return_trip): ?>
    <tr class="eventFleet" id="eventRow-<?php echo e($fleet_event_row->id); ?>"
        data-mission-type="<?php echo e($fleet_event_row->mission_type); ?>"
        data-return-flight="true"
        data-arrival-time="<?php echo e($fleet_event_row->mission_time_arrival); ?>"
    >
        <td class="countDown">
        <span id="counter-eventlist-<?php echo e($fleet_event_row->id); ?>" class="friendly textBeefy">
                    load...
        </span>
        </td>
        <td class="arrivalTime"><?php echo e(date('H:i:s', $fleet_event_row->mission_time_arrival)); ?> Clock</td>
        <td class="missionFleet">
            <img src="/img/fleet/<?php echo e($fleet_event_row->mission_type); ?>.gif" class="tooltipHTML"
                 title="Own fleet | <?php echo e($fleet_event_row->mission_label); ?> (R)" alt=""/>
        </td>

        <td class="originFleet">
            <?php switch($fleet_event_row->destination_planet_type):
                case (OGame\Models\Enums\PlanetType::Planet): ?>
                    <figure class="planetIcon planet js_hideTipOnMobile" title="Planet"></figure>
                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::Moon): ?>
                    <figure class="planetIcon moon js_hideTipOnMobile" title="Moon"></figure>
                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::DebrisField): ?>
                    <figure class="planetIcon tf js_hideTipOnMobile" title="Debris Field"></figure>
                    <?php break; ?>
            <?php endswitch; ?>
            <?php echo e($fleet_event_row->destination_planet_name); ?>

        </td>
        <td class="coordsOrigin">
            <a href="<?php echo e(route('galaxy.index', ['galaxy' => $fleet_event_row->destination_planet_coords->galaxy, 'system' => $fleet_event_row->destination_planet_coords->system])); ?>"
               target="_top">
                [<?php echo e($fleet_event_row->destination_planet_coords->asString()); ?>]
            </a>
        </td>

        <td class="detailsFleet">
            <span><?php echo e($fleet_event_row->fleet_unit_count); ?></span>
        </td>
        <td class="icon_movement_reserve">
            <span class="tooltip tooltipRight tooltipClose"
                  title="&lt;div class=&quot;htmlTooltip&quot;&gt;
    &lt;h1&gt;<?php echo app('translator')->get('Fleet details'); ?>:&lt;/h1&gt;
    &lt;div class=&quot;splitLine&quot;&gt;&lt;/div&gt;
            &lt;table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;fleetinfo&quot;&gt;
            &lt;tr&gt;
                &lt;th colspan=&quot;3&quot;&gt;<?php echo app('translator')->get('Ships'); ?>:&lt;/th&gt;
            &lt;/tr&gt;
            <?php /** @var \OGame\GameObjects\Models\Units\UnitCollection $fleet_unit */ ?>
            <?php $__currentLoopData = $fleet_event_row->fleet_units->units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fleet_unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo e($fleet_unit->unitObject->title); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_unit->amount); ?>&lt;/td&gt;
                &lt;/tr&gt;
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                &lt;tr&gt;
                    &lt;th colspan=&quot;3&quot;&gt;&nbsp;&lt;/th&gt;
                &lt;/tr&gt;

                &lt;tr&gt;
                    &lt;th colspan=&quot;3&quot;&gt;<?php echo app('translator')->get('Shipment'); ?>:&lt;/th&gt;
                &lt;/tr&gt;

                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Metal'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->metal->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;

                                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Crystal'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->crystal->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;
                                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Deuterium'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->deuterium->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;
            &lt;/table&gt;
    &lt;/div&gt;
">
                &nbsp;
            </span>
        </td>

        <!--
           &lt;tr&gt;
                        &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Food'); ?>:&lt;/td&gt;
                        &lt;td class=&quot;value&quot;&gt;0&lt;/td&gt;
                    &lt;/tr&gt;
        -->

        <td class="destFleet">
            <?php switch($fleet_event_row->origin_planet_type):
                case (OGame\Models\Enums\PlanetType::Planet): ?>
                    <figure class="planetIcon planet js_hideTipOnMobile" title="Planet"></figure><?php echo e($fleet_event_row->origin_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::Moon): ?>
                    <figure class="planetIcon moon js_hideTipOnMobile" title="Moon"></figure><?php echo e($fleet_event_row->origin_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::DebrisField): ?>
                    <figure class="planetIcon tf js_hideTipOnMobile" title="Debris Field"></figure>debris field
                    <?php break; ?>
            <?php endswitch; ?>
        </td>
        <td class="destCoords">
            <a href="<?php echo e(route('galaxy.index', ['galaxy' => $fleet_event_row->origin_planet_coords->galaxy, 'system' => $fleet_event_row->origin_planet_coords->system])); ?>"
               target="_top">
                [<?php echo e($fleet_event_row->origin_planet_coords->asString()); ?>]
            </a>
        </td>

        <td class="sendMail">
        </td>
        <td class="sendProbe">
        </td>
        <td class="sendMail">
        </td>
    </tr>
<?php else: ?>
    <tr class="eventFleet" id="eventRow-<?php echo e($fleet_event_row->id); ?>"
        data-mission-type="<?php echo e($fleet_event_row->mission_type); ?>"
        data-return-flight="false"
        data-arrival-time="<?php echo e($fleet_event_row->mission_time_arrival); ?>"
    >
        <td class="countDown">
        <span id="counter-eventlist-<?php echo e($fleet_event_row->id); ?>" class="friendly textBeefy">
                    load...
        </span>
        </td>
        <td class="arrivalTime"><?php echo e(date('H:i:s', $fleet_event_row->mission_time_arrival)); ?> Clock</td>
        <td class="missionFleet">
            <img src="/img/fleet/<?php echo e($fleet_event_row->mission_type); ?>.gif" class="tooltipHTML"
                 title="Own fleet | <?php echo e($fleet_event_row->mission_label); ?>" alt=""/>
        </td>

        <td class="originFleet">
            <?php switch($fleet_event_row->origin_planet_type):
                case (OGame\Models\Enums\PlanetType::Planet): ?>
                    <figure class="planetIcon planet js_hideTipOnMobile" title="Planet"></figure><?php echo e($fleet_event_row->origin_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::Moon): ?>
                    <figure class="planetIcon moon js_hideTipOnMobile" title="Moon"></figure><?php echo e($fleet_event_row->origin_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::DebrisField): ?>
                    <figure class="planetIcon tf js_hideTipOnMobile" title="Debris Field"></figure>debris field
                    <?php break; ?>
            <?php endswitch; ?>
        </td>
        <td class="coordsOrigin">
            <a href="<?php echo e(route('galaxy.index', ['galaxy' => $fleet_event_row->origin_planet_coords->galaxy, 'system' => $fleet_event_row->origin_planet_coords->system])); ?>"
               target="_top">
                [<?php echo e($fleet_event_row->origin_planet_coords->asString()); ?>]
            </a>
        </td>

        <td class="detailsFleet">
            <span><?php echo e($fleet_event_row->fleet_unit_count); ?></span>
        </td>
        <td class="icon_movement">
            <span class="tooltip tooltipRight tooltipClose"
                  title="&lt;div class=&quot;htmlTooltip&quot;&gt;
    &lt;h1&gt;<?php echo app('translator')->get('Fleet details'); ?>:&lt;/h1&gt;
    &lt;div class=&quot;splitLine&quot;&gt;&lt;/div&gt;
            &lt;table cellpadding=&quot;0&quot; cellspacing=&quot;0&quot; class=&quot;fleetinfo&quot;&gt;
            &lt;tr&gt;
                &lt;th colspan=&quot;3&quot;&gt;<?php echo app('translator')->get('Ships'); ?>:&lt;/th&gt;
            &lt;/tr&gt;
            <?php /** @var \OGame\GameObjects\Models\Units\UnitCollection $fleet_unit */ ?>
            <?php $__currentLoopData = $fleet_event_row->fleet_units->units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fleet_unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo e($fleet_unit->unitObject->title); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_unit->amount); ?>&lt;/td&gt;
                &lt;/tr&gt;
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                &lt;tr&gt;
                    &lt;th colspan=&quot;3&quot;&gt;&nbsp;&lt;/th&gt;
                &lt;/tr&gt;

                &lt;tr&gt;
                    &lt;th colspan=&quot;3&quot;&gt;<?php echo app('translator')->get('Shipment'); ?>:&lt;/th&gt;
                &lt;/tr&gt;

                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Metal'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->metal->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;

                                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Crystal'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->crystal->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;
                                &lt;tr&gt;
                    &lt;td colspan=&quot;2&quot;&gt;<?php echo app('translator')->get('Deuterium'); ?>:&lt;/td&gt;
                    &lt;td class=&quot;value&quot;&gt;<?php echo e($fleet_event_row->resources->deuterium->getFormattedLong()); ?>&lt;/td&gt;
                &lt;/tr&gt;
            &lt;/table&gt;
    &lt;/div&gt;
">
                &nbsp;
            </span>
        </td>

        <td class="destFleet">
            <?php switch($fleet_event_row->destination_planet_type):
                case (OGame\Models\Enums\PlanetType::Planet): ?>
                    <figure class="planetIcon planet js_hideTipOnMobile" title="Planet"></figure><?php echo e($fleet_event_row->destination_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::Moon): ?>
                    <figure class="planetIcon moon js_hideTipOnMobile" title="Moon"></figure><?php echo e($fleet_event_row->destination_planet_name); ?>

                    <?php break; ?>
                <?php case (OGame\Models\Enums\PlanetType::DebrisField): ?>
                    <figure class="planetIcon tf js_hideTipOnMobile" title="Debris Field"></figure>debris field
                    <?php break; ?>
            <?php endswitch; ?>
        </td>
        <td class="destCoords">
            <a href="<?php echo e(route('galaxy.index', ['galaxy' => $fleet_event_row->destination_planet_coords->galaxy, 'system' => $fleet_event_row->destination_planet_coords->system])); ?>"
               target="_top">
                [<?php echo e($fleet_event_row->destination_planet_coords->asString()); ?>]
            </a>
        </td>

        <td class="sendMail">
            <?php if($fleet_event_row->is_recallable): ?>
                <span class="reversal reversal_time" ref="<?php echo e($fleet_event_row->id); ?>">
                    <a class="icon_link tooltipHTML recallFleet" data-fleet-id="<?php echo e($fleet_event_row->id); ?>"
                       title="Recall:| 22.04.2024<br>15:28:45">
                        <img src="https://gf2.geo.gfsrv.net/cdna2/89624964d4b06356842188dba05b1b.gif" height="16" width="16"/>
                    </a>
                </span>
            <?php endif; ?>
        </td>
        <td class="sendProbe">
        </td>
        <td class="sendMail">
        </td>
    </tr>
<?php endif; ?>

<script type="text/javascript">
    (function ($) {
        new eventboxCountdown(
            $("#counter-eventlist-<?php echo e($fleet_event_row->id); ?>"),
                <?php echo e($fleet_event_row->mission_time_arrival); ?> - <?php echo e(time()); ?>,
            $("#eventListWrap"),
            "#TODO_page=componentOnly&component=eventList&action=checkEvents&ajax=1&asJson=1",
            [0, 1]
        );
    })(jQuery);
</script><?php /**PATH /var/www/resources/views/ingame/fleetevents/eventrow.blade.php ENDPATH**/ ?>