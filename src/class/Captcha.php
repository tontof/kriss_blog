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
     *  __  __  __  _   ___ ___ __      ___ ___            
     * /  \|  \/  \| \ |   |   /  \|  |  |   | |  /|   |  |
     * |__||__/|   |  ||__ |__ | _ |__|  |   | |_/ |   |\/|
     * |  ||  \|   |  ||   |   |  ||  |  |   | | \ |   |  |
     * |  ||__/\__/|_/ |___|   \__/|  | _|_\_/ |  \|___|  |
     *
     *      __  __  __  __  ___ ___                    ____
     * |  |/  \|  \/  \|  \/     | |  ||  ||  |\  /|  |   /
     * |\ ||  ||__/|  ||__/\__   | |  ||  ||  | \/ \__/  / 
     * | \||  ||   | \|| \    \  | |  |\  /|/\| /\    | /  
     * |  |\__/|   \__\|  \___/  | \__/ \/ |  |/  \\__//___
     *
     *  __      __ ____    ____    ____ __  __ 
     * /  \ /| /  \   /  / |     /    //  \/  \
     * | /|/ |   _/ _/  /  |__  /_  _/_\__/\__/
     * |/ |  |  /     \/_|_   \/  \ /  /  \  / 
     * \__/ _|_/______/  | ___/\__//   \__/ /  
     *
     * @param array $alphaFont default alphabet
     * @param int   $rowFont   number of row for one letter
     */
    public function __construct(
        $alphaFont = array(
            'A' => " __ /  \\|__||  ||  |",
            'B' => " __ |  \\|__/|  \\|__/",
            'C' => " __ /  \\|   |   \\__/",
            'D' => " _  | \\ |  ||  ||_/ ",
            'E' => " ___|   |__ |   |___",
            'F' => " ___|   |__ |   |   ",
            'G' => " __ /  \\| _ |  |\\__/",
            'H' => "    |  ||__||  ||  |",
            'I' => " ___  |   |   |  _|_",
            'J' => " ___  |   |   | \\_/ ",
            'K' => "    |  /|_/ | \\ |  \\",
            'L' => "    |   |   |   |___",
            'M' => "    |  ||\\/||  ||  |",
            'N' => "    |  ||\\ || \\||  |",
            'O' => " __ /  \\|  ||  |\\__/",
            'P' => " __ |  \\|__/|   |   ",
            'Q' => " __ /  \\|  || \\|\\__\\",
            'R' => " __ |  \\|__/| \\ |  \\",
            'S' => " ___/   \\__    \\___/",
            'T' => " ___  |   |   |   | ",
            'U' => "    |  ||  ||  |\\__/",
            'V' => "    |  ||  |\\  / \\/ ",
            'W' => "    |  ||  ||/\\||  |",
            'X' => "    \\  / \\/  /\\ /  \\",
            'Y' => "    |  |\\__/   |\\__/",
            'Z' => "____   /  /  /  /___",
            '0' => " __ /  \\| /||/ |\\__/",
            '1' => "     /| / |   |  _|_",
            '2' => " __ /  \\  _/ /  /___",
            '3' => "____   / _/    \\___/",
            '4' => "      /  /  /_|_  | ",
            '5' => "____|   |__    \\___/",
            '6' => "      /  /_ /  \\\\__/",
            '7' => "____   / _/_ /  /   ",
            '8' => " __ /  \\\\__//  \\\\__/",
            '9' => " __ /  \\\\__/  /  /  ",
        ),
        $rowFont = 5
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
    public function generateString($len = 7)
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
