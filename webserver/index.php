<?php
include 'config.php';

$mtimgpath = realpath($exportdir.'/textures');
$mtdata = json_decode(file_get_contents($exportdir.'/mtdata.json'), true);

include 'image.php';

function url_item($name) {
	return '/?type=item&q='.$name;
}

function url_group($name) {
	return '/?type=group&q='.$name;
}

function is_group($name) {
	if ($name) {
		$e = explode(':', $name);
		if ($e[0] == 'group') 
			return $e[1];
	}
	return false;
}

function get_item_image_data($name, $size) {
	global $mtdata;
	$data = array();
	if ($groupname = is_group($name)) {
		$group = $mtdata['groups'][$groupname];
		prepare_images($group['image_item']);
		$data['group'] = true;
		$data['src'] = get_image_url($group['image_item'], $size);
		$data['alt'] = 'Group '.$groupname;
		$data['itemlink'] = url_group($groupname);
	} else {
		$item = $mtdata['items'][$name];
		prepare_images($item);
		$data['group'] = false;
		$data['src'] = get_image_url($item, $size);
		$data['alt'] = $item['description'];
		$data['itemlink'] = url_item($name);
	}
	return $data;
}

function render_item_image($name) {
	$data = get_item_image_data($name, 128);
	$ret = '<img class="itemimage" src="'.$data['src'].'" alt="'.$data['alt'].'"/>';
	return $ret;
}

function render_item_icon($name) {
	global $mtdata;
	if ($name) {
		$qty = null;
		$e = explode(' ', $name);
		if (count($e) > 1) {
			$name = $e[0]; $qty = $e[1];
		}
		$data = get_item_image_data($name, 32);

		$ret = '<a href="'.$data['itemlink'].'"><img class="itemicon" src="'.$data['src'].'" alt="'.$data['alt'].'"/>';
		
		if ($qty) $ret .= '<div class="itemiconqty">'.$qty.'</div>';

		if ($data['group']) $ret .= '<div class="itemicongroup">G</div>';

		$ret .= '</a>';
	}

	$ret = '<div class="itemicon">'.$ret.'</div>';
    return $ret;
}

function render_item($name) {
	global $mtdata;
	if ($name) {
		if ($groupname = is_group($name)) {
			$description = 'Group '.$groupname;
			$url = url_group($groupname);
		} else {
			$description = $mtdata['items'][$name]['description'];
			$url = url_item($name);
		}
		$ret = '<div class="item">
'.render_item_icon($name).'
<div class="itemdesc"><a href="'.$url.'">'.$description.'</a></div>
</div>';
	}
	return $ret;
}

function render_craft($craft) {

	switch($craft['type']) {
		case 'normal'     : $display = 'craft'; $method = 'Crafting'; break;
		case 'shapeless'  : $display = 'craft'; $method = 'Crafting (shapeless)'; break;
		case 'toolrepair' : $display = 'craft'; $method = 'Repairing'; break;
		case 'cooking'    : $display = 'other'; $method = 'Cooking';
						    if ($craft['cooktime']) $method .= ' ('.$craft['cooktime'].'&nbsp;sec)';
                            break;
		case 'alloy'      : $display = 'other'; $method = 'Alloying'; break;
		default : $display = 'other'; $method = $craft['type'].' (?)';
	}

	$ret = '<div class="craft"><div class="craftinput">';

	if ($display == 'craft') {
		$ret .= '<table class="craft">
<tbody><tr>
<td>'.render_item_icon($craft['inputs'][0]).'</td>
<td>'.render_item_icon($craft['inputs'][1]).'</td>
<td>'.render_item_icon($craft['inputs'][2]).'</td></tr>
<tr>
<td>'.render_item_icon($craft['inputs'][3]).'</td>
<td>'.render_item_icon($craft['inputs'][4]).'</td>
<td>'.render_item_icon($craft['inputs'][5]).'</td></tr>
<tr>
<td>'.render_item_icon($craft['inputs'][6]).'</td>
<td>'.render_item_icon($craft['inputs'][7]).'</td>
<td>'.render_item_icon($craft['inputs'][8]).'</td></tr>
</tbody></table>';
	} else {
		$ret .= '<table class="other"><tbody><tr>';
		foreach ($craft['inputs'] as $item) {
			$ret .= '<td>'.render_item_icon($item).'</td>';
		}
		$ret .= '</tr></tbody></table>';
	}

	$ret.= '</div><div class="craftmethod">'.$method.'
</div>
<div class="craftoutput">'.render_item_icon($craft['output']).'</div>
</div>';
 	return $ret;

}

