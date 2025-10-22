<div id="technologytree" data-title="<?php echo app('translator')->get('Technology'); ?> - <?php echo e($object->title); ?>">
    <?php echo $__env->make('ingame.techtree.partials.nav', ['currentAction' => 'technologies', 'objectId' => $object->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="content technology">
        <p class="hint">
            <?php echo app('translator')->get('No requirements available'); ?>
        </p>
    </div>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
<?php /**PATH /var/www/resources/views/ingame/techtree/technology.blade.php ENDPATH**/ ?>