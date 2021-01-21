<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GetTheMacGuffin implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * getthemacguffin.action.php
 *
 * GetTheMacGuffin main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/getthemacguffin/getthemacguffin/myAction.html", ...)
 *
 */


class action_getthemacguffin extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "getthemacguffin_getthemacguffin";
      self::trace("Complete reinitialization of board game");
    }
  }

  // TODO: defines your action entry points there


  /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  public function playCardAction()
  {
    self::setAjaxMode();
    $played_card_id = self::getArg("played_card_id", AT_posint, true);
    $effect_on_card_id = self::getArg("effect_on_card_id", AT_posint, true);
    $effect_on_player_id = self::getArg("effect_on_player_id", AT_posint, true);
    $this->game->playCard($played_card_id, $effect_on_card_id, $effect_on_player_id);

    self::ajaxResponse();
  }

  public function discardAction()
  {
    self::setAjaxMode();
    $played_card_id = self::getArg("played_card_id", AT_posint, true);
    $effect_on_card_id = self::getArg("effect_on_card_id", AT_posint, true);
    $effect_on_player_id = self::getArg("effect_on_player_id", AT_posint, true);
    $this->game->discard($played_card_id, $effect_on_card_id, $effect_on_player_id);

    self::ajaxResponse();
  }

  public function seenSecretCardsAction()
  {
    self::setAjaxMode();
    $selected_card_id = self::getArg("selected_card_id", AT_posint, true);
    $this->game->seenSecretCardsAction($selected_card_id);

    self::ajaxResponse();
  }

  public function specifyClockwiseAction()
  {
    self::setAjaxMode();
    $clockwise = self::getArg("clockwise", AT_bool, true);
    $this->game->playClockwise($clockwise);

    self::ajaxResponse();
  }

  public function takeObjectAction()
  {
    self::setAjaxMode();
    $object_id = self::getArg("object_id", AT_posint, true);
    $this->game->takeObject($object_id);

    self::ajaxResponse();
  }

  public function swapObjectsAction()
  {
    self::setAjaxMode();
    $object_id_1 = self::getArg("object_id_1", AT_posint, true);
    $object_id_2 = self::getArg("object_id_2", AT_posint, true);
    $this->game->swapObjects($object_id_1, $object_id_2);

    self::ajaxResponse();
  }
}
