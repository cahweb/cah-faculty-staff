<?php

function format_phone_us($phone) {
  // note: making sure we have something
  if(!isset($phone{3})) { return ''; }
  // note: strip out everything but numbers 
  $phone = preg_replace("/[^0-9]/", "", $phone);
  $length = strlen($phone);
  switch($length) {
  case 7:
    return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
  break;
  case 10:
   return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
  break;
  case 11:
  return preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2) $3-$4", $phone);
  break;
  default:
    return $phone;
  break;
  }
}

/**
 * get_semester
 *
 * This function guesses a term based on the current month. It is used to provide a default for term-related data such as courses
 *
 * @return (string) (term)
 */


function get_semester() {
	$now = getdate();
	$term = "";
	
	switch ($now['mon']) {
		case 10:
		case 11:
		case 12:
			$term = "Spring " . ($now['year']+1);
			break;
		case 1:
		case 2:		
			$term = "Spring " . $now['year'];
			break;
		case 3:
		case 4:
		case 5:
		case 6:
			$term = "Summer " . $now['year'];
			break;
		default:
			$term = "Fall " . $now['year'];
			break;
		}
	return $term;
}

function sql_termselect($fiter_year = 0) {
	$year = date("Y");
	$ts1 = strtotime(date('d.m.') . $year);
	$ts2 = strtotime("05.03." . $year);

	if($ts1 > $ts2)
	$sql = "select distinct  term,term,CAST(SUBSTRING(term,LOCATE(' ',term)) AS UNSIGNED)+CAST(IF(SUBSTRING_INDEX(term, ' ', 1)='Fall',1,0) AS UNSIGNED) as ordering from courses where term != CONCAT('Summer ',(YEAR(NOW())+1)) order by ordering desc, term desc LIMIT 0, 5";
	
	else
	//HIDE Summer , Fall & Spring next year
	$sql = "select distinct  term,term,CAST(SUBSTRING(term,LOCATE(' ',term)) AS UNSIGNED)+CAST(IF(SUBSTRING_INDEX(term, ' ', 1)='Fall',1,0) AS UNSIGNED) as ordering from courses where term != CONCAT('Summer ',(YEAR(NOW())+1)) AND term != CONCAT('Summer ',YEAR(NOW())) AND term != CONCAT('Fall ',YEAR(NOW())) AND term != CONCAT('Spring ',(YEAR(NOW())+1)) order by ordering desc, term desc";
	return $sql;
}

