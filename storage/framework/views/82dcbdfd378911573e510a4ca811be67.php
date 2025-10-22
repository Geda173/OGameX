<?php /** @var OGame\ViewModels\Queue\BuildingQueueViewModel $build_active */ ?>
<?php if(!empty($build_active)): ?>
    <table cellspacing="0" cellpadding="0" class="construction active">
        <tbody>
        <tr>
            <th colspan="2"><?php echo $build_active->object->title; ?></th>
        </tr>
        <tr class="data">
            <td class="first" rowspan="3">
                <div>
                    <a href="javascript:void(0);" class="tooltip js_hideTipOnMobile tpd-hideOnClickOutside"
                       style="display: block;"
                       onclick="cancelbuilding(<?php echo e($build_active->object->id); ?>, <?php echo e($build_active->id); ?>, &quot;Cancel production of <?php echo $build_active->object->title; ?> level <?php echo $build_active->level_target; ?>?&quot;); return false;"
                       title="">
                        <img class="queuePic" width="40" height="40"
                             src="<?php echo asset('img/objects/buildings/' . $build_active->object->assets->imgSmall); ?>"
                             alt="<?php echo e($build_active->object->title); ?>">
                    </a>
                    <a href="javascript:void(0);" class="tooltip js_hideTipOnMobile abortNow"
                       onclick="cancelbuilding(<?php echo e($build_active->object->id); ?>, <?php echo e($build_active->id); ?>, &quot;Cancel production of <?php echo $build_active->object->title; ?> level <?php echo $build_active->level_target; ?>?&quot;); return false;"
                       title="Cancel production of <?php echo $build_active->object->title; ?> level <?php echo $build_active->level_target; ?>?">
                        <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="15" width="15">
                    </a>
                </div>
            </td>
            <td class="desc ausbau"><?php echo app('translator')->get('Improve to'); ?>
                <span class="level"><?php echo app('translator')->get('Level'); ?> <?php echo $build_active->level_target; ?></span>
            </td>
        </tr>
        <tr class="data">
            <td class="desc"><?php echo app('translator')->get('Duration'); ?>:</td>
        </tr>
        <tr class="data">
            <td class="desc timer">
                <time class="countdown buildingCountdown"
                      data-segments="2"><?php echo e(\OGame\Facades\AppUtil::formatTimeDuration($build_active->time_countdown)); ?></time>
            </td>
        </tr>
        <tr class="data">
            <td colspan="2">
                <a class="build-faster dark_highlight tooltipLeft js_hideTipOnMobile building "
                   title="Reduces construction time by 50% of the total construction time (7m 10s)."
                   href="javascript:void(0);"
                   rel="#TODO_componentOnly&amp;component=itemactions&amp;action=buyAndActivate&amp;itemUuid=cb4fd53e61feced0d52cfc4c1ce383bad9c05f67&amp;asJson=1">
                    <div class="                                                build-faster-img
                                            " alt="                                                Halve time
                                            "></div>
                    <span class="build-txt">
                                                                                            Halve time
                                                                                    </span>
                    <span class="dm_cost ">
                                                                                                    Costs:
                                                                                                        750 DM
                                                                                            </span>
                </a>
            </td>
        </tr>
        </tbody>
    </table>
    <script type="text/javascript">
        var cancelBuildListEntryUrl = '<?php echo e(route('resources.cancelbuildrequest')); ?>';
        var questionbuilding = 'Do\u0020you\u0020want\u0020to\u0020reduce\u0020the\u0020construction\u0020time\u0020of\u0020the\u0020current\u0020construction\u0020project\u0020by\u002050\u0025\u0020of\u0020the\u0020total\u0020construction\u0020time\u0020\u00287m\u002010s\u0029\u0020for\u0020\u003Cspan\u0020style\u003D\u0022font\u002Dweight\u003A\u0020bold\u003B\u0022\u003E750\u0020Dark\u0020Matter\u003C\/span\u003E\u003F';
        var pricebuilding = 750;
        var referrerPage = $.deparam.querystring().page;

        new CountdownTimer('buildingCountdown', <?php echo e($build_active->time_countdown); ?>, '<?php echo e(url()->current()); ?>', null, true, 3)

        function cancelbuilding(id, listId, question) {
            errorBoxDecision('Caution', "" + question + "", 'yes', 'No', function () {
                buildListActionCancel(id, listId)
            });
        }
    </script>
    
<?php else: ?>
    <table cellspacing="0" cellpadding="0" class="construction active">
        <tbody>
        <tr>
            <td colspan="2" class="idle">
                <a class="tooltip js_hideTipOnMobile
                                   "
                   title="<?php echo app('translator')->get('At the moment there is no building being built on this planet. Click here to go to the build page.'); ?>"
                   href="<?php echo e(url()->current()); ?>">
                    <?php echo app('translator')->get('No buildings in construction.'); ?></a>
            </td>
        </tr>
        </tbody>
    </table>
<?php endif; ?><?php /**PATH /var/www/resources/views/ingame/shared/buildqueue/building-active.blade.php ENDPATH**/ ?>