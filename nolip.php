<?php
/*
Plugin Name: NoLIP: Nofollow Links in Posts Reborn!
Plugin URI: http://www.theresabloginmysoup.com/wordpress-plugins/nolip/
Description: Adds the rel="nofollow" to links in posts within a selected category. Useful for sponsored posts.
Author: Patrick Curl
Version: 2.0
Author URI: http://www.theresabloginmysoup.com/
*/
/*
    Copyright (C) 2009 Patrick Curl @ TheresABlogInMySoup.com
    Originally developed by: Ibnu Asad @ TheMiak.com

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

if(!function_exists('make_nofollow_links'))
{


function make_nofollow_links($content)
{
global $post;


  if(isset($post->ID) && is_numeric($post->ID))
  {
  $post2cats = wp_get_post_categories($post->ID);
  
  //getting global settings 
   $default_older = get_option('global_nofollow_settings');
   $default_older = trim($default_older) == '' || !is_numeric($default_older)?-1:$default_older;

//  $added_no_follow_cats = unserialize(get_option('nofollow_cats'));
  $added_no_follow_cats = get_option('nofollow_cats');
  $added_no_follow_cats = !is_array($added_no_follow_cats)?unserialize($added_no_follow_cats):$added_no_follow_cats;
				 
  $added_no_follow_cats = !is_array($added_no_follow_cats)?array():$added_no_follow_cats;
  $filter_categories = array_intersect($post2cats,array_keys($added_no_follow_cats));
  //getting the post date
  $post_date_time = explode(" ",$post->post_date);
  $post_dateonly = explode("-",$post_date_time[0]);
  $post_timeonly = explode(":",$post_date_time[1]);
  $timediff = time() - mktime($post_timeonly[0],$post_timeonly[1],$post_timeonly[2],$post_dateonly[1],$post_dateonly[2],$post_dateonly[0]);
$totalDaysNofollow = floor($timediff/(60*60*24));


 if($default_older > 0)
 {
if($totalDaysNofollow >= $cat_date)
      {
  $content = make_nofollow( $content );
    }
 }
else  if(count($filter_categories) > 0)
                       {
                $proceed_nofollow = true;

                         //do filter for categories
                             for($i=0;$i<count($filter_categories);$i++)
                               {
                                                 if(!isset($cat_date))
                                                   {
                                                   $cat_date = $added_no_follow_cats[$filter_categories[$i]]['numdays'];
                                                   $over_ride = $added_no_follow_cats[$filter_categories[$i]]['overrideind'];
                                                   }
                                                   else
                                                   {
                                                     if($added_no_follow_cats[$filter_categories[$i]]['numdays'] > $cat_date)
                                                          {
                                                          $cat_date = $added_no_follow_cats[$filter_categories[$i]]['numdays'];
                                                          $over_ride = $added_no_follow_cats[$filter_categories[$i]]['overrideind'];                                                                                                              }
                                                    }

                                   }
                             if($over_ride == 1)
                                 {
                                    if(get_post_meta($post->ID, 'nofollow4post', true) == 1 && $over_ride == 1)
                                         {
                                         $proceed_nofollow = false;
                                         }
                                 }
                               if($proceed_nofollow == true && $totalDaysNofollow >= $cat_date)
                                 {
                                 $content = make_nofollow( $content );
                                 }
                       }
}
return $content;
}
}
if(!function_exists('make_nofollow'))
{
function make_nofollow( $text ) {
preg_match_all("/<a.*? href=\"(.*?)\".*?>(.*?)<\/a>/i", $text, $matches);
for($i=0;$i<count($matches[0]);$i++)
 {
  if(!preg_match("/rel=[\"\']*nofollow[\"\']*/",$matches[0][$i]))
   {
 // $replaced = str_replace('<a ','<a rel="nofollow"',$matches[0][$i]);
  //$text = str_replace($matches[0][$i],$replaced,$text);
  preg_match_all("/<a.*? href=\"(.*?)\"(.*?)>(.*?)<\/a>/i", $matches[0][$i], $matches1);
  $text = str_replace(">".$matches1[3][0]."</a>"," rel='nofollow'>".$matches1[3][0]."</a>",$text);
   }
 }
