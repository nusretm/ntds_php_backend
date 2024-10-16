                <ul class="list-group">
<?php
                $api = App::loadAPI($activeModuleName, 'index');
                foreach($api->methodNames() as $methodName) {
?>
                    <li class="list-group-item" onclick='window.open("/<?=URI_API?>/<?=$activeModuleName?>/<?=$methodName?>");'>
                        <span class="material-symbols-outlined">function</span> <?=$methodName;?>
                    </li>
<?php
                }
?>
                </ul>
