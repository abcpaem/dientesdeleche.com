<?php 
/************************************

   storedisplay.php

*/

namespace zstore\manager;

require_once 'cacheMgr.php';
include_once( ABSPATH . WPINC . '/feed.php' );


class _Zazzle_Store_Display_Basic

{

	private $options = array();

	private $defaults = array();

	private $gridNumber;

	private $startpage;

	private $gridPageHist;

	private $sortMethod; 

	private $sortMode;

	private $currentSort;

	private $gridSort;

	private $gridSortHist;

	private $keywordParam;

	private $gridSortHistDate;

	private $gridSortHistPopularity;

	private $showsortingText;

	private $showpaginationText;

	private $customfeedurl;

	private $cache_dir;
	

	private $paginationText ;

	private $paginationBackOnePage ;

	private $paginationBackToFirstPage ;

	private $jumpToPage;

	private $ofResults ;

	private $advanceOnePageOfResults ;

	private $advanceToLastPageOfResults ;

	private $sortBy ;

	private $dateCreated ;

	private $popularity ;

	private $showingXofY ;

	private $of;

	private $viewMoreProductsFrom ;

	private $by;

	private $poweredByZazzle ;

	private $errorStringProductsUnavailable ;

	private $errorStringRSSNotFound;

	private $sortByDateTooltip;

	private $sortByPopularityTooltip;

	private $keywords;
	
	private $targetWindow = '';

	private $nofollow = "rel=\"nofollow\""; 
	private $associateid;

	public function __construct()

    {

		

		add_shortcode( 'zStoreBasic', array( $this, 'z_store_display_func' ) );

		add_action( 'wp_enqueue_scripts', array( $this,'basic_store_display_enqueue_styles' ));

		$this->initialize_strings();

		$defaults = get_option( 'zstore_basic_manager_settings' );
		$this->defaults = $this->convert_keys_to_lower($defaults);


	}
    
	private function initialize_strings()

	{

	

		$this->paginationText = __('Ir a página: ', 'zstore');

		$this->paginationBackOnePage = __('Ir a página anterior', 'zstore');

		$this->paginationBackToFirstPage = __('Ir a primera página', 'zstore');

		$this->jumpToPage = __('Ir a página', 'zstore');

		$this->ofResults = __('de resultados', 'zstore');

		$this->advanceOnePageOfResults = __('Ir a siguente página', 'zstore');

		$this->advanceToLastPageOfResults = __('Ir a última página', 'zstore');

		$this->sortBy = __('Orden', 'zstore');

		$this->dateCreated = __('más nuevo', 'zstore');

		$this->popularity = __('popular', 'zstore');

		$this->showingXofY = __('Mostrando', 'zstore');

		$this->of = __('de', 'zstore');

		$this->viewMoreProductsFrom = __('Ver más productos de ', 'zstore');

		$this->by = __('por', 'zstore');

		$this->poweredByZazzle = __('Powered by Zazzle', 'zstore');

		$this->errorStringProductsUnavailable = __('No hay resultados.', 'zstore');

		$this->errorStringRSSNotFound = __('Error: Feed temporarily unavailable.<br/>Please try again later.', 'zstore');

		$this->sortByDateTooltip = __('Ordenar resultados for fecha', 'zstore');

		$this->sortByPopularityTooltip = __('Ordenar resultados por popularidad', 'zstore');


	}

	public static function instance() {

		 new _Zazzle_Store_Display_Basic;

			

	}

	function iever($compare=false, $to=NULL){
	if(!preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $m)
	 || preg_match('#Opera#', $_SERVER['HTTP_USER_AGENT']))
		return false === $compare ? false : NULL;

