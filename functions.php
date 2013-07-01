<?php

/*
 * selects two random words from the dictionary
 * @return String containing password
 */

function generate_random_password()
{
    //load dictionary
    $words = array();
    $handle = fopen('wordsEn.txt', 'r');
    while(!feof($handle))
    {    
        $words[] = trim(fgets($handle));
    }
    
    $rand1 = rand(0, sizeof($words) - 1);
//    $rand2 = rand(0, sizeof($words) - 1);
    
    return $words[$rand1];
}

/*
 * Makes sure the password has 1 uppercase letter and number and is at least 8 characters
 * @param unsecure password
 * @return secure password
 */

function secure_password($password)
{
    //capitalize first available letter
    for($i = 0; $i < strlen($password); $i++)
    {
        if(!is_numeric($password[$i]))
            $password[$i] = strtoupper($password[$i]);
        break; 
    }
    
    //replace up to 2 letters with numbers
    $replacables = array(
        'a' => '4',
        'e' => '3',
        'l' => '1',
        'o' => '0',
        't' => '7',
    );
    
    $replace_count = 0;
    foreach($replacables as $key => $value)
    {
        if($replace_count >= 1)
            break;
        if(strpos($password, $key) !== false)
        {
            $password = preg_replace('/'.$key.'/', $value, $password, 1);
            $replace_count++;
        }
        
    }
    
    //check if password contains a number. If not, append one
    $numbers = 0;
    for($i = 0; $i < strlen($password); $i++)
    {
        if(is_numeric($password[$i]))
            $numbers++;
    }
    
     //if($numbers == 0)
    $password .= rand();
    
    //if password is less than 8 characters, append numbers 
    while(strlen($password) < 8)
    {
        $password .= rand(0, 9);
    }
    
    return $password;
        
}

/*
 * Get word associations by using the DuckDuckGo API
 * @param keyword 
 * @return associated keywords
 * Format: [keywords, link]
 */

function get_associations($keyword)
{
    $request = 'http://api.duckduckgo.com/?q=' . urlencode($keyword) . '&format=json';   
    $response = file_get_contents($request);
    $returned_data = json_decode($response);
    //print_r($returned_data);
    if(empty($returned_data->RelatedTopics)) //Houston, we have a problem
    {
        $related = $keyword;
//        $url = 'http://duckduckgo.com/?q=' . urlencode($keyword);
        //return [$related, $url];
    }
    else
        $related = $returned_data->RelatedTopics[0]->Text;
//    $url = $returned_data->RelatedTopics[0]->FirstURL;

//get the url from bing
    if(strlen($related) > 20)
        $cutoff = 20;
    else
        $cutoff = strlen($related);
    $url = bing_first_url(substr($related, 0, $cutoff));

    return [$related, $url];    


    //return $returned_data;
}

/*
 * Seaches up the query using bing and returns the first url. 
 */
function bing_first_url($query)
{
    $key = '6ZW7mTOX7dxFNjJT9jP7y2+oKv9lcc76Q3xr+DEireU=';
    $query = "'" . urlencode($query) . "'";
    $request = "https://api.datamarket.azure.com/Bing/Search/Web?\$format=json&Query=$query";
    $auth = base64_encode("$key:$key");
    $data = array(
      'http'=>array(
          'request_fulluri' => true,
          'ignore_errors' => true,
          'header' => "Authorization: Basic $auth"
      )  
    );
    
    $context = stream_context_create($data);
    
    $jsonObj = json_decode(file_get_contents($request, 0, $context));
    $url = $jsonObj->d->results[0]->Url;
    return $url;
}


/*
 * Gets two keywords from a url.
 * Format [keyword1, keyword2]
 */
function extract_keywords($url)
{
    $url = filter_var($url, FILTER_SANITIZE_URL);
    $key = '60b94df7c23924d5ec6209e13e053640df2a1828';
    $request = file_get_contents("http://access.alchemyapi.com/calls/url/URLGetRankedKeywords?apikey=$key&url=$url&maxRetrieve=2&keywordExtractMode=strict&outputMode=json");
    
    $jsonObj = json_decode($request);
    //print_r($jsonObj->keywords[0]->text, $jsonObj->keywords[1]->text);
    if(!empty($jsonObj->keywords[1]->text))
        return [$jsonObj->keywords[0]->text, $jsonObj->keywords[1]->text];
    else {
        return ['undefined' , 'undefined'];
    }
}

