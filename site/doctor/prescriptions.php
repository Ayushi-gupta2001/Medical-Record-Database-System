<?php
	if(isset($_POST['email'])&&isset($_POST['num_row']))
	{
		include '../config.php';

		$db = pg_connect("$host $port $dbname $user $password");

		if(!$db){
		echo pg_last_error();
		} 
		else {

		$email= $_POST['email'];
		$numr = $_POST['num_row'];
		$search_query= $_POST['query'];

		// echo "Opened database successfully\n\n\n";
		if(strcmp($search_query,"")==0)
			$query = "SELECT * FROM Prescription ORDER BY time_stamp DESC;";
		else
			$query = "SELECT * FROM Prescription WHERE id_pat LIKE '%$search_query%' OR 
								id_pha LIKE '%$search_query%' OR id_doc LIKE '%$search_query%' ORDER BY time_stamp DESC;";
		
		$result = pg_query($db,$query);

		$arr = pg_fetch_all($result);
		
		//print_r($arr);
		$num_rows = pg_num_rows($result);
		
		//echo $num_rows;

		$body = "<div class=\"panel panel-default\">
				   <div class=\"panel-heading\" role=\"tab\" id=\"heading%d\">
				      <h4 class=\"panel-title\">
				         <a style='text-decoration: none;' %s data-toggle=\"collapse\" href=\"#collapse%d\" aria-expanded=\"%s\" aria-controls=\"collapse%d\">
				         Prescription #%d
				         </a>
				      </h4>
				   </div>
				   <div id=\"collapse%d\" class=\"panel-collapse %s\" role=\"tabpanel\" aria-labelledby=\"heading%d\">
				    <div class=\"panel-body\">
				    <div class='row'>
				    	<div class='col-md-8'>
				    		<table class='table text-left table-striped'>
							<tr><td class='col-md-4'><b>Patient:</b></td>
								<td class='col-md-4'>%s</td></tr>
							<tr><td class='col-md-4'><b>Doctor:</b></td>
								<td class='col-md-4'>%s</td></tr>
							<tr><td class='col-md-4'><b>Pharmacist:</b></td>
								<td class='col-md-4'>%s</td></tr>
							<tr><td class='col-md-4'><b>Date:</b></td>
								<td class='col-md-4'>%s</td></tr>
							<tr><td class='col-md-4'><b>Description:</b></td>
								<td class='col-md-4'>%s</td></tr>
							<tr><td class='col-md-4'><b>Attachments:</b></td>
								<td class='col-md-4'><ul class='list-inline'>%s</ul></td></tr>
							<tr><td class='col-md-4'><b>Suggested Medicine:</b></td>
							<td><ul class='text-left'>%s</ul></td></tr>
							</table>
						</div>
						<div class='col-md-4'>
						<form action='upload.php' method='post' enctype='multipart/form-data'>
							<div class='row'>
								<input type='hidden' name='id_doc' value='%s'/>
								<input type='hidden' name='id_pha' value='%s'/>
								<input type='hidden' name='id_pat' value='%s'/>
								<input type='hidden' name='timestamp' value='%s'/>
								<span class='btn btn-default btn-file'>Browse
									<input type='file' name='file' id='file'/>
								</span>
								<button type='submit' class='btn btn-primary'>Upload Certificate</button><br><br>
								<span id='filelabel'>No file selected.</span>
							</div>
							<br>
							<div class='row'><b>Certificate:</b>&nbsp;&nbsp<span>%s</span></div>
							</div>
						</form>
						</div>
					</div>
				    </div>
				   </div>
				</div>";

		$text="";
		$format = 'd-m-Y';

		for($row=0;$row<$numr&&$row<$num_rows;$row++)
		{
			$id_pat = $arr[$row]['id_pat'];
			$id_doc = $arr[$row]['id_doc'];
			$id_pha = $arr[$row]['id_pha'];

			$query = "SELECT name FROM Patient WHERE id_pat = '$id_pat'";
	  		$res = pg_query($db,$query);
	  		$res = pg_fetch_row($res);
	  		$username = $res[0];

	  		$query = "SELECT name FROM Doctor WHERE id_doc = '$id_doc'";
	  		$res = pg_query($db,$query);
	  		$res = pg_fetch_row($res);
	  		$doc = $res[0];

	  		$query = "SELECT name FROM Pharmacist WHERE id_pha = '$id_pha'";
	  		$res = pg_query($db,$query);
	  		$res = pg_fetch_row($res);
	  		$pha = $res[0];

	  		$timestamp = $arr[$row]['time_stamp'];
	  		$description = $arr[$row]['description'];

			if($arr[$row]['medical_cert']!=null)
			{
				$time = explode(" ",$timestamp);
				$base64 = "./show.php?pat=$id_pat&doc=$id_doc&pha=$id_pha&date=$time[0]&time=$time[1]&type=cert&indx=0";
				// $base64 = 'data:image/jpeg;base64,' . base64_encode(pg_unescape_bytea($arr[$row]['medical_cert']));
				$base64="<a id='medcert' target='blank' href=$base64>Get Certificate</a>";
			}
			else
				$base64="None";

			$query = "SELECT test_result FROM Test_result WHERE 
							id_pat = '$id_pat' and id_doc = '$id_doc' and id_pha = '$id_pha'
							and time_stamp='$timestamp'";
	  		
	  		$res = pg_query($db,$query);
	  		$numrows = pg_num_rows($res);
	  		$attach="";

	  		if($numrows==0)
	  		{
	  			$attach="None";
	  		}

	  		for($r=0;$r<$numrows;$r++)
	  		{
	  			$time = explode(" ",$timestamp);
				$att = "./show.php?pat=$id_pat&doc=$id_doc&pha=$id_pha&date=$time[0]&time=$time[1]&type=attc&indx=$r";
	  			$attach=$attach."<li><a target='blank' href=$att>".($r+1)."</a></li>";
	  		}

	  		$query = "SELECT name,dose,quantity FROM Suggested_med WHERE id_pha = '$id_pha' AND id_doc = '$id_doc' AND id_pha = '$id_pha' 
	  														AND time_stamp='$timestamp'";
			//echo $query;
	  		$res = pg_query($db,$query);
	  		$numrows = pg_num_rows($res);
	  		$res = pg_fetch_all($res);
			
			$med_text="";

			if($numrows==0)
	  		{
	  			$med_text="None";
	  		}

			for($i=0;$i<$numrows;$i++)
			{
				$med_text=$med_text."<li>".$res[$i]['name']." (".$res[$i]['dose'].") - ".$res[$i]['quantity']."</li>";
			}
			
			// $med_text= $med_text."*";

			// if($row==0)
			// {
		  		$text = $text.sprintf($body,$row+1,'class="btn-block"',$row+1,'true',$row+1,$row+1,
		  			$row+1,'in',$row+1,$username,$doc,$pha,date($format, strtotime($timestamp)),
		  			$description,$attach,$med_text,$id_doc,$id_pha,$id_pat,$timestamp,$base64);
		  	// }
		 //  	else
		 //  	{
		 //  		$text = $text.sprintf($body,$row+1,'class="btn-block"',$row+1,'true',$row+1,
		 //  			$row+1,$row+1,'in',$row+1,$username,$doc,$pha,date($format, strtotime($timestamp)),
		 //  			$description,$attach,$med_text,$id_doc,$id_pha,$id_pat,$timestamp,$base64);
			// }
			
			//echo $id_pha.$id_pat.$id_doc;
			
			
		}
		//echo $med_text;
		echo $text;
		}

		pg_close($db);
	}
	else
	{
		echo "email not set";
		// function to Reset Session variable 
		header('Location: index.php');
	}
?>
