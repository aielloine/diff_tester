<?php include_once "inc/header.inc" ?>
<body class="fk">
  <div class="fk_wrapper">
    <a href="javascript:history.back()"><h1 class="fk">Comparaisons de l'image</h1> </a>
    <?php
    $time = strtotime($_GET['date']);
    $form_date = date('Y-m-d', $time);
    $srcs = $diff->get_src($_GET["id"], $_GET["device"], $form_date);?>
    <h2 class="fk fk_left" style="width: 33%" ><?= date('j F Y', $time) ?></h2>
    <h2 class="fk fk_left" style="width: 33%" ><?= date('j F Y') ?></h2>
    <h2 class="fk fk_left" style="width: 33%" >Différences</h2>

    <img class="fk_left" width="33%" src="<?= $srcs["old"] ?>" alt="old">
    <img class="fk_left" width="33%" src="<?= $srcs["new"] ?>" alt="new">
    <img class="fk_left" width="33%" src="<?= $srcs["compare"] ?>" alt="differences">
    
  </div>
</body>
<?php include_once "inc/footer.inc" ?>
