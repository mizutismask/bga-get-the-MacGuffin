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
    define("NOTIF_HAND_CHANGE", "handChange");
    define("NOTIF_IN_PLAY_CHANGE", "inPlayChange");
    define("NOTIF_REVELATION", "revelation");
    define("NOTIF_SEE_SECRET_CARDS", "secretCards");
    define("NOTIF_PLAYING_ZONE_DETAIL_CHANGE", "playingZoneDetailChange");
    define("NOTIF_PLAYER_ELIMINATED", "gtmplayerEliminated");


    // constants for game states
    define("TRANSITION_PLAYER_TURN", "playerTurn");
    define('GS_SECRET_CARDS_LOCATION', "secretCardsLocation");
    define('GS_SECRET_CARDS_LOCATION_ARG', "secretCardsLocationArg");
    define('GS_SECRET_CARDS_SELECTION', "secretCardsSelection");
    define('GS_SECRET_CARDS_SHOW_SELECTED', "showSelectedSecretCard");
    define('GS_MANDATORY_CARD', "mandatory_card_id");

    define('GS_SECRET_CARDS_LOCATION_DISCARD', "1");
    define('GS_SECRET_CARDS_LOCATION_HAND', "2");
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
            GS_SECRET_CARDS_LOCATION => 10,
            GS_SECRET_CARDS_LOCATION_ARG => 11,
            GS_SECRET_CARDS_SELECTION => 12,
            GS_SECRET_CARDS_SHOW_SELECTED => 13,
            GS_MANDATORY_CARD => 14,
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
        self::setGameStateInitialValue(GS_SECRET_CARDS_LOCATION, 0);
        self::setGameStateInitialValue(GS_SECRET_CARDS_LOCATION_ARG, 0);
        self::setGameStateInitialValue(GS_SECRET_CARDS_SELECTION, 0);
        self::setGameStateInitialValue(GS_SECRET_CARDS_SHOW_SELECTED, 0);
        self::setGameStateInitialValue(GS_MANDATORY_CARD, 0);

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
        $number = min(5, round(23 / count($players), 0, PHP_ROUND_HALF_DOWN));

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
        $result['topOfDiscard'] = $this->deck->getCardOnTop(DECK_LOC_DISCARD);
        $result['secretCards'] = $this->getSecretCardsProperties();
        $result['counters'] = $this->argCardsCounters();
        $result['playingZoneDetail'] = $this->deck->getCardsInLocation(DECK_LOC_DISCARD);

        return $result;
    }

    function getSecretCardsProperties()
    {
        $current_player_id = self::getCurrentPlayerId();
        $active_player_id = self::getActivePlayerId();
        $secretCardsAction = array();
        $from = self::getGameStateValue(GS_SECRET_CARDS_LOCATION);
        if ($current_player_id === $active_player_id && $from) {
            //there is a seeing secret cards action running
            $id_from_player = self::getGameStateValue(GS_SECRET_CARDS_LOCATION_ARG);
            $location = null;
            switch ($from) {
                case GS_SECRET_CARDS_LOCATION_HAND:
                    $location = DECK_LOC_HAND;
                    break;
                case GS_SECRET_CARDS_LOCATION_DISCARD:
                    $location = DECK_LOC_DISCARD;
                    break;

                default:
                    throw new BgaVisibleSystemException("this kind of location is not supposed to be found when looking for secret cards");
            }
            $cards = $this->deck->getCardsInLocation($location, $id_from_player ? $id_from_player : null);
            $location_desc = $location === DECK_LOC_DISCARD ? self::_("discard") : $this->getPlayerName($id_from_player);

            $secretCardsAction['cards'] = $cards;
            $secretCardsAction['selection_required'] = (bool)self::getGameStateValue(GS_SECRET_CARDS_SELECTION);
            $secretCardsAction['location_desc'] = $location_desc;
        }
        return $secretCardsAction;
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
        $dealt = 23 - $this->deck->countCardInLocation(DECK_LOC_DECK);
        $played = $this->deck->countCardInLocation(DECK_LOC_DISCARD) + $this->deck->countCardInLocation(DECK_LOC_IN_PLAY) / 2;
        return $this->isEndOfGame() ? 100 : $played * 100 / $dealt;
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

    function updateScores($winners_id)
    {
        foreach ($winners_id as $player_id) {
            $this->updateScore($player_id, 1);
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
        $cards = $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_from);
        if ($cards) {
            $card_id = array_rand($cards);
            $card = $this->deck->getCard($card_id);
            $this->deck->moveCard($card_id, DECK_LOC_HAND, $player_to);
            // Notify players about changes
            self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, 'You stole ${card_name}', array(
                'added' => [$card],
                'card_name' => $this->cards_description[$card["type"]]["name"],
                'i18n' => array('card_name'),
            ));
            self::notifyPlayer($player_from, NOTIF_HAND_CHANGE, 'You’ve been stolen ${card_name}', array(
                'removed' => [$card],
                'card_name' => $this->cards_description[$card["type"]]["name"],
                'i18n' => array('card_name'),
            ));
            return $this->deck->getCard($card_id);
        }
        return null;
    }

    function stealCardFromDiscard($card, $player_to)
    {
        $this->deck->moveCard($card['id'], DECK_LOC_HAND, $player_to);
        // Notify player about change
        self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, '', array(
            'added' => [$card],

        ));
        self::notifyAllPlayers(NOTIF_PLAYING_ZONE_DETAIL_CHANGE, '${player_name} takes ${card_name} from the discard', array(
            'removed' => [$card],
            'card_name' => $this->cards_description[$card["type"]]["name"],
            'i18n' => array('card_name'),
            'player_name' => $this->getPlayerName($player_to),
        ));
    }

    /** MUST NOT be called with location HAND */
    function stealRandomCardFromLocation($player_to, $location)
    {
        $cards = $this->deck->getCardsInLocation($location);
        if ($cards) {
            $card_id = array_rand($cards);
            $card = $this->deck->getCard($card_id);
            $this->deck->moveCard($card_id, DECK_LOC_HAND, $player_to);
            // Notify player about change
            self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, 'You got ${card_name}', array(
                'added' => [$card],
                'card_name' => $this->cards_description[$card["type"]]["name"],
                'i18n' => array('card_name'),
            ));
        } else {
            self::notifyAllPlayers('msg', 'There is no card left', array());
        }
    }

    function stealObjectInPlay($player_to, $object_card)
    {
        $from = $object_card["location_arg"];
        $this->deck->moveCard($object_card["id"], DECK_LOC_HAND, $player_to);
        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '', array("player_id" => $from, 'removed' => [$object_card]));
        // Notify players about changes
        self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, 'You stole ${card_name}', array(
            'added' => [$object_card],
            'card_name' => $this->cards_description[$object_card["type"]]["name"],
            'i18n' => array('card_name'),
        ));
    }

    function discardCardFromHand($card_id)
    {
        $card = $this->deck->getCard($card_id);
        if ($card["location"] === DECK_LOC_HAND) {
            $owner = $card["location_arg"];
            $this->deck->playCard($card_id);
            // Notify players about change
            self::notifyPlayer($owner, NOTIF_HAND_CHANGE, '', array('removed' => [$card]));
            self::notifyAllPlayers(NOTIF_PLAYING_ZONE_DETAIL_CHANGE, '', array('added' => [$card]));
        } else {
            throw new BgaUserException(self::_("This card is not part of the hand of any player"));
        }
    }

    function discardRandomCardFromHand($player_id)
    {
        $cards = $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_id);
        $card_id = array_rand($cards);
        $this->discardCardFromHand($card_id);
        $card = $this->deck->getCard($card_id);

        self::notifyAllPlayers(NOTIF_PLAYING_ZONE_DETAIL_CHANGE, '${player_name} loses a random card from his hand', array(
            'player_name' => $this->getPlayerName($player_id),
            'added' => [$card],
        ));

        self::notifyPlayer($player_id, 'msg', 'You’ve been discarded ${card_name}', array(
            'card_name' => $this->cards_description[$card["type"]]["name"],
            'i18n' => array('card_name'),
        ));
    }

    function discardInPlayObject($object_card)
    {
        $toDiscard = $this->deck->getCard($object_card["id"]);
        $this->deck->playCard($object_card["id"]);
        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '${player_name} loses ${card_name}', array(
            'removed' => [$toDiscard],
            'discarded' => [$toDiscard],
            "player_id" => $toDiscard["location_arg"],
            'player_name' => $this->getPlayerName($toDiscard["location_arg"]),
            'card_name' => $this->cards_description[$toDiscard["type"]]["name"],
            'i18n' => array('card_name'),
        ));
    }

    function swapHands($player_from, $player_to)
    {
        $from_cards = $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_from);
        $to_cards = $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_to);

        $this->deck->moveCards($this->concatenateFieldValues($from_cards, "id"), DECK_LOC_HAND, $player_to);
        $this->deck->moveCards($this->concatenateFieldValues($to_cards, "id"), DECK_LOC_HAND, $player_from);

        // Notify players about changes
        self::notifyAllPlayers('msg', '${player_name} swaps hands with ${player_name2}', array(
            'player_name' => $this->getPlayerName($player_from),
            'player_name2' => $this->getPlayerName($player_to),
        ));
        self::notifyPlayer($player_from, NOTIF_HAND_CHANGE, '', array('added' => $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_from), 'reset' => true));
        self::notifyPlayer($player_to, NOTIF_HAND_CHANGE, '', array('added' => $this->deck->getCardsInLocation(DECK_LOC_HAND, $player_to), 'reset' => true));
    }

    function playInterrogator()
    {
        $seen = $this->reveal(MACGUFFIN);
        if (!$seen) {
            $this->reveal(BACKUP_MACGUFFIN);
        }
    }

    function reveal($macGuffinType)
    {
        $player_id = self::getActivePlayerId();
        $seen = false;
        $cards = $this->deck->getCardsOfType($macGuffinType);
        $mcGuffin = array_pop($cards);

        $message = null;
        $msg_args = array(
            'card_name' => $this->cards_description[$macGuffinType]["name"],
            'i18n' => array('card_name'),
            'type' => $macGuffinType,
            'ownerId' => 0,
        );

        if ($mcGuffin["location"] == DECK_LOC_DECK || $mcGuffin["location_arg"] == $player_id) {
            $message = 'No one knows where ${card_name} is…';
        } else if ($mcGuffin["location"] == DECK_LOC_HAND && $mcGuffin["location_arg"] != $player_id) {
            //in the hand of someone else
            $seen = true;
            $message = '${player_name} reveals ${card_name}';
            $msg_args['player_name'] = $this->getPlayerName($mcGuffin["location_arg"]);
            $msg_args['ownerId'] = $mcGuffin["location_arg"];
        } else if ($mcGuffin["location"] == DECK_LOC_IN_PLAY) {
            $seen = true;
            $message = '${player_name} already played ${card_name}';
            $msg_args['player_name'] = $this->getPlayerName($mcGuffin["location_arg"]);
            $msg_args['ownerId'] = $mcGuffin["location_arg"];
        } else if ($mcGuffin["location"] == DECK_LOC_DISCARD) {
            $seen = true;
            $message = '${card_name} is in the playing zone';
            $msg_args['inDiscard'] = true;
        }

        self::notifyAllPlayers(NOTIF_REVELATION, clienttranslate($message), $msg_args);
        return $seen;
    }

    function getPlayersInOrder()
    {
        $result = array();

        $players = self::loadPlayersBasicInfos();
        $next_player = self::getNextPlayerTable();
        $player_id = self::getCurrentPlayerId();

        // Check for spectator
        if (!key_exists($player_id, $players)) {
            $player_id = $next_player[0];
        }

        // Build array starting with current player
        for ($i = 0; $i < count($players); $i++) {
            $result[] = $player_id;
            $player_id = $next_player[$player_id];
        }

        //Need to remove eliminated players
        $eliminated = array_keys(array_filter($players, function ($player) {
            return $player["player_eliminated"];
        }));
        $result = array_diff($result, $eliminated);

        return $result;
    }

    function playVortex($player_id)
    {
        $hands = $this->deck->getCardsInLocation(DECK_LOC_HAND);
        shuffle($hands);
        $players = new ArrayObject($this->getPlayersInOrder());
        $playersIterator = $players->getIterator();
        foreach ($hands as $card) {
            $next_player = $playersIterator->current();
            $this->deck->moveCard($card["id"], DECK_LOC_HAND, $next_player);
            $playersIterator->next();
            if (!$playersIterator->valid()) {
                $playersIterator->rewind();
            }
        }

        $playersIterator->rewind();
        while ($playersIterator->valid()) {
            self::notifyPlayer($playersIterator->current(), NOTIF_HAND_CHANGE, 'Cards have been reshuffled', array(
                'reset' => true,
                'added' => $this->deck->getCardsInLocation(DECK_LOC_HAND, $playersIterator->current())
            ));
            $playersIterator->next();
        }
    }

    function playShifumi($player_id, $weakCard)
    {
        $cards = array_values($this->deck->getCardsOfType($weakCard));
        $toDiscard = array_pop($cards);
        if ($toDiscard["location"] === DECK_LOC_IN_PLAY && $toDiscard["location_arg"] != $player_id) {
            $this->deck->playCard($toDiscard["id"]);
            self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '${player_name} loses ${card_name}', array(
                'removed' => [$toDiscard],
                'discarded' => [$toDiscard],
                "player_id" => $toDiscard["location_arg"],
                'player_name' => $this->getPlayerName($toDiscard["location_arg"]),
                'card_name' => $this->cards_description[$weakCard]["name"],
                'i18n' => array('card_name'),
            ));
        } else {
            throw new BgaUserException("You can NOT use this card if the weaker shifumi card has not been played by another player");
        }
    }

    function playMoney($player_id, $played_card, $effect_on_card, $effect_on_player_id)
    {
        if ((!$effect_on_card && !$effect_on_player_id) || $effect_on_card && $effect_on_player_id) {
            throw new BgaUserException("You have to select an object to steal or a player’s hand.");
        }
        if ($effect_on_card) {
            $this->stealObjectInPlay($player_id, $effect_on_card);
        } else if ($effect_on_player_id) {
            $this->stealCardFromHand($effect_on_player_id, $player_id);
        }

        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, "", array(
            'removed' => [$played_card],
            'discarded' => [$played_card],
            "player_id" => $player_id,
        ));

        $this->deck->playCard($played_card["id"]);
    }

    function playAssassin($player_id, $effect_on_card, $effect_on_player_id)
    {
        $cards = array_values($this->deck->getCardsOfType(CROWN));
        $toDiscard = array_pop($cards);
        if ($toDiscard["location"] === DECK_LOC_IN_PLAY) {
            $this->deck->playCard($toDiscard["id"]);
            self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '${player_name} loses ${card_name}', array(
                'removed' => [$toDiscard],
                'discarded' => [$toDiscard],
                "player_id" => $toDiscard["location_arg"],
                'player_name' => $this->getPlayerName($toDiscard["location_arg"]),
                'card_name' => $this->cards_description[CROWN]["name"],
                'i18n' => array('card_name'),
            ));
        } else {
            //discards an object in play or a random card from hand
            if (!$effect_on_card && !$effect_on_player_id)
                throw new BgaUserException("When the crown is not in play, you have to choose an object in play or a player’s hand to use the assassin");

            if ($effect_on_card && $effect_on_player_id)
                throw new BgaUserException("You have to choose an object in play OR a player’s hand");

            if ($effect_on_card) {
                $this->discardInPlayObject($effect_on_card);
            }

            if ($effect_on_player_id) {
                $this->discardRandomCardFromHand($effect_on_player_id);
            }
        }
    }

    function playFistOfDoom($effect_on_card, $effect_on_player_id)
    {
        if ($effect_on_card && $effect_on_card["location"] === DECK_LOC_IN_PLAY) {
            $this->discardInPlayObject($effect_on_card);
        } else if ($effect_on_player_id && $this->deck->countCardInLocation(DECK_LOC_IN_PLAY) == 0) {
            $this->discardRandomCardFromHand($effect_on_player_id);
        } else {
            throw new BgaUserException("You can discard a card from another player’s hand only if no objects are in play. To play this card, select an object instead.");
        }
    }

    function playNotDeadYet($player_id, $effect_on_card, $effect_on_player_id)
    {
        if ($this->hasNoCardsInHand($player_id) && $this->hasNoCardsInPlay($player_id)) {

            if ($effect_on_player_id && !$this->hasNoCardsInHand($effect_on_player_id)) {
                $this->stealCardFromHand($effect_on_player_id, $player_id);
            } else if ($this->no_one_has_a_hand_other_than($player_id) && $effect_on_card) {
                if ($this->inPlayObjectsAreMacGuffins()) {
                } else {
                    if (($effect_on_card["type"] === MACGUFFIN) || ($effect_on_card["type"] === BACKUP_MACGUFFIN)) {
                        throw new BgaUserException("You can NOT steal a sort of MacGuffin");
                    }
                    $this->stealObjectInPlay($player_id, $effect_on_card);
                }
            } else {
                throw new BgaUserException("Select a player to steal a card from his hand. If no one has a hand, you can steal an object.");
            }
        }
    }

    function inPlayObjectsAreMacGuffins()
    {
        $cards = $this->deck->getCardsInLocation(DECK_LOC_IN_PLAY);
        $macguffins = true;
        foreach ($cards as $card) {
            if ($card["type"] != MACGUFFIN && $card["type"] != BACKUP_MACGUFFIN) {
                return false;
            }
        }
        return $macguffins;
    }

    function no_one_has_a_hand_other_than($other_than_player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $noone = true;
        foreach ($players as $player) {
            $player_id = $player["player_id"];
            if ($player_id != $other_than_player_id && !$this->hasNoCardsInHand($player_id)) {
                $noone = false;
            }
        }
        return $noone;
    }

    function someone_has_a_hand_other_than($other_than_player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $someone = false;
        foreach ($players as $player) {
            $player_id = $player["player_id"];
            if ($player_id != $other_than_player_id && !$this->hasNoCardsInHand($player_id)) {
                $someone = true;
                break;
            }
        }
        return $someone;
    }

    function someone_has_in_play_cards_other_than($other_than_player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $someone = false;
        foreach ($players as $player) {
            $player_id = $player["player_id"];
            if ($player_id != $other_than_player_id && !$this->hasNoCardsInPlay($player_id)) {
                $someone = true;
                break;
            }
        }
        return $someone;
    }

    function playSpy($player_id, $effect_on_player_id)
    {
        if ($effect_on_player_id) {
            self::setGameStateValue(GS_SECRET_CARDS_LOCATION, GS_SECRET_CARDS_LOCATION_HAND);
            self::setGameStateValue(GS_SECRET_CARDS_LOCATION_ARG, $effect_on_player_id);
            self::setGameStateValue(GS_SECRET_CARDS_SELECTION, 0);
            self::setGameStateValue(GS_SECRET_CARDS_SHOW_SELECTED, 0);

            self::notifyPlayer($player_id, NOTIF_SEE_SECRET_CARDS, '', array(
                'args' => $this->getSecretCardsProperties(),
            ));

            self::notifyAllPlayers("msg", clienttranslate('${player_name} spies on ${player_name2}'), array(
                'player_name' => self::getPlayerName($player_id),
                'player_name2' => self::getPlayerName($effect_on_player_id),
            ));
        } else {
            throw new BgaUserException("You have to select a player to apply this effect on him");
        }
        return TRANSITION_SEE_SECRET_CARDS;
    }

    function playGarbageCollector($player_id, $effect_on_card)
    {
        $this->stealCardFromDiscard($effect_on_card, $player_id);
    }

    function playMerchant($player_id)
    {
        $count_by_player_id = $this->deck->countCardsByLocationArgs(DECK_LOC_IN_PLAY);
        //if one player has objects
        if (count($count_by_player_id) == 1) {
            //if he has only one card, take it
            $playersWithOneCard = array_filter($count_by_player_id, function ($count, $player) {
                return $count == "1";
            }, ARRAY_FILTER_USE_BOTH);

            if (count($playersWithOneCard) == 1) {
                $playersWithOneCardIds = array_keys($playersWithOneCard);
                $from = array_pop($playersWithOneCardIds);
                $cards = $this->deck->getCardsInLocation(DECK_LOC_IN_PLAY, $from);
                $card = array_pop($cards);
                $this->deck->moveCard($card["id"], DECK_LOC_IN_PLAY, $player_id);
                self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '', array("player_id" => $from, 'removed' => [$card]));

                self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} trades ${card_name} from ${player_name2}'), array(
                    'player_id' => $player_id,
                    'player_name' => self::getActivePlayerName(),
                    'player_name2' => self::getPlayerName($from),
                    'card_name' =>  $this->cards_description[$card["type"]]["name"],
                    'card' => $card,
                    'toInPlay' => true,
                    'i18n' => array('card_name'),
                ));
            } else {
                //other specify which one
                return TRANSITION_SPECIFY_IN_PLAY_OBJECT_TO_TAKE;
            }
        } else if (count($count_by_player_id) >= 2) {
            //specify swap
            return TRANSITION_SPECIFY_IN_PLAY_OBJECTS_TO_SWAP;
        }
    }

    function takeObject($object_id)
    {
        self::checkAction('takeObject');

        $player_id = self::getActivePlayerId();
        $card = $this->deck->getCard($object_id);
        $from = $card["location_arg"];
        $this->deck->moveCard($object_id, DECK_LOC_IN_PLAY, $player_id);
        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '', array("player_id" => $from, 'removed' => [$card]));
        self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} trades ${card_name} from ${player_name2}'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'player_name2' => self::getPlayerName($from),
            'card_name' =>  $this->cards_description[$card["type"]]["name"],
            'card' => $card,
            'toInPlay' => true,
            'i18n' => array('card_name'),
        ));

        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }

    function swapObjects($object_id_1, $object_id_2)
    {
        self::checkAction('swapObjects');

        $card1 = $this->deck->getCard($object_id_1);
        $card2 = $this->deck->getCard($object_id_2);
        $from1 = $card1["location_arg"];
        $from2 = $card2["location_arg"];

        $this->deck->moveCard($object_id_1, DECK_LOC_IN_PLAY, $from2);
        $this->deck->moveCard($object_id_2, DECK_LOC_IN_PLAY, $from1);

        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '', array("player_id" => $from1, 'removed' => [$card1]));
        self::notifyAllPlayers(NOTIF_IN_PLAY_CHANGE, '', array("player_id" => $from2, 'removed' => [$card2]));
        self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} and ${player_name2} swap ${card_name1} and ${card_name2}'), array(
            'player_id' => $from2,
            'player_name' => self::getPlayerName($from1),
            'player_name2' => self::getPlayerName($from2),
            'card_name1' =>  $this->cards_description[$card1["type"]]["name"],
            'card_name2' =>  $this->cards_description[$card2["type"]]["name"],
            'card' => $card1,
            'toInPlay' => true,
            'i18n' => array('card_name1', 'card_name2'),
        ));
        self::notifyAllPlayers("cardPlayed", "", array(
            'player_id' => $from1,
            'player_name1' => self::getPlayerName($from1),
            'player_name2' => self::getPlayerName($from2),
            'card_name1' =>  $this->cards_description[$card1["type"]]["name"],
            'card_name2' =>  $this->cards_description[$card2["type"]]["name"],
            'card' => $card2,
            'toInPlay' => true,
        ));

        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }

    function playCanIUseThat($player_id, $effect_on_player_id)
    {
        if ($this->someone_has_a_hand_other_than($player_id)) {
            if (!$effect_on_player_id) {
                throw new BgaUserException("You have to select a player to take a card from.");
            }
            $card = $this->stealCardFromHand($effect_on_player_id, $player_id);
            if ($card) {
                self::setGameStateValue(GS_MANDATORY_CARD, $card["id"]);

                self::notifyAllPlayers("msg", '${player_name} takes the ${card_name}', array(
                    'player_name' => $this->getPlayerName($player_id),
                    'card_name' => $this->cards_description[$card["type"]]["name"],
                    'i18n' => array('card_name'),
                ));
                return TRANSITION_MANDATORY_CARD;
            }
        }
    }

    function playActionCard($played_card, $description, $effect_on_card = null, $effect_on_player_id = null)
    {
        $player_id = self::getActivePlayerId();
        $this->deck->playCard($played_card["id"]);
        switch ($played_card["type"]) {
            case MARSHALL:
                //nothing
                break;
            case SHRUGMASTER:
                //nothing
                break;
            case INTERROGATOR:
                $this->playInterrogator();
                break;
            case THIEF:
                if (!$effect_on_player_id && !$effect_on_card && ($this->someone_has_a_hand_other_than($player_id) || $this->someone_has_in_play_cards_other_than($player_id))) {
                    throw new BgaUserException("You have to select an object in play or someone else’s hand.");
                }
                if ($effect_on_card) {
                    $this->stealObjectInPlay($player_id, $effect_on_card);
                } else if ($effect_on_player_id) {
                    $this->stealCardFromHand($effect_on_player_id, $player_id);
                }
                break;
            case TOMB_ROBBERS:
                $this->stealRandomCardFromLocation($player_id, DECK_LOC_DECK);
                break;
            case WHEEL_OF_FORTUNE:
                //nothing is done here, but when you confirm clockwise or not
                return TRANSITION_SPECIFY_CLOCKWISE;
            case SWITCHEROO:
                if (!$effect_on_player_id) {
                    throw new BgaUserException("You have to select a player to switch hand with.");
                }
                $this->swapHands($player_id, $effect_on_player_id);
                break;
            case MERCHANT:
                return $this->playMerchant($player_id);
            case SPY:
                return $this->playSpy($player_id, $effect_on_player_id);
            case FIST_OF_DOOM:
                $this->playFistOfDoom($effect_on_card, $effect_on_player_id);
                break;
            case GARBAGE_COLLECTR:
                if (!$effect_on_card && $this->deck->countCardInLocation(DECK_LOC_DISCARD) > 0) {
                    throw new BgaUserException("You have to select one card from the discard.");
                }
                if ($effect_on_card["location"] != DECK_LOC_DISCARD) {
                    throw new BgaUserException("You can take a card from the discard only.");
                }
                if ($effect_on_card) {
                    $this->playGarbageCollector($player_id, $effect_on_card);
                }
                break;
            case CAN_I_USE_THAT:
                return $this->playCanIUseThat($player_id, $effect_on_player_id);
            case ASSASSIN:
                $this->playAssassin($player_id, $effect_on_card, $effect_on_player_id);
                break;
            case VORTEX:
                $this->playVortex($player_id);
                break;
            case HIPPIE:
                //nothing
                break;
            case NOT_DEAD_YET:
                $this->playNotDeadYet($player_id, $effect_on_card, $effect_on_player_id);
                break;
            default:
                # code...
                break;
        }
        return null;
    }

    function useObjectCard($played_card, $description, $effect_on_card = null, $effect_on_player_id = null)
    {
        $player_id = self::getActivePlayerId();
        switch ($played_card["type"]) {
            case MACGUFFIN:
                if (!$this->hasNoCardsInHand($player_id) || count($this->deck->getCardsInLocation(DECK_LOC_IN_PLAY, $player_id)) > 1) {
                    throw new BgaUserException("You can NOT use the MacGuffin if you have cards in your hand or other Objects to play");
                }
                break;
            case MONEY:
                $this->playMoney($player_id, $played_card, $effect_on_card, $effect_on_player_id);
                break;
            case CROWN:
                if ($this->isTypeInPlay(MACGUFFIN) || $this->isTypeInPlay(BACKUP_MACGUFFIN)) {
                    throw new BgaUserException("You can NOT pass if a MacGuffin is in play");
                }
                break;
            case BACKUP_MACGUFFIN:
                if ($this->isTypeInPlay(MACGUFFIN) || !$this->hasNoCardsInHand($player_id) || count($this->deck->getCardsInLocation(DECK_LOC_IN_PLAY, $player_id)) > 1) {
                    throw new BgaUserException("You can NOT use the MacGuffin if the real MacGuffin is in play, or if you have cards in your hand or other Objects to play");
                }
                break;
            case SCISSORS:
                $this->playShifumi($player_id, PAPER);
                break;
            case ROCK:
                $this->playShifumi($player_id, SCISSORS);
                break;
            case PAPER:
                $this->playShifumi($player_id, ROCK);
                break;
            default:
                # code...
                break;
        }
    }

    function hasNoCardsInHand($player_id)
    {
        return count($this->deck->getCardsInLocation(DECK_LOC_HAND, $player_id)) == 0;
    }

    function hasNoCardsInPlay($player_id)
    {
        return count($this->deck->getCardsInLocation(DECK_LOC_IN_PLAY, $player_id)) == 0;
    }

    function eliminatePlayersIfNeeded()
    {
        $players = self::loadPlayersBasicInfos();
        $newEliminated = array();
        foreach ($players as $player) {
            $player_id = $player["player_id"];
            if (!$player["player_eliminated"]) {
                if ($this->hasNoCardsInHand($player_id) && $this->hasNoCardsInPlay($player_id)) {
                    $newEliminated[] = $player_id;
                    self::eliminatePlayer($player_id);
                    self::notifyAllPlayers(NOTIF_PLAYER_ELIMINATED, '', array("player_id" => $player_id));
                }
            }
        }
        $stillAlivePlayers = $this->getStillAlivePlayers();
        $stillAliveCount = count($stillAlivePlayers);
        if ($stillAliveCount == 1) {
            //end of game
            $last = array_pop($stillAlivePlayers);
            $this->updateScores([$last["player_id"]]);
        } else if ($stillAliveCount == 0) {
            //end of game
            $this->updateScores($newEliminated);
        }
        return $this->isEndOfGame();
    }

    function getStillAlivePlayers()
    {
        $players = self::loadPlayersBasicInfos();
        $stillAlivePlayers = array_filter($players, function ($p) {
            return !$p["player_eliminated"];
        });
        return $stillAlivePlayers;
    }

    function getStillAlivePlayersCount()
    {
        $stillAlivePlayers = $this->getStillAlivePlayers();
        return count($stillAlivePlayers);
    }

    function isEndOfGame()
    {
        return $this->getStillAlivePlayersCount() < 2;
    }

    function isInPlay($card_id)
    {
        $card = $this->deck->getCard($card_id);
        return $card["location"] === DECK_LOC_IN_PLAY;
    }

    function isTypeInPlay($card_type)
    {
        $cards = $this->deck->getCardsOfType($card_type);
        $card = array_pop($cards);
        return $card["location"] === DECK_LOC_IN_PLAY;
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
        $mandatory_id = self::getGameStateValue(GS_MANDATORY_CARD);
        if ($mandatory_id && $played_card_id != $mandatory_id) {
            throw new BgaUserException("You have to play the card you’ve just stolen");
        }

        $player_id = self::getActivePlayerId();

        $played_card = $this->deck->getCard($played_card_id);
        $description = $this->cards_description[$played_card["type"]];

        $effect_on_card = null;
        if ($effect_on_card_id) {
            $effect_on_card = $this->deck->getCard($effect_on_card_id);
        }

        $uses = false;
        $transition = null;
        if ($description["type"] === OBJ) {
            if ($this->isInPlay($played_card_id)) {
                //use object
                $uses = true;
                $this->useObjectCard($played_card, $description, $effect_on_card, $effect_on_player_id);
            } else {
                //put object in play
                $this->deck->moveCard($played_card_id, DECK_LOC_IN_PLAY, $player_id);
            }
        } else {
            //use action
            $transition = $this->playActionCard($played_card, $description, $effect_on_card, $effect_on_player_id);
        }

        // Notify all players about the card played
        self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} ${plays} ${card_name}'), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $description["name"],
            'card' => $played_card,
            'plays' => $uses ? self::_("uses") : self::_("plays"),
            'toInPlay' => $description["type"] === OBJ && !($played_card["type"] === MONEY && $uses), //objects all go to inplay except for money who is discarded after its first use
            'i18n' => array('card_name', 'plays'),
        ));
        if ($transition) {
            $this->gamestate->nextState($transition);
        } else {
            $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
        }
    }

    function discard($played_card_id, $effect_on_card_id, $effect_on_player_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('discard');

        $player_id = self::getActivePlayerId();

        $played_card = $this->deck->getCard($played_card_id);
        $description = $this->cards_description[$played_card["type"]];

        $this->deck->playCard($played_card_id);

        // Notify all players about the card discarded
        self::notifyAllPlayers("cardPlayed", clienttranslate('${player_name} discards ${card_name}'), array(
            'player_id' => $player_id,
            'discarded' => true,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $description["name"],
            'card' => $played_card,
            'toInPlay' => false,
            'i18n' => array('card_name'),
        ));

        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }

    function playClockwise($clockwise)
    {
        $order = $this->getPlayersInOrder();
        if ($clockwise) {
            $order = array_reverse($order);
        }

        $previous = null;
        $first_hand = null;
        $first_hand_player = null;
        foreach ($order as $player) {
            if (!$previous) {
                //save first player hand
                $first_hand = $this->deck->getCardsInLocation(DECK_LOC_HAND, $player);
                $first_hand_player = $player;
            } else {
                //player gives his hand to the previous
                $this->swapHands($player, $previous);
            }
            $previous = $player;
        }
        //put saved cards to the last player
        foreach ($first_hand as $card) {
            $this->deck->moveCard($card["id"], DECK_LOC_HAND, $player);
        }
        self::notifyPlayer($player, NOTIF_HAND_CHANGE, '', array('added' => $this->deck->getCardsInLocation(DECK_LOC_HAND, $player), 'reset' => true));
        self::notifyAllPlayers('msg', '${player_name} swaps hands with ${player_name2}', array(
            'player_name' => $this->getPlayerName($first_hand_player),
            'player_name2' => $this->getPlayerName($player),
        ));
        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }

    function seenSecretCardsAction($selected_card_id)
    {
        if (self::getGameStateValue(GS_SECRET_CARDS_SELECTION) && !$selected_card_id) {
            throw new BgaUserException("You have to select a card");
        };
        $location = self::getGameStateValue(GS_SECRET_CARDS_LOCATION);
        if ($selected_card_id) {
            $show = self::getGameStateValue(GS_SECRET_CARDS_SHOW_SELECTED);
            $player_id = self::getActivePlayerId();
            if ($location === GS_SECRET_CARDS_LOCATION_HAND) {
                $this->stealCardFromHand(self::getGameStateValue(GS_SECRET_CARDS_LOCATION_ARG), $player_id);
            } else {
                throw new BgaVisibleSystemException("this kind of location is not supposed to be found when seeing secret cards");
            }
            if ($show) {
                $card = $this->deck->getCard($selected_card_id);
                $description = $this->cards_description[$card["type"]];
                self::notifyAllPlayers("msg", clienttranslate('${player_name} takes ${card_name}'), array(
                    'player_name' => self::getActivePlayerName(),
                    'card_name' => $description["name"],
                    'i18n' => array('card_name'),
                ));
            }
        }
        self::setGameStateValue(GS_SECRET_CARDS_LOCATION, 0);
        self::setGameStateValue(GS_SECRET_CARDS_LOCATION_ARG, 0);
        self::setGameStateValue(GS_SECRET_CARDS_SELECTION, 0);
        self::setGameStateValue(GS_SECRET_CARDS_SHOW_SELECTED, 0);
        $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
    }




    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    function argSeeSecretCards()
    {
        return array(
            'selection_required' => (bool)self::getGameStateValue(GS_SECRET_CARDS_SELECTION),
        );
    }

    public function argMandatoryCard()
    {
        return array(
            'mandatory_card_id' => self::getGameStateValue(GS_MANDATORY_CARD),
        );
    }

    function argCardsCounters()
    {
        $players = self::getObjectListFromDB("SELECT player_id id FROM player", true);
        $counters = array();
        for ($i = 0; $i < ($this->getPlayersNumber()); $i++) {
            $counters['cards_count_' . $players[$i]] = array('counter_name' => 'cards_count_' . $players[$i], 'counter_value' => 0);
        }
        $cards_in_hand = $this->deck->countCardsByLocationArgs(DECK_LOC_HAND);
        foreach ($cards_in_hand as $player_id => $cards_nbr) {
            $counters['cards_count_' . $player_id]['counter_value'] = $cards_nbr;
        }

        $counters['tomb_count']['counter_name'] = 'tomb_count';
        $counters['tomb_count']['counter_value'] = $this->deck->countCardInLocation(DECK_LOC_DECK);

        return $counters;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */
    public function stNextPlayer()
    {
        $player_id = $this->activeNextPlayer();
        self::setGameStateValue(GS_MANDATORY_CARD, 0);

        $endOfGame = $this->eliminatePlayersIfNeeded();

        if ($endOfGame) {
            $this->gamestate->nextState(TRANSITION_END_GAME);
        } else {
            if (!self::isZombie($player_id)) {
                self::giveExtraTime($player_id);
            }
            $this->gamestate->nextState(TRANSITION_PLAYER_TURN);
        }
    }

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
                case "playerTurn":
                    $this->gamestate->nextState(TRANSITION_NEXT_PLAYER);
                    break;
            }
            return;
        }

        throw new BgaUserException("Zombie mode not supported at this game state: " . $statename);
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
