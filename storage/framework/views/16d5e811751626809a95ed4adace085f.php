<div id="technologytree" data-title="<?php echo app('translator')->get('Technology'); ?> - <?php echo e($object->title); ?>">
    <?php echo $__env->make('ingame.techtree.partials.nav', ['currentAction' => 'technologyinformation', 'objectId' => $object->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="content technologyinformation sprite_before sprite_large <?php echo e($object->class_name); ?>">
        <div class="information">
            <p><?php echo nl2br($object->description_long); ?></p>
            <?php echo $production_table; ?>

            <?php echo $storage_table; ?>

            <?php echo $rapidfire_table; ?>

            <?php echo $properties_table; ?>

            <?php echo $plasma_table; ?>

            <?php echo $astrophysics_table; ?>

        </div>
    </div>
    <script>
    </script>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
<?php /**PATH /var/www/resources/views/ingame/techtree/techinfo.blade.php ENDPATH**/ ?>