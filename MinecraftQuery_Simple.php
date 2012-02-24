<?php
/*
 * Queries Minecraft server (1.8+)
 * Returns array on success, false on failure.
 *
 * Written by xPaw
 *
 * Website: http://xpaw.ru
 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
 */
	
function QueryMinecraft( $IP, $Port = 25565, $Timeout = 3 ){
	$Socket = Socket_Create( AF_INET, SOCK_STREAM, SOL_TCP );
	
	Socket_Set_Option( $Socket, SOL_SOCKET, SO_SNDTIMEO, array( 'sec' => $Timeout, 'usec' => 0 ) );
	
	if( $Socket === FALSE || @Socket_Connect( $Socket, $IP, (int)$Port ) === FALSE ){
		return FALSE;
	}
	
	Socket_Send( $Socket, "\xFE", 1, 0 );
	$Len = Socket_Recv( $Socket, $Data, 256, 0 );
	Socket_Close( $Socket );
	
	if( $Len < 4 || $Data[ 0 ] != "\xFF" ){
		return FALSE;
	}
	
	$Data = SubStr( $Data, 3 );
	$Data = iconv( 'UTF-16BE', 'UTF-8', $Data );
	$Data = Explode( "\xA7", $Data );
	
	return Array(
		'HostName'   => SubStr( $Data[ 0 ], 0, -1 ),
		'Players'    => isset( $Data[ 1 ] ) ? IntVal( $Data[ 1 ] ) : 0,
		'MaxPlayers' => isset( $Data[ 2 ] ) ? IntVal( $Data[ 2 ] ) : 0
	);
}

function JsonQueryMinecraft( $IP, $Port = 25565, $Timeout = 3 ){
	return json_encode( QueryMinecraft( $IP, $Port, $Timeout ) );
}

//These make sure expected function exists and works as expected.
if(!function_exists("json_encode")){
	function json_encode($value, $options = null) {
		$_escape = function ($str) {
			return addcslashes($str, "\v\t\n\r\f\"\\/");
		};
		$out = "";
		if (is_object($value)) {
			$class_vars = get_object_vars(($value));
			$arr = array();
			foreach ($class_vars as $key => $val) {
				$arr[$key] = "\"{$_escape($key)}\":\"{$val}\"";
			}
			$val = implode(',', $arr);
			$out .= "{{$val}}";
		}elseif (is_array($value)) {
			$obj = false;
			$arr = array();
			foreach($value AS $key => $val) {
				if(!is_numeric($key)) {
					$obj = true;
				}
				$arr[$key] = json_encode($val);
			}
			if($obj) {
				foreach($arr AS $key => $val) {
					$arr[$key] = "\"{$_escape($key)}\":{$val}";
				}
				$val = implode(',', $arr);
				$out .= "{{$val}}";
			}else {
				$val = implode(',', $arr);
				$out .= "[{$val}]";
			}
		}elseif (is_bool($value)) {
			$out .= $value ? 'true' : 'false';
		}elseif (is_null($value)) {
			$out .= 'null';
		}elseif (is_string($value)) {
			$out .= "\"{$_escape($value)}\"";
		}else {
			$out .= $value;
		}
		return "{$out}";
	}
}

?>