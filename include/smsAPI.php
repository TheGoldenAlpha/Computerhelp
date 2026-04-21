<?php
/* New version of sms.php
   This should be compatible to new PHP Versions >5.3
*/
function inwHttpPOST ($s_url, $s_postvars, $s_http_auth = '') {
  // INWHTTPPOST: Execute a HTTP(S)-POST request.
  // Input: s_url: Remote host name and path
  // s_postvars: POST payload as string
  // s_http_auth: Additional HTTP authorization header (username:password), default: none
  // Output: data: HTTP response payload.
  // error: Internal CURL error message.
  // status: HTTP response code.
  $h_result = array("data" => array(), "error" => "", "status" => 0);
  $ch = curl_init($s_url);
  curl_setopt($ch, CURLOPT_URL, $s_url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_FORBID_REUSE, true); // force the connection to explicitly close
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $s_postvars);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_HEADER, false); // do not return http headers
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the contents of the call
  if (strlen($s_http_auth) > 0) curl_setopt($ch, CURLOPT_USERPWD, $s_http_auth);
  $h_result['data'] = curl_exec($ch);
  $h_result['error'] = curl_error($ch);
  if(!curl_errno($ch)) {
    $h_result['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  }
  curl_close($ch);
return $h_result;
}
?>