function print_courses($id = 0, $term = "", $career = "",$catalogref_filter_any = "",$catalogref_filter_none = "", $prefix_filter_any = "") {
	$sqlaux = "";
	$sqlterm = "";
	$term = trim($term);
	$id = intval($id);
	$career = trim($career);
	$summer_flag = false;
	$guessterm = "";
	$terms = array();
	$termcourses = array();
	$termlabels = array();
	$courses_info=array();
	
		
	if (empty($id)) return;

	if (empty($term)) {
		$sql = sql_termselect();
		$result = mysqli_query(get_dbconnection(),$sql);
		while ($row = mysqli_fetch_assoc($result)) {
			if ($row['term'] != "-") {
				$terms[] = $row['term'];
				if (empty($sqlterm)) {
					$sqlterm = "term in (";
				}
				else {
					$sqlterm .= ",";	
				}
				$sqlterm .= "'" . $row['term'] . "'";
			}
		}
		if (!empty($sqlterm)) $sqlterm .= ") ";
		$guessterm = get_semester();
	}
	else {
		$terms[] = $term;
		$guessterm = $term;
		$sqlterm = "term='" . mysqli_real_escape_string(get_dbconnection(),$term) . "'";
	}
	// check any filters
	if (!empty($course_filter_any)) {
		if (is_array($course_filter_any)) {
			$filters = $course_filter_any;
		}
		else {
			$filters = explode(",",$catalogref_filter_any);
		}
		$sqlfilter1 = "";
		foreach ($filters as $value) {
			if (!empty($sqlfilter1)) $sqlfilter1 .= " , ";
			if (empty($sqlfilter1)) $sqlfilter1 = "CONCAT(prefix,catalog_number) IN (";
			$sqlfilter1 .= "'" . strtoupper($value) . "'";
		}
		if (!empty($sqlfilter1)) {
			$sqlfilter1 .= ") ";
			$sqlaux .= " and " . $sqlfilter1;
		}
	}
	// check none filters
	if (!empty($catalogref_filter_none)) {
		if (is_array($catalogref_filter_none)) {
			$filters = $catalogref_filter_none;
		}
		else {
			$filters = explode(",",$catalogref_filter_none);
		}
		$sqlfilter1 = "";
		
		foreach ($filters as $value) {
			if (!empty($sqlfilter1)) $sqlfilter1 .= " , ";
			if (empty($sqlfilter1)) $sqlfilter1 = "CONCAT(prefix,catalog_number) NOT IN (";
			$sqlfilter1 .= "'" . strtoupper($value) . "'";
		}
		if (!empty($sqlfilter1)) {
			$sqlfilter1 .= ") ";
			$sqlaux .=  " and " . $sqlfilter1;
		}
	}
	// check prefix filters
	if (!empty($prefix_filter_any)) {
		if (is_array($prefix_filter_any)) {
			$filters = $prefix_filter_any;
		}
		else {
			$filters = explode(",",$prefix_filter_any);
		}
		$sqlfilter1 = "";
		foreach ($filters as $value) {
			if (!empty($sqlfilter1)) $sqlfilter1 .= " , ";
			if (empty($sqlfilter1)) $sqlfilter1 = "prefix IN (";
			$sqlfilter1 .= "'" . strtoupper($value) . "'";
		}
		if (!empty($sqlfilter1)) {
			$sqlfilter1 .= ") ";
			$sqlaux .=  " and " . $sqlfilter1;
		}
	}
	
	// setup course list
	$sql = "select courses.id, number, IF(ISNULL(description),\"No Description Available\",description) as description, CONCAT(prefix,catalog_number) as catalogref,syllabus_file,term,section, title, instruction_mode, session, CONCAT(meeting_days,' ',class_start,' - ',class_end) as dateandtime from courses left join users on (courses.user_id = users.id) where " . $sqlterm . $sqlaux . " and (user_id=" . $id . " or suser_id=" . $id . ")";
	if (!strcasecmp($career,'UGRD')) {
		$sql .= " and career='UGRD'";
	}
	else if (!strcasecmp($career,'GRAD')) {
		$sql .= " and career='GRAD'";
	}
	$sql .=  " order by term, catalogref, title, number";
	
	
	$result = mysqli_query(get_dbconnection(),$sql);
	check_result($result,$sql);
	$num_results = mysqli_num_rows($result);
	if ($num_results == 0) return "";
	/* build output based on rows */
	
	while ($row = mysqli_fetch_assoc($result)) {
		$termindex = trim($row['term']);
			
		if (stripos($termindex,"summer") !== false) {
			$summer_flag = true;
		}

		if (empty($termcourses[$termindex])){
		$termcourses[$termindex] = "<table class='table table-condensed table-bordered table-striped volumes' cellspacing='0' title='{ $termindex}Offered Courses'>";
		$termcourses[$termindex] .= "<thead><tr><th>Course Number</th><th>Course</th><th>Title</th><th>Mode</th>";
			if ($summer_flag)
				$termcourses[$termindex] .= "<th>Session</th>";
			$termcourses[$termindex] .= "<th>Date and Time</th><th>Syllabus</th></tr></thead><tbody>";
		}//end of empty check
		
		//if not empty
		$termcourses[$termindex] .= "<tr><td>".$row['number']."</td>";
		$termcourses[$termindex] .= "<td>".trim($row['catalogref'])."</td>";
		$termcourses[$termindex] .= "<td>".trim($row['title'])."</td>";
		$termcourses[$termindex] .= "<td>".trim($row['instruction_mode'])."</td>";
		if ($summer_flag) {
			$termcourses[$termindex] .= "<td>".trim($row['session'])."</td>";
		}
		$termcourses[$termindex] .= "<td>".trim($row['dateandtime']) . "</td>";
		$termcourses[$termindex] .=  "<td>";		
		
		//syllabus
		if (!empty($row["syllabus_file"])) {
			$syllbusURL = "//www.cah.ucf.edu/common/files/syllabi/" . str_replace(" ","",$row['catalogref'] . $row['section'] . $row['term'] . ".pdf");
			$termcourses[$termindex] .=  "<a href=\"" . $syllbusURL . "\" rel=\"external\">Available</a>";
		}
		else {
			$termcourses[$termindex] .=  "Unavailable";
		}
		$termcourses[$termindex] .=  "</td>";		
		$termcourses[$termindex] .=  "</tr>";
		$termcourses[$termindex] .=  "<tr><td colspan=\"" .(($summer_flag) ? "7":"6") . "\">" . $row['description'] . "</td></tr>";
	}
	
	
	if(!isset($outHTML)) $outHTML ="";
	
$outHTML .="<div style='width:100%'><ul class='nav nav-tabs' id='courseTab' role='tablist'>";

	$termlabels = str_replace(" ","",$terms);
	for ($c = 0; $c < count($terms); $c++) {
		
		$outHTML .="<li class='nav-item'>";
		$outHTML .="<a class='nav-link " . ((!strcmp($guessterm,$terms[$c])) ? "active" : "") . "' data-toggle='tab' href='#{$termlabels[$c]}' role='tab' aria-controls='{$termlabels[$c]}'>{$terms[$c]}</a>";
		$outHTML .="</li>";
		
	}

	$outHTML .= "</ul></div><div class='tab-content'>";
	
	/* physical order of the content matters so iterate the same way */
	for ($c = 0; $c < count($terms); $c++) {	
	
		if (!empty($termcourses[$terms[$c]])) {
			$outHTML .= "<div class='pt-3 tab-pane " . ((!strcmp($guessterm,$terms[$c])) ? "active" : "") . "' id='{$termlabels[$c]}' role='tabpanel'>" . $termcourses[$terms[$c]] . "</div>";
			$outHTML .="</tbody></table></div>";
		}
		
		else {
			$outHTML .="<div class='pt-3 tab-pane " . ((!strcmp($guessterm,$terms[$c])) ? "active" : "") . "' id='{$termlabels[$c]}' role='tabpanel'><p>No courses found for {$terms[$c]}</p></div>";
		}
		
	}
	do_dbcleanup();
	return $outHTML;
}

