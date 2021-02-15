{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- GetTheMacGuffin implementation : © Séverine Kamycki severinek@gmail.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    getthemacguffin_getthemacguffin.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
    
    Please REMOVE this comment before publishing your game on BGA
-->
<div id="help_line" class="whiteblock gtm_white_block">
    <div>
        <img src="{GAMETHEMEURL}/img/icons/tomb.jpg" style="vertical-align: text-bottom;" />
        <span class="gtm_cards_counter">x</span>
        <span id="tomb_count" class="gtm_cards_counter"></span>
        <div id="help_msg_wrapper">
            <span id="help_msg" class="gtm_help"></span>
        </div>
    </div>
</div>
<div id="mainLine">
    <div id="discard_pile_wrap" class="whiteblock gtm_white_block">
        <h3 class="gtm_block_title">{PLAYING_ZONE}</h3>
        <div id="discard_pile">
        </div>
    </div>
    <div id="myhand_wrap" class="whiteblock gtm_white_block">
        <h3 class="gtm_block_title">{MY_HAND}</h3>
        <div id="myhand">
        </div>
    </div>
</div>
<div id="secret_zone_wrap" class="whiteblock gtm_white_block">
    <h3 class="gtm_block_title" style="display: inline;">{SECRET_CARDS_FROM}</h3>
    <h3 id="secret_zone_title" style="display: inline-block;"></h3>
    <div id="secret_zone"></div>
</div>

<div id="in_play_wrapper">

    <!-- BEGIN player -->
    <div id="in_play_{PLAYER_ID}" class="whiteblock gtmInPlayZone gtm_white_block">
        <div style="color:#{PLAYER_COLOR}" class="gtmPlayerName">
            <h3 class="gtm_block_title">{PLAYER_NAME}</h3>
        </div>
        <div class="cardsInPlay" id="cards_in_play_{PLAYER_ID}">
        </div>
        <span id="cards_count_{PLAYER_ID}" class="gtm_cards_counter gtm_cards_count"></span>
        <div id="options_{PLAYER_ID}">
        </div>
    </div>
    <!-- END player -->

</div>

<div id="playing_zone_detail_wrap" class="whiteblock gtm_white_block">
    <h3 class="gtm_block_title">{PLAYING_ZONE_DETAIL}</h3>
    <div id="playing_zone_detail"></div>
</div>

<script type="text/javascript">

    // Javascript HTML templates

    /*
    // Example:
    var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';
    
    */
    var jstpl_card_tooltip = '\
        <div class="gtm_card-tooltip-desc">\
            <div class="gtm-tooltip-name">${cardName}</div>\
            <hr/>\
            <div class="gtm-tooltip-description">${cardDescription}</div>\
        </div>';

    var jstpl_animation = '<div class="animationDiv animation_background animation ${background_class}" id="animationDiv"></div>';
</script>

{OVERALL_GAME_FOOTER}