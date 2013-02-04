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
    public $alphabetFont;
    public $colFont = 0;
    public $rowFont = 0;

    /**
     * Constructor
     *
     *  _   _   __  _   __  __  __     ___ ___         _ _ 
     * |_| |_) |   | \ |__ |_  / _ |_|  |   |  |_/ |   ||| 
     * | | |_) |__ |_/ |__ |   \_/ | | _|_ _|  | \ |__ | | 
     *
     * __   _   _   _   _   __ ___                     ___ 
     * | | / \ |_| | | |_| (_   |  | | \ / \ / \_/ |_|  /  
     * | | \_/ |   |_\ | \ __)  |  |_|  v   w  / \  _| /__ 
     *
     *  _       _   _       _   _   __  _   _  
     * |/|  /|  _|  _| |_| |_  |_    | |_| |_| 
     * |_|   | |_   _|   |  _| |_|  /  |_|  _| 
     *
     * @param array $alphaFont default alphabet
     * @param int   $rowFont   number of row for one letter
     */
    public function __construct(
        $alphaFont = array(
            'A' => " _ |_|| |",
            'B' => " _ |_)|_)",
            'C' => " __|  |__",
            'D' => " _ | \\|_/",
            'E' => " __|__|__",
            'F' => " __|_ |  ",
            'G' => " __/ _\\_/",
            'H' => "   |_|| |",
            'I' => "___ | _|_",
            'J' => "___ | _| ",
            'K' => "   |_/| \\",
            'L' => "   |  |__",
            'M' => "_ _|||| |",
            'N' => "__ | || |",
            'O' => " _ / \\\\_/",
            'P' => " _ |_||  ",
            'Q' => " _ | ||_\\",
            'R' => " _ |_|| \\",
            'S' => " __(_ __)",
            'T' => "___ |  | ",
            'U' => "   | ||_|",
            'V' => "   \\ / v ",
            'W' => "   \\ / w ",
            'X' => "   \\_// \\",
            'Y' => "   |_| _|",
            'Z' => "___ / /__",
            '0' => " _ |/||_|",
            '1' => "    /|  |",
            '2' => " _  _||_ ",
            '3' => " _  _| _|",
            '4' => "   |_|  |",
            '5' => " _ |_  _|",
            '6' => " _ |_ |_|",
            '7' => " __  | / ",
            '8' => " _ |_||_|",
            '9' => " _ |_| _|",
        ),
        $rowFont = 3
    )
    {
        $this->alphabetFont = $alphaFont;

        $keys = array_keys($this->alphabetFont);

        foreach ($keys as $k) {
            $this->alphabet .= $k;
        }

        if ($keys[0]) {
            $this->rowFont = $rowFont;
            $this->colFont = (int) strlen($this->alphabetFont[$keys[0]])/$this->rowFont;
        }
    }

    /**
     * Generate a random string for captcha
     * 
     * @param int $len length of the generated string (default : 5)
     *
     * @return string $str generated string
     */
    public function generateString($len = 5)
    {
        $i = 0;
        $str = '';
        while ($i < $len) {
            $str .= $this->alphabet[mt_rand(0, strlen($this->alphabet) - 1)];
            $i++;
        }

        return $str;
    }

    /**
     * Convert a string into captcha
     * 
     * @param string $strIn String to convert into captcha
     *
     * @return string $strOut Captcha corresponding to the given string
     */
    public function convertString($strIn)
    {
        $strOut="\n";
        $strOut.='<pre>';
        $strOut.="\n";
        $i=0;
        while ($i<$this->rowFont) {
            $j=0;
            while ($j<strlen($strIn)) {
                $strOut.= substr(
                    $this->alphabetFont[$strIn[$j]],
                    $i*$this->colFont,
                    $this->colFont)." ";
                $j++;
            }
            $strOut.= "\n";
            $i++;
        }
        $strOut.='</pre>';

        return $strOut;
    }
}