function staff_location($roomid){
	$sql = "select room_number, buildings.short_description,building_number from rooms left join buildings on (building_id=buildings.id) where rooms.id=".$roomid;
	$result = mysqli_query(get_dbconnection(),$sql);
	check_result($result,$sql);
	$row = mysqli_fetch_assoc($result);
	mysqli_free_result($result);
	return $row;
}

function staff_publication($userid,$approved=true){
	$sql = "select publications.id,photo_path,forthcoming,DATE_FORMAT(publish_date,'%M %Y') as pubdate, citation, plural_description as pubtype from publications left join publications_categories on (publication_id=publications_categories.id) where user_id=" .$userid. " and approved= " .$approved . " order by level, pubtype, publish_date desc,citation";
	$result = mysqli_query(get_dbconnection(),$sql);
	if(check_result($result,$sql))
	return $result;
	else return false;
}

function staff_education($userid){
	$sql = "SELECT * from education left join degrees on degrees_id = degrees.id where user_id = {$userid}  order by year desc";
	$result = mysqli_query(get_dbconnection(),$sql);
	if(check_result($result,$sql))
	return $result;
	else return false;
}

function NSCM_staff($dept=37, $sub_dept=0, $user_id=0){
$sql = "SELECT lname, users.id as id, users.phone, photo_path, photo_extra, users.email, users.location, users.room_id, users.office,interests, users.activities, awards, duties, research, has_cv, homepage, biography,  REPLACE(CONCAT_WS(' ',fname,mname,lname),' ',' ') as fullname, titles.description as title,  departments_sub.description, titles.title_group, prog_title_dept as title_dept, prog_title_dept_short as title_dept_short
FROM users, titles, departments, users_departments left join departments_sub on users_departments.subdepartment_id = departments_sub.id
where users_departments.department_id = {$dept} 
AND users.active=1 and users.show_web=1 
AND users.id=users_departments.user_id 
AND titles.id = users_departments.title_id
AND departments.id = users_departments.department_id";


if($sub_dept!==0 && $sub_dept!=1 && $sub_dept!=2)
$sql .=" AND subdepartment_id={$sub_dept}";

if($sub_dept===1)//administration
$sql .=" AND title_group in ('Administrative Faculty')"; 

if($sub_dept===2)//staff
$sql .=" AND title_id in (67,84,121,85,53)";

if($user_id)
$sql.=" AND users.id = {$user_id} LIMIT 1 ";

if($user_id==0)
$sql .=" order by users.lname";

if($sub_dept===1)
$sql = "select * from (" . $sql . ") as NSCM_Admin order by 
		(case 
			when title = 'Director' then 0
			when title !='Director' then 1
		end)";

	$result = mysqli_query(get_dbconnection(),$sql);
	if(check_result($result,$sql))
	return $result;
	else return false;
}


