

document.addEventListener("scroll",function(){

var h=document.documentElement;

var scrolled=(h.scrollTop)/(h.scrollHeight-h.clientHeight)*100;

var bar=document.getElementById("readingBar");

if(bar) bar.style.width=scrolled+"%";

});

