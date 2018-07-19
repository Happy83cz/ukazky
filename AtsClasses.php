<?
namespace ATSapp;

/**
 * Main class of the Site
 */
class Site {
  public $title = "";
  public $og_image = "//www.ats-global.com/images/ATS_article.jpg";
  public $description = "";
  public $URL = "";
  private static $languages = array();
  private static $countries = array();
  public $seo_type = "";
  public $type = "category"; // article, category
  public $type_cms = "categories"; // type for CMS
  public $id = 1; // id of the article / category
  public $canonical = "";//"<link rel='canonical' href='*' />";
  public $hreflang = "<link rel='alternate' hreflang='|' href='*' />";
  public $lang = "";
  public $country = "";
  public $offices = array();
  public $obj = [];
  public $server = "";
  private static $countryIP = "";
  public $path = [];
  public $home_link = "";

  /**
   * Constructor
   */
  function __construct() {
    if (isset($_REQUEST['seo']))
      $_REQUEST['seo3'] = $_REQUEST['seo'];
    if (isset($_REQUEST['seo3'])) $this->seo();
    $this->old_links();
    $this->server = "https://" . $_SERVER['HTTP_HOST'];
    $this->title = "ATS Global";
    $this->URL = 'https://' . $_SERVER['HTTP_HOST'] . @$_SERVER['REDIRECT_URL'];
    $this->lang = $GLOBALS['lang'];
    $this->country = $GLOBALS['country'];
    self::$languages = $this->getLanguages();
    self::$countries = $this->getCountries();
    $this->id = (0 + @$_REQUEST['id']);
    if ($this->id == 0) $this->id = 1;
    $this->setType();
    $this->getHreflang();
    if (in_array($this->type, array("article", "category", "form")))
      $this->setOffices();
    $this->path = $this->getPath();
    $this->home_link = $this->server . (((trim($this->lang) != "") && ($this->lang != "en")) ? "/" . $this->lang . "/" : "");
  }

  public function __get($prom) {
    if (isset(self::$$prom)) {
      return self::$$prom;
    }
    if (property_exists($this, $property)) {
      return $this->$property;
    }
  }

  public function __isset($prom) {
    if (isset($this->$prom)) return true;
    if (isset(self::$$prom)) return true;
    return false;
  }

  /**
   * Sets attributes by old links
   */
  private function old_links() {
    if (isset($_REQUEST['art'])) {
      $this->seo_type = "art";
      $_REQUEST['id'] = 0 + $_REQUEST['art'];
    } else if (isset($_REQUEST['cat'])) {
      $this->seo_type = "cat";
      $_REQUEST['id'] = 0 + $_REQUEST['cat'];
    }
  }

  /**
   * Sets variables by seo link
   */
  private function seo() {
    $seo = getSQL("SELECT * FROM ats_Seo WHERE seo = '" . sql_vstup($_REQUEST['seo3']) . "' LIMIT 1");
    
    if ($seo[0]) {
      $_REQUEST['id'] = $seo[0]['seo_type_id'];
      $this->seo_type = $seo[0]['seo_type'];
    } else {
      $_REQUEST['id'] = 1;
    }
  }
    
