<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GetTheMacGuffin implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * getthemacguffin.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

if (!defined('DECK_LOC_DECK')) {
    // constants for deck locations
    define("DECK_LOC_DECK", "deck");
    define("DECK_LOC_IN_PLAY", "inPlay");
    define("DECK_LOC_DISCARD", "discard");
    define("DECK_LOC_HAND", "hand");

    // constants for notifications
    define("NOTIF_PLAYER_TURN", "playerTurn");
    define("NOTIF_UPDATE_SCORE", "updateScore");
    define("NOTIF_NEW_RIVER", "newRiver");
    define("NOTIF_HAND_CHANGE", "handChange");

    // constants for game states
    define("TRANSITION_PLAYER_TURN", "playerTurn");
}

class GetTheMacGuffin extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            //    "my_first_global_variable" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ));

        $this->deck = self::getNew("module.common.deck");
        $this->deck->init("deck");
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "getthemacguffin";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        $cards = array();
        foreach ($this->getCardsAvailable() as $name => $card) {
            $cards[] = array('type' => $name, 'type_arg' => $card["type"] === OBJ, 'nbr' => 1);
        }
        $this->deck->createCards($cards, DECK_LOC_DECK);
        $this->deck->shuffle(DECK_LOC_DECK);
        $number = min(5, round(25 / count($players), 0, PHP_ROUND_HALF_DOWN));

        foreach ($players as $player_id => $player) {
            $this->pickCardsAndNotifyPlayer($number, $player_id);
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        //in play cards for each player
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player) {
            $result['inPlay'][$player["player_id"]] = $this->deck->getCardsInLocation(DECK_LOC_IN_PLAY, $player["player_id"]);
        }

        $result['hand'] = $this->deck->getCardsInLocation(DECK_LOC_HAND, $current_player_id);
        $result['cardsAvailable'] = $this->getCardsAvailable();

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression

        return 0;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function publicGetCurrentPlayerId()
    {
        return self::getCurrentPlayerId();
    }

    function getCardsAvailable()
    {
        return $this->cards_description;
    }

    function concatenateFieldValues($arr, $field)
    {
        $concatenated = array();
        foreach ($arr as $element) {
            $concatenated[] = $element[$field];
        }
        return $concatenated;
    }

    function getPlayerName($player_id)
    {
        $sql = "select player_name from player where player_id=" . $player_id;
        return self::getUniqueValueFromDB($sql);
    }

    function updateScore($player_id, $score)
    {
        $sql = "UPDATE player set player_score=" . $score . " where player_id=" . $player_id;
        self::DbQuery($sql);
    }

    function updateScores($winner_id)
    {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            $score = 0;
            if ($player_id == $winner_id) {
                $score = 1;
            }
            $this->updateScore($player_id, $score);
        }
    }

    function pickCardsAndNotifyPlayer($nb, $player_id)
    {
        $cards = $this->deck->pickCards($nb, DECK_LOC_DECK, $player_id);

        // Notify player about his cards
        self::notifyPlayer($player_id, NOTIF_HAND_CHANGE, '', array('added' => $cards));
    }

    function stealCardFromHand($player_from, $player_to)
    {
        $cards = $this->deck->getCardsInLocation(DECK_LOC_DECK, $player_from);
        $i = rand(0, count($cards) - 1);
        $card_id = $cards[$i]["id"];
        $card = $this->deck->getCard($card_id);
        $this->deck->moveCard($card_id, DECK_LOC_DECK, $player_to);
        // Notify players about changes
        self::notifyPlayer($player_id, NOTIF_HAND_CHANGE, '', array('added' => [$card]));
        self::notifyPlayer($player_id, NOTIF_HAND_CHANGE, '', array('removed' => [$card]));
    }

    function stealCardFromDiscard($player_to)
    {
        $cards = $this->deck->getCardsInLocation(DECK_LOC_DISCARD);
        $i = rand(0, count($cards) - 1);
        $card_id = $cards[$i]["id"];
        $card = $this->deck->getCard($card_id);
        $this->deck->moveCard($card_id, DECK_LOC_DECK, $player_to);
        // Notify player about change
        self::notifyPlayer($player_id, NOTIF_HAND_CHANGE, '', array('added' => [$card]));
    }

    function stealObjectInPlay($player_to, $object_id)
    {
        $card = $this->deck->getCard($object_id);
        $this->deck->moveCard($card["id"], DECK_LOC_DECK, $player_to);
        // Notify players about changes
        //self::notifyPlayer($player_id, NOTIF_HAND_CHANGE, '', array('cards' => $cards));
    }

    function discardCardFromHand($card_id)
    {
        $card = $this->deck->getCard($card_id);
        if ($card["location"] === DECK_LOC_HAND) {
            $owner = $card["location_arg"];
            $this->deck->play($card_id);
            // Notify players about change
            self::notifyPlayer($owner, NOTIF_HAND_CHANGE, '', array('removed' => [$card]));
        } else {
            throw new BgaUserException(self::_("This card is not part of the hand of any player"));
        }
    }

    function swapHands($player_from, $player_to)
    {
        $from_cards = $this->deck->getCardsInLocation(DECK_LOC_DECK, $player_from);
        $to_cards = $this->deck->getCardsInLocation(DECK_LOC_DECK, $player_to);

        $this->deck->moveCards($this->concatenateFieldValues($from_cards, "id"), DECK_LOC_DECK, $player_to);
        $this->deck->moveCards($this->concatenateFieldValues($to_cards, "id"), DECK_LOC_DECK, $player_from);

        // Notify players about changes
        self::notifyPlayer($player_from, NOTIF_HAND_CHANGE, '', array('added' => [$this->deck->getCardsInLocation(DECK_LOC_HAND, $player_from)], 'reset' => true));
        self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, '', array('added' => [$this->deck->getCardsInLocation(DECK_LOC_HAND, $player_to)], 'reset' => true));
    }

    function playInterrogator()
    {
        $player_id = self::getActivePlayerId();
        $mcGuffin = $this->deck->getCardsOfType(MACGUFFIN);
        $seen = false;
        if ($mcGuffin["location"] == DECK_LOC_HAND && $mcGuffin["location_arg"] != $player_id) {
            $seen = true;
            self::notifyAllPlayers(
                NOTIF_REVELATION,
                clienttranslate('${player_name} reveals ${card_name}'),
                array(
                    'player_name' => $this->getPlayerName($player_id),
                    'card_name' => $this->cards_description[MACGUFFIN]["name"],
                    'i18n' => array('card_name'),
                )
            );
        }
        if (!$seen) {
            $mcGuffin = $this->deck->getCardsOfType(BACKUP_MACGUFFIN);
            if ($mcGuffin["location"] == DECK_LOC_HAND && $mcGuffin["location_arg"] != $player_id) {
                $seen = true;
                self::notifyAllPlayers(
                    NOTIF_REVELATION,
                    clienttranslate('${player_name} reveals ${card_name}'),
                    array(
                        'player_name' => $this->getPlayerName($player_id),
                        'card_name' => $this->cards_description[BACKUP_MACGUFFIN]["name"],
                        'i18n' => array('card_name'),
                    )
                );
            }
        }
    }

    function checkIfEndOfGame($player_id)
    {

        //victory
        $this->updateScores($player_id);
        $this->gamestate->nextState(TRANSITION_END_GAME);
    }

    function eliminatePlayerIfNeeded($player_id)
    {
        $inHand = $this->guestcards->getCardsInLocation(DECK_LOC_HAND, $player_id);
        $inPlay = $this->guestcards->getCardsInLocation(DECK_LOC_IN_PLAY, $player_id);
        if (count($inHand) + count($inPlay) == 0) {
            self::eliminatePlayer($player_id);
        }
    }

    /*
   * stEliminatePlayer: this function is called when the active player is eliminated
   */
    public function stEliminatePlayer()
    {
        $pId = $this->getActivePlayerId();
        $this->activeNextPlayer();
        PlayerManager::eliminate($pId);
        $this->stNextPlayer(false);
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in getthemacguffin.action.php)
    */


    function playCard($played_card_id, $effect_on_card_id, $effect_on_player_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('playCard');

        $player_id = self::getActivePlayerId();

        $played_card = $this->deck->getCard($played_card_id);
        $description = $this->cards_description[$played_card["type"]];

        if ($description["type"] === OBJ) {
            $this->deck->moveCard($played_card_id, DECK_LOC_IN_PLAY, $player_id);
        }

        // Notify all players about the card played
        self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $description["name"],
            'card' => $played_card,
            'toInPlay' => $description["type"] === OBJ,
            'i18n' => array('card_name'),
        ));

        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }






    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */
    /** Draws a dessert and a guest at the beginning of each turn for non zombie players. */
    function stNextPlayer()
    {
        $players = self::loadPlayersBasicInfos();
        $player_id = self::activeNextPlayer();

        if (!self::isZombie($player_id)) {
            //self::incStat(1, "turns_number", $player_id);
            self::giveExtraTime($player_id);
        }

        $this->gamestate->nextState(TRANSITION_PLAYER_TURN);
    }
    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */
    function isZombie($player_id)
    {
        return self::getUniqueValueFromDB("SELECT player_zombie FROM player WHERE player_id=" . $player_id);
    }

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //


    }
}
