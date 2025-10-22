<!-- Plasma -->
<table class="general_details">
    <thead>
    <tr>
        <th>Level</th>
        <th>Maximum colonies</th>
        <th>Maximum expeditions</th>
    </tr>
    </thead>
    <tbody>
    <?php $__currentLoopData = $astrophysics_table; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr class="<?php if($record['level'] === $current_level): ?>
                                current
                                <?php endif; ?>">
            <td class="level" data-value="<?php echo e($record['level']); ?>"><?php echo e($record['level']); ?></td>
            <td class="max_colonies" data-value="<?php echo e($record['max_colonies']); ?>">
                <?php echo e($record['max_colonies']); ?>

            </td>
            <td class="max_expedition" data-value="<?php echo e($record['max_expedition_slots']); ?>">
                <?php echo e($record['max_expedition_slots']); ?>

            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody>
</table>

<ul class="additional_information">
    <li>Positions 3 and 13 can be populated from level 4 onwards.</li>
    <li>Positions 2 and 14 can be populated from level 6 onwards.</li>
    <li>Positions 1 and 15 can be populated from level 8 onwards.</li>
</ul>
<?php /**PATH /var/www/resources/views/ingame/techtree/info/astrophysics.blade.php ENDPATH**/ ?>