<?php
require_once 'config.cnf';
require_once 'vendor/autoload.php';
use JonnyW\PhantomJs\Client;

class diff_tester{

  /******************
  * @var driver
  * @access private
  ******************/
  private $driver = DB_DRIVER;

  /******************
  * @var hostname
  * @access private
  ******************/
  private $hostname = DB_HOST;

  /******************
  * @var serverport
  * @access private
  ******************/
  private $serverport = DB_SERVER_PORT;

  /******************
  * @var username
  * @access private
  ******************/
  private $username = DB_USER;

  /******************
  * @var password
  * @access private
  ******************/
  private $password = DB_PASSWORD;

  /******************
  * @var database
  * @access private
  ******************/
  private $database = DB_NAME;

  /******************
  * @var charset
  * @access private
  ******************/
  private $charset = DB_CHARSET;

  /******************
  * @var pdo
  * @access private
  ******************/
  private $pdo;

  /******************
  * @var responsive
  * @access private
  ******************/
  private $responsive = DEVICES;

  /******************
  * @var domain
  * @access private
  ******************/
  private $domain = DOMAIN;

  /******************
  * @var ban_words
  * @access private
  ******************/
  private $ban_words = BAN_WORDS;

  /******************
  * @var urls
  * @access private
  ******************/
  private $urls;

  /******************
  * @var max_urls
  * @access private
  ******************/
  private $max_urls = MAX_URLS;

  function __construct(){
    // to have the time to see all the pages
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 86400);

