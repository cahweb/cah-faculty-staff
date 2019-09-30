<?php 
/*
 * Template Name: NSCM Faculty and Staff Page Template
 * Description: Faculty and Staff Page Template for NSCM Website
 */
?>

<?php get_header(); ?>

<div class="container mb-5 mt-3 mt-lg-5" style="min-height:250px;">

<div class="container">
<div class="row">
	<div class="col-md-3" id="dept-menu-div">
	    
        
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle" type="button" id="sub_dept" data-toggle="dropdown" auto-close="disabled">
            Filter
          </button>
          <div class="dropdown-menu" >
            <a class="dropdown-item" id="0">A-Z List</a>
            <a class="dropdown-item" id="1">Administration</a>
            <a class="dropdown-item" id="2">Advising</a>
            <a class="dropdown-item" id="57">Communication Dept.</a>
            <a class="dropdown-item" id="50">Games and Interactive Media</a>
            <a class="dropdown-item" id="58">FIEA</a>
            <a class="dropdown-item" id="48">Film and Mass Media</a>
          </div>
		</div>
    </div>
    
    <div class="col-md-9" id="cah-faculty-staff">
    	
    </div>

</div>
</div>

</div>
<?php get_footer(); ?>

<script type="application/javascript">

//get URL parameter
var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

var uid=0;
uid=getUrlParameter('id');

if(!uid)
$("#sub_dept").dropdown('toggle');


$(document).ready(function(e) {
	
	$('.dropdown-menu a:first-child').addClass('active');

	$("#cah-faculty-staff").load('<?php echo __DIR__; ?>' +'/includes/print-faculty.php?id='+uid, function(result){
		if(uid)
		$(this).html(result);
		else			
		$(this).html("<h2 class='pl-2 mb-4 heading-underline'>A-Z List</h2>"+result);
		});
			
});

$('.dropdown-menu a').click(function(e) {
		$('.dropdown-menu a').removeClass('active');
		$(this).addClass('active');
		var text_clicked = $(this).text();
		e.stopPropagation();

		$.ajax({
			url: '<?php echo __DIR__; ?>'+ '/includes/print-faculty.php', 
			data: {sub_dept: $(this).attr('id'), id: uid},
			type: 'post',
			success: function(result){
        	$("#cah-faculty-staff").html("<h2 class='pl-2 mb-4 heading-underline'>"+text_clicked+"</h2>"+result);
			}
		});

});


</script>