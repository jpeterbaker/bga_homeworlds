{OVERALL_GAME_HEADER}

<!--
- - BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com>
- -               & Emmanuel Colin <ecolin@boardgamearena.com>
- - Homeworlds implementation : © Jonathan Baker <babamots@gmail.com>
- -
- - This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
- - See http://en.boardgamearena.com/#!doc/Studio for more information.
-->

<!-- This node will be easy to find when debugging.
Changing its style will affect the other markers -->
<div class='HWanimarker' id='HWspare_ani_marker'></div>

<!--
These buttons don't belong here,
but the titlebar where they belong is inaccessible from here.
They will be moved by JS.
Maybe it would be better to use variables for the inner HTML
of the buttons (and the legend labels), but I thought that was
why translations weren't working.
-->
<div id='HWpowerBox' class='HWdisabled'>
<!-- BEGIN power_button -->
    <div id='HWpowerButton{COLORNUM}' class='HWbutton HWpowerButton'></div>
<!-- END power_button -->
</div>

<div id='HWcatastropheButton' class='HWactionButton HWbutton HWdisabled HWhilit'>Temp text</div>
<div id='HWsacrificeButton'   class='HWactionButton HWbutton HWdisabled'>Temp text</div>
<div id='HWpassButton'        class='HWactionButton HWbutton HWdisabled'>Temp text</div>
<div id='HWdrawButton'        class='HWactionButton HWbutton HWdisabled'>Temp text</div>
<div id='HWcancelButton'      class='HWactionButton HWbutton HWdisabled'>Temp text</div>
<div id='HWrestartButton'     class='HWactionButton HWbutton HWdisabled'>Temp text</div>

<!--
        VARIABLE NAMES IN TEMPLATE BLOCKS CAN INTERFERE WITH
        NAMES USED IN THE TEMPLATE STRINGS AT THE BOTTOM
-->
<div id='HWbank'>
    <!-- BEGIN legend -->
    <div id='HWlegend_label{COLORNUM}' class='HWlegend_label' style='bottom:{BOTTOM}%'> </div>
    <!-- END legend -->

    <!-- BEGIN stack -->
    <div id='HWstack_{COLORNUM}_{PIPS}' class='HWstack' style='left:{LEFT}%;top:{TOP}%'> </div>
    <!-- END stack -->
</div>

<!-- Putting the board after the bank makes it fill the remaining space-->
<div id='HWboard'>
    <div class='HWcenterizer'>
        <div id='HWhome_container_top' class='HWsystem_container'>
            <div id='HWtoken_space_top' class='HWtoken_space'></div>
        </div>
    </div>
    <div class='HWcenterizer'>
        <div id='HWcolony_container_1' class='HWsystem_container'></div>
    </div>
    <div class='HWcenterizer'>
        <div id='HWcolony_container_2' class='HWsystem_container'></div>
    </div>
    <div class='HWcenterizer'>
        <div id='HWcolony_container_3' class='HWsystem_container'></div>
    </div>
    <div class='HWcenterizer'>
        <div id='HWhome_container_bot' class='HWsystem_container'>
            <div id='HWtoken_space_bot' class='HWtoken_space'></div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var jstpl_system = "<div class='HWsystem' id='HWsystem_${system_id}' homeplayer_id='none'><div class='HWstar_container'><div class='HWsystem_label'>${system_name}</div></div></div>";
    var jstpl_homesystem = "<div class='HWsystem' id='HWsystem_${system_id}' homeplayer_id='${homeplayer_id}'><div class='HWstar_container'><div class='HWsystem_label'>Homeworld <span class='playername' style='color:#${homeplayer_color};background-color:#${name_background_color}'>${homeplayer_name}</span></div></div></div>";
    var jstpl_piece = "<div class='HWpiece HW${colorname} HW${pipsname} ${more_classes}' id='HWpiece_${piece_id}' ptype='${colornum}_${pipsnum}'><div class='HWcolor_symbol HWsymbol_${colorname}'></div></div>";
    var jstpl_legend_label = "${colorname_local}<div class='HWcolor_symbol HWsymbol_${colorname_eng}'></div><br>${actionname}";


</script>

{OVERALL_GAME_FOOTER}

