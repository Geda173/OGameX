<?php $__env->startSection('content'); ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div id="shipyardcomponent" class="maincontent">
        <div id="shipyard">
            <header id="planet" data-anchor="technologyDetails">
                <h2><?php echo app('translator')->get('Shipyard'); ?> - <?php echo e($planet_name); ?></h2>
            </header>
            <div id="technologydetails_wrapper">
                <div id="technologydetails_content"></div>
            </div>

            <div id="technologies">
                <div id="technologies_battle">
                    <h3><?php echo app('translator')->get('Battleships'); ?></h3>
                    <ul class="icons">
                        <?php /** @var OGame\ViewModels\BuildingViewModel $building */ ?>
                        <?php $__currentLoopData = $units[0]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('ingame.shipyard.unit-item', ['building' => $building, 'shipyard_upgrading' => $shipyard_upgrading], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <div id="technologies_civil">
                    <h3>Civil ships</h3>
                    <ul class="icons">
                        <?php /** @var OGame\ViewModels\BuildingViewModel $building */ ?>
                        <?php $__currentLoopData = $units[1]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo $__env->make('ingame.shipyard.unit-item', ['building' => $building, 'shipyard_upgrading' => $shipyard_upgrading], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            </div>
        </div>
        <div id="productionboxBottom">
            <div class="productionBoxShips boxColumn ship">

                <div id="productionboxshipyardcomponent" class="productionboxshipyard injectedComponent parent shipyard"><div class="content-box-s">
                        <div class="header">
                            <h3><?php echo app('translator')->get('Shipyard'); ?></h3>
                        </div>
                        <div class="content">
                            
                            <?php echo $__env->make('ingame.shared.buildqueue.unit-active', ['build_active' => $build_active], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                            
                            <?php echo $__env->make('ingame.shared.buildqueue.unit-queue', ['build_queue' => $build_queue], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>
                        <div class="footer"></div>
                    </div>
                    <script type="text/javascript">
                        var scheduleBuildListEntryUrl = '<?php echo e(route('shipyard.addbuildrequest')); ?>';
                        var LOCA_ERROR_INQUIRY_NOT_WORKED_TRYAGAIN = 'Your last action could not be processed. Please try again.';
                        redirectPremiumLink = '#TODO_index.php?page=premium&showDarkMatter=1'
                    </script>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            var planetMoveInProgress = false;
        </script>
    </div>

    <div id="technologydetailscomponent" class="technologydetails injectedComponent parent shipyard">
        <script type="text/javascript">
            var loca = {"LOCA_ALL_NOTICE":"Reference","LOCA_ALL_NETWORK_ATTENTION":"Caution","locaDemolishStructureQuestion":"Really downgrade TECHNOLOGY_NAME by one level?","LOCA_ALL_YES":"yes","LOCA_ALL_NO":"No","LOCA_LIFEFORM_BONUS_CAP_REACHED_WARNING":"One or more associated bonuses is already maxed out. Do you want to continue construction anyway?"};

            var technologyDetailsEndpoint = "<?php echo e(route('shipyard.ajax')); ?>";
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

<?php echo $__env->make('ingame.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ingame/shipyard/index.blade.php ENDPATH**/ ?>