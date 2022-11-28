<?php
namespace ThaiUtilities;
use \Exception;


/**
 * Class ThaiRender
 * */
class ThaiRender
{
    private static $Twig;
    private static $DTO = [];
    private static $Content = [];
    private static $ClassName;
    private static $Class;
    private static $params;
    private static $fileTwig;
    private static $Config;


    /**
     * @param $ConfigCMS
     * @throws Exception
     * @return $this
     */
    public function __construct( $ConfigCMS )
    {
        $this::$Config = $ConfigCMS;
        return $this;
    }

    /**
     * @param $MyArrayDTO
     * @return ThaiRender
     */
    public function setDTO($MyArrayDTO): ThaiRender
    {
        $this::$DTO = $MyArrayDTO;
        return $this;
    }

    /**
     * @param null $ClassName
     * @return \Twig\Environment
     */
    public function getTwig($ClassName = null): \Twig\Environment
    {
        if($this::$ClassName && $ClassName === null){
            $ClassName = $this::$ClassName;
        }

        $this::$Twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader($this::$Config->getTwigDir()), ['cache' => $this::$Config->isTwigCacheEnabled()]);
        $this::$Twig->addFilter(new \Twig\TwigFilter('json', function ($array) {
            return json_encode($array ?: [],JSON_UNESCAPED_UNICODE);
        }));
        $this::$Twig->addFilter(new \Twig\TwigFilter('assetic', function ($string) {

            $dirAsset =  'projects'.'/'.$_SERVER['HTTP_HOST'].'/asset';
            $pattern  = "/((@import\s+[\"'`]([\w:?=@&\/#._;-]+)[\"'`];)|";
            $pattern .= "(:\s*url\s*\([\s\"'`]*([\w:?=@&\/#._;-]+)";
            $pattern .= "([\s\"'`]*\))|<[^>]*\s+(src|href|url)\=[\s\"'`]*";
            $pattern .= "([\w:?=@&\/#._;-]+)[\s\"'`]*[^>]*>))/i";

            preg_match_all($pattern,$string,$matches);

            if(!empty($matches[8]) && count($matches[8])>=1){
                $AssetFiles = [
                    'css'=>array(),
                    'js'=>array(),
                    'cssName'=>'',
                    'jsName'=>'',
                    'htmlName'=>''
                ];
                $resultHTML = '';

                foreach ($matches[8] as $link){
                    if (mb_strpos($link, '/') !== false && mb_strpos($link, '/') === 0) {
                        $link= mb_substr( $link, 1, strlen($link) ) ;
                    }
                    if(file_exists($link)){
                        if(pathinfo($link, PATHINFO_EXTENSION) === 'css'){
                            $AssetFiles['cssName'].=$link;
                        }
                        if(pathinfo($link, PATHINFO_EXTENSION) === 'scss'){
                            $AssetFiles['cssName'].=$link;
                        }
                        if(pathinfo($link, PATHINFO_EXTENSION) === 'less'){
                            $AssetFiles['cssName'].=$link;
                        }
                        if(pathinfo($link, PATHINFO_EXTENSION) === 'js'){
                            $AssetFiles['jsName'].=$link;
                        }
                        if(pathinfo($link, PATHINFO_EXTENSION) === 'twig'){
                            $AssetFiles['htmlName'].=$link;
                        }
                    }
                }

                //<script type="text/html" src="/projects/{{ ExtraData.domain }}/twig/components/calendar.html"></script>

                if((!file_exists($dirAsset.'/cache/'.md5($AssetFiles['htmlName']).'.html')  || !$this::$Config->isTwigCacheEnabled()) && $AssetFiles['htmlName'] != ''  ){

                    foreach ($matches[8] as $link){
                        if (mb_strpos($link, '/') !== false && mb_strpos($link, '/') === 0) {
                            $link= mb_substr( $link, 1, strlen($link) ) ;
                        }
                        if(file_exists($link)){
                            if(pathinfo($link, PATHINFO_EXTENSION) === 'twig'){
                                $HandlebarsFilter = new \Assetic\Filter\CallablesFilter();
                                $AssetFiles['html'][] = new \Assetic\Asset\FileAsset($link,array($HandlebarsFilter));
                            }
                        }
                    }

                    if(count($AssetFiles['html'])>=1){
                        $js = new \Assetic\Asset\AssetCollection($AssetFiles['html']);
                        if (!file_exists($dirAsset)) {
                            mkdir($dirAsset, 0777, true);
                        }
                        if (!file_exists($dirAsset.'/cache/')) {
                            mkdir($dirAsset.'/cache/', 0777, true);
                        }
                        @unlink($dirAsset.'/cache/'.md5($AssetFiles['htmlName']).'.html');
                        file_put_contents($dirAsset.'/cache/'.md5($AssetFiles['htmlName']).'.html',str_replace('app_loader_','app_loader_asset_',$js->dump()));
                    }
                }


                if((!file_exists($dirAsset.'/cache/'.md5($AssetFiles['jsName']).'.js') || !$this::$Config->isTwigCacheEnabled()) && $AssetFiles['jsName'] != '' ){
                    foreach ($matches[8] as $link){
                        if (mb_strpos($link, '/') !== false && mb_strpos($link, '/') === 0) {
                            $link= mb_substr( $link, 1, strlen($link) ) ;
                        }
                        if(file_exists($link)){
                            if(pathinfo($link, PATHINFO_EXTENSION) === 'js'){
                                $jsMinifier = new \Assetic\Filter\JavaScriptMinifierFilter();
                                $AssetFiles['js'][] = new \Assetic\Asset\FileAsset($link,array($jsMinifier));
                            }
                        }
                    }
                    if(count($AssetFiles['js'])>=1){
                        $js = new \Assetic\Asset\AssetCollection($AssetFiles['js']);
                        if (!file_exists($dirAsset)) {
                            mkdir($dirAsset, 0777, true);
                        }
                        if (!file_exists($dirAsset.'/cache/')) {
                            mkdir($dirAsset.'/cache/', 0777, true);
                        }
                        @unlink($dirAsset.'/cache/'.md5($AssetFiles['jsName']).'.js');
                        file_put_contents($dirAsset.'/cache/'.md5($AssetFiles['jsName']).'.js',$js->dump());
                    }
                }

                if((!file_exists($dirAsset.'/cache/'.md5($AssetFiles['cssName']).'.css') || !$this::$Config->isTwigCacheEnabled()) && $AssetFiles['cssName'] !='' ){
                    foreach ($matches[8] as $link){
                        if (mb_strpos($link, '/') !== false && mb_strpos($link, '/') === 0) {
                            $link= mb_substr( $link, 1, strlen($link) ) ;
                        }
                        if(file_exists($link)){
                            if(pathinfo($link, PATHINFO_EXTENSION) === 'css'){
                                $CSSMinFilter = new \Assetic\Filter\CSSMinFilter();
                                //$StylesheetMinifyFilter = new Assetic\Filter\StylesheetMinifyFilter();
                                $PhpCssEmbedFilter = new \Assetic\Filter\PhpCssEmbedFilter();
                                $AssetFiles['css'][] = new \Assetic\Asset\FileAsset($link,array($CSSMinFilter,$PhpCssEmbedFilter));
                            }
                            if(pathinfo($link, PATHINFO_EXTENSION) === 'scss'){
                                $ScssphpFilter = new \Assetic\Filter\ScssphpFilter();
                                //$StylesheetMinifyFilter = new Assetic\Filter\StylesheetMinifyFilter();
                                $PhpCssEmbedFilter = new \Assetic\Filter\PhpCssEmbedFilter();
                                $CSSMinFilter = new \Assetic\Filter\CSSMinFilter();
                                $AssetFiles['css'][] = new \Assetic\Asset\FileAsset($link,array($ScssphpFilter,$PhpCssEmbedFilter,$CSSMinFilter));
                            }
                            if(pathinfo($link, PATHINFO_EXTENSION) === 'less'){
                                $LessphpFilter = new \Assetic\Filter\LessphpFilter();
                                //$StylesheetMinifyFilter = new Assetic\Filter\StylesheetMinifyFilter();
                                $PhpCssEmbedFilter = new \Assetic\Filter\PhpCssEmbedFilter();
                                $CSSMinFilter = new \Assetic\Filter\CSSMinFilter();
                                $AssetFiles['css'][] = new \Assetic\Asset\FileAsset($link,array($LessphpFilter,$PhpCssEmbedFilter,$CSSMinFilter));
                            }
                        }
                    }
                    if(count($AssetFiles['css'])>=1){
                        $css = new \Assetic\Asset\AssetCollection($AssetFiles['css']);
                        if (!file_exists($dirAsset)) {
                            mkdir($dirAsset, 0777, true);
                        }
                        if (!file_exists($dirAsset.'/cache/')) {
                            mkdir($dirAsset.'/cache/', 0777, true);
                        }
                        @unlink($dirAsset.'/cache/'.md5($AssetFiles['cssName']).'.css');
                        file_put_contents($dirAsset.'/cache/'.md5($AssetFiles['cssName']).'.css',$css->dump());
                    }
                }

                if($AssetFiles['jsName'] != ''){
                    $resultHTML.='
    <script type="text/javascript" src="/'.$dirAsset.'/cache/'.md5($AssetFiles['jsName']).'.js'.(($this::$Config->isTwigCacheEnabled())?'':'?v='.rand(500, 99999999)).'"></script>';
                }
                if($AssetFiles['cssName'] !=''){
                    $resultHTML.='
    <link type="text/css" rel="stylesheet" href="/'.$dirAsset.'/cache/'.md5($AssetFiles['cssName']).'.css'.(($this::$Config->isTwigCacheEnabled())?'':'?v='.rand(500, 99999999)).'">
                            ';
                }
                if($AssetFiles['htmlName'] !=''){
                    $resultHTML.="
                <script>$.ajax({ url:'/".$dirAsset."/cache/".md5($AssetFiles['htmlName']).".html' ,type: 'GET',async: true,success: function (data) { $('#loadPrev').append(data); }});</script>
                            ";
                }
            }
            return $resultHTML;
        }));

