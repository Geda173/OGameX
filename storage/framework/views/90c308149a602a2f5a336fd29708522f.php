<?php /** @var int $open_tech_id */ ?>
<?php if($open_tech_id > 0): ?>
<script type="text/javascript">
        $('.technology.hasDetails:not(.showsDetails)[data-technology="<?php echo e($open_tech_id); ?>"] .icon').click();
</script>
<?php endif; ?>
<?php /**PATH /var/www/resources/views/ingame/shared/technology/open-tech.blade.php ENDPATH**/ ?>