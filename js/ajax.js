 jQuery(document).ready(function(){
 var intv;
 
 function clock() {
 if (jQuery("#clock").text()==1) {
 clearInterval(intv);
 jQuery("#stage").html('');
 }
 else 
 jQuery("#clock").text(jQuery("#clock").text()-1);
 }
if (status=="2") {
	 jQuery("#step1").hide();
		 jQuery("#step2").show();
}
jQuery("#driver2").click(function(event){
 jQuery("#step1").show();
		 jQuery("#step2").hide();
});
      jQuery("#driver").click(function(event){
        clearInterval(intv);
		  
		  jQuery.post(
	
	MyAjax.ajaxurl,
	{
		action : 'myajax-submit',
 
		 phne: jQuery("input#telephone").val(),
		 vtype: jQuery("input#vtype:checked").val(),
	},
	function( response ) {
	
		if (response.error>0) {
		if (response.error=="96") {
		intv=setInterval(function(){clock()},1000);
		}
		jQuery("#stage").html(response.errormsg);
	}
	else {
		 jQuery("#step1").hide();
		 jQuery("#step2").show();
		 jQuery("#stage").html(response.resultmsg);
	
	}
	}
);
		
    
});
});