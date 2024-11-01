<?php
/*
Plugin Name: Tags
Plugin URI: http://boke.name/c/wordpress-tags
Description: A plugin that tag each entry of the blog, and give a tag access method and tag list.
Author: Felix Wong
Author URI: http://boke.name/
Version: 0.3.0
Email: felix@cenrik.net

It is compatible with Wordpress 1.5.

Tags
Copyright(C) 2005, Felix Wong

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/*
INSTALLATION NOTES

1. Put tags.php into wp-content/plugins.

2. To install this plugin, you have to modify 3 wordpress files for Tags support.
	wp-admin/edit-form.php
	wp-admin/edit-form-advanced.php
	wp-admin/edit-page-form.php

or you can use the patch instead.

--- wp-admin/edit-form.php ---
--- LOOK FOR ---
    <fieldset id="titlediv">
      <legend><a href="http://wordpress.org/docs/reference/post/#title" title="<?php _e('Help on titles') ?>"><?php _e('Title') ?></a></legend>
          <div><input type="text" name="post_title" size="30" tabindex="1" value="<?php echo $edited_post_title; ?>" id="title" /></div>
    </fieldset>

-- ADD AFTER ---

    <!-- Tags Plugin Begin -->
    <fieldset id="titlediv">
        <legend><a href="http://boke.name/c/blog/wordpress-tags"><?php _e('Tags'); ?></a></legend>
        <div><input type="text" tabindex="2" name="post_tags" size="30" value="<?php echo $edited_post_tags; ?>" id="tags" /></div>
    </fieldset>
    <!-- Tags End -->

--- wp-admin/edit-form-advanced.php ---
--- LOOK FOR ---
</fieldset>
    <fieldset id="postpassworddiv">
      <legend><a href="http://wordpress.org/docs/reference/post/#post_password" title="<?php _e('Help on post password') ?>"><?php _e('Post Password') ?></a></legend>
          <div><input name="post_password" type="text" size="13" id="post_password" value="<?php echo $post_password ?>" /></div>
    </fieldset>

--- ADD AFTER ---
    <!-- Tags Plugin Begin -->
    <fieldset id="titlediv">
        <legend><a href="http://boke.name/c/blog/wordpress-tags"><?php _e('Tags'); ?></a></legend>
        <div><input type="text" tabindex="2" name="post_tags" size="30" value="<?php echo $edited_post_tags; ?>" id="tags" /></div>
    </fieldset>
    <!-- Tags End -->


--- wp-admin/edit-page-form.php ---
--- LOOK FOR ---
    <fieldset id="pageparent">
      <legend><?php _e('Page Parent') ?></legend>
          <div><select name="parent_id">
          <option value='0'><?php _e('Main Page (no parent)'); ?></option>
                        <?php parent_dropdown($post_parent); ?>
        </select>
          </div>
    </fieldset>

--- ADD AFTER ---
    <!-- Tags Plugin Begin -->
    <fieldset id="titlediv">
        <legend><a href="http://boke.name/c/blog/wordpress-tags"><?php _e('Tags'); ?></a></legend>
        <div><input tabindex="2" type="text" name="post_tags" size="30" value="<?php echo $edited_post_tags; ?>" id="tags" /></div>
    </fieldset>
    <!-- Tags End -->

3. Activate the plugin, go to Manage/Tags page so the plugin can create
a table for you. Then you can go to Options/Permal Link to generate
new Rewrite rules.

4. DONE.
You can access your tags with:
	http://website/index.php?tags=tag1+tag2
or, with permal link:
	http://website/tags/tag1+tag2
    
*/

//require_once(ABSPATH . '/wp-includes/nusoap.php');

function tags_add_menu() {
	add_management_page(__('Tags Management'), __('Tags'), 8, 'tags.php', 'tags_tags_page');
	add_options_page(__('Tags Settings'), __('Tags'), 8, __FILE__, 'tags_settings_page');
}

function tags_tags_page() {
	global $wpdb, $WP_TAGS_ITEMS;

	$request = "SHOW TABLES;";
	$rows = $wpdb->get_results($request);
	$table1 = false;
	foreach($rows as $table) {
		foreach($table as $value) if($value==$WP_TAGS_ITEMS) $table1 = true;
	}

	if(!$table1):
		$request = "CREATE TABLE `$WP_TAGS_ITEMS` (
			`tag_item_id` bigint(20) NOT NULL auto_increment,
			`post_id` mediumint(9) NOT NULL default '0',
			`tag_name` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`tag_item_id`),
			KEY `post_id` (`post_id`),
			KEY `tag_name` (`tag_name`)
	  	);";
	  	$wpdb->query($request);
