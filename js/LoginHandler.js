/*
 *  LoginHandler.js 2.0 12/08/09
 *
 * Javascript implementation for Login Process
 *
 * Copyright (c) 2008 Microswiss Computer Consultants. All Rights Reserved.
 *
 * Permission to use, copy, modify, and distribute this software
 * and its documentation for any purposes and without
 * fee is hereby granted provided that this copyright notice
 * appears in all copies.
 *
 * Of course, this soft is provided "as is" without express or implied
 * warranty of any kind.
 *
 */

function MoveToPswd(event)
{
  if (event && (event.which==13 || event.keyCode==13))
  {
    document.getElementById("pswd").focus();
    return false;
  }
  else
    return true;
}

function MoveToAut(event)
{
  if (event && (event.which==13 || event.keyCode==13))
    authorize();
  else
    return true;
}

function authorize(elem,msg)
{
var formname = document.getElementsByName(elem.name)[0].form.name;
var form = document.getElementsByName(formname);
var text = form[0].innerHTML;
var fields = [];

  input_ix = text.indexOf("<input");
  ix = 0;
  while (input_ix>0) {
    fld_ix = text.indexOf("name=",input_ix)+6;
    fld_len = text.indexOf('"',fld_ix)-fld_ix;
    fld_name = text.substr(fld_ix,fld_len);
    typ_ix = text.indexOf("type=",input_ix)+6;
    typ_len = text.indexOf('"',typ_ix)-typ_ix;
    typ_name = text.substr(typ_ix,typ_len);
    var field = [fld_name,typ_name,document.getElementsByName(fld_name)[0].value];
    fields[ix++] = field;
    input_ix = text.indexOf("<input",fld_ix+fld_len);
  }
  var tt = "";
  var pwn = "";
  var pw = "";
  var idn = "";
  var id = "";
  for (i=0;i<fields.length;i++) {
    if (fields[i][1]!="button") {
      if (fields[i][1]=="text") {
        idn = fields[i][0];
        id = fields[i][2];
      }
      if (fields[i][1]=="password") {
        pwn = fields[i][0];
        pw = fields[i][2];
      }
    }
  }
var tt = id.trim()+pw.trim();
  if (id.trim()!="" && pw.trim()!="")
  {
    var myDate = new Date();
    cryptKey  = MD5(MD5(myDate.formatDate("Y-m-d")));
    enc = MD5(MD5(base64_encode(tt)));
    enc = base64_encode(cryptKey+enc);
    document.getElementsByName(pwn)[0].value=enc;
    form[0].submit(); //reload but now with user and passwd
  }
  else
  {
    alert(msg);
    if (id.trim()=="") 
      document.getElementsByName(idn).focus();
    else
      document.getElementsByName(idn).focus();
  }
}

function CheckPassword(val)
{
 minLen=8;
 minAZ=1;
 minDigits=1;
 UpperandLower=1;    //true=1 false=0
 numPunc=0;

 if (val.length<minLen) return false;
 if (minAZ && !(/[a-z]/i.test(val) || val.match(/[A-Z]/gi).length < minAZ)) return false;
 if (minDigits && !(/\d/.test(val) || val.match(/\d/g).length < minDigits)) return false;
 if (UpperandLower && (!/[a-z]/g.test(val) || !/[A-Z]/g.test(val))) return false;
 if (numPunc && (!/[\.\'\;\,\!\"\:\?\_]/.test(val) || (val.match(/[\.\'\;\,\!\"\:\?\_]/g).length < numPunc))) return false;

 return true;
}

function change_it(fld_uid,fld_old,fld_new,fld_rep,msg1,msg2)
{
var formname = document.forms[0].name;
var form = document.getElementsByName(formname);
var uid = document.getElementsByName(fld_uid)[0].value;
var altpwd = document.getElementsByName(fld_old)[0].value;
var newpwd = document.getElementsByName(fld_new)[0].value;
var reppwd = document.getElementsByName(fld_rep)[0].value;
if (altpwd.trim()=="" || newpwd.trim()=="" || reppwd.trim()=="")
  alert(msg1);
else if (newpwd.trim()!=reppwd.trim())
  alert(msg2);
else {
  var myDate = new Date();
  tt = uid.trim() + altpwd.trim();
  cryptKey  = MD5(MD5(myDate.formatDate("Y-m-d")));
  enc = MD5(MD5(tt));
  enc = base64_encode(cryptKey+enc);
  document.getElementsByName(fld_old)[0].value=enc;
  tt = uid.trim() + newpwd.trim();
  enc = MD5(MD5(tt));
  document.getElementsByName(fld_new)[0].value=enc;
  document.getElementsByName(fld_rep)[0].value=enc;
  form[0].submit(); //reload but now with user and passwd
  }
}