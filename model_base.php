<?php
use \Model_Crud;

class Model_Base extends Model_Crud
{
	//trie tree構造のワードを基として、指定した文字列のマッチングを行う
	public static function match(&$trietree, $string = '')
	{
		// 文字列を配列に変換
		$end_flag = '@^d';
		$target = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
	
		for( $i=0,$l=count($target) ; $i<$l ; $i+=1 ){
			$currentNode = $trietree;
			$hit = '';
			for( $j=0 ; $i+$j < $l ; $j+=1 ){
				$char = $target[$i+$j];
				$hit .= $char;

				$currentNode = ( isset($currentNode[$char])) ? $currentNode[$char] : null;

				//マッチしないため、次の処理へ進む
				if($currentNode == null) break;

				//一致する部分が存在する場合、一致したワードを返す
				if(isset($currentNode[ $end_flag ])) return $hit;
			}
		}
		//一致する部分がない
		return false;
	}
	
	// redisのtrie treeより文字列のマッチングを行う
	// $str : マッチング対象文字列, $prefix : redisのsorted setのキーの接頭辞
	public static function matchRedis(&$redis, $str = '', $prefix = 'ngw:', $ns_redis = 'd::')
	{
	    $terminal = '@^d';
	    // 文字列を配列に変換
	    $target = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);

	    for( $i=0,$l=count($target) ; $i<$l ; $i+=1 ){
	    	$key = '';
	    	for( $j=0 ; $i+$j < $l ; $j+=1 ){
	    		$char = $target[ $i+$j ];
	    		$key .= $char;

	    		$ret = $redis->zrange($ns_redis.$prefix.$key, 0, -1);

	    		if( in_array($terminal, $ret) ) return $key;

	    		if( !count($ret) ) break;
	    	}
	    }
	    //一致する部分がない
	    return false;
	}
}
