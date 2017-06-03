<?php

$debug = FALSE;
$file_blacklist = array (
'code/game/objects/items/weapons/cards_ids.dm',
'code/game/objects/items/stacks/sheets/'
);
$use_blacklist = FALSE;

$itemlist = array();
$stringlist = array();

$handle = fopen("../file_list_combined", "r");
if ($handle) {
	while (($line = fgets($handle)) !== false) {
		$line = chop($line);
		if ($use_blacklist) {
			foreach ($file_blacklist as $b) {
				if(strpos($line, $b) !== FALSE) {
					//print "BLACKLIST: $line against $b \n";
					continue;
				} else {
					//print "NOT MATCHED: '$line' V '$b' \n";
				}
			}
		}
		$handle2 = fopen("$line", "r");
		if ($handle2) {
			if ($debug) { print "OPENED: $line \n"; }
			$item = $name = $rtech = $otech = $techstring = $ofile = $build_types = '';
			$ofile = $line;
			while (($line2 = fgets($handle2)) !== false) {
				chop($line2);
				if(preg_match('/^(\/[0-9a-zA-Z\/]+)/', $line2, $matches) && !preg_match('/^\/\//', $line2) && strpos($line2, '(') === FALSE ) {
					if ($debug) { print "Found item definition: $matches[0] \n"; }
					build_new_item($item, $name, $rtech, $otech, $build_types, $ofile);
					$item = $name = $rtech = $otech = $techstring = $build_types = '';
					$item = $matches[1];
					$name = $tech = $techstring = '';
				} else if ($item != '' && preg_match('/name = "(.+)"/', $line2, $matches)) {
					//print "Found item name: $matches[1] \n";
					$name = str_replace("'", "", $matches[1]);
					$name = str_replace('\improper ', '', $name);
				} else if ($item != '' && preg_match('/req_tech = list\((.+)\)/', $line2, $matches)) {
					//print "Found req tech: '$matches[1]' \n";
					if($matches[1] != '') {
						//print "Converting...\n";
						$rtech = convert_to_techstring($matches[1], TRUE);
						//print "RTECH is now $rtech \n";
					}
				} else if ($item != '' && preg_match('/origin_tech = "(.+)"/', $line2, $matches)) {
					//print "Found tech origin: $matches[1] \n";
					$otech = convert_to_techstring($matches[1], FALSE);
					//print "OTECH is now $otech \n";
				} else if ($item != '' && preg_match('/build_type = ([A-Za-z0-9]+)/', $line2, $matches)) {
					if ($debug) { print "Found BUILD TYPE: '$matches[1]' \n"; }
					if ($matches[1] == 'null') { continue; }
					$build_type = array();
					$pvals = array('IMPRINTER','PROTOLATHE','AUTOLATHE','CRAFTLATHE','MECHFAB','PODFAB','BIOGENERATOR');
					foreach ($pvals as $pbt) {
						//print "Considering: $pbt \n";
						if(strpos($line2, $pbt) !== FALSE) {
							$build_type[] = $pbt;
							//print "MATCH: $pbt against $line2 \n";
						} else {
							//print "NO MATCH: $pbt versus $line2 \n";
						}
					}
					//print_r($build_type);
					$build_types = implode(', ', $build_type);
					//print "RETURN: $build_types \n";
				} else {
					//print "NO MATCH: $line2 ";
				}
			}
			build_new_item($item, $name, $rtech, $otech, $build_types, $ofile);
			fclose($handle2);		
        	} else {
			print "ERROR OPENING: ./$line \n";
		}
	}
    fclose($handle);
} else {
    print "ERROR OPENING: ./file_list";
} 

sort($itemlist);
foreach($itemlist as $thisitem) {
//	print "$thisitem \n";
}
sort($stringlist);
print "rndData = [\n";
foreach($stringlist as $thisstring) {
	print "$thisstring, \n";
}
print "]";
//print_r($stringlist);

function build_new_item($t, $n, $r, $o, $s, $i) {
	global $itemlist;
	global $stringlist;
	global $debug;
	//if ($t != '' && $n != '' && ($r != '' || $o != '')) {
	if ($t != '' && $n != '' && $o != '') {
		if ($r === '') { $r = '{}'; }
		if ($o === '') { $o = '{}'; }
		if ($s === '') { $s = $i; }
		$itemlist[] = "'$n' ($t) has $r/$o";
		$newitem = "{'name':'$n', 'buildType':'$s', 'numCost':0, 'reqTech':$r, 'originTech':$o }";
		$stringlist[] = $newitem;
		if ($debug) { print "BUILDING ITEM FOR $t: $newitem \n"; }
		//{'name':'Advanced Laser Scalpel','buildType':'PROTOLATHE','numCost':37500,'reqTech':{'m':6,'e':0,'pl':0,'pow':0,'bs':0,'bio':4,'c':0,'em':5,'dt':0,'i':0},'originTech':{'m':1,'e':0,'pl':0,'pow':0,'bs':0,'bio':1,'c':0,'em':0,'dt':0,'i':0}},
		//print "{'name':'" + $n + "',buildType':'MISC','numCost':0,'reqTech':{" + 
	} else {
		//print "X: $t $n $r $o \n";
	}
}

function convert_to_techstring($tstr, $islist) {	
	//print "$tstr \n";
	$xta = array(
			"materials" => "m", 
			"engineering" => "e", 
			"plasmatech" => "pl", 
			"powerstorage" => "pow",
			"bluespace" => "bs",
			"biotech" => "bio",
			"combat" => "c",
			"magnets" => "em",
			"programming" => "dt",
			"syndicate" => "i"
	);
	$techarr = array();
	if($islist) {
		//print "ILTX: $tstr \n";
		$temp_arr = explode(",", $tstr);
		foreach ($temp_arr as $temp_string) {
			$temp_string = str_replace('"', '', $temp_string);
			foreach ($xta as $x => $y) {
				if(preg_match("/$x = ([\d]+)/", $temp_string, $matches)) {
					$jj = "$x=" . $matches[1];
					$techarr[] = $jj;
					//print "ADDED: $jj \n";
				} else {
					//print "FAIL: '$x = (###)' v '$temp_string' \n";
				}
			}
		}
	} else {
		$techarr = explode(";", $tstr);	
	}
	$nta = array("m" => 0, "e" => 0, "pl" => 0, "pow" => 0, "bs" => 0, "bio" => 0, "c" => 0, "em" => 0, "dt" => 0, "i" => 0);
	foreach($techarr as $t) {
		$parts = explode("=", $t);
		//print_r($parts);
		if(isset($xta[$parts[0]])) {
			//print "GOT PART for" . $parts[0] . " \n";
			$nta[$xta[$parts[0]]] = $parts[1];
		} else {
			//print "NO PART for" . $parts[0] . " \n";
		}
	}
	//print "Resulting array: \n";
	//print_r($nta);
	$techstring = '{';
	//print_r($nta);
	$firsttime = TRUE;
	foreach ($nta as $k => $v) {
		$p = '';
		if($firsttime) {
			$firsttime = FALSE;
		} else {
			$p .= ', '; 
		}
		$p .= "'$k':'$v'";
		$techstring .= $p;
	//	print "Adding '$p' to $techstring \n";
	}
	$techstring .= '}';
	return $techstring;
}

?>
