<?php include_once "inc/header.inc" ?>
<body class="fk">
  <div class="fk_wrapper">
    <h1 class="fk">Liste des Urls à tester</h1>
    <div class="fk_wrapper_body">
      <div class="fk_box">
        <div class="head fk_sec fk_text_white">Actions</div>
        <div class="body">
          <form action="index.php" method="get">
            <input type="date" class="fk" name="datepicker" id="datepicker"><br><br>
            <label for="DnoDif">Inclut 0% diffs</label> <input type="checkbox" data-fk-custom="switch" class="fk" name="DnoDif" id="DnoDif" value="true"><br>
            <button type="submit" class="fk fk_mt_3 colored_btn">Lancer la comparaison</button>
            <a class="fk_btn fk_mt_3 colored_btn"  onclick="return confirm('Toutes les urls dans la BDD seront perdues!')" title="ATTENTION : peut prendre des heures" href="refresh_BDD.php">Rafraichir la BDD</a>
            <a class="fk_btn fk_mt_3 colored_btn" title="ATTENTION : peut prendre des heures" href="normalize_BDD.php">Enlever les accents de la BDD</a>
            <a class="fk_btn fk_mt_3 colored_btn" title="ATTENTION : peut prendre des heures" href="test_urls.php">Tester les urls</a>
            <a class="fk_btn fk_mt_3 colored_btn" href="menu_compare.php">Comparer les menus de 2 sites</a>
          </form>
        </div>
      </div>
      <?php if (isset($_GET['datepicker'])) {
				foreach ($diff->get_devices() as $Dkey => $device) { ?>
        <h2 class="fk fk_mt_3"><?= $device[0].'x'. $device[1] ?></h2>
        <table class="fk bordered fk_mt_2">
          <thead>
            <tr>
              <th class="fk_main fk_text_white">Url</th><th>%</th><th>Diff</th>
            </tr>
            <tbody>
              <?php
                foreach($diff->get_urls() as $key => $url) {
                  $time = strtotime($_GET['datepicker']);
                  $form_date = date('Y-m-d', $time);
                  $devices = $diff->get_snap_diff($form_date, $url, $Dkey);
                  $pourcent = round((float)$devices * 100,2 );
                  $icon = '<i style="color: #388E3C" class="fas fa-check-circle"></i>';
                  if ($pourcent > 0) {
                    $icon = '<i style="color: #EF6C00" class="fas fa-exclamation-triangle"></i><i class="fas fa-eye"></i>';
                  }
                  if ($pourcent > 1) {
                    $icon = '<i style="color: #B71C1C" class="fas fa-exclamation-circle"></i><i class="fas fa-eye"></i>';
                  }
                  if ($pourcent > 0 || ($pourcent == 0 && isset($_GET['DnoDif']))) {
                  ?>
                  <tr>
                    <td><a href="<?= $url ?>"><?= $url ?></a></td>
                    <td><?= $pourcent . '%'; ?></td>
                    <td><a href="display_diffs.php?id=<?= $key ?>&device=<?= $Dkey ?>&date=<?= $_GET['datepicker'] ?>"> <?= $icon ?></a></td>
                  </tr>
                  <?php

                  ob_flush();
                  flush();
                  }
                }
              ?>
            </tbody>
          </thead>
        </table>
      <?php }
      } ?>

    </div>
  </div>
</body>
<?php include_once "inc/footer.inc" ?>
