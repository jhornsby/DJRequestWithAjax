<?php
// this can probably be removed when finished
$error='';

$introduceDJ = '';
include_once '../configuration.php';
include_once '../functions/functions.php';

start_session();
$bypass = 0;
if (isset($_COOKIE['name'])) {
$name = preg_replace("/[^A-Za-z0-9 ]/", "", $_COOKIE['name']);
}
error_log("requests.php read name of ".$name." from cookie");
// check user is logged in
if (!(isset($_COOKIE['eventkey']) && $_COOKIE['eventkey'] != ''))
{
  header("Location: index.php");
}

if (isset($_SESSION['timeout'])){
    if ($_SESSION['timeout'] + $session_timeout * 60 < time()) {
        if(session_destroy()) // Destroying All Sessions
        {
            header("Location: timedout.php"); // Redirecting To Timed-Out Page
        }
    }
}

// dont' trust cookies
$key = makeSafe($_COOKIE['eventkey']);

$uniqueid = uniqid();
// If cookie hasn't been set, set it and put this user in the requestuser table
if (!isset($_COOKIE['guestuser'])) {
    setcookie("guestuser", $uniqueid, time() + (60 * 60 * 8), "/"); // 60 * 60 * 8 seconds = 8 hours
    setcookie("eventkey", $key, time() + (60 * 60 * 8), "/"); // 60 * 60 * 8 seconds = 8 hours
    $ip_addr = $_SERVER['REMOTE_ADDR'];
    $conn = mysqli_connect($host, $username, $password, $db);
    $query = mysqli_query($conn, "INSERT INTO guestusers (uniqueid, ipaddr, thekey, createdTime) VALUES ('$uniqueid', '$ip_addr', '$key', NOW())");
    // Delete users older than $maxUserAge days old
//    $query = mysqli_query($conn, "DELETE FROM guestusers WHERE createdTime < NOW - INTERVAL '$maxUserAge' DAY");
    // Delete requests older than $maxUserAge days old
//    $query = mysqli_query($conn, "DELETE FROM requests WHERE timedate < NOW - INTERVAL '$maxUserAge' DAY");
    mysqli_close($conn);
}
else {
    // dont' trust cookies
	$uniqueid = makeSafe($_COOKIE['guestuser']);
    $conn = mysqli_connect($host, $username,$password,$db);
    $query = mysqli_query($conn, "SELECT logintimes,thekey FROM guestusers WHERE uniqueid='".$uniqueid."'");
    $result = mysqli_fetch_row($query);
    $times = $result[0];
    $lastkey = $result[1];
    if ($lastkey != $key) { // If key is different to the one the user originally logged in with, increment their logintimes counter
        $query = mysqli_query($conn, "UPDATE guestusers SET logintimes=logintimes+1 WHERE uniqueid='".$uniqueid."'");
    }

    mysqli_close($conn);

}
// Better check key exists. If not, kick back to login page
$result=""; 
$record="";
$conn = mysqli_connect($host, $username, $password, $db);
$result = mysqli_query($conn, "select thekey from events where thekey='$key'");
        $rows = mysqli_num_rows($result);
        if ($rows == 1) {
            $row = mysqli_fetch_row($result);
            $result = $row[0];
        }
        mysqli_close($conn);

