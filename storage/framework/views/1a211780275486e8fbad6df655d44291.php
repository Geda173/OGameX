<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['building']));

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

foreach (array_filter((['building']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php /** @var OGame\ViewModels\UnitViewModel $building */ ?>

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
    <?php elseif(!$building->enough_resources): ?>
        data-status="disabled"
    title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Not enough resources!'); ?>"
    <?php elseif(!$building->max_build_amount): ?>
        data-status="disabled"
    title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Maximum number reached!'); ?>"
    <?php elseif($shipyard_upgrading ?? false): ?>
        data-status="disabled"
    title="<?php echo e($building->object->title); ?><br/><?php echo app('translator')->get('Shipyard is being upgraded.'); ?>"
    <?php else: ?>
        data-status="on"
    title="<?php echo e($building->object->title); ?>"
    <?php endif; ?>
>
    <span class="icon sprite <?php if($building->object->type == \OGame\GameObjects\Models\Enums\GameObjectType::Defense): ?>
        sprite_medium medium
        <?php else: ?>
        sprite_small small
        <?php endif; ?><?php echo e($building->object->class_name); ?>">
        <?php if($building->currently_building): ?>
            <span class="targetamount" data-value="<?php echo e($building->amount + $building->currently_building_amount); ?>" data-bonus="0">
                <?php echo e($building->amount + $building->currently_building_amount); ?>

            </span>
            <div class="cooldownBackground"></div>
            <time-counter><time class="countdown unitCountdown" id="countdownbuildingDetails" data-segments="2">...</time></time-counter>
        <?php endif; ?>
            <span class="amount" data-value="<?php echo e($building->amount); ?>" data-bonus="0">
            <span class="stockAmount"><?php echo e($building->getFormattedFull()); ?></span>
            <span class="bonus"></span>
        </span>
    </span>
</li>
<?php /**PATH /var/www/resources/views/ingame/shipyard/unit-item.blade.php ENDPATH**/ ?>