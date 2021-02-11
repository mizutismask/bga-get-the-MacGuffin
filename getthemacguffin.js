/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GetTheMacGuffin implementation : © Séverine Kamycki severinek@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * getthemacguffin.js
 *
 * GetTheMacGuffin user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
    function (dojo, declare) {
        return declare("bgagame.getthemacguffin", ebg.core.gamegui, {
            constructor: function () {
                console.log('getthemacguffin constructor');

                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;

                this.cardwidth = 200;
                this.cardheight = 311;
                this.image_items_per_row = 10;
                this.cards_img = 'img/cards/cards200x311.jpg';
                this.icons_img = 'img/icons_50.png';
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                console.log("gamedatas ", gamedatas);

                this.cardsAvailable = gamedatas.cardsAvailable;
                // Setting up player boards
                for (var player_id in gamedatas.players) {
                    var player = gamedatas.players[player_id];

                    // TODO: Setting up players boards if needed
                }

                //---------- Player hand setup
                this.playerHand = new ebg.stock(); // new stock object for hand
                this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);//myhand is the div where the card is going
                this.playerHand.image_items_per_row = this.image_items_per_row;
                this.playerHand.item_margin = 6;
                this.playerHand.apparenceBorderWidth = '2px';
                this.playerHand.setSelectionAppearance('class');
                this.playerHand.setSelectionMode(1);
                this.playerHand.onItemCreate = dojo.hitch(this, 'createTooltip');

                // Create cards types:
                var i = 0;
                for (var name in this.cardsAvailable) {
                    var card = this.cardsAvailable[name];
                    i++;
                    // Build card type id
                    this.playerHand.addItemType(name, 0, g_gamethemeurl + this.cards_img, i);
                }

                for (var card_id in gamedatas.hand) {
                    var card = gamedatas.hand[card_id];
                    //console.log("ajout dans la main de la carte id/type/type arg :" + card.id + " " + card.type + " " + card.type_arg);
                    this.playerHand.addToStockWithId(card.type, card.id);
                }



                //-----------cards in play setup + options setup for each player
                this.inPlayStocksByPlayerId = [];
                this.optionsByPlayerId = [];
                for (var player_id in gamedatas.inPlay) {

                    var playerInPlayCards = new ebg.stock();
                    playerInPlayCards.setSelectionMode(1);
                    playerInPlayCards.setSelectionAppearance('class');
                    playerInPlayCards.autowidth = true;
                    playerInPlayCards.create(this, $('cards_in_play_' + player_id), this.cardwidth, this.cardheight);
                    playerInPlayCards.image_items_per_row = this.image_items_per_row;
                    playerInPlayCards.onItemCreate = dojo.hitch(this, 'createTooltip');

                    // Create cards types:
                    var i = 0;
                    for (var name in this.cardsAvailable) {
                        var card = this.cardsAvailable[name];
                        i++;
                        // Build card type id
                        playerInPlayCards.addItemType(name, 0, g_gamethemeurl + this.cards_img, i);
                    }

                    //adds already played objects
                    var cards = gamedatas.inPlay[player_id];
                    for (var card_id in cards) {
                        var card = cards[card_id];
                        playerInPlayCards.addToStockWithId(card.type, card.id);
                    }
                    this.inPlayStocksByPlayerId[player_id] = playerInPlayCards;

                    //adds options panel for others
                    if (this.player_id != player_id) {
                        var stock = new ebg.stock();
                        stock.setSelectionMode(1);
                        stock.create(this, $('options_' + player_id), 50, 50);
                        stock.image_items_per_row = 2;
                        stock.apparenceBorderWidth = '2px';
                        stock.setSelectionAppearance('class');

                        stock.addItemType("hand", 0, g_gamethemeurl + this.icons_img, 0);
                        stock.addToStockWithId("hand", 0);
                        dojo.connect(stock, 'onChangeSelection', this, 'onSelectOption');

                        this.optionsByPlayerId[player_id] = stock;
                    }
                }

                dojo.connect(this.inPlayStocksByPlayerId[this.player_id], 'onChangeSelection', this, 'onSelectInPlayCard');


                //discard_pile
                this.discard = new ebg.stock(); // new stock object for playing zone
                this.discard.create(this, $('discard_pile'), this.cardwidth, this.cardheight);//discard_pile is the div where the card is going
                this.discard.image_items_per_row = this.image_items_per_row;
                this.discard.setSelectionMode(0);
                this.discard.autowidth = true;
                this.discard.onItemCreate = dojo.hitch(this, 'createTooltip');

                // Create cards types:
                var i = 0;
                for (var name in this.cardsAvailable) {
                    var card = this.cardsAvailable[name];
                    i++;
                    // Build card type id
                    this.discard.addItemType(name, 0, g_gamethemeurl + this.cards_img, i);
                }

                if (gamedatas.topOfDiscard) {
                    this.discard.addToStockWithId(gamedatas.topOfDiscard.type, gamedatas.topOfDiscard.id);
                }

                //secret zone
                this.secretZone = new ebg.stock(); // new stock object for seeing secret cards
                this.secretZone.create(this, $('secret_zone'), this.cardwidth, this.cardheight);//secret_zone is the div where the card is going
                this.secretZone.image_items_per_row = this.image_items_per_row;
                this.secretZone.setSelectionMode(0);
                this.secretZone.setSelectionAppearance('class');
                this.secretZone.onItemCreate = dojo.hitch(this, 'createTooltip');

                // Create cards types:
                var i = 0;
                for (var name in this.cardsAvailable) {
                    var card = this.cardsAvailable[name];
                    i++;
                    // Build card type id
                    this.secretZone.addItemType(name, 0, g_gamethemeurl + this.cards_img, i);
                }

                this.displaySecretCards(gamedatas.secretCards);

                dojo.style("cards_count_" + this.player_id, "display", "none");//do not display my counter
                this.updateCounters(gamedatas.counters);

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);
                console.log('args', args);

                switch (stateName) {
                    case 'seeSecretCards':
                        if (this.isCurrentPlayerActive()) {
                            this.playerHand.setSelectionMode(0);
                            this.makeInPlayPanelsSelectable(false);
                        }
                        break;
                    case 'playerTurn':
                        if (this.isCurrentPlayerActive()) {
                            var mcgfnId = this.getStockCardIdOfType(this.inPlayStocksByPlayerId[this.player_id], "MACGUFFIN");
                            if (mcgfnId && (this.inPlayStocksByPlayerId[this.player_id].count() > 1 || this.playerHand.count() > 0)) {
                                var htmlId = "cards_in_play_" + this.player_id + "_item_" + mcgfnId;
                                dojo.removeClass(htmlId, 'selectable');
                                dojo.addClass(htmlId, 'unselectable');
                            }
                            var mcgfnId = this.getStockCardIdOfType(this.inPlayStocksByPlayerId[this.player_id], "BACKUP_MACGUFFIN");
                            if (mcgfnId && (this.inPlayStocksByPlayerId[this.player_id].count() > 1 || this.playerHand.count() > 0)) {
                                var htmlId = "cards_in_play_" + this.player_id + "_item_" + mcgfnId;
                                dojo.removeClass(htmlId, 'selectable');
                                dojo.addClass(htmlId, 'unselectable');
                            }
                        }
                        break;
                    case 'mandatoryCard':
                        var mandatorCardId = args.args.mandatory_card_id;
                        if (this.isCurrentPlayerActive()) {
                            this.inPlayStocksByPlayerId[this.player_id].setSelectionMode(0);
                            dojo.query("#myhand .stockitem").removeClass('selectable').addClass('unselectable');
                            dojo.removeClass("myhand_item_" + mandatorCardId, 'unselectable');
                            dojo.addClass("myhand_item_" + mandatorCardId, 'selectable');
                            break;
                        }
                    case 'nextPlayer':
                        this.updateCounters(args.args);
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {
                    case 'seeSecretCards':
                        this.secretZone.removeAll();
                        dojo.style("secret_zone_wrap", "display", "none");
                        this.playerHand.setSelectionMode(1);
                        this.makeInPlayPanelsSelectable(true);
                        break;
                    case 'mandatoryCard':
                        if (this.isCurrentPlayerActive()) {
                            this.inPlayStocksByPlayerId[this.player_id].setSelectionMode(1);
                            dojo.query(".stockitem").removeClass('unselectable').addClass('selectable');
                            break;
                        }
                    case 'playerTurn':
                        if (this.isCurrentPlayerActive()) {
                            dojo.query(".stockitem").removeClass('unselectable').addClass('selectable');
                            break;
                        }
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName, args);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case "playerTurn":
                            this.addActionButton('button_confirm_card', _('Play selected card'), 'onPlayCard');
                            this.addActionButton('button_discard', _('Discard an Object'), 'onDiscard');
                            break;
                        case "mandatoryCard":
                            this.addActionButton('button_confirm_card', _('Play selected card'), 'onPlayCard');
                            break;
                        case "seeSecretCards":
                            var selectionRequired = args.selection_required;
                            if (selectionRequired) {
                                this.addActionButton('button_confirm_card', _('Take a card'), 'onSelectSecretCard');
                            } else {
                                this.addActionButton('button_confirm_viewed', _('Done'), 'onDoneViewing');
                            }
                            break;
                        case "specifyClockwise":
                            this.addActionButton('button_confirm_card_clockwise', _('Clockwise'), 'onClockwise');
                            this.addActionButton('button_confirm_card_counterclockwise', _('Counterclockwise'), 'onCounterclockwise');
                            break;
                        case "specifyObjectToTake":
                            this.addActionButton('button_take_object', _('Take'), 'onTakeObject');
                            break;
                        case "specifyObjectsToSwap":
                            this.addActionButton('button_swap_objects', _('Swap'), 'onSwapObjects');
                            break;

                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */
            makeInPlayPanelsSelectable(selectable) {
                for (var player_id in this.inPlayStocksByPlayerId) {
                    this.inPlayStocksByPlayerId[player_id].setSelectionMode(selectable ? 1 : 0);
                }
            },

            getSelectedPlayer: function () {
                for (var player_id in this.optionsByPlayerId) {
                    var stock = this.optionsByPlayerId[player_id];
                    if (stock.getSelectedItems().length > 0) {
                        return player_id;
                    }
                }
            },

            displaySecretCards: function (secretCardsProperties) {
                var cards = secretCardsProperties.cards;
                if (this.isCurrentPlayerActive() && cards) {
                    var selectionRequired = secretCardsProperties.selection_required;
                    var title = secretCardsProperties.location_desc;

                    dojo.byId("secret_zone_title").innerHTML = title;
                    dojo.style("secret_zone_wrap", "display", "block");

                    for (var card_id in cards) {
                        var card = cards[card_id];
                        this.secretZone.addToStockWithId(card.type, card.id);
                        this.secretZone.setSelectionMode(selectionRequired ? 1 : 0);
                    }
                }
            },

            changeInnerHtml: function (id, text) {
                if (dojo.byId(id)) {
                    dojo.byId(id).innerHTML = text;
                }
            },

            createTooltip: function (card_div, card_type_id, card_id) {
                console.log("tooltip card_type_id" + card_type_id);
                console.log("tooltip card_id" + card_id);
                // Note that "card_type_id" contains the type of the item, so you can do special actions depending on the item type
                delay = 200;
                this.addTooltipHtml(card_id, this.format_block('jstpl_card_tooltip', {
                    cardName: this.cardsAvailable[card_type_id].name,
                    cardDescription: this.cardsAvailable[card_type_id].description,
                }), delay);

            },

            getKeyByValue: function (object, value) {
                return Object.keys(object).find(key => object[key] === value);
            },

            getStockCardIdOfType: function (stock, cardType) {
                for (var idAndType of stock.getAllItems()) {
                    console.log("idAndType", idAndType);
                    if (idAndType.type === cardType) {
                        return idAndType.id;
                    }
                }
                return undefined;
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */

            /* Example:
            
            onMyMethodToCall1: function( evt )
            {
                console.log( 'onMyMethodToCall1' );
                
                // Preventing default browser reaction
                dojo.stopEvent( evt );
         
                // Check that this action is possible (see "possibleactions" in states.inc.php)
                if( ! this.checkAction( 'myAction' ) )
                {   return; }
         
                this.ajaxcall( "/getthemacguffin/getthemacguffin/myAction.html", { 
                                                                        lock: true, 
                                                                        myArgument1: arg1, 
                                                                        myArgument2: arg2,
                                                                        ...
                                                                     }, 
                             this, function( result ) {
                                
                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)
                                
                             }, function( is_error) {
         
                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)
         
                             } );        
            },        
            
            */
            onSelectCard: function (control_name, item_id) {
                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    //get selected card
                    var itemsFromHand = this.playerHand.getSelectedItems();
                    if (itemsFromHand.length == 1) {
                        var card = itemsFromHand[0];
                        console.log("selection of ", card.type);


                        switch (card.type) {
                            case "WHEEL_OF_FORTUNE":

                                break;

                            default:

                        }
                    }
                };
            },

            onSelectInPlayCard: function (control_name, item_id) {
                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    //get selected card
                    var itemsFromInPlayZone = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                    if (itemsFromInPlayZone.length == 1) {
                        var card = itemsFromInPlayZone[0];
                        console.log("selection of in play ", card.type);


                        switch (card.type) {
                            case "CROWN":
                                this.changeInnerHtml("button_confirm_card", _("Pass"));
                                break;

                            default:
                                this.changeInnerHtml("button_confirm_card", _("Play selected card"));

                        }
                    }
                    else {
                        this.changeInnerHtml("button_confirm_card", _("Play selected card"));
                    }
                };
            },

            /** 
             * Unselects other player hands when one is clicked, since hands are not in the same stock.
             */
            onSelectOption: function (control_name, item_id) {
                var clickedStock = null;
                for (var player_id in this.optionsByPlayerId) {
                    var stock = this.optionsByPlayerId[player_id];
                    if (stock.control_name == control_name)
                        clickedStock = stock;
                }

                if (clickedStock.getSelectedItems().length == 1) {
                    for (var player_id in this.optionsByPlayerId) {
                        var stock = this.optionsByPlayerId[player_id];
                        if (stock.control_name != clickedStock.control_name)
                            stock.unselectAll();
                    }
                }
            },

            onSelectSecretCard: function (evt) {
                console.log('onSelectSecretCard');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('takeCard');
                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    //get selected card
                    var items = this.secretZone.getSelectedItems();
                    if (items.length == 1) {
                        var card = items[0];
                        console.log("selection of secret ", card.type);
                        this.ajaxcall('/getthemacguffin/getthemacguffin/seenSecretCardsAction.html',
                            {
                                lock: true,
                                selected_card_id: card.id,
                            },
                            this,
                            function (result) { });

                    } else {
                        this.showMessage(_('You have to select one card'), 'error');
                    }
                };
            },

            onDoneViewing: function (evt) {
                console.log('onDoneViewing');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('confirm');
                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    this.ajaxcall('/getthemacguffin/getthemacguffin/seenSecretCardsAction.html',
                        {
                            lock: true,
                            selected_card_id: 0,
                        },
                        this,
                        function (result) { });
                };
            },

            onClockwise: function (evt) {
                console.log('onClockwise');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('clockwise');

                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    this.ajaxcall('/getthemacguffin/getthemacguffin/specifyClockwiseAction.html',
                        {
                            lock: true,
                            clockwise: true,
                        },
                        this,
                        function (result) { });
                };
            },

            onCounterclockwise: function (evt) {
                console.log('onClockonCounterclockwisewise');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('counterclockwise');

                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    this.ajaxcall('/getthemacguffin/getthemacguffin/specifyClockwiseAction.html',
                        {
                            lock: true,
                            clockwise: false,
                        },
                        this,
                        function (result) { });
                };
            },

            onTakeObject: function (evt) {
                console.log('onTakeObject');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('takeObject');
                if (this.isCurrentPlayerActive()) {
                    var cardFromOtherPlayer;
                    for (var player_id in this.inPlayStocksByPlayerId) {
                        if (player_id != this.player_id) {
                            var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                            if (selected.length == 1) {
                                cardFromOtherPlayer = selected[0];
                                console.log("selection from other player ", cardFromOtherPlayer.type);
                            }
                        }
                    }

                    if (cardFromOtherPlayer) {
                        this.ajaxcall('/getthemacguffin/getthemacguffin/takeObjectAction.html',
                            {
                                lock: true,
                                object_id: cardFromOtherPlayer.id,
                            },
                            this,
                            function (result) { });

                    } else {
                        this.showMessage(_('You have to select one card'), 'error');
                    }
                }
            },

            onSwapObjects: function (evt) {
                console.log('onSwapObjects');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('swapObjects');
                if (this.isCurrentPlayerActive()) {
                    var card1;
                    var card2;
                    for (var player_id in this.inPlayStocksByPlayerId) {
                        var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                        if (selected.length == 1) {
                            if (!card1) {
                                card1 = selected[0];
                                console.log("selection from other player ", card1.type);
                            } else {
                                card2 = selected[0];
                                console.log("selection from other player ", card2.type);
                            }
                        }
                    }

                    if (card1 && card2) {
                        this.ajaxcall('/getthemacguffin/getthemacguffin/swapObjectsAction.html',
                            {
                                lock: true,
                                object_id_1: card1.id,
                                object_id_2: card2.id,
                            },
                            this,
                            function (result) { });

                    } else {
                        this.showMessage(_('You have to select 2 cards from different players'), 'error');
                    }
                }
            },

            getSelectedInPlayCard: function () {
                var cardFromOtherPlayer;
                for (var player_id in this.inPlayStocksByPlayerId) {
                    if (player_id != this.player_id) {
                        var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                        if (selected.length == 1) {
                            cardFromOtherPlayer = selected[0];
                            console.log("selection from other player ", cardFromOtherPlayer.type);
                        }
                    }
                }
                return cardFromOtherPlayer;
            },

            onPlayCard: function (evt) {
                console.log('onPlayCard');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('playCard');

                var itemsFromHand = this.playerHand.getSelectedItems();
                var itemsFromInPlayZone = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();

                var cardFromOtherPlayer = this.getSelectedInPlayCard();
                if ((itemsFromHand.length == 0 && itemsFromInPlayZone.length == 0)
                    || (itemsFromHand.length == 1 && itemsFromInPlayZone.length == 1)) {
                    this.showMessage(_('You have to select one card to play'), 'error');
                }
                else {
                    var playedCard;
                    if (itemsFromHand.length == 1) {
                        var playedCard = itemsFromHand[0];
                    } else {
                        var playedCard = itemsFromInPlayZone[0];
                    }

                    //check selection
                    var selectedPlayer = this.getSelectedPlayer();

                    switch (playedCard.type) {
                        case "SWITCHEROO":
                            console.log(selectedPlayer);
                            break;

                        default:

                    }
                    //back call
                    this.ajaxcall('/getthemacguffin/getthemacguffin/playCardAction.html',
                        {
                            lock: true,
                            played_card_id: playedCard.id,
                            effect_on_card_id: cardFromOtherPlayer ? cardFromOtherPlayer.id : 0,
                            effect_on_player_id: selectedPlayer ? selectedPlayer : 0,
                        },
                        this,
                        function (result) {
                            this.playerHand.removeFromStockById(playedCard.id);
                            /*items.forEach(removed => {
                                this.discardedDesserts.addToStockWithId(removed.type, removed.id, "myhand");
                                this.playerHand.removeFromStockById(removed.id);
                            });
    */
                        });
                }
            },

            onDiscard: function (evt) {
                console.log('onDiscard');
                this.checkAction('discard');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                var items = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                //money needs to select an object or a player
                var cardFromOtherPlayer = this.getSelectedInPlayCard();
                var selectedPlayer = this.getSelectedPlayer();

                if (items.length == 1) {
                    var playedCard = items[0];
                    //back call
                    this.ajaxcall('/getthemacguffin/getthemacguffin/discardAction.html',
                        {
                            lock: true,
                            played_card_id: playedCard.id,
                            effect_on_card_id: cardFromOtherPlayer ? cardFromOtherPlayer.id : 0,
                            effect_on_player_id: selectedPlayer ? selectedPlayer : 0,

                        },
                        this,
                        function (result) {
                        });

                } else {
                    this.showMessage(_('You have to select an Object in play to discard'), 'error');
                }
            },

            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your getthemacguffin.game.php file.
             
            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                // TODO: here, associate your game notifications with local methods

                // Example 1: standard notification handling
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

                // Example 2: standard notification handling + tell the user interface to wait
                //            during 3 seconds after calling the method in order to let the players
                //            see what is happening in the game.
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
                // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
                // 

                dojo.subscribe('handChange', this, "notif_handChange");
                dojo.subscribe('inPlayChange', this, "notif_inPlayChange");

                dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
                dojo.subscribe('secretCards', this, "notif_secretCards");
                dojo.subscribe('revelation', this, "notif_revelation");


                dojo.subscribe('gtmplayerEliminated', this, "notif_playerEliminated");

                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onSelectCard');
            },

            // TODO: from this point and below, you can write your game notifications handling methods

            /*
            Example:
             
            notif_cardPlayed: function( notif )
            {
                console.log( 'notif_cardPlayed' );
                console.log( notif );
                
                // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
                
                // TODO: play the card in the user interface.
            },    
             
            */

            notif_handChange: function (notif) {
                console.log('notif_handChange', notif.args);
                if (notif.args.reset) {
                    this.playerHand.removeAll();
                }
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.added[i];
                        console.log("notif_handChange add card id/type :" + card.id + " " + card.type);
                        this.playerHand.addToStockWithId(card.type, card.id);
                    }
                }
                if (notif.args.removed) {
                    for (var i in notif.args.removed) {
                        var card = notif.args.removed[i];
                        console.log("notif_handChange remove card id/type :" + card.id + " " + card.type);
                        this.playerHand.removeFromStockById(card.id);
                    }
                }
            },

            notif_inPlayChange: function (notif) {
                $player_id = notif.args.player_id;
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.added[i];
                        console.log("notif_inPlayChange add card id/type :" + card.id + " " + card.type);
                        this.inPlayStocksByPlayerId[$player_id].addToStockWithId(card.type, card.id);
                    }
                }
                if (notif.args.removed) {
                    for (var i in notif.args.removed) {
                        var card = notif.args.removed[i];
                        console.log("notif_inPlayChange remove card id/type :" + card.id + " " + card.type);
                        this.inPlayStocksByPlayerId[$player_id].removeFromStockById(card.id);
                    }
                }
            },

            notif_cardPlayed: function (notif) {
                console.log('notif_cardPlayed', notif);
                //console.log(notif);
                var card = notif.args.card;

                if (notif.args.toInPlay) {
                    this.inPlayStocksByPlayerId[notif.args.player_id].addToStockWithId(card.type, card.id);
                }
                else {
                    this.discard.removeAll();
                    this.discard.addToStockWithId(card.type, card.id);
                }

                if (this.player_id == notif.args.player_id) {
                    this.playerHand.removeFromStockById(card.id);
                }
                if (notif.args.discarded) {
                    this.inPlayStocksByPlayerId[notif.args.player_id].removeFromStockById(card.id);
                }

                //animates void cards
                if (card.type == "HIPPIE" || card.type == "SHRUGMASTER" || card.type == "MARSHALL") {
                    this.animate(card.type, 'discard_pile');
                }
            },

            notif_secretCards: function (notif) {
                this.displaySecretCards(notif.args.args);
            },

            notif_revelation: function (notif) {
                console.log('notif_cardPlayed', notif);
                if (notif.args.inDiscard || notif.args.ownerId) {
                    if (notif.args.inDiscard) {
                        var divFrom = "player_boards";
                        var divTo = "discard_pile";
                    } else if (notif.args.ownerId) {
                        var divFrom = "player_board_" + notif.args.ownerId;
                        var divTo = "in_play_" + notif.args.ownerId;
                    }
                    this.slideTemporaryObject(this.format_block('jstpl_animation', {
                        background_class: "background_" + notif.args.type,
                    }), "game_play_area", divFrom, divTo, 4500, 0);
                }
            },

            animate: function (cardType, animationLocationDiv) {
                dojo.place(this.format_block('jstpl_animation', {
                    background_class: "background_" + cardType,
                }), document.getElementById(animationLocationDiv));

                function callback() {
                    dojo.destroy("animationDiv");
                }
                var node = document.getElementById(animationLocationDiv);
                node.addEventListener("webkitAnimationEnd", callback, false);
                node.addEventListener("animationend", callback, false);
            },

            notif_playerEliminated: function (notif) {
                var player_id = notif.args.player_id;
                this.disablePlayerPanel(player_id);
            },
        });
    });