return $text;
}
}
add_action('the_content', 'make_nofollow_links');
function build_catTree($name,$cats_nofollow_array,$edit_cats,$default = null)
{
$categories = get_categories($cats_nofollow_array);
echo '<select  onchange="changeCat();" name="'.$name.'">';
 foreach($categories as $key=>$val)
     {
        $selected = '';
         if($default != null)
          {
         $selected = $default == $val->cat_ID?' Selected ':'';
          }
        $bold = in_array($val->cat_ID,$edit_cats)?' style="font-weight:bold"':'';
        echo '<option value="'.$val->cat_ID.'" '.$bold.$selected.'>'.$val->cat_name.'</option>';
         }
echo '</select>';
}
//================================================
//taking the admin part into action
if(preg_match('/wp-admin/',$_SERVER['PHP_SELF']))
{
  class admin_makenofollow
     {
             function init_makenofollow()
               {
              add_action('admin_menu', array('admin_makenofollow', 'add_makenofollowoption_page'));
               }
              function add_makenofollowoption_page() //adding menu itme into option page
                   {
                                  if ( !function_exists('get_site_option') || is_admin() )

                          {
                                    add_options_page(__('NoLiP'),  __('NoLiP'), 7, str_replace("\\", "/", __FILE__), array('admin_makenofollow', 'display_makenofollow_settings'));
                                   }

                           }

                           function display_makenofollow_settings()
               {
               $makenofollow_url = $_SERVER['PHP_SELF'].'?page=NoLiP/'.basename(__FILE__);
			   
			   
			   if(isset($_POST['older_than_days']) && is_numeric($_POST['older_than_days']))
			   {
			   update_option('global_nofollow_settings',$_POST['older_than_days']);			   
			   
			   }			   
			   
               $default_older = get_option('global_nofollow_settings');
			   $default_older = trim($default_older) == '' || !is_numeric($default_older)?-1:$default_older;


                          if(isset($_POST['btnNofollowAction']))
                          {
                          $numberofdays = trim($_POST['num_days']);
                          $overrideindividual = isset($_POST['over_ride']) && $_POST['over_ride'] == 'ind_over'?1:0;

				$added_no_follow_cats = get_option('nofollow_cats');
                 $added_no_follow_cats = !is_array($added_no_follow_cats)?unserialize($added_no_follow_cats):$added_no_follow_cats;
                                        $added_no_follow_cats = !is_array($added_no_follow_cats)?array():$added_no_follow_cats;

                                if($_POST['btnNofollowAction']  == 'DELETE')
                                  {
                                    foreach($added_no_follow_cats as $key=>$value)
                                          {
                                           if($_POST['nofollow_cat'] == $key)
                                             {

                                                 unset($added_no_follow_cats[$key]);
                                                 update_option('nofollow_cats',serialize($added_no_follow_cats));
                                                 $deleted = true;
                                                 $error = "Options Deleted";
                                                 }
                                          }
                                  }

                                if(!isset($deleted))
                                {
                           if(!is_numeric($numberofdays))
                            {
                                $error = "Enter Numeric Value in Number of Days field";
                                }

                                        $added_no_follow_cats[$_POST['nofollow_cat']]['numdays'] = $numberofdays;
                                        $added_no_follow_cats[$_POST['nofollow_cat']]['overrideind'] = $overrideindividual;
                                        update_option('nofollow_cats',serialize($added_no_follow_cats));
                                        $error = "Options Added/Updated";
                                }
			 }

                        if(!isset($added_no_follow_cats))
                        {
               //category options if any
			     $added_no_follow_cats = get_option('nofollow_cats');
                 $added_no_follow_cats = !is_array($added_no_follow_cats)?unserialize($added_no_follow_cats):$added_no_follow_cats;
                 $added_no_follow_cats = !is_array($added_no_follow_cats)?array():$added_no_follow_cats;
                        }

                   if(isset($_GET['nofollow_cat']))
               {
                $cat_nofollow = $_GET['nofollow_cat'];
               }

             $parent_cat_nofollow = isset($cat_nofollow) && !empty($cat_nofollow)?$cat_nofollow:min(get_all_category_ids());


                          if(isset($added_no_follow_cats[$parent_cat_nofollow]))
                          {
                          $nofollow_days = $added_no_follow_cats[$parent_cat_nofollow]['numdays'];
                          $over_checked = $added_no_follow_cats[$parent_cat_nofollow]['overrideind'] == 1?"checked":"";
                          $btnValue = "EDIT";
                          }
                          else
                          {
                          $nofollow_days = '';
                          $over_checked = "";
                          $btnValue = "ADD";
						  
                          }

                           ?><div class="wrap">

                           <h2>NoLiP - Options</h2><BR />
            <style type="text/css">
                        a.sm_button {
                        padding:4px;
                        display:block;
                        padding-left:25px;
                        background-repeat:no-repeat;
                        background-position:5px 50%;
                        text-decoration:none;
                        border:none;
                }

                a.sm_button:hover {
                        border-bottom-width:1px;
                }

               </style>

               <style type="text/css">
                        div#moremeta {
                                float:left;
                                width:300px;
                                margin-right:10px;
                               
                        }
                        div#advancedstuff {
                                width:770px;
                        }
                        div#poststuff {
                                margin-top:10px;
                        }
                        fieldset.dbx-box {
                                margin-bottom:5px;
                                
                        }

                        </style>
                        <!--[if lt IE 7]>
                        <style type="text/css">
                        div#advancedstuff {
                                width:735px;
                        }
                        </style>
                        <![endif]-->

                              <div id="poststuff" >
                                        <div id="moremeta">
                                                <div id="grabit" class="dbx-group" style="border:1px; 
                                                border-color: #000000;
                                border-style: dashed">
                                                        <fieldset id="sm_pnres" class="dbx-box">
                                                                <h3 class="dbx-handle">About this Plugin:</h3>
                                                                <div class="dbx-content">
                                                                        <a class="sm_button" href="http://www.thereseabloginmysoup.com/">Author URI</a>
                                                                        <a class="sm_button" href="http://www.theresabloginmysoup.com/wordpress-plugins/nolip/">Plugin Homepage</a>
                                                                        <a class="sm_button" href="http://www.twitter.com/">Follow me on Twitter</a>
                                                                       </div>

                                                        </fieldset>
                                                        <fieldset id="sm_smres" class="dbx-box">
                                                                <h3 class="dbx-handle">Like this Plugin? </h3>
                                                                <div class="dbx-content" style="margin-left: 10px;">
                                       If you like this plugin, please donate to our Adoption fund as a bonus you'll get your link on some huge sites!:
                                       <div id="ScratchBackWidget" align="center">
