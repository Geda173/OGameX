<?php if(!empty($properties)): ?>
    <table class="combat_unit_details">
        <thead>
        <tr>
            <th colspan="2">Technical data</th>
        </tr>
        </thead>
        <tbody>
        <?php if(isset($properties['structural_integrity'])): ?>
            <tr class="structural_integrity">
                <th>Structural Integrity</th>
                <td>
                    <span class="tooltipHTML tipped advice"
                          title="<?php echo e($tooltips['structural_integrity']); ?>" data-value="9600"
                          data-base-value="4000" data-bonus="4800" data-bonus-formula="12 x 400">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['structural_integrity'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <?php if(isset($properties['shield'])): ?>
            <tr class="shield_strength">
                <th>Shield Strength</th>
                <td>
                    <span class="tooltipHTML tipped advice" title="<?php echo e($tooltips['shield']); ?>"
                          data-value="21" data-base-value="10" data-bonus="9" data-bonus-formula="9 x 1">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['shield'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <?php if(isset($properties['attack'])): ?>
            <tr class="attack_strength">
                <th>Attack Strength</th>
                <td>
                    <span class="tooltipHTML tipped advice" title="<?php echo e($tooltips['attack']); ?>"
                          data-value="110" data-base-value="50" data-bonus="50"
                          data-bonus-formula="10 x 5">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['attack'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <?php if(isset($properties['speed'])): ?>
            <tr class="speed">
                <th>Speed</th>
                <td>
                    <span class="tooltipHTML tipped advice" title="<?php echo e($tooltips['speed']); ?>"
                          data-value="32500" data-base-value="12500" data-bonus="7500"
                          data-bonus-formula="6 × 1,250">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['speed'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <?php if(isset($properties['capacity'])): ?>
            <tr class="cargo_capacity">
                <th>Cargo Capacity</th>
                <td>
                    <span class="tooltipHTML tipped advice" title="<?php echo e($tooltips['capacity']); ?>"
                          data-value="57" data-base-value="50" data-bonus="7.5"
                          data-bonus-formula="50 × 15%">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['capacity'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        <?php if(isset($properties['fuel'])): ?>
            <tr class="fuel_consumption">
                <th>Fuel usage (Deuterium)</th>
                <td>
                    <span class="tooltipHTML tipped advice" title="<?php echo e($tooltips['fuel']); ?>"
                          data-value="7" data-base-value="20" data-bonus="-10" data-bonus-formula="50%">
                        <?php echo e(\OGame\Facades\AppUtil::formatNumber($properties['fuel'])); ?>

                    </span>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?><?php /**PATH /var/www/resources/views/ingame/techtree/info/properties.blade.php ENDPATH**/ ?>