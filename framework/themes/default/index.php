<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title>NetTeam API UI</title>
    <meta Content-Type = "text/html"; charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <!--
    <link rel="shortcut icon" type="image/x-icon" href="/themes/default/favicons/favicon.ico">
    <link rel="manifest" href="/themes/default/manifest.json">
    <meta name="msapplication-TileImage" content="/themes/default/favicons/mstile-150x150.png">
    -->
    <!-- ===============================================-->
    <!--    Theme Settings-->
    <!-- ===============================================-->
    <meta name="theme-color" content="#0b1727">
    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!--<link type="text/css" href="http://dev.localhost/api/theme.css" rel="stylesheet" />-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        .list-group-item {
            cursor: pointer;
        }
        .list-group-item:hover {
            background-color: var(--bs-list-group-action-hover-bg);
            color: var(--bs-list-group-action-hover-color);
        }
        .list-group-item.active:hover {
            background-color: var(--bs-list-group-active-bg);
            color: var(--bs-list-group-active-color);
        }
    </style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
</head>
<body>
    <header class="navbar navbar-expand-sm bg-body-tertiary sticky-top shadow">
        <div class="container-fluid position-sticky">
            <span class="navbar-brand">NetTeam API</span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-sm-0">
<?php
            foreach($data['navbar'] as $menuItem)  {
?>
                    <li class="nav-item">
                        <a class="nav-link<?php if($menuItem['active'])print(" active");?>" aria-current="page" href="<?=$menuItem['link'];?>"><?=$menuItem['text'];?></a>
                    </li>
<?php
            }
?>
                </ul>
            </div>
        </div>
    </header>
    <div class="container-fluid p-0 m-0">
        <div class="row p-0 m-0">
            <div class="btn-group dropdown-center d-md-none p-1 pt-3 pb-2">
<?php
    if(isset($activeMenuName)) {
?>
                <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <?=$data['navbar'][$activeNavbarName]['menu'][$activeMenuName]['text']?>
                </button>
                <ul class="dropdown-menu w-100 shadow">
<?php
            foreach($data['navbar'][$activeNavbarName]['menu'] as $menuItem) {
?>
                    <li><a class="dropdown-item p-2 <?php if($menuItem['active'])print(" active");?>" href="<?=$menuItem['link'];?>"><?=$menuItem['text'];?></a></li>
<?php
            }
?>
                </ul>
            </div>
            <nav class="sidebar p-0 d-md-block d-none" style="min-width: 250px; max-width: 250px;">
                <div class="position-fixed pt-2" style="width: 250px;">
                    <ul class="list-group flex-column">
<?php
                foreach($data['navbar'][$activeNavbarName]['menu'] as $menuItem) {
?>
                        <li class="list-group-item<?php if($menuItem['active'])print(" active"); ?>" onclick='document.location.href="<?=$menuItem['link'];?>";'>
                            <span class="material-symbols-outlined"><?=$data['navbar'][$activeNavbarName]['icon']?></span> <span><?=$menuItem['text']?></span>
                        </li>
<?php
                }
?>
                    </ul>
                </div>
            </nav>
<?php
    }
?>

            <main class="col p-2 m-0 overflow-auto">
<?php
    include($activeNavbarName.".php");
?>
            </main>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<script type="text/javascript">
    $(document).ready(function() {
        //<button class="copy-btn btn btn-sm btn-primary me-2 pt-0 pb-1" data-target="json-row-data"><i class="btn-icon bi bi-copy fs-4"></i> <span class="btn-text">Copy</span></button>
        $(".copy-btn").each(function(index, elem) {
            let btn=$(elem);
            btn.html('').addClass("btn btn-sm btn-primary pt-0 pb-1");
            $("<i>", {class: "btn-icon bi bi-copy fs-4"}).appendTo(btn);
            $("<i>", {class: "btn-text ms-1"}).text("Copy").appendTo(btn);
            btn.click(function() {
                let btn = $(this);
                if(copyToClipboard(btn.attr("data-target"))) {
                    btn.find(".btn-icon")
                    .removeClass('bi-copy')
                    .removeClass('bi-clipboard-x')
                    .addClass('bi-clipboard-check')
                    ;
                    btn.attr('disabled', true);
                    btn.find(".btn-text").text('Success');
                } else {
                    btn.find(".btn-icon")
                    .removeClass('bi-copy')
                    .removeClass('bi-clipboard-check')
                    .addClass('bi-clipboard-x')
                    ;
                    btn.find(".btn-text").text('Fail !');
                }

                setTimeout(function() { btn.reset(); }, 3000);
                btn.reset = function() {
                    btn= $(this);
                    btn.find(".btn-icon")
                    .removeClass('bi-clipboard-check')
                    .removeClass('bi-clipboard-x')
                    .addClass('bi-copy')
                    ;
                    btn.attr('disabled', false);
                    btn.find(".btn-text").text('Copy');
                }
            });
        });

    });
    function copyToClipboard(elemId) {
        elem = document.getElementById(elemId);
        // create hidden text element, if it doesn't already exist
        var targetId = "_hiddenCopyText_";
        target = document.getElementById(targetId);
        if (!target) {
            var target = document.createElement("textarea");
            target.style.position = "absolute";
            target.style.left = "-9999px";
            target.style.top = "0";
            target.id = targetId;
            document.body.appendChild(target);
        }
        target.textContent = elem.textContent;

        // select the content
        var currentFocus = document.activeElement;
        target.focus();
        target.setSelectionRange(0, target.value.length);
        
        // copy the selection
        var succeed;
        try {
            succeed = document.execCommand("copy");
        } catch(e) {
            succeed = false;
        }
        // restore original focus
        if (currentFocus && typeof currentFocus.focus === "function") {
            currentFocus.focus();
        }
        
        target.textContent = "";

        return succeed;
    }
</script>
</body>
</html>