    // get date of today
    $this->now = date('Y-m-d');
    //connect to de BDD
    $this->connect_BDD();
    // get all urls from BDD
    $this->urls = $this->get_urls_BDD();
  }
  // Get all recursive links
  public function get_all_links($url, $parent = 'Home'){
    // get mthe max of urls
    if ($this->max_urls != -1 && $this->max_urls <= count($this->urls)) {
      return;
    }
    $url = $this->remove_accents(urldecode($url));
    // if the address is not local or if the address is already registered, nothing is done
    if(
      strpos($url, $this->domain) == false ||
      in_array($url, $this->urls)
    ){ return; }

    // if a ban word is in the url, nothing is done
    foreach ($this->ban_words as $ban_word) {
      if(strpos($url, $ban_word) == true){ return; }
    }

    // get all the links from the page
    $motif='#<a href="(.*?)"(.*?)>#';
    // title tr
    echo "<br /><br /> <a href=\"$parent\">".mb_strtoupper(urldecode($parent), 'UTF-8')."</a> find : ".count($this->urls)." - <a href=\"$url\">".urldecode($url)."</a> <br />";
    echo "<script>
    setTimeout(function() {
      var scrollBottom = $(window).scrollTop() + $(window).height();
      $(window).scrollTop(scrollBottom);}
      ,80);
      </script>";
      // affiche les urls que l'on s'aprète à ajouter en direct
      ob_flush();
      flush();

      // get all the content of the page
      $file = file_get_contents($url);
      preg_match_all($motif,$file,$out,PREG_PATTERN_ORDER);


      // if the content is visible, add url to BDD
      $this->insert_url_BDD($url);

      // register the url
      $this->urls[] = $url;




      // for each link, we will search the links of the page
      foreach ($out[1] as $link) {
        // si le lien commence par / on ajoute le base url de l'url ouverte
        if (substr( $link, 0, 1 ) === "/") {
          $url_info = parse_url($url);
          $link = $url_info['scheme'] . '://' . $url_info['host'].$link;
        }
        $this->get_all_links($link, $url);
      }
    }

  // take snaps of each url and each device in the folder of the date of today
  public function get_snaps(){
    // create the screens directory if not exist
    if (!is_dir("screens") && !file_exists("screens") ) {
      mkdir("screens", 0777, true);
    }
    // instancie le client de phantomJs
    $client = Client::getInstance();
    $client->isLazy();
    $client->getEngine()->setPath(__DIR__.'/bin/phantomjs');

    // pour chaque appareil
    foreach ($this->responsive as $device) {
      // pour chaque url
      foreach ($this->urls as $url) {
        $request = $client->getMessageFactory()->createCaptureRequest($url, 'GET');
        $request->setTimeout(5000);
        $request->setOutputFile('./screens/'.$this->now.'/'.$device[0].'x'. $device[1].'/'.$url.'.jpg');
        // $request->setOutputFile('/media/aielloine/Données/screens/'.$this->now.'/'.$device[0].'x'. $device[1].'/'.$url.'.jpg');
        $request->setViewportSize($device[0], $device[1]);
        $response = $client->getMessageFactory()->createResponse();
        // if dont diffs, delete screen, if capture is new, do nothing
        $most_recent_screen = $this->get_most_recent_screen($this->now, $url, $device);
exit;
        $client->send($request, $response);
        // if font have diffs, delete picture
        if ($most_recent_screen != false) {
          // $src_img1 = '/media/aielloine/Données/screens/'.$this->now.'/'.$device[0].'x'. $device[1].'/'.$url.'.jpg';
          $src_img1 = __DIR__.'/screens/'.$this->now.'/'.$device[0].'x'. $device[1].'/'.$url.'.jpg';
          $src_img2 = __DIR__."/".$most_recent_screen;
          $result = $this->get_pics_diff($src_img1, $src_img2);
          if ($result[1] == 0) {
            unlink($src_img1);
          }
        }
      }
    }
  }

  // create in à reopository all the difference between a date and today
  public function get_snaps_diff(String $date){
    foreach ($this->responsive as $device) {
      foreach ($this->urls as $url) {
        $this->get_diff($device, $url, $date);
      }
    }
  }

  //return the purcentage in float of the changes of the page
  public function get_snap_diff(String $date, String $url, $Dkey){
    $device = $this->responsive[$Dkey];
    return $this->get_diff($device, $url, $date);
  }

  // return the most recent snap that have been taken before the date
  public function get_most_recent_date($dateB)
  {
    $dateB = strtotime($dateB);
    // get all the dirs exists
    $dirs = array();
    // $scan_dir = scandir("/media/aielloine/Données/screens");
    $scan_dir = scandir("screens");
    foreach ($scan_dir as $key => $value) {
      if (!in_array($value,array(".","..")) && is_dir("screens/". $value)) {
      // if (!in_array($value,array(".","..")) && is_dir("/media/aielloine/Données/screens/". $value)) {
        $dirs[] = strtotime($value);
      }
    }
    $mostRecent= 0;
    foreach($dirs as $date){
      if ($date > $mostRecent && $date < $dateB) {
        $mostRecent = $date;
      }
    }
    if (!$mostRecent) {
      return false;
    }
    return date('Y-m-d', $mostRecent);
  }

  // get the most recent screens
  public function get_most_recent_screen($date, $url, $device)
  {
    $path_screens = "/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    // $src_img1 = "/media/aielloine/Données/screens/".$date. $path_screens;
    $src_img1 = "screens/".$date. $path_screens;
    while(!file_exists($src_img1)){
      $date = $this->get_most_recent_date($date);
      if (!$date) {
        return false;
        exit;
      }else {
        $src_img1 = "screens/".$date. $path_screens;
        // $src_img1 = "/media/aielloine/Données/screens/".$date. $path_screens;
      }
    }
    return $src_img1;
  }

  //return the purcentage in float of the changes of the page and create a diff file
  public function get_diff(Array $device, String $url, String $date){
    // get the screenshots of today if we dont have
    // if (!is_dir("/media/aielloine/Données/screens/".$this->now)) {
    if (!is_dir("screens/".$this->now)) {
      $this->get_snaps();
    }
    $most_recent = $this->get_most_recent_screen($date, $url, $device);
    $src_img1 = __DIR__."/".$most_recent;
    if ($most_recent == false) {
      // $src_img1 = "/media/aielloine/Données/screens/".$date."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
      $src_img1 = __DIR__."/screens/".$date."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    }

    $most_recent = $this->get_most_recent_screen($this->now, $url, $device);
    $src_img2 = __DIR__."/".$most_recent;
    if ($most_recent == false) {
      // $src_img2 = "/media/aielloine/Données/screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
      $src_img2 = __DIR__."/screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    }
    $result = $this->get_pics_diff($src_img1, $src_img2);

    $parsed_url = explode('/', $url);

    $name = array_pop($parsed_url);
    $path = implode("/", $parsed_url);

    // $path_difs = "/media/aielloine/Données/diffs/".$this->now."/".$device[0].'x'. $device[1].'/'.$path;
    $path_difs = "diffs/".$this->now."/".$device[0].'x'. $device[1].'/'.$path;
    $result[0]->setImageFormat("jpg");
    if ($result[1] > 0) {
      if (!is_dir($path_difs)) {
        mkdir($path_difs, 0777, true);
      }
      // il y a des différences
      $result[0]->writeImage(__DIR__."/".$path_difs."/".$name.".jpg");
    }
    return $result[1];
  }

  // diff between 2 imagesy
  public function get_pics_diff($src_img1, $src_img2){
    $image1 = new imagick($src_img1);
    $image2 = new imagick($src_img2);
    return $image1->compareImages($image2, Imagick::METRIC_MEANSQUAREERROR);
  }
  // get urls var
  public function get_urls(){
    return $this->urls;
  }

  // get devices var
  public function get_devices(){
    return $this->responsive;
  }

  // get the sources of a url device of a date
  public function get_src($id, $id_device, $date){
    $url = $this->urls[$id];
    $device = $this->responsive[$id_device];

    $most_recent = $this->get_most_recent_screen($date, $url, $device);
    if ($most_recent == false) {
      // $most_recent = "/media/aielloine/Données/screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
      $most_recent = "screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    }

    $return["old"] = $most_recent;
    // $return["new"] = "/media/aielloine/Données/screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    $return["new"] = "screens/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    // $return["compare"] = "/media/aielloine/Données/diffs/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    $return["compare"] = "diffs/".$this->now."/".$device[0].'x'. $device[1]."/".$url.'.jpg';
    return $return;
  }
  // test headers of a url
  public function url_work(string $url){
    $file_headers = @get_headers($url);
    if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
        return array(false, $file_headers[0]);
    }
    else {
        return array(true, $file_headers[0]);
    }
  }





  // ##################### BDD functions ##################################
  // connection do BDD
  public function connect_BDD(){
    try {
      $this->pdo = new PDO($this->driver.":"."host=".$this->hostname.";port=".$this->serverport.";dbname=".$this->database.";charset=".$this->charset, $this->username, $this->password, array(PDO::ATTR_PERSISTENT => true));
      $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch (PDOException $e) {
      echo "BDD Connection error";
      echo "<br />";
      echo $e->getMessage();
    }
  }

  // insert url in a BDD
  public function insert_url_BDD(String $url){
    $stmt = $this->pdo->prepare("INSERT INTO urls (url) VALUES (:url)");
    $stmt->bindParam(':url', $url);
    $stmt->execute();
  }

  // update url in a BDD
  public function update_url_BDD($id, String $url){
    $stmt = $this->pdo->prepare("UPDATE urls SET url = :url WHERE id = :id");
    $stmt->bindParam(':url', $url);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
  }

  // get url from BDD
  public function get_url_BDD(int $id) :String {
    return $this->pdo->query("SELECT * FROM urls WHERE id = ".$id)['url'];
  }

  // get all urls from BDD
  public function get_urls_BDD() {
    // if the table urls exist, return all the urls
    if ($this->pdo->query("SHOW TABLES LIKE \"urls\"")->fetch()) {
      $q = $this->pdo->query("SELECT url FROM urls");
      return $q->fetchAll(PDO::FETCH_COLUMN, 0);
    }else {
      return [];
    }
  }

  // remove all accents from the BDD
  public function normalize_BDD()
  {
    echo "--------------";
    echo "<br />";
    echo "## CONSOLE ##";
    echo "<br />";
    echo "--------------";
    echo "<br />";
    echo "<br />";
    $urls = $this->get_urls_BDD();
    foreach ($urls as $id => $url) {
      $good_url = $this->remove_accents(urldecode($url));
      $this->update_url_BDD($id + 1, $good_url);
      echo "<br /><br /> $url <br /> changed to <br /> $good_url";
      echo "<script>
      setTimeout(function() {
        var scrollBottom = $(window).scrollTop() + $(window).height();
        $(window).scrollTop(scrollBottom);}
        ,80);
        </script>";
        // affiche les urls que l'on s'aprète à ajouter en direct
        ob_flush();
        flush();
    }
    echo "<br />";
    echo "<br />";
    echo "------------------------------";
    echo "<br />";
    echo "## Normalization successful ##";
    echo "<br />";
    echo "------------------------------";
  }

  // remove spécial characters from a text
  public function remove_accents($text) {
    $utf8 = array(
      '/[áàâãªä]/u' => 'a',
      '/[ÁÀÂÃÄ]/u' => 'A',
      '/[ÍÌÎÏ]/u' => 'I',
      '/[íìîï]/u' => 'i',
      '/[éèêë]/u' => 'e',
      '/[ÉÈÊË]/u' => 'E',
      '/[óòôõºö]/u' => 'o',
      '/[ÓÒÔÕÖ]/u' => 'O',
      '/[úùûü]/u' => 'u',
      '/[ÚÙÛÜ]/u' => 'U',
      '/ç/' => 'c',
      '/Ç/' => 'C',
      '/ñ/' => 'n',
      '/Ñ/' => 'N',
    );
    return preg_replace(array_keys($utf8), array_values($utf8), $text);
  }

  // reinitialisation de le BDD et on re-remplie tout
  public function drop_BDD()
  {
    $this->pdo->query("DROP TABLE IF EXISTS urls; ");
    $this->pdo->query(file_get_contents('diff_tester.sql'));
  }
  // see if they are new links
  public function actualise_BDD()
  {
    echo "--------------";
    echo "<br />";
    echo "## CONSOLE ##";
    echo "<br />";
    echo "--------------";
    echo "<br />";
    echo "<br />";
    $this->get_all_links(HOME);
    echo "<br />";
    echo "<br />";
    echo "------------------------------";
    echo "<br />";
    echo "## Actualisation successful ##";
    echo "<br />";
    echo "------------------------------";
  }

  // see if they are new links
  public function test_urls()
  {
    echo "--------------";
    echo "<br />";
    echo "## CONSOLE ##";
    echo "<br />";
    echo "## List of don't worked urls ##";
    echo "<br />";
    echo "--------------";
    echo "<br /><table>";
    $urls = $this->get_urls_BDD();
    foreach ($urls as $id => $url) {

      $parsedUrl = parse_url($url);
      $host = explode('.', $parsedUrl['host']);
      if (!in_array(SUB_DOMAIN, $host)) {
          echo "<tr><td style='padding:20px'>$url</td><td style='padding:20px'>dont in de good subdomain ".SUB_DOMAIN;
          echo "<script>
          setTimeout(function() {
          var scrollBottom = $(window).scrollTop() + $(window).height();
          $(window).scrollTop(scrollBottom);}
          ,80);
          </script></td></tr>";
          // affiche les urls que l'on s'aprète à ajouter en direct
          ob_flush();
          flush();

      } else{
        $work = $this->url_work($url);
        if (!$work[0]) {
          echo "<tr><td style='padding:20px'>$url</td><td style='padding:20px'>$work[1]";
          echo "<script>
          setTimeout(function() {
          var scrollBottom = $(window).scrollTop() + $(window).height();
          $(window).scrollTop(scrollBottom);}
          ,80);
          </script></td></tr>";
          // affiche les urls que l'on s'aprète à ajouter en direct
          ob_flush();
          flush();
        }
      }
    }
    echo "</table><br />";
    echo "<br />";
    echo "------------------------------";
    echo "<br />";
    echo "## Verification successful ##";
    echo "<br />";
    echo "------------------------------";
  }

  public function menu_compare(){
    // get all the links menu of the actuel website
    $menu = $this->get_menu_links(HOME, MENU_div_a, MENU_div_b);
    // get all the links menu of the new website
    $menu_new = $this->get_menu_links(HOME_new, MENU_new_div_a, MENU_new_div_b);
    // the sup var is a suplement if a url dont exist so dont shift all the array
    $sup = 0;
    $miss_old = 0;
    $miss_new = 0;
    $padding = "20px";
    ?>
    <table>
      <tr style="border: 2px solid black">
        <td style="padding-top:<?= $padding ?>; font-weight:bold">Liens de <?= HOME ?></td>
        <td style="padding-top:<?= $padding ?>;"></td>
        <td style="padding-top:<?= $padding ?>; font-weight:bold">Liens de <?= HOME_new ?></td>
      </tr>
      <?php
      foreach ($menu as $key => $link) {
        $act = $key+$sup;
        if (isset($menu_new[$act]) && $link != $menu_new[$act]) {

          $is_in_new_menu = 0;
          // see if the link is in the new menu
          for ($i=1; $i < 5; $i++) {
            if (isset($menu_new[$act+$i]) && $link == $menu_new[$act+$i]) {
              $is_in_new_menu = $i;
            }
          }
          // if the link is in the other menu, reset the good index and "set" links from
          // the new menu in menu
          if ($is_in_new_menu > 0) {
            for ($i=0; $i <$is_in_new_menu ; $i++) {
              $act = $key+$sup;?>
              <tr>
                <td style="padding-top:<?= $padding ?>;"></td>
                <td style="padding:<?= $padding ?> <?= $padding ?> 0 <?= $padding ?>; font-weight:bold"> <= </td>
                <td style="padding-top:<?= $padding ?>;"><?= $menu_new[$act] ?> </td>
              </tr>

              <?php $sup ++;
              $miss_old ++;
            }
          }



          $is_in_menu = 0;
          // see if the link is in the old menu
          for ($i=1; $i < 10; $i++) {
            if (isset($menu[$key+$i]) && $menu_new[$act] == $menu[$key+$i]) {
              $is_in_menu = $i;
            }
          }
          // if the link is in the other menu, reset the good index and "set" links from
          // the old menu in menu
          if ($is_in_menu > 0) {
            for ($i=0; $i < $is_in_menu ; $i++) {
              $act = $key+$sup;?>
              <tr>
                <td style="padding-top:<?= $padding ?>;"><?= $menu[$act] ?></td>
                <td style="padding:<?= $padding ?> 20px 0 <?= $padding ?>; font-weight:bold"> => </td>
                <td style="padding-top:<?= $padding ?>;"> </td>
              </tr>

              <?php $sup --;
              $miss_new ++;
            }
          }
        }
      } ?>
      <tr>
        <td style="padding-top:<?= $padding ?>; font-weight:bold">Il manque <?= $miss_old ?> liens dans <?= HOME ?></td>
        <td style="padding-top:<?= $padding ?>;"></td>
        <td style="padding-top:<?= $padding ?>; font-weight:bold">Il manque <?= $miss_new ?> liens dans <?= HOME_new ?></td>
      </tr>
    </table>


    <?php

  }

  // return all the links of the menu of the page
  // menu a is the begin of the menu div
  // menu b is the end of the menu div
  public function get_menu_links($url, $menu_a, $menu_b){
    $url = $this->remove_accents(urldecode($url));


    $content = file_get_contents($url);
    $first_step = explode($menu_a, $content );
    $second_step = explode($menu_b, $first_step[1] );

    // get all the links from the menu
    $motif='#<a href="(.*?)"(.*?)>#';

    preg_match_all($motif,$second_step[0],$out,PREG_PATTERN_ORDER);
    return $out[1];
  }

}
$diff = new diff_tester();
