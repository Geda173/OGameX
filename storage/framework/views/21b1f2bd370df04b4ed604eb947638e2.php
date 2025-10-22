
<nav data-current-action="<?php echo e($currentAction); ?>">
    <ul>
        <li>
            <a class="overlay" data-action="technologytree" data-overlay-same="true" href="<?php echo e(route('techtree.ajax', ['tab' => 1, 'object_id' => $objectId])); ?>">
                <?php echo app('translator')->get('Techtree'); ?>
            </a>
        </li>
        <li>
            <a class="overlay" data-action="applications" data-overlay-same="true" href="<?php echo e(route('techtree.ajax', ['tab' => 4, 'object_id' => $objectId])); ?>">
                <?php echo app('translator')->get('Applications'); ?>
            </a>
        </li>
        <li>
            <a class="overlay" data-action="technologyinformation" data-overlay-same="true" href="<?php echo e(route('techtree.ajax', ['tab' => 2, 'object_id' => $objectId])); ?>">
                <?php echo app('translator')->get('Techinfo'); ?>
            </a>
        </li>
        <li>
            <a class="overlay" data-action="technologies" data-overlay-same="true" href="<?php echo e(route('techtree.ajax', ['tab' => 3, 'object_id' => $objectId])); ?>">
                <?php echo app('translator')->get('Technology'); ?>
            </a>
        </li>
    </ul>
</nav><?php /**PATH /var/www/resources/views/ingame/techtree/partials/nav.blade.php ENDPATH**/ ?>