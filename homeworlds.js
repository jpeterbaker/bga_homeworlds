/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Homeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * homeworlds.js
 *
 * Homeworlds user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo",
    "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare( "bgagame.homeworlds", ebg.core.gamegui, {
    constructor: function(){
        // TRANSLATIONS DON'T WORK HERE
        //this.size_names_local = {1:_('small'),2:_('medium'),3:_('large')};
        // Once homeworlds are established,
        // colony_assignments[size] will be the position (1,2, or 3)
        // where colonies with stars of the given size belong
        // (in a colony_container)
        this.colony_assignments = {1:1,2:2,3:3};
    },

    /*
    setup:

    This method must set up the game user interface according to
    current game situation specified in parameters.

    The method is called each time the game interface is displayed to a player, ie:
    _ when the game starts
    _ when a player refreshes the game page (F5)

    "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
    */
    setup: function(gamedatas) {
        // String setup (since translations don't work in constructor)
        this.color_names_eng = {1:'red',2:'yellow',3:'green',4:'blue'};
        this.color_names_local = {1:_('Red'),2:_('Yellow'),3:_('Green'),4:_('Blue')};
        this.action_names_local = {1:_('Capture'),2:_('Move'),3:_('Build'),4:_('Trade')};
        this.size_names_eng = {1:'small',2:'medium',3:'large'};

        // Remember player positions
        var player;
        for(var player_id in gamedatas.players){
            player = gamedatas.players[player_id];
            this['player_'+player.player_no] = player_id;
        }

        // Display first player indicator in the player panel
        var player_label = _('First player');
        dojo.place(
            "<div id='HWfirst_player_indicator'>"+player_label+"</div>",
            'player_board_'+this.player_1
        );
        document.getElementById('HWfirst_player_indicator').innerHTML = player_label;

        //////////////////////////////////
        // Set up buttons and power box //
        //////////////////////////////////

        this.setup_action_buttons();

        dojo.place(
            'HWpowerBox',
            'maintitlebar_content'
        );
        var color,button;
        for(color=1;color<=4;color++){
            button = document.getElementById('HWpowerButton'+color);
            button.innerHTML = this.action_names_local[color];
            this.connect(button,'onclick','power_button_clicked');
        }

        ///////////////////
        // Set up legend //
        ///////////////////
        var label,label_html;
        for(color=1;color<=4;color++){
            label = document.getElementById('HWlegend_label'+color);
            label_html = this.format_block(
                'jstpl_legend_label',
                {
                    colorname_local : this.color_names_local[color],
                    colorname_eng   : this.color_names_eng[color],
                    actionname      : this.action_names_local[color]
                }
            );
            label.innerHTML = label_html;
        }

        this.setup_pieces(gamedatas);

        this.connectClass('HWstack','onclick','stack_clicked');

        // Setup turn token
        var token_pos;
        if(this.getActivePlayerId() == this.get_bot_player())
            token_pos = 'bot';
        else
            token_pos = 'top';
        var token_space = document.getElementById('HWtoken_space_'+token_pos);
        dojo.place("<div id='HWturn_token'></div>",token_space);
        var token = document.getElementById('HWturn_token')
        this.connect(token,'onclick','token_clicked');

        this.browser_adapt();

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();
    },

    setup_action_buttons: function(){
        // The right place seems to be just before generalactions
        // generalactions would be the parent of ordinary BGA buttons
        var dest = document.getElementById('generalactions');
        dojo.place('HWcatastropheButton',dest,'before');
        dojo.place('HWsacrificeButton'  ,dest,'before');
        dojo.place('HWpassButton'       ,dest,'before');
        dojo.place('HWdrawButton'       ,dest,'before');
        dojo.place('HWrestartButton'    ,dest,'before');
        dojo.place('HWcancelButton'     ,dest,'before');

        // These buttons always have the same text,
        // so it can be set now
        button = document.getElementById('HWcatastropheButton');
        button.innerHTML = _('Trigger catastrophe');

        button = document.getElementById('HWsacrificeButton');
        button.innerHTML = _('Sacrifice ship');

        button = document.getElementById('HWrestartButton');
        button.innerHTML = _('Restart turn');

        button = document.getElementById('HWcancelButton');
        button.innerHTML = _('Cancel');

        this.connect(
            document.getElementById('HWcatastropheButton'),
            'onclick',
            function(evt){
                evt.preventDefault();
                this.catastrophe_button_selected();
            }
        );

        this.connect(
            document.getElementById('HWsacrificeButton'),
            'onclick', //dojo.hitch( this, TODO verify this works
            function(evt){
                evt.preventDefault();
                this.sacrifice_button_selected();
            }
            //),
        );

        this.connect(
            document.getElementById('HWpassButton'),
            'onclick',
            function(evt){
                evt.preventDefault();
                this.pass_button_selected();
            }
        );

        this.connect(
            document.getElementById('HWdrawButton'),
            'onclick',
            function(evt){
                evt.preventDefault();
                this.draw_button_selected();
            }
        );

        this.connect(
            document.getElementById('HWcancelButton'),
            'onclick',
            function(evt){
                evt.preventDefault();
                this.cancel_action();
            }
        );

        this.connect(
            document.getElementById('HWrestartButton'),
            'onclick',
            function(evt){
                evt.preventDefault();
                this.restart_button_selected();
            }
        );
    },

    setup_pieces: function(gamedatas){
        // Create the piece nodes
        var colornum,pipsnum;
        var params;
        var piece_id,stack_id;
        // Create bank pieces
        for(piece_id in gamedatas.bank){
            piece = gamedatas.bank[piece_id];
            stack_id = 'HWstack_'+piece.color+'_'+piece.pips;
            this.setup_piece(piece,'HWbanked',stack_id);
        }
        // Create systems and their pieces
        var system;
        for(system_id in gamedatas.systems) {
            system = gamedatas.systems[system_id];
            this.setup_system(system);
        }
        // If colonies already existed,
        // they were placed according to old colony assignments
        // It's not enough to just remake colony assignments
        this.arrange_colonies();

        this.connectClass('HWpiece','onclick','piece_clicked');
    },

    clear_all: function(){
        var pieces_and_systems = dojo.query('.HWsystem,.HWship,.HWstar,.HWbanked');
        pieces_and_systems.remove();
    },

    // Browser-specific setup
    browser_adapt: function(){
        // THIS APPEARS TO BE UNNECESSARY
        // because BGA seems to add dj_browsername to the html element
        return;

        // Prevent animated token shadow on Safari
        // (it makes ugly artifacts)
        var ua = navigator.userAgent;
        // Chrome includes "Safari" in it's useragent name and I have no idea why
        // but only Safari itself seems to have this shadow problem
        var isChrome = ua.indexOf('Chrome') >= 0;
        var isSafari = ua.indexOf('Safari') >= 0;
        if(isSafari && !isChrome){
            dojo.query('html').addClass('no_slide_shadow');
        }
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState:
    // This method is called each time we are entering into a new game state.
    // You can use this method to perform user interface changes.
    onEnteringState: function( state_name, args ) {
        // Current player saves most recent info from server
        // to make it easier to cancel partial actions from client states.
        // All server-side player-decision states start with "want"
        if(this.isCurrentPlayerActive() && state_name.startsWith('want'))
            this.set_turn_checkpoint(args,state_name);
        this['client_state'] = state_name;
        // Call appropriate method
        var methodName = 'onEntering_' + state_name;
        if (this[methodName] !== undefined)
            this[methodName](args.args);
    },

    onEntering_want_creation: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var stacks = dojo.query('.HWstack');
        stacks.addClass('HWselectable');
        this.add_tooltip(
            stacks,
            _('Click to add this star to your homeworld'),
            1500
        );
    },

    onEntering_client_want_creation_ship: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var stacks = dojo.query('.HWstack');
        stacks.addClass('HWselectable');
        this.add_tooltip(
            stacks,
            _('Click to add this ship to your homeworld'),
            1500
        );
    },

    onEntering_client_want_creation_confirmation: function(args){
        if(!this.isCurrentPlayerActive())
            return
        // The buttons are handled in onUpdateActionButtons
        dojo.addClass('HWturn_token','HWonly_option');
    },

    onEntering_want_free: function(args){
        console.log('want free')
        if(!this.isCurrentPlayerActive())
            return
        var ships = dojo.query('.HWship.HWfriendly');
        console.log('found ships',ships)
        ships.addClass('HWselectable');
        this.add_tooltip(
            ships,
            _('Click to activate or sacrifice this ship'),
            1500
        );
    },

    onEntering_want_sacrifice_action: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var ships = dojo.query('.HWship.HWfriendly');
        ships.addClass('HWselectable');
        var tooltip;
        switch(parseInt(args.color)){
            case 1:
                tooltip = _('Click to give this ship capturing power');
                break;
            case 2:
                tooltip = _('Click to give this ship movement power');
                break;
            case 3:
                tooltip = _('Click to build another ship of this color');
                break;
            case 4:
                tooltip = _('Click to trade this ship for another color');
                break;
            default:
                console.error('Bad power number: '+args.color);
        }
        this.add_tooltip(
            ships,
            tooltip,
            1500
        );
    },

    onEntering_client_want_power: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var activatequery = dojo.query('[activate]');
        var activatednode = activatequery[0];
        // The system that the activated ship is in
        var systemnode = this.get_system(activatednode);
        // Candidates for activating technology
        var candidates = dojo.query('.HWstar,.HWfriendly.HWship',systemnode);
        candidates.addClass('HWselectable');
        this.add_power_tooltips(candidates.concat(activatequery));
    },

    onEntering_want_restart_turn: function(args){
        if(!this.isCurrentPlayerActive())
            return
        dojo.addClass('HWturn_token','HWonly_option');
    },

    // Set the function f to be called when any element of nodes is clicked
    // f should accept a click event as a parameter
    connect_nodes: function(nodes, f){
        for(var i=0;i<nodes.length;i++)
            this.connect(nodes[i],'onclick',f);
    },

    add_tooltip: function(nodes,tip,delay){
        // Replacing addTooltipHtmlToClass which doesn't seem to delay
        // when a text arg is missing
        var node;
        for(var i=0;i<nodes.length;i++){
            node = nodes[i];
            this.addTooltip(node.id,tip,'',delay);
        }
    },

    add_power_tooltips: function(pieces){
        var piece,color;
        for(var i=0;i<pieces.length;i++){
            piece = pieces[i];
            color = this.get_color(piece);
            switch(color){
                case 1:
                    this.addTooltip(
                        piece.id,
                        _('Click to choose capturing power'),
                        '',1500
                    );
                    break;
                case 2:
                    this.addTooltip(
                        piece.id,
                        _('Click to choose movement power'),
                        '',1500
                    );
                    break;
                case 3:
                    this.addTooltip(
                        piece.id,
                        _('Click to choose build power and build a new ship in the color of the activated ship'),
                        '',1500
                    );
                    break;
                case 4:
                    this.addTooltip(
                        piece.id,
                        _('Click to choose trade power'),
                        '',1500
                    );
                    break;
                default:
                    console.error('Bad power number: '+color);
            }
        }
    },

    onEntering_client_want_target: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var activatednode = dojo.query('[activate]')[0];
        var power = parseInt(activatednode.getAttribute('activate'));
        var targets;
        var tooltip;
        switch(power){
            case 1:
                targets = this.power_targets(activatednode,power);
                if(targets.length==0){
                    this.showMessage( _('There are no enemy ships that can be captured by that ship.'), 'error');
                    this.cancel_action();
                    return;
                }
                targets.addClass('HWselectable');
                tooltip = _('Click this ship to capture it');
                this.add_tooltip(targets,tooltip,1500);
                break;
            case 2:
                var systemnode = this.get_system(activatednode);
                var systems = this.connected_systems(systemnode);
                var stacks = this.connected_stacks(systemnode);
                if(systems.length==0 && stacks.length==0){
                    this.showMessage( _('There are no connected destinations for that ship.'), 'error');
                    this.cancel_action();
                    return;
                }

                tooltip = _('Click to move activated ship to this system');
                this.add_tooltip(systems,tooltip,1500);
                systems.addClass('HWselectable');

                tooltip = _('Click to move activated ship to a new system with this star');
                this.add_tooltip(stacks,tooltip,1500);
                stacks.addClass('HWselectable');
                break;
            case 3:
                //tooltip = '';
                //this.add_tooltip(targets,tooltip,1500);
                break;
            case 4:
                targets = this.power_targets(activatednode,power);
                if(targets.length==0){
                    this.showMessage( _('There are no available pieces that can be traded for that ship.'), 'error');
                    this.cancel_action();
                    return;
                }
                targets.addClass('HWselectable');
                tooltip = _('Click to trade for a ship of this color');
                this.add_tooltip(targets,tooltip,1500);
                break;
            default:
                console.error('Bad power number: '+power);
        }
    },

    onEntering_client_want_catastrophe_target: function(args){
        if(!this.isCurrentPlayerActive())
            return
        var overpopulated = this.get_overpopulated_pieces();
        overpopulated.addClass('HWselectable HWoverpopulated');
        this.add_tooltip(
            overpopulated,
            _('Click to destroy all pieces of this color in this system'),
            1500
        );
    },

    power_targets: function(activatednode,power){
        var systemnode = this.get_system(activatednode);
        // Nodes to highlight
        power = parseInt(power);
        switch(power){
            case 1:
                return dojo.query('.HWhostile.HWship',systemnode);
            case 2:
                return this.connected_systems(systemnode).concat(
                    this.connected_stacks(systemnode));
            case 3:
                // Build target is selected automatically, so don't highlight anything
                return dojo.NodeList();
            case 4:
                // TODO return candidates rather than highlighting manually
                var targets = dojo.NodeList();
                var old_color = this.get_color(activatednode);
                var pips = this.get_size(activatednode);
                var stack,children;
                for(var i=1;i<=4;i++){
                    if(i==old_color)
                        continue;
                    stack = document.getElementById('HWstack_'+i+'_'+pips);
                    children = dojo.query('.HWbanked',stack);
                    if(children.length==0)
                        continue;
                    targets.push(stack);
                }
                return targets;
            default:
                console.error('Bad power number: '+power);
        }
    },

    get_overpopulated_pieces: function(){
        var systems = dojo.query('.HWsystem');
        var targets = dojo.NodeList();

        var system,i,j,color,result;
        for(i=1;i<=4;i++){
            color = '.HW'+this.color_names_eng[i];
            for(j=0;j<systems.length;j++){
                system = systems[j];
                result = dojo.query(color,system);
                if(result.length>=4){
                    targets = targets.concat(result);
                }
            }
        }
        return targets;
    },

    // onLeavingState:
    // This method is called each time we are leaving a game state.
    // You can use this method to perform user interface changes.
    onLeavingState: function( state_name ) {
        // TODO get smarter about when and how to hide buttons
        dojo.query('.HWactionButton').addClass('HWdisabled');
        // Call appropriate method
        var methodName = "onLeaving_" + state_name;
        if (this[methodName] !== undefined)
            this[methodName]();
    },
    onLeaving_client_want_creation_confirmation: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
        dojo.removeClass('HWturn_token','HWonly_option');
    },
    onLeaving_client_want_creation_ship: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
    },
    onLeaving_want_free: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
    },
    onLeaving_want_sacrifice_action: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
    },
    onLeaving_client_want_power: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
        // client_want_power is the only state where button box appears
        dojo.addClass('HWpowerBox','HWdisabled');
    },
    onLeaving_client_want_target: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
        this.deactivate_all();
    },
    onLeaving_client_want_catastrophe_target: function(args){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
        var selectable = dojo.query('.HWoverpopulated');
        selectable.removeClass('HWoverpopulated')
    },
    onLeaving_want_restart_turn: function(){
        if(!this.isCurrentPlayerActive())
            return
        this.deselect_all();
        dojo.removeClass('HWturn_token','HWonly_option');
    },

    // onUpdateActionButtons:
    // in this method you can manage "action buttons" that are displayed in the
    // action status bar (ie: the HTML links in the status bar).

    // This function gets called just before onEnteringState with the same parameters.
    onUpdateActionButtons: function( state_name, args ) {
        // Only active players get buttons
        if(!this.isCurrentPlayerActive())
            return;
        // Only choice states get buttons
        if(!state_name.startsWith('want') && !state_name.startsWith('client'))
            return;

        // Set default pass button params
        // These will be changed if passing is not expected,
        // i.e., if there are things the player could still do
        var pass_button_message = _('End turn');
        var pass_button = document.getElementById('HWpassButton');
        dojo.removeClass(pass_button,'HWhilit');
        switch(state_name){
            // Server choice states get catastrophe button
            case 'want_free':
            case 'want_sacrifice_action':
            case 'want_catastrophe':
                // This needs to be checked in free and sacrifice action cases
                if(this.get_overpopulated_pieces().length > 0){
                    dojo.query('#HWcatastropheButton').removeClass('HWdisabled');
                }
                // In these states, there are still actions available
                // Make the pass button an alarming color
                pass_button_message = _('Pass');
                dojo.addClass(pass_button,'HWhilit');
            // NO BREAK
            // The above states get pass, draw, and restart buttons
            // which get added lower down
            case 'want_restart_turn':
                this.setup_draw_button(args);
            // NO BREAK
            // The above states get pass and restart buttons
            // which get added lower down
            case 'client_want_creation_confirmation':
                pass_button.innerHTML = pass_button_message;
                dojo.removeClass(pass_button,'HWdisabled');
                // Token needs to be selectable when pass button is available
                this.selectablize_token();
            // NO BREAK
            // The above states get restart button
            // which gets added lower down
            case 'want_creation':
            case 'client_want_creation_ship':
                // Before creation is complete,
                // there is only a restart button
                dojo.query('#HWrestartButton').removeClass('HWdisabled');
                break;
            case 'client_want_power':
                this.show_button_box();
                dojo.query('#HWsacrificeButton').removeClass('HWdisabled');
                // NO BREAK
                // The client_want_power state gets both
                // a sacrifice and cancel button
            case 'client_want_catastrophe_target':
            case 'client_want_target':
                dojo.query('#HWcancelButton').removeClass('HWdisabled');
                break;
        }
    },

    setup_draw_button: function(args){
        var button = document.getElementById('HWdrawButton');
        dojo.removeClass(button,'HWdisabled');
        if(args.draw_offerer == 0){
            // No one has offered a draw yet
            button.innerHTML = _('Offer draw');
            dojo.removeClass(button,'HWhilit');
        }
        else if(args.draw_offerer == this.player_id){
            // This player has already offered a draw
            button.innerHTML = _('Cancel draw offer');
            dojo.removeClass(button,'HWhilit');
        }
        else{
            // The other player offered a draw
            // or the other player brought the game to the same state 3+ times
            button.innerHTML = _('Accept draw and end game');
            dojo.addClass(button,'HWhilit');
        }
    },

    show_button_box: function(){
        // Show the power button box and make every button's state
        // correspond to its availability in the system
        var activatednode = dojo.query('[activate]')[0];
        var systemnode = this.get_system(activatednode);
        var power_providers = dojo.query('.HWfriendly,.HWstar',systemnode);
        var i,color,button;
        var available = {1:0,2:0,3:0,4:0};
        for(i=0;i<power_providers.length;i++){
            color  = this.get_color(power_providers[i]);
            available[color] = 1;
        }
        for(color=1;color<=4;++color){
            button = document.getElementById('HWpowerButton'+color);
            if(available[color]){
                dojo.removeClass(button,'HWdisabled');
            }
            else{
                dojo.addClass(button,'HWdisabled');
            }
        }
        dojo.removeClass('HWpowerBox','HWdisabled');
    },

    power_button_clicked: function(evt){
        evt.preventDefault();
        var button = evt.currentTarget;
        if(dojo.hasClass(button,'HWdisabled')){
            this.showMessage(
                _('That power is not available to you in the system of the selected ship.'),
                'error'
            );
            return;
        }
        var color = button.id[button.id.length-1];
        this.power_selected(color);
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    get_color: function(piecenode){
        return parseInt(piecenode.getAttribute('ptype').split('_')[0]);
    },
    get_size: function(piecenode){
        return parseInt(piecenode.getAttribute('ptype').split('_')[1]);
    },
    get_id: function(piecenode){
        return parseInt(piecenode.id.split('_')[1]);
    },

    get_system: function(piecenode){
        // Get the system node containing this piece
        var par = piecenode;
        while(!dojo.hasClass(par,'HWsystem')){
        //while(!par.id.startsWith('HWsystem')){
            par = par.parentNode;
            if(par === undefined || par.id === undefined){
                this.showMessage( _('Piece is not in a system.'), 'error');
                return null;
            }
        }
        return par;
    },

    get_bad_home_warning: function(){
        // Warn the player if they don't have green, blue, and a large ship
        var systemnode = dojo.query('[homeplayer_id=player_'+this.player_id+']')[0];
        var blues = dojo.query('.HWblue',systemnode);
        var greens = dojo.query('.HWgreen',systemnode);
        var large_ships = dojo.query('.HWlarge.HWship',systemnode);

        var stars = dojo.query('.HWstar',systemnode);
        var star_match = this.get_color(stars[0]) == this.get_color(stars[1]);
        var gemini     = this.get_size (stars[0]) == this.get_size (stars[1]);

        var message = '';
        if(star_match)
            message += ' '+_('Your stars are the same color and will be very vulnerable to catastrophe.');
        if(large_ships.length == 0)
            message += ' '+_('You do not have a large ship and will be vulnerable to direct assault.');
        if(greens.length == 0)
            message += ' '+_('You do not have a green piece and cannot build.');
        if(blues.length == 0)
            message += ' '+_('You do not have a blue piece and cannot diversify.');
        if(gemini)
            message += ' '+_('Your stars are the same size and your home will be connected to more systems, which may be confusing.');
        if(message.length > 0){
            // At least one problem was detected
            message =
                '<span style="color:red">'
                + _('WARNING: Your homeworld has the following problems:')
                + '</span>'
                + message
                + ' <span style="color:red">'
                + _('You should restart your turn unless this was deliberate.')
                + '</span>';
        }
        //this.confirmationDialog(message);
        //this.multipleChoiceDialog(message,['OK'],()=>{});
        return message;
    },

    // Set up the global variable this.colony_assignments
    setup_colony_assignments: function(){
        var player_id_top = this.get_top_player();
        var player_id_bot = this.get_bot_player();
        var home_top = dojo.query('[homeplayer_id=player_'+player_id_top+']');
        var home_bot = dojo.query('[homeplayer_id=player_'+player_id_bot+']');

        if(home_top.length == 0 || home_bot.length == 0){
            // Creation is not finished
            return;
        }

        var top_stars = dojo.query('.HWstar',home_top[0]);
        var bot_stars = dojo.query('.HWstar',home_bot[0]);
        var all_stars = {1:top_stars,2:bot_stars};
        /*
         groupings[0]: an array of the sizes of colony stars that are connected to neither homeworld
         groupings[1]: star sizes that are connected only the home of the player displayed at the top
         groupings[2]: only to the home of the player displayed at the bottom
         groupings[3]: to both
        */
        var groupings = [[],[],[],[]];
        var pips;
        var groupi,group,connects,i,stars,star,home_num;
        for(pips=1;pips<=3;pips++){
            // groupi will match the index in groupings
            groupi = 0;
            // For each of the two homes, 1 for top and 2 for bot
            for(home_num=1;home_num<=2;home_num++){
                stars = all_stars[home_num];
                // Check if a star of size pips connects to these stars
                connects = 1;
                for(i=0;i<stars.length;i++){
                    star = stars[i];
                    if(pips == this.get_size(star)){
                        connects = 0;
                        break;
                    }
                }
                if(connects){
                    groupi += home_num;
                }
            }
            groupings[groupi].push(pips);
        }
        var adjacent_row;
        // Handle "both" and "neither" groups
        var groups = [0,3];
        for(i in groups){
            groupi = groups[i];
            group = groupings[groupi];
            if(group.length == 0)
                continue;
            // If there is exactly one star size that connects to neither home,
            // (likewise to both homes)
            // it makes sense to put that size in the middle
            // (The weird case is when there's exactly one in the "neither" category
            // and exactly one in the "both" category")
            if(group.length == 1){
                this.colony_assignments[group[0]] = 2;
                if(groupi==0 && groupings[3].length==1){
                    // Here's the weird case:
                    // put the "neither" in the middle
                    // and "both" by the less-connected home,
                    var both = groupings[3][0];
                    var less_connected;
                    if(groupings[1].length==1)
                        less_connected = 2;
                    else
                        less_connected = 1;
                    // Colony row that's 
                    adjacent_row = 2*less_connected-1;
                    this.colony_assignments[both] = adjacent_row;
                    // The lower loop about singly-connected stars
                    // Can work out the more-connected system
                    break;
                }
            }
            else{
                this.colony_assignments[group[0]] = 1;
                this.colony_assignments[group[1]] = 3;
            }
        }
        // Handle "top only" and "bot only" groups
        groups = [1,2];
        for(i in groups){
            groupi = groups[i];
            group = groupings[groupi];
            if(group.length == 0)
                continue;
            adjacent_row = 2*groupi-1;
            // If there is any star size that connects to only one home,
            // it makes sense to put that one such size next to that home.
            this.colony_assignments[group[0]] = adjacent_row;
            // If there's another size that connects to only that home,
            // then it goes in the middle
            if(group.length > 1)
                this.colony_assignments[group[1]] = 2;
        }
    },

    // Remember server state for easy partial action canceling
    set_turn_checkpoint: function(args,state_name){
        // args needs to be deeply copied because
        // it appears to be overwritten on state change
        //this['latest_args'] = this.deepcopy(args);
        this['latest_args'] = {
            state_name: state_name,
            descriptionmyturn: args.descriptionmyturn,
            args: args.args
        };
    },

    deepcopy: function(x){
        // Not fast, but easy to write
        return JSON.parse(JSON.stringify(x));
    },

    /*
    Return a piecenode in this stack node
    The piece is NOT removed from the stack
    For consistency, the highest-index piece is returned
    (this can prevent soft-lock during tutorials).
    */
    get_piece_in_stack: function(stacknode){
        var children = stacknode.children;
        var hi = -1;
        var piecenode = null;
        var child,pid;
        for(var i=0;i<children.length;++i){
            child = children[i];
            pid = this.get_id(child);
            if(pid>hi){
                hi = pid;
                piecenode = child;
            }
        }
        return piecenode;
    },

    put_in_bank: function(piecenode){
        var ani_origin = this.place_animation_marker(piecenode,true);

        var systemnode = this.get_system(piecenode);
        dojo.removeClass(piecenode,'HWfriendly HWhostile HWstar HWship HWoverpopulated');
        piecenode.removeAttribute('activate');
        dojo.addClass(piecenode,'HWbanked');
        var color = this.get_color(piecenode);
        var pips = this.get_size(piecenode);
        var stacknode = document.getElementById('HWstack_'+color+'_'+pips);
        dojo.place(piecenode,stacknode,'first');
        // TODO be smarter about when this is done
        this.on_system_change(systemnode);

        var ani_target = this.place_animation_marker(piecenode,false);
        this.slide_between(piecenode,ani_origin,ani_target);
    },

    // Make a system at setup from JSON object (creating new pieces)
    setup_system: function(system){
        var star_size = null;
        if(system.homeplayer_id == null){
            // This is a colony, so find its star size
            for(star_id in system.stars){
                star_size = system.stars[star_id].pips;
                break;
            }
        }
        var systemnode = this.place_system(
            system.system_id,
            system.system_name,
            system.homeplayer_id,
            star_size
        );

        var ship_id,star_id;

        // player ID of the player whose ships should point north
        var friendly_id = this.get_bot_player();
        // Add ships
        for(ship_id in system.ships){
            ship = system.ships[ship_id];
            if(friendly_id == ship.owner_id)
                this.setup_piece(ship,'HWfriendly HWship',systemnode);
            else
                this.setup_piece(ship,'HWhostile HWship',systemnode);
        }

        // Add stars
        var starcontainer = dojo.query('.HWstar_container',systemnode)[0];
        for(star_id in system.stars){
            star = system.stars[star_id];
            this.setup_piece(star,'HWstar',starcontainer);
        }
        this.on_system_change(systemnode);
    },

    setup_piece: function(piece,more_classes,container){
        var params = {
            piece_id     : piece.piece_id,
            colorname    : this.color_names_eng[piece.color],
            pipsname     : this.size_names_eng[piece.pips],
            colornum     : piece.color,
            pipsnum      : piece.pips,
            more_classes : more_classes
        };
        var piece_html = this.format_block('jstpl_piece',params);
        dojo.place(piece_html,container);
    },

    ajaxcallwrapper: function(action, args, err_handler) {
        // this allows to skip args parameter for action which do not require them
        if (!args)
            args = [];
        // Avoid rapid clicking problems
        args.lock = true;
        // Check that player is active and action is declared
        if (this.checkAction(action)) {
            // this is mandatory fluff
            this.ajaxcall(
                "/" + this.game_name + "/" + this.game_name + "/" + action + ".html",
                args,
                this,
                // Success result handler is mandatory argument but not needed
                // If everything goes as expected, notifications are used to update
                (result) => {},
                // The optional error handler param  is "seldom needed"
                err_handler
            );
        }
    },

    /*
    Create and return an un-displayed node that is in the location of the given node.
    The given node can then be moved in the DOM tree and then animated from
    the un-displayed node to its new location

    If old is set to true, piecenode is going to be taken from the DOM tree,
    so the marker needs to save the spot in the DOM tree.
    If old is false, the piecenode is already in the new place in the DOM tree,
    so the marker needs to save the new display location and its place in the DOM tree doesn't matter.

    The markers should be destroyed when unneeded to avoid (small) memory leaks.
    Destruction will be performed by slide_between if marker is passed
    */
    place_animation_marker: function(piecenode,old=true){
        var html = "<div class='HWanimarker' id='"+piecenode.id+old+"'></div>";
        var marker = dojo.place(html,'HWboard');
        // Get the current style including CSS stuff
        //var style = getComputedStyle(piecenode);
        // Give the same margins to marker
        //console.log('margin style:',style.margin);
        //dojo.setStyle(marker,'margin',style.margin);
        //dojo.setStyle(marker,'height',style.height);
        //dojo.setStyle(marker,'width',style.width);

        if(!old){
            // The node is in its new position now. Just save it.
            this.placeOnObject(marker,piecenode);
            return marker;
        }

        // The node is in its old position.
        // Put the marker next to it in the DOM tree
        // so the marker will move correspondingly if elements shift
        // when the node changes in the tree.
        if(piecenode.id=='HWturn_token' || dojo.hasClass(piecenode,'HWbanked')){
            this.placeOnObject(marker,piecenode.parentNode);
        }
        else if(dojo.hasClass(piecenode,'HWstar')){
            dojo.place(marker,piecenode,'after');
        }
        else if(dojo.hasClass(piecenode,'HWfriendly')){
            dojo.addClass(marker,'HWfriendly');
            dojo.place(marker,piecenode,'after');
        }
        else if(dojo.hasClass(piecenode,'HWhostile')){
            dojo.addClass(marker,'HWhostile');
            dojo.place(marker,piecenode,'after');
        }
        else{
            this.showMessage(
                'Attempting unknown animation.',
                'error'
            );
        }
        return marker;
    },

    /*
    Run an animation moving node from origin to target (all nodes)
    The node should already be in the desired place in the DOM tree

    origin and target nodes will be deleted when animation is complete
    */
    slide_between: function(node,origin,target,delay=0,remove_markers=true){
        var t = 400;
        var delay_html = "<div id='HWdelayer"+node.id+"' style='display:none'></div>";
        var delayer = dojo.place(delay_html,'HWboard');
        dojo.addClass(node,'HWsliding');
        this.placeOnObject(node,origin);
        var animation_slide = this.slideToObject(node.id,target.id,t,delay);
        var animation_delay = this.slideToObject(delayer.id,delayer.id,0,delay+t+500);

        this.connect(
            animation_delay,
            'onEnd',
            function(){
                // Animation callbacks appear to be called too early.
                // Applying this callback to the animation itself
                // seems to make the animation jump at the end.
                dojo.removeAttr(node,'style');
                dojo.removeClass(node,'HWsliding');
                delayer.remove();
                if(remove_markers){
                    origin.remove();
                    target.remove();
                }
            }
        );
        var ani = dojo.fx.combine([animation_slide,animation_delay]);
        ani.play();
    },

    /*
    Place a ship in a system and update classes as appropriate
    piecenode: the piece node that should be placed as a ship
    targetnode: the node where piecenode should be placed
        If this is a system, piecenode will become a child of targetnode
        If this is a ship, piecenode will be placed before targetnode as a sibling 
        (this parameter is not used if neighbor is provided)
        (if neighbor is not provided, then systemnode must be)
    owner_id: the ID of the player who should own the ship
        (if null, the HWfriendly and HWhostile classes will not be modified)
    */
    place_ship: function(piecenode,targetnode,owner_id=null){
        var ani_origin = this.place_animation_marker(piecenode,true);
        var systemnode;

        if(dojo.hasClass(targetnode,'HWsystem')){
            dojo.place(piecenode,targetnode);
            systemnode = targetnode;
        }
        else{
            dojo.place(piecenode,targetnode,'before');
            systemnode = this.get_system(targetnode);
        }
        dojo.removeClass(piecenode,'HWbanked');
        dojo.addClass(piecenode,'HWship');
        // If owner is specified, set it
        // Otherwise, leave it alone
        if(owner_id != null){
            if(owner_id==this.get_bot_player())
                dojo.addClass(piecenode,'HWfriendly');
            else
                dojo.addClass(piecenode,'HWhostile');
        }
        this.on_system_change(systemnode);

        var ani_target = this.place_animation_marker(piecenode,false);
        this.slide_between(piecenode,ani_origin,ani_target);
    },

    place_star: function(piecenode,systemnode){
        var ani_origin = this.place_animation_marker(piecenode,true);

        var containernode = dojo.query('.HWstar_container',systemnode)[0];
        dojo.place(piecenode,containernode);
        dojo.removeClass(piecenode,'HWbanked');
        dojo.addClass(piecenode,'HWstar');

        var ani_target = this.place_animation_marker(piecenode,false);
        this.slide_between(piecenode,ani_origin,ani_target);
    },

    place_system: function(system_id,system_name,homeplayer_id=null,star_size=null){
        var params,par,pos,template;
        if(homeplayer_id == null){
            // COLONY SETUP
            params = {
                system_id:system_id,
                system_name:system_name
            };
            if(star_size==null){
                this.showMessage(
                    'Placing a colony with unknown star.',
                    'error'
                );
                par = 'HWcolony_container_1';
            }
            else
                par = 'HWcolony_container_'+this.colony_assignments[star_size];
            pos = 'last';
            template = 'jstpl_system';
        }
        else{
            // HOMEWORLD SETUP
            var color = this.gamedatas.players[homeplayer_id].color;
            var player_name = this.gamedatas.players[homeplayer_id].name;
            var bgcolor = 'transparent';
            // If the color is hard to see on the background, change text background
            if(
                color == "000000" || // Black
                color == "0000ff" // Blue
            ){
                bgcolor = '777777';
            }
            params = {
                system_id:system_id,
                homeplayer_id:'player_'+homeplayer_id,
                homeplayer_name:player_name,
                homeplayer_color:color,
                name_background_color:bgcolor
            };
            if(homeplayer_id == this.get_bot_player()){
                par = 'HWhome_container_bot';
                // Putting system first makes token below above if needed
                pos = 'first';
            }
            else{
                par = 'HWhome_container_top';
                // Putting system last makes token appear above if needed
                pos = 'last';
            }
            template = 'jstpl_homesystem';
        }

        var systemnode = dojo.place(
            this.format_block(template,params),
            par,
            pos
        );
        this.connect(systemnode,'onclick','system_clicked');
        /* Apply a player-colored border
        if(homeplayer_id != null){
            // Now that the homeworld is created, add a color border
            var color = this.gamedatas.players[homeplayer_id].color;
            dojo.style(systemnode,'outlineColor','#'+color);
            console.log('setting outline of',systemnode,'to',color);
        }
        */
        return systemnode;
    },

    // Make sure the correct friendly/hostile class is in place
    on_system_change:function(systemnode){
        if(systemnode.getAttribute('homeplayer_id') != 'none')
            // Homeworlds don't need these labels
            return;
        var top_player = this.get_top_player();
        var bot_player = this.get_bot_player();
        var pip_counts = {
            friendly:0,
            hostile:0
        };
        var ships = dojo.query('.HWship',systemnode);
        var size,ship;
        for(var i=0;i<ships.length;i++){
            ship = ships[i];
            size = this.get_size(ship);
            if(dojo.hasClass(ship,'HWfriendly'))
                pip_counts.friendly += size;
            else
                pip_counts.hostile += size;
        }
        if(pip_counts.friendly > pip_counts.hostile){
            dojo.removeClass(systemnode,'HWhostile');
            dojo.addClass(systemnode,'HWfriendly');
        }
        else if(pip_counts.friendly < pip_counts.hostile){
            dojo.removeClass(systemnode,'HWfriendly');
            dojo.addClass(systemnode,'HWhostile');
        }
        else{
            // Pip count is equal, put it in the middle
            dojo.removeClass(systemnode,'HWfriendly HWhostile');
        }
    },

    selectablize_token: function(){
        var tokennode = document.getElementById('HWturn_token');
        this.add_tooltip(
            [tokennode],
            _('Click to end your turn.'),
            1500
        );
        dojo.addClass(tokennode,'HWselectable');
    },

    // Move the token to the NEXT player
    // This should be called before the state transition
    // (e.g. by the pass notification)
    // because the player AFTER the active player gets the token
    update_token: function(){
        var tokennode = document.getElementById('HWturn_token');

        var ani_origin = this.place_animation_marker(tokennode,true);

        var token_pos;
        if(this.getActivePlayerId() == this.get_bot_player())
            token_pos = 'top';
        else
            token_pos = 'bot';
        var spacenode = document.getElementById('HWtoken_space_'+token_pos);
        dojo.place(tokennode,spacenode);

        var ani_target = this.place_animation_marker(tokennode,false);
        this.slide_between(tokennode,ani_origin,ani_target);
    },

    connected_systems: function(systemnode){
        // Get an array of connected system nodes and bank stacks
        var old_stars = dojo.query('.HWstar',systemnode);
        var systems = dojo.query('.HWsystem');
        var i,j,k;
        var new_stars;
        var new_star,new_size;
        var old_size,old_size;
        var breakout;
        i = 0;
        while(i<systems.length){
            new_stars = dojo.query('.HWstar',systems[i]);
            for(j=0;j<new_stars.length;j++){
                breakout = 0;
                new_star = new_stars[j];
                new_size = this.get_size(new_star);
                for(k=0;k<old_stars.length;k++){
                    old_star = old_stars[k];
                    old_size = this.get_size(old_star);
                    if(new_size==old_size){
                        // Not connected
                        systems.splice(i,1);
                        breakout = 1;
                        break;
                    }
                }
                if(breakout)
                    break;
            }
            // If array was shortened, don't increment index
            if(!breakout)
                i++;
        }
        return systems;
    },

    connected_stacks: function(systemnode){
        var stars = dojo.query('.HWstar',systemnode);
        var stacks = dojo.query('.HWstack');
        var i,j;
        var new_size,old_size,star;
        var stack;
        i = 0;
        while(i<stacks.length){
            breakout = 0;
            stack = stacks[i];
            // Skip empty stacks
            if(dojo.query('.HWbanked',stack).length==0){
                stacks.splice(i,1);
                continue
            }
            new_size = stack.id.split('_')[2];
            for(j=0;j<stars.length;j++){
                star = stars[j];
                old_size = this.get_size(star);
                if(new_size==old_size){
                    // Not connected
                    stacks.splice(i,1);
                    breakout = 1;
                    break;
                }
            }
            // If array was shortened, don't increment index
            if(!breakout)
                i++;
        }
        return stacks;
    },

    get_bot_player: function(){
        if(this.isSpectator)
            return this.player_2;
        return this.player_id;
    },
    get_top_player: function(){
        if(this.isSpectator || this.player_id != this.player_1)
            return this.player_1;
        return this.player_2;
    },

    deselect_all: function(){
        var selectable = dojo.query('.HWselectable');
        selectable.removeClass('HWselectable')
        for(var i=0;i<selectable.length;i++)
            this.removeTooltip(selectable[i].id);
    },

    deactivate_all: function(){
        var activated = dojo.query('[activate]');
        activated.removeAttr('activate');
    },

    ///////////////////////////////////////////////////
    //// Player's action

    // Graphical elements clicked, determine action by current state
    piece_clicked: function(evt){
        evt.preventDefault();
        var node = evt.currentTarget;
        console.log('piece clicked',node);

        if(!dojo.hasClass(node,'HWselectable'))
            return;
        switch(this['client_state']){
            case 'want_free':
                this.activate_ship(node);
                break;
            case 'client_want_power':
                this.power_selected(this.get_color(node));
                break;
            case 'client_want_target':
                this.target_selected(node);
                break;
            case 'want_sacrifice_action':
                var color = this['latest_args'].args.color;
                this.activate_ship(node,color);
                break;
            case 'client_want_catastrophe_target':
                this.catastrophe_target_selected(node);
                break;
            default:
                console.error('Bad client state: ',this['client_state']);
        }
    },
    stack_clicked: function(evt){
        evt.preventDefault();
        var node = evt.currentTarget;
        console.log('stack clicked',node);

        if(!dojo.hasClass(node,'HWselectable')){
            console.log('stack not selectable');
            return;
        }
        switch(this['client_state']){
            case 'want_creation':
                this.stack_selected_star_creation(node);
                break;
            case 'client_want_creation_ship':
                this.stack_selected_ship_creation(node);
                break;
            case 'client_want_target':
                this.target_selected(node);
                break;
            default:
                console.error('Bad client state: ',this['client_state']);
        }
    },
    system_clicked: function(evt){
        evt.preventDefault();
        var node = evt.currentTarget;
        console.log('system clicked',node);

        if(!dojo.hasClass(node,'HWselectable'))
            return;
        switch(this['client_state']){
            case 'client_want_target':
                this.target_selected(node);
                break;
            default:
                console.error('Bad client state: ',this['client_state']);
        }
    },
    token_clicked: function(evt){
        evt.preventDefault();
        var node = evt.currentTarget;
        console.log('token clicked',node);

        if(!dojo.hasClass(node,'HWselectable'))
            return;

        this.pass_button_selected();
    },

    stack_selected_star_creation: function(stacknode){
        var piecenode = this.get_piece_in_stack(stacknode);
        if(piecenode == null){
            this.showMessage(
                _('No pieces of this type remain.'),
                'error'
            );
            return;
        }

        var home_candidates = dojo.query('[homeplayer_id=player_'+this.player_id+']');
        var systemnode;
        if(home_candidates.length == 0){
            // Home hasn't been created yet, so we must create it now
            // Start out with an empty name and temporary id
            systemnode = this.place_system(
                'tempid',
                '',
                this.player_id
            );
        }
        else
            systemnode = home_candidates[0];

        var starnodes = dojo.query('.HWstar',systemnode);
        if(starnodes.length>1){
            this.showMessage(
                _('Cannot select more stars for homeworld creation.'),
                'error'
            );
            return;
        }
        this.place_star(
            piecenode,
            systemnode
        );
        if(starnodes.length>0){
            // There was 1 star, the second just got added
            // Ship is needed next
            this.setClientState(
                'client_want_creation_ship',
                {
                    descriptionmyturn :
                    _('${you} must choose an initial ship from the bank.')
                }
            );
        }
    },

    stack_selected_ship_creation: function(stacknode){
        var systemnode = dojo.query('[homeplayer_id=player_'+this.player_id+']')[0];
        var shipnodes = dojo.query('.HWship',systemnode);
        // Make sure a ship didn't already get added
        if(shipnodes.length > 0){
            this.showMessage(
                _('Cannot select more more ships for homeworld creation.'),
                'error'
            );
            return
        }
        var piecenode = this.get_piece_in_stack(stacknode);
        if(piecenode == null){
            this.showMessage(
                _('No pieces of this type remain.'),
                'error'
            );
            return;
        }
        this.place_ship(
            piecenode,
            systemnode,
            this.player_id
        );
        var message = this.get_bad_home_warning();
        if(message.length == 0)
            message = _('${you} must end or restart your turn.');
        this.setClientState(
            'client_want_creation_confirmation',
            {
                descriptionmyturn : message
            }
        );
    },

    finalize_creation: function(){
        var systemnode = dojo.query('[homeplayer_id=player_'+this.player_id+']')[0];
        var shipnode   = dojo.query('.HWship',systemnode)[0];
        var starnodes  = dojo.query('.HWstar',systemnode);
        this.ajaxcallwrapper(
            'act_creation',
            {
                star1_id: this.get_id(starnodes[0]),
                star2_id: this.get_id(starnodes[1]),
                ship_id:  this.get_id(shipnode)
            }
        );
    },

    restart_creation: function(){
        var systemnode = dojo.query('[homeplayer_id=player_'+this.player_id+']')[0];
        if(systemnode == null)
            return;
        var contents = dojo.query('.HWship,.HWstar',systemnode);
        for(var i=0;i<contents.length;i++){
            this.put_in_bank(contents[i]);
        }
            systemnode.remove();
        args = this['latest_args'];
        this.setClientState(args.state_name,args);
    },

    cancel_action: function(){
        this.deselect_all();
        this.deactivate_all();
        args = this['latest_args'];
        this.setClientState(args.state_name,args);
    },

    activate_ship: function(shipnode,color=null){
        if(color == null){
            // Free action
            shipnode.setAttribute('activate','pending');
            this.setClientState(
                'client_want_power',
                {
                    descriptionmyturn :
                    _('${you} may sacrifice this ship or select the power of a star or friendly ship in the same system.')
                }
            );
        }
        else{
            // Sacrifice action
            shipnode.setAttribute('activate',color);
            this.power_selected(color);
        }
    },

    power_selected: function(color){
        // The ship being activated
        var activatednode = dojo.query('[activate]')[0];
        activatednode.setAttribute('activate',color);
        var description;
        color = parseInt(color);
        switch(color){
            case 1:
                description = _('${you} may choose an enemy ship in the same system to capture.');
                break;
            case 2:
                description = _('${you} may choose a destination system or a new star to discover from the bank.');
                break;
            case 3:
                description = 'Next state loading';
                break;
            case 4:
                description = _('${you} may choose a same-sized piece of a new color from the bank.');
                break;
            default:
                console.error('Bad power number: '+color);
        }
        this.setClientState(
            'client_want_target',
            { descriptionmyturn : description }
        );
        if(color==3){
            // Build
            // The color is green, so no target needs to be chosen
            // We just need to remember the clicked ship and notify the server
            this['remembered_builder_ship'] = activatednode;
            this.ajaxcallwrapper(
                'act_power_action',
                {
                    color_num:this.get_color(activatednode),
                    system_id:this.get_system(activatednode).id.split('_')[1],
                    power:3
                },
                // If the build fails, cancel the selection
                this.cancel_action
            );
            return;
        }
    },

    catastrophe_target_selected: function(targetnode){
        var target_color = this.get_color(targetnode);
        var target_system = this.get_system(targetnode);
        this.ajaxcallwrapper(
            'act_catastrophe',
            {
                system_id: target_system.id.split('_')[1],
                color: target_color
            }
        );
    },

    target_selected: function(targetnode){
        var activatednode = dojo.query('[activate]')[0];
        var power = activatednode.getAttribute('activate');
        var target_ids = targetnode.id.split('_');
        var activate_id = this.get_id(activatednode);
        switch(parseInt(power)){
        case 1:
            // Capture
            this.ajaxcallwrapper(
                'act_power_action',
                {
                    piece_id:  activate_id,
                    power:     1,
                    capture_id: target_ids[1]
                },
                this.cancel_action
            );
            break;
        case 2:
            // Move or discover
            var is_discovery = dojo.hasClass(targetnode,'HWstack');
            if(is_discovery){
                this.ajaxcallwrapper(
                    'act_power_action',
                    {
                        piece_id:       activate_id,
                        power:          2,
                        is_discovery:   1,
                        star_color_num: target_ids[1],
                        star_pips:      target_ids[2],
                    },
                    this.cancel_action
                );
            }
            else{
                this.ajaxcallwrapper(
                    'act_power_action',
                    {
                        piece_id:     activate_id,
                        power:        2,
                        is_discovery: 0,
                        system_id:    target_ids[1],
                    },
                    this.cancel_action
                );
            }
            break
        // case 3 (green) should have been handled without a target
        case 4:
            // Trade
            this.ajaxcallwrapper(
                'act_power_action',
                {
                    piece_id:     activate_id,
                    power:        4,
                    color_num: target_ids[1]
                },
                this.cancel_action
            );
            break;
        default:
            console.error('Bad power number: '+power);
        }
    },

    sacrifice_button_selected: function(){
        var shipnode = dojo.query('[activate]')[0];
        shipnode.setAttribute('activate','sacrifice');
        this.ajaxcallwrapper(
            'act_sacrifice',
            {
                ship_id: this.get_id(shipnode)
            }
        );
    },

    catastrophe_button_selected: function(){
        this.setClientState(
            'client_want_catastrophe_target',
            {descriptionmyturn:_('${you} may select an overpopulated piece to trigger a catastrophe.')}
        );
    },

    // This is called when player tries to pass with sacrifice actions left
    // Return true if there are places to use those actions.
    // Return false if the actions can't be used
    sacrifice_choice_available: function(){
        var color = this['latest_args'].args.color;
        if(color != 1){
            // Red is by far the most common color to not be able to use.
            // For now, just assume that all other colors can be used.
            // TODO implement for other colors
            return true;
        }
        var systems = dojo.query('.HWsystem');
        var ships,shipnode;
        var i,j;
        // Size of the smallest hostile ship in the system
        var small_hostile;
        // Size of the largest friendly ship in the system
        var big_friendly;
        var size;
        for(i=0;i<systems.length;i++){
            small_hostile=4;
            big_friendly=0;
            ships = dojo.query('.HWship',systems[i]);
            for(j=0;j<ships.length;j++){
                shipnode = ships[j];
                size = this.get_size(shipnode);
                if(dojo.hasClass(shipnode,'HWhostile')){
                    if(size < small_hostile){
                        small_hostile = size;
                    }
                }
                else if(size > big_friendly){
                    big_friendly = size;
                }
            }
            if(big_friendly >= small_hostile){
                // There is a ship that may be captured in this system
                return true;
            }
        }
        // No capture options found
        return false;
    },

    pass_button_selected: function(){
        var state_name = this['latest_args']['state_name'];
        if(state_name == 'want_creation'){
            // The client state should be client_want_creation_confirmation,
            // but the latest server args say want_creation
            this.finalize_creation();
            return;
        }

        // Check if there are actions still available
        // 'want_restart_turn' is the only state where
        // no forward board actions are available
        // From that state, just check for self-elimination
        if(state_name == 'want_restart_turn'){
            this.end_turn_with_self_elim_check();
            return;
        }
        // Otherwise, the player has an action or catastrophe available
        // Verify that they want to ignore remaining options
        var message;
        if(state_name == 'want_catastrophe'){
            message = _('There is an overpopulation. Are you sure you want to end your turn without triggering a catastrophe?');
        }
        else{
            if(state_name == 'want_sacrifice_action' && !this.sacrifice_choice_available()){
                // There are sacrifice actions left but no way to use them
                this.end_turn_with_self_elim_check();
                return;
            }
            message = _('You still have action(s) available. Are you sure you want to end your turn now?');
        }
        this.confirmationDialog(
            message,
            // Yes handler
            dojo.hitch(
                this,
                function(){
                    this.end_turn_with_self_elim_check();
                }
            ),
            // No handler, if any
        );
    },

    // Check for self-elimination and call server if approved
    end_turn_with_self_elim_check: function(){
        var home_bot = dojo.query('[homeplayer_id=player_'+this.player_id+']')[0];
        var defenders = dojo.query('.HWfriendly.HWship',home_bot);
        var stars = dojo.query('.HWstar',home_bot);
        if(home_bot == null || defenders.length==0 || stars.length==0){
            // This move is self-elimination
            var player_id_top = this.get_top_player();
            var home_top = dojo.query('[homeplayer_id=player_'+player_id_top+']')[0];
            var enemy_defenders = dojo.query('.HWhostile.HWship',home_top);
            var enemy_stars = dojo.query('.HWstar',home_top);
            var message;
            if(enemy_defenders.length==0 || enemy_stars.length==0){
                message = _('Both homeworlds are destroyed or unoccupied by their owners. If you end your turn now, the game will end in a draw. Is this what you want to do?');
            }
            else{
                message = _('Your homeworld is destroyed or undefended. If you end your turn now, the game will end and you will lose. Is this what you want to do?');
            }
            this.confirmationDialog(
                message,
                // Yes handler
                dojo.hitch(
                    this,
                    function(){
                        this.ajaxcallwrapper('act_pass',{});
                    }
                ),
                // No handler
                dojo.hitch(
                    this,
                    function(){
                        this.ajaxcallwrapper('act_restart_turn',{});
                    }
                ),
            );
            return;
        }
        this.ajaxcallwrapper('act_pass',{});
    },
    restart_button_selected: function(){
        var state_name = this['latest_args']['state_name'];
        if(state_name == 'want_creation'){
            this.restart_creation();
            return;
        }
        this.ajaxcallwrapper('act_restart_turn',{});
    },
    draw_button_selected: function(){
        args = this['latest_args'];
        if(args.args.draw_offerer == 0){
            // No player was offering a draw the last time we heard from the server
            // So this must be the current player offering a draw
            this.ajaxcallwrapper('act_offer_draw',{});
            this.showMessage( _('Finish taking your turn. Opponent can accept draw on their turn.'), 'info');
            args.args.draw_offerer = this.player_id;
            // Let the client think the turn is just starting
            // so the saved args will be updated and draw button will be updated
            this.setClientState(args.state_name,args);
        }
        else if(args.args.draw_offerer == this.player_id){
            // This player already offered a draw,
            // so this is a cancel
            this.ajaxcallwrapper('act_cancel_offer_draw',{});
            args.args.draw_offerer = 0;
            // Let the client think the turn is just starting
            // so the saved args will be updated and draw button will be updated
            this.setClientState(args.state_name,args);
        }
        else{
            // The other player offered the draw,
            // and this is an offer acceptance that should be confirmed
            this.confirmationDialog(
                _('Are you sure that you want to end the game in a draw?'),
                // Yes handler
                dojo.hitch(
                    this,
                    function(){
                        this.ajaxcallwrapper('act_offer_draw',{});
                    }
                )
                // No handler, if any
            );
        }
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
    setupNotifications:

    In this method, you associate each of your game notifications
    with your local method to handle it.

    Note: game notification names correspond to
    "notifyAllPlayers" and "notifyPlayer" calls in
    your homeworlds.game.php file.
    */
    setupNotifications: function() {
        dojo.subscribe('notif_debug'   ,this,'ignore_notif');

        dojo.subscribe('notif_create'  ,this,'create_from_notif');

        dojo.subscribe('notif_capture' ,this,'capture_from_notif');
        dojo.subscribe('notif_build'   ,this,'build_from_notif');
        dojo.subscribe('notif_trade'   ,this,'trade_from_notif');

        dojo.subscribe('notif_discover',this,'discover_from_notif');
        dojo.subscribe('notif_move',    this,'move_from_notif');
        dojo.subscribe('notif_fade',    this,'fade_from_notif');

        dojo.subscribe('notif_sacrifice',   this,'sacrifice_from_notif');
        dojo.subscribe('notif_catastrophe', this,'catastrophe_from_notif');

        dojo.subscribe('notif_restart', this,'restart_from_notif');
        dojo.subscribe('notif_pass', this,'pass_from_notif');
        // Notifications that don't need anything special
        dojo.subscribe('notif_elimination', this,'ignore_notif');
        dojo.subscribe('notif_offer_draw', this,'ignore_notif');
        dojo.subscribe('notif_cancel_offer_draw', this,'ignore_notif');
    },

    ignore_notif: function(notif){
    },

    create_from_notif: function(notif){
        var args = notif.args;
        var systemnode;

        var systemnode_candidates = dojo.query(
            '[homeplayer_id=player_'+args.homeplayer_id+']'
        );
        if(systemnode_candidates.length != 0){
            // The system is already represented by a node,
            // so this is the player who made it.
            // The pieces were moved in real time on client side,
            // so all that needs to happen is that the
            // system_id and system_name should be
            // made to match the server assignment
            systemnode = systemnode_candidates[0];
            systemnode.id = 'HWsystem_'+args.system_id;
            var labelnode = dojo.query('.HWsystem_label',systemnode)[0];
            // This should be handled by place_system
            //labelnode.innerHTML = args.system_name;
            // We don't need to arrange_colonies since there are no colonies
            this.setup_colony_assignments();
            this.update_token();
            return;
        }
        var systemnode = this.place_system(
            args.system_id,
            args.system_name,
            args.homeplayer_id
        );
        var piecenode;
        piecenode = document.getElementById('HWpiece_'+args.star1_id);
        this.place_star(
            piecenode,
            systemnode
        );
        piecenode = document.getElementById('HWpiece_'+args.star2_id);
        this.place_star(
            piecenode,
            systemnode
        );
        piecenode = document.getElementById('HWpiece_'+args.ship_id);
        this.place_ship(
            piecenode,
            systemnode,
            args.homeplayer_id
        );
        // We don't need to arrange_colonies since there are no colonies
        this.setup_colony_assignments();
        this.update_token();
    },

    capture_from_notif: function(notif){
        // Captures are sort of naturally animated by the rotation transition
        var args = notif.args;
        var shipnode = document.getElementById('HWpiece_'+args.target_id);
        if(dojo.hasClass(shipnode,'HWhostile')){
            dojo.removeClass(shipnode,'HWhostile');
            dojo.addClass(shipnode,'HWfriendly');
        }
        else{
            dojo.removeClass(shipnode,'HWfriendly');
            dojo.addClass(shipnode,'HWhostile');
        }
        var systemnode = this.get_system(shipnode);
        this.on_system_change(systemnode);
    },

    fade_from_notif: function(notif){
        var args = notif.args;
        var systemnode   = document.getElementById('HWsystem_'+args.system_id);
        var piecenodes = dojo.query('.HWship,.HWstar',systemnode);
        for(var i=0;i<piecenodes.length;i++)
            this.put_in_bank(piecenodes[i]);
        systemnode.remove();
    },

    move_from_notif: function(notif){
        var args = notif.args;
        var shipnode   = document.getElementById('HWpiece_'+args.ship_id);
        var systemnode = document.getElementById('HWsystem_'+args.system_id);
        this.place_ship(
            shipnode,
            systemnode
        );
    },

    discover_from_notif: function(notif){
        var args = notif.args;
        var starnode   = document.getElementById('HWpiece_'+args.star_id);
        var star_size = this.get_size(starnode);
        var systemnode = this.place_system(
            args.system_id,
            args.system_name,
            null,
            star_size
        );
        this.place_star(starnode,systemnode);
    },

    build_from_notif: function(notif){
        var args = notif.args;
        var systemnode = document.getElementById('HWsystem_'+args.system_id);
        var shipnode = document.getElementById('HWpiece_'+args.ship_id);
        var color = this.get_color(shipnode);
        var oldshipnode = null;
        if(this.isCurrentPlayerActive()){
            // This player is active, so recall the ship they clicked to build with
            oldshipnode = this['remembered_builder_ship'];
            // In a replay or tutorial, remembered ship may not be reliable
            // Make sure it is the right color and in the right system
            // Otherwise, set it back to null
            // so an appropriate ship can be found in next block
            if(
                oldshipnode == null
                || this.get_color(oldshipnode) != color
                || this.get_system(oldshipnode) != systemnode
            ){
                oldshipnode = null;
            }
        }
        if(oldshipnode == null){
            // This is the other player or observer,
            // so just pick a correctly colored ship in the system

            // Find alignment of ships allied with the one being constructed
            var alignment;

            if(this.get_bot_player() == args.player_id)
                alignment = '.HWfriendly';
            else
                alignment = '.HWhostile';
            // Find the color of this ship
            var colorname = this.color_names_eng[color];
            // Find allied, same-colored ships
            var ships = dojo.query(alignment+'.HWship.HW'+colorname,systemnode);
            if(ships.length == 0){
                // This can happen in a replay
                this.showMessage( _('No valid builder ship.'), 'error');
                oldshipnode = null;
            }
            else{
                // Pick any of them
                oldshipnode = ships[0];
            }
        }
        if(oldshipnode != null){
            this.place_ship(
                shipnode,
                oldshipnode,
                args.player_id
            );
        }
        else{
            // Something went wrong,
            // but do the best we can by putting it in the right system
            this.place_ship(
                shipnode,
                systemnode,
                args.player_id
            );
        }
    },

    trade_from_notif: function(notif){
        var args = notif.args;
        //var systemnode  = document.getElementById('HWsystem_'+args.system_id);
        var oldshipnode = document.getElementById('HWpiece_'+args.old_ship_id);
        var newshipnode = document.getElementById('HWpiece_'+args.new_ship_id);

        this.place_ship(
            newshipnode,
            //systemnode,
            oldshipnode,
            args.player_id
        );
        this.put_in_bank(oldshipnode);
    },

    sacrifice_from_notif: function(notif){
        var args = notif.args;
        var shipnode  = document.getElementById('HWpiece_'+args.ship_id);
        this.put_in_bank(shipnode);
    },

    catastrophe_from_notif: function(notif){
        var args = notif.args;
        var systemnode = document.getElementById('HWsystem_'+args.system_id);
        var color_name = this.color_names_eng[args.color];

        var pieces = dojo.query('.HW'+color_name,systemnode);
        for(var i=0;i<pieces.length;i++)
            this.put_in_bank(pieces[i]);
        // If it's a homeworld, rearrange the star map
        if(systemnode.getAttribute('homeplayer_id')!='none')
            this.arrange_colonies();
    },

    restart_from_notif: function(notif){
        // It's sloppy, but for now, I'm doing a full board reconstruction
        this.clear_all();
        this.setup_pieces(notif.args.gamedatas);
    },

    // Turn has ended, move the token
    // The player who just finished their turn is active
    pass_from_notif: function(notif){
        this.update_token();
    },

    // Rearrange colonies when home system connectivity may have changed
    arrange_colonies: function(){
        var i;
        // star_to_systems[i] is a list of the colony system nodes that
        // have a star of size i
        var star_to_systems = {};
        for(i=1;i<=3;i++){
            star_to_systems[i] = dojo.query(
                '.HWsystem',
                'HWcolony_container_'+this.colony_assignments[i]
            );
        }
        this.setup_colony_assignments();
        var containernode;
        for(i=1;i<=3;i++){
            containernode = document.getElementById(
                'HWcolony_container_'+this.colony_assignments[i]
            );
            star_to_systems[i].place(containernode);
        }
    }

   });
});

