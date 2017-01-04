<?php
error_reporting(E_ALL & ~E_WARNING);
$url = search("kids");
//echo $url;

//fetch ultimate guitar chord webpage of queried song
$handle = curl_init($url);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
$res = curl_exec($handle);
//var_dump($res);

//trim it
echo getSongContent($res);


//returns ultimate guitar homepage with best rating among all results from query q
function search($q)
{
  $value = str_replace(' ', '+', $q);
  //get search results from ultimate guitar

  $handle = curl_init("http://www.ultimate-guitar.com/search.php?title=".$value."&approved%5B1%5D=1&page=1&tab_type_group=text&app_name=ugt&order=myweight&type=300");
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
  $res = curl_exec($handle);

  //$errmsg  = curl_error($handle);

  $html = new DOMDocument();
  $html->loadHTML($res);
  //var_dump($html);
  $finder = new DOMXPath($html);
  //$nodes = $finder->query("b[@class='ratdig']");
  $songs = $finder->query("//a[@class='song result-link']");
  //var_dump($songs);

  $ratings = $finder->query("//td//b[@class='ratdig']");

  //find maximum songrating
  $maxRating = 0;
  $maxElem = null;
  for($i=0; $i<$ratings->length; $i++){
    if($ratings[$i]->nodeValue > $maxRating){
      $maxRating = $ratings[$i];
      $maxElem = $songs[$i]->getAttribute('href');
    }
  }

  return $maxElem;
  //var_dump($maxElem);
}

function getSongContent($htmlString){
  $html = new DOMDocument();
  $html->loadHTML($htmlString);

  $xpath = new DOMXPath($html);
  $trimmed = $xpath->query("//div[@id='cont']//pre[@class='js-tab-content']")->item(0);

  //convert to HTML string
  $trimmedString = "";
  $newdoc = new DOMDocument();
  $cloned = $trimmed->cloneNode(TRUE);
  $newdoc->appendChild($newdoc->importNode($cloned,TRUE));
  $trimmedString .= $newdoc->saveHTML();

  return $trimmedString;

}
?>
