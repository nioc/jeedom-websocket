<?php
if (!isConnect('admin')) {
    throw new \Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('Websocket');
sendVarToJS('eqType', $plugin->getId());
?>

<div>
    <legend>{{Websocket}}</legend>
    <div class="eqLogicThumbnailContainer">
        <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="color:#767676;">
            <i class="fa fa-wrench"></i>
            <br>
            <span>{{Configuration}}</span>
        </div>
    </div>
</div>

<?php
include_file('core', 'plugin.template', 'js');