<script type="text/javascript" src="http://www.scratchback.com/widget.php?id=513385ce-5e7c-d964-f17f-f2bf06efe92f"></script>
</div>
<center><strong>Donate and Get YOUR Link on These Great Sites:</strong></center>
<ul><li><a href="http://www.twtfollow.com">TwtFollow - Get More Twitter Followers!</a></li>
<li><a href="http://www.theresabloginmysoup.com">There's a Blog in my Soup: Social Media tips for a Social World!</a></li>
<li><font color="red">PLUS - EVERY WORDPRESS ADMIN AREA that has this plugin installed!	</font></li></ul>
                                                                </div>
                                                        </fieldset>
                                                </div>        </div>


                                                 <div class="dbx-c-ontent-wrapper" >
                                                                        <div class="dbx-content">
<div align="center">
<h1>NoFollow Links in Posts - Reloaded!</h1>
<table width='60%'><tr><td>This plugin will give you some control over how you use nofollow.
<p>If you don't use nofollow your Google rankings will surely plummett. 
However there are sometimes when you need to follow links, for instance sometimes sponsored blog 
posts from sponsoredreviews.com requires you to give link love to their advertisers.</p>
<p>Don't think no follow matters? I had a blog with a PR of 0 for 2 years, then added nofollow to 
EVERY LINK on the blog (except where I agreed not to for a sponsor - and my google pagerank went upto 3).</p>
<p>Tip: When choosing how many days to wait, the minimum is currently set at 1 day. Setting this value to 0 will not work!</p>

 </td></tr></table></div><center><h1>Plugin Settings</h1></center>
                           <form method="post" name="makenofollow" onsubmit="return check_form();">
