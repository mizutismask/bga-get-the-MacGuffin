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
 * material.inc.php
 *
 * GetTheMacGuffin game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

if (!defined("OBJ")) {
  define("OBJ", "object");
  define("ACTION", "action");
}


$this->cards_description = array(
  "MACGUFFIN" => array(
    'name' => clienttranslate('The MacGuffin'),
    'nametr' => self::_('The MacGuffin'),
    'type' => OBJ,
    'description' => clienttranslate('If you have no cards in hand and no other Objects in play pick this up and put it down again as your play.'),
  ),
  "MONEY" => array(
    'name' => clienttranslate('The Money'),
    'nametr' => self::_('The Money'),
    'type' => OBJ,
    'description' => clienttranslate('If you have this in play, you can discard it to buy an Object in play, or a random card from another player’s hand, and add it to your hand.'),
  ),
  "CROWN" => array(
    'name' => clienttranslate('The Crown'),
    'nametr' => self::_('The Crown'),
    'type' => OBJ,
    'description' => clienttranslate('You are now the King or Queen of the game, aka the Monarch. Everyone must address you as "Your Majesty." You may pass on your turn, unless any player has a MacGuffin in play.'),
  ),
  "BACKUP_MACGUFFIN" => array(
    'name' => clienttranslate('Backup MacGuffin'),
    'nametr' => self::_('Backup MacGuffin'),
    'type' => OBJ,
    'description' => clienttranslate('In the absence of the real MacGuffin, this is considered to be the MacGuffin. If the real MacGuffin is not in play, and you have no cards in hand and no other Objects, pick this up and put it down again as your play.'),
  ),
  "SCISSORS" => array(
    'name' => clienttranslate('The Scissors'),
    'nametr' => self::_('The Scissors'),
    'type' => OBJ,
    'description' => clienttranslate('If another player has the Paper in play, you may force them to discard it as your turn action.'),
  ),
  "ROCK" => array(
    'name' => clienttranslate('The Rock'),
    'nametr' => self::_('The Rock'),
    'type' => OBJ,
    'description' => clienttranslate('If another player has the Scissors in play, you may force them to discard it as your turn action.'),
  ),
  "PAPER" => array(
    'name' => clienttranslate('The Paper'),
    'nametr' => self::_('The Paper'),
    'type' => OBJ,
    'description' => clienttranslate('If another player has the Rock in play, you may force them to discard it as your turn action.'),
  ),

  "MARSHALL" => array(
    'name' => clienttranslate('Grand Marshall'),
    'nametr' => self::_('Grand Marshall'),
    'type' => ACTION,
    'description' => clienttranslate('Wave at everyone as you play this card.'),
  ),
  "SHRUGMASTER" => array(
    'name' => clienttranslate('The Shrugmaster'),
    'nametr' => self::_('The Shrugmaster'),
    'type' => ACTION,
    'description' => clienttranslate('Shrug when you play this.'),
  ),
  "INTERROGATOR" => array(
    'name' => clienttranslate('The Interrogator'),
    'nametr' => self::_('The Interrogator'),
    'type' => ACTION,
    'description' => clienttranslate('If a player (othan than you) has the MacGuffin, they must reveal it. If the MacGuffin has not been seen and a player (other than you) has the Backup MacGuffin, they must reveal it.'),
  ),
  "THIEF" => array(
    'name' => clienttranslate('The Thief'),
    'nametr' => self::_('The Thief'),
    'type' => ACTION,
    'description' => clienttranslate('Steal a card at random from someone else’s hand, or an Object in front of another player, and add it to your hand.'),
  ),
  "TOMB_ROBBERS" => array(
    'name' => clienttranslate('Tomb Robbers'),
    'nametr' => self::_('Tomb Robbers'),
    'type' => ACTION,
    'description' => clienttranslate('Take a card at random from the pile of cards left out of the game and add it to your hand. Do NOT look at the other cards.'),
  ),
  "WHEEL_OF_FORTUNE" => array(
    'name' => clienttranslate('Wheel of fortune'),
    'nametr' => self::_('Wheel of fortune'),
    'type' => ACTION,
    'description' => clienttranslate('Everyone passes their hand of cards (but not the cards they have in play) to the player next to them. You choose the direction.'),
  ),
  "SWITCHEROO" => array(
    'name' => clienttranslate('The Switcheroo'),
    'nametr' => self::_('The Switcheroo'),
    'type' => ACTION,
    'description' => clienttranslate('Trade hands with another player. It’s possible that one or even both players may end up with no cards.'),
  ),
  "MERCHANT" => array(
    'name' => clienttranslate('The Merchant'),
    'nametr' => self::_('The Merchant'),
    'type' => ACTION,
    'description' => clienttranslate('If no one has an Object in play, do nothing. If only one player has an Object(s), move one Object from that player to you. If at least two players have Objects in play, any two of those players must make a one-for-one exchange of Objects as per your direction.'),
  ),
  "SPY" => array(
    'name' => clienttranslate('The Spy'),
    'nametr' => self::_('The Spy'),
    'type' => ACTION,
    'description' => clienttranslate('Play this card to look at the cards in another player’s hand.'),
  ),
  "FIST_OF_DOOM" => array(
    'name' => clienttranslate('The Fist of Doom'),
    'nametr' => self::_('The Fist of Doom'),
    'type' => ACTION,
    'description' => clienttranslate('Play this card to discard an Object. If no Objects are in play, discard a randomly chosen card from another player’s hand.'),
  ),
  "GARBAGE_COLLECTR" => array(
    'name' => clienttranslate('Garbage Collector'),
    'nametr' => self::_('Garbage Collector'),
    'type' => ACTION,
    'description' => clienttranslate('Look through the cards already in the discard pile, take one, and add it to your hand. You must allow the other players to see what you took.'),
  ),
  "CAN_I_USE_THAT" => array(
    'name' => clienttranslate('Can I Use That?'),
    'nametr' => self::_('Can I Use That?'),
    'type' => ACTION,
    'description' => clienttranslate('Take a card at random from someone else’s hand and immediately play it as if it were your own'),
  ),
  "ASSASSIN" => array(
    'name' => clienttranslate('The Assassin'),
    'nametr' => self::_('The Assassin'),
    'type' => ACTION,
    'description' => clienttranslate('If someone has the Crown in play, that player must immediatly discard it. If no one has the Crown in play, discard any Object in play or a randomly chosen card from another player’s hand.'),
  ),
  "VORTEX" => array(
    'name' => clienttranslate('Vortex'),
    'nametr' => self::_('Vortex'),
    'type' => ACTION,
    'description' => clienttranslate('Gather up all players’s hands (but not cards they have in play), shuffle the cards together, and deal them back out, starting with yourself. Do NOT look at the cards.'),
  ),
  "HIPPIE" => array(
    'name' => clienttranslate('The Hippie'),
    'nametr' => self::_('The Hippie'),
    'type' => ACTION,
    'description' => clienttranslate('Flash the peace sign when you play this card.'),
  ),
  "NOT_DEAD_YET" => array(
    'name' => clienttranslate('I’m Not Dead Yet'),
    'nametr' => self::_('I’m Not Dead Yet'),
    'type' => ACTION,
    'description' => clienttranslate('If you have no other cards (including Objects), take a card at random from another player’s hand and add it to your own. If no one has a hand, you may steal an Object (other than any MacGuffin) and add it to your hand.
    Nothing happens if you play this early.'),
  ),
  "FIRST_BUMP" => array(
    'name' => clienttranslate('First Bump'),
    'nametr' => self::_('First Bump'),
    'type' => ACTION,
    'description' => clienttranslate('First bump another player when you play this.'),
  ),
);
