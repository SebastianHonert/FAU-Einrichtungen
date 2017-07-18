<?php
/**
 * Navigation Menu template functions
 *
 * @package WordPress
 * @subpackage FAU
 * @since FAU 1.0
 */


/*-----------------------------------------------------------------------------------*/
/* returns child items by parent
/*-----------------------------------------------------------------------------------*/
function add_has_children_to_nav_items( $items ) {
    $parents = wp_list_pluck( $items, 'menu_item_parent');
    $out     = array ();

    foreach ( $items as $item ) {
        in_array( $item->ID, $parents ) && $item->classes[] = 'has-sub';
        $out[] = $item;
    }
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'add_has_children_to_nav_items' );

/*-----------------------------------------------------------------------------------*/
/* get menu title
/*-----------------------------------------------------------------------------------*/
function fau_get_menu_name($location){
	if(!has_nav_menu($location)) return false;
	$menus = get_nav_menu_locations();
	$menu_title = wp_get_nav_menu_object($menus[$location])->name;
	return $menu_title;
}


/*-----------------------------------------------------------------------------------*/
/* returns top parent id
/*-----------------------------------------------------------------------------------*/
function get_top_parent_page_id($id, $offset = FALSE) {

	$parents = get_post_ancestors( $id );
	if( ! $offset) $offset = 1;
	
	$index = count($parents)-$offset;
	if ($index <0) {
	    $index = count($parents)-1;
	}
	return ($parents) ? $parents[$index]: $id;

}

/*-----------------------------------------------------------------------------------*/
/* Walker for main menu
/*-----------------------------------------------------------------------------------*/
class Walker_Main_Menu extends Walker_Nav_Menu {
	private $currentID;

	function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$level = $depth + 2;		
		$output .= $indent.'<div class="nav-flyout"><div class="container"><div class="row"><div class="span4"><ul class="sub-menu level'.$level.'">';
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		global $options;
		
		$indent = str_repeat("\t", $depth);
		$output .= $indent.'</ul>';
		$output .= '<a href="'.get_permalink($this->currentID).'" class="button-portal">';
		if (isset($options['menu_pretitle_portal']) && $options['menu_pretitle_portal']) {
		    $output .=  $options['menu_pretitle_portal'].' ';
		}
		$output .= get_the_title($this->currentID);
		if (isset($options['menu_aftertitle_portal']) && $options['menu_aftertitle_portal']) {
		    $output .=  ' '.$options['menu_aftertitle_portal'];
		}
		$output .= '</a>';
		$output .= '</div>';
			
		$nothumbnail  = get_post_meta( $this->currentID, 'menuquote_nothumbnail', true );	

		if ($nothumbnail==1) {
		    $thumbregion = '';
		} else {
		    $thumbregion = get_the_post_thumbnail($this->currentID,'post');
		}
	        $quote  = get_post_meta( $this->currentID, 'zitat_text', true );
	        $author =  get_post_meta( $this->currentID, 'zitat_autor', true );
		$val  = get_post_meta( $this->currentID, 'menuquote_texttype', true );	
		$texttype = ( isset( $val ) ? intval( $val ) : 0 );
		
		
		if (!empty($thumbregion)) {
		    $output .= '<div class="span4 hide-mobile introtext">';
		    if($quote) {
			if ($texttype==0) {
			    $output .= '<blockquote>';
			    $output .= '<p class="quote">'.$quote.'</p>';
			    
			    $output .= '</blockquote>';
			    if($author) $output .= '<p class="author"> &mdash; '.$author.'</p>';
			} elseif ($texttype==1) {
			     $output .= '<p>'.$quote.'</p>';
			}
		
		    }
		    $output .= '</div>';
		    $output .= '<div class="span4 hide-mobile">';		
		    $output .= $thumbregion;
		    $output .= '</div>';	
		} else {
		    $output .= '<div class="span8 hide-mobile introtext">';
		     
		     if($quote) {
			if ($texttype==0) {
			    $output .= '<blockquote>';
			    $output .= '<p class="quote">'.$quote.'</p>';
			    
			    $output .= '</blockquote>';
			    if($author) $output .= '<p class="author"> &mdash; '.$author.'</p>';
			} elseif ($texttype==1) {
			     $output .= '<p>'.$quote.'</p>';
			}

		    }
		    $output .= '</div>';	
	
		}
		$output .= '</div></div></div>';
	}
	
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
	    global $options;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$level = $depth + 1;

		$class_names = $value = '';
		$force_cleanmenu = 0;

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		if ($level<2) {
		    $classes[] = 'menu-item-' . $item->ID;
		    $classes[] = 'level' . $level;
		}	
		$rellink = fau_make_link_relative($item->url);
		if (substr($rellink,0,4) == 'http') {
		    // absoluter Link auf externe Seite
		    $classes[] = 'external';
		    $force_cleanmenu= 1;
		} elseif ($rellink == '/') {
		    // Link auf Startseite
		    $classes[] = 'homelink';
		    $force_cleanmenu = 2;
		}

		if (($options['advanced_forceclean_homelink']) && ($force_cleanmenu==1)) {
		    // Ignore homelink
		} elseif (($options['advanced_forceclean_externlink']) && ($force_cleanmenu==1)) {    
		    // Ignore external link in Main menu
		} else {
		   

		    $id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
		    $id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
	    
		    $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
		    $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		    
		    if ($level>1) {
			 $class_names = str_replace("has-sub","",$class_names);   
		    }
		    $output .= $indent . '<li' . $id . $value . $class_names .'>';
		    
		    $atts = array();
		    $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		    $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		    $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		    $atts['href']   = ! empty( $item->url )        ? $item->url        : '';

		    $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

		    if($level == 1) $this->currentID = $item->object_id;		

		    $attributes = '';
		    foreach ( $atts as $attr => $value ) {
			    if ( ! empty( $value ) ) {
				    $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				    $attributes .= ' ' . $attr . '="' . $value . '"';
			    }
		    }

		    $item_output = $args->before;
		    $item_output .= '<a'. $attributes .'>';
		    $item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		    $item_output .= '</a>';
		    $item_output .= $args->after;

		    $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
		}
	}
}

