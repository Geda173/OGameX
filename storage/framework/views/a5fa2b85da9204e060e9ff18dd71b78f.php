<div id="content">
    <div>
        <script type="text/javascript">
            var currentCategory = 1;
            var currentType = <?php echo e($highscoreCurrentType); ?>;
            var searchPosition = <?php echo e($player->getId()); ?>;
            var site = <?php echo e($highscoreCurrentPage); ?>;
            var searchSite = <?php echo e($highscoreCurrentPage); ?>;
            var resultsPerPage = 100;
            var searchRelId = <?php echo e($player->getId()); ?>;
        </script>

        <div class="pagebar">
            <?php if($highscoreCurrentPage > 1): ?>
                <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => 1, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">«</a>&nbsp;
            <?php endif; ?>
            <?php for($i = 1; $i <= ceil($highscorePlayerAmount / 100); $i++): ?>
                <?php if($highscoreCurrentPage == $i): ?>
                    <span class=" activePager"><?php echo e($i); ?></span>
                <?php else: ?>
                    <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => $i, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">
                        <?php echo e($i); ?>

                    </a>
                <?php endif; ?>
                &nbsp;
            <?php endfor; ?>
            <?php if($highscoreCurrentPage < floor($highscorePlayerAmount / 100)): ?>
                <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => floor($highscorePlayerAmount / 100) + 1, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">»</a>
            <?php endif; ?>
        </div>
        <select class="changeSite fright">
            <option value="<?php echo e($highscoreCurrentPlayerPage); ?>">Own position</option>
            <?php for($i = 1; $i <= ceil($highscorePlayerAmount / 100); $i++): ?>
                <option <?php echo e($i == $highscoreCurrentPage ? 'selected="selected"' : ''); ?> value="<?php echo e($i); ?>"> <?php echo e(((($i-1) * 100) + 1)); ?> - <?php echo e($i * 100); ?></option>
            <?php endfor; ?>
        </select>
        <div class="fleft" id="highscoreHeadline">
            Points
        </div>
        <table id="ranks" class="userHighscore">
            <thead>
            <tr>
                <td class="position">
                    Position
                </td>
                <td class="movement"></td>
                <td class="name">
                    Player’s Name
                    (Honour points)
                </td>
                <td class="sendmsg" align="center">
                    Action
                </td>
                <td class="score" align="center">
                    Points
                </td>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $highscorePlayers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $highscorePlayer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="<?php echo e($highscorePlayer['id'] == $player->getId()  ? 'myrank' : ($highscorePlayer['rank'] % 2 == 0  ? 'alt' : '')); ?>" id="position<?php echo e($highscorePlayer['id']); ?>">
                    <td class="position">
                        <?php echo e($highscorePlayer['rank']); ?>

                    </td>

                    <td class="movement">
                        <?php if(1 > 5): ?>
                            <span class="undermark"><img src="/img/icons/1c7545144452ec3e38c9fba216c4f9.gif" alt="up">
                                <span class="stats_counter">(11)</span>
                            </span>
                        <?php elseif(1 == 1): ?>
                            <img src="/img/icons/ea5bf2cc93e52e22e3c1b80c7f7563.gif" alt="stay">
                        <?php else: ?>
                            <span class="overmark">
                                <img src="/img/icons/7e6b4e65bec62ac2f10ea24ba76c51.gif" alt="down">
                                <span class="stats_counter">(1)</span>
                            </span>
                        <?php endif; ?>

                    </td>
                    <td class="name">

                        <a href="
<?php echo e(route('galaxy.index', ['galaxy' => $highscorePlayer['planet_coords']->galaxy, 'system' => $highscorePlayer['planet_coords']->system, 'position' => $highscorePlayer['planet_coords']->position])); ?>" class="dark_highlight_tablet">

<span class="
                 playername">
                    <?php echo e($highscorePlayer['name']); ?>

            </span>

                            <span class="honorScore">
(<span class="undermark tooltip js_hideTipOnMobile" title="Honour points">0</span>)
</span>
                        </a>
                    </td>

                    <td class="sendmsg">
                        <div class="sendmsg_content">
                            <a href="javascript:void(0)" class="sendMail js_openChat tooltip" data-playerid="114167" title="Write message"><span class="icon icon_chat"></span></a>
                            <a class="tooltip overlay js_hideTipOnMobile icon" title="Buddy request" data-overlay-title="Buddy request" href="#buddies&amp;action=7&amp;id=114167&amp;ajax=1">
                                <span class="icon icon_user"></span>
                            </a>

                        </div>
                    </td>

                    <td class="score
                     ">
                        <?php echo e($highscorePlayer['points_formatted']); ?>

                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <script type="text/javascript">
            $(document).ready(function(){
                initHighscoreContent();
                initHighscore();
            });
        </script>
        <div class="pagebar">
            <a href="javascript:void(0);" class="scrollToTop">Back to top</a>
            &nbsp;
            <?php if($highscoreCurrentPage > 1): ?>
                <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => 1, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">«</a>&nbsp;
            <?php endif; ?>
            <?php for($i = 1; $i <= ceil($highscorePlayerAmount / 100); $i++): ?>
                <?php if($highscoreCurrentPage == $i): ?>
                    <span class=" activePager"><?php echo e($i); ?></span>
                <?php else: ?>
                    <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => $i, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">
                        <?php echo e($i); ?>

                    </a>
                <?php endif; ?>
                &nbsp;
            <?php endfor; ?>
            <?php if($highscoreCurrentPage < floor($highscorePlayerAmount / 100)): ?>
                <a href="javascript:void(0);" class="" onclick="ajaxCall('<?php echo e(route('highscore.ajax', ['page' => floor($highscorePlayerAmount / 100) + 1, 'type' => $highscoreCurrentType])); ?>', '#stat_list_content'); return false;">»</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH /var/www/resources/views/ingame/highscore/players_points.blade.php ENDPATH**/ ?>