<center>                        <table width="65%" style="border:thin; border-style: dashed;" >
                           <caption style="color:#FF0000">
                           <?php
                           if(isset($error))
                           {
                           echo $error;
                           }
                           ?>
                           </caption>
                           <tr><td width="60%">Enable nofollow to links in posts older than<input type="text" value="<?php echo $nofollow_days; ?>" name="num_days" size="5" /> days in</td><td align="left"><?php

$dropdown_options_nofollow = array('hide_empty' => 0, 'hierarchical' => 1, 'name'=>'nofollow_cat','show_count' => 0, 'orderby' => 'ID', 'selected' => $cat_nofollow);
build_catTree('nofollow_cat',$dropdown_options_nofollow,array_keys($added_no_follow_cats),$parent_cat_nofollow);
?></td></tr>

<tr><td>Enable individual post to override the settings<input type="checkbox" name="over_ride" value="ind_over" <?php echo $over_checked;?> /></td><td><input type="submit" value="<?php echo $btnValue;?>" name="btnNofollowAction" />&nbsp;<?php
if($btnValue == 'EDIT')
{
?>
<input type="submit" value="DELETE" name="btnNofollowAction" />
<?php
}
 ?> </form></td></tr>

<tr><td width="60%">
<form method="post" onsubmit="return checkOlderDays();" name="new_form">
Enable nofollow to links in ALL posts older than <input type="text" name="older_than_days" size="3" value="<?php echo $default_older;?>" /> days<br /><font size="2"><strong><font color="red">NOTE:</font> If you enable this setting, all the nofollow settings for individual posts and posts within categories will be ignored.</strong></font></td><td><input type="submit" value="APPLY" /> [Put -1 for no effect]

</form>
</td></tr></table>	   
	</center>					   
						   
						   
						   
						   
                         
						   
						   
						   
						   
						   </div></div>
                                     </div>
<p>
<table align="center" width="75%">
<tr><td colspan="7" align="center"><strong>Support this Plugin: Visit our Sponsors!</strong></td></tr>
<tr>
<td align="center"><a href="http://www.theresabloginmysoup.com/go/woothemes/">
<img src="http://www.woothemes.com/ads/woothemes-125x125-1.gif" border=0 alt="WooThemes - Premium WordPress Themes Club" width=125 height=125></a></td>
<td align="center"><a href="http://thirtydealsamonth.com/a.php?a=CD10123&b=25367&d=0&l=0&o=&p=0&c=4110&s1=&s2=&s3=&s4=&s5="><img src="http://users.marketleverage.com/42/10123/25367/" alt="" border="0"></a></td>
<td align="center"><a href="http://www.theresabloginmysoup.com/go/bluehostside/" target="_blank"><img src="http://www.ftjcfx.com/jd77snrflj47EE7BEA4658DDACD" alt="Host Unlimited Domains on 1 Account" border="0"/></a></td>
<td align="center"><a href="http://www.theresabloginmysoup.com/go/aweber/" title="Email Marketing"><img src="http://www.aweber.com/banners/email_marketing/125x125_an.gif" alt="Email Marketing $19/Month!" style="border:none;" /></a></td>
<td align="center"><a href="http://www.theresabloginmysoup.com/go/shoemoney/"><img src="http://www.theresabloginmysoup.com/shoemoneyaff.gif"></a></td>
<td align="center"><a href="http://revtwt.com/index.php?id=5674"><img src="http://revtwt.com/images/TwtAd_referral.jpg"></a></td>
<td align="center"><a href="http://www.tweetlater.com/86475-0-1-3.html" target="_blank"><img border="0" src="http://www.tweetlater.com/idevaffiliate/banners/tl_125_125_01.gif" width="125" height="125"></a></td>                                     

