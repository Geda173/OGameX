<?php $__env->startSection('content'); ?>

    <?php if(session('status')): ?>
        <div class="alert alert-success">
            <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <script type="text/javascript">
        highscoreContentUrl = '<?php echo e(route('highscore.ajax')); ?>';
        var userWantsFocus = true;
    </script>

    <div id="inhalt">
        <div id="highscoreContent">
            <div class="header">
                <h2>Player highscore</h2>
            </div>
            <div class="content">
                <form id="send" name="send" action="#highscore">
                    <div id="scrollToTop" style="left: 760.336px;"><a href="javascript:void(0);" title="Back to top" class="scrollToTop tooltip js_hideTipOnMobile"></a></div>
                    <div id="row">

                        <div class="buttons leftCol" id="categoryButtons">
                            <a id="player" class="active navButton" href="javascript:void(0);" rel="1" onclick="">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Player</span>
                            </a>
                            <a id="alliance" class="navButton" href="javascript:void(0);" rel="2" onclick="">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Alliance</span>
                            </a>
                        </div>

                        <div class="buttons rightCol" id="typeButtons">

                            <a id="points" class="stat_filter active navButton fleft" href="javascript:void(0);" rel="0">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Points</span>
                            </a>

                            <a id="economy" class="stat_filter navButton fleft" href="javascript:void(0);" rel="1">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Economy</span>
                            </a>

                            <a id="research" class="stat_filter navButton fleft" href="javascript:void(0);" rel="2">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Research</span>
                            </a>

                            <a id="fleet" class="stat_filter navButton fleft" href="javascript:void(0);" rel="3">
                                <img src="/img/icons/3e567d6f16d040326c7a0ea29a4f41.gif" height="54" width="54">
                                <span class="marker"></span>
                                <span class="textlabel">Military</span>
                            </a>

                            <div id="subnav_fleet" class="fleft subnav">
                                <a href="javascript:void(0);" rel="5" class="subnavButton subnavButton_built tooltip js_hideTipOnMobile" title="Military points built">
                                    <span class="small-marker"></span>
                                </a>


                                <a href="javascript:void(0);" rel="6" class="subnavButton subnavButton_destroyed tooltip js_hideTipOnMobile" title="Military points destroyed">
                                    <span class="small-marker"></span>
                                </a>

                                <a href="javascript:void(0);" rel="4" class="subnavButton subnavButton_lost tooltip js_hideTipOnMobile" title="Military points lost">
                                    <span class="small-marker"></span>
                                </a>

                                <a href="javascript:void(0);" rel="7" class="subnavButton subnavButton_honor tooltip js_hideTipOnMobile" title="Honour points">
                                    <span class="small-marker"></span>
                                </a>
                            </div>
                        </div>

                        <br class="clearfloat">
                    </div>

                    <div class="" id="stat_list_content">
                        <!-- start dynamic highscore paging content -->
                        <?php echo $initialContent; ?>

                        <!-- end dynamic highscore paging content -->
                    </div>
                </form>
            </div>
            <div class="footer"></div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('ingame.layouts.main', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/resources/views/ingame/highscore/index.blade.php ENDPATH**/ ?>