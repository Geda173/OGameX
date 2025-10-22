<?php echo e($property_name); ?>|
<table class=&quot;combat_unit_details_tooltip&quot;>
    <tr>
        <th>Basic value:</th>
        <td><?php echo e(\OGame\Facades\AppUtil::formatNumber($property_breakdown['rawValue'])); ?></td>
    </tr>
    <?php $__currentLoopData = $property_breakdown['bonuses']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property_bonus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <th>
                <?php echo e($property_bonus['type']); ?>:
                <span class=&quot;formula&quot;>(<?php echo e($property_bonus['percentage']); ?>%)</span>
            </th>
            <td><?php echo e(\OGame\Facades\AppUtil::formatNumber($property_bonus['value'])); ?></td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <tr>
        <td colspan=&quot;2&quot; class=&quot;sum&quot;><?php echo e(\OGame\Facades\AppUtil::formatNumber($property_value)); ?></td>
    </tr>
</table>
<?php /**PATH /var/www/resources/views/ingame/techtree/info/property_tooltip.blade.php ENDPATH**/ ?>