</tr> </table>
						   
				</p>	<br /><br /><p align="center">This plugin brought to you buy <a href="http://www.twtfollow.com">TwtFollow&copy;2009</a> || <a href="http://www.theresabloginmysoup.com">There's a Blog in my Soup&copy;2009</a></p>

                           <script language="javascript">
function changeCat()
{
  if(<?php echo $parent_cat_nofollow;?> != document.makenofollow.nofollow_cat.value)
   {
 window.location = '<?php echo $makenofollow_url;?>&nofollow_cat='+document.makenofollow.nofollow_cat.value;
   }
}

function check_form()
{
   if(document.makenofollow.num_days.value == "")
   {
   alert("Enter Number Of Days");
   document.makenofollow.num_days.focus();
   return false;
   }
  else if(isNaN(document.makenofollow.num_days.value))
   {
   alert("Enter Numeric Values Only");
   document.makenofollow.num_days.focus();
   return false;
   }
  else if(document.makenofollow.num_days.value <= 0)
   {
   alert("Enter Greater Than Zero");
   document.makenofollow.num_days.focus();
   return false;
   }
   return true;
}

function checkOlderDays()
{
if(document.new_form.older_than_days.value == "")
   {
   alert("Enter Number Of Days");
   document.new_form.older_than_days.focus();
   return false;
   }
  else if(isNaN(document.new_form.older_than_days.value))
   {
   alert("Enter Numeric Values Only");
   document.new_form.older_than_days.focus();
   return false;
   }
   return true;
}
</script>
                           <?php

                           }
  function advanced_makefollow()
{
 if (substr($GLOBALS['wp_version'], 0, 3) >= 2.5) { ?>
                <div id="makenofollow" class="postbox closed">
                <h3>Nofollow Links in Posts - Advanced Option</h3>
                <div class="inside">
                <div id="makenofollow">
                <?php } else { ?>
                <div class="dbx-b-ox-wrapper">
                <fieldset id="makenofollowdiv" class="dbx-box">
                <div class="dbx-h-andle-wrapper">
                <h3 class="dbx-handle">Nofollow Links in Posts</h3>
                </div>
                <div class="dbx-c-ontent-wrapper">
                <div class="dbx-content">
                <?php
}
?>
<input type="checkbox" name="always_dofollow" value="1" <?php
if(isset($post->ID) && get_post_meta($post->ID, 'nofollow4post', true) == 1)
{
echo " checked";
}
?> />&nbsp;Always DoFollow Links
<?php
                 if (substr($GLOBALS['wp_version'], 0, 3) == '2.5') { ?>
                </div></div></div>
                <?php } else { ?>
                </div>
                </fieldset>
                </div>
                <?php }

     }


function nofollow2posts($id)
{
$post_option_nofollow = isset($_POST['always_dofollow']) && $_POST['always_dofollow'] == '1'?1:0;
delete_post_meta($id, 'nofollow4post');
add_post_meta($id, 'nofollow4post', $post_option_nofollow);
}




}



admin_makenofollow::init_makenofollow();
if (substr($GLOBALS['wp_version'], 0, 3) >= 2.5) {
        add_action('edit_form_advanced', array('admin_makenofollow', 'advanced_makefollow'));
} else {
        add_action('dbx_post_advanced', array('admin_makenofollow', 'advanced_makefollow'));

}


add_action('edit_post', array('admin_makenofollow', 'nofollow2posts'));
add_action('publish_post', array('admin_makenofollow', 'nofollow2posts'));
add_action('save_post', array('admin_makenofollow', 'nofollow2posts'));

}
?>
