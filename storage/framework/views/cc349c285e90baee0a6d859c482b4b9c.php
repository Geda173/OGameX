<?php /** @var array<\OGame\ViewModels\Queue\UnitQueueViewModel> $build_queue */ ?>
<?php if(count($build_queue) > 0): ?>
    <table class="queue">
        <tbody>
        <tr>
            <?php $__currentLoopData = $build_queue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <td>
                        <img class="queuePic"
                             src="<?php echo asset('img/objects/units/' . $item->object->assets->imgSmall); ?>" height="28"
                             width="28" alt="<?php echo $item->object->title; ?>">
                    <br />
                    <?php echo $item->object_amount; ?>

                </td>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tr>
        </tbody>
    </table>
<?php endif; ?><?php /**PATH /var/www/resources/views/ingame/shared/buildqueue/unit-queue.blade.php ENDPATH**/ ?>