/*-----------------------------------------------------------------------------------*/
/* Create submenu icon/grid in content
/*-----------------------------------------------------------------------------------*/
function fau_get_contentmenu($menu, $submenu = 1, $subentries =0, $spalte = 0, $nothumbs = 0, $nodefthumbs = 0) {
    global $options;
    
    
    if (empty($menu)) {
	echo '<!-- no id and empty slug for menu -->';
	return;
    }
    if ($menu == sanitize_key($menu)) {
	$term = get_term_by('id', $menu, 'nav_menu');
    } else {
	$term = get_term_by('name', $menu, 'nav_menu');
    }
    if ($term===false) {
	echo '<!-- invalid menu -->';
	return;   
    }
    $slug = $term->slug;     
    
    if ($subentries==0) {
	$subentries = $options['default_submenu_entries'];
    }
     if ($spalte==0) {
	$spalte = $options['default_submenu_spalten'];
    }
   
    echo '<div class="contentmenu" role="navigation">';   
    wp_nav_menu( array( 'menu' => $slug, 'container' => false, 'items_wrap' => '%3$s', 'link_before' => '', 'link_after' => '', 'walker' => new Walker_Content_Menu($submenu,$subentries,$spalte,$nothumbs,$nodefthumbs)));
    echo "</div>\n";

    return;
}

/*-----------------------------------------------------------------------------------*/
/* Walker for subnav
/*-----------------------------------------------------------------------------------*/
class Walker_Content_Menu extends Walker_Nav_Menu {
	private $level = 1;
	private $count = array();
	private $element;
	private $showsub = 1;
	
	
	function __construct($showsub=1,$maxsecondlevel=5,$spalten=4,$noshowthumb=0,$nothumbnailfallback=0) {
	    echo '<ul class="row subpages-menu">';
	    $this->showsub = $showsub;
	    $this->maxsecondlevel = $maxsecondlevel;
	    $this->maxspalten = $spalten;
	    $this->nothumbnail = $noshowthumb;
	    $this->nothumbnailfallback = $nothumbnailfallback;
	}
	
	function __destruct() {
		echo '</ul>';
	}
	
	function start_lvl( &$output, $depth = 0, $args = array() ) {
		parent::start_lvl($output, $depth, $args);
		$this->level++;
		
		$this->count[$this->level] = 0;
	}
	
	function end_lvl( &$output, $depth = 0, $args = array() ) {
		parent::end_lvl($output, $depth, $args);
		$this->level--;
	}
	
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
	    global $options;
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';

		if (isset($this->count[$this->level])) {   
		    $this->count[$this->level]++;
		} else {
		     $this->count[$this->level] =1;
		}
		
