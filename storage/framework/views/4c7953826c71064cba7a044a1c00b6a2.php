<?php /** @var OGame\Services\PlanetService $currentPlanet */ ?>
<div id="abandonplanet">
    <img src="<?php echo asset('img/planets/big/' . $currentPlanet->getPlanetBiomeType() . '_' . $currentPlanet->getPlanetImageType() . '.png'); ?>"
         class="float_left"/>
    <p class="desc_txt"><?php echo app('translator')->get('Using this menu you can change planet names and moons or completely abandon them.'); ?></p>
    <table cellpadding="0" cellspacing="0">
        <tbody>
        <tr class="head">
            <th colspan="3">Rename</th>
        </tr>
        <tr>
            <td colspan="3" class="ipiHintable" data-ipi-hint="ipiPlanetSettingsName">
                <form id="planetMaintenance" class="formValidation"
                      onsubmit="clearField(); $('#newPlanetName').val($('#planetName').val()); ajaxFormSubmit('planetMaintenance', '<?php echo e(route('planetabandon.rename')); ?>', planetRenamed); return false;">
                    <input type="hidden" id="newPlanetName" name="newPlanetName" value="<?php echo e($isMoon ? 'New name of the moon' : 'New planet name '); ?>">
                    <input type='hidden' name='_token' value='<?php echo e(csrf_token()); ?>'/>

                    <?php if($isMoon): ?>
                        <a title="Rules|You can rename your moon here.&lt;br /&gt;
&lt;br /&gt;
The moon name has to be between &lt;span style=&quot;font-weight: bold;&quot;&gt;2 and 20 characters&lt;/span&gt; long.&lt;br /&gt;
Moon names may comprise of lower and upper case letters as well as numbers.&lt;br /&gt;
They may contain hyphens, underscores and spaces - however these may not be placed as follows:&lt;br /&gt;
- at the beginning or at the end of the name&lt;br /&gt;
- directly next to one another&lt;br /&gt;
- more than three times in the name"
                       href="javascript:void(0);"
                       class="tooltipHTML tooltipLeft help"></a>
                    <?php else: ?>
                        <a title="Rules|You can rename your planet here.&lt;br /&gt;
