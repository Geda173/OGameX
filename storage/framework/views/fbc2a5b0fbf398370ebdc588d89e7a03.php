<?php /** @var OGame\ViewModels\Queue\UnitQueueViewModel $build_active */ ?>
<?php if(!empty($build_active)): ?>
    <table cellspacing="0" cellpadding="0" class="construction active">
        <tbody>
        <tr>
            <th colspan="2"><?php echo e($build_active->object->title); ?></th>
        </tr>
        <tr class="data">
            <td class="first" rowspan="5">
                <div>
                    <a href="<?php echo e(route('shipyard.index', ['openTech' => $build_active->object->id])); ?>">
                        <img class="queuePic" width="40" height="40"
                             src="<?php echo asset('img/objects/units/' . $build_active->object->assets->imgSmall); ?>"
                             alt="<?php echo e($build_active->object->title); ?>">
                    </a>
                </div>
                <div class="shipSumCount shipyardCountdownUnit"><?php echo e($build_active->object_amount_remaining); ?></div>
            </td>
        </tr>
        <tr class="data">
            <td class="desc"><?php echo app('translator')->get('Building duration'); ?></td>
        </tr>
        <tr class="data">
            <td class="desc timer">
                <time class="countdown shipyardCountdownUnit"
                      data-segments="2"><?php echo e(\OGame\Facades\AppUtil::formatTimeDuration($build_active->time_countdown_object_next)); ?></time>
            </td>
        </tr>
        <tr class="data">
            <td class="desc"><?php echo app('translator')->get('Total time'); ?>:</td>
        </tr>
        <tr class="data">
            <td class="desc timer">
                <time class="countdown unitCountdown"
                      data-segments="2"><?php echo e(\OGame\Facades\AppUtil::formatTimeDuration($build_queue_countdown)); ?></time>
            </td>
        </tr>
        <tr class="data">
            <td colspan="2">
                <a class="build-faster dark_highlight tooltipLeft js_hideTipOnMobile ship tpd-hideOnClickOutside"
                   title="" href="javascript:void(0);"
                   rel="#page=componentOnly&amp;component=itemactions&amp;action=buyAndActivate&amp;itemUuid=75accaa0d1bc22b78d83b89cd437bdccd6a58887&amp;asJson=1">
                    <div class="                                                build-finish-img
                                            " alt="                                                Complete
                                            "></div>
                    <span class="build-txt">
                                                                                            Complete
                                                                                    </span>
                    <span class="dm_cost ">
                                                                                                    Costs:
                                                                                                        5,250 DM
                                                                                            </span>
                </a>
            </td>
        </tr>
        </tbody>
    </table>

    <script type="text/javascript">
        var questionbuilding = 'Do\u0020you\u0020want\u0020to\u0020reduce\u0020the\u0020construction\u0020time\u0020of\u0020the\u0020current\u0020construction\u0020project\u0020by\u002050\u0025\u0020of\u0020the\u0020total\u0020construction\u0020time\u0020\u00287m\u002010s\u0029\u0020for\u0020\u003Cspan\u0020style\u003D\u0022font\u002Dweight\u003A\u0020bold\u003B\u0022\u003E750\u0020Dark\u0020Matter\u003C\/span\u003E\u003F';
        var pricebuilding = 750;
        var referrerPage = $.deparam.querystring().page;
        new CountdownTimer('unitCountdown', <?php echo e($build_active->time_countdown); ?>, '<?php echo e(url()->current()); ?>', null, true, 3)
        new CountdownTimerUnit('shipyardCountdownUnit', <?php echo e($build_active->time_countdown_object_next); ?>, <?php echo e($build_active->object_amount_remaining); ?>, <?php echo e($build_active->object->id); ?>, <?php echo e($build_active->time_countdown_per_object); ?>, false, 3)
    </script>
<?php else: ?>
    <table cellspacing="0" cellpadding="0" class="construction active">
        <tbody>
        <tr>
            <td colspan="2" class="idle">
                <a class="tooltip js_hideTipOnMobile " title="<?php echo app('translator')->get('At the moment there are no ships or defense built on this planet. Click here to get to the shipyard.'); ?>" href="<?php echo e(route('shipyard.index')); ?>">
                    <?php echo app('translator')->get('No ships/defense in construction.'); ?>
                    <br>
                    <?php echo app('translator')->get('(To shipyard)'); ?>
                </a>
            </td>
        </tr>
        </tbody>
    </table>
<?php endif; ?><?php /**PATH /var/www/resources/views/ingame/shared/buildqueue/unit-active.blade.php ENDPATH**/ ?>