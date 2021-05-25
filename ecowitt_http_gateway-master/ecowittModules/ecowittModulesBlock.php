<?php

	# 		Ecowitt - Modules
	# 		Namespace:		ecowittModules
	#		Meteotemplate Block

	# 		v1.0 - May 11, 2020
	# 			- initial release
	
		
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


	
	if(!file_exists("../../../meteotemplateLive.txt")){
		die("<br><br>API file not found.<br><br>");
	}

	$apiData = file_get_contents("../../../meteotemplateLive.txt");
	$apiData = json_decode($apiData,true);

	//$rf_status['value'] = (110 - ($apiData['WBAT'] * 18)) ;
	//echo $apiData['WBAT'];
	//echo $rf_status['value'];
	//$lastseen = date($dateTimeFormat,$apiData['U']);
	//echo $lastseen;
	$firmware = "2.0";
	
	$data['modules'] = array (
		"WS80"  => array("module_name" => "WS80", "battery_percent" => ($apiData['WBAT'] * 31), "last_seen" => $apiData['U'], "data_type" => array("Wind","Temperature","Humidity","Pressure","UV"), "firmware" => $firmware, "rf_status" => (117 - ($apiData['WBAT'] * 18)) ),
		"WH40"  => array("module_name" => "WH40", "battery_percent" => ($apiData['RBAT'] * 62), "last_seen" => $apiData['U'], "data_type" => array("Rain"), "firmware" => $firmware, "rf_status" => (89 - ($apiData['RBAT'] * 18)) ),
		"WH41"  => array("module_name" => "WH41", "battery_percent" => ($apiData['PP1BAT'] * 18), "last_seen" => $apiData['U'], "data_type" => array("pp25"), "firmware" => $firmware, "rf_status" => (150 - ($apiData['PP1BAT'] * 18)) ),
		"WH51_1"  => array("module_name" => "WH51_1", "battery_percent" => ($apiData['SM1BAT'] * 58), "last_seen" => $apiData['U'], "data_type" => array("Soil"), "firmware" => $firmware, "rf_status" => (91 - ($apiData['SM1BAT'] * 18)) ),
		"WH51_2"  => array("module_name" => "WH51_2", "battery_percent" => ($apiData['SM2BAT'] * 58), "last_seen" => $apiData['U'], "data_type" => array("Soil"), "firmware" => $firmware, "rf_status" => (91 - ($apiData['SM2BAT'] * 18)) ),
		"WH57"  => array("module_name" => "WH57", "battery_percent" => ($apiData['LBAT'] * 19), "last_seen" => $apiData['U'], "data_type" => array("Lightning"), "firmware" => $firmware, "rf_status" => (150 - ($apiData['LBAT'] * 18)) ),
	);


	
	if(isset($data['modules'])){
		foreach($data['modules'] as $module){
			$thisModule = array();
			//echo $module['battery_percent'];
			if(empty($module['battery_percent'])) {
				
			} else {
				$thisModule['name'] = $module['module_name'];
				$thisModule['battery'] = $module['battery_percent'];
				$thisModule['lastSeen'] = $module['last_seen'];
				$thisModule['parameters'] = $module['data_type'];
				$thisModule['firmware'] = $module['firmware'];
				$thisModule['connection'] = $module['rf_status']; // 90 = low, 60 = highest
				$modules[] = $thisModule;
			}
		}
	}
	$ecowitt['modules'] = $modules;
	

	// OLD netAtmo	

	/*
	if(isset($data['wifi_status'])){
		$ecowitt['wifi'] = transformWifi($data['wifi_status']); // 86 = bad, 56 = good
	}
	if(isset($data['firmware'])){
		$ecowitt['firmware'] = $data['firmware'];
	}
	if(isset($data['last_upgrade'])){
		$ecowitt['lastUpgrade'] = $data['last_upgrade'];
	}
	if(isset($data['module_name'])){
		$ecowitt['mainUnitName'] = $data['module_name'];
	}
	if(isset($data['last_status_store'])){
		$ecowitt['lastSeen'] = $data['last_status_store'];
	}
	if(isset($data['data_type'])){
		$ecowitt['mainUnitParameters'] = $data['data_type'];
	}
	
	*/
	// OLD netAtmo
		
	$ecowitt['mainUnitName'] = "Ecowitt GW1000"; 
	$ecowitt['wifi'] = transformWifi(108 - ($apiData['WBAT'] * 18 )); // 86 = bad, 56 = good
	
	if(isset($apiData['WBAT'])){
		$ecowitt['WBAT'] = $apiData['WBAT'];
	}
	if(isset($apiData['RBAT'])){
		$ecowitt['RBAT'] = $apiData['RBAT'];
	}	
	if(isset($apiData['LBAT'])){
		$ecowitt['LBAT'] = $apiData['LBAT'];
	}	
	if(isset($apiData['PP1BAT'])){
		$ecowitt['PP1BAT'] = $apiData['PP1BAT'];
	}	
	if(isset($apiData['SM1BAT'])){
		$ecowitt['SM1BAT'] = $apiData['SM1BAT'];
	}	
	if(isset($apiData['SM2BAT'])){
		$ecowitt['SM2BAT'] = $apiData['SM2BAT'];
	}	
	if(isset($apiData['T1BAT'])){
		$ecowitt['T1BAT'] = $apiData['T1BAT'];
	}	
	if(isset($apiData['T2BAT'])){
		$ecowitt['T2BAT'] = $apiData['T2BAT'];
	}	
	

	
	
	function transformWifi($signal){
		$totalPercent = 86 - 56;
		$currentPercent = 86 - $signal;
		$quality = ($currentPercent/$totalPercent)*100;
		if($quality<0){
			$quality = 0;
		}
		if($quality>100){
			$quality = 100;
		}
		if($quality<=25){
			$color = "#b20303";
		}
		if($quality>25 && $quality<=50){
			$color = "#b26603";
		}
		if($quality>50 && $quality<=75){
			$color = "#afaa00";
		}
		if($quality>75){
			$color = "#009919";
		}
		return array(round($quality),$color);
	}

	$color1 = $theme=="dark" ? "white" : "black";
	$color2 = $theme=="dark" ? $color_schemes[$design2]['900'] : $color_schemes[$design2]['100'];
	$color3 = $theme=="dark" ? $color_schemes[$design2]['300'] : $color_schemes[$design2]['600'];

	function convertIcon($parameter){
		if($parameter=="Temperature"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-temp' style='font-size:27px'></div></td>";
		}
		if($parameter=="UV"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-uv' style='font-size:27px'></div></td>";
		}		
		if($parameter=="Humidity"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-humidity' style='font-size:27px'></div></td>";
		}
		if($parameter=="Pressure"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-pressure' style='font-size:27px'></div></td>";
		}
		if($parameter=="Wind"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-wind' style='font-size:27px'></div></td>";
		}
		if($parameter=="Rain"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-rain' style='font-size:27px'></div></td>";
		}
		if($parameter=="pp25"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-pm10' style='font-size:27px'></div></td>";
		}
		if($parameter=="Lightning"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-storm' style='font-size:27px'></div></td>";
		}
		if($parameter=="Soil"){
			return "<td><div style='width:30px;height:30px;border:3px solid ".$color1.";border-radius:50%;padding:3px'><span class='mticon-soiltemperature' style='font-size:27px'></div></td>";
		}		
	}

	function transformBattery($level){
		if($level>=90){
			return "fa fa-battery-full";
		}
		if($level>=70 && $level<90){
			return "fa fa-battery-three-quarters";
		}
		if($level>=30 && $level<70){
			return "fa fa-battery-half";
		}
		if($level>=10 && $level<30){
			return "fa fa-battery-quarter";
		}
		if($level<10){
			return "fa fa-battery-empty";
		}
	}

	function transformSignal($signal){
		$percent = (90 - $signal)/30;
		$percent = $percent * 100;
		return round($percent);
	}

	function getBG($percent){
		if($percent<=25){
			$color = "#b20303";
		}
		if($percent>25 && $percent<=50){
			$color = "#b26603";
		}
		if($percent>50 && $percent<=75){
			$color = "#afaa00";
		}
		if($percent>75){
			$color = "#009919";
		}
		return $color;
	}
	
