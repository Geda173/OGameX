<?php $__env->startSection('content'); ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <div id="resourcesettingscomponent">
    <div id="inhalt">
        <div id="planet" class="shortHeader">
            <h2><?php echo app('translator')->get('Resource settings'); ?> - <?php echo e($planet_name); ?></h2>
        </div>
        <div class="contentRS">
            <div class="headerRS"><a href="<?php echo e(route('resources.index')); ?>" class="close_details close_ressources"></a>
            </div>
            <div class="mainRS">
                <form method="POST" action="<?php echo e(route('resources.settingsUpdate')); ?>">
                    <?php echo e(csrf_field()); ?>

                    <input type="hidden" name="saveSettings" value="1">
                    <input type="hidden" name="token" value="1e31c04875d85148ce663b4eb30d328c">
                    <table cellpadding="0" cellspacing="0" class="list listOfResourceSettingsPerPlanet"
                           style="margin-top:0px;">
                        <tbody>
                        <tr>
                            <td colspan="7" id="factor">
                                <div class="secondcol">
                                    <div style="width:376px; margin: 0px auto;">
                                        <span class="factorkey"><?php echo app('translator')->get('Production factor'); ?>: <?php echo e($production_factor); ?>%</span>
                                        <span class="factorbutton">
                                        <input class="btn_blue" type="submit" value="Recalculate">
                                    </span>
                                        <br class="clearfloat">
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th colspan="2"></th>
                            <th><?php echo app('translator')->get('Metal'); ?></th>
                            <th><?php echo app('translator')->get('Crystal'); ?></th>
                            <th><?php echo app('translator')->get('Deuterium'); ?></th>
                            <th><?php echo app('translator')->get('Energy'); ?></th>
                            <th></th>
                        </tr>
                        <tr class="alt">
                            <td colspan="2" class="label"><?php echo app('translator')->get('Basic Income'); ?></td>
                            <td class="undermark textRight">
                                <span class="tooltipCustom"
                                      title="<?php echo e($basic_income->metal->get()); ?>"><?php echo e($basic_income->metal->getFormattedLong()); ?></span>
                            </td>
                            <td class="undermark textRight">
                                <span class="tooltipCustom"
                                      title="<?php echo e($basic_income->crystal->get()); ?>"><?php echo e($basic_income->crystal->getFormattedLong()); ?></span>
                            </td>
                            <td class="normalmark textRight">
                                <span class="tooltipCustom"
                                      title="<?php echo e($basic_income->deuterium->get()); ?>"><?php echo e($basic_income->deuterium->getFormattedLong()); ?></span>
                            </td>
                            <td class="normalmark textRight">
                                <span class="tooltipCustom"
                                      title="<?php echo e($basic_income->energy->get()); ?>"><?php echo e($basic_income->energy->getFormattedLong()); ?></span>
                            </td>
                            <td></td>
                        </tr>
                        <?php $__currentLoopData = $building_resource_rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="<?php echo e($loop->iteration % 2 == 0 ? 'alt' : ''); ?>">
                                <td class="label">
                                    <?php echo e($row['title']); ?> (<?php echo app('translator')->get('Level'); ?> <?php echo e($row['level']); ?>)
                                </td>
                                <td>
                                </td>
                                <td class="<?php echo e($row['production']->metal->get() > 0 ? 'undermark' : ($row['production']->metal->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom " title="<?php echo e($row['production']->metal->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->metal->getFormattedLong()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e($row['production']->crystal->get() > 0 ? 'undermark' : ($row['production']->crystal->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom " title="<?php echo e($row['production']->crystal->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->crystal->getFormattedLong()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e($row['production']->deuterium->get() > 0 ? 'undermark' : ($row['production']->deuterium->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom "
                                      title="<?php echo e($row['production']->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->deuterium->getFormattedLong()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e(($row['production']->energy->get() * -1) > 0 ? 'undermark' : (($row['production']->energy->get() * -1) < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom "
                                      title="<?php echo e(\OGame\Facades\AppUtil::formatNumberLong($row['actual_energy_use'] * -1)); ?>/<?php echo e(\OGame\Facades\AppUtil::formatNumberLong($row['production']->energy->get() * -1)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($row['actual_energy_use'] * -1)); ?>/<?php echo e(\OGame\Facades\AppUtil::formatNumberLong($row['production']->energy->get() * -1)); ?>

                                </span>
                                </td>
                                <td>
                                    <select name="last<?php echo e($row['id']); ?>" size="1" class="overmark">
                                        <option class="undermark"
                                                value="10" <?php echo e($row['percentage'] == 10 ? 'selected' : ''); ?>>100%
                                        </option>
                                        <option class="undermark"
                                                value="9" <?php echo e($row['percentage'] == 9 ? 'selected' : ''); ?>>90%
                                        </option>
                                        <option class="undermark"
                                                value="8" <?php echo e($row['percentage'] == 8 ? 'selected' : ''); ?>>80%
                                        </option>
                                        <option class="undermark"
                                                value="7" <?php echo e($row['percentage'] == 7 ? 'selected' : ''); ?>>70%
                                        </option>
                                        <option class="middlemark"
                                                value="6" <?php echo e($row['percentage'] == 6 ? 'selected' : ''); ?>>60%
                                        </option>
                                        <option class="middlemark"
                                                value="5" <?php echo e($row['percentage'] == 5 ? 'selected' : ''); ?>>50%
                                        </option>
                                        <option class="middlemark"
                                                value="4" <?php echo e($row['percentage'] == 4 ? 'selected' : ''); ?>>40%
                                        </option>
                                        <option class="overmark"
                                                value="3" <?php echo e($row['percentage'] == 3 ? 'selected' : ''); ?>>30%
                                        </option>
                                        <option class="overmark"
                                                value="2" <?php echo e($row['percentage'] == 2 ? 'selected' : ''); ?>>20%
                                        </option>
                                        <option class="overmark"
                                                value="1" <?php echo e($row['percentage'] == 1 ? 'selected' : ''); ?>>10%
                                        </option>
                                        <option class="overmark"
                                                value="0" <?php echo e($row['percentage'] == 0 ? 'selected' : ''); ?>="">0%</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php $__currentLoopData = $building_energy_rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $count => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="<?php echo e($loop->iteration % 2 == 0 ? '' : 'alt'); ?>">
                                <td class="label">
                                    <?php echo e($row['title']); ?>

                                    (<?php echo e(($row['type'] === \OGame\GameObjects\Models\Enums\GameObjectType::Ship || $row['type'] === \OGame\GameObjects\Models\Enums\GameObjectType::Defense) ? __('Number:') : __('Level')); ?> <?php echo e($row['level']); ?>)
                                </td>
                                <td>
                                </td>
                                <td class="<?php echo e($row['production']->metal->get() > 0 ? 'undermark' : ($row['production']->metal->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom " title="<?php echo e($row['production']->metal->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->metal->getFormatted()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e($row['production']->crystal->get() > 0 ? 'undermark' : ($row['production']->crystal->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom " title="<?php echo e($row['production']->crystal->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->crystal->getFormatted()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e($row['production']->deuterium->get() > 0 ? 'undermark' : ($row['production']->deuterium->get() < 0 ? 'overmark' : 'normalmark')); ?>">
                                <span class="tooltipCustom "
                                      title="<?php echo e($row['production']->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->deuterium->getFormattedLong()); ?>

                                </span>
                                </td>
                                <td class="<?php echo e(($row['production']->energy->get()) > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom " title=" <?php echo e($row['production']->energy->getFormattedFull()); ?>">
                                    <?php echo e($row['production']->energy->getFormattedLong()); ?>

                                </span>
                                </td>
                                <td>
                                    <select name="last<?php echo e($row['id']); ?>" size="1" class="overmark">
                                        <option class="undermark"
                                                value="10" <?php echo e($row['percentage'] == 10 ? 'selected' : ''); ?>>100%
                                        </option>
                                        <option class="undermark"
                                                value="9" <?php echo e($row['percentage'] == 9 ? 'selected' : ''); ?>>90%
                                        </option>
                                        <option class="undermark"
                                                value="8" <?php echo e($row['percentage'] == 8 ? 'selected' : ''); ?>>80%
                                        </option>
                                        <option class="undermark"
                                                value="7" <?php echo e($row['percentage'] == 7 ? 'selected' : ''); ?>>70%
                                        </option>
                                        <option class="middlemark"
                                                value="6" <?php echo e($row['percentage'] == 6 ? 'selected' : ''); ?>>60%
                                        </option>
                                        <option class="middlemark"
                                                value="5" <?php echo e($row['percentage'] == 5 ? 'selected' : ''); ?>>50%
                                        </option>
                                        <option class="middlemark"
                                                value="4" <?php echo e($row['percentage'] == 4 ? 'selected' : ''); ?>>40%
                                        </option>
                                        <option class="overmark"
                                                value="3" <?php echo e($row['percentage'] == 3 ? 'selected' : ''); ?>>30%
                                        </option>
                                        <option class="overmark"
                                                value="2" <?php echo e($row['percentage'] == 2 ? 'selected' : ''); ?>>20%
                                        </option>
                                        <option class="overmark"
                                                value="1" <?php echo e($row['percentage'] == 1 ? 'selected' : ''); ?>>10%
                                        </option>
                                        <option class="overmark"
                                                value="0" <?php echo e($row['percentage'] == 0 ? 'selected' : ''); ?>="">0%</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <tr class="">
                            <td class="label">
                                <?php echo app('translator')->get('Plasma Technology'); ?> (<?php echo app('translator')->get('Level'); ?> <?php echo e($plasma_technology_level); ?>)
                            </td>
                            <td>
                            </td>
                            <td class="<?php echo e($production_total->plasma_technology->metal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom " title="<?php echo e($production_total->plasma_technology->metal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->plasma_technology->metal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->plasma_technology->crystal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom " title="<?php echo e($production_total->plasma_technology->crystal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->plasma_technology->crystal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->plasma_technology->deuterium->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom " title="<?php echo e($production_total->plasma_technology->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($production_total->plasma_technology->deuterium->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->plasma_technology->energy->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom " title="<?php echo e($production_total->plasma_technology->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->plasma_technology->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="alt">
                            <td class="label">
                                <?php echo app('translator')->get('Items'); ?>
                            </td>
                            <td>
                            </td>
                            <td class="normalmark">
                                <span class="tooltipCustom " title="0">
                                    0
                                </span>
                            </td>
                            <td class="normalmark">
                                <span class="tooltipCustom " title="0">
                                    0
                                </span>
                            </td>
                            <td class="normalmark">
                                <span class="tooltipCustom " title="0">
                                    0
                                </span>
                            </td>
                            <td class="normalmark">
                                <span class="tooltipCustom " title="0">
                                    0
                                </span>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="">
                            <td class="label">
                                <?php echo app('translator')->get('Geologist'); ?>
                            </td>
                            <td>
                                <div class="tooltipCustom smallOfficer geologe <?php echo e($officers['geologist'] ? '' : 'grayscale'); ?>" title="+10% <?php echo app('translator')->get('mine production'); ?>">
                                    <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" width="25" height="25">
                                </div>
                            </td>
                            <td class="<?php echo e($production_total->geologist->metal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['geologist'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->geologist->metal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->geologist->metal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->geologist->crystal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['geologist'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->geologist->crystal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->geologist->crystal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->geologist->deuterium->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['geologist'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->geologist->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($production_total->geologist->deuterium->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->geologist->energy->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['geologist'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->geologist->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->geologist->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="alt">
                            <td class="label">
                                <?php echo app('translator')->get('Engineer'); ?>
                            </td>
                            <td>
                                <div class="tooltipCustom smallOfficer engineer <?php echo e($officers['engineer'] ? '' : 'grayscale'); ?>"
                                     title="+10% <?php echo app('translator')->get('energy production'); ?>">
                                    <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" width="25" height="25">
                                </div>
                            </td>
                            <td class="<?php echo e($production_total->engineer->metal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['engineer'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->engineer->metal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->engineer->metal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->engineer->crystal ->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['engineer'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->engineer->crystal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->engineer->crystal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->engineer->deuterium->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['engineer'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->engineer->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($production_total->engineer->deuterium->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->engineer->energy->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['engineer'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->engineer->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->engineer->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="">
                            <td class="label">
                                <?php echo app('translator')->get('Commanding Staff'); ?>
                            </td>
                            <td>
                                <div class="tooltipCustom smallOfficer stab <?php echo e($officers['commanding_staff'] ? '' : 'grayscale'); ?>"
                                     title="+2% mine production<br>+2% energy production">
                                    <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" width="25" height="25">
                                </div>
                            </td>
                            <td class="<?php echo e($production_total->commanding_staff->metal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['commanding_staff'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->commanding_staff->metal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->commanding_staff->metal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->commanding_staff->crystal->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['commanding_staff'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->commanding_staff->crystal->getFormattedFull()); ?>">
                                    <?php echo e($production_total->commanding_staff->crystal->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->commanding_staff->deuterium->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['commanding_staff'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->commanding_staff->deuterium->getFormattedFull()); ?>">
                                    <?php echo e($production_total->commanding_staff->deuterium->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->commanding_staff->energy->get() > 0 ? 'undermark' : 'normalmark'); ?>">
                                <span class="tooltipCustom <?php echo e($officers['commanding_staff'] ? '' : 'disabled'); ?>" title="<?php echo e($production_total->commanding_staff->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->commanding_staff->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td>
                            </td>
                        </tr>
                        <tr class="">
                            <td colspan="2" class="label"><?php echo app('translator')->get('Storage capacity'); ?></td>
                            <td class="<?php echo e($metal >= $metal_storage ? 'overmark' : 'normalmark'); ?> left2">
                            <span class="tooltipCustom" title="<?php echo e($metal_storage_formatted); ?>">
                                <?php echo e($metal_storage_formatted); ?>

                            </span>
                            </td>
                            <td class="<?php echo e($crystal >= $crystal_storage ? 'overmark' : 'normalmark'); ?> left2">
                            <span class="tooltipCustom" title="<?php echo e($crystal_storage_formatted); ?>">
                                <?php echo e($crystal_storage_formatted); ?>

                            </span>
                            </td>
                            <td class="<?php echo e($deuterium >= $deuterium_storage ? 'overmark' : 'normalmark'); ?> left2">
                            <span class="tooltipCustom" title="<?php echo e($deuterium_storage_formatted); ?>">
                                <?php echo e($deuterium_storage_formatted); ?>

                            </span>
                            </td>
                            <td>-</td>
                            <td></td>
                        </tr>
                        <tr class="summary alt">
                            <td colspan="2" class="label"><em><?php echo app('translator')->get('Total per hour:'); ?></em></td>
                            <td class="undermark">
                            <span class="tooltipCustom" title="<?php echo e($production_total->total->metal->getFormattedFull()); ?>">
                                <?php echo e($production_total->total->metal->getFormattedLong()); ?>

                            </span>
                            </td>
                            <td class="undermark">
                            <span class="tooltipCustom" title="<?php echo e($production_total->total->crystal->getFormattedFull()); ?>">
                                <?php echo e($production_total->total->crystal->getFormattedLong()); ?>

                            </span>
                            </td>
                            <td class="undermark">
                            <span class="tooltipCustom" title="<?php echo e($production_total->total->deuterium->getFormattedFull()); ?>">
                                <?php echo e($production_total->total->deuterium->getFormattedLong()); ?>

                            </span>
                            </td>
                            <td class="<?php echo e(($production_total->total->energy->getFormatted() > 0) ? 'undermark' : 'overmark'); ?>">
                            <span class="tooltipCustom" title="<?php echo e($production_total->total->energy->getFormattedFull()); ?>">
                                <?php echo e($production_total->total->energy->getFormattedLong()); ?>

                            </span>
                            </td>
                            <td></td>
                        </tr>
                        <tr class="">
                            <td colspan="2" class="label"><em><?php echo app('translator')->get('Total per day'); ?>:</em></td>
                            <td class="undermark">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->metal->get() * 24)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->metal->get() * 24)); ?>

                                </span>
                            </td>
                            <td class="undermark">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->crystal->get() * 24)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->crystal->get() * 24)); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->total->deuterium->get() > 0 ? 'undermark' : 'overmark'); ?>">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->deuterium->get() * 24)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->deuterium->get() * 24)); ?>

                                </span>
                            </td>
                            <td class="<?php echo e(($production_total->total->energy->get() > 0) ? 'undermark' : 'overmark'); ?>">
                                <span class="tooltipCustom" title="<?php echo e($production_total->total->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->total->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td></td>
                        </tr>
                        <tr class="alt">
                            <td colspan="2" class="label"><em><?php echo app('translator')->get('Total per week'); ?>:</em></td>
                            <td class="undermark">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->metal->get() * 168)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->metal->get() * 168)); ?>

                                </span>
                            </td>
                            <td class="undermark">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->crystal->get() * 168)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->crystal->get() * 168)); ?>

                                </span>
                            </td>
                            <td class="<?php echo e($production_total->total->deuterium->get() > 0 ? 'undermark' : 'overmark'); ?>">
                                <span class="tooltipCustom" title="<?php echo e(\OGame\Facades\AppUtil::formatNumber($production_total->total->deuterium->get() * 168)); ?>">
                                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($production_total->total->deuterium->get() * 168)); ?>

                                </span>
                            </td>
                            <td class="<?php echo e(($production_total->total->energy->getFormatted() > 0) ? 'undermark' : 'overmark'); ?>">
                                <span class="tooltipCustom" title="<?php echo e($production_total->total->energy->getFormattedFull()); ?>">
                                    <?php echo e($production_total->total->energy->getFormattedLong()); ?>

                                </span>
                            </td>
                            <td></td>
                        </tr>

                        </tbody>
                    </table>
                </form>
            </div>
            <div class="footerRS"></div>
        </div>
        <br class="clearfloat">
    </div>
    </div>

    <script type="text/javascript">
        function initResourceSettings() {
            $('.mainRS tr:gt(0)').hover(function () {
                $(this).addClass('hover');
            }, function () {
                $(this).removeClass('hover');
            });
        }

        $(function () {
            initResourceSettings();
        });
    </script>
    <div id="eventboxContent">

        <div id="eventListWrap">
            <div id="eventHeader">
                <a class="close_details eventToggle" href="javascript:void(0);">
                    <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="16" width="16">
                </a>
                <h2>Events</h2>
            </div>
            <table id="eventContent">
                <tbody>
                </tbody>
            </table>
            <div id="eventFooter"></div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('ingame.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ingame/resources/settings.blade.php ENDPATH**/ ?>