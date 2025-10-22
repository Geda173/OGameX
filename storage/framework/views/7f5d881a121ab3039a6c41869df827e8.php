<?php
    use OGame\GameObjects\Models\Enums\GameObjectType;
    /** @var OGame\GameObjects\Models\Abstracts\GameObject $object */
?>


<div class="techtreeNode">
    <div class="techImage js_hideTipOnMobile tooltipHTML tech<?php echo e($object->id); ?> techt<?php echo e($object->id); ?>l<?php echo e($required_level); ?> <?php echo e($current_level >= $required_level ? 'built' : 'notBuilt'); ?>" title="<?php echo e($object->title); ?> <?php echo app('translator')->get('Level'); ?> (<?php echo e($required_level); ?>)|<?php echo e($object->description); ?>">
        <a href="<?php echo e(route('techtree.ajax', ['tab' => 1, 'object_id' => $object->id])); ?>"
           class="sprite sprite_small small overlay <?php echo e($object->class_name); ?>"
           data-overlay-same="true"
           data-tech-id="<?php echo e($object->id); ?>"
           data-tech-name="<?php echo e($object->title); ?>"
           data-tech-type="<?php echo e($object->type === GameObjectType::Research ? 'Type Research' : 'Type Buildings'); ?>">
           </a>
    </div>
</div><?php /**PATH /var/www/resources/views/ingame/techtree/partials/techtree_node.blade.php ENDPATH**/ ?>