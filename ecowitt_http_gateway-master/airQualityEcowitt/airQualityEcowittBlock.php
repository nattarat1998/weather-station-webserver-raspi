<?php

	# 	Air Quality Index Ecowitt
	# 	Namespace:		airQualityEcowitt
	#		Meteotemplate Block
	#   Derived by Air Quality Index World
	#
	#
	#		v1.0 - Oct 1, 2019
	#			- first version
	#		v1.1 - Oct 2, 2019
	#			- new css for use with Air Qality Index World on the same page
	
	$thisBlockVersion = "1.0";
	
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$blockStartTime = $time;
	
	$errorLog[] = array("","%c Loading Air Quality Ecowitt Block ".$thisBlockVersion."','font-weight:bold;color:green");
	
	
	#$errorLog[] = array("","URL for data retrieval: ".$url);
	#$errorLog[] = array("","Cache time: 3 hours");
	
	// load theme
	$designTheme = json_decode(file_get_contents("../../css/theme.txt"),true);
	$theme = $designTheme['theme'];
	
	include("../../../config.php");
	include("../../../css/design.php");
	include("../../../scripts/functions.php");
	
	$languageRaw = file_get_contents($baseURL."lang/gb.php");
	$language['gb'] = json_decode($languageRaw,true);
	$languageRaw = file_get_contents($baseURL."lang/".$lang.".php");
	$language[$lang] = json_decode($languageRaw,true);
	
	function InvLinear($AQIhigh, $AQIlow, $Conchigh, $Conclow, $a) {
		//var AQIhigh;
		//var AQIlow;
		//var Conchigh;
		//var Conclow;
		//var linear;
		//var Conc=parseFloat(Concentration);
		//var a;
		$Conc=floatval($a);
		$c=(($Conc-$Conclow)/($Conchigh-$Conclow))*($AQIhigh-$AQIlow)+$AQIlow;
		$linear=round($c);
		//$c=(($a-$AQIlow)/($AQIhigh-$AQIlow))*($Conchigh-$Conclow)+$Conclow;
		return $linear;
	}

	function AQIPM25($a){
		$Conc=floatval($a);
		$a=(floor(10*$Conc))/10;
		if ($a>=0 && $a<=12.1){
			$ConcCalc=InvLinear(50,0,12,0,$a);
		}
		else if ($a>12.1 && $a<=35.5){
			$ConcCalc=InvLinear(100,51,35.4,12.1,$a);
		}
		else if ($a>35.5 && $a<=55.5){
			$ConcCalc=InvLinear(150,101,55.4,35.5,$a);
		}
		else if ($a>55.5 && $a<=150.5){
			$ConcCalc=InvLinear(200,151,150.4,55.5,$a);
		}
		else if ($a>150.5 && $a<=250.5){
			$ConcCalc=InvLinear(300,201,250.4,150.5,$a);
		}
		else if ($a>250.5 && $a<=350.5){
			$ConcCalc=InvLinear(400,301,350.4,250.5,$a);
		}
		else if ($a>350.5 && $a<=500.5){
			$ConcCalc=InvLinear(500,401,500.4,350.5,$a);
		}
		else{
			$ConcCalc="PM25message";
		}
	return $ConcCalc;
    }
 
	if(file_exists("../../../meteotemplateLive.txt")){
		$apiData = file_get_contents("../../../meteotemplateLive.txt");
		$apiData = json_decode($apiData,true);
		if(isset($apiData['PP1'])){
			$pm25_mg = $apiData['PP1'];

			$pm25=AQIPM25($pm25_mg);

		}
		else{
			die("No PP1 data found in the current conditions file.");
		}
	}
	else{
		die("Current conditions file not found.");
	}

	$xpm25_min5_mg = mysqli_query($con,
			"
			SELECT min(PP1) FROM alldataExtra  WHERE DateTime >= now() - interval 5 day
			"
		);
	
	while($row = mysqli_fetch_array($xpm25_min5_mg))
	{    
    $pm25_min5_mg .=  $row['min(PP1)'];
	}
	
	$pm25_min5 = AQIPM25($pm25_min5_mg);
	
	
	
	
	
	$xpm25_max5_mg = mysqli_query($con,
			"
			SELECT max(PP1) FROM alldataExtra  WHERE DateTime >= now() - interval 5 day
			"
		);
	
	while($row = mysqli_fetch_array($xpm25_max5_mg))
	{    
    $pm25_max5_mg .=  $row['max(PP1)'];
	}
	
	$pm25_max5 = AQIPM25($pm25_max5_mg);	

	
	
	
	$conditions = array(lang('good','c'),lang('moderate','c'),lang('unhealthy for sensitive groups','c'),lang('unhealthy','c'),lang('very unhealthy','c'),lang('hazardous','c'));
	$bgColors = array('#00B300','#FFFF00','#FF8000','#FF2626','#B30086','#000000');
	$fontColors = array('#FFFFFF','#000000','#FFFFFF','#FFFFFF','#FFFFFF','#FFFFFF');
	
	$values = array();
	

	$values['pm25'] = array($pm25,17,21);

	$details = "<table class='table aqiWorldTable1'>";
	
	if(array_key_exists('pm25',$values)){
		$position = getCondition($values['pm25'][0]);
		$details = $details."<tr><td style='padding-right:2px;font-weight:bold'>PM2.5</td><td style='text-align:justify'>";
		if($position==0){
			$details = $details."It’s a great day to be active outside.";
		}
		if($position==1){
			$details = $details."<strong>Unusually sensitive people</strong><br>Consider reducing prolonged or heavy exertion. Watch for symptoms such as coughing or shortness of breath. These are signs to take it easier.<br><br><strong>Everyone else</strong><br>It’s a good day to be active outside.";
		}
		if($position==2){
			$details = $details."<strong>Sensitive groups</strong><br>Reduce prolonged or heavy exertion. It’s OK to be active outside, but take more breaks and do less intense activities. Watch for symptoms such as coughing or shortness of breath.<br><br><strong>People with asthma</strong><br>People with asthma should follow their asthma action plans and keep quick relief medicine handy.<br><br><strong>People with heart disease<br>Symptoms such as palpitations, shortness of breath, or unusual fatigue may indicate a serious problem. If you have any of these, contact your heath care provider.";
		}
		if($position==3){
			$details = $details."<strong>Sensitive groups</strong><br>Avoid prolonged or heavy exertion. Move activities indoors or reschedule to a time when the air quality is better.<br><br><strong>Everyone else</strong><br>Reduce prolonged or heavy exertion. Take more breaks during all outdoor activities.";
		}
		if($position==4){
			$details = $details."<strong>Sensitive groups</strong><br>Avoid all physical activity outdoors. Move activities indoors or reschedule to a time when air quality is better.<br><br><strong>Everyone else</strong><br>Avoid prolonged or heavy exertion. Consider moving activities indoors or rescheduling to a time when air quality is better.";
		}
		if($position==5){
			$details = $details."<strong>Everyone</strong><br>Avoid all physical activity outdoors.<br><br><strong>Sensitive groups</strong><br>Remain indoors and keep activity levels low. Follow tips for keeping particle levels low indoors.";
		}
		$details = $details."</td></tr>";
	}	
	
	$details = $details."</table>";
	
	function getCondition($index){
		if($index<=50){
			$position = 0;
		}
		else if($index>=51 && $index<=100){
			$position = 1;
		}
		else if($index>=101 && $index<=150){
			$position = 2;
		}
		else if($index>=151 && $index<=200){
			$position = 3;
		}
		else if($index>=201 && $index<=300){
			$position = 4;
		}
		else{
			$position = 5;
		}
		return $position;
	}
	
