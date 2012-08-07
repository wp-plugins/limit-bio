<?php 
$character_count = $_GET["char"];
echo $character_count;
?>

jQuery(document).ready(function() {
	var character_count = "<?php echo $character_count ?>";
	jQuery("#description").charCount({
		allowed: character_count,		
		warning: 25,
		counterText: 'Characters left: '	
	});
	jQuery("#description").keyup(function(){
		   var count = this.value.length;
		   if (count > character_count) {

		    this.value = this.value.substr(0,character_count);

		   } 
	});

});


