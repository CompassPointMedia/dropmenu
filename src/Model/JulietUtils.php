<?php

namespace Compasspointmedia\Julietmenu\Model;



class JulietUtils {

    /**
     * matches a pattern which can be a string or array, against a string or an array.
     *
     * @param mixed $pattern
     * @param mixed $against
     * @param array $options
     * @return array|bool
     */
    static function match($pattern, $against, $options = []){
        /*
         * Matching a string against a string should be straightforward
         * If pattern:string matches against:array then either the first matching index is returned, or an array of matching "against" indexes
         * If pattern:array matches against:string then either the first matching index is returned, or an array of matching pattern indexes
         * If both pattern and against are arrays, then multiple match information about pattern will be discarded; it will behave as pattern:string vs. against:array; you won't know which of the patterns matched which of the "against's"
         *
         */
        extract($options);
        #print_r('match: '. "\n");
        #var_dump($pattern);
        #var_dump($against);

        $return = (empty($return) ? 'string' : 'array');        // default string.
        if(is_array($against)){
            // pattern vs. array.
            $indexes = [];
            foreach($against as $index => $string) {
                if(self::match($pattern, $string, $options) !== false){
                    // This will allow the caller to select the best match
                    $indexes[] = $index;
                }
            }
            if($indexes) {
                if($return === 'array') {
                    return $indexes;
                }else{
                    // string.
                    if(count($indexes) > 1 ) {
                        // what do we do with this?
                    }
                    return current($indexes);
                }
            }
        } else {
            // pattern vs. string.
            if (is_array($pattern)) {
                $indexes = [];
                foreach ($pattern as $index => $string) {
                    if (self::match($string, $against, $options) !== false) {
                        // Simply return true; caller knows what the pattern is
                        $indexes[] = $index;
                    }
                }
                if($indexes) {
                    if($return === 'array') {
                        return $indexes;
                    }else{
                        // string.
                        if(count($indexes) > 1 ) {
                            // what do we do with this?
                        }
                        return current($indexes);
                    }
                }
            } else {
                // final string to string comparison
                $pattern = strtolower($pattern);
                $against = strtolower($against);
                if ($pattern === $against && strlen($pattern)) {
                    return true;
                }
            }
        }
        return false;
    }
}
