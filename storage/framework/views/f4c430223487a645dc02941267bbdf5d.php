<?php
    use OGame\GameObjects\Models\Abstracts\GameObject;
    use OGame\GameObjects\Models\Techtree\TechtreeRequirement;
    /** @var GameObject $object */
    /** @var array<TechtreeRequirement> $techtree */
    /** @var array<TechtreeRequirement> $techtree_unique */
    /** @var array<int, array<int, TechtreeRequirement>> $techtree_by_depth */
?>

<div id="technologytree" data-title="<?php echo app('translator')->get('Technology'); ?> - <?php echo e($object->title); ?>">
    <?php echo $__env->make('ingame.techtree.partials.nav', ['currentAction' => 'technologytree', 'objectId' => $object->id], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="content technologytree">
        <?php if($object->hasRequirements()): ?>
        <div class="graph columns_<?php echo e($amount_of_columns); ?>" data-id="67d6cebc93399">
            <?php $__currentLoopData = $techtree_by_depth; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $depth => $depth_items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="techWrapper depth<?php echo e($depth); ?> clearfix">
                    <?php for($column = 0; $column < $amount_of_columns; $column++): ?>
                        <?php if(isset($depth_items[$column])): ?>
                            <?php echo $__env->make('ingame.techtree.partials.techtree_node', ['object' => $depth_items[$column]->gameObject, 'required_level' => $depth_items[$column]->levelRequired, 'current_level' => $depth_items[$column]->levelCurrent], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php else: ?>
                            <div class="techtreeNode empty">&nbsp;</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php else: ?>
            <p class="hint">
                <?php echo app('translator')->get('No requirements available'); ?>
            </p>
        <?php endif; ?>
    </div>
    <script>
        var endpoints = [
            
            <?php $__currentLoopData = $techtree_unique; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requirement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                "t<?php echo e($requirement->gameObject->id); ?>l<?php echo e($requirement->levelRequired); ?>",
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ];
        var connections = [
            <?php $__currentLoopData = $techtree; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requirement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($requirement->parent !== null && $requirement->levelCurrent >= $requirement->levelRequired): ?>
                    {"source":"t<?php echo e($requirement->gameObject->id); ?>l<?php echo e($requirement->levelRequired); ?>","target":"t<?php echo e($requirement->parent->gameObject->id); ?>l<?php echo e($requirement->parent->levelRequired); ?>","label":"<?php echo e($requirement->levelRequired); ?>","paintStyle":"hasRequirements"},
                <?php elseif($requirement->parent !== null): ?>
                    {"source":"t<?php echo e($requirement->gameObject->id); ?>l<?php echo e($requirement->levelRequired); ?>","target":"t<?php echo e($requirement->parent->gameObject->id); ?>l<?php echo e($requirement->parent->levelRequired); ?>","label":"<?php echo e($requirement->levelCurrent); ?>/<?php echo e($requirement->levelRequired); ?>","paintStyle":"hasNotRequirements"},
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        ];
        (function($){
          initTechtree("67d6cebc93399")
        })(jQuery);
    </script>
</div>

<script type="text/javascript">
    $(
        function(){
            initOverlayName();
        }
    );
</script>
<?php /**PATH /var/www/resources/views/ingame/techtree/techtree.blade.php ENDPATH**/ ?>