if (empty($result)) {
    header("Location: logout.php");
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="">
		<meta name="author" content="">

		<title><?php echo $company_name; ?>  Song Requests</title>
        <!-- FontAwesomeness -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<!-- Bootstrap core CSS -->
		<link href="../bootstrap/css/bootstrap.min.css" rel="stylesheet">

		<!-- Custom styles for this template -->
		<link href="../theme.css" rel="stylesheet">
		<![endif]-->

	</head>

	<body role="document">
		<div class="container theme-showcase" role="main">
			<div class="row top-buffer">
				<div class="col-md-12">
					<img class="img-fluid center-block" src="<?php echo $logoURL; ?>" alt="<?php echo $company_name; ?>"/>
				</div>
			</div>
			<div class="row top-buffer">
				<div class="col-md-12">
					<h1 class="text-center"><?php echo $company_name; ?> request system</h1>
				</div>
			</div>
            <div id="yourname">
            <?php echo "<p>Welcome, " . $name. "</p>"; ?>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs request-tabs">
                        <li class="nav-item">
                            <a href="#addrequest" aria-controls="addrequest" class="nav-link active" data-toggle="tab">
                                <i class="fa fa-plus-circle"></i> New Request
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#currentrequests" aria-controls="currentrequests" class="nav-link" data-toggle="tab">
                                <i class="fa fa-music"></i> Current Requests <span id="requests-badge" class="badge"></span> <span id="requests-length" class="badge"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="alreadyrequested">
                        <p>This track has already been requested. Please choose a different track</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-success collapse" role="alert" id="goodpopup">
                        <p>Your request has been entered successfully</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="databaseerror">
                        <p>ERROR: Whoops there was a problem with the database</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="toomanyuser">
                        <p>Sorry! You have reached your limit of requests</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="toomany">
                        <p>Sorry! The maximum number of requests has been reached</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="banned">
                        <p>Unknown Error</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger collapse" role="alert" id="floodalert">
                        <p>ERROR: You may only make one request every <?php if($flood_period > 60) { echo (intval($flood_period / 60) . " minutes.  "); } else { echo $flood_period . " seconds. ";} ?> Please wait and re-submit a new request.</p>
                    </div>
                </div>
            </div>
			<!-- Tab panes -->
    		<div class="tab-content">
                <div class="tab-pane active" role="tabpanel" id="addrequest">
                    <div class="search-box input-group mb-3">
                        <input type="text" class="form-control" id="searchText" placeholder="Search for tracks here" autocomplete="off" />
                        <div class="input-group-append"><button id="clear">Clear</button></div>
                    </div>
                    <div class="result"></div>    
                    <div id="livesearchbox"></div> <!-- Live Search -->
					<div class="as_grid_container">
						<div class="as_gridder" id="as_gridder"></div> <!-- GRID LOADER -->
					</div>
				</div>
				<div class="tab-pane request-pane fade" role="tabpanel" id="currentrequests">
					<div id="requests-placeholder"></div>
                </div>
			</div>
                    <div class="row">
            <div class="col-md-12">
                <a href="logout.php" class="btn btn-danger btn pull-right" role="button">Log Out <span class="glyphicon glyphicon-log-out" aria-hidden="true"></span></a>
            </div>
        </div>
		</div>


    
<!-- /container -->

		<!-- Bootstrap core JavaScript
		================================================== -->
		<!-- Placed at the end of the document so the pages load faster -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="../bootstrap/js/jquery.1.11.3.min.js"><\/script>')</script>
		<script src="../bootstrap/js/bootstrap.min.js"></script>

		<!-- Javascript for entries etc. -->
		<script>
$(document).ready(function(){
 $('.requestinfo').click(function(e){
     e.stopPropagation();
 });

 $(document).click(function(){
    $(".requestinfo").hide();
});

      
$(document).on("click", "button#clear", function() {
document.getElementById('searchText').value = "";
$('.result').hide();
$('#addnewreq').show();
});

    $('.search-box input[type="text"]').on("keyup input", function(){
        /* Get input value on change */
        var inputVal = $(this).val();
        var resultDropdown = $("div").siblings(".result");
        if(inputVal.length){
            $('#addnewreq').hide();
            $('.result').show();
            $.get("requestajax.php?action=search", {term: inputVal}).done(function(data){
                // Display the returned data in browser
                resultDropdown.html(data);
                
            });
        } else{
            resultDropdown.empty();
        }
    });
    
    // Do stuff on click of result item
    $(document).on("click", ".result button#addthis", function(){

    	var data = $(this).attr('value');
        var datasplit = data.split(';');
        var eventid = datasplit[1];
        var trackid = datasplit[0];
        var name = datasplit[2];
var comment = document.getElementById("comment"+trackid).value;
        data = "&eventid=" + eventid + "&trackid=" + trackid + "&name=" + name + "&message=" + comment;
        $.ajax({
            url : 'requestajax.php?action=addnewfromsearch',
            type : 'POST',
            data : data,
            success: function(data) {
                if (data.status == "alreadyrequested") {
                     setTimeout(function(){ $('#alreadyrequested').show(); }, 100);
                     setTimeout(function(){ $('#alreadyrequested').fadeOut('fast'); }, 8000);
                      $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                }

                if (data.status == "toomany") {
                     setTimeout(function(){ $('#toomany').show(); }, 100);
                     setTimeout(function(){ $('#toomany').fadeOut('fast'); }, 8000);
                      $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                }
                if (data.status == "toomanyuser") {
                     setTimeout(function(){ $('#toomanyuser').show(); }, 100);
                     setTimeout(function(){ $('#toomanyuser').fadeOut('fast'); }, 8000);
                      $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                }
                if (data.status == "banned") {
                     setTimeout(function(){ $('#banned').show(); }, 100);
                     setTimeout(function(){ $('#banned').fadeOut('fast'); }, 8000);
                      $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                }
                if (data.status == "flood") {
                     setTimeout(function(){ $('#floodalert').show(); }, 100);
                     setTimeout(function(){ $('#floodalert').fadeOut('fast'); }, 8000);
                      $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                }
                if (data.status == "success") {
                     setTimeout(function(){ $('#goodpopup').show(); }, 100);
                     setTimeout(function(){ $('#goodpopup').fadeOut('fast'); }, 5000);
                     // update the requests tab badge / list
                     $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
                     LoadRequests();
                     // clear the form
                    // $('#gridder_addform').trigger("reset");
                     
                }
                // LoadGrid();
            }
        });
    });
});

        		// Function to hide all errors
		function HideErrors() {
			$('.error').hide();
		}

		// Function for loading the grid
		function LoadGrid() {
			var gridder = $('#as_gridder');
			var UrlToPass = 'action=load';
			gridder.html('<i class="fa fa-spinner fa-spin" style="font-size:120px;color:purple"></i>Loading...');
			$.ajax({
				url : 'requestajax.php',
				type : 'POST',
				data : UrlToPass,
				success: function(responseText) {
					gridder.html(responseText);
				}
			});
		}

// Function for loading the search
		function LoadSearch() {
			var search = $('#livesearchbox');
			var UrlToPass = 'action=search';
			search.html('<i class="fa fa-spinner fa-spin" style="font-size:120px;color:purple"></i>Loading...');
			$.ajax({
				url : 'requestajax.php',
				type : 'POST',
				data : UrlToPass,
				success: function(responseText) {
					search.html(responseText);
				}
			});
		}

		// Function to populate requests
		function LoadRequests() {
			var therequests = $('#requests-placeholder');
			var UrlToPass = 'action=populateRequests';
			therequests.html('<i class="fa fa-spinner fa-spin" style="font-size:120px;color:purple"></i>Loading...');
			$.ajax({
				url : 'requestajax.php?rnd=',
				type : 'POST',
				data : UrlToPass,
				success: function(responseText) {
					therequests.html(responseText);
				}
			});
		}

		// update current requests on tab click
		$('a[data-toggle="tab"][aria-controls="currentrequests"]').on('shown.bs.tab', function (e) {
			LoadRequests();
		})


		$(function(){

            LoadSearch();
			LoadGrid(); // Load the grid on page loads

			LoadRequests(); // Load the requests
			// disable form default submit
			$("#cpa-form").submit(function(e){
				e.preventDefault();
			});

			// Pass the values to ajax page to add the values
			$('body').delegate('#gridder_addrecord', 'click', function(){
				//clear any existing error messages
				HideErrors();
				var suberrors = 0;
				// Do insert validation here
				if($('#name').val() == "") {
					$('#name').focus();
					$('#nameerror').show(); 
					++suberrors;
				}

				if($('#name').val().length > 64) {
					$('#name').focus();
					$('#nameerror_tl').show(); 
					++suberrors;
				}

				if($('#artist').val() == '') {
					$('#artist').focus();
					$('#artisterror').show(); 
					++suberrors;
				}

				if($('#artist').val().length > 64) {
					$('#artist').focus();
					$('#artisterror_tl').show(); 
					++suberrors;
				}

				if($('#title').val() == '') {
					$('#title').focus();
					$('#titleerror').show(); 
					++suberrors;
				}

				if($('#title').val().length > 64) {
					$('#title').focus();
					$('#titleerror_tl').show(); 
					++suberrors;
				}

				if($('#message').val().length > 140) {
					$('#message').focus();
					$('#messageerror_tl').show(); 
					++suberrors;
				}
				if(suberrors > 0) {
					alert("There was a problem with your request.");
				}
				if(suberrors == 0) {
					// Pass the form data to the ajax page
					var data = $('#gridder_addform').serialize();
					$.ajax({
						url : 'requestajax.php',
						type : 'POST',
						data : data,
						success: function(data) {
							if (data.status == "toomany") {
								 setTimeout(function(){ $('#toomany').show(); }, 100);
								 setTimeout(function(){ $('#toomany').fadeOut('fast'); }, 8000);
                                  $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
							}
							if (data.status == "toomanyuser") {
								 setTimeout(function(){ $('#toomanyuser').show(); }, 100);
								 setTimeout(function(){ $('#toomanyuser').fadeOut('fast'); }, 8000);
                                  $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
							}
							if (data.status == "banned") {
								 setTimeout(function(){ $('#banned').show(); }, 100);
								 setTimeout(function(){ $('#banned').fadeOut('fast'); }, 8000);
                                  $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
							}
							if (data.status == "flood") {
								 setTimeout(function(){ $('#floodalert').show(); }, 100);
								 setTimeout(function(){ $('#floodalert').fadeOut('fast'); }, 8000);
                                  $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
							}
							if (data.status == "success") {
 								 setTimeout(function(){ $('#goodpopup').show(); }, 100);
								 setTimeout(function(){ $('#goodpopup').fadeOut('fast'); }, 5000);
                                 $('html, body').animate({ scrollTop: $('.nav').offset().top}, 250);
								 // update the requests tab badge / list
								 LoadRequests();
								 // clear the form
								 $('#gridder_addform').trigger("reset");
							}
							// LoadGrid();
						}
					});
				}
				return false;
			});
		});
        </script>
	</body>
</html>