?>
	<div class="wrap">
		<h2><?=_e("Installation")?></h2>
		<p><?=_e("You are the first time to activate Tags plugin. The Tags table has been created for you to use Tags plugin. You may deactivate the plugin and delete the table manually.")?></p>
	</div>
<?
	endif;
?>

<div class="wrap">
	<h2><?=_e('Tags List')?></h2>
	<?php list_tags(', ', false, false, 'ALL'); ?>
</div>
<?

}

function tags_settings_page() {
?>
<div class="wrap">
<h2><?=_e("Tags Settings")?></h2>
<form name="tagsoptions" method="post" action="options.php">
	<input type="hidden" name="action" value="udpate" />
	<input type="hidden" name="page_options"/>
</form>
</div>
<?
}


function tags_parse_query(&$param) {
	global $_tags;
	$wp_query =& $param;
	$tags = rawurldecode($wp_query->get('tags'));
	//$tags = iconv('GB2312', 'UTF-8', $tags);
	$tags = preg_split("/\s+/", $tags);
	$_tags = $tags;
}

function &tags_add_tags_support(&$param) {
	global $edited_post_tags, $wpdb, $WP_TAGS_ITEMS, $post_ID;
	$request = "SELECT * FROM $WP_TAGS_ITEMS WHERE post_id=$post_ID";
	$rows = $wpdb->get_results($request);
	if(count($rows)==0) {
		$edited_post_tags = "";
		return $param;
	}

	$edited_post_tags = array();
	for($i=0; $i<count($rows); $i++)
		$edited_post_tags[] = $rows[$i]->tag_name;
	$edited_post_tags = array_unique($edited_post_tags);
	$edited_post_tags = join( ' ', $edited_post_tags );

	return $param;
}

function tags_save_post($post_ID) {
	global $wpdb, $WP_TAGS_ITEMS, $WP_TAGS;
	$tags = rawurldecode($tags);
	$tags = trim($_POST['post_tags']);
	$tags = preg_split('/\s+/', $tags);
	$tags = array_unique($tags);
	$tags = array_filter( $tags, tags_empty_check );
	
	$request = "DELETE FROM $WP_TAGS_ITEMS WHERE post_id=$post_ID";
	$wpdb->query($request);
	
	if(count($tags)==0)
		return;

	foreach($tags as $tag) {
		$tag = addslashes(strtolower($tag));
		$request = "INSERT INTO $WP_TAGS_ITEMS (post_id, tag_name) VALUES ($post_ID,'$tag')";
		$wpdb->query($request);
	}
}

function tags_shutdown(&$param) {
	global $_tags;
	unset($_tags);
}

function &tags_filter_query_vars(&$query_vars) {
	$query_vars[] = 'tags';
	return $query_vars;
}

function tags_empty_check(&$elem) {
	return !empty($elem);
}

function &tags_filter_posts_join(&$join) {
	global $_tags, $WP_TAGS_ITEMS, $WP_TAGS, $wpdb;

	$_tags = array_filter( $_tags, tags_empty_check );
	// if no tag is specified, return
	if(count($_tags)==0)
		return $join;

	$join .= " ";
	$join .= "JOIN $WP_TAGS_ITEMS ON ($wpdb->posts.ID=$WP_TAGS_ITEMS.post_id AND ( 0";
	foreach($_tags as $tag) {
		$tag = addslashes($tag);
		$join .= " OR $WP_TAGS_ITEMS.tag_name='$tag'";
	}
	$join .= ")) ";
	
	return $join;
}

function &tags_filter_posts_where(&$where) {
	global $_tags, $WP_TAGS_ITEMS, $WP_TAGS, $wpdb;
	
	$_tags = array_filter( $_tags, tags_empty_check );
	$num_tags = count($_tags);
	if($num_tags == 0)
		return $where;
	
	$where .= " ";
	return $where;
}

