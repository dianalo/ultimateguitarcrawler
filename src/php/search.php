<?php
include "functions.php";

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
  $songcontent = getSongContent($res);

  $dom = injectIntoHTML("chordtext", $songcontent, null);

  $chords = extractChords($songcontent);
  $dom = injectIntoHTML("chordpics", generateChordView($chords), $dom);
  $dom = injectIntoHTML("capo", getCapoFret($res), $dom);

  $dom = injectIntoHTML("video", getVideoDOM(trim($songname)), $dom);

  echo $dom->saveHTML();
}
?>
