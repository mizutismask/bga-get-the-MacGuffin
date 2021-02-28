<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GetTheMacGuffin implementation : © Séverine Kamycki severinek@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * getthemacguffin.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in getthemacguffin_getthemacguffin.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_getthemacguffin_getthemacguffin extends game_view
{
  function getGameName()
  {
    return "getthemacguffin";
  }
  function build_page($viewArgs)
  {
    // Get players
    $players = $this->game->loadPlayersBasicInfos();
    $players_in_order = $this->game->getPlayersInOrder(false);
    $active_player_id = $this->game->publicGetCurrentPlayerId();
    $template = self::getGameName() . "_" . self::getGameName();

    /*********** Place your code below:  ************/

    $this->tpl['MY_HAND'] = self::_("My hand");
    $this->tpl['PLAYING_ZONE'] = self::_("Discard");
    $this->tpl['HAND_COUNT'] = self::_("Cards in hand number");
    $this->tpl['SECRET_CARDS_FROM'] = self::_("Secret cards from ");
    $this->tpl['TOMB_COUNT'] = self::_("Cards in the tomb:");
    $this->tpl['PLAYING_ZONE_DETAIL'] = self::_("Discard detail");


    $this->page->begin_block($template, "player");

    foreach ($players_in_order  as $player_id) {
      if (key_exists($active_player_id, $players)) {
        $this->page->insert_block("player", array(
          "PLAYER_ID" => $player_id,
          "PLAYER_NAME" => $players[$player_id]['player_name'],
          "PLAYER_COLOR" => $players[$player_id]['player_color'],
          "PLAYER_NAME" => $players[$player_id]['player_name'],
        ));
      }
    }

    /*********** Do not change anything below this line  ************/
  }
}