function &tags_filter_the_posts(&$the_posts) {
	global $_tags, $WP_TAGS_ITEMS, $WP_TAGS, $wpdb;
	
	$_tags = array_filter( $_tags, tags_empty_check );
	$num_tags = count($_tags);
	
	if($num_tags == 0)
		return $the_posts;

	foreach($the_posts as $key=>$post) {
		$post_id = $post->ID;
		$request = "SELECT DISTINCT COUNT(*) AS c FROM $WP_TAGS_ITEMS WHERE post_id='$post_id' AND (0";
		foreach($_tags as $tag) {
			$tag = addslashes($tag);
			$request .= " OR tag_name='$tag'";
		}
		$request .= ")";
		$rows = $wpdb->get_results($request);

		if(count($rows)==0)
			continue;

		$post_count = intval($rows[0]->c);
		if($post_count < $num_tags) {
			unset($the_posts[$key]);
		}
	}
	
	if(count($the_posts)>0)
		array_unshift( $the_posts, array_shift($the_posts) );
	return $the_posts;
}

function &tags_filter_rewrite_rules_array(&$rules) {
	$work["tags/?$"] = 'index.php?&tags=';
	$work["untagged/?$"] = 'index.php?&untagged=';
	$work["tagsearch/rpc/?$"] = "soap.php";
	$work["tagsearch/1.0/wsdl/?$"] = "wsdl/tags.wsdl";
	$work["tags/([^/]+)/?$"] = 'index.php?&tags=$1';
	$work["tags/([^/]+)/([0-9]{4})/?$"] = 'index.php?year=$2&tags=$1';
	$work["tags/([^/]+)/([0-9]{4})/([0-9]{1,2})/?$"] = 'index.php?year=$2&monthnum=$3&tags=$1';
	$work["tags/([^/]+)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$"] = 'index.php?year=$2&monthnum=$3&day=$4&tags=$1';
	$work["tags/([^/]+)/(feed|atom|rss|rss2|rdf)/?$"] = 'index.php?&tags=$1&feed=$2';
	$work["tags/([^/]+)/([0-9]{4})/(feed|atom|rss|rss2|rdf)/?$"] = 'index.php?year=$2&tags=$1&feed=$3';
	$work["tags/([^/]+)/([0-9]{4})/([0-9]{1,2})/(feed|atom|rss|rss2|rdf)/?$"] = 'index.php?year=$2&monthnum=$3&tags=$1&feed=$4';
	$work["tags/([^/]+)/([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(feed|atom|rss|rss2|rdf)/?$"] = 'index.php?year=$2&monthnum=$3&day=$4&tags=$1&feed=$5';
	$work["tags/([^/]+)/page/?([0-9]{1,})/?$"] = 'index.php?&tags=$1&paged=$2';
	$work["tags/([^/]+)/([0-9]{4})/page/?([0-9]{1,})/?$"] = 'index.php?year=$2&tags=$1&paged=$3';
	$i = 0;
	foreach($rules as $key => $value) {
		if( ctype_alpha($key[0]) || ctype_digit($key[0]) )  break;
		$i++;
	}
	$temp = array_splice($rules, $i);
	$rules = array_merge( $rules, $work, $temp);
	return $rules;
}

function tags_delete_post($post_ID) {
	global $WP_TAGS_ITEMS, $wpdb;

	$request = "DELETE FROM $WP_TAGS_ITEMS WHERE post_id=$post_ID";
	$wpdb->query($request);
}

function get_tags_template() {
	$template = '';
	if(file_exists( TEMPLATEPATH . "/tags.php" ))
		$template = TEMPLATEPATH . "/tags.php";
	else if(file_exists( TEMPLATEPATH . "/index.php"))
		$template = TEMPLATEPATH . "/index.php";
	return $template;
}

function tags_template_redirect() {
	if( (is_tags() || is_tags_list())&& get_tags_template() ) {
		include( get_tags_template() );
		exit;
	}
	if($_GET['tagimage']==1) {
		tag_list_image(true, true, 'ALL');
		exit;
	}
}

function is_tags() {
	return ( isset($_REQUEST['tags']) || isset($_REQUEST['untagged']));
}

function is_tags_list() {
	return isset($_REQUEST['tags']) && $_REQUEST['tags']=='';
}

/**
 * template functions
 */

