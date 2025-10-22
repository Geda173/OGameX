<?php $__env->startSection('content'); ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div id="facilitiescomponent" class="maincontent">
        <div id="facilities">
            <div class="c-left"></div>
            <div class="c-right"></div>
            <header data-anchor="technologyDetails" data-technologydetails-size="large" style="background-image:url(<?php echo e(asset('img/headers/facilities/' . $header_filename)); ?>.jpg);">
                <h2>Facilities - <?php echo e($planet_name); ?></h2>
            </header>
            <div id="technologydetails_wrapper">
                <div id="technologydetails_content"></div>
            </div>
            <div id="technologies">
                <h3>
                    <?php echo app('translator')->get('Facility buildings'); ?>
                </h3>
                <ul class="icons">
                    <?php /** @var OGame\ViewModels\BuildingViewModel $building */ ?>
                    <?php $__currentLoopData = $buildings[0]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="technology <?php echo e($building->object->class_name); ?> hasDetails tooltip hideTooltipOnMouseenter js_hideTipOnMobile ipiHintable tpd-hideOnClickOutside"
                            data-technology="<?php echo e($building->object->id); ?>"
                            data-is-spaceprovider=""
                            aria-label="<?php echo e($building->object->title); ?>"
                            data-ipi-hint="ipiTechnology<?php echo e($building->object->class_name); ?>"
                            <?php if($building->currently_building): ?>
                                data-status="active"
                                data-is-spaceprovider=""
                                data-progress="26"
                                data-start="1713521207"
                                data-end="1713604880"
                                data-total="61608"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Under construction'); ?>"
                            <?php elseif(!$building->requirements_met): ?>
                                data-status="off"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Requirements are not met!'); ?>"
                            <?php elseif(!$building->valid_planet_type): ?>
                                data-status="disabled"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('You can\'t construct that building on a moon!'); ?>"
                            <?php elseif($building->ship_or_defense_in_progress && $building->object->machine_name == 'shipyard'): ?>
                                data-status="disabled"
                            title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('The shipyard is still busy'); ?>"
                            <?php elseif($building->research_in_progress && $building->object->machine_name == 'research_lab'): ?>
                                data-status="disabled"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Research is currently being carried out!'); ?>"
                            <?php elseif(!$building->enough_resources): ?>
                                data-status="disabled"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Not enough resources!'); ?>"
                            <?php elseif($build_queue_max): ?>
                                data-status="disabled"
                                title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Queue is full'); ?>"
                            <?php else: ?>
                                data-status="on"
                                title="<?php echo e($building->object->title); ?>"
                                <?php endif; ?>
                        >

                        <span class="icon sprite sprite_medium medium <?php echo e($building->object->class_name); ?>">
                            <?php if($building->currently_building): ?>
                            <?php elseif(!$building->requirements_met): ?>
                            <?php elseif(!$building->valid_planet_type): ?>
                            <?php elseif(!$building->enough_resources): ?>
                            <?php elseif($build_queue_max): ?>
                            <?php elseif($building->research_in_progress && $building->object->machine_name == 'research_lab'): ?>
                            <?php elseif($building->ship_or_defense_in_progress  && $building->object->machine_name == 'shipyard'): ?>
                            <?php else: ?>
                                <button
                                        class="upgrade tooltip hideOthers js_hideTipOnMobile"
                                        aria-label="Expand <?php echo $building->object->title; ?> on level <?php echo ($building->current_level + 1); ?>" title="Expand <?php echo $building->object->title; ?> on level <?php echo ($building->current_level + 1); ?>"
                                        data-technology="<?php echo e($building->object->id); ?>" data-is-spaceprovider="">
                                </button>
                            <?php endif; ?>
                            <?php if($building->currently_building): ?>
                                <span class="targetlevel" data-value="<?php echo e($building->current_level + 1); ?>" data-bonus="0"><?php echo e($building->current_level + 1); ?></span>
                                <div class="cooldownBackground"></div>
                                <time-counter><time class="countdown buildingCountdown" id="countdownbuildingDetails" data-segments="2">...</time></time-counter>
                            <?php endif; ?>
                            <span class="level" data-value="<?php echo e($building->current_level); ?>" data-bonus="0">
                            <span class="stockAmount"><?php echo e($building->current_level); ?></span>
                            <span class="bonus"></span>
                            </span>
                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
        <div id="productionboxBottom">
            <div class="productionBoxBuildings boxColumn building">
                <div id="productionboxbuildingcomponent" class="productionboxbuilding injectedComponent parent facilities"><div class="content-box-s">
                        <div class="header">
                            <h3><?php echo app('translator')->get('Buildings'); ?></h3>
                        </div>
                        <div class="content">
                            
                            <?php echo $__env->make('ingame.shared.buildqueue.building-active', ['build_active' => $build_active], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            
                            <?php echo $__env->make('ingame.shared.buildqueue.building-queue', ['build_queue' => $build_queue], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="footer"></div>
                    </div>
                    <script type="text/javascript">
                        var scheduleBuildListEntryUrl = '<?php echo e(route('resources.addbuildrequest.post')); ?>';
                        var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                        redirectPremiumLink = '#TODO_index.php?page=premium&showDarkMatter=1'
                    </script>
                </div>
            </div>
            <div class="productionBoxShips boxColumn ship">
            </div>
        </div>
        <script type="text/javascript">
            var planetMoveInProgress = false;
        </script>
        
        <?php echo $__env->make('ingame.shared.buildings.last-building-slot-warning', ['planet' => $planet], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>

    <div id="technologydetailscomponent" class="technologydetails injectedComponent parent facilities">
        <script type="text/javascript">
            var loca = {"LOCA_ALL_NOTICE":"Reference","LOCA_ALL_NETWORK_ATTENTION":"Caution","locaDemolishStructureQuestion":"Really downgrade TECHNOLOGY_NAME by one level?","LOCA_ALL_YES":"yes","LOCA_ALL_NO":"No","LOCA_LIFEFORM_BONUS_CAP_REACHED_WARNING":"One or more associated bonuses is already maxed out. Do you want to continue construction anyway?"};

            var technologyDetailsEndpoint = "<?php echo e(route('facilities.ajax')); ?>";
            var selectCharacterClassEndpoint = "#TODO_page=ingame&component=characterclassselection&characterClassId=CHARACTERCLASSID&action=selectClass&ajax=1&asJson=1";
            var deselectCharacterClassEndpoint = "#TODO_page=ingame&component=characterclassselection&characterClassId=CHARACTERCLASSID&action=deselectClass&ajax=1&asJson=1";

            var technologyDetails = new TechnologyDetails({
                technologyDetailsEndpoint: technologyDetailsEndpoint,
                selectCharacterClassEndpoint: selectCharacterClassEndpoint,
                deselectCharacterClassEndpoint: deselectCharacterClassEndpoint,
                loca: loca
            })
            technologyDetails.init()
        </script>
    </div>
    
    <?php echo $__env->make('ingame.shared.technology.open-tech', ['open_tech_id' => $open_tech_id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('ingame.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ingame/facilities/index.blade.php ENDPATH**/ ?>