?>


	<style>
		.aqiWorldicon1{
			max-width:45px;
			width:100%;
		}
		.aqiWorldTable1 td{
			padding: 2%;
		}
		#aqiWorldDetails1 table td{
			padding: 8px;
		}
		.airQualitySingle1{
			display: inline-block;
			font-size: 18px;
			width: 200px;
			padding: 2px;
			font-weight:bold;
			border:3px solid <?php echo $theme=="dark" ? 'white' : 'black'?>;
			border-radius:5px;
		}
	</style>

		<div style="width:98%;margin:0 auto;text-align:center">
			<h2><?php echo $city?></h2>
			<?php echo lang("air quality","c")?>
			<br>


					<?php
						if(array_key_exists('pm25',$values)){
							$position = getCondition($values['pm25'][0]);
					?>
							<div style="margin:0 auto;padding:10px;display:inline-block;text-align:center">
								<img src="homepage/blocks/airQualityEcowitt/icons/<?php echo $theme?>/pm25.png" class="aqiWorldicon1"><br>
								<div class="airQualitySingle1" style="background:<?php echo $bgColors[$position]?>;color:<?php echo $fontColors[$position]?>"><?php echo $pm25_mg ?> ug/m<sup>3</sup> - <?php echo $values['pm25'][0]?> AQI</div><br><span style="font-size:1.3em;font-variant:small-caps;font-weight:bold"><?php echo $conditions[$position]?></span>
							</div>
					<?php
						}
					?>

					<br>
					<div id="aqiWorldDetails1" class="details">
						<br>
						<span style="color:#<?php echo $color_schemes[$design2]['200']?>;font-size:1.1em;font-variant:small-caps;font-weight:bold"><?php echo lang("last","c")?> 5 <?php echo lang('days','l')?></span>
						<br>
						<table class="table">
							<tr>
								<td></td>
								<td>
									<?php echo lang('minimumAbbr','c')?>
								</td>
								<td>
									<?php echo lang('maximumAbbr','c')?>
								</td>
							</tr>
						<?php
							if(array_key_exists('pm25',$values)){
						?>
								<tr>
									<td>
										PM2.5
									</td>
									<td>
										<div style="display:inline-block;font-weight:bold;font-size:1em;border:1px solid white;border-radius:3px;padding:4px;background:<?php echo $bgColors[getCondition($pm25_min5)]?>;color:<?php echo $fontColors[getCondition($pm25_min5)]?>"><?php echo $pm25_min5?></div>
									</td>
									<td>
										<div style="display:inline-block;font-weight:bold;font-size:1em;border:1px solid white;border-radius:3px;padding:4px;background:<?php echo $bgColors[getCondition($pm25_max5)]?>;color:<?php echo $fontColors[getCondition($pm25_max5)]?>"><?php echo $pm25_max5?></div>
									</td>
								</tr>
						<?php
							}
						?>


						</table>
						<h4><?php echo lang("current",'c')?></h4>
						<?php echo $details?>
					</div>
					<span class="more" onclick="txt = $('#aqiWorldDetails').is(':visible') ? '<?php echo lang('more','l')?>' : '<?php echo lang('hide','l')?>';$('#aqiWorldDetails1').slideToggle(800);$(this).text(txt)">
						<?php echo lang('more','l')?>
					</span>
					<br>
					<a href="#"><span id="AIQinfoDivOpenerWorld">AQI <?php echo lang('info','l')?></span></a>

			</div>

	<div id="AIQinfoDivWorld">
		<h1>Air Quality Index</h1>
		<br>
		<table class="table">
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[0]?>;color:<?php echo $fontColors[0]?>">Good</div>
				</td>
				<td style="text-align:justify;padding:3px">
					Air quality is considered satisfactory, and air pollution poses little or no risk.
				</td>
			</tr>
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[1]?>;color:<?php echo $fontColors[1]?>">Moderate</div>
				</td>
				<td style="text-align:justify;padding:3px">
					Air quality is acceptable; however, for some pollutants there may be a moderate health concern for a very small number of people. For example, people who are unusually sensitive to ozone may experience respiratory symptoms.
				</td>
			</tr>
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[2]?>;color:<?php echo $fontColors[2]?>">Unhealthy for Sensitive Groups</div>
				</td>
				<td style="text-align:justify;padding:3px">
					Although general public is not likely to be affected at this AQI range, people with lung disease, older adults and children are at a greater risk from exposure to ozone, whereas persons with heart and lung disease, older adults and children are at greater risk from the presence of particles in the air.
				</td>
			</tr>
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[3]?>;color:<?php echo $fontColors[3]?>">Unhealthy</div>
				</td>
				<td style="text-align:justify;padding:3px">
					Everyone may begin to experience some adverse health effects, and members of the sensitive groups may experience more serious effects.
				</td>
			</tr>
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[4]?>;color:<?php echo $fontColors[4]?>">Very Unhealthy</div>
				</td>
				<td style="text-align:justify;padding:3px">
					This would trigger a health alert signifying that everyone may experience more serious health effects.
				</td>
			</tr>	
			<tr>
				<td>
					<div style="text-align:center;display:inline-block;min-width:45px;font-weight:bold;font-size:1.0em;border:3px solid white;border-radius:5px;padding:2px;margin:10px;background:<?php echo $bgColors[5]?>;color:<?php echo $fontColors[5]?>">Hazardous</div>
				</td>
				<td style="text-align:justify;padding:3px">
					This would trigger a health warnings of emergency conditions. The entire population is more likely to be affected.
				</td>
			</tr>				
		</table>
		<br>
		<h2>PM<sub>2.5</sub></h2>
		<p>
			Fine particles (PM2.5) are 2.5 micrometers in diameter or smaller, and can only be seen with an electron microscope. Fine particles are produced from all types of combustion, including motor vehicles, power plants, residential wood burning, forest fires, agricultural burning, and some industrial processes.
		</p>
	</div>
	<script>
		$( "#AIQinfoDivWorld" ).dialog({
			autoOpen: false,
			show: {
				effect: "puff",
				duration: 500
			},
			hide: {
				effect: "puff",
				duration: 500
			},
			height: 800,
			width: '40%',
			position:{
				my: 'top', 
				at: 'top+5%'
			}
		});
		$("#AIQinfoDivOpenerWorld").click(function(){
			$( "#AIQinfoDivWorld" ).dialog( "open" );
		});
	</script>
	
	<?php
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$blockEndTime = $time;
		$blockLoadTime = $blockEndTime - $blockStartTime;
		$blockLoadTime = round($blockLoadTime,4);
		$errorLog[] = array("","Block loaded in ".$blockLoadTime." s.");
		showLog($errorLog);
	?>