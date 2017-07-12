<?php 
function begtoend($htmltag){
    return preg_replace('/<([A-Za-z]+)>/','</$1>',$htmltag);
}
function replace_pcre_array($text,$array){
    $pattern = array_keys($array);
    $replace = array_values($array);
    $text = preg_replace($pattern,$replace,$text);
    return $text;
}
    function ezDate($d) {
        $ts = time() - $d;
       
        if($ts>31536000) $val = round($ts/31536000,0).' year';
        else if($ts>2419200) $val = round($ts/2419200,0).' month';
        else if($ts>604800) $val = round($ts/604800,0).' week';
        else if($ts>86400) $val = round($ts/86400,0).' day';
        else if($ts>3600) $val = round($ts/3600,0).' hour';
        else if($ts>60) $val = round($ts/60,0).' minute';
        else $val = $ts.' second';
       
        if($val>1) $val .= 's';
        return $val;
    }
?>