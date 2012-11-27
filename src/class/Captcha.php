<?php
/**
 * Captcha management class
 *
 * Features:
 * - Use an ASCII font by default but can be customized
 *   (letter width should be the same)
 * - generate random strings with 5 letters
 * - convert string into captcha using the ASCII font
 */

class Captcha
{
    public $alphabet="";
    public $alphabet_font;
    public $col_font = 0;
    public $row_font = 0;

    public function __construct($alpha_font=array(
                                    'A'=>" _ |_|| |",
                                    'B'=>" _ |_)|_)",
                                    'C'=>" __|  |__",
                                    'D'=>" _ | \\|_/",
                                    'E'=>" __|__|__",
                                    'F'=>" __|_ |  ",
                                    'G'=>" __/ _\\_/",
                                    'H'=>"   |_|| |",
                                    'I'=>"___ | _|_",
                                    'J'=>"___ | _| ",
                                    'K'=>"   |_/| \\",
                                    'L'=>"   |  |__",
                                    'M'=>"_ _|||| |",
                                    'N'=>"__ | || |",
                                    'O'=>" _ / \\\\_/",
                                    'P'=>" _ |_||  ",
                                    'Q'=>" _ | ||_\\",
                                    'R'=>" _ |_|| \\",
                                    'S'=>" _ (_  _)",
                                    'T'=>"___ |  | ",
                                    'U'=>"   | ||_|",
                                    'V'=>"   \\ / v ",
                                    'W'=>"   \\ / w ",
                                    'X'=>"   \\_// \\",
                                    'Y'=>"   |_| _|",
                                    'Z'=>"___ / /__",
                                    '0'=>" _ |/||_|",
                                    '1'=>"    /|  |",
                                    '2'=>" _  _||_ ",
                                    '3'=>" _  _| _|",
                                    '4'=>"   |_|  |",
                                    '5'=>" _ |_  _|",
                                    '6'=>" _ |_ |_|",
                                    '7'=>" __  | / ",
                                    '8'=>" _ |_||_|",
                                    '9'=>" _ |_| _|"),
                                $row_font=3) {
        $this->alphabet_font = $alpha_font;

        $keys = array_keys($this->alphabet_font);

        foreach ($keys as $k) {
            $this->alphabet .= $k;
        }

        if ($keys[0]) {
            $this->row_font = $row_font;
            $this->col_font =
                (int)strlen($this->alphabet_font[$keys[0]])/$this->row_font;
        }
    }

    public function generateString($len=5) {
        $i=0;
        $str='';
        while ($i<$len) {
            $str.=$this->alphabet[mt_rand(0,strlen($this->alphabet)-1)];
            $i++;
        }
        return $str;
    }

    public function convertString($str_in) {
        $str_out="\n";
        $str_out.='<pre>';
        $str_out.="\n";
        $i=0;
        while($i<$this->row_font) {
            $j=0;
            while($j<strlen($str_in)) {
                $str_out.= substr($this->alphabet_font[$str_in[$j]],
                                  $i*$this->col_font,
                                  $this->col_font)." ";
                $j++;
            }
            $str_out.= "\n";
            $i++;
        }
        $str_out.='</pre>';
        return $str_out;
    }
}
