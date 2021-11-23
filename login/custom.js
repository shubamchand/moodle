
function passReveal() {
 var cname = document.getElementById("hidepass").className; 
 if(cname === "icon fa fa-eye fa-fw "){
	 document.getElementById("hidepass").className = "icon fa fa-eye-slash fa-fw ";
 }
 else {
	 	 document.getElementById("hidepass").className = "icon fa fa-eye fa-fw ";
 }
  var x = document.getElementById("id_password");
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}

function cpassReveal() {
 var cname = document.getElementById("hidecpass").className; 
 if(cname === "icon fa fa-eye fa-fw "){
	 document.getElementById("hidecpass").className = "icon fa fa-eye-slash fa-fw ";
 }
 else {
	 	 document.getElementById("hidecpass").className = "icon fa fa-eye fa-fw ";
 }  var x = document.getElementById("id_cpassword");
  if (x.type === "password") {
    x.type = "text";
  } else {
    x.type = "password";
  }
}
