<?
class Sorter
{
	/*
	A collection of functions to allow sort capabilities that are expandable
	The idea is, you construct your query, use 
		generateOrderBy		to get the Order By clause
		sortButton			to generate the Button
	*/

	var $downImgUrl = '/images/downArrowCircle.png';
	var $upImgUrl = '/images/upArrowCircle.png';
	function Sorter($sortButtonArray=array()){
		$this->sortButtonArray = $sortButtonArray;
		
		$Order = array();

		//Build Order
		foreach ($_GET as $gk=>$gv){
			$m = preg_match('%Order(?P<type>By|Direction)_(?P<num>\d+)%', $gk, $matches);
			if($m){
				$Order[$matches['num']]['order'.$matches['type']] = $gv;
			}
		}
		
		$this->Order = $Order;
		
		if($_GET['OrderAdd'] && $_GET['OrderAdd'] != ""){
			$num = sizeOf($Order);
			header('Location:'.$this->changeGet(array(
				'OrderBy_'.$num=>$_GET['OrderAdd'],
				'OrderDirection_'.$num=>'ASC',
			), true, array('OrderAdd') ) );
		}
		
		if($_GET['OrderRemove'] && $_GET['OrderRemove'] != ""){
			//This just pops off the last one, whatever it is.
			$num = sizeOf($Order)-1;
			if($num >= 0){
				$numKeys = array_keys($Order);
				$num = $numKeys[$num];
				header('Location:'.$this->changeGet(array(), false, array(
					'OrderBy_'.$num, 'OrderDirection_'.$num, 'OrderRemove'
				)
				)
				);
			}
		}

	}
	
	function generateOrderBy(){
		$str = "";
		foreach($this->Order as $ok=>$ov){
			$str .= ", ".$ov['orderBy'].' '.$ov['orderDirection'];
		}
		if(strlen($str) > 2){
			$str = substr($str, 2);
		}
		if($str == ""){
			//default Ordering
			$str = array_keys($this->sortButtonArray);
			$str = $str[0].' ASC';
		}
		return $str;
	}
	
	

	public static function changeGet($get_array, $append=true, $remove_array=array(), $url=false){
		/*
		$get_array			The associative array containing the key
							to add or replace in the real GET array
							, the value of which is the desired value
							or given key
		$append				Specifies whether or not you would want to
							add to the get array. If true, it will add
							if the key doen't exist, otherwise it replaces
		$remove_array		Remove all entries with given key.
		
		Example:
		$this->changeGet(array(
				'OrderBy_'.$num=>$_GET['OrderAdd'],
				'OrderDirection_'.$num=>'ASC',
			), true, array('OrderAdd') ) );
		This will ensure that OrderBy_.$num does equal $_GET['OrderAdd']
			(And that OrderDirection.$num='ASC')
		If it doesn't exist it will be created, if it does then it will be replaced
		Also, this will delete $_GET['OrderAdd']
		*/
		if($url == false){
			$url = $_SERVER['PHP_SELF'];
		}
		$gets = $_GET;
		foreach($get_array as $gk=>$gv){
			if( (!$gets[$gk] and $append==true) | (in_array($gk, array_keys($gets) )) ){
				$gets[$gk] = $gv;
			}
		}
		
		foreach($remove_array as $r){
			unset($gets[$r]);
		}
		
		//Need to ouput url
		return $url.'?'.http_build_query($gets);
	}

	function sortButton($showName, $GetName){
		global $CFG;
		
		$sorted = $sortNo = -1;
		foreach($this->Order as $ok=>$ov){
			if($ov['orderBy']==$GetName){
				$sorted=$ok;
				break;
			}
		}
		/*
		Since this is only a one-dimensional sort, I can assume this is the only one that's
		sorted, and on click (name plus button) it jsut switches the orderDirection
		*/
		
		$directionImg = "";
		if($sorted >=0 ){
			$sortNo = $sorted;
			$sorted = $this->Order[$sortNo];
			if($sorted['orderDirection'] == 'ASC'){
				$directionImg = '&nbsp;<img height=10 src="'.$this->downImgUrl.'" />';
			}else if($sorted['orderDirection'] == 'DESC'){
				$directionImg = '&nbsp;<img height=10 src="'.$this->upImgUrl.'" />';
			}
		}
		$ReverseDirection = ($sorted['orderDirection'] == 'DESC')?'ASC':'DESC';
		
		if($sortNo < 0){
			$sortNo = sizeOf($Order);
		}
		$changeDirection = $this->changeGet(array(
			"OrderBy_".$sortNo=>$GetName,
			"OrderDirection_".$sortNo=>$ReverseDirection,
		) , true);
		$addToOrder = $removeFromOrder = "";
		
		
		return "<a href=\"$changeDirection\">$showName $directionImg</a>";
	}

}

class MultiSort extends Sorter
{
	
	function MultiSort($sortButtonArray=array()){
		parent::Sorter($sortButtonArray);
	}
	
	function sortButton($showName, $GetName){
		global $CFG;
		
		$sorted = $sortNo = -1;
		foreach($this->Order as $ok=>$ov){
			if($ov['orderBy']==$GetName){
				$sorted=$ok;
				break;
			}
		}
		
		$directionImg = "";
		if($sorted >=0 ){
			$sortNo = $sorted;
			$sorted = $this->Order[$sortNo];
			if($sorted['orderDirection'] == 'ASC'){
				$directionImg = '&nbsp;<img height=10 src="'.$CFG->baseroot.'/images/site/downArrowCircle.png" />';
			}else if($sorted['orderDirection'] == 'DESC'){
				$directionImg = '&nbsp;<img height=10 src="'.$CFG->baseroot.'/images/site/upArrowCircle.png" />';
			}
		}
		$ReverseDirection = ($sorted['orderDirection'] == 'DESC')?'ASC':'DESC';
		
		if($sortNo < 0){
			$sortNo = sizeOf($Order);
		}
		$changeDirection = $this->changeGet(array(
			"OrderDirection_".$sortNo=>$ReverseDirection,
		) , false); //I only want to replace if it exists
		$addToOrder = $removeFromOrder = "";
		
		if($sorted >= 0){
			//It is currently one of the sorted columns
			$sortNo ++;
		}else{
			//it is not in the Order book
			$url = $this->changeGet(array(
				"OrderAdd"=>$GetName,
			));
			$addToOrder = "<a href='$url'>+</a>";
			$sortNo = "";
		}
		
		return "$showName $link<a href=\"$changeDirection\">$directionImg</a>&nbsp;|&nbsp;$addToOrder$sortNo";//$removeFromOrder";
	}
	
	function showSort(){
		?>
		<p>
		<b>Sorting:</b> <?
		foreach($this->Order as $ok=>$ov){
			echo $this->sortButtonArray[$ov['orderBy']];
			if($ok < sizeOf($this->Order)-1){
				echo ' > ';
			}
		}

		echo ' - <a href="'.$this->changeGet(array(
			"OrderRemove"=>1
		), true).'">Remove Last Sort</a>';

		?>
		</p>
		<?
	}
}
?>