/**
 * Remove HTML tags, including invisible text such as style and
 * script code, and embedded objects.  Add line breaks around
 * block-level tags to prevent word joining after tag removal.
 */
function strip_html_tags( $text )
{
    $text = preg_replace(
        array(
          // Remove invisible content
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<object[^>]*?.*?</object>@siu',
            '@<embed[^>]*?.*?</embed>@siu',
            '@<applet[^>]*?.*?</applet>@siu',
            '@<noframes[^>]*?.*?</noframes>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu',
            '@<noembed[^>]*?.*?</noembed>@siu',
          // Add line breaks before and after blocks
            '@</?((address)|(blockquote)|(center)|(del))@iu',
            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
            '@</?((table)|(th)|(td)|(caption))@iu',
            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
            '@</?((frameset)|(frame)|(iframe))@iu',
        ),
        array(
            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
            "\n\$0", "\n\$0",
        ),
        $text );
    return strip_tags( $text );
}

/**
 * Strip punctuation from text.
 */
function strip_punctuation( $text )
{
    $urlbrackets    = '\[\]\(\)';
    $urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
    $urlspaceafter  = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
    $urlall         = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;
 
    $specialquotes  = '\'"\*<>';
 
    $fullstop       = '\x{002E}\x{FE52}\x{FF0E}';
    $comma          = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep        = '\x{066B}\x{066C}';
    $numseparators  = $fullstop . $comma . $arabsep;
 
    $numbersign     = '\x{0023}\x{FE5F}\x{FF03}';
    $percent        = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
    $prime          = '\x{2032}\x{2033}\x{2034}\x{2057}';
    $nummodifiers   = $numbersign . $percent . $prime;
 
    return preg_replace(
        array(
        // Remove separator, control, formatting, surrogate,
        // open/close quotes.
            '/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u',
        // Remove other punctuation except special cases
            '/\p{Po}(?<![' . $specialquotes .
                $numseparators . $urlall . $nummodifiers . '])/u',
        // Remove non-URL open/close brackets, except URL brackets.
            '/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u',
        // Remove special quotes, dashes, connectors, number
        // separators, and URL characters followed by a space
            '/[' . $specialquotes . $numseparators . $urlspaceafter .
                '\p{Pd}\p{Pc}]+((?= )|$)/u',
        // Remove special quotes, connectors, and URL characters
        // preceded by a space
            '/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u',
        // Remove dashes preceded by a space, but not followed by a number
            '/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u',
        // Remove consecutive spaces
            '/ +/',
        ),
        ' ',
        $text );
}

/**
 * Strip symbols from text.
 */
function strip_symbols( $text )
{
    $plus   = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
    $minus  = '\x{2012}\x{208B}\x{207B}';
 
    $units  = '\\x{00B0}\x{2103}\x{2109}\\x{23CD}';
    $units .= '\\x{32CC}-\\x{32CE}';
    $units .= '\\x{3300}-\\x{3357}';
    $units .= '\\x{3371}-\\x{33DF}';
    $units .= '\\x{33FF}';
 
    $ideo   = '\\x{2E80}-\\x{2EF3}';
    $ideo  .= '\\x{2F00}-\\x{2FD5}';
    $ideo  .= '\\x{2FF0}-\\x{2FFB}';
    $ideo  .= '\\x{3037}-\\x{303F}';
    $ideo  .= '\\x{3190}-\\x{319F}';
    $ideo  .= '\\x{31C0}-\\x{31CF}';
    $ideo  .= '\\x{32C0}-\\x{32CB}';
    $ideo  .= '\\x{3358}-\\x{3370}';
    $ideo  .= '\\x{33E0}-\\x{33FE}';
    $ideo  .= '\\x{A490}-\\x{A4C6}';
 
    return preg_replace(
        array(
        // Remove modifier and private use symbols.
            '/[\p{Sk}\p{Co}]/u',
        // Remove mathematics symbols except + - = ~ and fraction slash
            '/\p{Sm}(?<![' . $plus . $minus . '=~\x{2044}])/u',
        // Remove + - if space before, no number or currency after
            '/((?<= )|^)[' . $plus . $minus . ']+((?![\p{N}\p{Sc}])|$)/u',
        // Remove = if space before
            '/((?<= )|^)=+/u',
        // Remove + - = ~ if space after
            '/[' . $plus . $minus . '=~]+((?= )|$)/u',
        // Remove other symbols except units and ideograph parts
            '/\p{So}(?<![' . $units . $ideo . '])/u',
        // Remove consecutive white space
            '/ +/',
        ),
        ' ',
        $text );
}

