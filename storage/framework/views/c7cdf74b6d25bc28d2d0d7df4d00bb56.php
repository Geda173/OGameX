<br>
<br>
<div class="compacting">
    <span class="ctn ctn4"><?php echo app('translator')->get('Player'); ?>:</span>
    <!-- TODO: implement dynamic status_abbr_active based on active status of player
    (active last 7 days, last 28 days etc, see galaxy legend for more info). -->
    <span class="status_abbr_active">&nbsp;&nbsp;<?php echo $playername; ?></span>
    <!-- TODO: implement player activity detection -->
    &nbsp;<span class="ctn ctn4 fright"><?php echo app('translator')->get('Activity'); ?>: &gt;60 <?php echo app('translator')->get('minutes ago'); ?>.</span>
</div>

<div class="compacting">
    <!-- TODO: implement player class -->
    <span class="ctn ctn4"><?php echo app('translator')->get('Class'); ?>:</span>
    &nbsp;
    <?php echo app('translator')->get('Unknown'); ?>
</div>

<div class="compacting">
    <!-- TODO: implement alliance class -->
    <span class="ctn ctn4"><?php echo app('translator')->get('Alliance Class'); ?>:</span>
    &nbsp;
    <span class="alliance_class small none"><?php echo app('translator')->get('No alliance class selected'); ?></span>
</div>

<br>

<div class="compacting">
    <span class="ctn ctn4">
        <span class="resspan"><?php echo app('translator')->get('Metal'); ?>: <?php echo e($resources->metal->getFormattedLong()); ?></span>
        <span class="resspan"><?php echo app('translator')->get('Crystal'); ?>: <?php echo e($resources->crystal->getFormattedLong()); ?></span>
        <span class="resspan"><?php echo app('translator')->get('Deuterium'); ?>: <?php echo e($resources->deuterium->getFormattedLong()); ?></span>
        <!--
        <br>
        <span class="resspan">Food: 190,005</span>
        <span class="resspan">Population: 27.893Mn</span>
        -->
    </span>
    <!-- TODO: implement this element -->
    <span class="ctn ctn4 fright tooltipRight tooltipClose" title="Loot: 352,927<br/><a href=&quot;#TODO_page=ingame&amp;component=fleetdispatch&amp;galaxy=1&amp;system=4&amp;position=10&amp;type=1&amp;mission=1&amp;am202=51&quot;>S.Cargo: 51</a><br/><a href=&quot;#TODO_page=ingame&amp;component=fleetdispatch&amp;galaxy=1&amp;system=4&amp;position=10&amp;type=1&amp;mission=1&amp;am203=11&quot;>L.Cargo: 11</a><br/>"><?php echo app('translator')->get('Resources'); ?>: <?php echo e(\OGame\Facades\AppUtil::formatNumber($resources->sum())); ?></span>
</div>

<div class="compacting">
    <!-- TODO: implement loot percentage -->
    <span class="ctn ctn4"><?php echo app('translator')->get('Loot'); ?>: 75%</span>
    <!-- TODO: implement counter-espionage -->
    <span class="fright"><?php echo app('translator')->get('Chance of counter-espionage'); ?>: 0%</span>
</div>

<div class="compacting">
    <!-- TODO: implement fleet to resource calculation -->
    <span class="ctn ctn4 tooltipLeft" title="Fleets: 0"><?php echo app('translator')->get('Fleets'); ?>: 0</span>
    <!-- TODO: implement defense to resource calculation -->
    <span class="ctn ctn4 fright tooltipRight" title="693,000"><?php echo app('translator')->get('Defense'); ?>: 0</span>
</div>

<br><?php /**PATH /var/www/resources/views/ingame/messages/templates/espionage_report.blade.php ENDPATH**/ ?>