		if($this->level == 1) {
		    $this->element = $item;
		}
		$item_output = '';
		// Only show elements on the first level and only five on the second level, but only if showdescription == FALSE
		if($this->level == 1 || ($this->level == 2 && $this->count[$this->level] <= $this->maxsecondlevel && $this->showsub == 1)) {
			$class_names = $value = '';
			$externlink = false;
			$classes = empty( $item->classes ) ? array() : (array) $item->classes;
			$classes[] = 'menu-item-' . $item->ID;
			if($this->level == 1 && (isset($this->count[$this->level])) && (($this->count[$this->level]-1) % $this->maxspalten==0) ) {
			    $classes[] = 'clear';
			}    
			if($this->level == 1) {
			    $classes[] = 'span3';
			}
		//	$classes[] = 'level'.$this->level;
		//	$classes[] = 'count'.$this->count[$this->level];
			$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args ) );
			$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

			$id = apply_filters( 'nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args );
			$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';

			if($this->level == 1) {
				$output .= $indent . '<li' . $id . $value . $class_names .'>';
			} else	{
				$output .= '<li>';
			}

			$atts = array();
			$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
			$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
			$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
			
		
			$post = get_post($item->object_id);
			if ($post && $post->post_type != 'imagelink') {
			    $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
			    $targeturl = $atts['href'];
			} else {
			    $targeturl = '';
			}

			if($this->level == 1) $atts['class'] = 'subpage-item';
			
		
			$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args );

			$attributes = '';
			foreach ( $atts as $attr => $value ) {
				if ( ! empty( $value ) ) {
					$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
					$attributes .= ' ' . $attr . '="' . $value . '"';
				}
			}
			
			$item_output = $args->before;
			if($post && $post->post_type == 'imagelink') {
				$protocol  = get_post_meta( $item->object_id, 'protocol', true );
				$link  = get_post_meta( $item->object_id, 'link', true );
				$targeturl = get_post_meta( $item->object_id, 'fauval_imagelink_url', true );
				
				 if (empty($targeturl) && isset($protocol) && isset($link)) {
				    $targeturl = $protocol.$link;
				}
				$externlink = true; 	
				$link = '<a class="ext-link" data-wpel-link="internal"  href="'.$targeturl.'">';
			} else {
				$link = '<a'. $attributes .' >';
			}

			if($this->level == 1) {
				if (!$this->nothumbnail) {
				    
				    $item_output .= '<a ';
				    
				    if ($externlink) {
					 $item_output .= 'data-wpel-link="internal" ';
				    }
				    $item_output .= 'class="image';
				    if ($externlink) {
					 $item_output .= ' ext-link';
				    }
				    $item_output .= '" href="'.$targeturl.'">';
				
				
				    $post_thumbnail_id = get_post_thumbnail_id( $item->object_id, 'page-thumb' ); 
				    $imagehtml = '';
				    $imageurl = '';
				    if ($post_thumbnail_id) {
					$thisimage = wp_get_attachment_image_src( $post_thumbnail_id,  'page-thumb');
					$imageurl = $thisimage[0]; 	
					$item_output .= '<img src="'.fau_esc_url($imageurl).'" width="'.$thisimage[1].'" height="'.$thisimage[2].'" alt="">';
				    }
				    if ((!isset($imageurl) || (strlen(trim($imageurl)) <4 )) && (!$this->nothumbnailfallback))  {
					$imageurl = $options['default_submenuthumb_src'];
					$item_output .= '<img src="'.fau_esc_url($imageurl).'" width="'.$options['default_submenuthumb_width'].'" height="'.$options['default_submenuthumb_height'].'" alt="">';
				    }
				    $item_output .= '</a>';
				    
				    // Anmerkdung: Leeres alt="", da dieses ansonsten redundant wäre zum darunter stehenden Titel.
				}
				

				$item_output .= $args->link_before.'<h3>';
				$item_output .= $link;
				$item_output .=  apply_filters( 'the_title', $item->title, $item->ID );
				$item_output .= '</a>';
				$item_output .= '</h3>'. $args->link_after;
			} else {
				$item_output .= $link;
				$item_output .= $args->link_before.apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
				$item_output .= '</a>';
			}

			
			$item_output .= $args->after;
			
			
			if(!($this->showsub==1) && ($this->level == 1)) {
			     $desc  = get_post_meta( $item->object_id, 'portal_description', true );
			     // Wird bei Bildlink definiert
			     if ($desc) {
				$item_output .= '<p>'.$desc.'</p>';
			     }	
			}
		}
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
	
	function end_el(&$output, $item, $depth=0, $args=array()) {      
		if($this->level == 1 || ($this->level == 2 && $this->count[$this->level] <= $this->maxsecondlevel))	{
			if($this->level == 1) $output .= "</li>\n";  
			else $output .= "</li>\n";
		} elseif(($this->level == 2) && ($this->count[$this->level] == ($this->maxsecondlevel+1)) && ($this->showsub == 1)) {
			$output .= '<li class="more"><a href="'.$this->element->url.'">'. __('Mehr', 'fau').' ...</a></li>';
		}	
	}     
}



