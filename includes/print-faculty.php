<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'db-config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';

$sub_dept =0; $id=0;

if(isset($_POST['sub_dept'])){
	if(!is_numeric($_POST['sub_dept']))
	$sub_dept = 0;
	else
	$sub_dept = intval($_POST['sub_dept']);
	$result = NSCM_staff(37,$sub_dept);
}
elseif(isset($_GET['id'])){
	if(!is_numeric($_GET['id']))
	$id = 0;
	else
	$id = intval($_GET['id']);
	$result = NSCM_staff(37,0,$id);
}
elseif(isset($_POST['id'])){
	if(!is_numeric($_POST['id']))
	$id = 0;
    else
    $id = intval($_POST['id']);
	$result = NSCM_staff(37,0,$id);
}
else
	$result = "<h2>{$_POST['sub_dept_name']}</h2>" . NSCM_staff();


if($sub_dept==0 && $id==0)
	echo print_staff($result);
if($sub_dept!=0)
	echo print_staff($result,1);
if($id!=0)
	detail_staff($result);


?>

<script>
/*$('div.faculty').click(function(e) {
	
		$.ajax({
			url: '/var/www/projects/athena_test/wp-content/plugins/cah-faculty-staff/includes/print-faculty.php', 
			data: {id: $(this).attr('id')},
			type: 'post',
			success: function(result){
        	$("#cah-faculty-staff").html(result);
			}
		});

});*/

  $(function () {
    $('#courseTab a:first').tab('show')
	$('#courseTab a:first').parent().addClass('pl-5')
  })

</script>