        $translator = new \Symfony\Component\Translation\Translator($this::$Config->getUserLng().'_'.mb_strtoupper($this::$Config->getUserLng()));
        $translator->addLoader('po', new \Symfony\Component\Translation\Loader\PoFileLoader());
        $finder = new \Symfony\Component\Finder\Finder();

        if(file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate')){
            $poFiles = $finder->files()->in($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate'.DIRECTORY_SEPARATOR.'*')->name('GeneralUI.po')->name($this::$ClassName.'.po');
            foreach ($poFiles->getIterator() as $oneFile) {
                $pp = explode(DIRECTORY_SEPARATOR,$oneFile->getPath());
                $locale = array_pop( $pp );
                $fp = explode('.',$oneFile->getFilename());
                $domain = count($fp)===3 ? $fp[1] : 'messages';
                $translator->addResource( 'po',
                    $oneFile->getPath().DIRECTORY_SEPARATOR.$oneFile->getFilename(),
                    $locale,
                    $domain);
            }
        }
        $thisSelf = $this;
        $ConfigCMS = $this::$Config;
        $this::$Twig->addFilter(new \Twig\TwigFilter('trans', function ($string)use($translator,$ClassName,$thisSelf,$ConfigCMS) {

            $file = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate'.DIRECTORY_SEPARATOR.$ConfigCMS->getUserLng().'_'.mb_strtoupper($ConfigCMS->getUserLng()).DIRECTORY_SEPARATOR.$ClassName.'.po';
            if(!file_exists($file)){
                if (!file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate')) {
                    mkdir($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate', 0777, true);
                }
                if (!file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate'.DIRECTORY_SEPARATOR.$ConfigCMS->getUserLng().'_'.mb_strtoupper($ConfigCMS->getUserLng()))) {
                    mkdir($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'translate'.DIRECTORY_SEPARATOR.$ConfigCMS->getUserLng().'_'.mb_strtoupper($ConfigCMS->getUserLng()), 0777, true);
                }
                if (!file_exists($file)) {
                    file_put_contents($file, 'msgid ""
msgstr ""
"Project-Id-Version: taix\n"
"POT-Creation-Date: '.date("Y-m-d H:i:s").'+0300\n"
"PO-Revision-Date: \n"
"Last-Translator: \n"
"Language-Team: Qwerty\n"
"Language: '.$ConfigCMS->getUserLng().'\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Generator: Taix app\n"
"Plural-Forms: nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);\n"
"X-Poedit-KeywordsList: trans\n"
"X-Poedit-Basepath: ../..\n"
"X-Poedit-SearchPath-0: language\n"
"X-Poedit-SearchPath-1: Console\n"
"X-Poedit-SearchPathExcluded-0: twig/*.twig\n"
                            
                            ', FILE_APPEND);
                }
            }

            $newText = $translator->trans($string);
            if(($newText === ''|| $newText === $string) && $ConfigCMS->getUserLng()!=='en' ){

                $translated = $thisSelf->translate_google($string, 'en', $ConfigCMS->getUserLng(), 'text',$ConfigCMS);
                if ($translated == "0") $translated = "";
                $translated = str_replace("'","",$translated);
                if ($translated == '') $translated = $string;


                $textRO = file_get_contents($file);
                if(strpos($textRO,'"'.$string.'"') === false){
                    if($string !== $translated) {
                        $data = '
msgid "' . $string . '"
msgstr "' . $translated . '"
 ';
                    }
                    if($string === $translated){
                        $data ='
 #TODO Automatic translation has been disabled
msgid "'.$string.'"
msgstr ""
 ';
                    }
                    file_put_contents($file, $data, FILE_APPEND);
                }

                return $translated;
            }else{
                return $translator->trans($string);
            }
        }));

        return $this::$Twig;
    }

    /**
     * @return array
     */
    public function getDTO(): array
    {
        return $this::$DTO;
    }

    /**
     * @param $text
     * @return array|string|string[]
     */
    public function Montenegrin_transit($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $cyr_alphabet = array(
            'А', 'Б', 'В', 'Г', 'Д', 'Ђ', 'Е', 'Ж', 'З', 'И', 'Й',
            'К', 'Л', 'Љ', 'М', 'Н', 'Њ', 'О', 'П', 'Р', 'С', 'Т', 'Ћ', 'У', 'Ф',
            'Х', 'Ц', 'Ч', 'Џ', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я',
            'а', 'б', 'в', 'г', 'д', 'ђ', 'е', 'ж', 'з', 'и', 'й',
            'к', 'л', 'љ', 'м', 'н', 'њ', 'о', 'п', 'р', 'с', 'т', 'ћ', 'у', 'ф',
            'х', 'ц', 'ч', 'џ', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я'
        );

        $mnt_alphabet = array(
            'A', 'B', 'V', 'G', 'D', 'Đ', 'E', 'Ž', 'Z', 'I', 'J',
            'K', 'L', 'Lj', 'M', 'N', 'Nj', 'O', 'P', 'R', 'S', 'T', 'Ć', 'U', 'F',
            'H', 'C', 'Č', 'Dž', 'Š', 'Š', '', 'Y', '', 'E', 'JU', 'JA',
            'a', 'b', 'v', 'g', 'd', 'đ', 'e', 'ž', 'z', 'i', 'ј',
            'k', 'l', 'lj', 'm', 'n', 'nj', 'o', 'p', 'r', 's', 't', 'ć', 'u', 'f',
            'h', 'c', 'č', 'dž', 'š', 'š', '', 'y', '', 'e', 'ju', 'ja'
        );

        return str_replace($cyr_alphabet, $mnt_alphabet, $text);
    }

    /**
     * @param $text
     * @param $from_lng
     * @param $to_lng
     * @param $Reformat
     * @param $ConfigCMS
     * @return array|mixed|string|string[]
     */
    public function translate_google($text, $from_lng, $to_lng, $Reformat, $ConfigCMS)
    {
        if((!empty($ConfigCMS->getGoogleKey()) && $ConfigCMS->getGoogleKey() === '') || empty($ConfigCMS->getGoogleKey())){
            return $text;
        }
        $this_from_lng = $from_lng;
        $this_to_lng = $to_lng;
        if (in_array($this_from_lng,array('me','sr','sl','sk','bs'))) $this_from_lng = "hr";
        if (in_array($this_to_lng,array('me','sr','sl','sk','bs'))) $this_to_lng = "hr";
        $return_phrase = "";
        $post = array('q' => $text,'target'=> $this_to_lng,'format'=> $Reformat,'source'=> $this_from_lng,'key'=> $ConfigCMS->getGoogleKey());
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://translation.googleapis.com/language/translate/v2");
        curl_setopt($ch, CURLOPT_POST, True);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER ,True);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        $result=curl_exec ($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status_code == 200) {
            $result = json_decode($result,true);
            $return_phrase = $result['data']['translations'][0]['translatedText'];
        }
        curl_close ($ch);
        if (strpos($return_phrase," т. Д.") !== false) $return_phrase = str_replace(" т. Д."," т.д.",$return_phrase);
        if ($to_lng == 'me') $return_phrase = $this->Montenegrin_transit($return_phrase);
        return $return_phrase;
    }

    /**
     * @param null $file
     * @return string
     * @throws Exception
     */
    public function getContent($file = null): string
    {
        if($file){
            if(!empty($this::$Content[$file])){
                return (string)$this::$Content[$file];
            }else{
                throw new \Exception("No content");
            }
        }else{
            if(!empty($this::$Content[$file])) {
                return (string)$this::$Content[$this::$fileTwig];
            }else{
                throw new \Exception("No content");
            }
        }
    }

    /**
     * @param string $file
     * @param string $Content
     * @return $this
     * @throws Exception
     */
    public function setContent(string $file,string $Content): ThaiRender
    {
        $this::$Content[$file] = $Content;
        return $this;
    }


    /**
     * @param string $file
     * @return $this
     * @throws Exception
     */
    public function render(string $file = ''): ThaiRender
    {
        if(!$this::$Twig){
            $this->getTwig();
        }

        if($file === ''){
            if(file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'twig'.DIRECTORY_SEPARATOR.$this::$ClassName.'.twig') || file_exists($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'projects'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.'twig'.DIRECTORY_SEPARATOR.$this::$ClassName.'.twig')){
                $this->setContent($this::$ClassName.'.twig',$this::$Twig->render($this::$ClassName.'.twig', $this->getDTO()));
                $this::$fileTwig = $this::$ClassName.'.twig';
            }else{
                die('ERROR file '.$this::$ClassName.'.twig not found');
            }
        }else{
            $this::$fileTwig = $file;
            $this->setContent($file,$this::$Twig->render($file, $this->getDTO()));
        }
        return  $this;
    }

}