function print_staff($result,$format=0){
	$outputHTML = "";
	$i = 0;
	
	$outputHTML = "<div class='row'>";
	
	
	while($row = mysqli_fetch_assoc($result)){
		
	$outputHTML .= "<div class='col-lg-6 col-md-12'>";
	
		$outputHTML .= "<div class='cah-staff-list'><a href='//projects.cah.ucf.edu/athena_test/faculty-and-staff/?id={$row['id']}'><div class='staff-list'>";
		
		//starting media if not A-Z list
		if($format!=0){
			$outputHTML .="<div class='media'>";
  			
			if(!empty($row['photo_path']))
			    $outputHTML .="<img class='img-circle mr-3' src='//www.cah.ucf.edu/common/resize.php?filename={$row['photo_path']}{$row['photo_extra']}&sz=5' alt='{$row['fullname']}'><div class='media-body'>";
			else
				$outputHTML .="<img class='d-flex mr-3 img-circle' src='//www.cah.ucf.edu/common/resize.php?filename=446.jpg&sz=5' alt='{$row['fullname']}'><div class='media-body'>";
				
		}

		//if A-Z format
		$outputHTML .="<strong>{$row['fullname']}</strong><br>";
		if($format!=0)
			{
			$outputHTML .='<div class="fs-list">';	
			if(!empty($row['title_dept_short'])){
				if($row['title_dept_short']=="Director")
				$outputHTML .= "<span class='fa fa-star mr-1 text-primary' aria-hidden='true'></span>";
				$outputHTML .="<em>{$row['title_dept_short']}</em><br>";
				}
			else{
				if($row['title']=='Director')
				$outputHTML .= "<span class='fa fa-star mr-1 text-primary' aria-hidden='true'></span>";
				$outputHTML .="<em>{$row['title']}</em><br>";
				}
			$outputHTML .="{$row['email']}<br>";
			
			//Research Interests
			if (!empty($row['interests']) || !empty($row['prog_interests']))
			{
			
				 if(!empty($row['interests']))
				 $interests = html_entity_decode($row['interests'],ENT_QUOTES,"utf-8");
                 if(!empty($row['prog_interests'])) $interests = html_entity_decode($row['prog_interests'],ENT_QUOTES,"utf-8");
				 
				 if ((stripos($interests,"<ul>") !== false) ) {
			$commainterests = "";
			libxml_use_internal_errors(true);
			try { $XMLdoc = new SimpleXMLElement("<body>" . $interests . "</body>"); } catch (Exception $e) { $XMLdoc = NULL; }
			if ($XMLdoc != NULL) {
				$XPresult = $XMLdoc->xpath('ul/li');
				while (list(,$node) = each($XPresult)) {
					if (!empty($commainterests)) $commainterests .= "; ";
					$commainterests .= trim($node);
				}
			}
			else {
				$interests = strip_tags($interests, "<li>");
				$arrayinterests = explode("<li>", $interests);
				foreach ($arrayinterests as &$value){
					$value = trim($value);
				}
				$commainterests = implode("; ", $arrayinterests);
				$commainterests = ltrim($commainterests, "; ");
			}
			$interests = $commainterests;
		}
		else {
			$interests = strip_tags($interests);
			$interests = str_ireplace("<p>","",$interests);
			$interests = str_ireplace("</p>","",$interests);
		}
		
			$outputHTML .="<span class='fs-interest'><em>Interests:</em> " . substr($interests, 0, 45) . "...</span><br>";
			
			}
			//End of Research Interests
			$outputHTML .='</div>';
			}
		else					
		$outputHTML .="{$row['email']}<br>";
		if($format==0)
		$outputHTML .=format_phone_us($row['phone']);
		//endif A-Z format

		//ending media
		if($format!=0){
			$outputHTML .="</div></div>";	
		}

		$outputHTML .= "</div></a></div>";
        
        
		$outputHTML .= "</div>"; //close column
		}
		
		$outputHTML .= "</div>"; //row

return $outputHTML;
}