  /**
   * Sets the offices for the part of site with a map and list of offices
   * 
   * @return void
   */
  private function setOffices() {
    $this->offices = getSQL("SELECT c.*
      FROM ats_Contacts AS c " . (($this->country != "gx") ? " 
      WHERE c.con_Country = '" . sql_vstup($this->country) . "'" : "") . " 
      ORDER BY c.con_CategoryID, c.con_Main DESC, c.con_Priority DESC");
    array_walk($this->offices, function(&$a, $b) {
      $a['add'] = /*"<b>" . $a['con_Subtitle'] . "</b><br>" .*/
        ((trim($a['con_Address1']) != "") ? $a['con_Address1'] . "<br>" : "") .
        ((trim($a['con_Address2']) != "") ? $a['con_Address2'] . "<br>" : "") .
        ((trim($a['con_CityZip']) != "") ? $a['con_CityZip'] . "<br>" : "") .
        ((trim($a['con_Tel']) != "") ? "<span class='phone'>" . telephonize($a['con_Tel']) . "</span><br>" : "");
      });
    formatArray($this->offices, ['con_Subtitle'], ['nokey'=>1]);
    array_walk($this->offices, function (&$a, $b) {
      $tei = "";
      if ($a['array']) {
        $lei = ".[@#++]\\";
        foreach($a['array'] as $o) {
          if ($lei == ".[@#++]\\") {
            $lei = $o['con_ExtraInfo'];
          } elseif ($lei != $o['con_ExtraInfo']) {
            $lei = "\\[++#@].";
          }
        }
        if ($lei != "\\[++#@].") {
          $a['key2'] = HtmlTextFromDB($lei);
        } else {
          foreach($a['array'] as $k=>$o) {
            $a['array'][$k]['add'] .= HtmlTextFromDB(trim($o['con_ExtraInfo'])) . "<br>";
          }
        }
      }
    });
  }

  /**
   * Inits the language and country for this session
   * 
   * @return void
   */
  public static function initLangCountry() {
    $byip = self::CLbyIP();
    $locate = false;
    if (isset($_REQUEST['h83'])) {
      if (isset($_SESSION['lang']) && trim($_SESSION['lang']) != "") {
        $GLOBALS['lang'] = $_SESSION['lang'];
      } else {
        $GLOBALS['lang'] = $byip['lang'];
        $locate = true;
      }
      if (isset($_REQUEST['lang']) && trim($_REQUEST['lang']) != "") {
        if (in_array(trim($_REQUEST['lang']), array_map(function($a){return $a['code'];}, self::$languages))) {
          $GLOBALS['lang'] = trim($_REQUEST['lang']);
          $_SESSION['lang'] = $GLOBALS['lang'];
        }
      }
      if ($locate && ($GLOBALS['lang'] != 'en')) {
        header("Location: " . $this->server . "/" . $GLOBALS['lang'] . "/" . ((trim($seo2) != "") ? $seo2 : ""));
        exit;
      }
    } else {
      if (isset($_REQUEST['lang']) && ($_REQUEST['lang'] != "")) {
        $GLOBALS['lang'] = $_REQUEST['lang'];
        $_SESSION['lang'] = $GLOBALS['lang'];
      }
    }

    if (isset($_SESSION['country']) && trim($_SESSION['country']) != "") {
      $GLOBALS['country'] = $_SESSION['country'];
    } else {
      $GLOBALS['country'] = $byip['country'];
    }
    if (isset($_REQUEST['country']) && trim($_REQUEST['country']) != "") {
      if (in_array(trim($_REQUEST['country']), array_map(function($a){return $a['code'];}, self::$countries))) {
        $GLOBALS['country'] = trim($_REQUEST['country']);
        $_SESSION['country'] = $GLOBALS['country'];
      }
    }

    if (!isset($GLOBALS['lang'])) {
      $GLOBALS['lang'] = "en";
      $_SESSION['lang'] = "en";
    }
    if (!isset($GLOBALS['country'])) {
      $GLOBALS['country'] = "gb";
    }
  }

  /**
   * Return the pair of language and country for users ip
   * 
   * @return array of key-value "country" and "lang"
   */
  public static function CLbyIP() {
    $ip = getSQL('SELECT i.country, c.name FROM ip2nation4 AS i, country_codes AS c WHERE INET_ATON("'.$_SERVER['REMOTE_ADDR'].'") BETWEEN ip_from AND ip_to AND i.country = c.code2 LIMIT 1');
    $cntry = "";
    if (isset($ip[0]) && count($ip[0]) > 0) {
      $_SESSION['nation'] = $ip[0]['country'];
      
      $cntry = strtolower($ip[0]['country']);
      self::$countryIP = $ip[0]['name'];
        
      switch ($cntry) {
        case strpos('de,at', $cntry):
          $result = array('country' => 'de', 'lang' => 'de');
          break;    
        case 'nl':
          $result = array('country' => 'nl', 'lang' => 'nl');
          break;
        case 'be':
          $result = array('country' => 'be', 'lang' => 'nl');
          break;
        case 'fr':
          $result = array('country' => 'be', 'lang' => 'fr');
          break;
        case strpos('ar,bo,cl,do,ec,gt,hn,co,cr,cu,mx,ni,pa,py,pe,sv,uy,ve', $cntry):
          $result = array('country' => 'mx', 'lang' => 'es');
          break;
        case 'es':
          $result = array('country' => 'es', 'lang' => 'es');
          break;
        case strpos('ag,bs,bb,bz,br,dm,gd,gy,ht,jm,sr,lc,kn,vc,tt,us', $cntry):
          $result = array('country' => 'us', 'lang' => 'ae');
          break;
        case 'ca':
          $result = array('country' => 'ca', 'lang' => 'ae');
          break;
        case strpos('au,nz,nf,fj,nc,pg,sb,vu,ck,pf,nu,ws,tk,to,tv', $cntry):
          $result = array('country' => 'au', 'lang' => 'en');
          break;
        case strpos('ao,bw,ls,mg,mw,mz,na,sz,zm,zw,za', $cntry):
          $result = array('country' => 'za', 'lang' => 'en');
          break;
        case strpos('cz,sk', $cntry):
          $result = array('country' => 'cz', 'lang' => 'cs');
          break;
        case 'tr':
          $result = array('country' => 'tr', 'lang' => 'tr');
          break;
        case strpos('cn,my,jp,ph,tw,hk,sg,bn,tl,la,mm,th,vn,cx,cc', $cntry):
          $result = array('country' => 'sg', 'lang' => 'en');
          break;
        case 'cn':
          $result = array('country' => 'cn', 'lang' => 'cn');
          break;
        case 'ie':
          $result = array('country' => 'ie', 'lang' => 'en');
          break;  
        case 'it':
          $result = array('country' => 'it', 'lang' => 'it');
          break;  
        default:
          $result = array('country' => 'gb', 'lang' => 'en');
          break;
      }
    } else {
      $result = array('country' => 'gb', 'lang' => 'en');
    }
    return $result;
  }

  /**
   * Sets the hreflangs tag for the head of the page
   * 
   * @return void
   */
  private function getHreflang() {
    $hl = "";
    $langs = array_values(array_unique(array_map(function($a){return $a['code'];}, self::$languages)));
    
    if (isset($this->obj['groups'])) {
      foreach($this->obj['groups'] as $g) {
        if (in_array(strtolower($g['art_Language']), $langs)) {
          array_splice($langs, array_search(strtolower($g['art_Language']), $langs), 1);
          $c = $g['art_DisplayCountry'];
          $c = explode(",", $c);
          foreach($c as $c_) {
            if (trim($c_) == "") continue;
            $hl .= "<link rel='alternate' hreflang='" . strtolower($g['art_Language']) . "-" . strtoupper(trim($c_)) . "' href='" . 
              $this->server . "/" . friendly_url($g['art_Title'], $g['art_Language'] == "cn") . "_" . $g['art_ID'] . "_" . 
              strtolower($g['art_Language']) . trim(strtolower($c_)) . "' />\r\n";
          }
        }
      }
    }
    
    foreach(self::$languages as $l) {
      if (in_array($l['code'], $langs)) {
        array_splice($langs, array_search(strtolower($l['code']), $langs), 1);
        if ($this->type == "article") {
          $hl .= "<link rel='alternate' hreflang='" . $this->hreflangstr($l['cntry_link']) . "' href='" . $this->server . "/" . 
                friendly_url($this->obj['art_Title'], $l['code'] == "cn") . "_" . $this->id . "_" . 
                str_replace("-", "", $l['cntry_link']) . "' />\r\n";
        } elseif ($this->type == "category") {
          if (isset($this->obj['cat_Name_' . $l['code']])) {
            $hl .= "<link rel='alternate' hreflang='" . $this->hreflangstr($l['cntry_link']) . "' href='" . $this->server . "/" . 
                  friendly_url($this->obj['cat_Name_' . $l['code']], $l['code'] == "cn") . "_" . $this->id . "_" . 
                  str_replace("-", "", $l['cntry_link']) . "' />\r\n";
          }
        }
      }
    }

    $this->hreflang = $hl;
  }

/**
 * Returns hreflang string from the seo link
 * 
 * @param string $a seo link
 * @return string right format of hreflang
 */
  private function hreflangstr($a) {
    return substr($a, 3) . "-" . strtoupper(substr($a, 0, 2));
  }

/**
 * Sets canonical links for the tag <HEAD>
 * 
 * @return void
 */
  private function getCanonical() {
    $can = "";
    $pref_l = "en";
    $pref_c = "gb";

    if (count($this->obj) == 0) {
      $this->canonical = "";
      return;
    }
    if ($this->type == "article") {
      if (isset($this->obj['art_Language']) && (trim($this->obj['art_Language']) != '')) {
        $x = explode(",", trim($this->obj['art_Language']));
        $pref_l = trim($x[0]);
        $can = friendly_url($this->obj['art_Title'], $x == "cn");
      }
      if (isset($this->obj['art_DisplayCountry']) && (trim($this->obj['art_DisplayCountry']) != '')) {
        $x = explode(",", trim($this->obj['art_DisplayCountry']));
        $pref_c = trim($x[0]);
      }
    } elseif ($this->type == "category") {
      if (isset($this->obj['cat_ID'])) {
        $pref_l = $GLOBALS['lang'];
        $pref_c = $GLOBALS['country'];
        $ca = @$this->obj['cat_Name_en'];
        if (isset($this->obj['cat_Name_' . $GLOBALS['lang']]) && (trim($this->obj['cat_Name_' . $GLOBALS['lang']]) != ""))
          $ca = @$this->obj['cat_Name_' . $GLOBALS['lang']];
        $can = friendly_url($ca, $GLOBALS['country'] == "cn");
      }
    } elseif ($this->type == "form") {
      $this->canonical = "";
      return;
    }

    $this->canonical = str_replace("*", $can . "_" . $this->id . "_" . $pref_c . strtolower($pref_l), $this->canonical);
  }

/**
 * Sets the page type (article, category, form, ...) and the main object
 * 
 * @return void
 */
  private function setType() {
    $sets = false;
    if (isset($_REQUEST['search'])) {
      $this->obj = ["cat_ID" => 99999, "cat_Name" => translate("Search") . " '" . trim($_REQUEST['search']) . "'", "cat_Name2" => "", "cat_Description" => ""];
      $this->type = "category";
      $this->type_cms = "categories";
      $this->id = 99999;
      $sets = true;
    }
    
    if (!$sets) {
      $cat = getSQL("SELECT * FROM ats_Categories WHERE cat_ID = " . (0 + $this->id) . " LIMIT 1");
      if (isset($cat[0]) && $this->seo_type != "art") {
        $ok = (($this->seo_type == "cat") || ($this->id == 1));
        foreach(self::$languages as $l) {
          if (isset($l['seo']) && isset($cat[0]['cat_Name_' . $l['code']])) {
            if ((levenshtein(friendly_url($cat[0]['cat_Name_' . $l['code']]), $_REQUEST['seo3']) < 5) || (isset($_REQUEST['seo3']))) {
              if ($cat[0]['cat_ID'] == 7) {
                $_REQUEST['form'] = 2;
                $ok = true;
              }          
            }
          }
        }

        if ($ok) {
          $this->obj = $cat[0];
          if (isset($cat[0]['cat_Name_' . $GLOBALS['lang']]) && (trim($cat[0]['cat_Name_' . $GLOBALS['lang']]) != ""))
            $this->obj['cat_Name'] = $cat[0]['cat_Name_' . $GLOBALS['lang']];
          else
            $this->obj['cat_Name'] = $cat[0]['cat_Name_en'];
          
          if (isset($cat[0]['cat_Name2_' . $GLOBALS['lang']]) && (trim($cat[0]['cat_Name2_' . $GLOBALS['lang']]) != ""))
            $this->obj['cat_Name2'] = $cat[0]['cat_Name2_' . $GLOBALS['lang']];
          else
            $this->obj['cat_Name2'] = $cat[0]['cat_Name2_en'];

          if (isset($cat[0]['cat_Description_' . $GLOBALS['lang']]) && (trim($cat[0]['cat_Description_' . $GLOBALS['lang']]) != ""))
            $this->obj['cat_Description'] = $cat[0]['cat_Description_' . $GLOBALS['lang']];
          else
            $this->obj['cat_Description'] = $cat[0]['cat_Description_en'];
          $this->security($this->obj['cat_Description']);
          $this->obj['list'] = array();
          $this->type = "category";
          $this->type_cms = "categories";
          $this->title = $this->obj['cat_Name'];
          $this->obj['cat_Description'] = HtmlTextFromDB($this->obj['cat_Description']);

          if ($this->id == 1) {
            $ttt = new Template("", $this->obj['cat_Description']);
            $this->obj['cat_Description'] = $ttt->_get();
          }

          $this->description = substr(str_replace("\r\n", " ", strip_tags($this->obj['cat_Description'])), 0, 200);

          if ($this->id == 65) {
            $this->obj['list'] = getEvents(constant('\ATSapp\onPage'), $all);
          }
          if ($this->id == 26) {
            $this->obj['list'] = getArticles(["art_CategoryID = 26"], "art_ID DESC", constant('\ATSapp\onPage'), "art_ID, art_ShortText, art_Title", $all);
          }
          if (preg_match("~<img[^>]*class=['\"][^>]*pic_[^>]*['\"][^>]*>~smU", $this->obj['cat_Description'], $pregs)) {
            if (preg_match("~src=['\"]([^'\"]+)['\"]~i", $pregs[0], $pregs2)) {
              $this->og_image = $pregs2[1];
            }
          } elseif (trim($this->obj['cat_IconLarge']) != "") {
            $this->og_image = $this->obj['cat_IconLarge'];
          }
          $sets = true;
        }
      }
    }
    
    if (!$sets) {
      $art = getSQL("SELECT * FROM ats_Articles WHERE art_ID = " . (0 + $this->id) . " LIMIT 1");
      if (isset($art[0])) {
          if ($art[0]['art_CategoryID'] == 65) { // Event
            $art[0]['art_ShortText'] = "<div class='date_location'>" .
              "<div class='date'>" . date(getDateFormat(), strtotime($art[0]['art_DateOut'])) . "</div>" .
              "<div class='location'>" . $art[0]['art_Location'] . "</div>" . 
              "</div>" .
              $art[0]['art_ShortText'];
          }
          $this->obj = $art[0];
          $this->security($this->obj['art_LongText']);
          $hl = getSQL("SELECT art_ID, art_Title, art_Language, art_DisplayCountry 
                          FROM ats_Articles 
                        WHERE art_ID IN 
                          (SELECT article FROM ats_ArticleGroups WHERE skupina IN 
                              (SELECT skupina FROM ats_ArticleGroups WHERE article = " . $this->id . "))");
          $this->obj['groups'] = $hl;
          $this->type = "article";
          $this->type_cms = "articles";
          $this->title = $this->obj['art_Title'];
          $this->obj['art_ShortText'] = HtmlTextFromDB($this->obj['art_ShortText']);
          $this->obj['art_LongText'] = HtmlTextFromDB($this->obj['art_LongText']);
          $this->description = substr(str_replace("\r\n", " ", strip_tags($this->obj['art_LongText'])), 0, 200);
          if (preg_match("~<img[^>]*class=['\"][^>]*pic_[^>]*['\"][^>]*>~smU", $this->obj['art_LongText'], $pregs)) {
            if (preg_match("~src=['\"]([^'\"]+)['\"]~i", $pregs[0], $pregs2)) {
              $this->og_image = $pregs2[1];
            }
          } elseif (trim($art['art_IconLarge']) != "") {
            $this->og_image = trim($art['art_IconLarge']);
          }
          $sets = true;
      }
    }
    
    if (isset($_REQUEST['form'])) {
      $this->obj = [];
      $this->type = "form";
      $this->type_cms = "form";
      $this->id = (0 + $_REQUEST['form']);
      $sets = true;
    }

    if (!$sets) {
      $this->type = "category";
      $this->id = 1;
    }
  }

  private function make_some_changes(&$text) {
    $text = str_replace("\$country", $GLOBALS['country'], $text);
    $text = str_replace("\$lang", $GLOBALS['lang'], $text);
  }

  private function security(&$html) {
    $html = preg_replace_callback("~<a ([^>]+)>~i", 
      function($match) {
        if (!preg_match("~!secure!~i", $match[1])) return $match[0];

        $m1 = str_replace("!secure!", "", $match[1]);
        $m1 = preg_replace_callback("~href=(['\"])([^'\"]*)(['\"])~i", 
          function($m) {
            $f = urldecode(str_replace("'", "\"", substr($m[2], strrpos($m[2], "/") + 1)));
            return "href=" . $m[1] . "#" . $m[3] . " onclick='secure_download(\"" . $f . "\", \"" . $this->base64url_encode($m[2]) . "\")'";
          }, $m1);
        
        return "<a " . $m1 . ">";
      }, $html);
  }

  private function base64url_encode($data) { 
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
  } 

/**
 * Returns all languages
 * 
 * @return array languages
 */
  private function getLanguages() {
    $languages = array();
    
    $uri = $_SERVER['REQUEST_URI'];
    if (trim($uri) == "") $uri = "/";
    $uri = preg_replace("~/([a-z]{2}/)?~i", "/%/", $uri);

    $c = getSQL("SELECT DISTINCT SUBSTRING(cntry_link, -2) as seo, SUBSTRING_INDEX(cntry_LangName, '(', 1) as lang, cntry_link 
                   FROM ats_Countries 
                  WHERE SUBSTRING(cntry_link, -2) <> 'ae' 
                  GROUP BY seo 
                  ORDER BY seo <> '" .(@$GLOBALS['lang']) . "', seo");
    $link = 0;
    foreach($c as $c_) {
      if (trim($c_['seo']) == "") continue;
      $languages[] = array(
        "code" => $c_['seo'], 
        "seo" => $this->server . ($c_['seo'] == "en" ? str_replace("/%", "", $uri) : str_replace("%", $c_['seo'], $uri))/*$this->addUrlParam(array("lang" => $c_['seo']))*/, 
        "name" => $c_['lang'], 
        "cntry_link" => str_replace("/", "-", $c_['cntry_link']),
        "link" => $link
      );
      $link = 1;
    }
    return $languages;
  }

/**
 * Returns all countries
 * 
 * @return array countries
 */
  private function getCountries() {
    $countries = array(array("code" => "gx", "seo" => $this->addUrlParam(array("country" => "gx")), "name" => "Global"));
    $c = getSQL("SELECT DISTINCT cntry_en, cntry_Abbr FROM ats_Countries ORDER BY cntry_en");
    foreach($c as $c_) {
      $countries[] = array("code" => $c_['cntry_Abbr'], "seo" => $this->addUrlParam(array("country" => $c_['cntry_Abbr'])), "name" => $c_['cntry_en']);
    }
    return $countries;
  }

/**
 * Returns current url link with changes/including
 * 
 * @param array $mod array of the params
 * @return string url
 */
  private function addUrlParam($mod = array()) { 
    $url = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
    $query = explode("&", $_SERVER['QUERY_STRING']);
    //print_r2($_SERVER); exit;
    if (strpos($_SERVER['REQUEST_URI'], "?") !== false) {$queryStart = "&";} else {$queryStart = "?";}
    foreach($query as $q) {
      if (trim($q) == "") continue;
      list($key, $value) = explode("=", trim($q)); 
      if(array_key_exists($key, $mod)) { 
        if($mod[$key]) { 
          $url = preg_replace('/'.$key.'='.$value.'/', $key.'='.$mod[$key], $url); 
        } else { 
          $url = preg_replace('/&?'.$key.'='.$value.'/', '', $url); 
        } 
      } 
    }    
    foreach($mod as $key => $value) { 
      if($value && !preg_match('/'.$key.'=/', $url)) { 
        $url .= $queryStart.$key.'='.$value; 
      } 
    } 
    return $url; 
  }

  /**
   * Returns path to the arcticle / category
   * 
   * @return array path
   */
  private function getPath() {
    $p = [];
    $ret = "";

    if ($this->type == "category") { 
      $cid = $this->id;
      array_push($p, $this->obj);
      $ret = true;
      do {
        $up = getUpcats($cid);
        if ((count($up) > 0) && !in_array($up['cat_ID'], array(1, 7, 8))) {
          array_push($p, $up);
          $cid = $up['cat_ID'];
        } else {
          $ret = false;
        }
      } while($ret);
    }
    if ($this->type == "article") {
      $cid = $this->obj['art_CategoryID'];
      $cc = getSQL("SELECT cat_ID, cat_Name_" . sql_vstup($GLOBALS['lang']) . " as cat_Name, cat_Name_en FROM ats_Categories WHERE cat_ID = " . (0 + $cid) . " LIMIT 1");
      if (count($cc) > 0)
        $p = $cc;
      $ret = true;
      do {
        $up = getUpcats($cid);
        if ((count($up) > 0) && !in_array($up['cat_ID'], array(1, 7, 8))) {
          array_push($p, $up);
          $cid = $up['cat_ParentID'];
        } else {
          $ret = false;
        }
      } while($ret);
    }

    if (count($p) > 0) {
      foreach($p as $p_=>$pp) {
        $p[$p_]['seo'] = getLink($pp);
      }
      $p = array_map(function($a) { return "<a href='" . $a['seo'] . "'>" . ((trim(@$a['cat_Name']) != "") ? @$a['cat_Name'] : @$a['cat_Name_en']) . "</a>"; }, array_reverse($p));
      array_unshift($p, "<a href='https://testing.ats-global.com'>" . translate("Home") . "</a>");
      $ret = implode(" &gt; ", $p);
    }

    return $ret;
  }
}

/**
 * Template class
 */
class Template {
  private $file;
  private $html;
  private $template_dir = "templates/";

/**
 * Constructor
 * 
 * @param string $file name of the template file
 * @return void
 */
  function __construct($file, $content = "") {
    $this->file = $file;
    if ($content == "") {
      $this->_load();
    } else {
      $this->html = $content;
    }
    $this->_fill($this->html);
    $this->_translate();
    $this->_afterFlush();
  }

/**
 * Loads html template from the file
 * 
 * @return void
 */
  private function _load() {
    try {
      $this->html = file_get_contents($this->template_dir . $this->file);
    } catch(Error $e) {
      $this->html = "";
      if (DEBUG) {
        $this->html = "<span class='debug_err'>Template '" . $this->file . "' was not found</span>";
      }
    }
  }

/**
 * Returns global variable (or local save in $prom)
 * 
 * @param string $n name of desired variable
 *        array $prom local variables
 * 
 * @return string value of $n param 
 */
  private function getGvar($n, $prom = array(), $default = null) {
    if (isset($prom[$n]))
      return $prom[$n];
    if (isset($GLOBALS[$n]))
      return $GLOBALS[$n];
      
    if ((($pos = strpos($n, ".")) !== false) || (($pos = strpos($n, "[")) !== false)) {
      $p_ = substr($n, 0, $pos);
      $p__ = substr($n, $pos + 1);
      $p__ = preg_split("~[\.\]]~i", $p__);
      
      $set = true;
      $val = $this->getGvar($p_, $prom, $default);
      foreach($p__ as $p___) {
        $p___ = trim($p___, "'\"[].");
        if ($p___ == "") continue;
        if ((gettype($val) == "array") && isset($val[$p___])) {
          $val = $val[$p___];
        } elseif (gettype($val) == "object" && isset($val->$p___)) {
          $val = $val->$p___;      
        } else {
          $set = false;
        }
      }
      if ($set)
        return $val;
    }
    if ($default !== null) {
      return $default;
    }

    return "VARIABLE '" . $n . "' DOESN'T EXIST";
  }

/**
 * Fill template
 * 
 * @param string $html content of the html template
 * @param mixed[] $prom local variables
 * @return void
 */
  public function _fill(&$html, $prom = array()) {
    $html = preg_replace_callback('~{@import (.*)}~smU', function($q) use ($prom) {
      return (new Template($q[1]))->_get();
    }, $html);

    $html = preg_replace_callback('~{@var (.*)}~smU', function ($q) {
      if (isset($GLOBALS['variables'][$q[1]]))
        return $GLOBALS['variables'][$q[1]];
      else
        return $q[1];
    }, $html);

    $html = preg_replace_callback('~{@for([0-9]+) (.*) in (.*)}(.*){@rof\\1}~smU', function($q) use ($prom) {
      $pompo = $this->getGvar($q[3], $prom);
      $htmlret = "";
      if (gettype($pompo) == "array") {
        foreach($pompo as $index=>$x) {
          $prom = array_merge($prom, array($q[2] => $x, "__index" => $index));
          $html2 = trim($q[4]);
          $this->_fill($html2, $prom);
          $htmlret .= $html2;
        }
      } elseif (gettype($pompo) == "object") {
        
      } else {
        $htmlret = "ARRAY '" . $q[3] . "' DOESN'T EXIST";
        if ($q[3] == 'prop.carousel') {
          $htmlret .= print_r($GLOBALS, true);
        }
      }

      return $htmlret;
    }, $html);

    
    $html = preg_replace_callback('~{@if([0-9]+) (.*);(.*);(.*)}(.*){@fi\\1}~smU', function ($q) use ($prom) {
      $html2 = "";
      $p = $this->getGvar($q[2], $prom);
      if (($q[3] == "=") && ($p == $q[4])) {
        $html2 = $q[5];
      } else if (($q[3] == "not") && ($p != $q[4])) {
        $html2 = $q[5];
      } else if (($q[3] == "notempty") && (!empty($p))) {
        $html2 = $q[5];
      } else if (($q[3] == "isset") && (gettype($p) != "string" || !preg_match('~DOESN\'T EXIST~i', $p))) {
        $html2 = $q[5];
      } else if (($q[3] == "!isset") && (gettype($p) == "string" && preg_match('~DOESN\'T EXIST~i', $p))) {
        $html2 = $q[5];
      } else if (($q[3] == "in") && (in_array($p, explode(",", $q[4])))) {
        $html2 = $q[5];
      } else if (($q[3] == "notin") && (!in_array($p, explode(",", $q[4])))) {
        $html2 = $q[5];
      } else {
        $html2 = "";
      }
      $this->_fill($html2, $prom);

      return $html2;
    }, $html);

    $html = preg_replace_callback('~(.)\{\$\$(.+)\}~smU', function ($p) use ($prom){
      if ($p[1] == "{")
        return $p[0];
      
      if ($p[2][0] == "\$")
        $p[2] = substr($p[2], 1);
        
      ob_start();
      eval("?><?php echo " . $p[2] . ";?><?");
      $ob = ob_get_clean();
      return $p[1] . $ob;
    }, $html);

    $html = preg_replace_callback('~(.)\{\$([a-zA-Z0-9-_\[\]\'\"\.]+)(\|.*)?\}~smU', function ($p) use ($prom){
      if ($p[1] == "{")
        return $p[0];
      
      if (!isset($p[3]) || (trim($p[3]) == ""))      
      return $p[1] . $this->getGvar($p[2], $prom);

      if ($p[3] == "|htmlentities")
        return $p[1] . htmlentities($this->getGvar($p[2], $prom));
      
      if (preg_match("~^\|\|(.*)$~i", $p[3], $pregs)) {
        return $p[1] . htmlentities($this->getGvar($p[2], $prom, $pregs[1]));
      }

    }, $html);
  }

/**
 * Translates the template
 * 
 * @return void
 */
  public function _translate() {
    $this->html = preg_replace_callback('~<text>(.*)</text>~smU', function($p) {
      if (isset($GLOBALS['translates'][$p[1]]) && trim($GLOBALS['translates'][$p[1]]) != "")
        return $GLOBALS['translates'][$p[1]];
      else
        return $p[1];
    }, $this->html);

    $this->html = preg_replace_callback('~<svgtext>([^<]*)</svgtext>~smU', function($p) {
      if (isset($GLOBALS['translates'][$p[1]]) && trim($GLOBALS['translates'][$p[1]]) != "")
        return $GLOBALS['translates'][$p[1]];
      else
        return $p[1];
    }, $this->html);
  }

  public function _afterFlush() {
    $this->html = preg_replace_callback('~<a.*href=[\'"]([^\'"]+)[\'"][^>]*>~smU', function($p) {
      if (strpos($p[0], "target=") === false) {
        if ((strpos($p[1], "ats-global.com") === false) && ($p[1]{0} !== "#") && ($p[1]{0} !== "{")) {
          return substr($p[0], 0, strlen($p[0]) - 1) . " target='_blank' >";
        }
      }
      return $p[0];
    }, $this->html);
  }

/**
 * Gets the html template
 * 
 * @return string html
 */
  public function _get() {
    return $this->html;
  }

/**
 * Gets the html template to standard output
 * 
 * @return void
 */
  public function _echo() {
    echo $this->html;
  }
}