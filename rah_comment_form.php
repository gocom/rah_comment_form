<?php	##################
	#
	#	rah_comment_form-plugin for Textpattern
	#	version 0.3
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	function rah_comment_form($atts=array(),$thing='') {
		global $is_article_list, $ign_user;
		if($ign_user) {
			$sep = (safe_field('val','txp_prefs',"name='permlink_mode'") == 'messy') ? '&' : '?';
			if($is_article_list == false) {
				$form =
					'	<form id="rah_comment_form" action="'.permlink(array()).$sep.'rah_comment_save=1" method="post">'.n.
					(($thing) ? parse($thing) : 
					'		<ul>'.n.
					'			<li class="label"><label for="rah_message">'.gTxt('comment_message').'</label></li>'.n.
					'			<li class="textarea"><textarea name="rah_comment_message" id="rah_message" rows="6" cols="20"></textarea></li>'.n.
					'			<li class="button"><button type="submit">'.gTxt('submit').'</button></li>'.n.
					'		</ul>'.n).
					'	</form>'.n;
				
				if(gps('rah_comment_save') == '1' && ps('rah_comment_message')){
					$id = article_id(array());
					$ip = doSlash(serverset('REMOTE_ADDR'));
					$message = ps('rah_comment_message');
					$message = substr(trim($message), 0, 65535);
					$message = doSlash(markup_comment($message));
					$name = doSlash($ign_user);

					$check = safe_field("message","txp_discuss","name='$name' and parentid='$id' and message='$message' and ip='$ip'");
					if($check) header('Location: '.permlink(array()));
					else {
						safe_insert(
							"txp_discuss","
								parentid = $id,
								name = '$name',
								ip = '$ip',
								message = '$message',
								visible = 1,
								posted = now()"
						);
						$count = fetch('comments_count','textpattern','ID',$id);
						$count = ($count) ? $count : 0;
						$count = $count+1;
						safe_update(
							'textpattern',
							"comments_count='".doSlash($count)."'",
							"ID='$id'"
						);

						//Lets redirect

						$cid = safe_field("discussid","txp_discuss","name='$name' and parentid='$id' and message='$message' and ip='$ip'");
						header('Location: '.permlink(array()).$sep.'commented=1#c'.$cid);
					}
				}
				return $form;
			}
		}
	}

	function rah_comment_message() {
		return '<textarea name="rah_comment_message" rows="6" cols="20"></textarea>';
	} ?>