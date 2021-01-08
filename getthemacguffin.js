/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * GetTheMacGuffin implementation : © <Your name here> <Your email address here>
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
                this.playerHand.setSelectionMode(1);

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

                //-----------cards in play setup for each player
                this.inPlayStocksByPlayerId = [];
                for (var player_id in gamedatas.inPlay) {

                    var playerInPlayCards = new ebg.stock();
                    playerInPlayCards.setSelectionMode(1);
                    playerInPlayCards.create(this, $('cards_in_play_' + player_id), this.cardwidth, this.cardheight);
                    playerInPlayCards.image_items_per_row = this.image_items_per_row;

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
                        //this.addCardToolTip(playerInPlayCards, card.id, card.type_arg);
                    }
                    this.inPlayStocksByPlayerId[player_id] = playerInPlayCards;
                }

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

                switch (stateName) {

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Show some HTML block at this game state
                        dojo.style( 'my_html_block_id', 'display', 'block' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
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
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */


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
                var items = this.playerHand.getSelectedItems();
                if (items.length == 1) {
                    var card = items[0];
                    console.log("selection of ", card.type);
                    switch (card.type) {
                        case "ROCK":
                        case "SCISSORS":
                        case "PAPER":
                            alert('Hey');
                            break;

                        default:

                    }
                };
            },

            onPlayCard: function (evt) {
                console.log('onPlayCard');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                var items = this.playerHand.getSelectedItems();
                var cardFromOtherPlayer;
                for (var player_id in this.inPlayStocksByPlayerId) {
                    var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                    if (selected.length == 1) {
                        cardFromOtherPlayer = selected[0];
                        console.log("selection from other player ", cardFromOtherPlayer.type);
                    }
                }

                if (items.length == 1) {
                    var playedCard = items[0];
                } else {
                    var playedCard = cardFromOtherPlayer;
                }

                if (playedCard) {
                    if (this.checkAction('playCard')) {
                        //check selection
                        var selectedPlayer;

                        switch (playedCard.type) {
                            case "MACGUFFIN":
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
                } else {
                    this.showMessage(_('You have to select a card to play'), 'error');
                }
            },

            onDiscard: function (evt) {
                console.log('onDiscard');

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                var items = this.inPlayStocksByPlayerId["metodo"].getSelectedItems();
                if (items.length == 1) {
                    var playedCard = items[0];
                    //back call
                    this.ajaxcall('/getthemacguffin/getthemacguffin/discardAction.html',
                        {
                            lock: true,
                            played_card_id: playedCard.id,
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
                dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
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

                if (notif.args.reset) {
                    this.playerHand.removeAll();
                }
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.cards[i];
                        console.log("notif_handChange add card id/type :" + card.id + " " + card.type);
                        this.playerHand.addToStockWithId(card.type_arg, card.id);
                    }
                }
                if (notif.args.removed) {

                    for (var i in notif.args.removed) {
                        var card = notif.args.cards[i];
                        console.log("notif_handChange remove card id/type :" + card.id + " " + card.type);
                        this.playerHand.removeFromStockById(card.id);
                    }
                }
            },

            notif_cardPlayed: function (notif) {
                console.log('notif_cardPlayed');
                console.log(notif);
                var card = notif.args.card;

                if (notif.args.toInPlay) {
                    this.inPlayStocksByPlayerId[notif.args.player_id].addToStockWithId(card.type_arg, card.id);
                }

                /* if (player_id == notif.args.player_id) {
                     this.playerHand.removeFromStockById(card.id);
                  }*/
            },

        });
    });
