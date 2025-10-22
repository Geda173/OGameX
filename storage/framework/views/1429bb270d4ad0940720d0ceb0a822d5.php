<div id="technologytree" data-title="<?php echo app('translator')->get('Technology'); ?> - <?php echo e($object->title); ?>">
    <?php echo $__env->make('ingame.techtree.partials.nav', ['currentAction' => 'applications', 'objectId' => $object->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="content applications">
        <p class="hint"><?php echo e($object->title); ?> <?php echo app('translator')->get('is a requirement for'); ?>:</p>
        <ul class="applications">
            <?php /** @var OGame\GameObjects\Models\Techtree\TechtreeRequiredBy $required */ ?>
            <?php $__currentLoopData = $required_by; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $required): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li class="tooltipHTML" title="<?php echo e($required->gameObject->title); ?>|<?php echo e($required->gameObject->description); ?>" aria-label="<?php echo e($required->gameObject->title); ?>" data-prerequisites-met="<?php echo e($required->requirementsMet ? 'true' : 'false'); ?>">
                    <a href="<?php echo e(route('techtree.ajax', ['tab' => 1, 'object_id' => $required->gameObject->id])); ?>" class="sprite sprite_small <?php echo e($required->gameObject->class_name); ?> overlay" data-overlay-same="true">
                    </a>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
<?php /**PATH /var/www/resources/views/ingame/techtree/applications.blade.php ENDPATH**/ ?>