?>
	<span class="mticon-station" style="font-size:4em"></span><br><span style="font-variant:small-caps;font-size:1.2em;font-weight:bold"><?php echo $ecowitt['mainUnitName']?></span>
	<table style="width:98%;margin:0 auto">
		<tr>
			<td style="width:50%">
				<span class="fa fa-refresh" style="font-size:2.5em"></span><br>
			</td>
			<td style="width:50%">
				<span class="fa fa-wifi" style="font-size:2.5em"></span>
			</td>
		</tr>
		<tr>
			<td style="font-size:1em">
				<?php echo date($dateTimeFormat,$apiData['U'])?>
			</td>
			<td style="font-size:1.3em">
				<div style="margin:5px;padding:3px;color:white;background:<?php echo $ecowitt['wifi'][1];?>;border:1px solid <?php echo $color1?>;border-radius:8px;font-weight:bold">
					<?php echo $ecowitt['wifi'][0]?><span style="font-size:0.7em">%</span>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="2" style="font-weight:bold;font-size:1.1em;font-variant:small-caps;text-align:left">
				<table cellspacing="4" style="margin:0 auto">
					<tr>
						<?php 
							if(isset($apiData['WBAT'])){
								echo convertIcon(Wind);
							}
							if(isset($apiData['RBAT'])){
								echo convertIcon(Rain);
							}	
							if(isset($apiData['LBAT'])){
								echo convertIcon(Lightning);
							}	
							if(isset($apiData['PP1BAT'])){
								echo convertIcon(pp25);
							}	
							if(isset($apiData['T1BAT'])){
								echo convertIcon(Temperature);
							}	
							if(isset($apiData['T2BAT'])){
								echo convertIcon(Temperature);
							}							
						?>
					</tr>
				</table>
			</td>
		<tr>
	</table>
	<div id="ecowittModulesDetails" class="details">
		<span style="font-variant:small-caps;font-size:1.2em;font-weight:bold"><?php echo lang('modules','c')?></span>
		<?php 
			foreach($ecowitt['modules'] as $module){
		?>
				<div style="width:94%;border-radius:8px;padding:1%;margin:0 auto;background:#<?php echo $color2?>;border:1px solid #<?php echo $color3?>">
					<table style="width:98%;margin: 0 auto">
						<tr>
							<td colspan="2" style="font-weight:bold;font-size:1.1em;font-variant:small-caps;text-align:left;padding-left:2px">
								<?php echo $module['name'];?>
							</td>
						<tr>
						<tr>
							<td colspan="2" style="font-weight:bold;font-size:1.1em;font-variant:small-caps;text-align:left">
								<table cellspacing="4">
									<tr>
										<?php 
											foreach($module['parameters'] as $parameter){
												echo convertIcon($parameter);
											}
										?> 
										<!-- <?php echo convertIcon($module['parameters'])?> -->
									</tr>
								</table>
							</td>
						<tr>
					</table>
					<table style="width:98%;margin: 0 auto">
						<tr>
							<td style="width:40%;vertical-align:top">
								<span class="fa fa-refresh" style="font-size:1.5em"></span><br>
							</td>
							<td style="width:20%;vertical-align:top">
								<span class="<?php echo transformBattery($module['battery'])?>" style="font-size:1.5em"></span><br>
							</td>
							<td style="width:20%;vertical-align:top">
								<span class="fa fa-signal" style="font-size:1.5em"></span><br>
							</td>
							<td style="width:20%;vertical-align:top">
								<span class="fa fa-cogs" style="font-size:1.5em"></span><br>
							</td>
						</tr>
						<tr>
							<td style="width:40%;vertical-align:top">
								<?php echo date($dateTimeFormat,$module['lastSeen']);?> 
							</td>
							<td style="width:20%;vertical-align:top">
								<div style="margin:5px;padding:3px;color:white;background:<?php echo getBG($module['battery']);?>;border:1px solid <?php echo $color1?>;border-radius:5px;font-weight:bold">
									<?php echo $module['battery']?><span style="font-size:0.7em">%</span>
								</div>
							</td>
							<td style="width:20%;vertical-align:top">
								<div style="margin:5px;padding:3px;color:white;background:<?php echo getBG(transformSignal($module['connection']));?>;border:1px solid <?php echo $color1?>;border-radius:5px;font-weight:bold">
									<?php echo transformSignal($module['connection'])?><span style="font-size:0.7em">%</span>
								</div>
							</td>
							<td style="width:20%">
								v.<?php echo $module['firmware'];?>
							</td>
						</tr>
					</table>
				</div>
				<div style="width:100%;height:15px"></div>
		<?php 
			}
		?>
	</div>
	<span class="more" onclick="txt = $('#ecowittModulesDetails').is(':visible') ? '<?php echo lang('more','l')?>' : '<?php echo lang('hide','l')?>';$('#ecowittModulesDetails').slideToggle(800);$(this).text(txt)">
		<?php echo lang('more','l')?>
	</span>
