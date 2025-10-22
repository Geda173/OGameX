<?php $__env->startSection('content'); ?>

    <?php /** @var \OGame\Services\PlanetService $currentPlanet */ ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div id="resourcesettingscomponent" class="maincontent">
        <div id="planet" class="shortHeader">
            <h2><?php echo app('translator')->get('Developer shortcuts'); ?></h2>
        </div>

        <div id="buttonz">
            <div class="header">
                <h2><?php echo app('translator')->get('Developer shortcuts'); ?></h2>
            </div>
            <div class="content">
                <div class="buddylistContent">
                    <form action="<?php echo e(route('admin.developershortcuts.update')); ?>" name="form" method="post">
                        <?php echo e(csrf_field()); ?>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Update current planet:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <input type="submit" class="btn_blue" name="set_mines" value="<?php echo app('translator')->get('Set all mines to level 30'); ?>">
                                        <input type="submit" class="btn_blue" name="set_storages" value="<?php echo app('translator')->get('Set all storages to level 15'); ?>">
                                        <input type="submit" class="btn_blue" name="set_shipyard" value="<?php echo app('translator')->get('Set all shipyard facilities to level 12'); ?>">
                                        <input type="submit" class="btn_blue" name="set_research" value="<?php echo app('translator')->get('Set all research to level 10'); ?>">
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Add X of unit to current planet:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Amount of units to add:'); ?></label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9,.kmb]+$" class="textInput w50 textCenter textBeefy" placeholder="1" size="2" name="amount_of_units">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <?php /** @var OGame\GameObjects\Models\UnitObject $unit */ ?>
                                        <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input type="submit" name="unit_<?php echo e($unit->id); ?>" class="btn_blue" value="<?php echo e($unit->title); ?>">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <input type="submit" class="btn_blue" value="<?php echo app('translator')->get('Light fighter'); ?>">
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Set building level on current planet:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Level to set:'); ?></label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9]+$" placeholder="0" class="textInput w50 textCenter textBeefy" size="2" name="building_level">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <?php $__currentLoopData = $buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input type="submit" name="building_<?php echo e($building->id); ?>" class="btn_blue" value="<?php echo e($building->title); ?>">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Set research level for current player:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Level to set:'); ?></label>
                                        <div class="thefield">
                                            <input type="text" pattern="^[0-9]+$" placeholder="0" class="textInput w50 textCenter textBeefy" size="2" name="research_level">
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <?php $__currentLoopData = $research; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tech): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input type="submit" name="research_<?php echo e($tech->id); ?>" class="btn_blue" value="<?php echo e($tech->title); ?>">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Reset planet'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <input type="submit" class="btn_blue" name="reset_buildings" value="<?php echo app('translator')->get('Set all buildings to level 0'); ?>">
                                        <input type="submit" class="btn_blue" name="reset_research" value="<?php echo app('translator')->get('Set all research to level 0'); ?>">
                                        <input type="submit" class="btn_blue" name="reset_units" value="<?php echo app('translator')->get('Remove all units'); ?>">
                                        <input type="submit" class="btn_blue" name="reset_resources" value="<?php echo app('translator')->get('Set all resources to 0'); ?>">
                                    </div>
                                </div>
                            </form>

                            <form action="<?php echo e(route('admin.developershortcuts.update-resources')); ?>" name="form" method="post">
                                <?php echo e(csrf_field()); ?>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Add / subtract resources at coordinates:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <div class="smallFont"><?php echo app('translator')->get('You can enter positive or negative values to add or subtract to the selected resource. Supports k/m/b suffixes (e.g., 1k, 2m, 3b)'); ?></div>
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Coordinates:'); ?></label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy"><?php echo app('translator')->get('Galaxy:'); ?></label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->galaxy); ?>" min="1" max="6" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system"><?php echo app('translator')->get('System:'); ?></label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->system); ?>" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position"><?php echo app('translator')->get('Position:'); ?></label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->position); ?>" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper"><label class="styled textBeefy"><?php echo app('translator')->get('Resources to add/subtract:'); ?></label>
                                        <div class="thefield" style="display: flex; flex-direction: column; gap: 10px;">
                                            <?php $__currentLoopData = \OGame\Models\Enums\ResourceType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div style="display: flex; gap: 10px;">
                                                    <label for="<?php echo e($resource->value); ?>" style="min-width: 80px;"><?php echo e($resource->name); ?>:</label>
                                                    <input type="text" id="<?php echo e($resource->value); ?>" pattern="^[-+0-9,.kmb]+$"
                                                           class="textInput w100 textCenter textBeefy"
                                                           placeholder="0" name="<?php echo e($resource->value); ?>">
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center;">
                                        <input type="submit" class="btn_blue" name="update_resources_planet" value="Update Resources (planet)">
                                        <input type="submit" class="btn_blue" name="update_resources_moon" value="Update Resources (moon)">
                                    </div>
                                </div>
                            </form>

                            <form action="<?php echo e(route('admin.developershortcuts.create-at-coords')); ?>" name="form" method="post">
                                <?php echo e(csrf_field()); ?>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Create planet/moon at coordinates:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Coordinates:'); ?></label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy"><?php echo app('translator')->get('Galaxy:'); ?></label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->galaxy); ?>" min="1" max="6" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system"><?php echo app('translator')->get('System:'); ?></label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->system); ?>" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position"><?php echo app('translator')->get('Position:'); ?></label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->position); ?>" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center; margin-bottom: 20px;">
                                        <input type="submit" class="btn_blue" name="create_planet" value="<?php echo app('translator')->get('Create Planet'); ?>">
                                        <input type="submit" class="btn_blue" name="create_moon" value="<?php echo app('translator')->get('Create Moon'); ?>">
                                        <input type="submit" class="btn_blue" name="delete_planet" value="<?php echo app('translator')->get('Delete Planet'); ?>">
                                        <input type="submit" class="btn_blue" name="delete_moon" value="<?php echo app('translator')->get('Delete Moon'); ?>">
                                    </div>
                                </div>
                            </form>

                            <form action="<?php echo e(route('admin.developershortcuts.create-debris')); ?>" name="form" method="post">
                                <?php echo e(csrf_field()); ?>

                                <p class="box_highlight textCenter no_buddies"><?php echo app('translator')->get('Create/delete debris field at coordinates:'); ?></p>
                                <div class="group bborder" style="display: block;">
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Coordinates:'); ?></label>
                                        <div class="thefield" style="display: flex; gap: 10px;">
                                            <div>
                                                <label for="galaxy"><?php echo app('translator')->get('Galaxy:'); ?></label>
                                                <input type="text" id="galaxy" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->galaxy); ?>" min="1" max="6" name="galaxy">
                                            </div>
                                            <div>
                                                <label for="system"><?php echo app('translator')->get('System:'); ?></label>
                                                <input type="text" id="system" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->system); ?>" min="1" max="499" name="system">
                                            </div>
                                            <div>
                                                <label for="position"><?php echo app('translator')->get('Position:'); ?></label>
                                                <input type="text" id="position" pattern="^[-+0-9,.kmb]+$" class="textInput w50 textCenter textBeefy"
                                                       value="<?php echo e($currentPlanet->getPlanetCoordinates()->position); ?>" min="1" max="15" name="position">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper">
                                        <label class="styled textBeefy"><?php echo app('translator')->get('Resources to add:'); ?></label>
                                        <div class="thefield" style="display: flex; flex-direction: column; gap: 10px;">
                                            <?php $__currentLoopData = \OGame\Models\Enums\ResourceType::cases(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div style="display: flex; gap: 10px;">
                                                    <label for="<?php echo e($resource->value); ?>" style="min-width: 80px;"><?php echo e($resource->name); ?>:</label>
                                                    <input type="text" id="<?php echo e($resource->value); ?>" pattern="^[-+0-9,.kmb]+$"
                                                           class="textInput w100 textCenter textBeefy"
                                                           placeholder="0" name="<?php echo e($resource->value); ?>">
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                    <div class="fieldwrapper" style="text-align: center; margin-bottom: 50px;">
                                        <input type="submit" class="btn_blue" name="create_debris" value="<?php echo app('translator')->get('Create/Append Debris Field'); ?>">
                                        <input type="submit" class="btn_blue" name="delete_debris" value="<?php echo app('translator')->get('Delete Debris Field'); ?>">
                                    </div>
                                </div>
                            </form>
                        <div class="footer">
                        </div>
                </div>
            </div>
            </div>

        <script language="javascript">
            initBBCodes();
            initOverlays();
        </script>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('ingame.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ingame/admin/developershortcuts.blade.php ENDPATH**/ ?>