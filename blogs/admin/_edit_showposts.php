<?php
/**
 * This file implements the UI view for the post browsing screen.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );
?>
	<div class="NavBar">
	<?php
	/**
	 * Includes:
	 */
	require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_itemlist.class.php' );
	require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_calendar.class.php' );
	require_once( dirname(__FILE__).'/'.$admin_dirout.$core_subdir.'_archivelist.class.php' );


	/*
	 * @movedTo _b2browse.php
	 */

	// Display title depending on selection params:
	request_title( '<h2>', '</h2>', '<br />', 'htmlbody', true, true, 'b2browse.php', 'blog='.$blog );

	if( !$posts )
	{
		if( $posts_per_page )
		{
			$posts = $posts_per_page;
		}
		else
		{
			$posts = 10;
			$posts_per_page = $posts;
		}
	}

	if( !$poststart )
	{
		$poststart = 1;
	}

	if( !$postend )
	{
		$postend = $poststart + $posts - 1;
	}

	$nextXstart = $postend + 1;
	$nextXend = $postend + $posts;

	$previousXstart = ($poststart - $posts);
	$previousXend = $poststart - 1;
	if( $previousXstart < 1 )
	{
		$previousXstart = 1;
	}

	require dirname(__FILE__). '/_edit_navbar.php';
	?>
	</div>
	<?php
	while( $Item = $MainList->get_item() )
	{
		?>
		<div class="bPost<?php $Item->status( 'raw' ) ?>" lang="<?php $Item->lang() ?>">
			<?php
			// We don't switch locales in the backoffice, since we use the user pref anyway
			$Item->anchor(); ?>
			<div class="bSmallHead">
				<?php

 					echo '<div class="bSmallHeadRight">';
					locale_flag( $Item->locale, 'h10px' );
					echo '</div>';

					echo '<strong>';
					$Item->issue_date(); echo ' @ '; $Item->issue_time();
					echo '</strong>';
					// TRANS: backoffice: each post is prefixed by "date BY author IN categories"
					echo ' ', T_('by'), ' ';
					$Item->Author->prefered_name();
					echo ' (';
					$Item->Author->login();
					echo ', ', T_('level:');
					$Item->Author->level();
					echo '), ';
					$Item->views();
					echo ' '.T_('views');

 					echo '<div class="bSmallHeadRight">';
					echo T_('Status').': ';
					echo '<span class="Status">';
					$Item->status();
					echo '</span>';
					echo '</div>';

					echo '<br />';
					$Item->type( T_('Type').': ', ' &nbsp; ' );
					$Item->assigned_to( T_('Assigned to:').' ' );

 					echo '<div class="bSmallHeadRight">';
					$Item->extra_status( T_('Extra').': ' );
					echo '</div>';

					echo '<br />'.T_('Categories').': ';
					$Item->categories( false );
				?>
			</div>

			<div class="bContent">
				<h3 class="bTitle"><?php $Item->title() ?></h3>
				<div class="bText">
					<?php
						$Item->content();
						link_pages( '<p class="right">'.T_('Pages:'), '</p>' );
					?>
				</div>
			</div>

			<div class="PostActionsArea">
				<a href="<?php $Item->permalink() ?>" title="<?php echo T_('Permanent link to full entry') ?>" class="permalink_right"><img src="img/chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
				<?php
 				// Display edit button if current user has the rights:
				$Item->edit_link( ' ', ' ', '#', '#', 'ActionButton', $edit_item_url );

				// Display publish NOW button if current user has the rights:
				$Item->publish_link( ' ', ' ', '#', '#', 'PublishButton');

				// Display delete button if current user has the rights:
				$Item->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, $delete_item_url );

				if( $Blog->allowcomments != 'never' )
				{ ?>
					<a href="b2browse.php?blog=<?php echo $blog ?>&amp;p=<?php $Item->ID() ?>&amp;c=1" class="ActionButton"><?php
					// TRANS: Link to comments for current post
					comments_number(T_('no comment'), T_('1 comment'), T_('%d comments'));
					trackback_number('', ' &middot; '.T_('1 Trackback'), ' &middot; '.T_('%d Trackbacks'));
					pingback_number('', ' &middot; '.T_('1 Pingback'), ' &middot; '.T_('%d Pingbacks'));
					?></a>
				<?php } ?>
			</div>

			<?php
			// ---------- comments ----------
			if( $c )
			{ // We have request display of comments
				?>
   			<div class="bFeedback">
				<a name="comments"></a>
				<h4><?php echo T_('Comments'), ', ', T_('Trackbacks'), ', ', T_('Pingbacks') ?>:</h4>
				<?php

				$CommentList = & new CommentList( 0, "'comment','trackback','pingback'", $show_statuses, $Item->ID, '', 'ASC' );

				$CommentList->display_if_empty(
											'<div class="bComment"><p>' .
											T_('No feedback for this post yet...') .
											'</p></div>' );

				while( $Comment = $CommentList->get_next() )
				{ // Loop through comments:
					?>
					<!-- ========== START of a COMMENT/TB/PB ========== -->
					<div class="bComment">
						<div class="bSmallHead">
							<?php
							$Comment->date();
							echo ' @ ';
							$Comment->time( 'H:i' );
							if( $Comment->author_url( '', ' &middot; Url: ', '' )
									&& $current_User->check_perm( 'spamblacklist', 'edit' ) )
							{ // There is an URL and we have permission to ban...
								// TODO: really ban the base domain! - not by keyword
								?>
								<a href="b2antispam.php?action=ban&amp;keyword=<?php
									echo urlencode(getBaseDomain($Comment->author_url))
									?>"><img src="img/noicon.gif" class="middle" alt="<?php echo /* TRANS: Abbrev. */ T_('Ban') ?>" title="<?php echo T_('Ban this domain!') ?>" /></a>&nbsp;
								<?php
							}
							$Comment->author_email( '', ' &middot; Email: ' );
							$Comment->author_ip( ' &middot; IP: ' );
						 ?>
						</div>
						<div class="bCommentContent">
						<div class="bCommentTitle">
						<?php
							switch( $Comment->get( 'type' ) )
							{
								case 'comment': // Display a comment:
									echo T_('Comment from:') ?>
									<?php break;

								case 'trackback': // Display a trackback:
									echo T_('Trackback from:') ?>
									<?php break;

								case 'pingback': // Display a pingback:
									echo T_('Pingback from:') ?>
									<?php break;
							}
						?>
						<?php $Comment->author() ?>
						</div>
						<div class="bCommentText">
							<?php $Comment->content() ?>
						</div>
						</div>
						<div class="CommentActionsArea">
						<a href="<?php $Comment->permalink() ?>" title="<?php echo T_('Permanent link to this comment')	?>" class="permalink_right"><img src="img/chain_link.gif" alt="<?php echo T_('Permalink') ?>" width="14" height="14" border="0" class="middle" /></a>
						<?php
			 				// Display edit button if current user has the rights:
							$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton');

							// Display delete button if current user has the rights:
							$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton');
						?>
						</div>

					</div>
					<!-- ========== END of a COMMENT/TB/PB ========== -->
					<?php //end of the loop, don't delete
				}

				if( $Item->can_comment() )
				{ // User can leave a comment
				?>
				<!-- ========== FORM to add a comment ========== -->
				<h4><?php echo T_('Leave a comment') ?>:</h4>

				<?php

				$Form = & new Form( $htsrv_url.'comment_post.php', '' );

				$Form->begin_form( 'bComment' );

				$Form->hidden( 'comment_post_ID', $Item->ID );
				$Form->hidden( 'redirect_to', htmlspecialchars($ReqURI) );
				?>
					<fieldset>
						<div class="label"><?php echo T_('User') ?>:</div>
						<div class="info">
							<strong><?php $current_User->prefered_name()?></strong>
							<?php user_profile_link( ' [', ']', T_('Edit profile') ) ?>
							</div>
					</fieldset>
				<?php
				$Form->textarea( 'comment', '', 12, T_('Comment text'), T_('Allowed XHTML tags').': '.htmlspecialchars(str_replace( '><',', ', $comment_allowed_tags)).'<br />'.T_('URLs, email, AIM and ICQs will be converted automatically.'), 40, 'bComment' );

				if(substr($comments_use_autobr,0,4) == 'opt-')
				{
					echo $Form->fieldstart;
					echo $Form->labelstart;
				?>
				<label><?php echo T_('Options') ?>:</label>

				<?php
					echo $Form->labelend;
					echo $Form->inputstart;
					$Form->checkbox( 'comment_autobr', 1, T_('Auto-BR'), T_('(Line breaks become &lt;br&gt;)'), 'checkbox' );
					echo $Form->inputend;
					$Form->fieldset_end();

				}

					echo $Form->fieldstart;
					echo $Form->inputstart;
					$Form->submit( array ('submit', T_('Send comment'), 'SaveButton' ) );
					echo $Form->inputend;
					$Form->fieldset_end();

				?>

					<div class="clear"></div>
				<?php
					$Form->end_form();
				?>
				<!-- ========== END of FORM to add a comment ========== -->
				<?php
				} // / can comment
			?>
			</div>
			<?php
		} // / comments requested
	?>
	</div>
	<?php
	}

	if( $MainList->get_total_num_posts() )
	{ // don't display navbar twice if we have no post
	?>
	<div class="NavBar">
		<?php require dirname(__FILE__). '/_edit_navbar.php'; ?>
	</div>
	<?php } ?>



<p class="center">
  <a href="<?php echo $add_item_url ?>"><img src="img/new.gif" width="13" height="13" class="middle" alt="" />
    <?php echo T_('New post...') ?></a>
</p>

<?php
/*
 * @movedTo _browse_posts_sidebar.inc.php
 */

/*
 * $Log$
 * Revision 1.95  2005/03/11 12:40:16  fplanque
 * multiple browsing views, part ONE
 *
 */
?>