/**
 * Strip numbers from text.
 */
function strip_numbers( $text )
{
    $urlchars      = '\.,:;\'=+\-_\*%@&\/\\\\?!#~\[\]\(\)';
    $notdelim      = '\p{L}\p{M}\p{N}\p{Pc}\p{Pd}' . $urlchars;
    $predelim      = '((?<=[^' . $notdelim . '])|^)';
    $postdelim     = '((?=[^'  . $notdelim . '])|$)';
 
    $fullstop      = '\x{002E}\x{FE52}\x{FF0E}';
    $comma         = '\x{002C}\x{FE50}\x{FF0C}';
    $arabsep       = '\x{066B}\x{066C}';
    $numseparators = $fullstop . $comma . $arabsep;
    $plus          = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
    $minus         = '\x{2212}\x{208B}\x{207B}\p{Pd}';
    $slash         = '[\/\x{2044}]';
    $colon         = ':\x{FE55}\x{FF1A}\x{2236}';
    $units         = '%\x{FF05}\x{FE64}\x{2030}\x{2031}';
    $units        .= '\x{00B0}\x{2103}\x{2109}\x{23CD}';
    $units        .= '\x{32CC}-\x{32CE}';
    $units        .= '\x{3300}-\x{3357}';
    $units        .= '\x{3371}-\x{33DF}';
    $units        .= '\x{33FF}';
    $percents      = '%\x{FE64}\x{FF05}\x{2030}\x{2031}';
    $ampm          = '([aApP][mM])';
 
    $digits        = '[\p{N}' . $numseparators . ']+';
    $sign          = '[' . $plus . $minus . ']?';
    $exponent      = '([eE]' . $sign . $digits . ')?';
    $prenum        = $sign . '[\p{Sc}#]?' . $sign;
    $postnum       = '([\p{Sc}' . $units . $percents . ']|' . $ampm . ')?';
    $number        = $prenum . $digits . $exponent . $postnum;
    $fraction      = $number . '(' . $slash . $number . ')?';
    $numpair       = $fraction . '([' . $minus . $colon . $fullstop . ']' .
        $fraction . ')*';
 
    return preg_replace(
        array(
        // Match delimited numbers
            '/' . $predelim . $numpair . $postdelim . '/u',
        // Match consecutive white space
            '/ +/u',
        ),
        ' ',
        $text );
}

function extractCommonWords($string){
      $stopWords = array('i','a','about','an','and','are','as','at','be','by',
          'com','de','domain','en', 'enter', 'feedback','first', 'for','from','help',
          'how','in','is','it','la','more', 'most', 'of','on','or',
          'prev','resultl','regionsite','search','that','the','this','to','was', 'within', 
          'what','when','where','who','will','with',
          'und','the','www');
   
      $string = preg_replace('/\s\s+/i', '', $string); // replace whitespace
      $string = trim($string); // trim the string
      $string = preg_replace('/[^a-zA-Z0-9 -]/', '', $string); // only take alphanumerical characters, but keep the spaces and dashes too…
      $string = strtolower($string); // make it lowercase
   
      preg_match_all('/\b.*?\b/i', $string, $matchWords);
      $matchWords = $matchWords[0];
      
      foreach ( $matchWords as $key=>$item ) {
          if ( $item == '' || in_array(strtolower($item), $stopWords) || strlen($item) <= 3 ) {
              unset($matchWords[$key]);
          }
      }   
      $wordCountArr = array();
      if ( is_array($matchWords) ) {
          foreach ( $matchWords as $key => $val ) {
              $val = strtolower($val);
              if ( isset($wordCountArr[$val]) ) {
                  $wordCountArr[$val]++;
              } else {
                  $wordCountArr[$val] = 1;
              }
          }
      }
      arsort($wordCountArr);
      $wordCountArr = array_slice($wordCountArr, 0, 2);
      return $wordCountArr;
} 

?>
