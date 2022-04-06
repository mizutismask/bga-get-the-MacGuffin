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
                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;

                this.cardwidth = 200;
                this.cardheight = 311;
                this.image_items_per_row = 10;
                this.cards_img = 'img/cards/cards200x311.jpg';
                this.icons_img = 'img/icons/cards.png';
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
                //console.log("gamedatas ", gamedatas);

                this.cardsAvailable = gamedatas.cardsAvailable;

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
                    this.playerHand.addToStockWithId(card.type, card.id);
                }

                //-----------cards in play setup + options setup for each player
                this.inPlayStocksByPlayerId = [];
                this.optionsByPlayerId = [];

                for (var player_id of gamedatas.playerorder) {
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
                        this.addTooltip('options_' + player_id + "_item_0", _("Cards in hand for this player"), "");

                        dojo.connect(this.inPlayStocksByPlayerId[player_id], 'onChangeSelection', this, 'onSelectInPlayCardFromOtherPlayers');
                    }

                    //show dead players
                    if (gamedatas.players[player_id].zombie || gamedatas.players[player_id].eliminated) {
                        dojo.addClass("in_play_" + player_id, "gtm_dead_player");
                        if (this.player_id != player_id) {
                            this.optionsByPlayerId[player_id].setSelectionMode(0);
                        }
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

                //playingZoneDetail
                this.playingZoneDetail = new ebg.stock(); // new stock object for seeing secret cards
                this.playingZoneDetail.create(this, $('playing_zone_detail'), this.cardwidth, this.cardheight);//playing_zone is the div where the card is going
                this.playingZoneDetail.image_items_per_row = this.image_items_per_row;
                this.playingZoneDetail.setSelectionMode(0);
                this.playingZoneDetail.setSelectionAppearance('class');
                //this.playingZoneDetail.autowidth = true;
                this.playingZoneDetail.onItemCreate = dojo.hitch(this, 'createTooltip');

                // Create cards types:
                var i = 0;
                for (var name in this.cardsAvailable) {
                    var card = this.cardsAvailable[name];
                    i++;
                    // Build card type id
                    this.playingZoneDetail.addItemType(name, 0, g_gamethemeurl + this.cards_img, i);
                }
                //adds already played objects
                var cards = gamedatas.playingZoneDetail;
                for (var card_id in cards) {
                    var card = cards[card_id];
                    this.playingZoneDetail.addToStockWithId(card.type, card.id);
                }

                dojo.style("cards_count_" + this.player_id, "display", "none");//do not display my counter
                this.updateCounters(gamedatas.counters);

                this.addTooltipToClass("gtm_cards_counter", _("Cards in the tomb"), "");

                if (dojo.byId("overall_player_board_" + this.player_id)) {//not in spectator mode
                    dojo.place(this.format_block('jstpl_ask_confirm', {
                        askForConfirm: _("ask for confirmation"),
                    }), "overall_player_board_" + this.player_id);
                }

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                dojo.connect(this.playingZoneDetail, 'onChangeSelection', this, 'onSelectDiscardedCard');

                //console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);
                //console.log('args', args);
                this.currentState = stateName;
                dojo.query("#playing_zone_detail .stockitem").removeClass('selectable').addClass('unselectable').addClass('stockitem_unselectable');
                switch (stateName) {
                    case 'seeSecretCards':
                        if (this.isCurrentPlayerActive()) {
                            this.playerHand.setSelectionMode(0);
                            this.makeInPlayPanelsSelectable(false);
                        }
                        break;
                    case 'playerTurn':
                        if (this.isCurrentPlayerActive()) {
                            // this.resetHelpMessage();
                            this.deselectAll();
                            this.argPossibleTargetsInfo = args.args;
                        }
                        break;
                    case 'mandatoryCard':
                        var mandatorCardId = args.args.mandatory_card_id;
                        if (this.isCurrentPlayerActive()) {
                            this.deselectAll();
                            this.inPlayStocksByPlayerId[this.player_id].setSelectionMode(0);
                            dojo.query("#myhand .stockitem").removeClass('selectable').addClass('unselectable').addClass('stockitem_unselectable');
                            dojo.query("#myhand_item_" + mandatorCardId).removeClass('unselectable').removeClass('stockitem_unselectable').addClass('selectable');
                            this.playerHand.selectItem(mandatorCardId);
                            this.onSelectCard("myhand", mandatorCardId);
                            break;
                        }
                    case 'nextPlayer':
                        this.updateCounters(args.args);
                        break;
                    case 'client_choose_target':
                        switch (this.clientStateArgsCardType) {
                            case "GARBAGE_COLLECTR":
                                this.playingZoneDetail.setSelectionMode(1);
                                dojo.query("#playing_zone_detail .stockitem").removeClass('unselectable').removeClass('stockitem_unselectable').addClass('selectable');
                                break;


                            default:
                                break;
                        }


                    default:

                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                //console.log('Leaving state: ' + stateName);

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
                            dojo.query("#myhand .stockitem").removeClass('unselectable').removeClass('stockitem_unselectable').addClass('selectable');
                            break;
                        }
                    case 'playerTurn':
                        if (this.isCurrentPlayerActive()) {
                            dojo.query(".stockitem").removeClass('unselectable').addClass('selectable');
                            this.playingZoneDetail.setSelectionMode(0);
                            //this.resetHelpMessage();
                            break;
                        }
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                //console.log('onUpdateActionButtons: ' + stateName, args);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case "playerTurn":
                            this.addActionButton('button_confirm_card', _('Use selected card'), 'onPlayCard');
                            if (this.inPlayStocksByPlayerId[this.player_id].count() > 0)
                                this.addActionButton('button_discard', _('Discard an object'), 'onDiscard');
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
                            if (args.clockwise_player_name) {
                                this.addActionButton('button_confirm_card_clockwise', args.clockwise_player_name, 'onClockwise');
                            }
                            if (args.counterclockwise_player_name) {
                                this.addActionButton('button_confirm_card_counterclockwise', args.counterclockwise_player_name, 'onCounterclockwise');
                            }
                            break;
                        case "specifyObjectToTake":
                            this.addActionButton('button_take_object', _('Take a card'), 'onTakeObject');
                            break;
                        case "specifyObjectsToSwap":
                            this.addActionButton('button_swap_objects', _('Swap'), 'onSwapObjects');
                            break;
                        case "client_choose_target":
                            this.addActionButton('button_confirm_card', _('Confirm'), 'onPlayCard');
                            this.addActionButton('button_cancel', _('Cancel'), 'gtmRestoreServerGameState', null, false, 'red');
                            break;

                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            gtmRestoreServerGameState() {
                this.restoreServerGameState();
                this.deselectAll();
            },

            showMessageAndResetSelection(msg) {
                this.showMessage(msg, 'error');
                this.deselectAll();
            },

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
                // Note that "card_type_id" contains the type of the item, so you can do special actions depending on the item type
                delay = 200;
                this.addTooltipHtml(card_id, this.format_block('jstpl_card_tooltip', {
                    cardName: _(this.cardsAvailable[card_type_id].name),
                    cardDescription: _(this.cardsAvailable[card_type_id].description),
                }), delay);
                var classe = this.getSizeTextClass(_(this.cardsAvailable[card_type_id].description));
                dojo.place("<div class='gtm_translated_desc " + classe + "'>" + _(this.cardsAvailable[card_type_id].description) + " </div> ", card_div, 1);
            },

            getSizeTextClass: function (text) {
                if (text.length > 220) return "gtm_translated_x_small";
                if (text.length > 139) return "gtm_translated_small";
                return "gtm_translated_normal";
            },

            getKeyByValue: function (object, value) {
                return Object.keys(object).find(key => object[key] === value);
            },

            getStockCardIdOfType: function (stock, cardType) {
                for (var idAndType of stock.getAllItems()) {
                    if (idAndType.type === cardType) {
                        return idAndType.id;
                    }
                }
                return undefined;
            },

            deselectAll: function () {
                this.playerHand.unselectAll();
                for (var player_id in this.inPlayStocksByPlayerId) {
                    this.inPlayStocksByPlayerId[player_id].unselectAll();
                    if (this.optionsByPlayerId.hasOwnProperty(player_id)) {
                        this.optionsByPlayerId[player_id].unselectAll();
                    }
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

            chooseEffectTarget: function (helpText) {
                this.setClientState("client_choose_target", { descriptionmyturn: helpText, });
            },

            isTargetClientState: function (state) {
                return "client_choose_target" == state;
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*
                Called when active user selects a card in his hand.
            */
            onSelectCard: function (control_name, item_id) {
                // This method is called when myStockControl selected items changed

                if (item_id == undefined) {
                    this.restoreServerGameState();
                    return;
                }

                // Check if item is selectable
                if (dojo.hasClass(control_name + "_item_" + item_id, "unselectable")) {
                    this.playerHand.unselectItem(item_id);
                }
                var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                //console.log("askForConfirm ", askForConfirm);
                if (this.isCurrentPlayerActive()) {
                    //get selected card
                    var itemsFromHand = this.playerHand.getSelectedItems();
                    if (itemsFromHand.length == 1) {
                        var card = itemsFromHand[0];
                        //console.log("selection of ", card.type);
                        this.clientStateArgsCardType = card.type;

                        switch (card.type) {
                            case "GARBAGE_COLLECTR":
                                if (this.playingZoneDetail.count() > 0) {
                                    this.chooseEffectTarget(_("Now, scroll down to select a card from the discard detail"));
                                }
                                break;
                            case "FIST_OF_DOOM":
                                this.chooseEffectTarget(_("Now, select an object in play or another player’s hand"));
                                break;
                            case "THIEF":
                                this.chooseEffectTarget(_("Now, select an object in play or another player’s hand"));
                                break;
                            case "SWITCHEROO":
                            case "SPY":
                            case "CAN_I_USE_THAT":
                                this.chooseEffectTarget(_("Now, select another player’s hand"));
                                break;
                            case "":
                                this.chooseEffectTarget(_("Now, select an object in play or another player’s hand"));
                                break;
                            case "NOT_DEAD_YET":
                                if (this.argPossibleTargetsInfo.no_other_cards) {
                                    if (this.argPossibleTargetsInfo.no_one_else_has_hand) {
                                        if (this.argPossibleTargetsInfo.only_mac_guffins_are_in_play) {
                                            if (!askForConfirm) {
                                                this.onPlayCard();
                                            }
                                        }
                                        else {
                                            this.chooseEffectTarget(_("Now, select an object in play"));
                                        }
                                    } else {
                                        this.chooseEffectTarget(_("Now, select another player’s hand"));
                                    }
                                }
                                else {
                                    if (!askForConfirm) {
                                        this.onPlayCard();
                                    }
                                }
                                break;
                            case "ASSASSIN":
                                if (this.argPossibleTargetsInfo.is_crown_in_play) {
                                    if (!askForConfirm) {
                                        this.onPlayCard();
                                    }
                                } else {
                                    this.chooseEffectTarget(_("Now, select an object in play or another player’s hand"));
                                }
                                break;
                            case "HIPPIE":
                            case "SHRUGMASTER":
                            case "MARSHALL":
                            case "INTERROGATOR":
                            case "MACGUFFIN":
                            case "MONEY":
                            case "CROWN":
                            case "BACKUP_MACGUFFIN":
                            case "SCISSORS":
                            case "ROCK":
                            case "PAPER":
                            case "TOMB_ROBBERS":
                            case "WHEEL_OF_FORTUNE":
                            case "INTERROGATOR":
                            case "VORTEX":
                            case "MERCHANT":
                                if (!askForConfirm) {
                                    this.onPlayCard();
                                }

                            //case "MERCHANT":
                            //if (!this.argPossibleTargetsInfo.two_players_have_objects && this.argPossibleTargetsInfo.one_other_has_objects) {
                            //    this.chooseEffectTarget(_("Now, select an object to take"));
                            //    break;
                            // }
                            default:
                                this.clientStateArgsCardTypeteArgs = undefined;
                            // this.gtmRestoreServerGameState();
                        }
                    } else {
                        this.gtmRestoreServerGameState();
                    }
                };
            },

            onSelectInPlayCard: function (control_name, item_id) {
                if (item_id == undefined)
                    return;

                // Check if item is selectable
                if (dojo.hasClass(control_name + "_item_" + item_id, "unselectable")) {
                    this.inPlayStocksByPlayerId[this.player_id].unselectItem(item_id);
                }

                // This method is called when myStockControl selected items changed
                var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                if (this.isCurrentPlayerActive()) {

                    //get selected card
                    var itemsFromInPlayZone = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                    if (itemsFromInPlayZone.length == 1) {
                        var card = itemsFromInPlayZone[0];

                        if (this.currentState == "specifyObjectsToSwap") {
                            var cards = this.getSelectedObjectsFromOtherPlayers();
                            if (cards.length == 2 && !askForConfirm) {
                                this.onSwapObjects();
                            }
                        }
                        else if (this.isTargetClientState(this.currentState)) {
                            //we are selecting the target of an already selected card in my hand, so we don't apply the effect of this one
                        }
                        else {
                            console.log("selection of in play ", card.type);
                            console.log("saskForConfirm ", askForConfirm);
                            this.clientStateArgsCardType = card.type;

                            switch (card.type) {
                                case "CROWN":
                                    if (!this.argPossibleTargetsInfo.is_a_mac_guffin_in_play) {
                                        if (!askForConfirm) {
                                            this.onPlayCard();
                                        }
                                    } else {
                                        this.changeInnerHtml("button_confirm_card", _("Pass"));
                                        dojo.query("#button_confirm_card").removeClass("bgabutton_blue").addClass("bgabutton_gray");
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.setAttr("button_confirm_card", 'disabled', 'true');
                                        }
                                        if (!askForConfirm) {
                                            this.onDiscard();
                                        }
                                    }
                                    break;
                                case "MONEY":
                                    this.chooseEffectTarget(_("Now, select an object in play or another player’s hand"));
                                    break;
                                case "PAPER":
                                    if (!this.argPossibleTargetsInfo.can_paper_be_used) {
                                        dojo.query("#button_confirm_card").removeClass("bgabutton_blue").addClass("bgabutton_gray");
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.setAttr("button_confirm_card", 'disabled', 'true');
                                        }
                                        if (!askForConfirm) {
                                            this.onDiscard();
                                        }
                                    } else {
                                        if (!askForConfirm) {
                                            this.onPlayCard();
                                        }
                                    }
                                    break;
                                case "ROCK":
                                    if (!this.argPossibleTargetsInfo.can_rock_be_used) {
                                        dojo.query("#button_confirm_card").removeClass("bgabutton_blue").addClass("bgabutton_gray");
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.setAttr("button_confirm_card", 'disabled', 'true');
                                        }
                                        if (!askForConfirm) {
                                            this.onDiscard();
                                        }
                                    } else {
                                        if (!askForConfirm) {
                                            this.onPlayCard();
                                        }
                                    }
                                    break;
                                case "SCISSORS":
                                    if (!this.argPossibleTargetsInfo.can_scissors_be_used) {
                                        dojo.query("#button_confirm_card").removeClass("bgabutton_blue").addClass("bgabutton_gray");
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.setAttr("button_confirm_card", 'disabled', 'true');
                                        }
                                        if (!askForConfirm) {
                                            this.onDiscard();
                                        }
                                    } else {
                                        if (!askForConfirm) {
                                            this.onPlayCard();
                                        }
                                    }
                                    break;
                                case "THIEF":
                                    if (!askForConfirm) {
                                        this.onPlayCard();
                                    }
                                    break;

                                case "MACGUFFIN":
                                    var mcgfnId = this.getStockCardIdOfType(this.inPlayStocksByPlayerId[this.player_id], "MACGUFFIN");
                                    if (mcgfnId && (this.inPlayStocksByPlayerId[this.player_id].count() > 1 || this.playerHand.count() > 0)) {
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.removeClass('button_confirm_card', 'bgabutton_blue');
                                            dojo.addClass('button_confirm_card', 'bgabutton_gray');
                                            dojo.setAttr('button_confirm_card', 'disabled', 'true');
                                        }
                                    }
                                    else {
                                        if (!askForConfirm) {
                                            this.onPlayCard();
                                        }
                                    }
                                    break;
                                case "BACKUP_MACGUFFIN":
                                    var mcgfnId = this.getStockCardIdOfType(this.inPlayStocksByPlayerId[this.player_id], "BACKUP_MACGUFFIN");
                                    if (mcgfnId && (this.inPlayStocksByPlayerId[this.player_id].count() > 1 || this.playerHand.count() > 0)) {
                                        if (dojo.byId("button_confirm_card")) {
                                            dojo.removeClass('button_confirm_card', 'bgabutton_blue');
                                            dojo.addClass('button_confirm_card', 'bgabutton_gray');
                                            dojo.setAttr('button_confirm_card', 'disabled', 'true');
                                        }
                                    } else {
                                        if (!this.argPossibleTargetsInfo.is_the_mac_guffin_in_play) {
                                            if (!askForConfirm) {
                                                this.onPlayCard();
                                            }
                                        }
                                        else {
                                            if (!askForConfirm) {
                                                this.onDiscard();
                                            }
                                        }
                                    }
                                    break;
                                /*
                            default:
                                if (dojo.byId("button_confirm_card")) {
                                    this.changeInnerHtml("button_confirm_card", _("Play selected card"));
                                    dojo.removeClass('button_confirm_card', 'bgabutton_gray');
                                    dojo.addClass('button_confirm_card', 'bgabutton_blue');
                                    dojo.removeAttr('button_confirm_card', 'disabled');
                                }
           */
                            }
                        }
                    }
                    else {
                        if (dojo.byId("button_confirm_card")) {
                            this.changeInnerHtml("button_confirm_card", _("Use selected card"));
                            dojo.removeClass('button_confirm_card', 'bgabutton_gray');
                            dojo.addClass('button_confirm_card', 'bgabutton_blue');
                            dojo.removeAttr('button_confirm_card', 'disabled');
                        }
                    }
                }
            },

            onSelectInPlayCardFromOtherPlayers: function (control_name, item_id) {
                if (item_id == undefined)
                    return;

                // Check if item is selectable
                if (dojo.hasClass(control_name + "_item_" + item_id, "unselectable")) {
                    this.inPlayStocksByPlayerId[this.player_id].unselectItem(item_id);
                }
                var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                if (this.isCurrentPlayerActive() && !askForConfirm) {
                    var cardFromOtherPlayer = this.getSelectedInPlayCard();
                    if (this.currentState == "client_choose_target" && cardFromOtherPlayer) {
                        //console.log("selection of in play ", cardFromOtherPlayer.type);
                        var itemsFromHand = this.playerHand.getSelectedItems();
                        var itemsFromMyInPlay = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                        if (itemsFromHand.length == 1) {
                            var card = itemsFromHand[0];
                            switch (card.type) {
                                case "MONEY":
                                case "THIEF":
                                case "FIST_OF_DOOM":
                                    this.onPlayCard();
                                    break;
                                case "ASSASSIN":
                                    if (!this.argPossibleTargetsInfo.is_crown_in_play) {
                                        this.onPlayCard();
                                    }
                                    break;
                            }
                        }
                        else if (itemsFromMyInPlay.length == 1) {
                            var card = itemsFromMyInPlay[0];
                            switch (card.type) {
                                case "MONEY":
                                    this.onPlayCard();
                                    break;
                            }
                        }
                    }
                    else if (this.currentState == "specifyObjectsToSwap") {
                        var cards = this.getSelectedObjectsFromOtherPlayers();
                        if (cards.length == 2) {
                            this.onSwapObjects();
                        }
                    }
                }
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

                    //Unselects other player hands when one is clicked, since hands are not in the same stock
                    for (var player_id in this.optionsByPlayerId) {
                        var stock = this.optionsByPlayerId[player_id];
                        if (stock.control_name != clickedStock.control_name)
                            stock.unselectAll();
                    }
                    var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                    //plays without confirmation some cards needing a player targeted
                    if (this.isCurrentPlayerActive() && !askForConfirm) {
                        //get selected card
                        var itemsFromMyInPlay = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                        var itemsFromHand = this.playerHand.getSelectedItems();
                        if (itemsFromHand.length == 1) {
                            var card = itemsFromHand[0];

                            switch (card.type) {
                                case "SWITCHEROO":
                                case "SPY":
                                case "CAN_I_USE_THAT":
                                case "ASSASSIN":
                                case "THIEF":
                                case "NOT_DEAD_YET":
                                    this.onPlayCard();
                                    break;
                                case "ASSASSIN":
                                    if (!this.argPossibleTargetsInfo.is_crown_in_play) {
                                        this.onPlayCard();
                                    }
                                    break;
                            }
                        } else if (itemsFromMyInPlay.length == 1) {
                            var card = itemsFromMyInPlay[0];
                            switch (card.type) {
                                case "MONEY":
                                    this.onPlayCard();
                                    break;
                            }
                        }
                        else {
                            this.gtmRestoreServerGameState();
                        }
                    };
                }
            },

            onSelectDiscardedCard: function (control_name, item_id) {
                var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                if (this.isCurrentPlayerActive() && this.playingZoneDetail.getSelectedItems().length == 1 && !askForConfirm) {
                    var itemsFromHand = this.playerHand.getSelectedItems();
                    if (itemsFromHand.length == 1) {
                        var card = itemsFromHand[0];

                        //plays without confirmation 
                        switch (card.type) {

                            case "GARBAGE_COLLECTR":
                                this.onPlayCard();
                                break;
                        }
                    } else {
                        this.gtmRestoreServerGameState();
                    }
                };
            },

            onSelectSecretCard: function (evt) {

                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('takeCard');
                // This method is called when myStockControl selected items changed
                var askForConfirm = dojo.byId("askConfirmCb") && $(askConfirmCb).checked;
                if (this.isCurrentPlayerActive() && !askForConfirm) {
                    //get selected card
                    var items = this.secretZone.getSelectedItems();
                    if (items.length == 1) {
                        var card = items[0];
                        this.ajaxcall('/getthemacguffin/getthemacguffin/seenSecretCardsAction.html',
                            {
                                lock: true,
                                selected_card_id: card.id,
                            },
                            this,
                            function (result) { });

                    } else {
                        this.showMessageAndResetSelection(_('You have to select one card'));
                    }
                };
            },

            onDoneViewing: function (evt) {
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
                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('clockwise');

                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    this.ajaxcall('/getthemacguffin/getthemacguffin/specifyClockwiseAction.html',
                        {
                            lock: true,
                            clockwise: false,//we give clockwise, so we receive counterclockwise, we changed the phrasing from give clockwise to receive, so we have to reverse the boolean.
                        },
                        this,
                        function (result) { });
                };
            },

            onCounterclockwise: function (evt) {
                // Preventing default browser reaction
                dojo.stopEvent(evt);

                this.checkAction('counterclockwise');

                // This method is called when myStockControl selected items changed
                if (this.isCurrentPlayerActive()) {
                    this.ajaxcall('/getthemacguffin/getthemacguffin/specifyClockwiseAction.html',
                        {
                            lock: true,
                            clockwise: true,//we give counterclockwise, so we receive clockwise
                        },
                        this,
                        function (result) { });
                };
            },

            onTakeObject: function (evt) {
                //console.log('onTakeObject');

                // Preventing default browser reaction
                if (evt)
                    dojo.stopEvent(evt);

                this.checkAction('takeObject');
                if (this.isCurrentPlayerActive()) {
                    var cardFromOtherPlayer;
                    for (var player_id in this.inPlayStocksByPlayerId) {
                        if (player_id != this.player_id) {
                            var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                            if (selected.length == 1) {
                                cardFromOtherPlayer = selected[0];
                                //console.log("selection from other player ", cardFromOtherPlayer.type);
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
                        this.showMessageAndResetSelection(_('You have to select one card'));
                    }
                }
            },

            getSelectedObjectsFromOtherPlayers: function () {
                var cards = [];
                for (var player_id in this.inPlayStocksByPlayerId) {
                    var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                    if (selected.length == 1) {
                        if (cards.length == 0) {
                            cards[0] = selected[0];
                            //console.log("selection from other player ", card1.type);
                        } else {
                            cards[1] = selected[0];
                            //console.log("selection from other player ", card2.type);
                        }
                    }
                }
                return cards;
            },

            onSwapObjects: function (evt) {
                //console.log('onSwapObjects');

                // Preventing default browser reaction
                if (evt)
                    dojo.stopEvent(evt);

                this.checkAction('swapObjects');

                if (this.isCurrentPlayerActive()) {
                    var cards = this.getSelectedObjectsFromOtherPlayers();
                    var card1 = cards[0];
                    var card2 = cards[1];
                    for (var player_id in this.inPlayStocksByPlayerId) {
                        var selected = this.inPlayStocksByPlayerId[player_id].getSelectedItems();
                        if (selected.length == 1) {
                            if (!card1) {
                                card1 = selected[0];
                                //console.log("selection from other player ", card1.type);
                            } else {
                                card2 = selected[0];
                                //console.log("selection from other player ", card2.type);
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
                        this.showMessageAndResetSelection(_('You have to select 2 cards from different players'));
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
                            // console.log("selection from other player ", cardFromOtherPlayer.type);
                        }
                    }
                }
                return cardFromOtherPlayer;
            },

            onPlayCard: function (evt) {
                //console.log('onPlayCard');

                // Preventing default browser reaction
                if (evt)
                    dojo.stopEvent(evt);

                this.checkAction('playCard');



                var itemsFromHand = this.playerHand.getSelectedItems();
                var itemsFromInPlayZone = this.inPlayStocksByPlayerId[this.player_id].getSelectedItems();
                var error = false;

                var cardFromOtherPlayer = this.getSelectedInPlayCard();
                if ((itemsFromHand.length == 0 && itemsFromInPlayZone.length == 0)
                    || (itemsFromHand.length == 1 && itemsFromInPlayZone.length == 1)) {
                    this.showMessageAndResetSelection(_('You have to select one card to play'));
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
                        case "GARBAGE_COLLECTR":
                            var itemsFromDiscard = this.playingZoneDetail.getSelectedItems();
                            if (itemsFromDiscard.length != 1 && this.playingZoneDetail.count() > 0) {
                                this.showMessageAndResetSelection(_('You have to select one card from the discard'));
                                error = true;
                            }
                            if (this.playingZoneDetail.getSelectedItems()) {
                                cardFromOtherPlayer = this.playingZoneDetail.getSelectedItems()[0];
                            }
                            break;
                        default:

                    }
                    //back call
                    if (!error) {
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
                }
            },

            onDiscard: function (evt) {
                //console.log('onDiscard');
                this.checkAction('discard');

                // Preventing default browser reaction
                if (evt)
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
                    this.showMessageAndResetSelection(_('You have to select an object in play to discard'));
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
                //console.log('notifications subscriptions setup');

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
                dojo.subscribe('playingZoneDetailChange', this, "notif_playingZoneDetailChange");
                dojo.subscribe('gtmplayerEliminated', this, "notif_playerEliminated");

                dojo.connect(this.playerHand, 'onChangeSelection', this, 'onSelectCard');
            },

            notif_handChange: function (notif) {
                //console.log('notif_handChange', notif.args);
                if (notif.args.reset) {
                    this.playerHand.removeAll();
                }
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.added[i];
                        //console.log("notif_handChange add card id/type :" + card.id + " " + card.type);
                        if (card.location === "deck") {
                            from = "tomb_count";
                        } else if (card.location === "discard") {
                            from = "discard_pile";
                        } else if (card.location_arg) {
                            from = "cards_in_play_" + card.location_arg;
                        }
                        this.playerHand.addToStockWithId(card.type, card.id, from);
                    }
                }
                if (notif.args.removed) {
                    for (var i in notif.args.removed) {
                        var card = notif.args.removed[i];
                        //console.log("notif_handChange remove card id/type :" + card.id + " " + card.type);
                        this.playerHand.removeFromStockById(card.id);
                    }
                }
            },

            notif_inPlayChange: function (notif) {
                $player_id = notif.args.player_id;
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.added[i];
                        //console.log("notif_inPlayChange add card id/type :" + card.id + " " + card.type);
                        this.inPlayStocksByPlayerId[$player_id].addToStockWithId(card.type, card.id);
                    }
                }
                if (notif.args.removed) {
                    for (var i in notif.args.removed) {
                        var card = notif.args.removed[i];
                        //console.log("notif_inPlayChange remove card id/type :" + card.id + " " + card.type);
                        this.inPlayStocksByPlayerId[$player_id].removeFromStockById(card.id);

                    }
                }
                if (notif.args.discarded) {
                    for (var i in notif.args.discarded) {
                        var card = notif.args.discarded[i];
                        this.playingZoneDetail.addToStockWithId(card.type, card.id, "cards_in_play_" + card["location_arg"]);
                        this.inPlayStocksByPlayerId[$player_id].removeFromStockById(card.id);
                    }
                }
            },

            notif_cardPlayed: function (notif) {
                //console.log('notif_cardPlayed', notif);
                var card = notif.args.card;

                if (notif.args.toInPlay) {
                    if (!notif.args.uses) {
                        if (card.location === "inPlay" && card.location_arg != notif.args.player_id) {
                            from = "cards_in_play_" + card.location_arg;//swaps (money)
                        } else if (this.player_id == notif.args.player_id) {
                            from = "myhand";
                        }
                        else {
                            from = "overall_player_board_" + notif.args.player_id;
                        }
                        this.inPlayStocksByPlayerId[notif.args.player_id].addToStockWithId(card.type, card.id, from);
                    }
                }
                else {

                    if (card.location === "inPlay") {
                        from = "cards_in_play_" + notif.args.player_id;
                    }
                    else if (card.location === "hand") {
                        if (this.player_id == notif.args.player_id) {
                            from = "myhand";
                        }
                        else {
                            from = "overall_player_board_" + notif.args.player_id;
                        }
                    } else if (this.player_id == notif.args.player_id) {
                        from = "myhand";
                    } else {
                        from = "cards_in_play_" + notif.args.player_id;
                    }
                    this.discard.removeAll();
                    this.discard.addToStockWithId(card.type, card.id, from);
                    this.playingZoneDetail.addToStockWithId(card.type, card.id);
                }

                if (this.player_id == notif.args.player_id) {
                    this.playerHand.removeFromStockById(card.id);
                }
                if (notif.args.discarded) {
                    this.inPlayStocksByPlayerId[notif.args.player_id].removeFromStockById(card.id);
                }

                //animates void cards
                if (card.type == "HIPPIE" || card.type == "SHRUGMASTER" || card.type == "MARSHALL") {
                    var that = this;
                    setTimeout(function () { that.animate(card.type, 'discard_pile'); }, 1300);
                }
            },

            notif_playingZoneDetailChange: function (notif) {
                if (notif.args.removed) {
                    for (var i in notif.args.removed) {
                        var card = notif.args.removed[i];
                        this.playingZoneDetail.removeFromStockById(card.id);
                    }
                }
                if (notif.args.added) {
                    for (var i in notif.args.added) {
                        var card = notif.args.added[i];
                        this.playingZoneDetail.addToStockWithId(card.type, card.id);
                    }
                }
                if (notif.args.reset_in_play_player_id) {
                    this.inPlayStocksByPlayerId[notif.args.reset_in_play_player_id].removeAll();//zombie clean
                }

            },

            notif_secretCards: function (notif) {
                this.displaySecretCards(notif.args.args);
            },

            notif_revelation: function (notif) {
                //console.log('notif_cardPlayed', notif);
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

            notif_playerEliminated: function (notif) {
                var player_id = notif.args.player_id;
                this.disablePlayerPanel(player_id);
                dojo.addClass("in_play_" + player_id, "gtm_dead_player");
            },

        });
    });
