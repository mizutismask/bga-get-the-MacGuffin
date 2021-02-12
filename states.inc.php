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
 * states.inc.php
 *
 * GetTheMacGuffin game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!
// define contants for state ids
if (!defined('STATE_PLAYER_TURN')) { // ensure this block is only invoked once, since it is included multiple times
    define("STATE_PLAYER_TURN", 2);
    define("STATE_SEE_SECRET_CARDS", 3);
    define("STATE_CLOCKWISE_OR_NOT", 4);
    define("STATE_SPECIFY_IN_PLAY_OBJECT_TO_TAKE", 5);
    define("STATE_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP", 6);
    define("STATE_MANDATORY_CARD", 7);
    define("STATE_NEXT_PLAYER", 23);

    //define("TRANSITION_PLAYER_TURN", "playerTurn");
    define("TRANSITION_NEXT_PLAYER", "nextPlayer");
    define("TRANSITION_END_GAME", "endGame");
    define("TRANSITION_DISCARD", "nextPlayer");
    define("TRANSITION_PASS", "pass");
    define("TRANSITION_SEE_SECRET_CARDS", "secretCards");
    define("TRANSITION_SPECIFY_CLOCKWISE", "specifyClockwise");
    define("TRANSITION_SPECIFY_IN_PLAY_OBJECT_TO_TAKE", "specifyInPlayObject");
    define("TRANSITION_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP", "specifyObjectsToSwap");
    define("TRANSITION_MANDATORY_CARD", "mandatoryCard");
}

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 2)
    ),

    // Note: ID=2 => your first state

    STATE_PLAYER_TURN => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card from your hand or play/discard an Object in play'),
        "type" => "activeplayer",
        "possibleactions" => array("playCard", "discard", "pass"),
        "transitions" => array(
            "playCard" => STATE_PLAYER_TURN, TRANSITION_DISCARD => STATE_PLAYER_TURN,
            TRANSITION_PASS => STATE_NEXT_PLAYER, TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,
            TRANSITION_SEE_SECRET_CARDS => STATE_SEE_SECRET_CARDS,
            TRANSITION_SPECIFY_CLOCKWISE => STATE_CLOCKWISE_OR_NOT,
            TRANSITION_SPECIFY_IN_PLAY_OBJECT_TO_TAKE => STATE_SPECIFY_IN_PLAY_OBJECT_TO_TAKE,
            TRANSITION_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP => STATE_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP,
            TRANSITION_MANDATORY_CARD => STATE_MANDATORY_CARD,
        )
    ),

    STATE_MANDATORY_CARD => array(
        "name" => "mandatoryCard",
        "description" => clienttranslate('${actplayer} must play the previously stolen card'),
        "descriptionmyturn" => clienttranslate('${you} must play the card you’ve just took'),
        "type" => "activeplayer",
        "args" => "argMandatoryCard",
        "possibleactions" => array("playCard", "discard", "pass"),
        "transitions" => array(
            "playCard" => STATE_PLAYER_TURN, TRANSITION_DISCARD => STATE_PLAYER_TURN,
            TRANSITION_PASS => STATE_NEXT_PLAYER, TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,
            TRANSITION_SEE_SECRET_CARDS => STATE_SEE_SECRET_CARDS,
            TRANSITION_SPECIFY_CLOCKWISE => STATE_CLOCKWISE_OR_NOT,
            TRANSITION_SPECIFY_IN_PLAY_OBJECT_TO_TAKE => STATE_SPECIFY_IN_PLAY_OBJECT_TO_TAKE,
            TRANSITION_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP => STATE_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP,
        )
    ),

    STATE_SEE_SECRET_CARDS => array(
        "name" => "seeSecretCards",
        "description" => clienttranslate('${actplayer} is looking at secret cards'),
        "descriptionmyturn" => clienttranslate('${you} are looking at secret cards'),
        "type" => "activeplayer",
        "args" => "argSeeSecretCards",
        "possibleactions" => array("takeCard", "confirm"),
        "transitions" => array(TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,)
    ),

    STATE_CLOCKWISE_OR_NOT => array(
        "name" => "specifyClockwise",
        "description" => clienttranslate('${actplayer} is choosing clockwise or counterclockwise'),
        "descriptionmyturn" => clienttranslate('${you} must give your hand to the next player or the previous one'),
        "type" => "activeplayer",
        "possibleactions" => array("clockwise", "counterclockwise"),
        "transitions" => array(TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,)
    ),

    STATE_SPECIFY_IN_PLAY_OBJECT_TO_TAKE => array(
        "name" => "specifyObjectToTake",
        "description" => clienttranslate('${actplayer} is choosing an object to take'),
        "descriptionmyturn" => clienttranslate('${you} must choose an object to take'),
        "type" => "activeplayer",
        "possibleactions" => array("takeObject"),
        "transitions" => array(TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,)
    ),

    STATE_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP => array(
        "name" => "specifyObjectsToSwap",
        "description" => clienttranslate('${actplayer} is choosing 2 objects to swap'),
        "descriptionmyturn" => clienttranslate('${you} must choose 2 objects to swap between different players'),
        "type" => "activeplayer",
        "possibleactions" => array("swapObjects"),
        "transitions" => array(TRANSITION_NEXT_PLAYER => STATE_NEXT_PLAYER,)
    ),

    STATE_NEXT_PLAYER => array(
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "args" => "argCardsCounters",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array(TRANSITION_PLAYER_TURN => STATE_PLAYER_TURN, TRANSITION_END_GAME => 99)
    ),
    /*
    Examples: 
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or pass'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or pass'),
        "type" => "activeplayer",
        "possibleactions" => array( "playCard", "pass" ),
        "transitions" => array( "playCard" => 2, "pass" => 2 )
    ), 

*/

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
