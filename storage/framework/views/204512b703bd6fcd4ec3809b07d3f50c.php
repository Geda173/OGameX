<?php /** @var OGame\Models\Resources $price */ ?>

<div id="technologydetails" data-technology-id="3">
    <div class="sprite sprite_large building <?php echo e($object->class_name); ?>">
        <?php if($has_requirements): ?>
            <button class="technology_tree  tooltip js_hideTipOnMobile overlay ipiHintable"
                    aria-label="<?php echo app('translator')->get('Open techtree'); ?>"
                    data-target="<?php echo e(route('techtree.ajax', ['tab' => 1, 'object_id' => $object->id])); ?>"
                    data-ipi-hint="ipiTechnologyTreefusionPlant"
                    data-tooltip-title="Open techtree">
                Techtree
            </button>
        <?php else: ?>
            <button class="technology_tree no_prerequisites tooltip js_hideTipOnMobile overlay ipiHintable"
                    aria-label="<?php echo app('translator')->get('Open techtree'); ?>" title="<?php echo app('translator')->get('No requirements available'); ?>"
                    data-target="<?php echo e(route('techtree.ajax', ['tab' => 1, 'object_id' => $object->id])); ?>"
                    data-ipi-hint="ipiTechnologyTreedeuteriumSynthesizer"> <?php echo app('translator')->get('Techtree'); ?>
            </button>
        <?php endif; ?>

        <?php if($object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Building || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Station || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Research): ?>
            <?php if(!empty($build_active_current) && $build_active_current->object->id == $object->id): ?>
                <a role="button" href="javascript:void(0);" class="tooltip abort_link js_hideTipOnMobile" title="" onclick="cancelbuilding(<?php echo e($object->id); ?>,<?php echo e($build_active_current->id); ?>,'Cancel expansion of <?php echo e($object->title); ?> to level <?php echo e($build_active_current->level_target); ?>?'); return false;"></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="content">
        <button class="close">✖</button>
        <h3><?php echo $title; ?></h3>

        <div class="information">
            <?php if($object_type === \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $object_type === \OGame\GameObjects\Models\Enums\GameObjectType::Defense): ?>
                <span class="amount" data-value="<?php echo e($next_level); ?>">
                     <?php echo app('translator')->get('Number'); ?>: <?php echo $current_level; ?>

                </span>
            <?php else: ?>
                <span class="level" data-value="<?php echo e($next_level); ?>">
                    <?php echo app('translator')->get('Level'); ?> <?php echo $current_level; ?>

                </span>
            <?php endif; ?>
            <ul class="narrow">

                <li class="build_duration"><strong><?php echo app('translator')->get('Production duration:'); ?></strong>
                    <time class="value tooltip" datetime="<?php echo e($production_datetime); ?>" title=""><?php echo $production_time; ?>

                        <!--
                        For event discounts
                        <span class="bonus" data-value="0">
                            -0%)
                        </span>
                        -->
                    </time>
                </li>

                <?php if($energy_difference > 0): ?>
                    <li class="additional_energy_consumption"><strong><?php echo app('translator')->get('Energy needed:'); ?></strong>
                        <span class="value tooltip"
                              data-value="<?php echo e($energy_difference); ?>"
                              title=""><?php echo e($energy_difference); ?>

                        </span>
                    </li>
                <?php elseif($energy_difference < 0): ?>
                    <li class="energy_production">
                        <strong><?php echo app('translator')->get('Production'); ?>:</strong>
                        <span class="value tooltip" data-value="<?php echo e($production_next->energy->get()); ?>" title=""><?php echo e($production_next->energy->getFormattedLong()); ?>

                            <span class="bonus" data-value="<?php echo e(($energy_difference * -1)); ?>">
                                (+<?php echo e(\OGame\Facades\AppUtil::formatNumberLong($energy_difference * -1)); ?>)
                            </span>
                        </span>

                    </li>
                <?php endif; ?>
            </ul>

            <div class="costs">
                <?php if($object_type === \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $object_type === \OGame\GameObjects\Models\Enums\GameObjectType::Defense): ?>
                    <p><?php echo app('translator')->get('Costs per piece'); ?>:</p>
                <?php else: ?>
                    <p><?php echo app('translator')->get('Required to improve to level'); ?> <?php echo $next_level; ?>:</p>
                <?php endif; ?>

                <ul class="ipiHintable" data-ipi-hint="">
                    <?php if(!empty($price->metal->get())): ?>
                        <li class="resource metal icon sufficient tooltip js_hideTipOnMobile
                        <?php if($planet->metal()->get() < $price->metal->get()): ?>
                        insufficient
                        <?php else: ?>
                        sufficient
                        <?php endif; ?>" data-value="<?php echo e($price->metal->get()); ?>"
                            aria-label="<?php echo $price->metal->getFormattedLong(); ?>  <?php echo app('translator')->get('Metal'); ?>" title="<?php echo $price->metal->getFormattedLong(); ?>  <?php echo app('translator')->get('Metal'); ?>">
                            <?php echo $price->metal->getFormatted(); ?>

                        </li>
                    <?php endif; ?>
                    <?php if(!empty($price->crystal->get())): ?>
                            <li class="resource crystal icon sufficient tooltip js_hideTipOnMobile
                        <?php if($planet->crystal()->get() < $price->crystal->get()): ?>
                        insufficient
                        <?php else: ?>
                        sufficient
                        <?php endif; ?>" data-value="<?php echo e($price->crystal->get()); ?>"
                                aria-label="<?php echo $price->crystal->getFormattedLong(); ?>  <?php echo app('translator')->get('Crystal'); ?>" title="<?php echo $price->crystal->getFormattedLong(); ?>  <?php echo app('translator')->get('Crystal'); ?>">
                                <?php echo $price->crystal->getFormatted(); ?>

                            </li>
                    <?php endif; ?>
                    <?php if(!empty($price->deuterium->get())): ?>
                            <li class="resource deuterium icon sufficient tooltip js_hideTipOnMobile
                        <?php if($planet->deuterium()->get() < $price->deuterium->get()): ?>
                        insufficient
                        <?php else: ?>
                        sufficient
                        <?php endif; ?>" data-value="<?php echo e($price->deuterium->get()); ?>"
                                aria-label="<?php echo $price->deuterium->getFormattedLong(); ?>  <?php echo app('translator')->get('Deuterium'); ?>" title="<?php echo $price->deuterium->getFormattedLong(); ?>  <?php echo app('translator')->get('Deuterium'); ?>">
                                <?php echo $price->deuterium->getFormatted(); ?>

                            </li>
                    <?php endif; ?>
                    <?php if(!empty($price->energy->get())): ?>
                            <li class="resource energy icon sufficient tooltip js_hideTipOnMobile
                        <?php if($planet->energyProduction()->get() < $price->energy->get()): ?>
                        insufficient
                        <?php else: ?>
                        sufficient
                        <?php endif; ?>" data-value="<?php echo e($price->energy->get()); ?>"
                                aria-label="<?php echo $price->energy->getFormattedLong(); ?>  <?php echo app('translator')->get('Energy'); ?>" title="<?php echo $price->energy->getFormattedLong(); ?>  <?php echo app('translator')->get('Energy'); ?>">
                                <?php echo $price->energy->getFormatted(); ?>

                            </li>
                    <?php endif; ?>
                </ul>

            </div>

            <div id="demolition_costs_tooltip" class="htmlTooltip">
                <h1>Deconstruction costs</h1>

                <div class="splitLine"></div>

                <table class="demolition_costs">

                    <tr class="demolition_costs_bonus">
                        <th>Ion technology bonus:</th>
                        <td data-value="24">-24%</td>
                    </tr>
                    <tr class="metal">
                        <th>Metal:</th>
                        <td class="sufficient" data-value="33279">33,279</td>
                    </tr>
                    <tr class="crystal">
                        <th>Crystal:</th>
                        <td class="sufficient" data-value="11092">11,092</td>
                    </tr>
                    <tr class="demolition_duration">
                        <th>Duration:</th>
                        <td>
                            <time datetime="PT6M22S"></time>6m 22s
                        </td>
                    </tr>
                </table>
            </div>

            <div id="demolition_costs_tooltip_oneTimeelement" class="htmlTooltip" style="display: none">
                <h1>Deconstruction costs</h1>
                <div class="splitLine"></div>
                <table class="demolition_costs">
                    <tr class="demolition_costs_bonus">
                        <th>Ion technology bonus:</th>
                        <td data-value="24">-24%</td>
                    </tr>
                    <tr class="metal">
                        <th>Metal:</th>
                        <td class="sufficient" data-value="33279">33,279</td>
                    </tr>
                    <tr class="crystal">
                        <th>Crystal:</th>
                        <td class="sufficient" data-value="11092">11,092</td>
                    </tr>
                    <tr class="demolition_duration">
                        <th>Duration:</th>
                        <td>
                            <time datetime="PT6M22S"></time>6m 22s
                        </td>
                    </tr>
                </table>

            </div>

            <?php if($max_build_amount && ($object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Defense)): ?>
                <div class="build_amount">
                    <label for="build_amount">Number:</label>
                    <input type="text" name="build_amount" id="build_amount" min="0" max="<?php echo e($max_build_amount); ?>" onfocus="clearInput(this);" onkeyup="checkIntInput(this, 1, <?php echo e($max_build_amount); ?>);event.stopPropagation();">
                    <button class="maximum">[max. <?php echo e($max_build_amount); ?>]</button>
                </div>
            <?php elseif($object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Building || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Station): ?>
                <!-- TODO: implement downgrade feature -->
                <!--<button class="downgrade" data-technology="3" data-name="<?php echo e($title); ?>">
                    <div class="demolish_img tooltipRel ipiHintable" rel="demolition_costs_tooltip_oneTimeelement"
                         data-ipi-hint="ipiTechnologyTearDowndeuteriumSynthesizer"></div>
                    <span class="label">tear down</span>
                </button>-->
            <?php endif; ?>

            <div class="build-it_wrap">
                <div class="ipiHintable" data-ipi-hint="ipiTechnologyUpgradedeuteriumSynthesizer">
                    <button class="upgrade"
                            <?php
                                $disabled_shipyard_upgrading = ($object->type == \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $object->type == \OGame\GameObjects\Models\Enums\GameObjectType::Defense) && $shipyard_upgrading;
                                $ships_being_built = $object->machine_name == 'shipyard' && $ship_or_defense_in_progress;
                            ?>

                            <?php if(!$enough_resources || !$requirements_met || !$valid_planet_type || $build_queue_max || !$max_build_amount || $research_lab_upgrading || ($object->machine_name === 'research_lab' && $research_in_progress || $disabled_shipyard_upgrading || $ships_being_built)): ?>
                                disabled
                            <?php else: ?>
                            <?php endif; ?>
                            data-technology="<?php echo e($object->id); ?>">
                            <?php
                                $tooltip = $disabled_shipyard_upgrading ? __('Shipyard is being upgraded') :
                                   ($ships_being_built ? __('The Shipyard is still busy') : false);
                            ?>
                        <span class="tooltip" title="<?php echo e(is_string($tooltip) ? $tooltip : ''); ?>">
                            <?php if($object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Defense): ?>
                                Build
                            <?php elseif(!empty($build_active->id)): ?>
                                In queue
                            <?php else: ?>
                                Improve
                            <?php endif; ?>
                        </span>
                    </button>
                </div>
                <!--
                <a class="build-it_premium" href="javascript:void(0);" data-title="" data-url="#TODO_page=premium&amp;openDetail=2" data-question="You need a Commander to be able to use the building queue. Would you like to learn more about the advantages of a Commander?">
                    <span class="tooltip tpd-hideOnClickOutside" title="">Hire Commander</span>
                </a>
                -->
            </div>
        </div>
    </div>

    <div class="description">
        <?php if($storage): ?>
            <div class="capacity">
                <span class="label"><?php echo app('translator')->get('Storage capacity:'); ?></span>
                <meter min="0" max="<?php echo e($max_storage); ?>" low="<?php echo e((int)($max_storage * 0.9)); ?>" high="<?php echo e($max_storage); ?>" optimum="0" value="<?php echo e($current_storage); ?>"></meter>
                <span class="description">
                        <span class="good"><?php echo e(number_format($current_storage, 0, ',', '.')); ?></span> / <?php echo e(number_format($max_storage, 0, ',', '.')); ?>

                    </span>
            </div>

            <div class="fill_capacity_info">
                <div class="arrow_description"></div>
                <div class="action">
                    <div class="description"><?php echo app('translator')->get('Gain resources to immediately refill your storage'); ?></div>
                    <a class="offers btn btn_confirm fright" href="<?php echo e(route('merchant.index')); ?>#animation=false&page=traderResources"><?php echo app('translator')->get('View offers'); ?></a>
                </div>
            </div>
        <?php endif; ?>
        <div class="txt_box">
            <button class="details tooltip js_hideTipOnMobile overlay" aria-label="<?php echo app('translator')->get('More details'); ?>" title="<?php echo app('translator')->get('More details'); ?>"
                    data-target="<?php echo e(route('techtree.ajax', ['tab' => 2, 'object_id' => $object->id])); ?>"
                    data-overlay-title="<?php echo e($title); ?>"> ?
            </button>
            <span class="text">
                <?php echo $description; ?>

            </span>
        </div>

    </div>

