
document.addEventListener("DOMContentLoaded",function(){
 const area=document.querySelector("textarea[name=source]");
 if(!area) return;
 area.addEventListener("input",()=>{
   console.log("Text length:",area.value.length);
 });
});
