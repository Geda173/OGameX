<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['building', 'build_queue_max']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['building', 'build_queue_max']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php /** @var OGame\ViewModels\ResearchViewModel $building */ ?>
<li class="technology <?php echo e($building->object->class_name); ?> hasDetails tooltip hideTooltipOnMouseenter js_hideTipOnMobile ipiHintable tpd-hideOnClickOutside"
    data-technology="<?php echo e($building->object->id); ?>"
    data-is-spaceprovider=""
    aria-label="<?php echo e($building->object->title); ?>"
    data-ipi-hint="ipiTechnology<?php echo e($building->object->class_name); ?>"
    <?php if($building->currently_building): ?>
        data-status="active"
        data-is-spaceprovider=""
        data-progress="26"
        data-start="1713521207"
        data-end="1713604880"
        data-total="61608"
        title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Under construction'); ?>"
    <?php elseif(!$building->requirements_met): ?>
        data-status="off"
        title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Requirements are not met!'); ?>"
    <?php elseif($building->research_lab_upgrading): ?>
        data-status="disabled"
        title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Research Lab is being expanded.'); ?>"
    <?php elseif(!$building->enough_resources): ?>
        data-status="disabled"
        title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Not enough resources!'); ?>"
    <?php elseif($build_queue_max): ?>
        data-status="disabled"
        title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Queue is full'); ?>"
    <?php else: ?>
        data-status="on"
        title="<?php echo e($building->object->title); ?>"
    <?php endif; ?>
>

                        <span class="icon sprite sprite_small small <?php echo e($building->object->class_name); ?>">
                            <!--
                            TODO: for events
                            <span class="acceleration"
                                  data-value="25">
                                    -25% ⌛
                                </span>-->
                            <?php if($building->currently_building): ?>
                                <span class="targetlevel" data-value="<?php echo e($building->current_level + 1); ?>" data-bonus="0"><?php echo e($building->current_level + 1); ?></span>
                                <div class="cooldownBackground"></div>
                                <time-counter><time class="countdown researchCountdown" id="countdownbuildingDetails" data-segments="2">...</time></time-counter>
                            <?php endif; ?>
                            <span class="level" data-value="<?php echo e($building->current_level); ?>" data-bonus="0">
                            <span class="stockAmount"><?php echo e($building->current_level); ?></span>
                            <span class="bonus"></span>
                            </span>
                        </span><?php /**PATH /var/www/resources/views/ingame/research/research-item.blade.php ENDPATH**/ ?>