function print_staff_up_down($result,$format=0){
	$outputHTML = "";
	$i = 0;
	
	$outputHTML = "<div class='row'>";
	$outputHTML .= "<div class='col-lg-6 col-md-12'>";
	$midpoint =  ceil($result->num_rows / 2) - 1; // split the list into two columns
	
	while($row = mysqli_fetch_assoc($result)){
		
		$outputHTML .= "<div class='cah-staff-list'><a href='?id={$row['id']}'><div class='staff-list'>";
		
		//starting media if not A-Z list
		if($format!=0){
			$outputHTML .="<div class='media'>";
  			
			if(!empty($row['photo_path']))
			    $outputHTML .="<img class='img-circle mr-3' src='//www.cah.ucf.edu/common/resize.php?filename={$row['photo_path']}{$row['photo_extra']}&sz=5' alt='{$row['fullname']}'><div class='media-body'>";
			else
				$outputHTML .="<img class='d-flex mr-3 img-circle' src='//www.cah.ucf.edu/common/resize.php?filename=446.jpg&sz=5' alt='{$row['fullname']}'><div class='media-body'>";
				
		}

		//if A-Z format
		$outputHTML .="<strong>{$row['fullname']}</strong><br>";
		if($format!=0)
			{
			$outputHTML .='<div class="fs-list">';	
			if(!empty($row['title_dept_short'])){
				if($row['title_dept_short']=="Director")
				$outputHTML .= "<span class='fa fa-star mr-1 text-primary' aria-hidden='true'></span>";
				$outputHTML .="<em>{$row['title_dept_short']}</em><br>";
				}
			else{
				if($row['title']=='Director')
				$outputHTML .= "<span class='fa fa-star mr-1 text-primary' aria-hidden='true'></span>";
				$outputHTML .="<em>{$row['title']}</em><br>";
				}
			$outputHTML .="{$row['email']}<br>";
			
			//Research Interests
			if (!empty($row['interests']) || !empty($row['prog_interests']))
			{
			
				 if(!empty($row['interests']))
				 $interests = html_entity_decode($row['interests'],ENT_QUOTES,"utf-8");
                 if(!empty($row['prog_interests'])) $interests = html_entity_decode($row['prog_interests'],ENT_QUOTES,"utf-8");
				 
				 if ((stripos($interests,"<ul>") !== false) ) {
			$commainterests = "";
			libxml_use_internal_errors(true);
			try { $XMLdoc = new SimpleXMLElement("<body>" . $interests . "</body>"); } catch (Exception $e) { $XMLdoc = NULL; }
			if ($XMLdoc != NULL) {
				$XPresult = $XMLdoc->xpath('ul/li');
				while (list(,$node) = each($XPresult)) {
					if (!empty($commainterests)) $commainterests .= "; ";
					$commainterests .= trim($node);
				}
			}
			else {
				$interests = strip_tags($interests, "<li>");
				$arrayinterests = explode("<li>", $interests);
				foreach ($arrayinterests as &$value){
					$value = trim($value);
				}
				$commainterests = implode("; ", $arrayinterests);
				$commainterests = ltrim($commainterests, "; ");
			}
			$interests = $commainterests;
		}
		else {
			$interests = strip_tags($interests);
			$interests = str_ireplace("<p>","",$interests);
			$interests = str_ireplace("</p>","",$interests);
		}
		
			$outputHTML .="<em>Research Interests:</em> " . substr($interests, 0, 65) . "...<br>";
			
			}
			//End of Research Interests
			$outputHTML .='</div>';
			}
		else					
		$outputHTML .="{$row['email']}<br>";
		if($format==0)
		$outputHTML .=format_phone_us($row['phone']);
		//endif A-Z format

		//ending media
		if($format!=0){
			$outputHTML .="</div></div>";	
		}

		$outputHTML .= "</div></a></div>";
        //up and down
		if ($i == $midpoint) {
           $outputHTML .= "</div><div class='col-lg-6 col-md-12'>";
        }
        $i++;
        }
		$outputHTML .= "</div>"; //close column
		$outputHTML .= "</div>"; //row

echo $outputHTML;
}