function render_item_page($itemname) {
	global $mtdata;
	if (array_key_exists($itemname, $mtdata['items'])) {
		$item = $mtdata['items'][$itemname];
		$ret = '<a href="/">[index]</a>
<h1>'.$item['description'].'</h1>
<div class="itemdescription">
<table><tr><th colspan="2">'.render_item_image($itemname).'</td></tr>
<tr><th colspan="2">'.$item['description'].'</td></tr>
<tr><td><b>Item string</b></td><td>'.$item['name'].'</td></tr>';
		$e = explode(':', $item['name']);
		$ret.= '<tr><td><b>Mod</b></td><td>'.$e[0].'</td></tr>
<tr><td><b>Type</b></td><td>'.$item['type'].'</td></tr>
<tr><td><b>Groups</b></td><td>';
		if (count($item['groups'])) {
			foreach ($item['groups'] as $group => $level) {
				$ret.= '<a href="'.url_group($group).'">'.$group.'</a> ('.$level.')<br/>';
			}
		} else $ret.= "<p>None</p>";
		$ret.= '</td></tr>
</table></div>
<h2>Craft recipies</h2>';
		if (count($item['crafts']) == 0)
			$ret.= '<p>None</p>';
		else
			foreach ($item['crafts'] as $craft) {
				$ret.= '<p>'.render_craft($craft).'</p>';
			}

		$ret.= '<h2>Usages</h2>';
		if (count($item['usages']) == 0)
			$ret.= '<p>None</p>';
		else
			foreach ($item['usages'] as $craft) {
				$ret.= '<p>'.render_craft($craft).'</p>';
			}
	} else {
		$ret = '<p>Item '.$itemname.' not found !</p>';
	}
	return $ret;
}

function render_group_page($groupname) {
	global $mtdata;
	if (array_key_exists($groupname, $mtdata['groups'])) {
		$group = $mtdata['groups'][$groupname];
		$ret = '<a href="/">[index]</a>
<h1>';
		if ($group['item_image']) $ret.= render_item_icon('group:'.$groupname, 128).' ';
 		$ret.= 'Group '.$groupname.'</h1>
<h2>Items in group</h2>';
		if (count($group['items']) == 0)
			$ret.= '<p>None</p>';
		else
			foreach ($group['items'] as $itemname) {
				if ($mtdata['items'][$itemname]['description']) 
					$ret.= render_item($itemname).' ';
			}
		$ret.= '<h2>Usages</h2>';
		if (count($group['usages']) == 0)
			$ret.= '<p>None</p>';
		else
			foreach ($group['usages'] as $craft) {
				$ret.= '<p>'.render_craft($craft).'</p>';
			}

	} else {
		$ret = '<p>Group '.$groupname.' not found !</p>';
	}
	return $ret;
}

function render_item_index() {
	global $mtdata;
	$ret = "<h1>Item index</h1>";
	foreach ($mtdata['items'] as $name => $item){
		if ($item['description'] 
			&& $name != 'air'
			&& !array_key_exists('not_in_creative_inventory', $item['groups'])) {
			$ret.= render_item($name);
		}
	}
	return $ret;
}

$q=$_GET["q"];
$type=$_GET["type"];

// If a group is asked instead of an item, change to group page
if ($type == 'item' && substr($q, 0, 6) == 'group:') {
	$type == 'group'; $q = substr($q, 7);
}

$starttime  = new DateTime();

echo '<head>
<link rel="stylesheet" type="text/css" href="main.css">
</head>
<body>';

switch ($type) {
	case 'item':
		echo render_item_page($q);
		break;
	case 'group':
		echo render_group_page($q);
		break;
	case 'test':
		echo mytest();
		break;
	default:
		echo render_item_index();
}

$endtime = new DateTime( );

$diff = $starttime->diff( $endtime );

echo "Elapsed time ".$diff->format( '%H:%I:%S' );
echo '</body>';
	
?>
