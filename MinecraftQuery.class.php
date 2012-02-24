<?php

class MinecraftQuery{
	/*
	 * Class written by xPaw
	 *
	 * Website: http://xpaw.ru
	 * GitHub: https://github.com/xPaw/PHP-Minecraft-Query
	 */

	private $Socket;
	private $Challenge;
	private $Players;
	private $Info;
	private $RawInfo;
	private $Error;

	public function Connect( $Ip, $Port = 25565, $Timeout = 3 ){
		if( $this->Socket = FSockOpen( 'udp://' . $Ip, (int)$Port ) ){
			Socket_Set_TimeOut( $this->Socket, $Timeout );

			if( !$this->GetChallenge( ) ){
				FClose( $this->Socket );
				$this->Error="Failed to receive challenge.";
				return false;
			}

			if( !$this->GetStatus( ) ){
				FClose( $this->Socket );
				$this->Error="Failed to receive status.";
				return false;
			}

			FClose( $this->Socket );
		}else{
			$this->Error="Can't open connection.";
			return false;
		}
		return true;
	}

	public function getError( ){
		return isset( $this->Error ) ? $this->Error : false;
	}

	public function GetRawInfo( ){
		return isset( $this->RawInfo ) ? $this->RawInfo : false;
	}

	public function GetInfo( ){
		return isset( $this->Info ) ? $this->Info : false;
	}

	public function GetJsonInfo( ){
		return json_encode( $this->GetInfo() );
	}

	public function GetPlayers( ){
		return isset( $this->Players ) ? $this->Players : false;
	}

	public function GetJsonPlayers( ){
		return json_encode( $this->GetPlayers() );
	}

	private function GetChallenge( ){
		$Data = $this->WriteData( "\x09" );

		if( !$Data ){
			return false;
		}

		$this->Challenge = Pack( 'N', $Data );

		return true;
	}

	private function GetStatus( ){
		$Data = $this->WriteData( "\x00", $this->Challenge . "\x01\x02\x03\x04" );

		if( !$Data ){
			return false;
		}

		$Last = "";
		$Info = Array( );
		$this->RawInfo=$Data;

		$Data    = SubStr( $Data, 11 ); // splitnum + 2 int
		$Data    = Explode( "\x00\x00\x01player_\x00\x00", $Data );
		$Players = SubStr( $Data[ 1 ], 0, -2 );
		$Data    = Explode( "\x00", $Data[ 0 ] );

		// Array with known keys in order to validate the result
		// It can happen that server sends custom strings containing bad things (who can know!)
		$Keys = Array(
			'hostname'   => 'HostName',
			'gametype'   => 'GameType',
			'version'    => 'Version',
			'plugins'    => 'Plugins',
			'map'        => 'Map',
			'numplayers' => 'Players',
			'maxplayers' => 'MaxPlayers',
			'hostport'   => 'HostPort',
			'hostip'     => 'HostIp'
		);

		foreach( $Data as $Key => $Value ){
			if( ~$Key & 1 ){
				if( !Array_Key_Exists( $Value, $Keys ) )
				{
					$Last = false;
					continue;
				}

				$Last = $Keys[ $Value ];
				$Info[ $Last ] = "";
			}
			else if( $Last != false ){
				// TODO: Filter html vars, potential security "exploits"?

				$Info[ $Last ] = $Value;
			}
		}

		// Ints
		$Info[ 'Players' ]    = IntVal( $Info[ 'Players' ] );
		$Info[ 'MaxPlayers' ] = IntVal( $Info[ 'MaxPlayers' ] );
		$Info[ 'HostPort' ]   = IntVal( $Info[ 'HostPort' ] );

		// Parse "plugins", if any
		if( $Info[ 'Plugins' ] ){
			$Data = Explode( ": ", $Info[ 'Plugins' ], 2 );

			$Info[ 'RawPlugins' ] = $Info[ 'Plugins' ];
			$Info[ 'Software' ]    = $Data[ 0 ];

			if( Count( $Data ) == 2 )
			{
				$Info[ 'Plugins' ] = Explode( "; ", $Data[ 1 ] );
			}
		}else{
			$Info[ 'Software' ] = 'Vanilla';
		}

		$this->Info = $Info;

		if( $Players ){
			$this->Players = Explode( "\x00", $Players );
		}

		return true;
	}

	private function WriteData( $Command, $Append = "" ){
		$Command = "\xFE\xFD" . $Command . "\x01\x02\x03\x04" . $Append;
		$Length  = StrLen( $Command );

		if( $Length !== FWrite( $this->Socket, $Command, $Length ) )
		{
			return false;
		}

		$Data = FRead( $this->Socket, 1440 );

		if( StrLen( $Data ) < 5 || $Data[ 0 ] != $Command[ 2 ] ){
			return false;
		}

		return SubStr( $Data, 5 );
	}
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