/*-----------------------------------------------------------------------------------*/
/* Create breadcrumb
/*-----------------------------------------------------------------------------------*/
function fau_breadcrumb($lasttitle = '') {
  global $options;
  
  $delimiter	= $options['breadcrumb_delimiter']; // = ' / ';
  $home		= $options['breadcrumb_root']; // __( 'Startseite', 'fau' ); // text for the 'Home' link
  $before	= $options['breadcrumb_beforehtml']; // '<span class="current">'; // tag before the current crumb
  $after	= $options['breadcrumb_afterhtml']; // '</span>'; // tag after the current crumb
  $pretitletextstart   = '<span>';
  $pretitletextend     = '</span>';
  
  if ($options['breadcrumb_withtitle']) {
	echo '<h3 class="breadcrumb_sitetitle" role="presentation">'.get_bloginfo( 'title' ).'</h3>';
	echo "\n";
    }
  echo '<nav aria-labelledby="bc-title" class="breadcrumbs">'; 
  echo '<h4 class="screen-reader-text" id="bc-title">'.__('Sie befinden sich hier:','fau').'</h4>';
  if ( !is_home() && !is_front_page() || is_paged() ) { 
    
    global $post;
    
    $homeLink = home_url('/');
    echo '<a href="' . $homeLink . '">' . $home . '</a>' . $delimiter;
 
    if ( is_category() ) {
	global $wp_query;
	$cat_obj = $wp_query->get_queried_object();
	$thisCat = $cat_obj->term_id;
	$thisCat = get_category($thisCat);
	$parentCat = get_category($thisCat->parent);
	if ($thisCat->parent != 0) 
	    echo(get_category_parents($parentCat, TRUE, $delimiter ));
	echo $before . single_cat_title('', false) .  $after;
 
    } elseif ( is_day() ) {
	echo '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' .$delimiter;
	echo '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a>' .$delimiter;
	echo $before . get_the_time('d') . $after; 
    } elseif ( is_month() ) {
	echo '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a>' . $delimiter;
	echo $before . get_the_time('F') . $after;
    } elseif ( is_year() ) {
	echo $before . get_the_time('Y') . $after; 
    } elseif ( is_single() && !is_attachment() ) {
	 
	if ( get_post_type() != 'post' ) {
	    $post_type = get_post_type_object(get_post_type());
	    $slug = $post_type->rewrite;
	    echo '<a href="' . $homeLink . '/' . $slug['slug'] . '/">' . $post_type->labels->singular_name . '</a>' .$delimiter;
	    echo $before . get_the_title() . $after; 
	} else {
	    
	$cat = get_the_category(); 
	if ($options['breadcrumb_uselastcat']) {
	    $last = array_pop($cat);
	} else {
	    $last = $cat[0];
	}
	$catid = $last->cat_ID;

	echo get_category_parents($catid, TRUE,  $delimiter );
	echo $before . get_the_title() . $after;

	} 
    } elseif ( !is_single() && !is_page() && !is_search() && get_post_type() != 'post' && !is_404() ) {
	$post_type = get_post_type_object(get_post_type());
	echo $before . $post_type->labels->singular_name . $after;
    } elseif ( is_attachment() ) {
	$parent = get_post($post->post_parent);
	echo '<a href="' . get_permalink($parent) . '">' . $parent->post_title . '</a>'. $delimiter;
	echo $before . get_the_title() . $after;
    } elseif ( is_page() && !$post->post_parent ) {
	echo $before . get_the_title() . $after;
 
    } elseif ( is_page() && $post->post_parent ) {
	$parent_id  = $post->post_parent;
	$breadcrumbs = array();
	while ($parent_id) {
	    $page = get_page($parent_id);
	    $breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
	    $parent_id  = $page->post_parent;
	}
	$breadcrumbs = array_reverse($breadcrumbs);
	foreach ($breadcrumbs as $crumb) echo $crumb . $delimiter;
	echo $before . get_the_title() . $after; 
    } elseif ( is_search() ) {
	if (isset($lasttitle) && (strlen(trim($lasttitle))>1)) {
	    echo $before . $lasttitle. $after; 
	} else {
	    echo $before .$pretitletextstart. __( 'Suche nach', 'fau' ).$pretitletextend.' "' . get_search_query() . '"' . $after; 
	}
    } elseif ( is_tag() ) {
	echo $before .$pretitletextstart. __( 'Schlagwort', 'fau' ).$pretitletextend. ' "' . single_tag_title('', false) . '"' . $after; 
    } elseif ( is_author() ) {
	global $author;
	$userdata = get_userdata($author);
	echo $before .$pretitletextstart. __( 'Beiträge von', 'fau' ).$pretitletextend.' '.$userdata->display_name . $after;
    } elseif ( is_404() ) {
	echo $before . '404' . $after;
    }

  } elseif (is_front_page())  {
	echo $before . $home . $after;
  } elseif (is_home()) {
	echo $before . get_the_title(get_option('page_for_posts')) . $after;
  }
   echo '</nav>'; 

}