function have_tags() {
	global $post, $id, $wpdb, $WP_TAGS_ITEMS;

	if(!isset($id))
		$id = $post->ID;
	
	// if the post is invalid, return
	if(intval($id)==0)
		return false;

	$request = "SELECT tag_name FROM $WP_TAGS_ITEMS WHERE post_id='$id' LIMIT 1";
	$rows = $wpdb->get_results($request);

	// no tags are found
	if(count($rows)==0)
		return false;

	// at least one tag is found
	return true;
}

function get_tag_link($tag) {
	global $wp_rewrite;

	if($wp_rewrite->using_mod_rewrite_permalinks()) {
		$permalink = get_settings('siteurl') . '/tags/' . $tag;
	}
	else {
		$permalink = get_settings('siteurl') . '/index.php?tags='. $tag;
	}
	return $permalink;
}

function tags_title($delimeter="+", $target="_self") {
	$tagsTemp = rawurldecode(trim($_REQUEST['tags']));
	$tagsTemp = preg_split('/\s+/', $tagsTemp);
	$tags = array();
	foreach($tagsTemp as $tag) {
		$tags[] = "<a href=\"".get_tag_link($tag)."\" target=\"$target\">".$tag."</a>";
	}
	$tags = join($delimeter, $tags);
	echo $tags;
}

function the_tags($delimeter = ", ", $echo = false, $link = true, $target = "_self") {
	global $id, $wpdb, $WP_TAGS_ITEMS, $post;

	if(!isset($id))
		$id = $post->ID;
	// if the post id is invalid, return
	if(intval($id)==0)
		return;

	// cope with tags
	$request = "SELECT tag_name FROM $WP_TAGS_ITEMS WHERE post_id='$id' LIMIT 100";
	$rows = $wpdb->get_results($request);
	
	// no tags found
	if(count($rows)==0) {
		return;
	}
	
	$tags = array();
	foreach($rows as $row) {
		if($link)
			$tags[] = "<a href=\"" . get_tag_link($row->tag_name) . "\" target=\"$target\">".$row->tag_name."</a>";
		else
			$tags[] = $row->tag_name;
	}

	$tags = join($delimeter, $tags);
	if($echo)
		echo $tags;
	return $tags;
}

function list_tags($delimeter=", ", $sort = true, $order = true, $count = 12, $list_only = false) {
	global $wpdb, $WP_TAGS_ITEMS;
	
	// count all tags
	$tags_quantity = array();
	if($sort) {
		if($order)
			$asc_desc = "DESC";
		else
			$asc_desc = "ASC";
		$order_by = "ORDER BY num";
	}
	else {
		$order_by = "ORDER BY tag_name";
		$asc_desc = "ASC";
	}

	if($count=="ALL") {
		$limit = "";
	}
	else if(is_numeric($count)) {
		$limit = "LIMIT $count";
	}
	else {
		$limit = "LIMIT 12";
	}
	
	$request = "SELECT tag_name, COUNT(post_id) AS num FROM $WP_TAGS_ITEMS GROUP BY tag_name $order_by $asc_desc $limit";
	$rows = $wpdb->get_results($request);
	// no tags are found, left
	if(count($rows)==0)
		return;
	$tags = array();

	if($list_only) {
		foreach($rows as $row)
			$tags[] = $row->tag_name;
		return $tags;
	}
	else {
		foreach($rows as $row) {
			$tags[] = "<a href=\"".get_tag_link($row->tag_name)."\">".$row->tag_name."</a> ($row->num)";
		}
	}
	$tags = join($delimeter, $tags);
	echo $tags;
}

function tag_list_image($sort = true, $order = true, $count = 12) {
	$tags = @list_tags("", $sort, $order, $count, true);
	putenv('GDFONTPATH='.ABSPATH);
	$font = 'comic';
	$max_font_size = 30;
	$width = 600;
	$height = $width*.618;
	$image = imagecreatetruecolor($width, $height);
	$bg = imagecolorallocate($image, 255, 255, 255);
	imagefill($image, 0, 0, $bg);
	$total = count($tags);
	$center_x = $width*(1-0.618);
	$center_y = $height*0.618;
	for($i = $total-1; $i>=0; $i--) {
		$size = ($total-$i)/$total*$max_font_size;
		$alpha = $i/$total*127;
		$bbox = imagettfbbox($size, 0, $font, $tags[$i]);
		$radius = $height*log(1+$i/$total)+rand(0,20)-40;
		$angle = deg2rad(rand(0, 360));
		$x = $center_x + $radius*sin($angle);
		$y = $center_y + $radius*cos($angle);
		$textcolor = imagecolorallocatealpha($image, rand(0,255), rand(0,255), rand(0,255), $alpha);
		imagettftext($image,($total-$i)/$total*$max_font_size, 0, $x, $y, $textcolor, $font, $tags[$i]);
	}
	
	header("Content-type: image/jpeg");
	imagejpeg($image);
	imagedestroy($image);
}

