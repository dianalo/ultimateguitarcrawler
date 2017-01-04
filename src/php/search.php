<?php
error_reporting(E_ALL & ~E_WARNING);
//error_reporting(E_ALL);
header('Content-type: text/html; charset=utf-8');

if(!array_key_exists("name", $_GET)){
  $basislayout = new DOMDocument();
  $basislayout->loadHTMLFile("../HTML/layout.html");
  echo $basislayout->saveHTML();
}
else{
  $name = htmlspecialchars($_GET["name"]);

  $song = search($name);

  $songname = $song[0];
  $url = $song[1];
  //echo $url;

  //fetch ultimate guitar chord webpage of queried song
  $handle = curl_init($url);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
  $res = utf8_decode(curl_exec($handle));
  //var_dump($res);

  //trim it
  //echo getSongContent($res);
  $dom = injectIntoHTML("chordtext", getSongContent($res), null);
  $dom = injectIntoHTML("video", getVideoDOM(trim($songname)), $dom);

  echo $dom->saveHTML();
}

//returns ultimate guitar homepage with best rating among all results from query q
function search($q)
{
  $value = str_replace(' ', '+', $q);
  //get search results from ultimate guitar

  $handle = curl_init("http://www.ultimate-guitar.com/search.php?title=".$value."&approved%5B1%5D=1&page=1&tab_type_group=text&app_name=ugt&order=myweight&type=300");
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
  $res = utf8_decode(curl_exec($handle));

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
  $maxURL = null;
  for($i=0; $i<$ratings->length; $i++){
    if($ratings[$i]->nodeValue > $maxRating){
      $maxRating = $ratings[$i];
      $maxURL = $songs[$i]->getAttribute('href');
      $maxName = $songs[$i]->nodeValue;
    }
  }

  //var_dump($maxElem);
  return array($maxName, $maxURL);
}

function getSongContent($htmlString){
  $html = new DOMDocument();
  $html->loadHTML($htmlString);

  $xpath = new DOMXPath($html);
  $trimmed = $xpath->query("//div[@id='cont']//pre[@class='js-tab-content']")->item(0);
  // var_dump($trimmed);
  // if($trimmed == ""){
  //   $trimmed = $xpath->query("//div[@id='cont']//pre[@class='js-tab-content']")->nodeValue;
  // }

  //convert to HTML string
  $trimmedString = "";
  $newdoc = new DOMDocument();
  $cloned = $trimmed->cloneNode(TRUE);
  $newdoc->appendChild($newdoc->importNode($cloned,TRUE));
  $trimmedString .= $newdoc->saveHTML();
  //var_dump($trimmedString);

  return $trimmedString;

}

function getVideoDOM($q){
  $value = str_replace(' ', '+', $q);
  //get search results from ultimate guitar

  $handle = curl_init("https://www.youtube.com/results?search_query=".$value);
  curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
  $res = utf8_decode(curl_exec($handle));

  //$errmsg  = curl_error($handle);

  $html = new DOMDocument();
  $html->loadHTML($res);
  //var_dump($html);
  $finder = new DOMXPath($html);
  //$nodes = $finder->query("b[@class='ratdig']");
  //ol[@class='item-section']
  $firstRes = $finder->query("//div[@id='results']//div[@data-context-item-id]")->item(0);
  //var_dump($firstRes);
  //$videoID = $firstRes->hasAttribute("data-context-item-id");
  $videoID = $firstRes->getAttribute("data-context-item-id");

  //var_dump($videoID);

  $videoDOM = new DOMDocument();
  $videoDOM->loadHTML('<iframe width="420" height="315"
src="https://www.youtube.com/embed/'.$videoID.'">
</iframe>');
  return $videoDOM->saveHTML();
}

function injectIntoHTML($parentId, $injectedHTML, $dom){

  if($dom == null){
    $dom = new DOMDocument();
    $dom->loadHTMLFile("../HTML/layout.html");
  }

  $parent = $dom->getElementById($parentId);
  //create fragment, append HTML to it & append it to node -> only works for correctly formed html
  // $frag = $dom->createDocumentFragment();
  // $frag->appendXML($injectedHTML);
  // var_dump($frag);
  // $parent->appendChild($frag);

  //workaround for malformed HTML: load new HTML into DOMDocument and append it
  $inDom = new DOMDocument();
  $inDom->loadHTML($injectedHTML);
  $parent->appendChild($dom->importNode($inDom->documentElement, true));

  return $dom;
}
?>
