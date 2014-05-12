<?php
namespace Fuel\Tasks;
class Gentrie_Redis
{
    public $redis;

    // d :development、s :staging、p :production
    public $namespace;
    public $namespace_type = array('d', 's', 'p');
    public $namespace_suffix = '::';

    public $read_file_name; //ずらっと改行されたワードが並んでいる形式
    public $prefix;
    public $terminal = '@^d';
    public $targetWords = array();

    /**
     * redisの指定したnsでtrieを作成する処理
     * 
     * @param string $namespace
     * @param string $object_prefix
     * @param string $file
     */
    public function run($namespace = 'd', $object_prefix = 'ngw', $file ='fuel/app/config/ng-word-list.txt')
    {
        //間違ったnamepaceが入るのを防止
        if( array_search($namespace, $this->namespace_type) === false ) exit();

        
        $this->namespace = $namespace;
        $this->prefix = $object_prefix;
        $this->read_file_name = $file;

        $this->redis = \Redis::forge('default');
        $this->createRecord();
    }

    private function createRedisTrie($str)
    {
        $atom = preg_split("//u" ,$str , -1 , PREG_SPLIT_NO_EMPTY);
        
        $key = $this->namespace
             . $this->namespace_suffix 
             . $this->prefix 
             . ':';
 
        $pipeline = $this->redis->pipeline();

        foreach($atom as $char){
            $this->redis->zadd($key,ord($char),$char);
            $key .= $char;
        }
        $this->redis->zadd($key, 0, $this->terminal);
        $this->redis->execute();
    }

    private function createRecord()
    {
    	//Step1.ファイル読み込み
    	$fp = fopen($this->read_file_name, 'r');
    	if($fp){
    		if(flock($fp, LOCK_SH)){
    			while(!feof($fp)){
    				$buffer = fgets($fp);
    				$buffer = mb_convert_kana($buffer, 'CKVrn');
                    $buffer = strtolower($buffer);

    				array_push($this->targetWords, trim($buffer));
    			}
    			flock($fp, LOCK_UN);
    		}else{
    			print('failed');
    			exit();
    		}
    	}
    	 
    	//Step2.Trie tree 生成
    	foreach ($this->targetWords as $str) {
    		$this->createRedisTrie($str);
    	};
    }
}