add_action('admin_menu', 'tags_add_menu');
add_action('parse_query', 'tags_parse_query');
add_action('shutdown', 'tags_shutdown');
add_action('edit_post', 'tags_save_post');
add_action('save_post', 'tags_save_post');
add_action('delete_post', 'tags_delete_post');
add_action('template_redirect', 'tags_template_redirect');

add_filter('query_vars', 'tags_filter_query_vars');
add_filter('posts_join', 'tags_filter_posts_join');
add_filter('posts_where', 'tags_filter_posts_where');
add_filter('the_posts', 'tags_filter_the_posts');
add_filter('rewrite_rules_array', 'tags_filter_rewrite_rules_array');
add_filter('content_edit_pre', 'tags_add_tags_support');


$WP_TAGS_ITEMS = $table_prefix . "tags_items";
$_tags = array();


/*****
		    GNU GENERAL PUBLIC LICENSE
		       Version 2, June 1991

 Copyright (C) 1989, 1991 Free Software Foundation, Inc.
                       59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 Everyone is permitted to copy and distribute verbatim copies
 of this license document, but changing it is not allowed.

			    Preamble

  The licenses for most software are designed to take away your
freedom to share and change it.  By contrast, the GNU General Public
License is intended to guarantee your freedom to share and change free
software--to make sure the software is free for all its users.  This
General Public License applies to most of the Free Software
Foundation's software and to any other program whose authors commit to
using it.  (Some other Free Software Foundation software is covered by
the GNU Library General Public License instead.)  You can apply it to
your programs, too.

  When we speak of free software, we are referring to freedom, not
price.  Our General Public Licenses are designed to make sure that you
have the freedom to distribute copies of free software (and charge for
this service if you wish), that you receive source code or can get it
if you want it, that you can change the software or use pieces of it
in new free programs; and that you know you can do these things.

  To protect your rights, we need to make restrictions that forbid
anyone to deny you these rights or to ask you to surrender the rights.
These restrictions translate to certain responsibilities for you if you
distribute copies of the software, or if you modify it.

  For example, if you distribute copies of such a program, whether
gratis or for a fee, you must give the recipients all the rights that
you have.  You must make sure that they, too, receive or can get the
source code.  And you must show them these terms so they know their
rights.

  We protect your rights with two steps: (1) copyright the software, and
(2) offer you this license which gives you legal permission to copy,
distribute and/or modify the software.

  Also, for each author's protection and ours, we want to make certain
that everyone understands that there is no warranty for this free
software.  If the software is modified by someone else and passed on, we
want its recipients to know that what they have is not the original, so
that any problems introduced by others will not reflect on the original
authors' reputations.

  Finally, any free program is threatened constantly by software
patents.  We wish to avoid the danger that redistributors of a free
program will individually obtain patent licenses, in effect making the
program proprietary.  To prevent this, we have made it clear that any
patent must be licensed for everyone's free use or not licensed at all.

  The precise terms and conditions for copying, distribution and
modification follow.

		    GNU GENERAL PUBLIC LICENSE
   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

  0. This License applies to any program or other work which contains
a notice placed by the copyright holder saying it may be distributed
under the terms of this General Public License.  The "Program", below,
refers to any such program or work, and a "work based on the Program"
means either the Program or any derivative work under copyright law:
that is to say, a work containing the Program or a portion of it,
either verbatim or with modifications and/or translated into another
language.  (Hereinafter, translation is included without limitation in
the term "modification".)  Each licensee is addressed as "you".

Activities other than copying, distribution and modification are not
covered by this License; they are outside its scope.  The act of
running the Program is not restricted, and the output from the Program
is covered only if its contents constitute a work based on the
Program (independent of having been made by running the Program).
Whether that is true depends on what the Program does.

  1. You may copy and distribute verbatim copies of the Program's
source code as you receive it, in any medium, provided that you
conspicuously and appropriately publish on each copy an appropriate
copyright notice and disclaimer of warranty; keep intact all the
notices that refer to this License and to the absence of any warranty;
and give any other recipients of the Program a copy of this License
along with the Program.

You may charge a fee for the physical act of transferring a copy, and
you may at your option offer warranty protection in exchange for a fee.

  2. You may modify your copy or copies of the Program or any portion
of it, thus forming a work based on the Program, and copy and
distribute such modifications or work under the terms of Section 1
above, provided that you also meet all of these conditions:

    a) You must cause the modified files to carry prominent notices
    stating that you changed the files and the date of any change.

    b) You must cause any work that you distribute or publish, that in
    whole or in part contains or is derived from the Program or any
    part thereof, to be licensed as a whole at no charge to all third
    parties under the terms of this License.

    c) If the modified program normally reads commands interactively
    when run, you must cause it, when started running for such
    interactive use in the most ordinary way, to print or display an
    announcement including an appropriate copyright notice and a
    notice that there is no warranty (or else, saying that you provide
    a warranty) and that users may redistribute the program under
    these conditions, and telling the user how to view a copy of this
    License.  (Exception: if the Program itself is interactive but
    does not normally print such an announcement, your work based on
    the Program is not required to print an announcement.)

These requirements apply to the modified work as a whole.  If
identifiable sections of that work are not derived from the Program,
and can be reasonably considered independent and separate works in
themselves, then this License, and its terms, do not apply to those
sections when you distribute them as separate works.  But when you
distribute the same sections as part of a whole which is a work based
on the Program, the distribution of the whole must be on the terms of
this License, whose permissions for other licensees extend to the
entire whole, and thus to each and every part regardless of who wrote it.

Thus, it is not the intent of this section to claim rights or contest
your rights to work written entirely by you; rather, the intent is to
exercise the right to control the distribution of derivative or
collective works based on the Program.

In addition, mere aggregation of another work not based on the Program
with the Program (or with a work based on the Program) on a volume of
a storage or distribution medium does not bring the other work under
the scope of this License.

  3. You may copy and distribute the Program (or a work based on it,
under Section 2) in object code or executable form under the terms of
Sections 1 and 2 above provided that you also do one of the following:

    a) Accompany it with the complete corresponding machine-readable
    source code, which must be distributed under the terms of Sections
    1 and 2 above on a medium customarily used for software interchange; or,

    b) Accompany it with a written offer, valid for at least three
    years, to give any third party, for a charge no more than your
    cost of physically performing source distribution, a complete
    machine-readable copy of the corresponding source code, to be
    distributed under the terms of Sections 1 and 2 above on a medium
    customarily used for software interchange; or,

    c) Accompany it with the information you received as to the offer
    to distribute corresponding source code.  (This alternative is
    allowed only for noncommercial distribution and only if you
    received the program in object code or executable form with such
    an offer, in accord with Subsection b above.)

The source code for a work means the preferred form of the work for
making modifications to it.  For an executable work, complete source
code means all the source code for all modules it contains, plus any
associated interface definition files, plus the scripts used to
control compilation and installation of the executable.  However, as a
special exception, the source code distributed need not include
anything that is normally distributed (in either source or binary
form) with the major components (compiler, kernel, and so on) of the
operating system on which the executable runs, unless that component
itself accompanies the executable.

If distribution of executable or object code is made by offering
access to copy from a designated place, then offering equivalent
access to copy the source code from the same place counts as
distribution of the source code, even though third parties are not
compelled to copy the source along with the object code.

  4. You may not copy, modify, sublicense, or distribute the Program
except as expressly provided under this License.  Any attempt
otherwise to copy, modify, sublicense or distribute the Program is
void, and will automatically terminate your rights under this License.
However, parties who have received copies, or rights, from you under
this License will not have their licenses terminated so long as such
parties remain in full compliance.

  5. You are not required to accept this License, since you have not
signed it.  However, nothing else grants you permission to modify or
distribute the Program or its derivative works.  These actions are
prohibited by law if you do not accept this License.  Therefore, by
modifying or distributing the Program (or any work based on the
Program), you indicate your acceptance of this License to do so, and
all its terms and conditions for copying, distributing or modifying
the Program or works based on it.

  6. Each time you redistribute the Program (or any work based on the
Program), the recipient automatically receives a license from the
original licensor to copy, distribute or modify the Program subject to
these terms and conditions.  You may not impose any further
restrictions on the recipients' exercise of the rights granted herein.
You are not responsible for enforcing compliance by third parties to
this License.

  7. If, as a consequence of a court judgment or allegation of patent
infringement or for any other reason (not limited to patent issues),
conditions are imposed on you (whether by court order, agreement or
otherwise) that contradict the conditions of this License, they do not
excuse you from the conditions of this License.  If you cannot
distribute so as to satisfy simultaneously your obligations under this
License and any other pertinent obligations, then as a consequence you
may not distribute the Program at all.  For example, if a patent
license would not permit royalty-free redistribution of the Program by
all those who receive copies directly or indirectly through you, then
the only way you could satisfy both it and this License would be to
refrain entirely from distribution of the Program.

If any portion of this section is held invalid or unenforceable under
any particular circumstance, the balance of the section is intended to
apply and the section as a whole is intended to apply in other
circumstances.

It is not the purpose of this section to induce you to infringe any
patents or other property right claims or to contest validity of any
such claims; this section has the sole purpose of protecting the
integrity of the free software distribution system, which is
implemented by public license practices.  Many people have made
generous contributions to the wide range of software distributed
through that system in reliance on consistent application of that
system; it is up to the author/donor to decide if he or she is willing
to distribute software through any other system and a licensee cannot
impose that choice.

This section is intended to make thoroughly clear what is believed to
be a consequence of the rest of this License.

  8. If the distribution and/or use of the Program is restricted in
certain countries either by patents or by copyrighted interfaces, the
original copyright holder who places the Program under this License
may add an explicit geographical distribution limitation excluding
those countries, so that distribution is permitted only in or among
countries not thus excluded.  In such case, this License incorporates
the limitation as if written in the body of this License.

  9. The Free Software Foundation may publish revised and/or new versions
of the General Public License from time to time.  Such new versions will
be similar in spirit to the present version, but may differ in detail to
address new problems or concerns.

Each version is given a distinguishing version number.  If the Program
specifies a version number of this License which applies to it and "any
later version", you have the option of following the terms and conditions
either of that version or of any later version published by the Free
Software Foundation.  If the Program does not specify a version number of
this License, you may choose any version ever published by the Free Software
Foundation.

  10. If you wish to incorporate parts of the Program into other free
programs whose distribution conditions are different, write to the author
to ask for permission.  For software which is copyrighted by the Free
Software Foundation, write to the Free Software Foundation; we sometimes
make exceptions for this.  Our decision will be guided by the two goals
of preserving the free status of all derivatives of our free software and
of promoting the sharing and reuse of software generally.

			    NO WARRANTY

  11. BECAUSE THE PROGRAM IS LICENSED FREE OF CHARGE, THERE IS NO WARRANTY
FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE LAW.  EXCEPT WHEN
OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR OTHER PARTIES
PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESSED
OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE.  THE ENTIRE RISK AS
TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU.  SHOULD THE
PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY SERVICING,
REPAIR OR CORRECTION.

  12. IN NO EVENT UNLESS REQUIRED BY APPLICABLE LAW OR AGREED TO IN WRITING
WILL ANY COPYRIGHT HOLDER, OR ANY OTHER PARTY WHO MAY MODIFY AND/OR
REDISTRIBUTE THE PROGRAM AS PERMITTED ABOVE, BE LIABLE TO YOU FOR DAMAGES,
INCLUDING ANY GENERAL, SPECIAL, INCIDENTAL OR CONSEQUENTIAL DAMAGES ARISING
OUT OF THE USE OR INABILITY TO USE THE PROGRAM (INCLUDING BUT NOT LIMITED
TO LOSS OF DATA OR DATA BEING RENDERED INACCURATE OR LOSSES SUSTAINED BY
YOU OR THIRD PARTIES OR A FAILURE OF THE PROGRAM TO OPERATE WITH ANY OTHER
PROGRAMS), EVEN IF SUCH HOLDER OR OTHER PARTY HAS BEEN ADVISED OF THE
POSSIBILITY OF SUCH DAMAGES.

		     END OF TERMS AND CONDITIONS
*/
?>
