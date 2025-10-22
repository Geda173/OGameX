<?php if(!empty($object->production)): ?>
    <?php if($object->id == 4): ?>
        <!--  Basic energy production -->
        <table class="general_details">
            <thead>
            <tr>
                <th>
                    Level
                </th>
                <th>
                    Energy Balance
                </th>
                <th>
                    Difference
                </th>
                <th>
                    Difference/Level
                </th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $production_table; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php if($record['level'] === $current_level): ?>
                                        current
                                        <?php endif; ?>">
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e($record['level']); ?>                </span>
                    </td>
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e(number_format($record['production'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference_per_level'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference_per_level'], 0, ',', '.')); ?>                </span>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    <?php elseif($object->id === 12): ?>
        <!--  Resource production -->
        <table class="general_details">
            <thead>
                <tr>
                    <th>
                        Level
                    </th>
                    <th>
                        Energy Balance
                    </th>
                    <th>
                        Difference
                    </th>
                    <th>
                        Difference/Level
                    </th>
                    <th>
                        Deuterium consumption
                    </th>
                    <th>
                        Difference
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $production_table; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php if($record['level'] === $current_level): ?>
                                            current
                                            <?php endif; ?>">
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e($record['level']); ?>                </span>
                    </td>
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e(number_format($record['production'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference_per_level'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference_per_level'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="overmark"
                                  style="white-space: nowrap">
                                <?php echo e(number_format($record['deuterium_consumption'], 0, ',', '.')); ?>                 </span>
                    </td>
                    <td>
                            <span class="<?php if($record['deuterium_consumption_per_level'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['deuterium_consumption_per_level'], 0, ',', '.')); ?>                </span>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    <?php else: ?>
        <!--  Resource production -->
        <table class="general_details">
            <thead>
                <tr>
                    <th>
                        Level
                    </th>
                    <th>
                        Production/h
                    </th>
                    <th>
                        Difference
                    </th>
                    <th>
                        Difference/Level
                    </th>
                    <th>
                        Energy Balance:
                    </th>
                    <th>
                        Difference
                    </th>
                    <th>
                        Protected
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $production_table; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php if($record['level'] === $current_level): ?>
                                        current
                                        <?php endif; ?>">
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e($record['level']); ?>                </span>
                    </td>
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <?php echo e(number_format($record['production'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="<?php if($record['production_difference_per_level'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['production_difference_per_level'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="overmark"
                                  style="white-space: nowrap">
                                <?php echo e(number_format($record['energy_balance'], 0, ',', '.')); ?>                 </span>
                    </td>
                    <td>
                            <span class="<?php if($record['energy_difference'] >= 0): ?>
                                    undermark
                                    <?php else: ?>
                                    overmark
                                    <?php endif; ?>" style="white-space: nowrap">
                                <?php echo e(number_format($record['energy_difference'], 0, ',', '.')); ?>                </span>
                    </td>
                    <td>
                            <span class="undermark"
                                  style="white-space: nowrap">
                                <!-- TODO: implement den capacity -->
                                <?php echo e(number_format($record['protected'], 0, ',', '.')); ?>                </span>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/ingame/techtree/info/production.blade.php ENDPATH**/ ?>