<?php

namespace Grypho\SecurityBundle\Lib;
/**
 * Description of PasswordGenerator
 *
 * @author cschumann
 */
class PasswordGenerator {
    public static function GeneratePassword(){
        $pw = '';
        $c  = 'bcdfghjklmnprstvwz'; //consonants except hard to speak ones
        $v  = 'aeiou';              //vowels
        $a  = $c.$v;                //both

        srand();

        //use two syllables...
        for($i=0;$i < 2; $i++)
        {
            $pw .= $c[rand(0, strlen($c)-1)];
            $pw .= $v[rand(0, strlen($v)-1)];
            $pw .= $a[rand(0, strlen($a)-1)];
        }
        
        //... and add a nice number
        $pw .= rand(10,99);

        return $pw;
    }
}
