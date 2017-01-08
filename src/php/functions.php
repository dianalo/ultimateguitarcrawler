<?php
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
  //$cloned->setAttribute("style", "overflow-y: scroll;");
  $newdoc->appendChild($newdoc->importNode($cloned,TRUE));
  $trimmedString .= $newdoc->saveHTML();
  //var_dump($trimmedString);

  return $trimmedString;

}

//translation if chords have strange names, that differ from picture names
$chord_map = array();

function extractChords($htmlString){
  global $chord_map;

  $html = new DOMDocument();
  $html->loadHTML($htmlString);

  //extract all spans (contain chords)
  $xpath = new DOMXPath($html);
  $spans = $xpath->query("//span");

  //add contents to array
  $chords = array();
  for($i=0; $i<$spans->length; $i++){
    $chord = $spans->item($i)->nodeValue;

    //chord name translation
    if(array_key_exists($chord, $chord_map)){
      $chord = $chord_map[$chord];
    }

    if(!in_array($chord, $chords)){
      array_push($chords, $chord);
    }
  }

  return $chords;
}

function generateChordView($chords){
  $dom = new DOMDocument();
  $ul = $dom->createElement('ul');
  //$dom->appendChild($ul);
  $path = "../../resources/all_chords/";

  for($i=0; $i<count($chords); $i++){
    $li = $dom->createElement('li');
    $img = $dom->createElement('img');
    $img->setAttribute('src', $path . $chords[$i] . '.png');
    $li->appendChild($img);
    $ul->appendChild($li);
  }

  $dom->appendChild($ul);


  return $dom->saveHTML();
  // <ul>
  //   <li><img src="../../resources/all_chords/A.png"/></li>
  //   <li><img src="../../resources/all_chords/A.png"/></li>
  // </ul>
}

function getCapoFret($htmlString){
  $html = new DOMDocument();
  $html->loadHTML($htmlString);

  //extract capo info
  $xpath = new DOMXPath($html);
  $capo = $xpath->query("//div[@class='t_dtde' and contains(text(), 'fret')]");

  $dom = new DOMDocument();
  if($capo->length > 0){
    $p = $dom->createElement('p', "CAPO: " . $capo->item(0)->nodeValue);
    $dom->appendChild($p);
  }
  else{
    $p = $dom->createElement('p', "no capo");
    $dom->appendChild($p);
  }

  return $dom->saveHTML();
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

  //remove all children and add new node
  $children = $parent->childNodes;
  while($children->length > 0){
    $parent->removeChild($children->item(0));
  }

  $parent->appendChild($dom->importNode($inDom->documentElement, true));

  return $dom;
}
?>
