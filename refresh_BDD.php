<?php include_once "inc/header.inc" ?>
<body class="fk">
  <div class="fk_wrapper">
    <h1 class="fk">BDD en cours d'actualisation</h1>
    <div class="fk_wrapper_body console_log">
      <script type="text/javascript" src="js/script.min.js" charset="utf-8"></script>

      <?php if (isset($_GET["action"])) {
        $diff->actualise_BDD();
      }else {
        $diff->drop_BDD();?>
        <div class="loader">
          <div class="bulle"></div>
            <svg version="1.1">
              <filter id="goo">
                <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 19 -9" result="goo" />
                <feComposite in="SourceGraphic" in2="goo" operator="atop"/>
              </filter>
            </svg>
        </div>
        <!-- refresh page to refresh bdd -->
        <meta http-equiv="refresh" content="5; URL=?action=go" />
      <?php } ?>
    </div>
  </div>
</body>
<?php include_once "inc/footer.inc" ?>
