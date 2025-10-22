<?php if(!empty($rapidfire_against) || !empty($rapidfire_from)): ?>
    <ul class="rapid_fire">
        <?php $__currentLoopData = $rapidfire_from; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $from => $rapidfire_data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="rapid_fire_from <?php echo e($rapidfire_data['object']->class_name); ?>">
                Rapidfire from
                <a href="#TODO_page=ajax&amp;component=technologytree&amp;ajax=1&amp;technologyId=<?php echo e($from); ?>&amp;tab=2" class="overlay" data-overlay-same="true"><?php echo e($rapidfire_data['object']->title); ?></a>:
                <span class="value" data-value="<?php echo e($rapidfire_data['rapidfire']->amount); ?>">
                    <?php
                        $chance = $rapidfire_data['rapidfire']->getChance();

                        // Determine the correct number of decimal places
                        if (floor($chance) === $chance) {
                            // It's a whole number, no need for decimals
                            $formattedChance = number_format($chance, 0);
                        } else {
                            // It's a decimal number, check for how many significant digits are needed
                            $roundedChance = round($chance, 2);
                            if (round($roundedChance, 1) === $roundedChance) {
                                // If rounding to 1 decimal gives the same result, we need only 1 decimal
                                $formattedChance = number_format($roundedChance, 1);
                            } else {
                                // Otherwise, use 2 decimals
                                $formattedChance = number_format($roundedChance, 2);
                            }
                        }

                        // Format the amount normally since it's likely an integer
                        $formattedAmount = number_format($rapidfire_data['rapidfire']->amount);
                    ?>
                    <?php echo e($formattedChance); ?>% (<?php echo e($formattedAmount); ?>)
                </span>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php $__currentLoopData = $rapidfire_against; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target => $rapidfire_data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="rapid_fire_against <?php echo e($rapidfire_data['object']->class_name); ?>">
                Rapidfire against
                <a href="#TODO_page=ajax&amp;component=technologytree&amp;ajax=1&amp;technologyId=<?php echo e($target); ?>&amp;tab=2" class="overlay" data-overlay-same="true"><?php echo e($rapidfire_data['object']->title); ?></a>:
                <span class="value" data-value="<?php echo e($rapidfire_data['rapidfire']->amount); ?>">
                    <?php
                        $chance = $rapidfire_data['rapidfire']->getChance();

                        // Determine the correct number of decimal places
                        if (floor($chance) === $chance) {
                            // It's a whole number, no need for decimals
                            $formattedChance = number_format($chance, 0);
                        } else {
                            // It's a decimal number, check for how many significant digits are needed
                            $roundedChance = round($chance, 2);
                            if (round($roundedChance, 1) === $roundedChance) {
                                // If rounding to 1 decimal gives the same result, we need only 1 decimal
                                $formattedChance = number_format($roundedChance, 1);
                            } else {
                                // Otherwise, use 2 decimals
                                $formattedChance = number_format($roundedChance, 2);
                            }
                        }

                        // Format the amount normally since it's likely an integer
                        $formattedAmount = number_format($rapidfire_data['rapidfire']->amount);
                    ?>
                    <?php echo e($formattedChance); ?>% (<?php echo e($formattedAmount); ?>)
                </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/ingame/techtree/info/rapidfire.blade.php ENDPATH**/ ?>