@extends('ingame.layouts.main')

@section('content')
@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<div id="eventboxContent" style="display: none">
    <img height="16" width="16" src="/img/icons/3f9884806436537bdec305aa26fc60.gif">
</div>

<div id="preferencescomponent" class="maincontent">
    <div id="preferences">
        <div id="inhalt">
            <div id="planet">
                <h2>Options - {!! $username !!}</h2>
            </div>
            <div class="c-left"></div>
            <div class="c-right"></div>

            <div id="content" style="color:#fff;">
                <div class="sectioncontent">
                    <div class="contentzs ui-tabs ui-widget ui-widget-content ui-corner-all" id="preferencesTabs">
                        <div class="tabwrapper">
                            <ul class="tabsbelow ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" id="tabs-pref" role="tablist">
                                <li class="ui-state-default ui-corner-top ui-tabs-active ui-state-active">
                                    <a href="#one"><span>User data</span></a>
                                </li>
                                <li class="ui-state-default ui-corner-top">
                                    <a href="#two"><span>General</span></a>
                                </li>
                                <li class="ui-state-default ui-corner-top">
                                    <a href="#three"><span>Display</span></a>
                                </li>
                                <li class="ui-state-default ui-corner-top">
                                    <a href="#four"><span>Extended</span></a>
                                </li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('options.save') }}" autocomplete="off" id="prefs" class="formValidation">
                            @csrf

                            <div class="content">
                                <!-- TAB ONE -->
                                <div id="one" class="wrap ui-tabs-panel ui-widget-content ui-corner-bottom" style="display: block;">
                                    <div class="category fieldwrapper alt bar">
                                        <label class="styled textBeefy" data-element="playername">Players Name</label>
                                    </div>
                                    <div class="group bborder">
                                        <div class="fieldwrapper">
                                            <label class="styled textBeefy">@lang('Your player name:')</label>
                                            <div class="thefield">{!! $username !!}</div>
                                        </div>
                                        @if ($canUpdateUsername)
                                        <div class="fieldwrapper">
                                            <label class="styled textBeefy">@lang('New player name:')</label>
                                            <div class="thefield">
                                                <input class="textInput w200" type="text" maxlength="20" name="new_username_username">
                                            </div>
                                        </div>
                                        @endif
                                        <div class="fieldwrapper">
                                            <p>@lang('You can change your username once per week.')</p>
                                        </div>
                                    </div>

                                    <div class="category fieldwrapper alt bar">
                                        <label class="styled textBeefy" data-element="userpassword">Change password</label>
                                    </div>
                                    <div class="group bborder">
                                        <div class="fieldwrapper">
                                            <label class="styled textBeefy">Enter old password:</label>
                                            <div class="thefield">
                                                <input class="textInput w200" type="password" name="db_password" maxlength="100">
                                            </div>
                                        </div>
                                        <div class="fieldwrapper">
                                            <label class="styled textBeefy">New password:</label>
                                            <div class="thefield">
                                                <input class="textInput w200" type="password" name="newpass1" maxlength="100">
                                            </div>
                                        </div>
                                        <div class="fieldwrapper">
                                            <label class="styled textBeefy">Repeat new password:</label>
                                            <div class="thefield">
                                                <input class="textInput w200" type="password" name="newpass2" maxlength="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- More tabs can be restored here if needed -->

                                <div class="textCenter">
                                    <input type="submit" class="btn_blue" value="Use settings">
                                </div>
                            </div>
                        </form>

                    </div> <!-- contentzs -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Umami tracking -->
<script defer src="https://umami.mierau-kiss.de/script.js" data-website-id="78f0acb4-da82-4de8-b6bd-bcad6a566d17"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ === 'function' && typeof $.fn.tabs === 'function') {
            $('#preferencesTabs').tabs();
        } else {
            console.warn('jQuery or jQuery UI tabs not found');
        }
    });
</script>
@endsection
