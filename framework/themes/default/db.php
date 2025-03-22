                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="json-tab" data-bs-toggle="tab" data-bs-target="#json" type="button" role="tab" aria-controls="json" aria-selected="true">Json</button>
                        </li>
                        <li class="nav-item" role="presentation">
<?php
    if(isset($activeTableName)) {
?>
                            <button class="nav-link" id="dart-tab" data-bs-toggle="tab" data-bs-target="#dart" type="button" role="tab" aria-controls="dart" aria-selected="false"><?=strtolower(DB::table($activeTableName)->generateDartFileName())?>.dart</button>
<?php
    }
?>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active pb-0" id="json" role="tabpanel" aria-labelledby="json-tab">
                            <div class="border-start border-end border-bottom p-2">
                                <button class="copy-btn fw-bold" data-target="dart-data-class"></button>
                            </div>
<?php
    if(isset($activeTableName)) {
?>
                            <pre class="border-start border-end border-bottom m-0 p-3" id="json-row-data"><?php print json_encode(DB::table($activeTableName)->generateRowData(), JSON_PRETTY_PRINT);?></pre>
<?php
    }
?>
                        </div>
                        <div class="tab-pane fade border-start border-end border-bottom" id="dart" role="tabpanel" aria-labelledby="dart-tab">
                            <div class="border-start border-end border-bottom p-2">
                                <button class="copy-btn fw-bold" data-target="dart-data-class"></button>
                            </div>
<?php
    if(isset($activeTableName)) {
?>
                            <pre class="border-start border-end border-bottom m-0 p-3" id="dart-data-class"><?php print htmlentities(DB::table($activeTableName)->generateDartClass());?></pre>
<?php
    }
?>
                        </div>
                    </div>
