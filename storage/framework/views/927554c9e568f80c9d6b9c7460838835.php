<?php /** @var OGame\ViewModels\Queue\BuildingQueueListViewModel $build_queue */ ?>
<?php if(count($build_queue) > 0): ?>
    <table class="queue">
        <tbody>
        <tr>
            <?php $__currentLoopData = $build_queue; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <td>
                    <a href="javascript:void(0);" class="queue_link tooltip js_hideTipOnMobile dark_highlight_tablet"
                       onclick="cancelbuilding(<?php echo $item->object->id; ?>,<?php echo $item->id; ?>,&quot;Cancel production of <?php echo $item->object->title; ?> level <?php echo $item->level_target; ?>?&quot;); return false;"
                       title="">
                        <img class="queuePic"
                             src="<?php echo asset('img/objects/buildings/' . $item->object->assets->imgMicro); ?>" height="28"
                             width="28" alt="<?php echo $item->object->title; ?>">
                        <span><?php echo $item->level_target; ?></span>
                    </a>
                </td>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tr>
        </tbody>
    </table>
<?php endif; ?><?php /**PATH /var/www/resources/views/ingame/shared/buildqueue/building-queue.blade.php ENDPATH**/ ?>