	if(false !== $compare
		&& in_array($compare, array('<', '>', '<=', '>=', '==', '!=')) 
		&& in_array((int)$to, array(5,6,7,8,9,10))){
		return eval('return ('.$m[1].$compare.$to.');');
	}
	else{
		return (int)$m[1];
	}
}

	function basic_store_display_enqueue_styles()

	{

		wp_register_style( 'ZStoreBasicDisplayStyleSheets', plugins_url('css/pagestyle.css', dirname(__FILE__)) );

		wp_enqueue_style( 'ZStoreBasicDisplayStyleSheets');

		if(!wp_script_is('jquery')) {
			
				wp_enqueue_script('jquery');  
		}
		
		wp_register_script( 'ZstoreDisplay', plugins_url( '../js/zStoredisplay.js', __FILE__ ) );

		wp_enqueue_script( 'ZstoreDisplay', plugins_url( '../js/zStoredisplay.js', __FILE__ ) , array('jquery'), '1.0.0', true );
		
		

		

	}

	private function get_zstore_sort_methods()

	{
		


			// init sort variable and some 'showing' variables we use later for pagination

			$this->sortMethod = !isset($_GET['st']) ? "st=date_created" : 'st=popularity';

	        $this->currentSort = isset($_GET['currentSort']) ?  $_GET['currentSort'] : "";
			
			
			

			if($this->options['defaultsort'] == 'popularity') {

				$this->sortMethod = 'st=popularity';

				$this->sortMode = 'popularity';

			}

			if($this->options['defaultsort'] == 'date_created') {

				$this->sortMethod = 'st=date_created';

				$this->sortMode = 'date_created';

			}

			if($this->currentSort == 'popularity') {

				$this->sortMethod = 'st=popularity';

					$this->sortMode = 'popularity';

			}

			if($this->currentSort == 'date_created') {

				$this->sortMethod = 'st=date_created';

				$this->sortMode = 'date_created';

			}

	}

	public function get_grid_cell_size()

	{


			switch( $this->options['gridcellsize']) {

			case 'tiny':

				$gridCellSize = 50;

				break;

			case 'small':

				$gridCellSize = 92;

				break;

			case 'medium':

				$gridCellSize = 152;

				break;

			case 'large':

				$gridCellSize = 210;

				break;

			case 'huge':

				$gridCellSize = 328;

				break;

			default:

				if (is_numeric($gridCellSize)) {

					$gridCellSize = $gridCellSize;

				} else {

					$gridCellSize = 152;

				}

				;

			

		}

		

			return $gridCellSize;

	

	}

	private function get_start_pages(&$pageinationStart,&$paginationEnd,&$paginationBack,&$paginationFwd,&$showing, &$showingEnd, $totalPages)

	{

		$showing = (( $this->options['showhowmany'] * $this->startpage) - $this->options['showhowmany'])+1;

		$showingEnd = $this->options['showhowmany'] * $this->startpage;

	

		// Figure out where to start and stop the pagination page listing

        $paginationStart = $this->startpage - 5;

        $paginationEnd = $this->startpage + 5;



        $paginationBack = $this->startpage - 1;

        $paginationFwd = $this->startpage + 1;



        if($paginationStart < 1) $paginationStart = 1;

        if($paginationBack < 1) $paginationBack = 1;



        if($paginationEnd > $totalPages) $paginationEnd = $totalPages;

        if($paginationFwd > $totalPages) $paginationFwd = $totalPages;
		
	

	}

	

	private function get_grid_sort()

	{

			$gS = '';

			if (isset($_GET['gridSort']))

				$gS = $_GET['gridSort'];

	


				switch( $gS) {

                

                case 'popularity':

                

                     $this->sortMethod = 'st=popularity';

                    $this->sortMode = 'popularity';
					break;
				case 'date':
				 default:

                    $this->sortMethod = 'st=date_created';

                    $this->sortMode = 'date_created';

                    break;

				}

		
	

	}


	private function get_grid_pages()

	{

		$gridPages = array();

		$gridPage =  $_GET['gridPage'] = isset($_GET['gridPage']) ?  htmlspecialchars($_GET['gridPage'],ENT_QUOTES) : "";

		
		if($gridPage != '') {
			$this->startpage = $gridPage;
		}
		else return;

	
		
	

	}

	private function format_pagination( $totalNum)

	{

		


		$totalPages = ceil( $totalNum/$this->options['showhowmany']);

        $this->get_start_pages($paginationStart,$paginationEnd,$paginationBack,$paginationFwd,$showing, $showingEnd,$totalPages);



        if ( $showingEnd > $totalNum) {

			$showingEnd = $totalNum;  // can't show more results than we have

		}


		$sortingText="";

		$pagination="";

		if ($this->options['showsorting'] == 'true' && $this->options['use_customfeedurl'] === 'false')

		{


	
		$this->gridSortHist="popularity";
		$this->gridSortHistDate="date_created";

			if ( $this->sortMode == 'date_created') {
				
			$this->showsortingText="<span class=\"sortLinks\">$this->sortBy: <a href=\"?gridPage=$this->startpage&amp;gridSort={$this->gridSortHistDate}$this->keywordParam\" class=\"selectedSort\" title=\"{$this->sortByDateTooltip}\" rel=\"nofollow\"><strong>$this->dateCreated</strong></a> | <a href=\"?gridPage={$this->startpage}&amp;gridSort={$this->gridSortHist}$this->keywordParam\" title=\"{$this->sortByPopularityTooltip}\" rel=\"nofollow\">$this->popularity</a></span>";
						

					$sortingText=$this->gridSortHistDate;

			} else {

					$this->showsortingText ="<span class=\"sortLinks\">$this->sortBy: <a href=\"?gridPage=$this->startpage&amp;gridSort={$this->gridSortHistDate}$this->keywordParam\" title=\"{$this->sortByDateTooltip}\" rel=\"nofollow\">$this->dateCreated</a> | <a href=\"?gridPage={$this->startpage}&amp;st=1&amp;gridSort={$this->gridSortHist}$this->keywordParam\" class=\"selectedSort\" title=\"{$this->sortByPopularityTooltip}\" rel=\"nofollow\"><strong>$this->popularity</strong></a></span>";

					$sortingText=$this->gridSortHist;
					

				}

	

		}

		

		



        if($this->startpage > 1) {

            $pagination .= "<small><a class=\"paginationArrows\" title=\"$this->paginationBackToFirstPage\" href=\"?gridPage={$this->gridNumber}1{$this->gridPageHist}&amp;gridSort={$sortingText}$this->keywordParam\"><span class=\"aquo\">&laquo;</span></a></small> "; // back to start



            $pagination .= "<small><a class=\"paginationArrows\" title=\"$this->paginationBackOnePage\" href=\"?gridPage=$paginationBack{$this->gridPageHist}&amp;gridSort={$sortingText}$this->keywordParam\">&lt;</a></small> "; // back one page

        }

	          

        for ( $i=$paginationStart; $i<=$paginationEnd; $i++) {



            if($totalPages <= 1) continue;

            if($i == $this->startpage) $pagination .= '<span class="current" ><strong>' . $i . '</strong> </span>';

 
    else {$pagination .= "<a title=\"$this->jumpToPage $i $this->ofResults\" href=\"?gridPage={$i}&amp;gridSort={$sortingText}$this->keywordParam\" class=\"numbersList\">".$i."</a> ";
        
	}
        }



        if($this->startpage < $totalPages ) {

        			$pagination .= "<small><a class=\"paginationArrows\" title=\"$this->advanceOnePageOfResults\" href=\"?gridPage=" . $paginationFwd . "{$this->gridPageHist}&amp;gridSort={$sortingText}$this->keywordParam\">&gt;</a></small> ";

            $pagination .= "<small><a class=\"paginationArrows\" title=\"$this->advanceToLastPageOfResults\" href=\"?gridPage=" .  $totalPages  . "{$this->gridPageHist}&amp;gridSort={$sortingText}$this->keywordParam\"><span class=\"aquo\">&raquo;</span></a></small> ";

        }

		
	/*	if ($showing > $showingEnd)
			$showing = $showingEnd;*/
		$this->showpaginationText = "&nbsp;&nbsp;&nbsp;&nbsp;<span>$this->showingXofY  $showing - $showingEnd $this->of ".$totalNum." productos.</span>&nbsp;&nbsp;".$pagination;

	}

	private function get_keywords()

	{

		$this->keywords = strtolower($this->options['keywords']);

		if (strpos($this->keywords,"+")) {

			$this->keywords = str_replace(" ","",$this->keywords);

			$this->keywords = str_replace("+",",and,",trim($this->keywords));

		} else {

			if (strpos($this->keywords,",")) {

				$this->keywords = str_replace(" ","",$this->keywords);

				$this->keywords = str_replace(",",",or,",$this->keywords);

			} else {

				if (strpos($this->keywords," or ")) {

					$this->keywords = str_replace(" or ",",or,",$this->keywords);

				} else {

					if (strpos($this->keywords," and ")) {

						$this->keywords = str_replace(" and ",",and,",$this->keywords);

					} else {

						if (strpos($this->keywords," ")) {

							$this->keywords = str_replace(" ",",or,",$this->keywords);

						}

					}

				}

			}

		}

	}
	private function convert_keys_to_lower($defaults)
	{
	

		$out = array();

	

		foreach($defaults as $key => $default) {

		

			$lkey = strtolower($key);

			

				$out[$lkey] = $defaults[$key];
	

		}

	
 
		return $out;

	

	}


	

	

	private function writeToCache($externalUrl,$localFilename){

	

		$ch = curl_init( rawurldecode( $externalUrl));
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_HEADER, 0);

		$cdir = plugin_dir_path( __FILE__ )  . 'c';

		
		$fh = fopen($cdir. '/' . $localFilename, "w");

	

        
        $fdata = curl_exec( $ch);

        fwrite( $fh, $fdata);

        curl_close( $ch);

        fclose( $fh);



	

	

	}

	private function get_image_src($imageUrl,$cache)

	{

		if($this->options['usecaching'] == 'true') {

			

			$imageUrl = preg_replace( '/amp;/','', $imageUrl);  // un-escape the ampersands

			 $imageSrc = ''; // we'll use this to set the image's initial src url



                    // build our product image url

                    

             $productFile = str_replace("http://rlv.zcache.com/","",$imageUrl);

			 

			$str1 = '';
			$str = substr(strrchr($productFile, '/'), 1);
			if (strlen($str) > 0)
				$str1 = substr($str, 0, strpos($str, '?'));
			if (strlen($str1) > 0) 
			    $str = $str1;

			if (strlen($str) > 0)		

				$productFile = $str;

		

			

			if($cache->is_image_cached($productFile)) {   // yes - override image url with local url

				

              

                 $imageSrc = $this->cache_dir. '/' . $productFile;

			



            }else {  // no - go get the image from the server and cache

                        // get product image - this will fail if your version of php is not curl-enabled

			

						$this->writeToCache($imageUrl,$productFile);

               

                        // override the remote url with the cached versions so we point at the local copies

                       

                        $imageSrc = $this->cache_dir. '/' . $productFile;

					

              }
					

		}


			else{

				

					// no caching yet;

			$imageSrc = $imageUrl;

		}

	

	

		return $imageSrc;

	}

	

	private function product_description($description)

	{

		$desc = "";

		

			$shortdescription="";

			

			$description = preg_replace( "/\.\.\./", '... ', $description);

			$description = preg_replace( "/\,/", ', ', $description);

			$descriptionWords = preg_split("/[\s]+/", $description);


			if ($this->options['useshortdescription'] == 'true') {
				
				for( $i = 0; $i <= 10; ++$i) {

					if (isset($descriptionWords[$i])){

						$shortdescription .= $descriptionWords[$i] . ' ';

					}

				}

			if(sizeof( $descriptionWords) > 10) 

				$shortdescription .= '...';


				

				$desc =  "<p>"  . $shortdescription . "</p>";

			} else {

				

				$desc =  "<p>"  . $description . "</p>";

			}

	
       $desc = html_entity_decode($desc);
		return $desc;

	}

	private function make_cache_dir_path()

	{

		

		if($this->options['usecaching'] == 'true') {

 

			$this->cache_dir = plugins_url( 'includes/c' , dirname(__FILE__));



		

        // create a cache manager object for image caching

			$cache = new cacheMgr_Zstore_Basic;

			

			$cache->set_lifetime( $this->options['cachelifetime']);

			return $cache;

		}

	

	

	}
	
	private function getAssociateidparam()
	{
		
		
		if (!empty($this->associateid)){
				
							$associateidParam = $this->options['associateid'] != "YOURassociateidHERE" ? "&rf=".$this->associateid: "";

				
						

						}

						else 

		$associateidParam = "";
		
		return $associateidParam;
		
	}
	
	private function showCollections($gridCellSize,$gridcellbgcolor,$cache)
	{
		$content = '<div>';
		
		$xml="https://feed.zazzle.com/collections/". $this->options['collections'] ."/rss?opensearch=true&at=".$this->associateid.'&isz='.$gridCellSize.'&bg='.$gridcellbgcolor;
	


	$xmlDoc = new \DOMDocument();
	$xmlDoc->load($xml);
   

   
	$content .= '<div class="gallery zStore_gallery" >';
 
   
   $x1 = $xmlDoc->getElementsByTagName('item');
   
	
	foreach($x1 as $x){
      $title = $x->getElementsByTagName('title')
      ->item(0)->childNodes->item(0)->nodeValue;
      
      $link = $x->getElementsByTagName('link')
      ->item(0)->childNodes->item(0)->nodeValue;
      

	    $price = $x->getElementsByTagName('price')
      ->item(0)->childNodes->item(0)->nodeValue;
	  $artist = $x->getElementsByTagName('author')
      ->item(0)->childNodes->item(0)->nodeValue;
 
  
  if (!empty($this->associateid))
      $link .= "&zbar=1";
  else 
  $link .= "?zbar=1";
  
  
	$description = $x->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'description')->item(0)->childNodes->item(0)->nodeValue;    

	$nlContent = $x->getElementsByTagNameNS('http://search.yahoo.com/mrss/', 'thumbnail');


	
	

	foreach( $nlContent as $n )
{		$imageUrl = $n->getAttribute('url');


}
 


 
	 $associateidParam = $this->getAssociateidparam();
	 $galleryUrl = "http://www.". ZAZZLE_BASIC_URL_BASE ."/".$artist.$this->targetWindow.$associateidParam;

		if (isset($this->options['trackingcode'] )){

							$galleryUrl .="&tc=". $this->options['trackingcode'];

							$link .="&tc=". $this->options['trackingcode'];

						}

			 $content .= $this->makeTheitem($galleryUrl, $link,$title,$price,$imageUrl,$cache,$description,$artist);
					

			}
		$content .= "</div></div>";
		
		return $content;
	
	
	
	}
	function setupPaginationline($maxitems,$location)
	{
		if ( ($this->options['showpagination'] || $this->options['showsorting'] ) && $this->options['use_customfeedurl'] === 'false'){

					$this->format_pagination( $maxitems);

					$content= $location == "top"? "<p class='z_pagination_top'>":"<p class='z_pagination_bottom'>";

					if ($this->options['showsorting'] == 'true')

						$content.= $this->showsortingText;

					if ($this->options['showpagination'] == 'true')

						$content.= $this->showpaginationText;

					$content.= "</p>";

				}
			return $content;
	}
	private function initializeOptions()
	{
		
		if(!isset($this->options['startpage']))

			$this->startpage = 1;

		else 

			$this->startpage = $this->options['startpage'];

		if (!empty($this->options['keywords']))

		{

				$this->keywords = htmlspecialchars($this->options['keywords'],ENT_QUOTES);

				$this->keywordParam .= "&qs=".urlencode($this->keywords)."#zazzle";

		}

		if (isset($this->options['customfeedurl'])){

			$this->customfeedurl = htmlspecialchars($this->options['customfeedurl'],ENT_QUOTES);

		}
		

		if ($this->options['newwindow'] == 'true'){
				$this->targetWindow="  target='_blank'";
		}

		$this->get_zstore_sort_methods();
		$this->get_grid_pages();
		$this->get_grid_sort();
		$this->keywordParam = "";
	}
	private function makeTheitem($galleryUrl, $link,$title,$price,$imageUrl,$cache,$description,$artist)
	{
		
		$displaytitle ="<p><a style='color: #176d91' href=\"$link \" {$this->nofollow}  title=\"$title\" " .  $this->targetWindow . ">$title</a></p>";

						$desc = $this->product_description($description);

						$byline = "<p class='z_productByline'>	 by <a rel=\"nofollow\" href=" . $galleryUrl . " title=\"" . $this->viewMoreProductsFrom . "" .  $artist . "" . $this->targetWindow . "\">" . $artist. "</a></p>";


						$displayprice = "<p class='z_productPrice'>" .  $price . "</p>";

						$imageSrc = $this->get_image_src($imageUrl,$cache);
										
		              

						$content = "<figure>";

						$content .= "<a href=\"$link \" $this->nofollow  title=\"$title\" " .  $this->targetWindow . "><img src=\"" . $imageSrc . '"  alt=' . $title. ' title=""  /> </a>'; 

						$content .="</a>";

						$content .= "<figcaption>";
						if($this->options['showproducttitle']== 'true')

							$content .=   $displaytitle ;
				

					if($this->options['showproductdescription']  == 'true')

							$content .= '<description>'. $desc .'</description>';

			

					if ( $this->options['showbyline'] == 'true')

							$content .= $byline;

					

					if($this->options['showproductprice'] == 'true') 

							$content .= $displayprice;
					$content .= "</figcaption>";
					
					$content .= "</figure>";
					
					return $content;
		
	}
	function z_store_display_func($atts, $content="" )

	{
	
	    $defaults = $this->defaults;

		if (empty($defaults['producttype']))

		{

			$defaults['producttype'][0]='All';

			$defaults['producttype'][1]='all';

		}
error_log('options are ' . print_r($atts,true));
		$atts = shortcode_atts(
		array(
			'associateid' => $defaults['associateid'],
			'collections' => '',
			'showhowmany' =>$defaults['showhowmany'],
			'contributorhandle' => $defaults['contributorhandle'],
			'productlineid' => $defaults['productlineid'],
    'trackingcode' => $defaults['trackingcode'],
    'use_customfeedurl' => $defaults['use_customfeedurl'],
    'showzoom' => $defaults['showzoom'],
    'newwindow' => $defaults['newwindow'],
    'showpagination' => $defaults['showpagination'],
    'showsorting' => $defaults['showsorting'],
    'usecaching' => $defaults['usecaching'],
    'showproductdescription' => $defaults['showproductdescription'],
    'useshortdescription' =>$defaults['useshortdescription'],
    'showbyline' => $defaults['showbyline'],
    'showproductprice' => $defaults['showproductprice'],
    'showproducttitle' => $defaults['showproducttitle'],
    'gridcellsize' => $defaults['gridcellsize'],
	'defaultsort' => $defaults['defaultsort'],
    'cachelifetime' => empty($defaults['cachelifetime']) ? 0 : $defaults['cachelifetime'],
    'gridcellspacing' => $defaults['gridcellspacing'],
    'gridcellbgcolor' => $defaults['gridcellbgcolor'],
    'startpage' => $defaults['startpage'],
    'keywords' 	=> $defaults['keywords'],	
	'customfeedurl' => empty($defaults['customfeedurl'])? '' : $defaults['customfeedurl'] ,
	'producttype' =>$defaults['producttype'][1],
		
					), $atts, 'zStoreBasic' );
					
		$this->options = $atts;
	
	    $this->initializeOptions();
		$this->associateid=isset($this->options['associateid'])?$this->options['associateid']:"";
		$producttype='';
		
error_log('options are ' . print_r($this->options,true));
		if(!empty($this->options['producttype'])){

			if (is_array($this->options['producttype']))

				

			 $producttype = $this->options['producttype'][1];

			else 

			{

				$producttype=$this->options['producttype'];

			}


			if ($producttype != "") {

			

				$this->keywordParam .= "&".$producttype."#zazzle";

			}

		}



		

		$cache= $this->make_cache_dir_path();
		

	

		// product line id

		$cg="";

	    $cg = $this->options['productlineid'];

		$gridCellSize = $this->get_grid_cell_size();
		$gridCellSpacing = $this->options['gridcellspacing'];
		$gridcellbgcolor=!empty($this->options['gridcellbgcolor'])?$this->options['gridcellbgcolor']:"FFFFFF";
		
		
		
		$dataToBePassed = array(
    'gridCellSize'            => $gridCellSize,
    'gridCellSpacing' => $gridCellSpacing,
	 'gridcellbgcolor' => $gridcellbgcolor,
	 'showZoom' =>   $this->options['showzoom']                   
);
	wp_localize_script( 'ZstoreDisplay', 'zstorePHPvars', $dataToBePassed );



		$this->get_keywords();


		

		$dataURLBase = $this->options['contributorhandle']!="" ? 'https://feed.'. ZAZZLE_BASIC_URL_BASE .'/'.$this->options['contributorhandle'].'/rss' : 'http://feed.'.ZAZZLE_BASIC_URL_BASE.'/rss';
		
						
			
		
		if ($this->options['use_customfeedurl'] === 'true')
{
			$feedUrl = $this->options['customfeedurl'];
}else 
			$feedUrl = $dataURLBase . '?'.$this->sortMethod.'&at='.$this->associateid.'&isz='.$gridCellSize.'&bg='.$gridcellbgcolor.'&src=zstore&pg=1&cg='.$cg . '&qs='.$this->keywords.'&dp='.$producttype.'&ps=100';
		


if (!empty($this->options['collections']))
{

	$content = $this->showCollections($gridCellSize,$gridcellbgcolor,$cache);
	
}else{
	
	//error_log('feed url  #1' . $feedUrl);
	
	$myRSS = fetch_feed( $feedUrl );
	


	                                              
	


	if ( ! is_wp_error( $myRSS ) ){
		
		$maxitems = $myRSS->get_item_quantity(0 ); 
		$beginItem = ($this->startpage - 1) * $this->options['showhowmany']  ;
		$associateidParam = $this->getAssociateidparam();
	
	if ($this->sortMode === "popularity")
		$myRSS->enable_order_by_date(false);

		if ($maxitems > 0)
		
		{
		
		$content='<h2>Catálogo Zazzle<a name="zazzle"></a></h2><div > ';

				$this->showsortingText = '';

				$this->showpaginationText = '';

				$content.= $this->setupPaginationline($maxitems,"top");
				$content .= '<div class="gallery zStore_gallery" >';
		
			
			
			
			foreach(  $myRSS->get_items($beginItem, $this->options['showhowmany']) as $item) {
		
				$enclosure = $item->get_enclosure();
			

			$imageUrl = $enclosure->get_thumbnail();
			// ARTIST
			$s = $item->get_item_tags ( '', 'author' );
			$artist = $s[0]['data'];
			
						
			// PRICE
			$s = $item->get_item_tags ( '', 'price' );
			$price = $s[0]['data'];
			
			//description
			$description =  $enclosure->get_description();
		
		
			//link
			$link = $item->get_permalink();
			$link = str_replace( "&amp;ZCMP=gbase", "", $link);

			//title
			$title = $item->get_title();
				
						
			$galleryUrl = "http://www.". ZAZZLE_BASIC_URL_BASE ."/".$artist.$this->targetWindow.$associateidParam;

			

						if (isset($this->options['trackingcode'] )){

						

							$galleryUrl .="&tc=". $this->options['trackingcode'];

							$link .="&tc=". $this->options['trackingcode'];

						}
						 $content .= $this->makeTheitem($galleryUrl, $link,$title,$price,$imageUrl,$cache,$description,$artist);
					

  				
			}
		  
$content .= "</div>";
		}	else{
			
			
			$content .= "No items found";
			
			
		}
			
	

				$content.= $this->setupPaginationline($maxitems,"bottom");
$content.= ' </div>';
			 return "$content";		
						
			
    
	}else {

		 

		  // fatal error - no cached RSS, no socket

      die ( $this->errorStringRSSNotFound);

}
	
}
return $content;
	
	}


}

function zsmb_init_zazzle_store_display(){

	return _Zazzle_Store_Display_Basic::instance();

	}

add_action( 'plugins_loaded',  'zstore\\manager\\zsmb_init_zazzle_store_display'  );



?>