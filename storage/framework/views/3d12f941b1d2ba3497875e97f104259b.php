<?php if(!empty($object->storage)): ?>
    <!-- Storage -->
    <table class="general_details">
        <thead>
        <tr>
            <th>Level</th>
            <th>Storage cap.</th>
            <th>Difference</th>
            <th>Difference/level</th>
            <th>Protected (Percent)</th>
        </tr>
        </thead>
        <tbody>
        <?php $__currentLoopData = $storage_table; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr class="<?php if($record['level'] === $current_level): ?>
                                    current
                                    <?php endif; ?>">
                <td class="level" data-value="<?php echo e($record['level']); ?>"><?php echo e($record['level']); ?></td>
                <td class="capacity" data-value="<?php echo e($record['storage']); ?>">
                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($record['storage'])); ?>

                </td>
                <td class="capacity_difference" data-value="<?php echo e($record['storage_difference']); ?>">
                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($record['storage_difference'])); ?>

                </td>
                <td class="capacity_level_difference" data-value="<?php echo e($record['storage_difference_per_level']); ?>">
                    <?php echo e(\OGame\Facades\AppUtil::formatNumberLong($record['storage_difference_per_level'])); ?>

                </td>
                <td class="protection" data-value="<?php echo e($record['protected']); ?>">
                    <?php echo e($record['protected']); ?>

                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/ingame/techtree/info/storage.blade.php ENDPATH**/ ?>