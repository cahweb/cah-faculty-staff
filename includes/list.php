<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db-config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

print_r($_POST);

$sub_dept =0;
if(isset($_POST['sub_dept'])){
	$sub_dept = intval($_POST['sub_dept']);
	$result = NSCM_staff(37,$sub_dept);
}
else
$result = NSCM_staff();
 
if($sub_dept==0)
	print_staff($result);
else
	print_staff($result,$sub_dept);


?>


<script>
$('div.faculty').click(function(e) {
	
		$.ajax({
			url: '/var/www/projects/athena_test/wp-content/plugins/cah-faculty-staff/includes/list.php', 
			data: {id: $(this).attr('id')},
			type: 'post',
			success: function(result){
        	$("#cah-faculty-staff").html(result);
			}
		});

});

</script>