&lt;br /&gt;
The planet name has to be between &lt;span style=&quot;font-weight: bold;&quot;&gt;2 and 20 characters&lt;/span&gt; long.&lt;br /&gt;
Planet names may comprise of lower and upper case letters as well as numbers.&lt;br /&gt;
They may contain hyphens, underscores and spaces - however these may not be placed as follows:&lt;br /&gt;
- at the beginning or at the end of the name&lt;br /&gt;
- directly next to one another&lt;br /&gt;
- more than three times in the name"
                       href="javascript:void(0);"
                       class="tooltipHTML tooltipLeft help"></a>
                    <?php endif; ?>
                    <input
                            class="text w200 validate[optional,custom[noSpecialCharacters],custom[noBeginOrEndUnderscore],custom[noBeginOrEndWhitespace],custom[noBeginOrEndHyphen],custom[notMoreThanThreeUnderscores],custom[notMoreThanThreeWhitespaces],custom[notMoreThanThreeHyphen],custom[noCollocateUnderscores],custom[noCollocateWhitespaces],custom[noCollocateHyphen],minSize[2]]"
                            type="text"
                            maxlength="20"
                            size="25"
                            id="planetName"
                            value="<?php echo e($isMoon ? 'New name of the moon' : 'New planet name'); ?>"
                            onFocus="clearField()"
                            onBlur="fillField()"
                    />
                    <input class="btn_blue float_right" type="submit" value="Rename" name="aktion"/>
                </form>
            </td>
        </tr>
        <tr class="head">
            <th colspan="3" class="second" id="giveupHeadline" rel="1">
                <?php if($isCurrentPlanetHomePlanet): ?>
                    <?php echo app('translator')->get('Abandom home planet'); ?>
                <?php elseif($isMoon): ?>
                    <?php echo app('translator')->get('Abandon Moon'); ?>
                <?php else: ?>
                    <?php echo app('translator')->get('Abandon Colony'); ?>
                <?php endif; ?>
            </th>
        </tr>

        <?php if($isCurrentPlanetHomePlanet): ?>
            <tr>
                <td colspan="3">
                    <?php echo app('translator')->get('If you abandon your home planet, immediately upon your next login you will be directed to the planet that you colonised next.'); ?>
                </td>
            </tr>
        <?php endif; ?>

        <tr>
            <td id="giveupCoordinates">[<?php echo e($currentPlanet->getPlanetCoordinates()->asString()); ?>]</td>
            <td id="giveupName"><?php echo e($currentPlanet->getPlanetName()); ?></td>
            <td>
                <a id="block" class="start btn_blue float_right">
                    <?php if($isCurrentPlanetHomePlanet): ?>
                        <?php echo app('translator')->get('Abandon Home Planet'); ?>
                    <?php elseif($isMoon): ?>
                        <?php echo app('translator')->get('Abandon moon'); ?>
                    <?php else: ?>
                        <?php echo app('translator')->get('Abandon Colony'); ?>
                    <?php endif; ?>
                </a>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <form id="planetMaintenanceDelete" action="<?php echo e(route('planetabandon.abandon.confirm')); ?>">
                    <input type='hidden' name='_token' value='<?php echo e(csrf_token()); ?>'/>
                    <div id="giveUpNotification">
                        <?php if($isMoon): ?>
                            <?php echo app('translator')->get('If you have activated items on a moon, they will be lost if you abandon the moon.'); ?>
                        <?php else: ?>
                            <?php echo app('translator')->get('If you have activated items on a planet, they will be lost if you abandon the planet.'); ?>
                        <?php endif; ?>
                    </div>
                    <div class="validate" id="validate" style="display:none;">
                        <p class="margin_10_0"><?php echo app('translator')->get('Please confirm deletion of :type [:coordinates] by putting in your password', [
                            'type' => $isMoon ? __('moon') : __('planet'),
                            'coordinates' => $currentPlanet->getPlanetCoordinates()->asString()
                        ]); ?></p>
                        <input class="text w200 pw_field" type="password" name="password" maxlength="1024" size="25"/>
                        <input class="btn_blue" type="submit" value="<?php echo app('translator')->get('Confirm'); ?>"/>
                    </div>
                </form>
            </td>
        </tr>
        </tbody>
    </table>

    <script type="text/javascript">
        (function ($) {
            $.fn.validationEngineLanguage = function () {
            };
            $.validationEngineLanguage = {
                newLang: function () {
                    $.validationEngineLanguage.allRules = {
                        "minSize": {
                            "regex": "none",
                            "alertText": "Not enough characters"
                        },
                        "pwMinSize": {
                            "regex": /^.{ 4,}$/,
                            "alertText": "The entered password is to short (min. 4 characters)"
                        },
                        "pwMaxSize": {
                            "regex": /^.{0, 20}$/,
                            "alertText": "The entered password is to long (max. 20 characters)"
                        },
                        "email": {
                            "regex": /^[a-zA-Z0-9_\.\-]+\@([a-zA-Z0-9\-]+\.)+[a-zA-Z0-9]{2,4}$/,
                            "alertText": "You need to enter a valid email address!"
                        },
                        "noSpecialCharacters": {
                            "regex": /^[a-zA-Z0-9\-_\s]+$/,
                            "alertText": "Contains invalid characters."
                        },
                        "noBeginOrEndUnderscore": {
                            "regex": /^([^_]+(.*[^_])?)?$/,
                            "alertText": "Your name may not start or end with an underscore."
                        },
                        "noBeginOrEndHyphen": {
                            "regex": /^([^\-]+(.*[^\-])?)?$/,
                            "alertText": "Your name may not start or finish with a hyphen."
                        },
                        "noBeginOrEndWhitespace": {
                            "regex": /^([^\s]+(.*[^\s])?)?$/,
                            "alertText": "Your name may not start or end with a space."
                        },
                        "notMoreThanThreeUnderscores": {
                            "regex": /^[^_]*(_[^_]*){0,3}$/,
                            "alertText": "Your name may not contain more than 3 underscores in total."
                        },
                        "notMoreThanThreeHyphen": {
                            "regex": /^[^\-]*(\-[^\-]*){0,3}$/,
                            "alertText": "Your name may not contain more than 3 hyphens."
                        },
                        "notMoreThanThreeWhitespaces": {
                            "regex": /^[^\s]*(\s[^\s]*){0,3}$/,
                            "alertText": "Your name may not include more than 3 spaces in total."
                        },
                        "noCollocateUnderscores": {
                            "regex": /^[^_]*(_[^_]+)*_?$/,
                            "alertText": "You may not use two or more underscores one after the other."
                        },
                        "noCollocateHyphen": {
                            "regex": /^[^\-]*(\-[^\-]+)*-?$/,
                            "alertText": "You may not use two or more hyphens consecutively."
                        },
                        "noCollocateWhitespaces": {
                            "regex": /^[^\s]*(\s[^\s]+)*\s?$/,
                            "alertText": "You may not use two or more spaces one after the other."
                        }

                    }
                }
            }
            $.validationEngineLanguage.newLang();
        })(jQuery);
    </script>
    <script language="javascript">
        var defaultName = "<?php echo e($isMoon ? 'New name of the moon' : 'New planet name'); ?>";
    </script>
    <script>
        initFormValidation();
        if (typeof IPI !== 'undefined') {
            IPI.refreshHighlights()
        }
    </script>
</div>
<?php /**PATH /var/www/resources/views/ingame/planetabandon/overlay.blade.php ENDPATH**/ ?>