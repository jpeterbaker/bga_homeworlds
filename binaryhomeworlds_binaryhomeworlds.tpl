{OVERALL_GAME_HEADER}

<!--
- - BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com>
- -               & Emmanuel Colin <ecolin@boardgamearena.com>
- - binaryHomeworlds implementation : © <Jonathan Baker> <babamots@gmail.com>
- -
- - This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
- - See http://en.boardgamearena.com/#!doc/Studio for more information.
-->

<div id='bank'>
    <!-- BEGIN stack -->
    <div id='stack_{COLOR}_{PIPS}' class='stack' style='left:{LEFT}%;top:{TOP}%'> </div>
    <!-- END stack -->
</div>

<!-- Putting the board after the bank makes it fill the remaining space-->
<div id='board'>
    <div id='home_container_top' class='home_container'></div>
    <div id='home_container_bot' class='home_container'></div>
</div>

<script type="text/javascript">
    var jstpl_system = "<div class='system' id='system_${system_id}' homeplayer_id='${homeplayer_id}'><span class='system_label'>${system_name}</span></div>"
    var jstpl_piece = "<div class='${colorname} ${pipsname} ${more_classes}' id='piece_${piece_id}' ptype='${colornum}_${pipsnum}'></div>"
</script>

{OVERALL_GAME_FOOTER}

