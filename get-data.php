<?php

/*
2020, mortzu <mortzu@gmx.de>.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of
  conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice, this list
  of conditions and the following disclaimer in the documentation and/or other materials
  provided with the distribution.

* The names of its contributors may not be used to endorse or promote products derived
  from this software without specific prior written permission.

* Feel free to send Club Mate to support the work.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
*/

if (!file_exists(__DIR__ . '/config.php')) {
  error_log('config.php is missing');
  exit(1);
}

$cache_dir = __DIR__ . '/cache';

$nina_region = '';
$nina_urls = array();

$xmpp_username = '';
$xmpp_server = '';
$xmpp_password = '';
$xmpp_apiurl = '';

require_once __DIR__ . '/config.php';

$today = date('Y-m-d');
$cached = array();

date_default_timezone_set('Europe/Berlin');

function br2nl($string) {
  return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function send_xmpp($xmpp_apiurl, $xmpp_username, $xmpp_server, $xmpp_password, $target_jid, $text) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "{$xmpp_apiurl}/{$target_jid}");
  curl_setopt($ch, CURLOPT_HEADER, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain', "Host: {$xmpp_server}"));
  curl_setopt($ch, CURLOPT_USERPWD, "{$xmpp_username}@{$xmpp_server}:{$xmpp_password}");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $text);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_exec($ch);
  curl_close($ch);
}

if (file_exists($cache_dir . '/' . $today)) {
  $cached = file($cache_dir . '/' . $today, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

foreach ($nina_urls as $nina_url) {
  if (false === $data = file_get_contents($nina_url))
    continue;

  if (NULL === $data_json = json_decode($data, true))
    continue;

  foreach ($data_json as $info) {
    if (preg_match('/' . $nina_region . '/', $info['info'][0]['area'][0]['areaDesc']) &&
       ($info['status'] == 'Actual') &&
       (date('Y-m-d', strtotime($info['sent'])) == $today)) {
      $text = strip_tags(br2nl($info['info'][0]['headline'])) . "\n" . strip_tags(br2nl($info['info'][0]['description']));

      if (!in_array(md5($text), $cached)) {
        file_put_contents($cache_dir . '/' . $today, md5($text), FILE_APPEND | LOCK_EX);
        send_xmpp($xmpp_apiurl, $xmpp_username, $xmpp_server, $xmpp_password, $xmpp_receiver, $text);
      }
    }
  }
}

?>
