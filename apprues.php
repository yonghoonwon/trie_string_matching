<?php
class AppRules
{
    //NGワード有無のチェック
    public function _validation_is_include_illegal_word($val)
    {
        $redis = null;
        $redis_type = "default";
        $data_prefix = 'ngw:';
        $needless = array( "\r\n", "\n", "\r", "\t", "　", " " );
        $target = str_replace( $needless, '', $val );
        $target = mb_convert_kana($target, 'CKVrn');
        $target = strtolower($target);

        //RedisのExceptionが発生した場合はMongoからチェックを行う
        try {
            $redis = Model_Base::get_redis_instance($redis_type);
            $redis_ns = \Config::get('db.redis.' . $redis_type . '.ns_prefix');

            $ret = Model_Base::matchRedis($redis, $target, $data_prefix, $redis_ns);
        } catch(RedisException $e) {
            $ngWordList = Model_Trie::get_one(array('name'=>'ngwords'));
            $ret = Model_Base::match($ngWordList['data'], $target);
            unset($ngWordList);
        }
        return !$ret;
    }
}