function detail_staff($result){
	$outputHTML = "";
	$row = mysqli_fetch_assoc($result);
	
	$outputHTML = "<div class='row'>";
	$outputHTML .= "<div class='media'>";
	
  			
			if(!empty($row['photo_path']))
			    $outputHTML .="<img class='mr-3 img-circle-detail' src='//www.cah.ucf.edu/common/resize.php?filename={$row['photo_path']}{$row['photo_extra']}&sz=2' alt='{$row['fullname']}'><div class='media-body'>";
			else
				$outputHTML .="<img class='d-flex pr-3 img-circle-detail' src='//www.cah.ucf.edu/common/resize.php?filename=446.jpg&sz=2' alt='{$row['fullname']}'><div class='media-body'>";
		
		
		$outputHTML .= "<h4>{$row['fullname']}</h4>";
		if(!empty($row['title_dept']))
			$outputHTML .= "<em><span class='small'>{$row['title_dept']}</span></em><br>";
			else
			$outputHTML .= "<em><span class='small'>{$row['title']}</span></em><br>";
		$outputHTML .= "<a href='mailto:{$row['email']}'>{$row['email']}</a><br>";
		if($row['phone'])
		$outputHTML .= format_phone_us($row['phone']) . "<br>";
		if($row['office'])
		$outputHTML .= "Office Hours: {$row['office']}<br>";
		
		//office loation
		if($row['room_id']){
			$roomid = intval($row['room_id']);
			$staff_location_row = staff_location($roomid);
			if (!empty($staff_location_row['building_number'])) {
				$outputHTML .= "Campus Location: <a href='http://map.ucf.edu/locations/{$staff_location_row['building_number']}' target='_blank'>";
			}
			$outputHTML .= $staff_location_row['short_description'] . $staff_location_row['room_number'];
			if (!empty($staff_location_row['building_number'])) {
				$outputHTML .= "</a>";
			}
			$outputHTML .= "<br>";
		}
		elseif($row['location'])
		$outputHTML .= "Campus Location: {$row['location']}<br>";
		
		//CV
		if (!empty($row['has_cv'])) {
			$outputHTML .= "<a href='http://www.cah.ucf.edu/common/files/cv/{$row['id']}.pdf'>View CV</a><br>";
		}
		else if (!empty($row['resume_path'])) {
			$external = "";
			if (stripos($row['homepage'],"ucf.edu") === false) $external = ' rel="external" ';
			$outputHTML .= "<a href=\"" . $row['resume_path'] . "\"" . $external . ">View CV</a><br>";
		}
	
	$outputHTML .= "</div></div>";//end of media
	
		if(!empty($row['biography']))
		$outputHTML .= "<div class='pt-2'>{$row['biography']}</div>";
		
		$result_edu = staff_education($row['id']);
		if($result_edu->num_rows>0){
		$outputHTML .= "<h3 class='heading-underline'>Education</h3>";
			$outputHTML .="<ul>";
			while($row_edu = mysqli_fetch_assoc($result_edu))
			{
				$outputHTML .= "<li>" . trim($row_edu['short_description']);
				if (!empty($row_edu['field'])) 
				$outputHTML .= " in " . trim($row_edu['field']);
				if (!empty($row_edu['institution']))
				$outputHTML .= " from " . trim($row_edu['institution']);
				if (!empty($row_edu['year'])) 
				$outputHTML .= " (" . $row_edu['year'] . ")";
		        $outputHTML .= "</li>";
			}
			$outputHTML .="</ul>";
		}
		
		if(!empty($row['interests'])){			
		$outputHTML .= "<h3 class='heading-underline'>Research Interests</h3>";
		$outputHTML .= "<p>" . html_entity_decode($row['interests'],ENT_QUOTES,"utf-8")."</p>";
		}
				
		if(!empty($row['research']))	{
		$outputHTML .= "<h3 class='heading-underline'>Recent Research Activities</h3>";
		$outputHTML .= "<p>" . html_entity_decode($row['research'],ENT_QUOTES,"utf-8")."</p>";
		}
		
		//publications
		$result_public = staff_publication($row['id']);$i=0;
		if($result_public->num_rows>0){
		$outputHTML .= "<h3 class='heading-underline'>Selected Publications</h3>";
		$public_type = "";
			
			while($row_public = mysqli_fetch_assoc($result_public))
			{
			  if($i!=0 && strcmp($public_type,$row_public['pubtype'])){
				$outputHTML .="</ul>";	
				}
				
				if(strcmp($public_type,$row_public['pubtype'])){
				$outputHTML .="<h4 class='pt-4'>{$row_public['pubtype']}</h4>";
				$outputHTML .="<ul>";	
				}
				//list of publications
				$outputHTML .= "<li>" . (($row_public['forthcoming']) ? "<em>Forthcoming</em> " : " ") . $row_public['publish_date'] . " " . html_entity_decode($row_public['citation'],ENT_QUOTES,"utf-8") . "</li>";
				//end of list
				$i++;
				$public_type=$row_public['pubtype'];
				
			}//end while 
			$outputHTML .="</ul>";
		}
		
				//couses
		$outHTMLCourses = print_courses($row['id']);
		
		if (!empty($outHTMLCourses)) {
			$outputHTML .= "<h3 class='heading-underline'>Courses</h3>" . $outHTMLCourses;
		}

		

	$outputHTML .= "</div>";//end of row
	
echo $outputHTML;
}
?>