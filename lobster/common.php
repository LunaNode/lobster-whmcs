<?php

function lobster_mysql_query_safe($query, array $params = array()) {
	if (!empty($params)) {
		// there is possibility to use % sign in query - this line escapes it!
		$query = str_replace('%', '%%', $query);

		foreach ($params as $k => $p) {
			if ($p === null) {
				$query = preg_replace('/\?/', 'NULL', $query, 1);
				unset($params[$k]);
			} elseif (is_int($p) || is_float($p)) {
				$query = preg_replace('/\?/', $p, $query, 1);
				unset($params[$k]);
			} else {
				$query = preg_replace('/\?/', "'%s'", $query, 1);
			}
		}
		foreach ($params as &$v)
			$v = mysql_real_escape_string($v);

		$sql_query = vsprintf(str_replace("?", "'%s'", $query), $params);

		$sql_query = mysql_query($sql_query);
	} else {
		$sql_query = mysql_query($query);
	}

	$err = mysql_error();
	if (!$sql_query && $err) {
		throw new Exception($err);
	}
	return ($sql_query);
}

function lobster_customFieldExists($relid, $fieldname) {
	$result = lobster_mysql_query_safe("SELECT COUNT(*) FROM tblcustomfields WHERE `relid` = ? AND `type` = 'product' AND `fieldname` LIKE ?", array($relid, (strpos($fieldname, '|') ? $fieldname . '|%' : $fieldname . '%')));
	$row = mysql_fetch_array($result);
	return $row[0] > 0;
}

function lobster_customFieldSet($relid, $fieldname, $serviceid, $value) {
	if(lobster_customFieldExists($relid, $fieldname)) {
		$result = lobster_mysql_query_safe("SELECT `id` FROM tblcustomfields WHERE `relid` = ? AND `type` = 'product' AND `fieldname` LIKE ?", array($relid, (strpos($fieldname, '|') ? $fieldname . '|%' : $fieldname . '%')));
		$row = mysql_fetch_array($result);
		lobster_mysql_query_safe('DELETE FROM tblcustomfieldsvalues WHERE `fieldid` = ? AND `relid` = ?', array($row[0], $serviceid));
		lobster_mysql_query_safe('INSERT INTO tblcustomfieldsvalues (fieldid, relid, value) VALUES (?, ?, ?)', array($row[0], $serviceid, $value));
	}
}

function lobster_redirect($url, $get = array(), $statusCode = 303) {
	global $config;
	$get_string = '';

	foreach($get as $k => $v) {
		if(!empty($get_string) || strpos($url, '?') !== false) {
			$get_string .= '&';
		} else {
			$get_string .= '?';
		}

		$get_string .= urlencode($k);
		$get_string .= '=';
		$get_string .= urlencode($v);
	}

	header('Location: ' . $url . $get_string, true, $statusCode);
	exit;
}

?>