</div>
<script type="text/javascript">
    if (document.getElementById("build_amount") !== null) {
        document.getElementById("build_amount").focus();
    }
    var showLifeformBonusCapReached = false
    if (typeof IPI !== 'undefined') {
        IPI.refreshHighlights()
    }
</script>

<?php if($object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Building || $object_type == \OGame\GameObjects\Models\Enums\GameObjectType::Station): ?>)
    
    <?php echo $__env->make('ingame.shared.buildings.last-building-slot-warning', ['planet' => $planet], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
    
    <script type="text/javascript">
        var lastBuildingSlot = {
            "showWarning": false,
            "slotWarning": ""
        };
    </script>
<?php endif; ?>
<script>
    $(document).ready(function () {
        $("#number").focus();
        initOverlays();
    });

    $(".build-it_disabled:not(.isWorking)")
        .click(function () {
            errorBoxDecision('Error', 'You need a Commander to be able to use the building queue. Would you like to learn more about the advantages of a Commander?', 'yes', 'No', function () {
                window.location.href = '<?php echo e(route('premium.index', ['openDetail' => 2])); ?>'
            });
        });

    var loca = loca || {};
    loca = $.extend({},
        loca,
        {
            'allError': 'Error',
            'infoBuildlist': 'You need a Commander to be able to use the building queue. Would you like to learn more about the advantages of a Commander?',
            'allYes': 'yes',
            'allNo': 'No',
            'allOk': 'Ok',
            'noRocketsiloCapacity': 'Not enough capacity. Upgrade missile silo.',
            'allDetailNow': 'now'
        }
    );

    var buttonClass = "build-it";
    var overlayTitle = 'Start with DM';
    var showSlotWarning = 1;
    var buttonState = 1;
    var techID = 1;
    var isRocketAndStorageNotFree = 0;
    var couldBeBuild = 1;
    var isShip = 0;
    var isRocket = 0;
    var hasCommander = 0;
    var buildableAt = null;
    var error = 2000;
    var premiumerror = 0;
    var showErrorOnPremiumbutton = 0;

    var errorlist = {
        '2000': 'With a price of 0 DM the profit margin is too low for the merchant!',
        '100': 'The merchant can only deliver resources to an amount totalling 10.000.000 to you',
        '10': 'Not enough storage capacity. - Would you like to expand your storage?',
        '20': 'Not enough storage capacity. - Would you like to expand your storage?',
        '30': 'Not enough storage capacity. - Would you like to expand your storage?',
        '1000': 'Not enough Dark Matter available! Do you want to buy some now?'
    };

    var isBuildlistNeeded = 0;
    //var showCommanderHint = (!buttonState && !hasCommander && isBuildlistNeeded && couldBeBuild && (isShip || isRocket));
    var showNoPremiumError = 0;
    var pageToReload = "<?php echo e(route('resources.index')); ?>";
    var isBusy = 0;

</script><?php /**PATH /var/www/resources/views/ingame/ajax/object.blade.php ENDPATH**/ ?>