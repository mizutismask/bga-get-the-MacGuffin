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
    // Get players & players number
    $players = $this->game->loadPlayersBasicInfos();
    $players_nbr = count($players);
    $active_player_id = $this->game->publicGetCurrentPlayerId();
    $template = self::getGameName() . "_" . self::getGameName();

    /*********** Place your code below:  ************/

    $this->tpl['MY_HAND'] = self::_("My hand");
    $this->tpl['PLAYING_ZONE'] = self::_("Playing zone");
    $this->tpl['HAND_COUNT'] = self::_("Cards in hand number");
    $this->tpl['SECRET_CARDS_FROM'] = self::_("Secret cards from ");
    $this->tpl['TOMB_COUNT'] = self::_("Cards in the tomb: ");


    /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */
    $this->page->begin_block($template, "player");

    //starting with the active player if he’s not a spectator
    if (key_exists($active_player_id, $players)) {
      $this->page->insert_block("player", array(
        "PLAYER_ID" => $active_player_id,
        "PLAYER_NAME" => $players[$active_player_id]['player_name'],
        "PLAYER_COLOR" => $players[$active_player_id]['player_color'],
        "PLAYER_NAME" => $players[$active_player_id]['player_name'],
      ));
    }

    //then the other players
    foreach ($players as $player_id => $info) {
      if ($player_id != $active_player_id) {
        $this->page->insert_block("player", array(
          "PLAYER_ID" => $player_id,
          "PLAYER_NAME" => $players[$player_id]['player_name'],
          "PLAYER_COLOR" => $players[$player_id]['player_color'],
          "PLAYER_NAME" => $players[$player_id]['player_name'],
        ));
      }
    }
    /*
        
        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 
        

        $this->page->begin_block( "getthemacguffin_getthemacguffin", "myblock" );
        foreach( $players as $player )
        {
            $this->page->insert_block( "myblock", array( 
                                                    "PLAYER_NAME" => $player['player_name'],
                                                    "SOME_VARIABLE" => $some_value
                                                    ...
                                                     ) );
        }
        
        */



    /*********** Do not change anything below this line  ************/
  }
}
