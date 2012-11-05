<?php
class CurrencyConverter {
	private function connect_db ( $sql ){
		$conn = mysql_connect('host', 'user', 'password');
		mysql_select_db('database');
        $res=mysql_query($sql,$conn);
		mysql_close($conn);
		return $res;
	}
	public function get_rate ( $currency ) {
		$res = $this->connect_db("SELECT rate FROM rates WHERE currency = '$currency'");
		$row = mysql_fetch_array($res);
		return $row['rate'];
	}

	public function parse ( $input ) {
		$input = trim($input);
		$pos = strpos($input,' ');
		if( $pos != strrpos($input,' ')){
			return array ( 'error' => 'true', 'msg' => 'invalid input: '.$input );
		}
		return array ( 'error' => 'false', 'currency' => substr($input,0,$pos), 'amount' => substr($input,$pos+1) );
	}

	public function set_rate ( $currency, $rate ) {
		$currency = strtoupper($currency);
		$res = $this->connect_db("SELECT rate FROM rates WHERE currency = '$currency'");
		$row = mysql_fetch_array($res);
		if($row['rate']){
			$this->connect_db("UPDATE rates SET rate=$rate WHERE currency='$currency'");
		}else{
			$this->connect_db("INSERT INTO rates (currency,rate) VALUES ('$currency',$rate)");
		}
	}

	public function convert_to_usd ( $input ) {
		$result = array();
		if(gettype($input) == 'string'){
			$input = array( $input );
		}
		if(gettype($input) != 'array'){
			return array ( 'error' => 'true', 'msg' => 'invalid input' );
		}else{
			foreach( $input as $item){
				$item_parsed = $this->parse($item);
				$rate = $this->get_rate( $item_parsed['currency']);
				$usd = $rate * $item_parsed['amount'];
				array_push($result,'USD '.$usd);
			}
		}
		return $result;
	}

	public function convert_from_usd ( $currency, $amount) {
		function walk_amount (&$item,$key,$rate){
			$item = number_format($item / $rate, 2, '.', '');
		}	
		if(!$currency || !$amount){
			$return = array ( 'error' => 'true', 'msg' => 'invalid input argument' );
		}else{
			$rate = $this->get_rate( $currency );
			$amounts = explode(',',$amount);
			array_walk($amounts,'walk_amount',$rate);
			$return = array ( 'error' => 'false', 'content' => implode(',',$amounts));
		}
		return '{"error":'.$return['error'].',"content":"'.$return['content'].'"}';
	}
}

$converter = new CurrencyConverter;
echo $converter->convert_from_usd($_GET['currency'],$_GET['amount']);

//for testing each function:
//echo $converter->get_rate($_GET['currency']);
//echo $converter->set_rate($_GET['currency'],$_GET['rate']);
//var_dump($converter->parse($_GET['input']));
//var_dump($converter->convert_to_usd('JPY 10000'));
//echo '<br />';
//var_dump($converter->convert_to_usd(array('JPY 10000','AUD 2000','CZK 4000')));
?>
